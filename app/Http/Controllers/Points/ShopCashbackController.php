<?php

namespace App\Http\Controllers\Points;

use App\Domain\Money\Money;
use App\Domain\Payment\PinBox;
use App\Domain\Points\Cashback;
use App\Domain\Points\Grid;
use App\Domain\Points\Php74;
use App\Domain\Points\TempUpload;
use App\Domain\Points\UploadInline;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * แจ้งร้านค้าคืนเงิน (ลูกค้า): _shop_cashback/*.php เดิม (สิทธิ์ shop_cashback) — ทุก query กรอง user = ผู้ที่ล็อกอิน
 */
class ShopCashbackController extends Controller
{
    private const AJAX = '_shop_cashback/grid';

    public const BAD_PRICE = 'กรุณาระบุจำนวนเงินที่ขอคืนให้ถูกต้อง (ตัวเลขมากกว่า 0 ทศนิยมไม่เกิน 2 ตำแหน่ง)';

    /** GET /shop_cashback — _shop_cashback/index.php */
    public function index(): View
    {
        return view('points.shop_cashback.index');
    }

    /** POST /_shop_cashback/grid — _shop_cashback/grid.php */
    public function grid(Request $request): Response
    {
        if (! perm('shop_cashback')) {
            return response('');
        }
        $s = Grid::search($request);
        $q = DB::table('shop_cashback')
            ->where('is_delete', 0)
            ->where('user', Legacy::userId())
            ->where('id', 'like', Grid::contains(Cashback::searchTerm($s['term'])));
        $page = Grid::php($request, 'page');
        [$total, $items] = Grid::page($q, 'id', $page, ['*']);

        return response(view('points.shop_cashback.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'details' => Cashback::detailsFor($items),
            's' => $s,
            'page' => $page,
            'total' => $total,
            'prefix' => Grid::prefixText('shop_cashback'),
        ])->render());
    }

    /** GET /shop_cashback/create (id 0), /shop_cashback/{id} — _shop_cashback/form.php */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id', 0);
        $data = DB::table('shop_cashback')->where('id', $id)->where('is_delete', 0)->where('user', Legacy::userId())->first();
        if ($data === null && $id !== 0) {
            // เดิม: ไม่ใช่ของตัวเอง / ไม่มีจริง → getPermissionAlert_Page('shop_cashback')
            return view('legacy.no-permission', ['module' => 'shop_cashback']);
        }
        $details = $data === null ? collect()
            : DB::table('shop_cashback_detail')->where('shop_cashback_id', $data->id)->orderBy('id')->get();

        return view('points.shop_cashback.form', [
            'id' => $id,
            'data' => $data,
            'details' => $details,
            'prices' => Cashback::sum($details),
            'prefix' => Grid::prefixText('shop_cashback'),
            'ledger' => Cashback::ledger($id),
            'upload' => UploadInline::render('file_0', '', true),
        ]);
    }

    /** POST /_shop_cashback/load_calculator — _shop_cashback/load_calculator.php */
    public function loadCalculator(Request $request): Response
    {
        // json_encode เดิม (escape / และ unicode) — JS เดิม $.parseJSON()
        return response(json_encode(['price_cn' => Format::numberformat(Grid::php($request, 'price'), true, true, true)]));
    }

    /** POST /_shop_cashback/load_new_line — _shop_cashback/load_new_line.php */
    public function loadNewLine(Request $request): Response
    {
        // เดิม $_POST['item'] ดิบ → ชื่อ class/ฟังก์ชัน JS (XSS) — JS เดิมส่งจำนวนแถว (ตัวเลข) เสมอ จึงบังคับเป็นจำนวนเต็ม
        $item = (int) Php74::num(Grid::php($request, 'item'));

        return response(view('points.shop_cashback.load_new_line', [
            'upload' => UploadInline::render('file_'.$item, '', true),
        ])->render());
    }

    /** POST /_shop_cashback/save — _shop_cashback/save.php (สร้างรายการใหม่เท่านั้น เหมือนเดิม) */
    public function save(Request $request): Response
    {
        // SR: ถาม PIN จ่ายเงินก่อนบันทึก (ของ jl ถูก comment ไว้) — getNewPinBox() / getPinBox(0) ต่อท้าย "form" แล้ว _profile/pin ส่งฟอร์มใหม่
        // เทียบ PIN ผ่าน PinBox::guard() (hash_equals + PinThrottle) แทน != หลวมของเดิม
        if (($js = PinBox::guard($request, Legacy::user())) !== null) {
            return response($js);
        }

        $uid = Legacy::userId();
        $orders = Grid::inArray($request, 'order');
        $dates = Grid::inArray($request, 'date');
        $prices = Grid::inArray($request, 'price');
        $remark = Grid::in($request, 'remark');

        // เดิมรับยอดอะไรก็ได้ (ติดลบ / 0 / "abc") แล้วแอดมินกดอนุมัติ = INSERT balance_cn ติดลบ (หักเงินหยวนลูกค้า)
        // ใหม่ (เจ้าของอนุมัติ): ทุกแถวต้องเป็นตัวเลขบวก ทศนิยมไม่เกิน 2 ตำแหน่ง (Money::validRefillAmount) ไม่งั้นไม่บันทึกอะไร
        if ($orders === []) {
            return response(Money::refuse(self::BAD_PRICE));
        }
        $clean = [];
        foreach (array_keys($orders) as $i) {
            $p = $prices[$i] ?? null;
            if (! is_scalar($p) || ! Money::validRefillAmount((string) $p)) {
                return response(Money::refuse(self::BAD_PRICE));
            }
            $clean[$i] = str_replace(',', '', trim((string) $p));
        }
        $prices = $clean;
        $total = 0.0;
        foreach ($prices as $p) {
            $total += (float) $p;
        }
        $total = Money::fmt($total);

        $id = DB::transaction(function () use ($uid, $orders, $dates, $prices, $remark, $total, $request) {
            $id = DB::table('shop_cashback')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'user' => $uid, 'total' => $total, 'remark' => $remark,
            ]);
            foreach ($orders as $i => $order) {
                $exp = explode('|', is_scalar($dates[$i] ?? null) ? (string) $dates[$i] : '');
                DB::table('shop_cashback_detail')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'shop_cashback_id' => $id,
                    'order' => is_scalar($order) ? (string) $order : '',
                    'price' => $prices[$i],
                    'status' => '1',
                    'attachment' => Grid::in($request, 'upload_file_'.$i),
                    'submit_date' => Format::getdatesubmit($exp[0]).' '.($exp[1] ?? ''),
                ]);
            }

            return $id;
        });

        $js = '';
        foreach (array_keys($orders) as $i) {
            $js .= TempUpload::jsIfExists(Grid::in($request, 'upload_file_'.$i));
        }

        return response($js.Alerts::success('window.location.href = "'.Legacy::href('shop_cashback').'";',
            Lang::get('success_process_no').Grid::prefixText('shop_cashback').$id));
    }

    /** POST /_shop_cashback/save_remark — _shop_cashback/save_remark.php */
    public function saveRemark(Request $request): Response
    {
        DB::table('shop_cashback')->where('id', Grid::in($request, 'id'))->where('user', Legacy::userId())
            ->update(['remark' => Grid::in($request, 'remark')]);

        return response(Alerts::success());
    }
}
