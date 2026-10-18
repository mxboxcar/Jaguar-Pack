<?php

namespace Tests\Feature\Settings;

use App\Domain\Settings\Input;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * write path ของกลุ่ม settings (ไม่แตะ DB จริง: DB facade = ตัวบันทึก; middleware ถูกปิด — สิทธิ์ตรวจใน SettingsRoutesTest)
 */
class SettingsSaveTest extends TestCase
{
    private const KEYS = [
        'rate_order', 'rate_alipay', 'rate_transfer', 'bank_acc', 'bank_no', 'bank_name', 'bank_branch', 'change_sender',
        'calculate_woodprotect', 'address_cn', 'phone_cn', 'phone_th', 'exchange_rate', 'today_lot', 'today_lot_yellow', 'today_lot_green',
    ];

    /** แถว setting จำลอง (key => value) — DB facade ถูกแทนด้วยตัวบันทึก เพราะเครื่องไม่มี pdo_sqlite และ test ห้ามแตะ DB จริง */
    private array $rows = [];

    /** ตารางที่ถูกเขียน (insert/update) นอกจาก setting */
    private array $writes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        foreach ([...self::KEYS, 'vat', 'ban_ip'] as $k) {
            $this->rows[$k] = 'orig-'.$k;
        }
        $test = $this;
        DB::shouldReceive('transaction')->andReturnUsing(fn (callable $cb) => $cb());
        DB::shouldReceive('raw')->andReturnUsing(fn ($v) => $v);
        DB::shouldReceive('table')->andReturnUsing(function (string $table) use ($test) {
            return new class($test, $table)
            {
                private array $wheres = [];

                public function __construct(private SettingsSaveTest $test, private string $table) {}

                public function where($col, $val = null): static
                {
                    $this->wheres[$col] = $val;

                    return $this;
                }

                public function update(array $values): int
                {
                    return $this->test->record($this->table, 'update', $this->wheres, $values);
                }

                public function insertGetId(array $values): int
                {
                    return $this->test->record($this->table, 'insert', [], $values);
                }

                public function __call($name, $args)
                {
                    return $this->test->record($this->table, $name, $this->wheres, $args);
                }
            };
        });
    }

    public function record(string $table, string $op, array $wheres, array $values): int
    {
        if ($table === 'setting' && $op === 'update' && array_keys($wheres) === ['key'] && array_keys($values) === ['value']) {
            if (! array_key_exists($wheres['key'], $this->rows)) {
                return 0;
            }
            $this->rows[$wheres['key']] = $values['value'];

            return 1;
        }
        $this->writes[] = [$table, $op];

        return 1;
    }

    private function settings(): array
    {
        return $this->rows;
    }

    public function test_empty_body_changes_nothing(): void
    {
        $before = $this->settings();
        $res = $this->post('/_admin/_setting/save', []);
        $res->assertOk();
        $this->assertStringStartsWith('ErrorBox(', $res->getContent());
        $this->assertSame($before, $this->settings());
    }

    public function test_query_string_or_array_values_do_not_count_as_fields(): void
    {
        $before = $this->settings();
        $this->post('/_admin/_setting/save?rate_order=9', ['bank_no' => ['x']])->assertOk();
        $this->assertSame($before, $this->settings());
    }

    public function test_only_keys_present_are_updated(): void
    {
        $before = $this->settings();
        $res = $this->post('/_admin/_setting/save', ['rate_order' => '6.10', 'bank_no' => '', 'vat' => '99']);
        $this->assertStringStartsWith('SuccessBox(', $res->getContent());
        $after = $this->settings();
        $this->assertSame('6.10', $after['rate_order']);
        $this->assertSame('', $after['bank_no']);        // ช่องที่ส่งมาเป็นค่าว่าง = บันทึก '' แบบเดิม
        $this->assertSame('orig-vat', $after['vat']);    // คีย์นอกรายการ 16 ตัวไม่แตะ
        unset($before['rate_order'], $before['bank_no'], $after['rate_order'], $after['bank_no']);
        $this->assertSame($before, $after);
    }

    public function test_full_form_updates_all_sixteen_keys_with_legacy_backslash_rule(): void
    {
        $post = [];
        foreach (self::KEYS as $k) {
            $post[$k] = "new'\\-".$k;
        }
        $this->post('/_admin/_setting/save', ['id' => '', ...$post])->assertOk();
        $after = $this->settings();
        foreach (self::KEYS as $k) {
            $this->assertSame("new'-".$k, $after[$k]); // เดิม: \ ถูกตัด, ' ลง DB เป็น '
        }
        $this->assertSame('orig-ban_ip', $after['ban_ip']);
    }

    public function test_product_and_shipping_type_save_refuse_a_body_without_name(): void
    {
        foreach (['_admin/_product_type/save', '_admin/_shipping_type/save'] as $url) {
            $res = $this->post('/'.$url, ['id' => '']);
            $this->assertStringStartsWith('ErrorBox(', $res->getContent(), $url);
        }
        $this->assertSame([], $this->writes);
    }

    public function test_js_eval_escapes_two_levels(): void
    {
        $this->assertSame('2', Input::jsEval('2'));
        // ' ต้องเหลือเป็น \' หลัง string "..." ชั้นนอกถูก parse (ชั้นเดียว = หลุดออกจาก '...' ตอน eval)
        $this->assertSame("\\\\\\',alert(1),\\\\\\'", Input::jsEval("',alert(1),'"));
        $this->assertStringNotContainsString('</', Input::jsEval('</script>'));
        $this->assertSame('\\"', Input::jsEval('"'));
    }
}
