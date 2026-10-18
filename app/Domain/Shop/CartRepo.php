<?php

namespace App\Domain\Shop;

use App\Support\Legacy\Format;
use Illuminate\Support\Facades\DB;

/**
 * ตะกร้าสินค้าของลูกค้า (ตาราง cart / cart_detail) — ของ _cart/*.php เดิม
 *
 * เดิม: SELECT * FROM cart WHERE user = me LIMIT 1 → random → SELECT id FROM cart WHERE random = ... (หา cart อีกรอบ)
 * ใหม่: ใช้แถว cart ของผู้ใช้ตรงๆ และกรอง cart.user = me ทุก query (random ซ้ำกันได้ทางทฤษฎี = เห็นตะกร้าคนอื่น)
 * ผู้ใช้บางคนมี cart หลายแถว: เดิม LIMIT 1 ไม่มี ORDER BY → MySQL คืนแถว id ต่ำสุด → orderBy('id') ให้ผลเดียวกันแน่นอน
 */
final class CartRepo
{
    public static function find(int $userId): ?object
    {
        return DB::table('cart')->where('user', $userId)->orderBy('id')->first();
    }

    /** load_cart / save_product: ยังไม่มีตะกร้า → สร้างใหม่ (random 30 ตัว) */
    public static function findOrCreate(int $userId): object
    {
        $cart = self::find($userId);
        if ($cart !== null) {
            return $cart;
        }
        $rand = Format::randd(30);
        $id = DB::table('cart')->insertGetId([
            'create_date' => DB::raw('NOW()'), 'create_by' => $userId, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $userId,
            'is_delete' => 0, 'user' => (string) $userId, 'random' => $rand,
        ]);

        return (object) ['id' => $id, 'user' => $userId, 'random' => $rand];
    }

    /**
     * รายการในตะกร้า เรียงตาม create_date ล่าสุดก่อน (load_cart)
     * @return list<object>
     */
    public static function items(object $cart): array
    {
        return DB::table('cart_detail')
            ->join('cart', 'cart.id', '=', 'cart_detail.cart_id')
            ->where('cart.id', $cart->id)
            ->orderByDesc('cart_detail.create_date')
            ->select('cart_detail.*')
            ->get()->all();
    }

    /**
     * รายการที่เลือก (checkout / load_full_cart): เดิม id IN (base64_decode($ids)) ต่อ string ตรง = SQL injection
     * ใหม่: ถอด base64 แล้วรับเฉพาะเลขจำนวนเต็มที่คั่นด้วย , (ค่าอื่น = เดิม SQL error → ไม่มีแถว)
     * count = จำนวนแถวร้านเดียวกันในตะกร้า (เดิม subquery ต่อแถว — ที่นี่ join กับ GROUP BY ครั้งเดียว)
     * @return list<object>
     */
    public static function selected(?object $cart, string $idsB64): array
    {
        $ids = self::decodeIds($idsB64);
        if ($cart === null || $ids === null) {
            return [];
        }
        $counts = DB::table('cart_detail')->where('cart_id', $cart->id)
            ->groupBy('shop_name')->select('shop_name', DB::raw('COUNT(id) as c'));

        return DB::table('cart_detail')
            ->join('cart', 'cart.id', '=', 'cart_detail.cart_id')
            ->leftJoinSub($counts, 't', 't.shop_name', '=', 'cart_detail.shop_name')
            ->where('cart.id', $cart->id)
            ->whereIn('cart_detail.id', $ids)
            ->orderBy('cart_detail.shop_name')->orderBy('cart_detail.id')
            ->select('cart_detail.*', DB::raw('COALESCE(t.c, 0) as count'))
            ->get()->all();
    }

    /** base64 ของ "1,2,3" → [1,2,3]; ไม่ใช่รูปแบบนี้ → null (เดิม = SQL error ไม่มีแถว) */
    public static function decodeIds(string $b64): ?array
    {
        $raw = base64_decode($b64, false);
        if (! is_string($raw)) {
            return null;
        }

        return self::parseIdList($raw);
    }

    /** "1,2,3" → [1,2,3] (รับช่องว่างรอบตัวเลข และเลขในเครื่องหมาย ' แบบที่ MySQL รับได้) */
    public static function parseIdList(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $out = [];
        foreach (explode(',', $raw) as $p) {
            $p = trim($p);
            if (preg_match("/^'(-?\\d+)'$/", $p, $m)) {
                $p = $m[1];
            }
            if (! preg_match('/^-?\d+$/', $p)) {
                return null;
            }
            $out[] = (int) $p;
        }

        return $out;
    }
}
