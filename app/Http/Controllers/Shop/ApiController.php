<?php

namespace App\Http\Controllers\Shop;

use App\Domain\Shop\CrawlGuard;
use App\Domain\Shop\CrawlPrices;
use App\Domain\Shop\ProductCrawler;
use App\Domain\Shop\Unjail;
use App\Support\Legacy\BlobStore;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * _api/*.php เดิม
 *  - load_product: ดึงสินค้าผ่าน crawler (dev = STUB ไม่ยิงภายนอก) + โควตา crawl_guard
 *  - blockblob_ajax: ส่งไฟล์ upload/temp ขึ้นที่เก็บถาวร (App\Support\Legacy\BlobStore: dev = คัดลอกในเครื่อง, production = Azure)
 *  - get_order_number_v2/v3, submit_order_number_v2, unban: API ของ crawler/cron ด้วย token
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

    /**
     * _api/load_product_v2 — ตัวดึงสินค้ารุ่นเก่า (vcanbuy.com) ไม่มีหน้าไหนเรียกแล้ว
     * เดิมไม่ตรวจล็อกอิน (ใครก็สั่งเซิร์ฟเวอร์ไปดึงเว็บภายนอกได้) → ต้องมีสิทธิ์ order
     * ลิงก์ .1688 เดิม include load_product_v5 แล้วเรียก getimage() ที่ไม่มีจริง → fatal 500 เสมอ
     */
    public function loadProductV2(Request $request): Response
    {
        $postUrl = $this->postPhp($request, 'url');
        if (self::pos($postUrl, '.1688')) {
            return response('', 500);
        }
        $url = 'http://www.vcanbuy.com/cgi-bin/grab/vcb_grab.pl?u='.$postUrl;
        $output = '';
        if (ProductCrawler::enabled()) {
            try {
                $output = (string) \Illuminate\Support\Facades\Http::timeout(25)->get($url)->body();
            } catch (\Throwable) {
                $output = '';
            }
        }

        return response(self::parseV2($output, $url, $postUrl))->header('Content-Type', 'text/html; charset=gbk');
    }

    /** ส่วนแยกข้อมูลของ load_product_v2 เดิม (ตัด v5 ออกเพราะ fatal เสมอ) */
    public static function parseV2(string $output, string $url, string $postUrl): string
    {
        $exp = explode('|', $output);
        $exps = explode('Access-Control-Allow-Origin: *', $exp[0]);
        $split = explode('||', $output);
        $skus = explode('^|^', $split[8] ?? '');
        $color = [];
        $size = [];
        foreach ($skus as $sku) {
            $lines = explode(':|:', $sku);
            $types = count($lines) > 1 ? explode(';', $lines[2] ?? '') : [];
            if (str_contains((string) ($lines[5] ?? ''), '//')) {
                $color[] = $lines[5];
            }
            $tc = explode(':', (string) ($types[0] ?? ''));
            if (($tc[3] ?? '') != '') {
                $color[] = $tc[3];
            }
            $ts = explode(':', (string) ($types[1] ?? ''));
            if (($ts[3] ?? '') != '') {
                $size[] = $ts[3];
            }
        }
        $color = array_unique($color);
        sort($color);
        $size = array_unique($size);
        sort($size);

        $product = [
            'product_name' => trim($exps[0]),
            'main_image' => $exp[4] ?? null,
            'price' => $exp[2] ?? null,
            'shop_name' => $exp[6] ?? null,
            'color_type' => 'radio',
            'size' => $size,
        ];
        if (self::pos($url, '.tmall')) {
            $product['url'] = 'https://detail.tmall.com/item.htm?id='.($exp[10] ?? '');
        } elseif (self::pos($url, '.1688')) {
            $product['url'] = 'https://detail.1688.com/offer/'.($exp[10] ?? '').'.html';
        } elseif (self::pos($url, '.taobao')) {
            $product['url'] = 'https://item.taobao.com/item.htm?id='.($exp[10] ?? '');
        } else {
            $product['url'] = $postUrl;
        }
        $product['color'] = $color;

        return urldecode((string) json_encode($product));
    }

    /** เดิม stripos(...) != "" — PHP 7.4: ตำแหน่ง 0 / ไม่เจอ = เท็จ */
    private static function pos(string $haystack, string $needle): bool
    {
        $p = stripos($haystack, $needle);

        return $p !== false && $p > 0;
    }

    /** _api/load_product_v5 — ไลบรารี (เปิดตรง = บรรทัดว่างนอกแท็ก PHP เดิม) */
    public function loadProductV5(): Response
    {
        return response("\r\n\r\n")->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * _api/load_product_v7 — ตัวดึงรุ่นเก่า (cnsthai.com): เดิมล็อกอินด้วยบัญชีที่ฝังในโค้ด แล้วเรียก removehtmltag()
     * ที่ไม่มีจริง → fatal 500 ทุกครั้ง — ที่นี่ตอบ 410 Gone (ยังเป็น error สำหรับ JS เหมือนเดิม แต่ไม่ใช่ 500 ที่ดูเหมือนระบบพัง)
     * โดยไม่ยิงภายนอก/ไม่ใช้บัญชีนั้น (ควรเปลี่ยนรหัสบัญชีนั้น)
     */
    public function loadProductV7(): Response
    {
        return response('', 410);
    }

    /**
     * _api/blockblob_ajax — POST path (SR เดิม checkUser() = ต้องล็อกอิน)
     * SR: หลังสั่งอัป (สำเร็จหรือ error) echo "<href>upload/temp/<ชื่อไฟล์>" ต่อท้าย — "temp empty" / "exists" ไม่มี
     */
    public function blockblobAjax(Request $request): Response
    {
        $path = $this->post($request, 'path');
        $out = BlobStore::uploadFromTemp($path);
        if ($out !== 'temp empty' && $out !== 'exists') {
            $out .= Legacy::href().'upload/temp/'.basename(str_replace('../upload/temp/', '', $path));
        }

        return $this->out($out);
    }

    /** ไฟล์ไลบรารีที่เปิดตรงได้แต่ไม่ตอบอะไร: blockblob, crawl_guard, image_shrink, useragent */
    public function library(): Response
    {
        return $this->out('');
    }

    /** _api/send_sms — เดิมยิง SMS ทดสอบด้วยบัญชีที่ฝังในโค้ด (ใครเปิดก็ส่งได้) → ไม่ส่งอะไร */
    public function sendSms(): Response
    {
        Log::notice('shop: _api/send_sms called — stub, nothing sent');

        return $this->out('');
    }

    // ------------------------------------------------------------ token API (crawler / cron)

    private static function tokenOk(string $configKey, mixed $given): bool
    {
        $expect = (string) config('legacy.shop.'.$configKey, '');

        return $expect !== '' && is_string($given) && hash_equals($expect, $given);
    }

    /** _api/get_order_number_v2 — เลขออเดอร์ที่ยังไม่มี tracking (บรรทัดละ 1) */
    public function getOrderNumberV2(Request $request): Response
    {
        $str = '';
        if (self::tokenOk('order_api_token', $request->query('token'))) {
            $rows = DB::select("SELECT tracking.order FROM `tracking`
                LEFT JOIN `user` ON `tracking`.user = user.id
                LEFT JOIN `bill_product_on_tracking` AS t2 ON t2.`tracking_id` = tracking.`id`
                LEFT JOIN `bill_product_detail` AS t3 ON t3.`id` = t2.`bill_product_detail_id`
                LEFT JOIN `bill_product` AS t4 ON t4.id = t3.`bill_product_id`
                WHERE `order` != '' AND `order` != '0' AND `tracking` = ''
                AND t4.`status` != '-1' ORDER BY tracking.create_date DESC");
            foreach ($rows as $row) {
                $str .= $row->order."\n";
            }
        }

        return response($str)->header('Content-Type', 'application/json');
    }

    /** _api/get_order_number_v3 — เลขออเดอร์ + URL สินค้า (60 วัน) */
    public function getOrderNumberV3(Request $request): Response
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
                // SR ไม่มีบรรทัด "[xx]tmall" เพิ่มสำหรับลิงก์ taobao (jl มี)
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

    /** _api/unban — SR: token จาก GET เท่านั้น (cron wget) → check_unjail() */
    public function unban(Request $request): Response
    {
        $token = $request->query('token', '');
        if (self::tokenOk('unban_token', $token)) {
            Unjail::run();
        }

        return response('');
    }
}
