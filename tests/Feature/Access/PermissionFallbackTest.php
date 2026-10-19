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

    /** SR (admin_sr 2026-10-20) ไม่มีสิทธิ์กำพร้า → map ว่าง: ไม่มีใครได้สิทธิ์เพิ่มจาก fallback (docs/CONVENTIONS.md ข้อ 3.1) */
    public function test_sr_has_no_fallbacks(): void
    {
        $this->assertSame([], config('legacy.permission_fallbacks'));
        // map ของ jl (admin_point ← admin_shop_cashback) จะให้ role 10–13 ของ SR ได้ admin_point ซึ่ง SR ไม่ได้ติ๊กไว้
        $this->actAs(900, ['admin_shop_cashback', 'admin_exchange', 'admin_balance', 'admin_report', 'admin_payment']);
        $p = $this->app->make(Permissions::class);
        foreach (['admin_point', 'admin_withdrawpoint', 'admin_nobilling', 'admin_balance_company', 'admin_change_sender'] as $n) {
            $this->assertFalse($p->has($n), $n);
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
        config(['legacy.permission_fallbacks' => ['admin_withdrawpoint' => ['admin_exchange']], 'legacy.permission_fallbacks_enabled' => false]);
        $this->actAs(903, ['admin_exchange', 'admin_point']);
        $p = $this->app->make(Permissions::class);
        $this->assertFalse($p->has('admin_withdrawpoint'));
        $this->assertTrue($p->has('admin_point'), 'สิทธิ์ที่ DB ให้จริงใช้ได้เสมอ');
    }

    public function test_first_row_wins_before_fallback(): void
    {
        // role_on_detail ซ้ำ: แถวแรก (permission=0) ชนะเหมือนเดิม แล้ว fallback ค่อยเพิ่ม
        config(['legacy.permission_fallbacks' => ['admin_change_sender' => ['admin_payment'], 'zz_orphan' => ['admin_lot']]]);
        $map = Permissions::build([
            (object) ['name' => 'admin_payment', 'permission' => '0'],
            (object) ['name' => 'admin_payment', 'permission' => '1'],
            (object) ['name' => 'Admin_Lot ', 'permission' => '1'],
        ]);
        $this->assertFalse($map['admin_payment']);
        $this->assertFalse($map['admin_change_sender'] ?? false);
        $this->assertTrue($map['zz_orphan'], 'ชื่อสิทธิ์เทียบแบบไม่สนตัวพิมพ์/ช่องว่างท้ายเหมือน collation เดิม');
    }

    /** เจ้าของไม่เปิด /get_price (2026-10-18) → view_bol ไม่อยู่ใน map: แอดมิน tracking ก็ไม่ได้ (endpoint ปิดเหมือนเดิม) */
    public function test_view_bol_is_not_granted_by_fallback(): void
    {
        $this->assertArrayNotHasKey('view_bol', config('legacy.permission_fallbacks'));
        $this->actAs(904, ['admin_tracking', 'admin_lot_view', 'admin_payment', 'admin_report']);
        $this->assertFalse($this->app->make(Permissions::class)->has('view_bol'));
        $this->assertFalse(perm('view_bol'));
    }

    public function test_logged_out_has_no_permissions(): void
    {
        $this->actAs(null);
        $this->assertFalse(perm('admin_withdrawpoint'));
    }
}
