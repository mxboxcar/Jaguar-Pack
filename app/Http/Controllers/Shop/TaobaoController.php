<?php

namespace App\Http\Controllers\Shop;

use App\Domain\Shop\Php74;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * _taobao/*.php เดิม — ฝากจ่ายเงิน Taobao (ลูกค้าส่งเลขออเดอร์ + ราคาหยวน ระบบตัดเงินในระบบ/เงินหยวน)
 * ทุกไฟล์เดิมตรวจ getPermission('taobao'); ข้อมูลเป็นของผู้ใช้ที่ล็อกอินเท่านั้น
 */
class TaobaoController extends ShopController
{
    /** taobao = _taobao/index.php (ตรวจสิทธิ์ใน view แบบเดิม: แสดงหัวหน้าก่อนแล้วค่อยเด้ง) */
    public function index(): View
    {
        return view('shop.taobao.index');
    }

    /** _taobao/grid — เดิมไม่มีสิทธิ์ = exit เงียบ (ตอบว่าง) */
    public function grid(Request $request): Response
    {
        if (! perm('taobao')) {
            return $this->out('');
        }
        $ajaxpage = '_taobao/grid';
        $txt = $this->txtSearch($request);
        $prefix = (string) Lookup::getPrefix('taobao');
        $term = str_replace(strtolower($prefix), '', strtolower($txt[0]));

        $q = DB::table('taobao')->where('is_delete', 0)->where('user', $this->uid())
            ->where('id', 'like', self::like($term));
        [$offset, $page] = $this->pageOffset($request);
        [$data, $rows] = $this->gridRows($q, $offset, fn ($q) => $q->orderByDesc('id'));

        // เดิม SELECT price, status FROM taobao_detail ทีละแถว (N+1) → ครั้งเดียวทั้งหน้า
        $details = $data === [] ? [] : DB::table('taobao_detail')
            ->whereIn('taobao_id', array_map(fn ($r) => $r->id, $data))
            ->orderBy('id')->get(['taobao_id', 'price', 'status'])->groupBy('taobao_id')->all();

        return $this->out(view('shop.taobao.grid', [
            'data' => $data, 'rows' => $rows, 'Page' => $page, 'ajaxpage' => $ajaxpage, 'prefix' => $prefix,
            'details' => $details, 'txtSearch' => $this->txtSearch($request, true),
            'txtsearchRaw' => $this->postPhp($request, 'txtsearch'),
        ])->render());
    }

    /** taobao/create, taobao/{id} = _taobao/form.php */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id', 0);
        $uid = $this->uid();
        $data = DB::table('taobao')->where('id', $id)->where('is_delete', 0)->where('user', $uid)->first();
        if ($data === null && $id !== 0) {
            return view('legacy.no-permission', ['module' => 'shop_cashback']);
        }
        $details = $data === null ? [] : DB::table('taobao_detail')->where('taobao_id', $data->id)->orderBy('id')->get()->all();
        $sum = $data === null ? null : DB::table('taobao_detail')->where('taobao_id', $data->id)
            ->selectRaw('count(id) as count, SUM(price) as sum')->first();

        // รายการเงินที่อ้างถึงใบนี้ (balance + balance_cn) เรียงตาม create_date (usort sortFunction เดิม — stable)
        $prefixId = Lookup::getPrefixID('taobao');
        $balance = [];
        foreach (['balance', 'balance_cn'] as $t) {
            foreach (DB::table($t)->leftJoin('prefix', $t.'.prefix', '=', 'prefix.id')
                ->where($t.'.prefix', $prefixId)->where($t.'.prefix_ref_id', $id)->where($t.'.is_delete', 0)
                // เดิมไม่กรองเจ้าของ: หน้า taobao/create (id 0) แสดงรายการเงินอ้าง prefix_ref_id = 0 ของทุกคน
                ->where($t.'.user', $uid)
                ->orderBy($t.'.id')->select($t.'.*', 'prefix.link as link')->get() as $row) {
                $row->types = $t;
                $balance[] = $row;
            }
        }
        usort($balance, fn ($a, $b) => (int) strtotime((string) $a->create_date) - (int) strtotime((string) $b->create_date));

        return view('shop.taobao.form', [
            'id' => $id, 'data' => $data, 'details' => $details, 'sum' => $sum, 'balance' => $balance,
            'prefix' => (string) Lookup::getPrefix('taobao'),
            'cash' => $data === null ? Lookup::getCurrentCash() : null,
            'cashCn' => $data === null ? Lookup::getCurrentCashCN() : null,
        ]);
    }

    /** _taobao/load_new_line — แถวใหม่ในตาราง */
    public function loadNewLine(): Response
    {
        return $this->out(view('shop.taobao.load_new_line')->render());
    }

    /** _taobao/load_calculator — คำนวณยอด (JSON) */
    public function loadCalculator(Request $request): Response
    {
        $order = $this->postPhp($request, 'order');
        $price = $this->postPhp($request, 'price');
        $byOrder = Lookup::getSetting('charge_taobao');
        $rate = Lookup::getSetting('rate_transfer');
        $cashCn = Lookup::getCurrentCashCN();
        $n = fn ($v) => Php74::num($v);

        if ($n($price) >= $n($cashCn)) {
            $useCash = $n($price) - $n($cashCn);
        } else {
            $useCash = 0;
        }

        $data = [
            'order' => $order,
            'total_order' => '('.$price.'-'.$cashCn.') x '.$rate.' = '.Format::numberformat($useCash * $n($rate)),
            'price_th' => $order.' x '.$byOrder.' = '.Format::numberformat($n($order) * $n($byOrder)),
            'price_cn' => Format::numberformat(($n($order) * $n($byOrder)) + $useCash * $n($rate)),
            'rate' => Format::numberformat($rate),
            'cash_cn' => Format::numberformat($cashCn, true, true, true),
        ];
        $data['total_thai'] = $useCash < 0 ? Format::numberformat(0) : Format::numberformat($useCash * $n($rate));

        return $this->out(json_encode($data));
    }

    /** _taobao/save_remark — แก้หมายเหตุ (ของตัวเองเท่านั้น) */
    public function saveRemark(Request $request): Response
    {
        DB::table('taobao')->where('id', $this->post($request, 'id'))->where('user', (string) $this->uid())
            ->update(['remark' => $this->post($request, 'remark')]);

        return $this->out(Alerts::success());
    }

    /**
     * _taobao/save — สร้างรายการใหม่ (ตัดเงิน balance + balance_cn) หรือแก้หมายเหตุ
     * แก้จากเดิม: bind ทุกค่า, ห่อ transaction + ล็อกยอดเงินของผู้ใช้ (กันกดซ้ำพร้อมกันจนเงินติดลบ),
     * ตอนแก้ไขกรอง user (เดิม UPDATE taobao ... WHERE id = ? ของใครก็ได้)
     */
    public function save(Request $request): Response
    {
        $uid = $this->uid();
        $id = $this->post($request, 'id');
        $remark = $this->post($request, 'remark');
        $prefix = (string) Lookup::getPrefix('taobao');

        // เดิม if ($id == "0") — string กับ string ตัวเลขเทียบเชิงตัวเลขเหมือนกันทั้ง 7.4/8.4
        if (! ($id == '0')) {
            DB::table('taobao')->where('id', $id)->where('user', (string) $uid)->update(['remark' => $remark]);

            // $id มาจาก POST — escape ก่อนฝังใน JS string (เดิมต่อดิบ = JS injection ในผล ajax ที่หน้าเว็บ eval)
            return $this->out(Alerts::success('window.location.href = "'.legacy_href('taobao').'";',
                Lang::get('success_process_no').$prefix.Alerts::js($id)));
        }

        $n = fn ($v) => Php74::num($v);
        $orderArr = $request->request->all()['order'] ?? [];
        $priceArr = $request->request->all()['price'] ?? [];
        $orderArr = is_array($orderArr) ? array_values(array_map(fn ($v) => is_string($v) ? str_replace('\\', '', $v) : '', $orderArr)) : [];
        $priceArr = is_array($priceArr) ? array_values(array_map(fn ($v) => is_string($v) ? str_replace('\\', '', $v) : '', $priceArr)) : [];
        $name = $this->post($request, 'taobao_name');
        $password = $this->post($request, 'taobao_password');
        $rate = Lookup::getSetting('rate_transfer');
        $byOrder = Lookup::getSetting('charge_taobao');

        // เดิมรับราคาติดลบได้: ใส่ [1000, -1000] = ยอดรวม 0 → จ่ายแค่ค่าบริการ แต่ taobao_detail มีออเดอร์ ¥1000 ให้แอดมินจ่ายแทน
        // → ราคาทุกช่องต้องไม่ติดลบ (ค่าว่าง/ไม่ใช่ตัวเลข = 0 แบบเดิม)
        foreach ($priceArr as $p) {
            if ($n($p) < 0) {
                return $this->out(Alerts::error('CloseLoading()', 'ราคาไม่ถูกต้อง (ติดลบ)'));
            }
        }

        return DB::transaction(function () use ($uid, $n, $orderArr, $priceArr, $name, $password, $rate, $byOrder, $remark, $prefix) {
            // ล็อกแถวเงินของผู้ใช้ระหว่างตรวจยอด/ตัดเงิน
            DB::table('balance')->where('user', $uid)->lockForUpdate()->count('id');
            DB::table('balance_cn')->where('user', $uid)->lockForUpdate()->count('id');
            $cashCn = Lookup::getCurrentCashCN();
            $cash = Lookup::getCurrentCash();

            $orders = count($orderArr);
            $prices = 0;
            foreach ($priceArr as $p) {
                $prices = $prices + $n($p);
            }
            if ($prices >= $n($cashCn)) {
                $useCash = $prices - $n($cashCn);
                $useBalance = $cashCn;
            } else {
                $useCash = 0;
                $useBalance = $prices;
            }
            $useCash = ($useCash * $n($rate)) + ($orders * $n($byOrder));

            // เดิม if ((string)$use_cash != (string)getCurrentCash()) { if ($use_cash > getCurrentCash()) error }
            if ($useCash > $n($cash)) {
                return $this->out(Alerts::error('CloseLoading()', Lang::get('error_usingcash')));
            }

            $now = DB::raw('NOW()');
            $audit = ['create_date' => $now, 'create_by' => $uid, 'mod_date' => $now, 'mod_by' => $uid, 'is_delete' => 0];
            $newId = DB::table('taobao')->insertGetId($audit + [
                'rate' => $rate, 'charge' => $byOrder, 'price' => Php74::str($useCash * $n($rate)),
                'taobao_name' => $name, 'taobao_password' => $password, 'status' => '1', 'user' => (string) $uid,
                'approved_remark' => '', 'approved_date' => '0000-00-00 00:00:00', 'approved_upload' => '', 'cancel_remark' => '',
                'usecash' => Php74::str($useCash), 'usecash_cn' => Php74::str($useBalance), 'remark' => $remark, 'remark_admin' => '',
            ]);
            if ($orders > 0) {
                $rows = [];
                for ($i = 0; $i < $orders; $i++) {
                    $rows[] = $audit + ['taobao_id' => $newId, 'order' => $orderArr[$i], 'price' => $priceArr[$i] ?? '', 'status' => '1'];
                }
                DB::table('taobao_detail')->insert($rows);
            }
            $prefixId = Lookup::getPrefixID('taobao');
            $uc = Php74::str($useCash);
            if ($uc !== '' && $uc !== '0.00' && $uc !== '0') {
                DB::table('balance')->insert($audit + ['user' => (string) $uid, 'amount' => '-'.$uc, 'detail' => $prefix.$newId,
                    'type' => '7', 'prefix' => $prefixId, 'prefix_ref_id' => $newId]);
            }
            $ub = Php74::str($useBalance);
            if ($ub !== '' && $ub !== '0.00' && $ub !== '0') {
                DB::table('balance_cn')->insert($audit + ['user' => (string) $uid, 'amount' => '-'.$ub, 'detail' => $prefix.$newId,
                    'type' => '7', 'prefix' => $prefixId, 'prefix_ref_id' => $newId]);
            }

            return $this->out(Alerts::success('window.location.href = "'.legacy_href('taobao').'";',
                Lang::get('success_process_no').$prefix.$newId));
        });
    }

    /**
     * _taobao/delete — ไฟล์เดิม require '../../system_check_ajax.php' (path ผิด: อยู่นอก webroot) → fatal error 500 ทุกครั้ง
     * (โค้ดข้างในเป็นสำเนาของ _admin/_product_type/delete: ลบ product_type) — คงผลเดิม: 500 ว่าง ไม่แตะข้อมูล
     */
    public function delete(): Response
    {
        return $this->out('', 500);
    }
}
