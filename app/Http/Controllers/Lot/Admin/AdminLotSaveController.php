<?php

namespace App\Http\Controllers\Lot\Admin;

use App\Domain\Lot\LotLookups;
use App\Domain\Lot\Php74;
use App\Domain\Money\Money;
use App\Domain\Tracking\TempUpload;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Lot\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use App\Support\Legacy\Pricing;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * ajax ที่เขียนข้อมูลของแอดมินล็อต: _admin/_lot/save*.php, delete.php, cancel.php เดิม
 * ทุก query bind ค่า (เดิมฝัง $_POST ลง SQL ตรงๆ ทุกไฟล์) — ค่าที่ลง DB = LegacyInput::post() (เท่าของเดิม)
 * รายการ id ที่เดิมฝังลง IN (...) ตรงๆ: ถ้าไม่ใช่ตัวเลขล้วน เดิม = SQL error → ไม่มีอะไรถูกแก้ แต่ยังตอบเหมือนสำเร็จ (คงไว้)
 */
class AdminLotSaveController extends Controller
{
    use ReadsLegacyInput;

    /** POST /_admin/_lot/save — _admin/_lot/save.php (เพิ่ม/แก้ tracking ในล็อต) */
    public function save(Request $request): Response
    {
        $lotId = $this->in($request, 'id');
        $no = $this->in($request, 'no');
        $trackingId = $this->in($request, 'tracking_id');
        $tracking = str_replace(' ', '', $this->in($request, 'tracking'));
        $choosePrice = $this->php($request, 'choose_price');
        $code = $this->in($request, 'code');
        $productTypeId = $this->in($request, 'product_type_id');
        $remark = $this->in($request, 'remark');
        $order = str_replace(' ', '', $this->in($request, 'order'));
        $isCraft = $this->in($request, 'is_craft');
        $isQc = $this->in($request, 'is_qc');
        $amount = $this->in($request, 'amount');
        // รูปพัสดุ upload_upload_track_mul[] — เดิมทำเฉพาะเมื่อมีค่าส่งมา (ไม่ส่ง = ไม่แตะรูปเดิม); ชื่อไฟล์ต้องเป็นชื่อล้วน
        $postedImages = LegacyInput::post($request, 'upload_upload_track_mul', null);
        $images = $postedImages === null || $postedImages === '' ? null : TempUpload::names($postedImages);
        // เดิม numberformat*(ค่า, false) ของตัวแปร PHP → string ตัวเลขที่ลง DB
        $num = [
            'weight' => Format::numberformatone($this->php($request, 'weight'), false),
            'width' => Format::numberformatone($this->php($request, 'width'), false),
            'height' => Format::numberformatone($this->php($request, 'height'), false),
            'long' => Format::numberformatone($this->php($request, 'long'), false),
            'price_qc' => Format::numberformat($this->php($request, 'qc'), false),
            'price_repack' => Format::numberformat($this->php($request, 'repack'), false),
            'price_express' => Format::numberformat($this->php($request, 'express'), false),
            'price_else' => Format::numberformat($this->php($request, 'else'), false),
            'price_craft' => Format::numberformat($this->php($request, 'price_craft'), false),
        ];

        $craftIds = [];
        if (Php74::eq($isCraft, 1)) {
            // เดิม query ทีละรายการ (จำนวนเท่าที่แอดมินกรอก) แล้วหยุดที่รายการแรกที่ไม่พบ — คงไว้แบบเดิม
            foreach ($this->inArray($request, 'craft_tracklist') ?? [] as $item) {
                $item = is_scalar($item) ? (string) $item : '';
                if ($item === '') {
                    continue;
                }
                $cid = DB::table('tracking')->where('tracking', $item)->limit(1)->value('id');
                if ($cid === null) {
                    return response(Alerts::error('LoadDetail();', Lang::get('craft_tracklist_invalid')));
                }
                $craftIds[] = (int) $cid;
            }
        }

        $member = DB::table('user')->where('code', $code)->where('is_delete', 0)->first(['code', 'id']);
        $lot = DB::table('lot')->where('id', $lotId)->first(['id', 'type']);
        if ($member === null) {
            return response(Alerts::error('LoadDetail();', Lang::get('no_user')));
        }
        $uid = (int) Legacy::userId();

        $fields = [
            'is_qc' => $isQc, 'lot_id' => $lotId, 'user' => $member->id, 'order' => $order, 'tracking' => $tracking,
            'amount' => $amount, 'weight' => $num['weight'], 'width' => $num['width'], 'height' => $num['height'],
            'long' => $num['long'], 'no' => $no, 'is_craft' => $isCraft,
            'price_qc' => $num['price_qc'], 'price_repack' => $num['price_repack'], 'price_express' => $num['price_express'],
            'price_else' => $num['price_else'], 'remark' => $remark, 'price_craft' => $num['price_craft'],
            'product_type_id' => $productTypeId,
        ];

        $js = '';
        DB::transaction(function () use ($trackingId, $fields, $craftIds, $isCraft, $isQc, $lotId, $no, $choosePrice, $member, $lot, $uid, $images, &$js) {
            if ($trackingId !== '') {
                $dw = DB::table('tracking')->where('id', $trackingId)->value('date_warehouse');
                $set = $fields;
                if ((string) $dw === '0000-00-00 00:00:00') {
                    $set['date_warehouse'] = DB::raw('NOW()');
                }
                $set['mod_by'] = $uid;
                $set['mod_date'] = DB::raw('NOW()');
                DB::table('tracking')->where('id', $trackingId)->update($set);
                $id = $trackingId;
                if (Php74::eq($isCraft, 1) && $craftIds !== []) {
                    DB::table('tracking')->whereIn('id', $craftIds)->update(['is_qc' => $isQc, 'header' => $id, 'lot_id' => $lotId, 'no' => $no]);
                }
                if ($images !== null) {
                    $js = self::syncImages($id, $images, $uid);
                }
                Pricing::calPrice($id, $choosePrice);
            } else {
                $id = DB::table('tracking')->insertGetId([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'no' => $fields['no'], 'tracking' => $fields['tracking'], 'order' => $fields['order'],
                    'price_kg' => '0.00', 'price_cubic' => '0.00', 'price_shipping' => '0.00', 'price_amount' => '0.00',
                    'rate_kg' => '0', 'rate_cubic' => '0', 'product_type_id' => $fields['product_type_id'], 'lot_id' => $lotId,
                    'user' => $member->id, 'weight' => $fields['weight'], 'width' => $fields['width'], 'height' => $fields['height'],
                    'long' => $fields['long'], 'amount' => $fields['amount'], 'ship' => (string) ($lot->type ?? ''), 'payment_id' => '0',
                    'is_craft' => $fields['is_craft'], 'quote' => '', 'price_qc' => $fields['price_qc'], 'price_repack' => $fields['price_repack'],
                    'price_express' => $fields['price_express'], 'price_else' => $fields['price_else'], 'price_craft' => $fields['price_craft'],
                    'remark' => $fields['remark'], 'date_warehouse' => DB::raw('NOW()'), 'is_qc' => $fields['is_qc'],
                ]);
                // PACK: รูปผูกกับ tracking ที่เพิ่งสร้าง ($id — SR เดิมใช้ $tracking_id ที่ว่าง)
                if ($images !== null) {
                    $js = self::syncImages($id, $images, $uid);
                }
                Pricing::calPrice($id, $choosePrice);
                if (Php74::eq($isCraft, 1) && $craftIds !== []) {
                    DB::table('tracking')->whereIn('id', $craftIds)->update(['is_qc' => $isQc, 'header' => (string) $id, 'lot_id' => $lotId, 'no' => $no]);
                }
            }

            // goodsconfirm: ถ้าไม่ใช่ผู้ใช้ nobody (PACK define('nobody', 'PACKnocode') → config legacy.nobody)
            $nobody = DB::table('user')->where('code', config('legacy.nobody'))->value('id');
            if (Php74::neq($member->id, $nobody)) {
                DB::table('goodsconfirm')->where('tracking_id', $id)->update(['status' => 2]);
            }
        });

        // PACK: ตอบ JS ย้ายรูปขึ้น storage (ถ้ามี — echo ก่อน getSuccess เดิม) + SuccessBox แล้ว LoadDetail() (แบบ jl; SR = reload หน้า)
        return response($js.Alerts::success('CloseLoading();LoadDetail();', Lang::get('success_process_no').Alerts::js($tracking)));
    }

    /**
     * รูปพัสดุของ tracking — ลบ (is_delete = 1) ของเดิมทั้งหมดแล้วเพิ่มตามรายการที่ส่งมา (save.php / save_quickfile.php เดิม)
     * ไฟล์ที่อยู่ใน upload/temp ได้ JS เรียก _api/blockblob_ajax ย้ายขึ้น storage แบบเดิม (App\Domain\Tracking\TempUpload)
     *
     * @param  list<string>  $images
     */
    private static function syncImages(int|string $trackingId, array $images, int $uid): string
    {
        DB::table('tracking_image')->where('tracking_id', $trackingId)->update(['is_delete' => '1']);
        $js = '';
        foreach ($images as $img) {
            if (TempUpload::exists($img)) {
                $js .= TempUpload::blobJs($img);
            }
            DB::table('tracking_image')->insert([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'tracking_id' => $trackingId, 'attachment' => $img,
            ]);
        }

        return $js;
    }

    /** POST /_admin/_lot/save_quickfile — บันทึกรูปพัสดุจาก modal แนบด่วน → JSON [JS ย้ายรูป, HTML รูปเล็ก] (สิทธิ์ admin_lot) */
    public function saveQuickfile(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $upload = null;
        $html = null;
        // เดิมไม่ตรวจ id (ค่าอะไรก็ได้ = tracking_id 0) → เฉพาะ id ตัวเลขของ tracking ที่มีอยู่
        if (ctype_digit($id) && DB::table('tracking')->where('id', $id)->exists()) {
            $names = TempUpload::names(explode(',', $this->in($request, 'image')));
            $uid = (int) Legacy::userId();
            $js = DB::transaction(fn () => self::syncImages((int) $id, $names, $uid));
            // เดิม $uploadstr ไม่ถูกกำหนด (null) เมื่อไม่มีไฟล์ใน temp
            $upload = $js === '' ? null : $js;
            $list = LotLookups::trackingImages([(int) $id])[(int) $id] ?? [];
            $html = $list === [] ? null : view('lot.admin.img_track', ['list' => $list])->render();
        }

        return response(json_encode([$upload, $html]));
    }

    /** POST /_admin/_lot/delete — _admin/_lot/delete.php (สิทธิ์ admin_lot) */
    public function delete(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $arr = $this->in($request, 'arr_id');
        $ids = Php74::idList($arr !== '' ? substr($arr, 1) : $id);

        $count = DB::table('tracking')->where('lot_id', $id)->count();
        // เดิมนับเฉพาะ lot_id = '$id' แต่ลบทุก id ใน IN ($id) → id=5,6 ลบล็อต 6 ที่ยังมี tracking ได้ — ที่นี่ต้องว่างทุกล็อตที่จะลบ
        if ($count === 0 && $ids !== null) {
            $count = DB::table('tracking')->whereIn('lot_id', $ids)->count();
        }
        if ($count !== 0) {
            // เดิม getError() ไม่ส่งอาร์กิวเมนต์ = ArgumentCountError (HTTP 500 ไม่มีข้อความ) → ตอบ ErrorBox ว่างแทน
            return response(Alerts::error('', ''));
        }
        if ($ids !== null) {
            DB::table('lot')->whereIn('id', $ids)->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => (int) Legacy::userId()]);
        }

        return response(Alerts::success());
    }

    /** POST /_admin/_lot/cancel — _admin/_lot/cancel.php (ยกเลิก refill — สิทธิ์ admin_refill) */
    public function cancel(Request $request): Response
    {
        // แก้ (เจ้าของอนุมัติ): เดิมยกเลิกได้ทุกสถานะ (รวมที่อนุมัติ/เติมเงินแล้ว) → กติกาเดียวกับ _admin/_refill/cancel (Money::cancelPendingRefill)
        // คอลัมน์ที่เขียนเท่าไฟล์เดิมของ lot (ไม่มี cancel_remark / อีเมล)
        $result = Money::cancelPendingRefill($this->in($request, 'id'),
            ['mod_date' => DB::raw('NOW()'), 'mod_by' => (int) Legacy::userId()]);
        if (is_string($result)) {
            return response(Money::refuse($result));
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/refill').'";'));
    }

    /** POST /_admin/_lot/save_lot_memo — _admin/_lot/save_lot_memo.php */
    public function saveLotMemo(Request $request): Response
    {
        $no = $this->in($request, 'no');
        $noMax = $this->in($request, 'no_max');
        $user = DB::table('user')->where('code', $this->in($request, 'memo_code'))->value('id');
        if (Php74::eq($this->php($request, 'no_max'), '') || Php74::eq($this->php($request, 'no_max'), '0')) {
            $noMax = $no;
        }
        $uid = (int) Legacy::userId();
        DB::table('lot_memo')->insert([
            'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
            'no' => $no, 'detail' => $this->in($request, 'memo_detail'), 'weight' => $this->in($request, 'memo_weight'),
            'cubic' => $this->in($request, 'memo_cubic'), 'user' => $user === null ? '' : $user,
            'lot_id' => $this->in($request, 'lot_id'), 'no_max' => $noMax,
        ]);

        return response('');
    }

    /** POST /_admin/_lot/save_delete_lot_memo */
    public function saveDeleteLotMemo(Request $request): Response
    {
        DB::table('lot_memo')->where('id', $this->in($request, 'id'))
            ->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => (int) Legacy::userId()]);

        return response("SuccessBox('".Lang::get('save_complete')."', true);");
    }

    /** POST /_admin/_lot/save_file_lot — บันทึกไฟล์แนบของล็อตหลังอัปโหลดผ่าน system_upload_2 */
    public function saveFileLot(Request $request): Response
    {
        $id = str_replace('lot_', '', $this->in($request, 'id'));
        $value = $this->in($request, 'value');
        $out = '';
        // เดิม copy('../../upload/temp/'.$value, ...) โดยไม่ตรวจชื่อไฟล์ = path traversal → รับเฉพาะชื่อไฟล์ล้วน
        if ($value !== '' && basename($value) === $value && $value !== '.' && $value !== '..') {
            $temp = rtrim((string) config('legacy.upload.temp_dir'), '/').'/'.$value;
            if (is_file($temp)) {
                $dir = rtrim((string) config('legacy.upload.local_dir'), '/');
                File::ensureDirectoryExists($dir);
                @copy($temp, $dir.'/'.$value);
                // production (azure): ให้หน้าเว็บเรียก _api/blockblob_ajax อัปขึ้น blob แบบเดิม — dev (local) ไม่เรียก
                if (config('legacy.upload.driver') !== 'local' && ! config('legacy.debug_mail')) {
                    $out = "$.ajax({ \n              type: 'POST', \n              async: true, \n              data : 'path=../upload/temp/".Alerts::js($value)."', \n              url: rooturl + '_api/blockblob_ajax', \n              success: function(data) { \n                \n              } });";
                }
            }
        }
        DB::table('lot')->where('id', $id)->update(['attachment' => $value, 'attachment_name' => $this->in($request, 'filename')]);

        return response($out);
    }

    /** POST /_admin/_lot/save_lot_name */
    public function saveLotName(Request $request): Response
    {
        return $this->updateLot($request, ['name' => $this->in($request, 'name')]);
    }

    /** POST /_admin/_lot/save_lot_name_admin */
    public function saveLotNameAdmin(Request $request): Response
    {
        return $this->updateLot($request, ['name_admin' => $this->in($request, 'name_admin')]);
    }

    /** POST /_admin/_lot/save_lot_ship_amount */
    public function saveLotShipAmount(Request $request): Response
    {
        return $this->updateLot($request, ['shipping_amount' => $this->in($request, 'amount')]);
    }

    /** POST /_admin/_lot/save_status_lot */
    public function saveStatusLot(Request $request): Response
    {
        return $this->updateLot($request, ['status' => $this->in($request, 'status')]);
    }

    /** POST /_admin/_lot/save_status_lot_date — method 1/2/3 = date_in/date_out/date_arrive */
    public function saveStatusLotDate(Request $request): Response
    {
        $method = $this->php($request, 'method');
        $date = Format::getdatesubmit(explode('|', $this->in($request, 'date'))[0]);
        // switch เดิมเทียบแบบหลวม (==)
        $col = match (true) {
            Php74::eq($method, '1') => 'date_in',
            Php74::eq($method, '2') => 'date_out',
            Php74::eq($method, '3') => 'date_arrive',
            default => null,
        };
        if ($col !== null) {
            return $this->updateLot($request, [$col => $date]);
        }

        return response('');
    }

    /** POST /_admin/_lot/save_new_lot — สร้างล็อตใหม่ ชื่อ = ชื่อล็อตล่าสุด (เรียงแบบ string) + 1 */
    public function saveNewLot(Request $request): Response
    {
        $ship = $this->in($request, 'ship');
        $uid = (int) Legacy::userId();
        // เดิมไม่กรอง is_delete และเรียงชื่อแบบ string ("999" > "1000") — คงไว้
        $last = DB::table('lot')->where('type', $ship)->orderByDesc('name')->limit(1)->value('name');
        $id = DB::table('lot')->insertGetId([
            'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            'is_delete' => 0, 'name' => Php74::str(Php74::num($last) + 1), 'type' => $ship, 'status' => '0',
            'date_in' => null, 'date_out' => null, 'date_arrive' => null, 'date_est' => null,
        ]);

        return response('window.location.href = "'.Legacy::href('admin/lot/'.$id).'";');
    }

    /** POST /_admin/_lot/save_print — เปิดหน้าพิมพ์หลายล็อต */
    public function savePrint(Request $request): Response
    {
        $val = '';
        foreach ($this->inArray($request, 'selected_print') ?? [] as $item) {
            $val .= ','.(is_scalar($item) ? (string) $item : 'Array');
        }

        return response("CloseLoading();$('.modal').modal('hide');window.open('".Legacy::href('_admin/_lot/print?lot=').Alerts::js(substr($val, 1))."')");
    }

    /** POST /_admin/_lot/save_remove_tracking — เอา tracking ออกจากล็อต */
    public function saveRemoveTracking(Request $request): Response
    {
        $arr = $this->in($request, 'arr_id');
        $ids = Php74::idList($arr !== '' ? substr($arr, 1) : $this->in($request, 'id'));
        if ($ids !== null) {
            DB::table('tracking')->whereIn('id', $ids)->update(['lot_id' => '0', 'mod_date' => DB::raw('NOW()'), 'mod_by' => (int) Legacy::userId()]);
        }

        return response(Alerts::success('LoadDetail();'));
    }

    /** POST /_admin/_lot/save_status_tracking — ตั้งวันที่สถานะของ tracking ที่เลือก */
    public function saveStatusTracking(Request $request): Response
    {
        return $this->trackingDates($request, Format::getdatesubmit($this->in($request, 'date')));
    }

    /** POST /_admin/_lot/save_status_reset_tracking — ล้างวันที่สถานะ */
    public function saveStatusResetTracking(Request $request): Response
    {
        return $this->trackingDates($request, '0000-00-00 00:00:00');
    }

    /** POST /_admin/_lot/save_verified — คำนวณราคาใหม่ทั้งล็อต (เฉพาะ user id 1, 2 ที่มีสิทธิ์ admin_lot) */
    public function saveVerified(Request $request): Response
    {
        $current = Legacy::userId();
        if ($current !== 1 && $current !== 2) {
            return response(Alerts::permission('admin_lot'));
        }
        $id = $this->in($request, 'id');
        DB::transaction(function () use ($id) {
            $ids = DB::table('tracking')->where('lot_id', $id)->orderByDesc('no')->pluck('id');
            // CalPriceAutoDiff เขียนทีละ tracking (ตรรกะคำนวณต่อแถวของฐานร่วม)
            foreach ($ids as $tid) {
                Pricing::calPriceAutoDiff($tid);
            }
            DB::table('lot')->where('id', $id)->update(['is_verified' => (string) count($ids)]);
        });

        return response(Alerts::success());
    }

    private function updateLot(Request $request, array $set): Response
    {
        DB::table('lot')->where('id', $this->in($request, 'id'))->update($set);

        return response('');
    }

    private function trackingDates(Request $request, string $value): Response
    {
        $status = $this->php($request, 'status');
        // เดิม if ($status == 0) / == 1 / == 2 (PHP 7.4: '' == 0 เป็นจริง)
        $col = match (true) {
            Php74::eq($status, 0) => 'date_warehouse',
            Php74::eq($status, 1) => 'date_outofchina',
            Php74::eq($status, 2) => 'date_thai',
            default => null,
        };
        $ids = Php74::idList($this->in($request, 'id'));
        if ($col !== null && $ids !== null) {
            DB::table('tracking')->whereIn('id', $ids)->update([$col => $value]);
        }

        return response(Alerts::success('LoadDetail();'));
    }
}
