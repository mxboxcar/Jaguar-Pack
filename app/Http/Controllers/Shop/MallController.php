<?php

namespace App\Http\Controllers\Shop;

use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use App\Domain\Shop\Php74;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * _mall/*.php เดิม — ร้านค้าในระบบ (สินค้าที่แอดมินลงไว้ ซื้อด้วยเงินในระบบ)
 */
class MallController extends ShopController
{
    /** mall, mall/{search} = _mall/index.php (ตรวจสิทธิ์ใน view แบบเดิม) */
    public function index(Request $request): View
    {
        return view('shop.mall.index', [
            'search' => strip_tags(trim((string) $request->route('search', ''))),
            'status' => is_string($s = $request->query('status')) ? strip_tags(trim($s)) : '',
        ]);
    }

    /** mall/create, mall/{id} — ชี้ไป _mall/form.php ซึ่งไม่มีไฟล์ในเว็บเดิม (หน้าเดิมหยุดกลางคัน = ว่าง) */
    public function detail(): View
    {
        return view('shop.mall.detail');
    }

    /**
     * _mall/grid — เดิมตรวจสิทธิ์ 'refill' (ไม่ใช่ mall) แล้ว exit เงียบ — คงไว้
     * stock/min/max: เดิม subquery ต่อแถวใน SQL เดียว (ไม่ใช่ N+1 ฝั่ง PHP) — คงเป็น query เดียว + count
     */
    public function grid(Request $request): Response
    {
        if (! perm('refill')) {
            return $this->out('');
        }
        $ajaxpage = '_mall/grid';
        $txt = $this->txtSearch($request);
        $like = self::like($txt[0]);

        $base = DB::table('mall')
            ->select('mall.*')
            ->selectSub('SELECT SUM(amount) FROM mall_stock WHERE mall_stock.mall_id = mall.id', 'stock')
            ->selectSub('select price from mall_detail where mall_detail.mall_id = mall.id and mall_detail.is_delete = 0 order by price asc limit 1', 'mins')
            ->selectSub('select price from mall_detail where mall_detail.mall_id = mall.id and mall_detail.is_delete = 0 order by price desc limit 1', 'maxs')
            ->where(fn ($q) => $q->where('mall.name', 'like', $like)->orWhere('mall.code', 'like', $like))
            ->where('mall.is_delete', 0)
            ->havingRaw('stock != 0');

        $rows = DB::query()->fromSub($base, 't')->count();
        [$offset, $page] = $this->pageOffset($request);
        $data = $offset < 0 ? [] : (clone $base)->orderByDesc('mall.id')->offset($offset)->limit(10)->get()->all();

        return $this->out(view('shop.mall.grid', [
            'data' => $data, 'rows' => $rows, 'Page' => $page, 'ajaxpage' => $ajaxpage,
            'txtSearch' => $this->txtSearch($request, true), 'txtsearchRaw' => $this->postPhp($request, 'txtsearch'),
        ])->render());
    }

    /**
     * _mall/load_detail — รายละเอียดสินค้า + ตัวเลือก (เดิมไม่ตรวจสิทธิ์/ไม่ตรวจล็อกอิน → ต้องล็อกอิน + สิทธิ์ mall)
     * สต๊อกต่อตัวเลือก: เดิม getStock() ทีละแถว → GROUP BY ครั้งเดียว
     */
    public function loadDetail(Request $request): Response
    {
        $data = DB::table('mall')->where('mall.id', $this->post($request, 'id'))->first();
        $details = $data === null ? [] : DB::table('mall_detail')->where('mall_id', $data->id)->where('is_delete', 0)
            ->orderBy('id')->get()->all();
        $stock = $details === [] ? collect() : DB::table('mall_stock')
            ->where('mall_id', $data->id)->whereIn('mall_detail_id', array_map(fn ($d) => $d->id, $details))
            ->groupBy('mall_detail_id')->selectRaw('mall_detail_id, SUM(amount) as c')->pluck('c', 'mall_detail_id');

        return $this->out(view('shop.mall.load_detail', [
            'data' => $data, 'details' => $details, 'stock' => $stock,
        ])->render());
    }

    /**
     * _mall/addtocart — ซื้อทันที: สร้าง tracking (lot -99) + ตัดเงิน + ตัดสต๊อก
     * แก้จากเดิม: ต้องล็อกอิน + สิทธิ์ mall (เดิมไม่ตรวจ), จำนวนต้องเป็นจำนวนเต็มบวก (เดิมติดลบได้),
     * ห่อ transaction + ล็อกสต๊อก/ยอดเงิน (กันขายเกินสต๊อก/จ่ายซ้ำพร้อมกัน), bind ทุกค่า
     */
    public function addToCart(Request $request): Response
    {
        $uid = $this->uid();
        $id = $this->post($request, 'id');
        $amountRaw = $this->post($request, 'amount');

        return DB::transaction(function () use ($uid, $id, $amountRaw) {
            $data = DB::table('mall_detail')->where('id', $id)->where('is_delete', 0)->first();
            $head = $data === null ? null : DB::table('mall')->where('id', $data->mall_id)->first();
            if ($data !== null) {
                DB::table('mall_stock')->where('mall_id', $data->mall_id)->where('mall_detail_id', $data->id)->lockForUpdate()->count('id');
            }
            $stock = Lookup::getStock($data->mall_id ?? '', $data->id ?? '');
            $n = fn ($v) => Php74::num($v);

            if ($n($stock) <= 0) {
                return $this->out("ErrorBox('สต๊อคไม่เพียงพอ', 'CloseLoading()'); ");
            }
            if ($n($amountRaw) > $n($stock)) {
                return $this->out("ErrorBox('สต๊อคไม่เพียงพอ ขาด ".Php74::str($n($amountRaw) - $n($stock))." ชิ้น', 'CloseLoading()'); ");
            }
            if (! preg_match('/^\d+$/', trim($amountRaw)) || (int) $amountRaw < 1) {
                return $this->out("ErrorBox('".Alerts::js(Lang::get('add_more_then_one'))."', 'CloseLoading()'); ");
            }
            $amount = (int) $amountRaw;

            DB::table('balance')->where('user', $uid)->lockForUpdate()->count('id');
            $rand = Format::randd(6);
            $prices = $n($data->price) * $amount;
            $trackingName = $rand.'-'.($head->code ?? '').'-'.($head->name ?? '').'-'.$data->color.'-'.$data->size;

            if (Format::numberformatforsubmit(Lookup::getCurrentCash()) < Format::numberformatforsubmit($prices)) {
                return $this->out(Alerts::error('CloseLoading()', Lang::get('error_usingcash')));
            }

            $now = DB::raw('NOW()');
            $audit = ['create_date' => $now, 'create_by' => $uid, 'mod_date' => $now, 'mod_by' => $uid, 'is_delete' => 0];
            DB::table('tracking')->insert($audit + [
                'no' => '-99', 'tracking' => $trackingName, 'price_kg' => '', 'amount' => (string) $amount, 'user' => (string) $uid,
                'date_thai' => $now, 'lot_id' => '-99', 'width' => $head->width ?? '', 'height' => $head->height ?? '',
                'long' => $head->long ?? '', 'weight' => $head->weight ?? '',
            ]);
            DB::table('balance')->insert($audit + [
                'user' => (string) $uid, 'amount' => '-'.Php74::str($prices),
                'detail' => 'ชำระเงิน ('.$trackingName.') * '.$amount, 'type' => 15, 'prefix' => '', 'prefix_ref_id' => '',
            ]);
            DB::table('mall_stock')->insert($audit + [
                'mall_id' => $data->mall_id, 'mall_detail_id' => $data->id, 'amount' => '-'.$amount, 'date' => $now,
                'detail' => 'สั่งซื้อโดย '.(Legacy::user()->code ?? '').' ('.$rand.')', 'user' => '', 'pay_type' => '0',
            ]);

            return $this->out(Alerts::success('window.location.href = "'.legacy_href('mall').'";',
                'สั่งซื้อสำเร็จ <br/>คุณสามารถส่งสินค้าพร้อมกับออกบิลชำระค่าสินค้าได้ในทันที'));
        });
    }

    /** mall/cart = _mall/formcart.php (ตาราง mall_cart ของผู้ใช้) */
    public function formCart(): View|Response
    {
        if (! perm('mall')) {
            // เดิม getPermissionAlert('mall') (JS ไม่มี <script>) แล้ว exit กลางหน้า
            return view('shop.mall.raw', ['html' => Alerts::permission('mall')]);
        }
        $cart = DB::table('mall_cart')
            ->leftJoin('mall_detail', 'mall_detail.id', '=', 'mall_cart.mall_detail_id')
            ->leftJoin('mall', 'mall.id', '=', 'mall_detail.mall_id')
            ->where('mall_cart.user', $this->uid())
            ->select('mall.*', 'mall_detail.*', 'mall_cart.amount', 'mall.image as main_image', 'mall_cart.id as ids')
            ->get()->all();

        return view('shop.mall.formcart', ['cart' => $cart]);
    }

    /** _mall/delete_cart — เอาออกจากตะกร้า + คืนสต๊อก */
    public function deleteCart(Request $request): Response
    {
        $uid = $this->uid();

        return DB::transaction(function () use ($request, $uid) {
            $data = DB::table('mall_cart')
                ->leftJoin('mall_detail', 'mall_detail.id', '=', 'mall_cart.mall_detail_id')
                ->leftJoin('mall', 'mall.id', '=', 'mall_detail.mall_id')
                ->where('mall_cart.user', (string) $uid)->where('mall_cart.id', $this->post($request, 'index'))
                ->select('mall_cart.*', 'mall.id as mall_id', 'mall_detail.id as mall_detail_id')
                ->lockForUpdate()->first();
            if ($data === null) {
                return $this->out("ErrorBox('ไม่มีสินค้านี้', 'CloseLoading()'); ");
            }
            DB::table('mall_cart')->where('id', $data->id)->where('user', (string) $uid)->delete();
            $now = DB::raw('NOW()');
            DB::table('mall_stock')->insert([
                'create_date' => $now, 'create_by' => $uid, 'mod_date' => $now, 'mod_by' => $uid, 'is_delete' => 0,
                'mall_id' => $data->mall_id ?? '', 'mall_detail_id' => $data->mall_detail_id ?? '', 'amount' => $data->amount,
                'date' => $now, 'detail' => 'นำออกตระกร้า '.(Legacy::user()->code ?? ''), 'user' => '', 'pay_type' => '0',
            ]);

            return $this->out('window.location.reload(); ');
        });
    }

    /** _mall/save — ยืนยันสั่งซื้อทั้งตะกร้า: 1 tracking ต่อรายการ แล้วล้างตะกร้า */
    public function save(): Response
    {
        $uid = $this->uid();

        return DB::transaction(function () use ($uid) {
            $rows = DB::table('mall_cart')
                ->leftJoin('mall_detail', 'mall_detail.id', '=', 'mall_cart.mall_detail_id')
                ->leftJoin('mall', 'mall.id', '=', 'mall_detail.mall_id')
                ->where('mall_cart.user', (string) $uid)
                ->select('mall.*', 'mall_detail.size', 'mall_detail.color', 'mall_cart.amount', 'mall_detail.price')
                ->lockForUpdate()->get();
            if ($rows->isEmpty()) {
                return $this->out("ErrorBox('กรุณาเพิ่มรายการสินค้า', 'CloseLoading()'); ");
            }
            $now = DB::raw('NOW()');
            foreach ($rows as $row) {
                DB::table('tracking')->insert([
                    'create_date' => $now, 'create_by' => $uid, 'mod_date' => $now, 'mod_by' => $uid, 'is_delete' => 0,
                    'no' => '-99', 'tracking' => $row->code.'-'.$row->name.'-'.$row->color.'-'.$row->size,
                    'price_kg' => Php74::str(Php74::num($row->price) * Php74::num($row->amount)), 'amount' => $row->amount,
                    'user' => (string) $uid, 'date_thai' => $now, 'lot_id' => '-99',
                    'width' => $row->width, 'height' => $row->height, 'long' => $row->long, 'weight' => $row->weight,
                ]);
            }
            DB::table('mall_cart')->where('user', (string) $uid)->delete();

            return $this->out(Alerts::success('window.location.href = "'.legacy_href('mall').'";',
                'สั่งซื้อสำเร็จ <br/>คุณสามารถส่งสินค้าพร้อมกับออกบิลชำระค่าสินค้าได้ในทันที'));
        });
    }
}
