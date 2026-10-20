<?php

namespace App\Domain\Shop;

use App\Support\Legacy\LegacyLog;
use App\Support\Legacy\Lookup;
use Illuminate\Support\Facades\Http;

/**
 * ดึงข้อมูลสินค้าจากลิงก์ Taobao/1688/Tmall ผ่าน crawler ภายนอก (setting `apiurls`) — _api/load_product.php เดิม
 *
 * ภายนอกจริงเรียกเฉพาะเมื่อ config legacy.external.enabled = true (production)
 * dev/test (ค่าเริ่มต้น false): ไม่ยิงออกนอกเครื่อง คืนข้อมูลสินค้าปลอม (STUB) ที่ระบุชัดว่าเป็นของทดสอบ
 */
final class ProductCrawler
{
    public static function enabled(): bool
    {
        return (bool) config('legacy.external.enabled');
    }

    /** คืน body ที่ crawler ตอบ (JSON string) แบบเดิม echo ตรงๆ */
    public static function load(string $url, int $uid, string $requestUri): string
    {
        if (! self::enabled()) {
            $body = self::stub($url);
            self::log($requestUri, 0, $body, 0.0, 'stub(external disabled)', $url);

            return $body;
        }

        $endpoint = (string) Lookup::getSetting('apiurls');
        $full = $endpoint.'?url='.urlencode($url).'&user_id='.$uid.'&__user='.urlencode((string) $uid);
        $start = microtime(true);
        $code = 0;
        $err = '';
        $body = '';
        try {
            $res = Http::withHeaders([
                'Accept' => 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
                'Cache-Control' => 'max-age=0',
                'Connection' => 'keep-alive',
                'Keep-Alive' => '300',
                'Accept-Charset' => 'big5;q=0.7,*;q=0.7',
                'Accept-Language' => 'en-us,en;q=0.5',
                'Accept-Encoding' => 'gzip,deflate',
            ])->withUserAgent(UserAgent::random())
                ->connectTimeout(120)->timeout(120) // SR: CURLOPT_CONNECTTIMEOUT/TIMEOUT 120 (jl 10/25)
                ->withoutVerifying()
                ->withOptions(['allow_redirects' => ['max' => 10, 'referer' => true]])
                ->send('POST', $full);
            $code = $res->status();
            $body = $res->body();
        } catch (\Throwable $e) {
            $err = $e->getMessage();
        }
        self::log($requestUri, $code, $body, microtime(true) - $start, $err, $url);

        return $body;
    }

    /** goLogMessage(REQUEST_URI, 'load_product http=.. ok=.. t=.. bytes=.. err=.. url=..') แบบเดิม */
    private static function log(string $uri, int $code, string $body, float $t, string $err, string $url): void
    {
        $lp = json_decode($body, true);
        $good = is_array($lp) && ! empty($lp['product_name']) && mb_strpos((string) $lp['product_name'], 'ไม่พบสินค้า') === false;
        LegacyLog::goLogMessage($uri, 'load_product http='.$code.' ok='.($good ? 1 : 0).' t='.round($t, 2).' bytes='.strlen($body)
            .' err='.$err.' url='.mb_substr((string) preg_replace('/\s+/u', ' ', $url), 0, 120, 'UTF-8'));
    }

    /** สินค้าปลอมสำหรับ dev — รูปแบบเดียวกับที่ crawler ตอบ (method 2 = color/size เป็น list) */
    public static function stub(string $url): string
    {
        if (trim($url) === '') {
            return json_encode(['product_name' => '', 'shop_name' => '', 'price' => null]);
        }

        return json_encode([
            'product_name' => '[DEV STUB] สินค้าทดสอบ — ไม่ได้ดึงจากเว็บจริง',
            'shop_name' => 'zz_dev_stub_shop',
            'main_image' => '',
            'price' => '10.00',
            'url' => $url,
            'method' => '2',
            'color_type' => 'radio',
            'color' => ['แดง', 'ดำ'],
            'size' => ['M', 'L'],
            // SR _cart/product.php เรียก sku[0].quantity โดยไม่ตรวจ (jl มี if (sku != '')) → STUB ต้องมี sku ตัวแรก
            'skus' => [['quantity' => '100', 'price' => ['priceText' => '10.00']]],
            'priceRange' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
