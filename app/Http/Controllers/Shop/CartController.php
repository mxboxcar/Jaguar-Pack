<?php

namespace App\Http\Controllers\Shop;

use App\Domain\Shop\CartHtml;
use App\Domain\Shop\CartRepo;
use App\Domain\Shop\CrawlPrices;
use App\Domain\Shop\Php74;
use App\Domain\Shop\Upload;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * _cart/*.php เดิม — ตะกร้าสินค้าที่ลูกค้าวางลิงก์ Taobao/1688/Tmall แล้วสั่งซื้อ
 * ทุกไฟล์เดิมตรวจ getPermission('order') — route ใช้ legacy.perm:order (ajax ได้ ErrorBox แบบเดิม)
 * ทุก query กรองตะกร้าของผู้ใช้ที่ล็อกอินเท่านั้น
 */
class CartController extends ShopController
{
    // ---------------------------------------------------------------- หน้า

    /** cart/checkout[/{ids}] = _cart/checkout.php (page=checkout) */
    public function checkout(Request $request): View
    {
        // RewriteRule ^cart/checkout/(.+)$ ?page=checkout&ids=$1 แล้วเดิม $_GET ถูก addslashes(strip_tags(trim()))
        // (อ่านจาก route() — ค่า defaults('legacy_page') ก็ถูกส่งเป็นพารามิเตอร์ของ action ด้วย)
        $ids = strip_tags(trim((string) $request->route('ids', '')));
        $cart = CartRepo::find($this->uid());
        $rows = CartRepo::selected($cart, $ids);

        return view('shop.cart.checkout', [
            'tooMany' => count($rows) >= 50 ? count($rows) : null,
            'cartId' => (string) ($cart->random ?? ''),
            'ids' => $ids,
        ]);
    }

    /** cart/product/{url} (และ ?page=checkout_product&l=...) = _cart/product.php */
    public function product(Request $request): View
    {
        return view('shop.cart.product', [
            'url' => strip_tags(trim((string) $request->route('url', ''))),
            'link' => (string) (is_string($l = $request->query('l')) ? strip_tags(trim($l)) : ''),
        ]);
    }

    // ---------------------------------------------------------------- ajax

    /** _cart/load_cart — ตะกร้าด้านข้าง (ทุกหน้า) */
    public function loadCart(): Response
    {
        $cart = CartRepo::findOrCreate($this->uid());
        $items = CartRepo::items($cart);

        // ร้านเรียงตามที่เจอครั้งแรก (เดิมวน query เดียวกันสองรอบ — ที่นี่ใช้ผลชุดเดียว)
        $shops = [];
        foreach ($items as $it) {
            $k = (string) $it->shop_name;
            if (! array_key_exists($k, $shops) || $shops[$k] === '' || $shops[$k] === null) {
                $shops[$k] = $it->create_date;
            }
        }

        return $this->out(CartHtml::loadCart($cart, $items, array_map('strval', array_keys($shops)), Lookup::getSetting('rate_order')));
    }

    /** _cart/load_full_cart — ตารางสินค้าที่เลือกในหน้า checkout */
    public function loadFullCart(Request $request): Response
    {
        $cart = CartRepo::find($this->uid());
        $items = ($cart !== null && (string) $cart->random !== '')
            ? CartRepo::selected($cart, $this->post($request, 'ids')) : [];

        return $this->out(CartHtml::fullCart($items, Lookup::getSetting('rate_order')));
    }

    /** _cart/load_choose_product — ฟอร์มเลือกสี/ขนาดในหน้าค้นหาสินค้า */
    public function loadChooseProduct(): Response
    {
        if (! perm('order')) {
            return $this->denyAjaxPage('order');
        }

        return $this->out(view('shop.cart.load_choose_product', [
            'rate' => Lookup::getSetting('rate_order'),
            'upload' => Upload::getupload('file', '', true, false),
        ])->render());
    }

    /** _cart/save_product — ใส่สินค้าลงตะกร้า (form #searchdetail) */
    public function saveProduct(Request $request): Response
    {
        $uid = $this->uid();
        // เดิม str_replace("'", "''") ซ้ำบนค่าที่ถูกแปลง ' → '' แล้ว → ค่าที่ลง DB = ค่าในตัวแปร PHP เดิม (postPhp)
        $f = fn (string $k) => $this->postPhp($request, $k);
        $uploadFile = $f('upload_file');
        // SR: if ($url != "") { INSERT cart_detail } — ลิงก์ว่าง = ไม่เพิ่มรายการ (แต่ยังสร้างตะกร้า/ตั้ง cookie/ตอบสำเร็จแบบเดิม)
        $hasUrl = $f('search_url') !== '';

        if (! $hasUrl) {
            $cart = DB::transaction(fn () => CartRepo::findOrCreate($uid));

            return $this->savedProductResponse($request, $cart, $uploadFile);
        }

        // เดิมไม่ตรวจ: จำนวน/ราคาติดลบลงตะกร้าได้ แล้วไปเป็นยอดบิลติดลบตอน checkout
        if (! self::positiveInt($f('search_amount')) || Php74::num($f('search_price')) < 0) {
            return $this->out(Alerts::error('CloseLoading()', 'จำนวนหรือราคาไม่ถูกต้อง'));
        }

        // แก้จากเดิม (เจ้าของอนุมัติ 2026-10-18 "ให้แอดมินแก้ไขก็พอ"): ลูกค้ากำหนดราคาเองไม่ได้
        // ราคา = ราคาที่ crawler ให้ตอนโหลดสินค้า (_api/load_product) — ราคาที่ส่งมาใช้ได้เฉพาะเมื่อตรงกับราคาของ SKU/ช่วงจำนวน
        // ผู้ถือ admin_mall (แอดมินร้านค้า) ใส่ราคาเองได้แบบเดิม — SR: ไม่มี role ใดถือ admin_mall (คำถามเจ้าของ ดู docs/modules/shop.md)
        $price = $f('search_price');
        if (! perm('admin_mall')) {
            $price = CrawlPrices::resolve($uid, $this->post($request, 'search_url'), $price);
            if ($price === null) {
                return $this->out(Alerts::error('CloseLoading()', 'ไม่พบข้อมูลราคาสินค้า กรุณาค้นหาสินค้าใหม่อีกครั้ง'));
            }
        }

        $cart = DB::transaction(function () use ($uid, $f, $uploadFile, $price) {
            $cart = CartRepo::findOrCreate($uid);
            DB::table('cart_detail')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'cart_id' => $cart->id,
                'url' => $f('search_url'), 'product_name' => $f('search_product_name'), 'shop_name' => $f('search_shop_name'),
                'main_image' => $f('search_main_image'), 'price' => $price, 'color' => $f('search_color'),
                'amount' => $f('search_amount'), 'remark' => $f('search_remark'), 'upload_file' => $uploadFile,
                'size' => $f('search_size'), 'user' => (string) $uid,
            ]);

            return $cart;
        });

        return $this->savedProductResponse($request, $cart, $uploadFile);
    }

    private function savedProductResponse(Request $request, object $cart, string $uploadFile): Response
    {
        // setcookie(cookiename_cart, $cart_id, time() + 604800, cookie_path) — เดิมไม่ใช่ httponly
        Cookie::queue(Cookie::make(config('legacy.cookies.cart'), (string) $cart->random, 10080,
            config('legacy.cookie_path', '/'), null, false, false, true, 'lax'));

        $js = $uploadFile !== '' ? Upload::blobJs($uploadFile) : '';
        $fn = str_contains((string) $request->headers->get('referer', ''), 'cart/checkout') ? 'window.location.reload();' : '';

        return $this->out($js.Alerts::success('$(".modal").modal("hide");swal.close();LoadCart();CloseLoading();'.$fn,
            Lang::get('already_add_to_cart')));
    }

    /** _cart/delete_cart — ลบ 1 รายการ (index = cart_detail.id) */
    public function deleteCart(Request $request): Response
    {
        $cart = CartRepo::find($this->uid());
        if ($cart !== null) {
            DB::table('cart_detail')->where('id', $this->post($request, 'index'))->where('cart_id', $cart->id)->delete();
        }

        return $this->out(Alerts::success('$(".modal").modal("hide");swal.close();LoadCart();CloseLoading();'));
    }

    /** _cart/delete_carts — ลบหลายรายการ (index = ",1,2,3") — เดิมต่อ string ใน IN (...) = SQL injection */
    public function deleteCarts(Request $request): Response
    {
        $cart = CartRepo::find($this->uid());
        $ids = CartRepo::parseIdList(substr($this->post($request, 'index'), 1));
        if ($cart !== null && $ids !== null) {
            DB::table('cart_detail')->whereIn('id', $ids)->where('cart_id', $cart->id)->delete();
        }

        return $this->out(Alerts::success('$(".modal").modal("hide");swal.close();LoadCart();CloseLoading();'));
    }

    /** _cart/save_amount / save_craft / save_qc — แก้ค่าในรายการของตัวเอง (ไม่ตอบอะไร) */
    public function saveAmount(Request $request): Response
    {
        // เดิมรับค่าอะไรก็ได้ (ติดลบ → th_price/total ของบิลติดลบตอน checkout) → ต้องเป็นจำนวนเต็มบวก ไม่งั้นไม่แก้ (ตอบว่างเหมือนเดิม)
        if (! self::positiveInt($this->post($request, 'value'))) {
            return $this->out('');
        }

        return $this->updateField($request, 'amount');
    }

    /** จำนวนชิ้น: จำนวนเต็ม >= 1 */
    private static function positiveInt(string $v): bool
    {
        return preg_match('/^\d{1,9}$/', trim($v)) === 1 && (int) trim($v) >= 1;
    }

    public function saveCraft(Request $request): Response
    {
        return $this->updateField($request, 'is_craft');
    }

    public function saveQc(Request $request): Response
    {
        return $this->updateField($request, 'is_qc');
    }

    /** _cart/save_takeapic (SR) — บริการถ่ายรูปสินค้า: UPDATE cart_detail SET is_takeapic WHERE id AND user = me */
    public function saveTakeapic(Request $request): Response
    {
        return $this->updateField($request, 'is_takeapic');
    }

    private function updateField(Request $request, string $column): Response
    {
        DB::table('cart_detail')
            ->where('id', $this->post($request, 'id'))
            ->where('user', (string) $this->uid())
            ->update([$column => $this->post($request, 'value')]);

        return $this->out('');
    }

    /**
     * _cart/save_checkout — ยืนยันสั่งซื้อ: สร้าง bill_product + bill_product_detail + tracking (1 ต่อร้าน)
     * แก้จากเดิม: หาตะกร้าจาก random ที่ส่งมา "และเป็นของผู้ใช้" (เดิมใช้ random ของใครก็ได้), bind ทุกค่า, ห่อ transaction
     */
    public function saveCheckout(Request $request): Response
    {
        $uid = $this->uid();
        $id = $this->post($request, 'id');
        $idsB64 = $this->post($request, 'ids');
        $rate = Lookup::getSetting('rate_order');
        $quote = str_replace("'", '', $this->post($request, 'quote'));
        $ship = $this->post($request, 'ship');

        if ($id === '') {
            return $this->out(Alerts::error('CloseLoading()', Lang::get('order_error')));
        }

        $billId = DB::transaction(function () use ($uid, $id, $idsB64, $rate, $quote, $ship) {
            $cart = DB::table('cart')->where('random', $id)->where('user', $uid)->orderBy('id')->lockForUpdate()->first(['id']); // กดซ้ำพร้อมกัน: คำขอที่สองรอ แล้วเห็นตะกร้าที่ถูกตัดรายการแล้ว
            $ids = CartRepo::decodeIds($idsB64);
            $details = ($cart !== null && $ids !== null)
                ? DB::table('cart_detail')->where('cart_id', $cart->id)->whereIn('id', $ids)->orderBy('id')->get()->all()
                : [];
            // เดิมไม่มีรายการ (กดสั่งซื้อซ้ำ = รายการถูกลบออกจากตะกร้าไปแล้ว / ids ไม่ใช่ของตะกร้านี้) ก็ยังสร้างใบสั่งซื้อเปล่ายอด 0
            // → ไม่สร้างอะไร (ตรวจภายใน transaction หลังอ่านตะกร้า)
            if ($details === []) {
                return null;
            }

            $total = 0;
            foreach ($details as $it) {
                $total = $total + ((Php74::num($it->price) * Php74::num($it->amount)) * Php74::num($rate));
            }

            $now = DB::raw('NOW()');
            $audit = ['create_date' => $now, 'create_by' => $uid, 'mod_date' => $now, 'mod_by' => $uid, 'is_delete' => 0];
            $billId = DB::table('bill_product')->insertGetId($audit + [
                'user' => (string) $uid, 'total' => Php74::str($total), 'usecash' => '0', 'exchange_rate' => $rate,
                'attachment' => '', 'submit_date' => null, 'status' => '0', 'quote' => $quote, 'ship' => $ship,
            ]);

            // SR: if ($item['url'] != "") — รายการที่ไม่มีลิงก์ไม่ลง bill_product_detail (แต่ยังนับในยอด total แบบเดิม)
            $withUrl = array_values(array_filter($details, fn ($it) => (string) $it->url !== ''));
            if ($withUrl !== []) {
                DB::table('bill_product_detail')->insert(array_map(fn ($it) => $audit + [
                    'bill_product_id' => $billId, 'url' => $it->url, 'product_name' => $it->product_name,
                    'shop_name' => $it->shop_name, 'main_image' => $it->main_image, 'price' => $it->price,
                    'color' => $it->color, 'amount' => $it->amount, 'remark' => $it->remark,
                    'upload_file' => $it->upload_file, 'size' => $it->size, 'user' => (string) $uid,
                    'is_craft' => $it->is_craft, 'is_qc' => $it->is_qc,
                    'th_price' => Php74::str((Php74::num($it->price) * Php74::num($it->amount)) * Php74::num($rate)),
                    'exchange_rate' => $rate, 'amount_before' => $it->amount, 'is_takeapic' => (int) $it->is_takeapic,
                ], $withUrl));
            }

            // 1 tracking ต่อร้าน — GROUP BY shop_name แบบเดิม (sql_mode ว่าง: MySQL เลือกแถวแรกของกลุ่ม)
            $groups = DB::select('SELECT bill_product_detail.id, shop_name, is_craft, is_qc, is_takeapic, bill_product.ship FROM `bill_product_detail`
                LEFT JOIN bill_product ON bill_product.id = bill_product_detail.bill_product_id
                WHERE `bill_product_id` = ? GROUP BY `shop_name`', [$billId]);
            foreach ($groups as $g) {
                $trackId = DB::table('tracking')->insertGetId($audit + [
                    'tracking' => '', 'order' => '', 'price_kg' => '0.00', 'price_cubic' => '0.00', 'rate_kg' => '',
                    'rate_cubic' => '', 'product_type_id' => '', 'lot_id' => '0', 'user' => (string) $uid,
                    'weight' => '0.0', 'width' => '0.0', 'height' => '0.0', 'long' => '0.0', 'amount' => '1',
                    'ship' => $ship, 'payment_id' => '0', 'is_craft' => $g->is_craft, 'is_qc' => $g->is_qc, 'quote' => $quote,
                    'is_takeapic' => (int) $g->is_takeapic,
                ]);
                // เดิม bill_product_id = id ของ "cart" (ตัวแปร $data ยังเป็นแถว cart) — คงค่าเดิมไว้ ดู docs/modules/shop.md
                DB::table('bill_product_on_tracking')->insert($audit + [
                    'bill_product_id' => $cart->id ?? '', 'bill_product_detail_id' => $g->id,
                    'tracking_id' => $trackId, 'user' => (string) $uid,
                ]);
            }

            // ลบรายการที่สั่งแล้วออกจากตะกร้า (ตะกร้าแรกของผู้ใช้ เหมือนเดิม)
            $mine = CartRepo::find($uid);
            if ($mine !== null && $ids !== null) {
                DB::table('cart_detail')->whereIn('id', $ids)->where('cart_id', $mine->id)->delete();
            }

            return $billId;
        });
        if ($billId === null) {
            return $this->out(Alerts::error('CloseLoading()', Lang::get('order_error')));
        }

        // clearcookie(cookiename_cart): ลบเฉพาะเมื่อมี cookie (path / และ /cargo แบบเดิม)
        $name = (string) config('legacy.cookies.cart');
        if ($request->cookies->has($name)) {
            Cookie::queue(Cookie::forget($name, '/'));
            Cookie::queue(Cookie::make($name, '', -60, '/cargo'));
        }

        return $this->out(Alerts::success('window.location.reload();',
            Lang::get('order_complete').Lookup::getPrefix('order').$billId));
    }
}
