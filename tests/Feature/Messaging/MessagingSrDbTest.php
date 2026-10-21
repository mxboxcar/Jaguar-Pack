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
 * SR: ส่วนของกลุ่ม messaging ที่ต่างจาก jl (JL_BENCH_DB_TESTS=1 — แถว zz_ ใน transaction ที่ rollback ทั้งหมด)
 * แถบแจ้งเตือน (goodsconfirm, send/sendself แยก 43/53, พัสดุรอตรวจ tracking_check, tracking ที่เข้าล็อตแล้ว, กระดิ่งประกาศ),
 * admin/send vs admin/sendself, JSON ของ _admin/_message/load แบบ htmlentities
 */
class MessagingSrDbTest extends BenchDbTestCase
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

    public function test_notification_bar_sr_blocks(): void
    {
        $self = $this->bill(53, 'self53');
        $this->bill(43, 'self43');
        $this->bill(63, 'send63');
        $track = $this->insertRow('tracking', ['tracking' => 'ZZMSG'.bin2hex(random_bytes(4)), 'user' => self::CUSTOMER, 'lot_id' => 0, 'payment_id' => 0]);
        $this->insertRow('tracking_check', ['tracking_id' => $track, 'is_checked' => 0]);
        $this->insertRow('tracking_check', ['tracking_id' => 0, 'is_checked' => 0]); // ไม่มี tracking → ไม่นับ
        $this->insertRow('goodsconfirm', ['status' => 1]);
        $this->insertRow('notice', ['text' => 'zz_bell &lt;b&gt;ล่าสุด&lt;/b&gt;', 'is_show' => 1, 'create_date' => '2099-01-01 00:00:00']);

        $this->as(self::ADMIN);
        $html = $this->act(NotificationController::class, 'bar', ['x' => 1]);

        $bp = fn (bool $s) => DB::table('bill_payment')->where('is_delete', 0)->where('status', 2)
            ->when($s, fn ($q) => $q->whereIn('shipping_type', [43, 53]), fn ($q) => $q->whereNotIn('shipping_type', [43, 53]))->count();
        $this->assertSame($bp(false), $this->badge($html, 'admin/send'));
        $this->assertSame($bp(true), $this->badge($html, 'admin/sendself'));
        $this->assertStringContainsString('<i class="text-danger fa fa-send"></i> รับเอง<span', $html);
        $checks = DB::table('tracking_check')->leftJoin('tracking', 'tracking.id', '=', 'tracking_check.tracking_id')
            ->where('tracking_check.is_checked', 0)->whereNotNull('tracking.tracking')->count();
        $this->assertSame($checks, $this->badge($html, 'admin/check'));
        $this->assertSame(DB::table('goodsconfirm')->where('is_delete', 0)->where('status', 1)->count(), $this->badge($html, 'admin/goodsconfirm/status/1'));
        // กระดิ่ง: ประกาศล่าสุด 1 แถว (strip_tags แล้ว)
        $this->assertStringContainsString('<i class="icon-bell"></i>', $html);
        $this->assertStringContainsString('&nbsp;&nbsp;&nbsp;&nbsp;zz_bell ล่าสุด...', $html);
        $this->assertGreaterThan(0, $self);
    }

    public function test_customer_bill_create_badge_counts_only_tracking_in_a_lot(): void
    {
        $u = $this->zzUser(8);
        $this->insertRow('tracking', ['tracking' => 'ZZMSGA', 'user' => $u->id, 'lot_id' => 0, 'payment_id' => 0, 'date_thai' => '2026-01-01 00:00:00']);
        $this->actAs($u);
        $this->assertNull($this->badge($this->act(NotificationController::class, 'bar', ['x' => 1]), 'payment/create'));
        $this->insertRow('tracking', ['tracking' => 'ZZMSGB', 'user' => $u->id, 'lot_id' => 99999, 'payment_id' => 0, 'date_thai' => '2026-01-01 00:00:00']);
        $html = $this->act(NotificationController::class, 'bar', ['x' => 1]);
        $this->assertSame(1, $this->badge($html, 'payment/create'));
        // ลูกค้าไม่เห็นช่องของแอดมิน
        $this->assertNull($this->badge($html, 'admin/check'));
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
        $this->assertStringNotContainsString('target="_blank"', $selfHtml);
        $this->assertGreaterThan(0, $self + $send);
    }

    public function test_admin_chat_load_returns_htmlentities_without_urldecode(): void
    {
        $dept = (int) DB::table('chat_dept')->insertGetId(['name' => 'zz_dept', 'icon' => 'fa fa-user']);
        DB::table('chat')->insert(['text' => 'zz_a+b %41 <i>x</i>', 'date_read' => '', 'is_read' => 0, 'is_read_admin' => 0,
            'dept' => $dept, 'from' => self::CUSTOMER, 'to' => 0, 'create_by' => self::CUSTOMER, 'create_date' => DB::raw('NOW()')]);
        $this->as(self::ADMIN);
        $j = json_decode($this->act(AdminMessageController::class, 'load', ['id' => $dept, 'user' => self::CUSTOMER, 'page' => 1]), true);
        $this->assertStringStartsWith('&lt;div id=&quot;page&quot;&gt;', $j['html']);
        $html = html_entity_decode($j['html'], ENT_COMPAT | ENT_HTML401, 'UTF-8'); // = decodeHtml() ใน JS
        $this->assertStringContainsString('zz_a+b %41 &lt;i&gt;x&lt;/i&gt;', $html); // ไม่ urldecode, ข้อความยัง escape
        $this->assertSame(DB::table('user')->where('id', self::CUSTOMER)->value('code'), $j['title']);
        $this->assertStringContainsString("`dept` = '$dept'", $j['sql']);

        DB::table('chat')->insert(['text' => 'zz_reply', 'date_read' => '', 'is_read' => 0, 'dept' => $dept, 'from' => 0,
            'to' => self::CUSTOMER, 'create_by' => self::ADMIN, 'create_date' => DB::raw('NOW()')]);
        $this->as(self::CUSTOMER);
        $j = json_decode($this->act(MessageController::class, 'load', ['id' => $dept, 'page' => 1]), true);
        $this->assertStringContainsString('zz_a+b %41', $j['html']);
        $this->assertStringContainsString('&lt;i&gt;PACK-Cargo Support Team&lt;/i&gt;', $j['html']);
    }
}
