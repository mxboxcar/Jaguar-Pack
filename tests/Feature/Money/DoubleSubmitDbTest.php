<?php

namespace Tests\Feature\Money;

use App\Http\Controllers\Balance\BalanceCnController;
use App\Http\Controllers\Balance\BalanceController;
use App\Http\Controllers\MoneyMove\Admin\AdminRefillController;
use App\Http\Controllers\MoneyMove\RefillController;
use App\Http\Controllers\MoneyMove\TransferController;
use App\Http\Controllers\Shop\TaobaoController;
use App\Http\Middleware\LegacySubmitOnce;
use App\Models\User;
use App\Support\Legacy\Lookup;
use App\Support\SubmitGuard;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * กันกดซ้ำ/ส่งซ้อนของ endpoint สร้างรายการที่ตัดเงิน (E2E: ดับเบิลคลิก _taobao/save = 2 รายการ ตัดเงิน 2 ครั้ง)
 * เรียกผ่าน middleware legacy.once + controller จริง กับ admin_jl_bench ใน transaction ที่ rollback (opt-in)
 * "พร้อมกัน" จำลองด้วยการถือ lock ของคำขอแรกไว้ (process เดียวยิงซ้อนจริงไม่ได้ — ยิงจริงด้วย curl ในรายงาน)
 */
class DoubleSubmitDbTest extends BenchDbTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** POST ผ่าน legacy.once แล้วเข้า controller (แบบที่ route ทำ) */
    private function submit(string $uri, string $controller, string $method, array $post, string ...$opts): string
    {
        $req = Request::create('/'.$uri, 'POST', $post);
        $req->headers->set('X-Requested-With', 'XMLHttpRequest');
        $route = new Route(['POST'], $uri, []);
        $req->setRouteResolver(fn () => $route);
        app()->instance('request', $req);

        return (string) (new LegacySubmitOnce)->handle($req, fn (Request $r) => app($controller)->{$method}($r), ...$opts)->getContent();
    }

    private function taobaoPost(string $price, string $remark = 'zz_dbl'): array
    {
        return ['id' => '0', 'order' => ['zz_order_1'], 'price' => [$price], 'taobao_name' => 'zz', 'taobao_password' => 'zz', 'remark' => $remark];
    }

    private function rows(string $table, User $u): int
    {
        return DB::table($table)->where('user', $u->id)->count();
    }

    public function test_double_click_taobao_creates_one_order_and_deducts_once(): void
    {
        $cust = $this->zzUser();
        $this->credit($cust, 100000);
        $this->actAs($cust);
        $before = $this->cash($cust);

        $first = $this->submit('_taobao/save', TaobaoController::class, 'save', $this->taobaoPost('10'));
        $this->assertStringContainsString('SuccessBox', $first);
        $afterOne = $this->cash($cust);
        $this->assertLessThan($before, $afterOne);

        // คลิกที่สองหลังคำขอแรกเสร็จ (payload เดิม) → ส่งซ้ำ
        $second = $this->submit('_taobao/save', TaobaoController::class, 'save', $this->taobaoPost('10'));
        $this->assertStringContainsString('ErrorBox(', $second);
        $this->assertStringContainsString(SubmitGuard::DUPLICATE, $second);

        // คลิกที่สองระหว่างคำขอแรกยังทำงาน (ถือ lock ของผู้ใช้+route) → กำลังดำเนินการ
        $lock = Cache::lock('submit:'.$cust->id.':_taobao/save', 30);
        $this->assertTrue($lock->get());
        $busy = $this->submit('_taobao/save', TaobaoController::class, 'save', $this->taobaoPost('20'));
        $lock->release();
        $this->assertStringContainsString(SubmitGuard::BUSY, $busy);

        $this->assertSame(1, $this->rows('taobao', $cust), 'ต้องมีรายการเดียว');
        $this->assertSame($afterOne, $this->cash($cust), 'ตัดเงินครั้งเดียว');

        // รายการต่างกันต่อกัน (ไม่ใช่ซ้ำ) ยังทำได้ตามปกติ
        $third = $this->submit('_taobao/save', TaobaoController::class, 'save', $this->taobaoPost('30'));
        $this->assertStringContainsString('SuccessBox', $third);
        $this->assertSame(2, $this->rows('taobao', $cust));
        $this->assertLessThan($afterOne, $this->cash($cust));
    }

    public function test_failed_submit_is_not_remembered_so_the_user_can_retry(): void
    {
        $cust = $this->zzUser();
        $this->actAs($cust);
        // ยอดไม่พอ → ErrorBox (ไม่จำ payload) → เติมเงินแล้วส่ง payload เดิมได้ทันที
        $this->assertStringContainsString('ErrorBox(', $this->submit('_taobao/save', TaobaoController::class, 'save', $this->taobaoPost('10')));
        $this->assertSame(0, $this->rows('taobao', $cust));
        $this->credit($cust, 100000);
        $this->assertStringContainsString('SuccessBox', $this->submit('_taobao/save', TaobaoController::class, 'save', $this->taobaoPost('10')));
        $this->assertSame(1, $this->rows('taobao', $cust));
    }

    public function test_transfer_double_click_and_balance_recheck(): void
    {
        $cust = $this->zzUser();
        $rate = (float) Lookup::getSetting('rate_transfer');
        $this->assertGreaterThan(0, $rate);
        $this->credit($cust, round(150 * $rate, 2)); // พอสำหรับ ¥100 ครั้งเดียว
        $this->actAs($cust);
        $post = ['total' => '100', 'acc_name' => 'zz_acc', 'acc_no' => '1', 'acc_bank' => 'zz', 'acc_branch' => 'zz', 'remark' => 'zz_dbl', 'upload_file' => ''];

        $this->assertStringContainsString('SuccessBox', $this->submit('_transfer/save', TransferController::class, 'save', $post));
        $this->assertStringContainsString(SubmitGuard::DUPLICATE, $this->submit('_transfer/save', TransferController::class, 'save', $post));
        $this->assertSame(1, $this->rows('bill_transfer', $cust));

        // คำขอต่างกัน (remark ต่าง) แต่ยอดเหลือไม่พอ → ตรวจยอดภายใต้ล็อกแถว user แล้วปฏิเสธ ไม่ติดลบ
        $out = $this->submit('_transfer/save', TransferController::class, 'save', ['remark' => 'zz_dbl_2'] + $post);
        $this->assertStringContainsString('ErrorBox(', $out);
        $this->assertSame(1, $this->rows('bill_transfer', $cust));
        $this->assertGreaterThanOrEqual(0.0, $this->cash($cust));
        $this->assertSame(round(150 * $rate - 100 * $rate, 2), $this->cash($cust));
    }

    public function test_withdraw_double_click_and_comma_amounts(): void
    {
        $cust = $this->zzUser();
        $this->credit($cust, 5000);
        $this->actAs($cust);
        $post = fn (string $amount) => ['wantcash' => $amount, 'bank_no' => '123', 'branch_bank' => 'zz', 'branch_name' => 'zz test'];
        if (! perm('balance')) {
            $this->markTestSkipped('role ลูกค้าใน bench ไม่มีสิทธิ์ balance');
        }

        // "1,00" กำกวม → ปฏิเสธ (เดิมลง DB เป็น 1.00 เงียบๆ)
        $bad = $this->submit('_balance/save_withdraw', BalanceController::class, 'saveWithdraw', $post('1,00'));
        $this->assertStringContainsString('ErrorBox(', $bad);
        $this->assertSame(0, $this->rows('balance_withdraw', $cust));

        // "1,000.50" (ตัวคั่นหลักพัน) → 1000.50
        $this->assertStringContainsString('SuccessBox', $this->submit('_balance/save_withdraw', BalanceController::class, 'saveWithdraw', $post('1,000.50')));
        $this->assertSame('1000.50', (string) DB::table('balance_withdraw')->where('user', $cust->id)->value('amount'));
        $this->assertSame(3999.5, $this->cash($cust));

        // กดซ้ำ → ไม่มีรายการที่สอง ไม่ตัดซ้ำ
        $this->assertStringContainsString(SubmitGuard::DUPLICATE, $this->submit('_balance/save_withdraw', BalanceController::class, 'saveWithdraw', $post('1,000.50')));
        $this->assertSame(1, $this->rows('balance_withdraw', $cust));
        $this->assertSame(3999.5, $this->cash($cust));

        // ถอนเกินยอดคงเหลือ → ปฏิเสธ
        $this->assertStringContainsString('ErrorBox(', $this->submit('_balance/save_withdraw', BalanceController::class, 'saveWithdraw', $post('4000')));
        $this->assertSame(3999.5, $this->cash($cust));
    }

    public function test_cny_exchange_double_click(): void
    {
        $cust = $this->zzUser();
        $this->credit($cust, 500, 'balance_cn');
        $this->actAs($cust);
        if (! perm('balance_cn')) {
            $this->markTestSkipped('role ลูกค้าใน bench ไม่มีสิทธิ์ balance_cn');
        }
        $this->assertStringContainsString('SuccessBox', $this->submit('_balance_cn/save_withdraw', BalanceCnController::class, 'saveWithdraw', ['wantcash' => '200']));
        $this->assertStringContainsString(SubmitGuard::DUPLICATE, $this->submit('_balance_cn/save_withdraw', BalanceCnController::class, 'saveWithdraw', ['wantcash' => '200']));
        $this->assertSame(1, $this->rows('balance_withdraw_cn', $cust));
        $this->assertSame(300.0, $this->cash($cust, 'balance_cn'));
    }

    public function test_refill_double_click_creates_one_request(): void
    {
        $cust = $this->zzUser();
        $this->actAs($cust);
        $post = ['amount' => '500', 'date' => '1 January 2026 | 10:00', 'remark' => 'zz_dbl', 'upload_file' => ''];
        $this->assertStringContainsString('SuccessBox', $this->submit('_refill/save', RefillController::class, 'save', $post));
        $this->assertStringContainsString(SubmitGuard::DUPLICATE, $this->submit('_refill/save', RefillController::class, 'save', $post));
        $this->assertSame(1, $this->rows('refill', $cust));
        // "1,00" กำกวม → ปฏิเสธ
        $this->assertStringContainsString('ErrorBox(', $this->submit('_refill/save', RefillController::class, 'save', ['amount' => '1,00'] + $post));
        $this->assertSame(1, $this->rows('refill', $cust));
    }

    public function test_admin_approving_a_cancelled_refill_is_an_error(): void
    {
        $cust = $this->zzUser();
        $admin = $this->zzUser(1);
        $id = $this->insertRow('refill', ['status' => '-1', 'user' => $cust->id, 'amount' => '100.00', 'date' => DB::raw('NOW()'),
            'attachment' => '', 'remark' => 'zz_dbl', 'approved_remark' => '']);
        $this->actAs($admin);
        $before = $this->cash($cust);
        $out = $this->act(AdminRefillController::class, 'saveApproved', ['id' => $id]);
        $this->assertStringContainsString('ErrorBox(', $out);
        $this->assertStringNotContainsString('SuccessBox', $out);
        $this->assertSame('-1', (string) DB::table('refill')->where('id', $id)->value('status'));
        $this->assertSame($before, $this->cash($cust));
    }
}
