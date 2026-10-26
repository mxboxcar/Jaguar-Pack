<?php

namespace Tests\Feature\Messaging;

use App\Http\Controllers\Messaging\Admin\AdminMessageController;
use App\Http\Controllers\Messaging\Admin\AdminSendController;
use App\Http\Controllers\Messaging\MessageController;
use App\Http\Controllers\Messaging\NotificationController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\BenchDbTestCase;

/**
 * PACK: ส่วนของกลุ่ม messaging ที่ต่างจาก SR (JL_BENCH_DB_TESTS=1 — แถว zz_ ใน transaction ที่ rollback ทั้งหมด)
 * แถบแจ้งเตือน (goodsconfirm ด้วยสิทธิ์ admin_withdrawpoint, send/sendself แยก 43/53, ไม่มีช่อง admin/check / nobilling,
 * tracking ที่เข้าล็อตแล้ว, กระดิ่งประกาศ), admin/send vs admin/sendself,
 * JSON ของ _message/load และ _admin/_message/load แบบ urldecode(json_encode()) ไม่มี htmlentities
 */
class MessagingPackDbTest extends BenchDbTestCase
{
    private const ADMIN = 11938;

    private const CUSTOMER = 11937;

    private function as(int $id): User
    {
        $u = User::query()->findOrFail($id);
        $this->actAs($u);

        return $u;
    }

    private function badge(string $html, string $path): ?int
    {
        $re = '~href="[^"]*/'.preg_quote($path, '~').'" >.*?badge-danger">(\d+)</span>~s';

        return preg_match($re, $html, $m) ? (int) $m[1] : null;
    }

    private function bill(int $shippingType, string $tag): int
    {
        return $this->insertRow('bill_payment', ['user' => self::CUSTOMER, 'status' => 2, 'shipping_type' => $shippingType,
            'send_name' => 'zz_'.$tag, 'total' => 1]);
    }

    /** role ใหม่ที่ถือเฉพาะสิทธิ์ที่ระบุ (rollback ตอนจบ) */
    private function roleWith(array $perms): int
    {
        $role = $this->insertRow('role', ['role_name' => 'zz_msg_role']);
        foreach ($perms as $p) {
            $detail = DB::table('role_detail')->where('role_detail_name', $p)->value('id');
            $this->assertNotNull($detail, $p);
            $this->insertRow('role_on_detail', ['role_id' => $role, 'role_detail_id' => $detail, 'permission' => 1]);
        }

        return $role;
    }

    public function test_notification_bar_pack_blocks(): void
    {
        $self = $this->bill(53, 'self53');
        $this->bill(43, 'self43');
        $this->bill(63, 'send63');
        $this->insertRow('goodsconfirm', ['status' => 1, 'submit_remark' => 'zz_msg']);
        $this->insertRow('notice', ['text' => 'zz_bell &lt;b&gt;ล่าสุด&lt;/b&gt;', 'is_show' => 1, 'create_date' => '2099-01-01 00:00:00']);

        $this->as(self::ADMIN);
        $html = $this->act(NotificationController::class, 'bar', ['x' => 1]);

        $bp = fn (bool $s) => DB::table('bill_payment')->where('is_delete', 0)->where('status', 2)
            ->when($s, fn ($q) => $q->whereIn('shipping_type', [43, 53]), fn ($q) => $q->whereNotIn('shipping_type', [43, 53]))->count();
        $this->assertSame($bp(false), $this->badge($html, 'admin/send'));
        $this->assertSame($bp(true), $this->badge($html, 'admin/sendself'));
        $this->assertStringContainsString('<i class="text-danger fa fa-send"></i> รับเอง<span', $html);
        $this->assertSame(DB::table('goodsconfirm')->where('is_delete', 0)->where('status', 1)->count(), $this->badge($html, 'admin/goodsconfirm/status/1'));
        // PACK ไม่มีช่อง admin/check (ไม่มีตาราง tracking_check) และ admin/nobilling
        $this->assertStringNotContainsString('admin/check', $html);
        $this->assertStringNotContainsString('admin/nobilling', $html);
        // กระดิ่ง: ประกาศล่าสุด 1 แถว (strip_tags แล้ว)
        $this->assertStringContainsString('<i class="icon-bell"></i>', $html);
        $this->assertStringContainsString('&nbsp;&nbsp;&nbsp;&nbsp;zz_bell ล่าสุด...', $html);
        $this->assertGreaterThan(0, $self);
    }

    public function test_goodsconfirm_badge_follows_admin_withdrawpoint(): void
    {
        $this->insertRow('goodsconfirm', ['status' => 1, 'submit_remark' => 'zz_msg']);
        $n = DB::table('goodsconfirm')->where('is_delete', 0)->where('status', 1)->count();

        $u = $this->zzUser($this->roleWith(['admin_withdrawpoint']));
        $this->actAs($u);
        $this->assertSame($n, $this->badge($this->act(NotificationController::class, 'bar', ['x' => 1]), 'admin/goodsconfirm/status/1'));

        // admin_goodsconfirm (สิทธิ์ของ SR) อย่างเดียว = ไม่เห็นช่องนี้ใน PACK
        $u = $this->zzUser($this->roleWith(['admin_goodsconfirm', 'admin_export']));
        $this->actAs($u);
        $html = $this->act(NotificationController::class, 'bar', ['x' => 1]);
        $this->assertStringNotContainsString('admin/goodsconfirm', $html);
        $this->assertStringNotContainsString('admin/check', $html);
    }

    public function test_customer_bill_create_badge_counts_only_tracking_in_a_lot(): void
    {
        $u = $this->zzUser(2);
        $this->insertRow('tracking', ['tracking' => 'ZZMSGA', 'user' => $u->id, 'lot_id' => 0, 'payment_id' => 0, 'date_thai' => '2026-01-01 00:00:00']);
        $this->actAs($u);
        $this->assertNull($this->badge($this->act(NotificationController::class, 'bar', ['x' => 1]), 'payment/create'));
        $this->insertRow('tracking', ['tracking' => 'ZZMSGB', 'user' => $u->id, 'lot_id' => 99999, 'payment_id' => 0, 'date_thai' => '2026-01-01 00:00:00']);
        $html = $this->act(NotificationController::class, 'bar', ['x' => 1]);
        $this->assertSame(1, $this->badge($html, 'payment/create'));
        // ลูกค้าไม่เห็นช่องของแอดมิน
        $this->assertStringNotContainsString('admin/goodsconfirm', $html);
    }

    public function test_send_and_sendself_split_shipping_types(): void
    {
        $self = $this->bill(53, 'sendself_row');
        $send = $this->bill(63, 'send_row');
        $this->as(self::ADMIN);
        $sendHtml = app(AdminSendController::class)->index()->render();
        $selfHtml = app(AdminSendController::class)->sendself()->render();
        $this->assertStringContainsString('zz_send_row', $sendHtml);
        $this->assertStringNotContainsString('zz_sendself_row', $sendHtml);
        $this->assertStringContainsString('zz_sendself_row', $selfHtml);
        $this->assertStringNotContainsString('zz_send_row', $selfHtml);
        $this->assertStringContainsString('style="word-break: break-all;"', $selfHtml);
        $this->assertGreaterThan(0, $self + $send);
    }

    public function test_chat_load_json_is_pack_style(): void
    {
        $dept = (int) DB::table('chat_dept')->insertGetId(['name' => 'zz_dept', 'icon' => 'fa fa-user']);
        DB::table('chat')->insert(['text' => 'zz_a+b %41 "q" <i>x</i>', 'date_read' => '', 'is_read' => 0, 'is_read_admin' => 0,
            'dept' => $dept, 'from' => self::CUSTOMER, 'to' => 0, 'create_by' => self::CUSTOMER, 'create_date' => DB::raw('NOW()')]);

        $this->as(self::ADMIN);
        $raw = $this->act(AdminMessageController::class, 'load', ['id' => $dept, 'user' => self::CUSTOMER, 'page' => 1]);
        $j = json_decode($raw, true);
        $this->assertSame(['html', 'title', 'page'], array_keys($j), 'PACK: ไม่มี sql');
        // ไม่ htmlentities, " ทุกตัวเป็น ', urldecode (+ → ช่องว่าง, %41 → A), ข้อความยัง escape
        $this->assertStringStartsWith("<div id='page'><li class='clearfix odd text-center load_more_btn'>", $j['html']);
        $this->assertStringNotContainsString('"', $j['html']);
        $this->assertStringContainsString("zz_a b A 'q' &lt;i&gt;x&lt;/i&gt;", $j['html']);
        $this->assertSame(DB::table('user')->where('id', self::CUSTOMER)->value('code'), $j['title']);
        $this->assertNull($j['page']);

        DB::table('chat')->insert(['text' => 'zz_reply', 'date_read' => '', 'is_read' => 0, 'dept' => $dept, 'from' => 0,
            'to' => self::CUSTOMER, 'create_by' => self::ADMIN, 'create_date' => DB::raw('NOW()')]);
        $this->as(self::CUSTOMER);
        // ลูกค้าเรียก load ของแอดมิน = ว่าง (PACK hotfix 2026-10-21: exit เมื่อไม่มีสิทธิ์ admin_message)
        $this->assertSame('', $this->act(AdminMessageController::class, 'load', ['id' => $dept, 'user' => self::CUSTOMER, 'page' => 1]));

        $j = json_decode($this->act(MessageController::class, 'load', ['id' => $dept, 'page' => 1]), true);
        $this->assertSame(['html', 'title', 'page', 'sql'], array_keys($j));
        $this->assertStringStartsWith('<div id="page"><li class="clearfix odd text-center load_more_btn">', $j['html']);
        $this->assertStringContainsString('zz_a b A &quot;q&quot; &lt;i&gt;x&lt;/i&gt;', $j['html']);
        $this->assertStringContainsString('<i>PACK-Cargo Support Team</i>', $j['html']);
        $this->assertSame('zz_dept', $j['title']);
        $this->assertSame('0 , 10', $j['sql']);
    }
}
