<?php

namespace App\Http\Controllers\Shop\Admin;

use App\Domain\Shop\Php74;
use App\Http\Controllers\Shop\ShopController;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * _admin/_mallstock/*.php เดิม — ประวัติสต๊อกร้านค้า (สิทธิ์ admin_mall_stock)
 * load_amount / save_amount เดิมเป็นสำเนาของ _admin/_balance (สิทธิ์ admin_balance, แก้ตาราง balance) — หน้าเว็บเรียก
 * _admin/_balance/* ตัวจริง ไฟล์ของกลุ่มนี้ไม่มีใครเรียก แต่เปิดตรงได้ → แก้แล้วให้ใช้สิทธิ์ admin_mall_stock และแตะเฉพาะ mall_stock
 */
class MallStockController extends ShopController
{
    /** admin/mall_stock = _admin/_mallstock/index.php */
    public function index(): View
    {
        return view('shop.admin.mall_stock.index');
    }

    /**
     * _admin/_mallstock/grid
     * คงเหลือหลังรายการ = SUM(amount) ของรายการก่อนหน้า (id < แถวนี้, mall เดียวกัน) + แถวนี้
     * เดิม query ทีละแถว (N+1) → query เดียวด้วย self-join ของแถวในหน้านั้น
     */
    public function grid(Request $request): Response
    {
        $ajaxpage = '_admin/_mallstock/grid';
        $txt = $this->txtSearch($request);
        $q = DB::table('mall_stock')
            ->leftJoin('mall_detail', 'mall_detail.id', '=', 'mall_stock.mall_detail_id')
            ->leftJoin('mall', 'mall.id', '=', 'mall_detail.mall_id')
            ->where('mall_stock.is_delete', 0)
            ->select('mall_stock.*', 'mall_detail.color', 'size', 'mall.code', 'mall.name');
        if ($txt[0] !== '') {
            $like = self::like(str_replace((string) Lookup::getPrefix('mall_stock'), '', $txt[0]));
            $q->where(fn ($w) => $w->where('mall.name', 'like', $like)->orWhere('mall.code', 'like', $like));
        }
        [$offset, $page] = $this->pageOffset($request);
        [$data, $rows] = $this->gridRows($q, $offset, fn ($q) => $q->orderByDesc('mall_stock.id'));

        $before = [];
        if ($data !== []) {
            $before = DB::table('mall_stock as s')
                ->join('mall_stock as p', function ($j) {
                    $j->on('p.mall_id', '=', 's.mall_id')->on('p.id', '<', 's.id')->where('p.is_delete', '=', 0);
                })
                ->whereIn('s.id', array_map(fn ($r) => $r->id, $data))
                ->groupBy('s.id')->selectRaw('s.id, SUM(p.amount) as c')->pluck('c', 'id')->all();
        }

        return $this->out(view('shop.admin.mall_stock.grid', [
            'data' => $data, 'rows' => $rows, 'Page' => $page, 'ajaxpage' => $ajaxpage, 'before' => $before,
            'prefix' => (string) Lookup::getPrefix('mall_stock'),
            'types' => DB::table('balance_type')->orderBy('id')->get()->all(),
            'typesActive' => DB::table('balance_type')->where('is_delete', 0)->orderBy('id')->get()->all(),
            'txtSearch' => $this->txtSearch($request, true), 'txtsearchRaw' => $this->postPhp($request, 'txtsearch'),
        ])->render());
    }

    /**
     * _admin/_mallstock/load_amount — แถว mall_stock + รหัสลูกค้า (JSON แบบ mysqli_fetch_array: มีทั้ง key ตัวเลขและชื่อ)
     * แก้จากเดิม (เจ้าของอนุมัติ 2026-10-18): เดิมเป็นสำเนาของ _admin/_balance (สิทธิ์ admin_balance) อ่านแถว `balance` ใดก็ได้
     * → สิทธิ์ admin_mall_stock และอ่านเฉพาะแถว `mall_stock` ที่ยังไม่ถูกลบ
     */
    public function loadAmount(Request $request): Response
    {
        $id = $this->post($request, 'id');
        $row = ctype_digit($id) ? DB::table('mall_stock')->leftJoin('user', 'user.id', '=', 'mall_stock.user')
            ->where('mall_stock.id', (int) $id)->where('mall_stock.is_delete', 0)
            ->select('mall_stock.*', 'user.code')->first() : null;
        if ($row === null) {
            return $this->out('null');
        }
        // mysqli_fetch_array (MYSQLI_BOTH) → ทุกค่าเป็น string, มี key 0..n ตามลำดับคอลัมน์ด้วย
        $out = [];
        $i = 0;
        foreach ((array) $row as $k => $v) {
            $s = $v === null ? null : (string) $v;
            $out[$i++] = $s;
            $out[$k] = $s;
        }

        return $this->out(json_encode($out));
    }

    /**
     * _admin/_mallstock/save_amount — แก้/เพิ่มรายการเคลื่อนไหวสต๊อก (mall_stock)
     * แก้จากเดิม (เจ้าของอนุมัติ 2026-10-18): เดิมเป็นสำเนาของ _admin/_balance/save_amount → ผู้ถือ admin_balance
     * INSERT/UPDATE แถว `balance` (ยอดเงินลูกค้า) ใดก็ได้ผ่าน URL นี้ → ที่นี่:
     *   - สิทธิ์ admin_mall_stock (route) และไม่แตะตาราง balance เลย
     *   - id = แถว mall_stock ที่มีอยู่ (is_delete = 0) → แก้ได้เฉพาะ amount (จำนวนเต็ม), detail และลูกค้าอ้างอิง (code)
     *   - id ว่าง = เพิ่มแถวใหม่ ต้องส่ง mall_detail_id ของสินค้าในร้านที่มีอยู่จริง (mall_id เอาจาก mall_detail)
     *   - `type` (ประเภทรายการ balance) ไม่ถูกใช้
     */
    public function saveAmount(Request $request): Response
    {
        $uid = $this->uid();
        $noData = "ErrorBox('".Lang::get('no_data')."', 'CloseLoading()'); ";
        $amount = trim($this->post($request, 'amount'));
        if (preg_match('/^-?\d{1,9}$/', $amount) !== 1) {
            return $this->out("ErrorBox('จำนวนไม่ถูกต้อง', 'CloseLoading()'); ");
        }
        $code = $this->post($request, 'code');
        $userId = null;
        if ($code !== '') {
            $user = DB::table('user')->where('code', $code)->first(['id']);
            if ($user === null) {
                return $this->out("ErrorBox('".Lang::get('no_user')."', 'CloseLoading()'); ");
            }
            $userId = (int) $user->id;
        }
        $id = $this->post($request, 'id');
        $now = DB::raw('NOW()');
        $vals = ['amount' => (int) $amount, 'detail' => $this->post($request, 'detail'), 'mod_date' => $now, 'mod_by' => $uid];
        if ($userId !== null) {
            $vals['user'] = $userId;
        }

        if ($id === '') {
            $detailId = $this->post($request, 'mall_detail_id');
            $detail = ctype_digit($detailId)
                ? DB::table('mall_detail')->where('id', (int) $detailId)->where('is_delete', 0)->first(['id', 'mall_id'])
                : null;
            if ($detail === null) {
                return $this->out($noData);
            }
            DB::table('mall_stock')->insert($vals + [
                'create_date' => $now, 'create_by' => $uid, 'is_delete' => 0,
                'mall_id' => (int) $detail->mall_id, 'mall_detail_id' => (int) $detail->id, 'date' => $now,
                'user' => $userId ?? 0, 'pay_type' => 0,
            ]);
        } else {
            if (! ctype_digit($id)) {
                return $this->out($noData);
            }
            $n = DB::table('mall_stock')->where('id', (int) $id)->where('is_delete', 0)->update($vals);
            if ($n === 0 && ! DB::table('mall_stock')->where('id', (int) $id)->where('is_delete', 0)->exists()) {
                return $this->out($noData);
            }
        }

        return $this->out(Alerts::success());
    }

    /** คงเหลือแบบ numbercal() เดิม (round 2 ตำแหน่ง แล้ว echo float) */
    public static function remain(mixed $amount, mixed $before): string
    {
        return Php74::str(Format::numbercal(Php74::num($amount) + Php74::num($before)));
    }
}
