<?php

namespace App\Http\Controllers\Lot\Admin;

use App\Domain\Lot\LotMailer;
use App\Domain\Lot\LotPrint;
use App\Domain\Lot\Php74;
use App\Http\Controllers\Controller;
use App\Support\Legacy\LegacyInput;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * หน้าเปิดในแท็บใหม่ (GET) ของแอดมินล็อต: print.php, print_receive.php, email_lot.php เดิม (SR ไม่มี print_all.php)
 * และไฟล์ที่เดิมเปิดตรงได้โดยไม่ต้องล็อกอิน: cron_lot_mail.php, email.php
 */
class AdminLotPrintController extends Controller
{
    /** ?lot=1,2,3 — เดิมฝังลง IN (...) ตรงๆ (SQL injection) → รับเฉพาะรายการตัวเลข ไม่งั้นเหมือน SQL error เดิม (ไม่มีข้อมูล) */
    private function lotIds(Request $request): array
    {
        $v = LegacyInput::query($request, 'lot');

        return is_string($v) ? (Php74::idList($v) ?? []) : [];
    }

    /**
     * print / email_lot เป็น GET ที่เขียนข้อมูล (is_print, เข้าคิวส่งเมลถึงลูกค้าทั้งล็อต) — LegacyCsrf ไม่ตรวจ GET
     * → ไม่ยอมคำขอที่มาจากเว็บอื่น (เช่น <img src=".../email_lot?id=N"> ในหน้าเว็บที่แอดมินเปิด)
     * JS/ลิงก์เดิมเปิดจากหน้าเดียวกัน (window.open / <a>) = Sec-Fetch-Site: same-origin; พิมพ์ URL เอง = none
     */
    public static function crossSite(Request $request): bool
    {
        $site = strtolower((string) $request->headers->get('Sec-Fetch-Site', ''));
        if ($site !== '') {
            return ! in_array($site, ['same-origin', 'none'], true);
        }
        $referer = (string) $request->headers->get('Referer', '');
        if ($referer === '') {
            return false;
        }
        $host = parse_url($referer, PHP_URL_HOST);
        if (! is_string($host)) {
            return true;
        }
        if (strtolower($host) === strtolower($request->getHost())) {
            return false;
        }
        foreach (array_filter(array_map('trim', explode(',', (string) config('legacy.trusted_origins')))) as $t) {
            $th = parse_url(str_contains($t, '://') ? $t : 'https://'.$t, PHP_URL_HOST);
            if (is_string($th) && strtolower($th) === strtolower($host)) {
                return false;
            }
        }

        return true;
    }

    private static function denied(): Response
    {
        return response('Forbidden (cross-site request)', 403);
    }

    /** GET /_admin/_lot/print — สิทธิ์ admin_lot */
    public function print(Request $request): Response
    {
        if (self::crossSite($request)) {
            return self::denied();
        }

        return response(LotPrint::print($this->lotIds($request)));
    }

    /** GET /_admin/_lot/print_receive?id= — สิทธิ์ admin_lot */
    public function printReceive(Request $request): Response
    {
        return response(LotPrint::printReceive((int) LegacyInput::query($request, 'id', 0)));
    }

    /** GET /_admin/_lot/email_lot?id= — เข้าคิวส่งเมลแจ้งลูกค้าในล็อต (สิทธิ์ admin_lot) */
    public function emailLot(Request $request): Response
    {
        if (self::crossSite($request)) {
            return self::denied();
        }
        $lotId = (int) $request->query('id');
        if ($lotId <= 0) {
            return response('ไม่พบล็อต');
        }
        [$total, $pending] = LotMailer::enqueue($lotId);

        return response('<meta charset="utf-8"><div style="text-align:center;font-family:arial;color:#555;margin-top:80px;">
  <div style="font-size:70px;color:#81c868;line-height:1;">&#10003;</div>
  <h2 style="margin:10px 0;">เข้าคิวส่งเมล/LINE แล้ว</h2>
  <p>ล็อตนี้มีลูกค้า '.$total.' ราย · รอส่ง '.$pending.' ราย</p>
  <p style="color:#888;">ระบบจะส่งเบื้องหลังอัตโนมัติ (ทุก 1 นาที) — <b>ปิดหน้านี้ได้เลย ไม่ต้องเปิดค้าง</b></p>
  <a href="'.e(Legacy::href('admin/lot')).'" style="display:inline-block;margin-top:20px;padding:10px 24px;background:#f0ad4e;color:#fff;text-decoration:none;border-radius:4px;">กลับหน้าล็อต</a>
</div>');
    }

    /**
     * cron_lot_mail.php / email.php — เดิมเปิดผ่านเว็บได้โดยไม่ต้องล็อกอิน (ใครก็สั่ง worker ส่งเมล / ส่งเมลเปล่าได้)
     * ที่นี่: ต้องมีสิทธิ์ admin_lot และไม่ทำอะไร — worker ย้ายไปเป็นคำสั่ง `php artisan lot:mail-worker` (cron)
     */
    public function cliOnly(): Response
    {
        return response('cron only: php artisan lot:mail-worker', 403);
    }
}
