<?php

namespace App\Http\Controllers\Shop;

use App\Domain\Shop\CrawlGuard;
use App\Domain\Shop\CrawlPrices;
use App\Domain\Shop\ProductCrawler;
use App\Domain\Shop\Unjail;
use App\Support\Legacy\BlobStore;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * _api/*.php เดิม
 *  - load_product: ดึงสินค้าผ่าน crawler (dev = STUB ไม่ยิงภายนอก) + โควตา crawl_guard
 *  - blockblob_ajax: ส่งไฟล์ upload/temp ขึ้นที่เก็บถาวร (App\Support\Legacy\BlobStore: dev = คัดลอกในเครื่อง, production = Azure)
 *  - get_order_number_v2, submit_order_number_v2, unban: API ของ crawler/cron ด้วย token
 *    (PACK ไม่มี load_product_v2/v7, send_sms, get_order_number_v3 ของ SR — ไม่มี route)
 *    (เดิมฝัง token ในโค้ด → ย้ายไป .env: config legacy.shop.*; ไม่ตั้ง = ปิด)
 *  - blockblob, crawl_guard, image_shrink, useragent: ไฟล์ไลบรารี (เปิดตรง = ว่าง) → app/Domain/Shop/*
 */
class ApiController extends ShopController
{
    // ------------------------------------------------------------ ลูกค้า

    /** _api/load_product — POST url (JSON ของ crawler ส่งต่อตรงๆ) */
    public function loadProduct(Request $request): Response
    {
        $uid = $this->uid();
        $blocked = CrawlGuard::check($uid);
        if ($blocked !== null) {
            // text/html เหมือนตอนดึงได้ — หน้าเว็บเรียก $.parseJSON เอง ถ้าส่ง application/json jQuery จะแปลงให้ก่อนแล้ว parse ซ้ำพัง โหลดค้าง
            return $this->out($blocked);
        }

        $url = $this->post($request, 'url');
        $body = ProductCrawler::load($url, $uid, $request->getRequestUri());
        // จำราคาที่ crawler ให้ — _cart/save_product ใช้ราคานี้แทนราคาที่ browser ส่งมา (CrawlPrices)
        CrawlPrices::remember($uid, $url, $body);

        return $this->out($body);
    }

    /** _api/load_product_v5 — ไลบรารี (เปิดตรง = บรรทัดว่างนอกแท็ก PHP เดิม) */
    public function loadProductV5(): Response
    {
        return response("\r\n\r\n")->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * _api/blockblob_ajax — POST path; PACK = jl (ไม่ echo URL ไฟล์ต่อท้ายแบบ SR)
     * เดิมไม่ตรวจล็อกอิน (ใครก็สั่งอัปไฟล์ temp ขึ้น Azure ได้) → ต้องล็อกอิน (แก้แบบ jl/SR)
     */
    public function blockblobAjax(Request $request): Response
    {
        return $this->out(BlobStore::uploadFromTemp($this->post($request, 'path')));
    }

    /** ไฟล์ไลบรารีที่เปิดตรงได้แต่ไม่ตอบอะไร: blockblob, crawl_guard, image_shrink, useragent */
    public function library(): Response
    {
        return $this->out('');
    }

    // ------------------------------------------------------------ token API (crawler / cron)

    private static function tokenOk(string $configKey, mixed $given): bool
    {
        $expect = (string) config('legacy.shop.'.$configKey, '');

        return $expect !== '' && is_string($given) && hash_equals($expect, $given);
    }

    /** _api/get_order_number_v2 — PACK: เลขออเดอร์ที่ยังไม่มี tracking + URL สินค้า ("<order>[xx]<url>", 60 วัน) — แบบ v3 ของ SR */
    public function getOrderNumberV2(Request $request): Response
    {
        $str = '';
        if (self::tokenOk('order_api_token', $request->query('token'))) {
            $rows = DB::select("SELECT tracking.order, t3.url FROM `tracking`
                LEFT JOIN `user` ON `tracking`.user = user.id
                LEFT JOIN `bill_product_on_tracking` AS t2 ON t2.`tracking_id` = tracking.`id`
                LEFT JOIN `bill_product_detail` AS t3 ON t3.`id` = t2.`bill_product_detail_id`
                LEFT JOIN `bill_product` AS t4 ON t4.id = t3.`bill_product_id`
                WHERE `order` != '' AND `order` != '0' AND `tracking` = ''  AND (tracking.create_date >= NOW() - INTERVAL 60 DAY )
                AND t4.`status` != '-1' ORDER BY tracking.create_date DESC");
            foreach ($rows as $row) {
                $str .= $row->order.'[xx]'.$row->url."\n";
            }
        }

        return response($str)->header('Content-Type', 'application/json');
    }

    /**
     * _api/submit_order_number_v2 — crawler ส่ง tracking ของออเดอร์กลับมา → CombineTrack()
     * CombineTrack เป็นของกลุ่ม tracking (ยังไม่พอร์ต): เรียก App\Domain\Tracking\CombineTrack::run() ถ้ามี
     * ไม่มี = ตอบ 501 ไม่เขียนอะไร (ดู docs/modules/shop.md)
     */
    public function submitOrderNumberV2(Request $request): Response
    {
        if (! self::tokenOk('order_api_token', $request->query('token'))) {
            return response('');
        }
        $tn = is_string($t = $request->query('tracking_number')) ? strip_tags(trim($t)) : '';
        $on = is_string($o = $request->query('order_number')) ? strip_tags(trim($o)) : '';
        if ($tn === '' || $on === '' || $tn === '-' || $tn === '—') {
            return response('');
        }
        $data = DB::table('tracking')->where('order', $on)->where('is_delete', '0')->first();
        if ($data !== null && (string) $data->tracking !== '') {
            return response('');
        }
        $class = 'App\\Domain\\Tracking\\CombineTrack';
        if (class_exists($class) && method_exists($class, 'run')) {
            ob_start();
            $class::run($tn, $on);

            return response((string) ob_get_clean());
        }
        Log::warning('shop: submit_order_number_v2 needs CombineTrack (tracking group) — not executed', ['order' => $on]);

        return response('CombineTrack not available', 501);
    }

    /** _api/unban — PACK: token จาก POST หรือ GET (แบบ jl) → check_unjail() */
    public function unban(Request $request): Response
    {
        $token = $request->request->has('token') ? $request->request->get('token') : $request->query('token', '');
        if (self::tokenOk('unban_token', $token)) {
            Unjail::run();
        }

        return response('');
    }
}
