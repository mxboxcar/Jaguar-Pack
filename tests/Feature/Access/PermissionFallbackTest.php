<?php

namespace Tests\Feature\Access;

use App\Support\Legacy\Permissions;
use Tests\Concerns\FakeLegacyUser;
use Tests\TestCase;

/**
 * config legacy.permission_fallbacks — สิทธิ์ที่ไม่มี role ใดถือ ได้มาจากสิทธิ์ต้นทางของงานเดียวกัน (ไม่แก้ DB)
 */
class PermissionFallbackTest extends TestCase
{
    use FakeLegacyUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpLegacyWorld();
    }

    /** ทุกสิทธิ์กำพร้าที่ docs/CONVENTIONS.md ระบุต้องอยู่ใน config และต้นทางต้องไม่ใช่สิทธิ์กำพร้าด้วยกัน */
    public function test_documented_orphans_are_mapped_to_real_permissions(): void
    {
        $map = config('legacy.permission_fallbacks');
        foreach ([
            'admin_point', 'admin_withdrawpoint', 'admin_nobilling', 'admin_balance_company',
            'admin_balance_company_add', 'admin_balance_company_delete', 'admin_change_sender',
            'admin_mall_category',
        ] as $orphan) {
            $this->assertArrayHasKey($orphan, $map, $orphan);
            $this->assertNotEmpty($map[$orphan], $orphan);
            foreach ($map[$orphan] as $src) {
                $this->assertArrayNotHasKey($src, $map, "$orphan ← $src (ต้นทางต้องเป็นสิทธิ์ที่ role ถือจริง)");
            }
        }
    }

    public function test_holder_of_source_permission_gets_orphan(): void
    {
        $this->actAs(900, ['admin_exchange', 'admin_balance', 'admin_report'], ['admin_withdrawpoint', 'admin_point']);
        $p = $this->app->make(Permissions::class);

        $this->assertTrue($p->has('admin_withdrawpoint'), 'admin_withdrawpoint ← admin_exchange (แถว permission=0 ใน DB ไม่ถอน)');
        $this->assertTrue($p->has('admin_balance_company'));
        $this->assertTrue($p->has('admin_nobilling'));
        $this->assertFalse($p->has('admin_point'), 'ไม่มี admin_shop_cashback');
        $this->assertFalse($p->has('admin_balance_company_delete'), 'ไม่มี admin_balance_cn_add');
        $this->assertTrue(perm('admin_withdrawpoint'));
    }

    public function test_customer_gets_nothing(): void
    {
        $this->actAs(901, ['order', 'tracking', 'lot', 'payment', 'profile']);
        $p = $this->app->make(Permissions::class);
        foreach (array_keys(config('legacy.permission_fallbacks')) as $orphan) {
            $this->assertFalse($p->has($orphan), $orphan);
        }
    }

    public function test_fallback_is_one_level_only(): void
    {
        config(['legacy.permission_fallbacks' => ['a_orphan' => ['admin_balance'], 'b_orphan' => ['a_orphan']]]);
        $this->actAs(902, ['admin_balance']);
        $p = $this->app->make(Permissions::class);
        $this->assertTrue($p->has('a_orphan'));
        $this->assertFalse($p->has('b_orphan'));
    }

    public function test_can_be_disabled_and_db_grants_still_apply(): void
    {
        config(['legacy.permission_fallbacks_enabled' => false]);
        $this->actAs(903, ['admin_exchange', 'admin_point']);
        $p = $this->app->make(Permissions::class);
        $this->assertFalse($p->has('admin_withdrawpoint'));
        $this->assertTrue($p->has('admin_point'), 'สิทธิ์ที่ DB ให้จริงใช้ได้เสมอ');
    }

    public function test_first_row_wins_before_fallback(): void
    {
        // role_on_detail ซ้ำ: แถวแรก (permission=0) ชนะเหมือนเดิม แล้ว fallback ค่อยเพิ่ม
        $map = Permissions::build([
            (object) ['name' => 'admin_payment', 'permission' => '0'],
            (object) ['name' => 'admin_payment', 'permission' => '1'],
            (object) ['name' => 'Admin_Mall ', 'permission' => '1'],
        ]);
        $this->assertFalse($map['admin_payment']);
        $this->assertFalse($map['admin_change_sender'] ?? false);
        $this->assertTrue($map['admin_mall_category'], 'ชื่อสิทธิ์เทียบแบบไม่สนตัวพิมพ์/ช่องว่างท้ายเหมือน collation เดิม');
    }

    /** ตารางที่เอกสารให้เจ้าของรีวิว: role ใน admin_jl_bench ที่ได้แต่ละสิทธิ์ (ตาม role_on_detail ณ 2026-10-18) */
    public function test_mapping_matches_reviewed_table(): void
    {
        $roles = [
            1 => ['admin_shop_cashback', 'admin_exchange', 'admin_report', 'admin_balance', 'admin_balance_cn_add', 'admin_payment', 'admin_mall', 'admin_tracking'],
            4 => ['admin_tracking', 'admin_lot_view'],
            5 => ['admin_shop_cashback', 'admin_exchange', 'admin_report', 'admin_balance', 'admin_payment', 'admin_tracking'],
            6 => ['admin_mall', 'admin_tracking'],
            7 => ['admin_report', 'admin_payment', 'admin_tracking'],
        ];
        $expected = [
            'admin_point' => [1, 5], 'admin_withdrawpoint' => [1, 5], 'admin_nobilling' => [1, 5, 7],
            'admin_balance_company' => [1, 5], 'admin_balance_company_add' => [1], 'admin_balance_company_delete' => [1],
            'admin_change_sender' => [1, 5, 7], 'admin_mall_category' => [1, 6],
        ];
        foreach ($expected as $orphan => $want) {
            $got = [];
            foreach ($roles as $id => $perms) {
                $map = Permissions::build(array_map(fn ($n) => (object) ['name' => $n, 'permission' => '1'], $perms));
                if ($map[$orphan] ?? false) {
                    $got[] = $id;
                }
            }
            $this->assertSame($want, $got, $orphan);
        }
    }

    /** เจ้าของไม่เปิด /get_price (2026-10-18) → view_bol ไม่อยู่ใน map: แอดมิน tracking ก็ไม่ได้ (endpoint ปิดเหมือนเดิม) */
    public function test_view_bol_is_not_granted_by_fallback(): void
    {
        $this->assertArrayNotHasKey('view_bol', config('legacy.permission_fallbacks'));
        $this->actAs(904, ['admin_tracking', 'admin_lot_view', 'admin_payment', 'admin_mall', 'admin_report']);
        $this->assertFalse($this->app->make(Permissions::class)->has('view_bol'));
        $this->assertFalse(perm('view_bol'));
    }

    public function test_logged_out_has_no_permissions(): void
    {
        $this->actAs(null);
        $this->assertFalse(perm('admin_withdrawpoint'));
    }
}
