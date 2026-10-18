<?php

namespace App\Http\Controllers\Settings\Admin;

use App\Domain\Settings\Input;
use App\Domain\Settings\Php74;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ประเภทการจัดส่ง + ตารางราคาตามช่วง (shipping_type, shipping_type_rate): _admin/_shipping_type/*.php เดิม
 * สิทธิ์ admin_shipping_type ทุกไฟล์
 */
class ShippingTypeController extends Controller
{
    private const AJAX = '_admin/_shipping_type/grid';

    /** GET /admin/shipping_type — index.php */
    public function index(): View
    {
        return view('settings.admin.shipping_type.index');
    }

    /** POST /_admin/_shipping_type/grid — grid.php */
    public function grid(Request $request): Response
    {
        if (! perm('admin_shipping_type')) {
            return response(''); // เดิม exit() เงียบๆ
        }
        $term = explode('|', Input::in($request, 'txtsearch'))[0];
        $like = Input::contains($term);
        $q = DB::table('shipping_type')->where('is_delete', 0)
            ->where(fn ($w) => $w->where('id', 'like', $like)->orWhere('name', 'like', $like));
        $page = Input::php($request, 'page');
        [$total, $items] = Input::page($q, 'id', $page);

        return response(view('settings.admin.shipping_type.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'termPhp' => explode('|', Input::php($request, 'txtsearch'))[0],
            'raw' => Input::php($request, 'txtsearch'),
            'page' => $page,
            'total' => $total,
            'prefix' => (string) (Lookup::getPrefix('shipping_type') ?? ''),
        ])->render());
    }

    /** GET /admin/shipping_type/create (id 0), /admin/shipping_type/{id} — form.php */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id', 0); // $_GET['id'] เดิมถูก (int)
        return view('settings.admin.shipping_type.form', [
            'id' => $id,
            'data' => DB::table('shipping_type')->where('id', $id)->where('is_delete', 0)->first(),
            'prefix' => (string) (Lookup::getPrefix('shipping_type') ?? ''),
        ]);
    }

    /**
     * POST /_admin/_shipping_type/save — save.php
     * is_use_rate != 0 → ราคาเหมา = 0 และย้ายช่วงราคา rate_id[] ที่ฟอร์มส่งมาให้ประเภทนี้
     * is_use_rate == 0 → คืนสถานะช่วงราคาที่ยังไม่มีเจ้าของ (shipping_type_id = 0) แบบเดิม
     */
    public function save(Request $request): Response
    {
        // ตั้งใจต่างจากเดิม: POST ที่ไม่มีช่อง name เลย (ไม่ใช่ฟอร์มจริง) เดิม INSERT แถวว่าง / ล้างทุกคอลัมน์ของ id นั้น → ปฏิเสธ
        if (! Input::has($request, 'name')) {
            return response(Alerts::error('CloseLoading()', 'ไม่มีข้อมูลที่จะบันทึก'));
        }
        $id = Input::in($request, 'id');
        $isUseRate = Input::in($request, 'is_use_rate');
        $useRate = ! Php74::eq($isUseRate, 0);
        $values = [
            'name' => Input::in($request, 'name'),
            'price_extened' => $useRate ? 0 : Input::in($request, 'price_extened'),
            'price_thai_storage' => Input::in($request, 'price_thai_storage'),
            'price_thai_shipping' => Input::in($request, 'price_thai_shipping'),
            'is_use_rate' => $isUseRate,
            'is_quick_show' => Input::in($request, 'is_quick_show'),
        ];
        $uid = Legacy::userId();

        $id = DB::transaction(function () use ($request, $id, $values, $useRate, $uid) {
            if ($id === '') {
                $id = (string) DB::table('shipping_type')->insertGetId([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'name' => $values['name'], 'is_enable' => '1',
                    ...array_diff_key($values, ['name' => 1]),
                ]);
            } else {
                // เดิมไม่อัปเดต mod_date/mod_by — คงไว้
                DB::table('shipping_type')->where('id', $id)->update($values);
            }
            if ($useRate) {
                foreach ($this->rateIds($request) as $rateId) {
                    DB::table('shipping_type_rate')->where('id', $rateId)->update(['shipping_type_id' => $id]);
                }
            } else {
                DB::table('shipping_type_rate')->where('shipping_type_id', '0')->update(['is_delete' => 0]);
            }

            return $id;
        });

        return response(Alerts::success(
            'window.location.href = "'.Legacy::href('admin/shipping_type').'";',
            Lang::get('success_process_no').Lookup::getPrefix('shipping_type').Alerts::js($id),
        ));
    }

    /**
     * rate_id[] เดิม: for($i < count($rate_id)) — ไม่ใช่ array: string → ตัวอักษรแรก (count("x") = 1), ไม่ส่ง → ไม่มี
     *
     * @return list<string>
     */
    private function rateIds(Request $request): array
    {
        $v = LegacyInput::post($request, 'rate_id', null);
        if (is_array($v)) {
            return array_values(array_map(fn ($x) => is_scalar($x) ? (string) $x : '', $v));
        }
        if (is_string($v)) {
            return [substr($v, 0, 1)];
        }

        return [];
    }

    /** POST /_admin/_shipping_type/save_listno — save_listno.php (ลำดับ no; เดิมไม่ตอบอะไร) */
    public function saveListno(Request $request): Response
    {
        DB::table('shipping_type')->where('id', Input::in($request, 'id'))->update(['no' => Input::in($request, 'val')]);

        return response('');
    }

    /** POST /_admin/_shipping_type/delete — delete.php */
    public function delete(Request $request): Response
    {
        return $this->softDelete($request, 'shipping_type');
    }

    /** POST /_admin/_shipping_type/delete_cal — delete_cal.php (ช่วงราคา) */
    public function deleteCal(Request $request): Response
    {
        return $this->softDelete($request, 'shipping_type_rate');
    }

    private function softDelete(Request $request, string $table): Response
    {
        $ids = Input::idList($request);
        if ($ids !== []) {
            DB::table($table)->whereIn('id', $ids)->update([
                'is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId(),
            ]);
        }

        return response(Alerts::success());
    }

    /** POST /_admin/_shipping_type/load_cal_1 — load_cal_1.php (ช่วงน้ำหนัก กก. type 1) */
    public function loadCal1(Request $request): Response
    {
        return $this->loadCal($request, 1, -1, 1);
    }

    /** POST /_admin/_shipping_type/load_cal_2 — load_cal_2.php (ช่วงคิว type 2) */
    public function loadCal2(Request $request): Response
    {
        return $this->loadCal($request, 2, -0.0001, 0.0001);
    }

    /** ค่าเริ่มของช่วงใหม่ = max ล่าสุด + step (ไม่มีแถว → $empty + step) แบบ echo ของ PHP 7.4 */
    private function loadCal(Request $request, int $type, int|float $empty, int|float $step): Response
    {
        $id = Input::in($request, 'id');
        $base = DB::table('shipping_type_rate')->where('shipping_type_id', $id)->where('type', (string) $type)->where('is_delete', 0);
        $max = (clone $base)->orderByDesc('max')->first(['max']);
        $minValue = Php74::str(($max === null ? $empty : Php74::num($max->max)) + $step);

        return response(view('settings.admin.shipping_type.load_cal_'.$type, [
            'minValue' => $minValue,
            'rates' => $base->orderBy('min')->get(),
        ])->render());
    }

    /** POST /_admin/_shipping_type/save_new_price_by_kg — เพิ่มช่วงราคาตามน้ำหนัก (type 1) */
    public function saveNewPriceByKg(Request $request): Response
    {
        return $this->saveNewPrice($request, 1, 'LoadCal1();CloseLoading();');
    }

    /** POST /_admin/_shipping_type/save_new_price_by_cubic — เพิ่มช่วงราคาตามคิว (type 2) */
    public function saveNewPriceByCubic(Request $request): Response
    {
        return $this->saveNewPrice($request, 2, 'LoadCal2();CloseLoading();');
    }

    private function saveNewPrice(Request $request, int $type, string $fn): Response
    {
        $min = Input::in($request, 'min');
        $max = Input::in($request, 'max');
        $price = Input::in($request, 'price');
        // เดิม: $x == "" แบบ 7.4 (ค่าที่ไม่ได้ส่ง = null == "" ด้วย)
        foreach ([[$min, 'min_error'], [$max, 'max_error'], [$price, 'min_max_value_error']] as [$v, $key]) {
            if ($v === '') {
                return response(Alerts::error('', Lang::get($key)));
            }
        }
        if (Php74::cmp(Input::php($request, 'max'), Input::php($request, 'min')) <= 0) {
            return response(Alerts::error('', Lang::get('max_min_error')));
        }
        $uid = Legacy::userId();
        DB::table('shipping_type_rate')->insert([
            'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            'is_delete' => 0, 'shipping_type_id' => Input::in($request, 'id'), 'type' => $type,
            'min' => $min, 'max' => $max, 'value' => $price,
        ]);

        return response(Alerts::success($fn, Lang::get('success_process')));
    }
}
