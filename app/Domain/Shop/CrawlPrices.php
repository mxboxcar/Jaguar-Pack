<?php

namespace App\Domain\Shop;

use Illuminate\Support\Facades\Cache;

/**
 * ราคาสินค้าที่ "เซิร์ฟเวอร์เห็นเอง" จาก crawler (_api/load_product) — ใช้แทนราคาที่ลูกค้าส่งมาใน _cart/save_product
 *
 * แก้จากเดิม (เจ้าของอนุมัติ 2026-10-18 "ให้แอดมินแก้ไขก็พอ"): เดิม save_product เชื่อ search_price จาก browser
 * (ช่องราคาแก้ได้ในฟอร์ม และยิง POST ตรงได้) → ลูกค้าตั้งราคาสินค้าเองได้
 *
 * load_product จำชุดราคาที่ crawler ตอบ (price, ราคาของแต่ละ SKU, priceRange) ต่อ ผู้ใช้ + URL ไว้ TTL วินาที
 * save_product: ราคาที่ส่งมาตรงกับชุดนี้ = ใช้ (JS เดิมเลือกราคาตาม SKU/จำนวนจากชุดเดียวกัน) · ไม่ตรง = ใช้ราคาหลักของ crawler
 * ไม่มีข้อมูล crawler (ไม่ได้โหลดผ่าน load_product / หมดอายุ) = ไม่รับ (ให้โหลดสินค้าใหม่)
 */
final class CrawlPrices
{
    public const TTL = 7200;

    /** เก็บราคาจาก body ที่ crawler ตอบ (JSON string) */
    public static function remember(int $uid, string $inputUrl, string $body): void
    {
        $data = json_decode($body, true);
        if (! is_array($data)) {
            return;
        }
        $base = self::numbers($data['price'] ?? null);
        if ($base === []) {
            return;
        }
        $all = $base;
        $skus = $data['skus'] ?? null;
        if (is_array($skus)) {
            foreach ($skus as $sku) {
                $p = is_array($sku) ? ($sku['price'] ?? null) : null;
                $all = array_merge($all, self::numbers(is_array($p) ? ($p['priceText'] ?? $p['price'] ?? null) : $p));
            }
        }
        $range = $data['priceRange'] ?? null;
        if (is_array($range)) {
            foreach ($range as $r) {
                if (is_array($r)) {
                    $all = array_merge($all, self::numbers($r[1] ?? null));
                }
            }
        }
        $entry = ['base' => $base[0], 'allowed' => array_values(array_unique($all))];
        $crawlUrl = $data['url'] ?? ''; // บางร้าน crawler ตอบ url เป็น array (vr-new 2026-10-26 500 "Array to string")
        foreach (array_unique([$inputUrl, is_scalar($crawlUrl) ? (string) $crawlUrl : '']) as $u) {
            if (trim($u) !== '') {
                Cache::put(self::key($uid, $u), $entry, self::TTL);
            }
        }
    }

    /**
     * ราคาที่จะลง cart_detail.price (string) หรือ null = ไม่มีข้อมูลจากเซิร์ฟเวอร์ (ไม่รับรายการ)
     * ราคาที่ส่งมาตรงกับราคาที่ crawler ให้ (เทียบ 2 ตำแหน่ง) → คืนค่าที่ส่งมาเดิม (ข้อมูลเหมือนเว็บเดิม) · ไม่ตรง → ราคาหลัก
     */
    public static function resolve(int $uid, string $url, string $submitted): ?string
    {
        $entry = Cache::get(self::key($uid, $url));
        if (! is_array($entry) || ! isset($entry['base'])) {
            return null;
        }
        $mine = self::numbers($submitted);
        if (count($mine) === 1 && preg_match('/^\s*\d+(\.\d+)?\s*$/', $submitted) === 1 && in_array($mine[0], $entry['allowed'], true)) {
            return trim($submitted);
        }

        return (string) $entry['base'];
    }

    private static function key(int $uid, string $url): string
    {
        return 'shop.crawl_prices:'.$uid.':'.sha1(trim($url));
    }

    /**
     * ตัวเลขราคาทุกตัวในค่า ("12.50", 12.5, "12.50-30.00") → ["12.50", "30.00"] (ทศนิยม 2 ตำแหน่ง, >= 0)
     *
     * @return list<string>
     */
    private static function numbers(mixed $v): array
    {
        if (is_int($v) || is_float($v)) {
            return $v >= 0 ? [number_format((float) $v, 2, '.', '')] : [];
        }
        if (! is_string($v) || preg_match_all('/\d+(?:\.\d+)?/', $v, $m) === 0) {
            return [];
        }

        return array_map(fn ($n) => number_format((float) $n, 2, '.', ''), $m[0]);
    }
}
