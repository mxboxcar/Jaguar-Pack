<?php

namespace App\Http\Controllers\Order\Admin;

use App\Domain\Order\Html;
use App\Domain\Order\OrderDetailRenderer;
use App\Domain\Order\OrderLookups;
use App\Domain\Order\OrderMailer;
use App\Domain\Order\OrderTotals;
use App\Domain\Order\Php74;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Order\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Pricing;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * รายการสินค้า/tracking ภายในใบสั่งซื้อ (หน้า admin/order/{id}): _admin/_order/load_detail.php และ save_* ของรายการ
 * ทุก route อยู่ใต้ legacy.perm:admin_order (เดิม save_amount.php ไม่ตรวจอะไรเลย)
 * งานที่เขียนหลายแถว/หลายตารางห่อ transaction (เดิมไม่มี)
 */
class AdminOrderDetailController extends Controller
{
    use ReadsLegacyInput;

    /** POST /_admin/_order/load_detail */
    public function loadDetail(Request $request): Response
    {
        $id = (int) Php74::num($this->in($request, 'id'));

        return response((new OrderDetailRenderer)->admin($id));
    }

    /** POST /_admin/_order/load_calculate_cashback — ((ราคาหยวน × เรท) × จำนวน) */
    public function loadCalculateCashback(Request $request): Response
    {
        $v = (Php74::num($this->in($request, 'cn_price')) * Php74::num($this->in($request, 'rate'))) * Php74::num($this->in($request, 'amount'));

        return response(Format::numberformatforsubmit($v));
    }

    /** POST /_admin/_order/load_color_form, load_remark_product — json ของแถว bill_product_detail */
    public function loadDetailRow(Request $request): Response
    {
        return response(self::mysqliJson(DB::table('bill_product_detail')->where('id', $this->in($request, 'id'))->first()));
    }

    /** POST /_admin/_order/load_remark_shop — json ของแถว tracking */
    public function loadRemarkShop(Request $request): Response
    {
        return response(self::mysqliJson(DB::table('tracking')->where('id', $this->in($request, 'id'))->first()));
    }

    /**
     * json_encode(mysqli_fetch_array()) เดิม: มีทั้ง key ตัวเลขและชื่อคอลัมน์ (สลับกัน) และค่าทุกตัวเป็น string
     */
    private static function mysqliJson(?object $row): string
    {
        if ($row === null) {
            return 'null';
        }
        $out = [];
        $i = 0;
        foreach ((array) $row as $k => $v) {
            $v = $v === null ? null : (string) $v;
            $out[$i++] = $v;
            $out[$k] = $v;
        }

        return json_encode($out);
    }

    /** POST /_admin/_order/save_amount — แก้จำนวนแล้วคำนวณยอดใบใหม่ */
    public function saveAmount(Request $request): Response
    {
        $this->saveDetailAndTotal($request, 'amount');

        return response('');
    }

    /** POST /_admin/_order/save_price */
    public function savePrice(Request $request): Response
    {
        $this->saveDetailAndTotal($request, 'price');

        return response('');
    }

    private function saveDetailAndTotal(Request $request, string $col): void
    {
        $id = $this->in($request, 'id');
        $value = $this->in($request, 'value');
        DB::transaction(function () use ($id, $value, $col) {
            DB::table('bill_product_detail')->where('id', $id)->update([$col => $value]);
            $bill = DB::table('bill_product_detail')->where('id', $id)->value('bill_product_id');
            if ($bill !== null) {
                OrderTotals::saveTotal($bill);
            }
        });
    }

    /** POST /_admin/_order/save_shipping — ค่าส่งในจีนของ tracking แล้วคำนวณยอด + VAT ใหม่ */
    public function saveShipping(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $value = $this->in($request, 'value');
        DB::transaction(function () use ($id, $value) {
            DB::table('tracking')->where('id', $id)->update([
                'price_shipping' => $value, 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId(),
            ]);
            $bill = DB::table('bill_product_on_tracking')
                ->leftJoin('tracking', 'bill_product_on_tracking.tracking_id', '=', 'tracking.id')
                ->leftJoin('bill_product_detail', 'bill_product_detail.id', '=', 'bill_product_on_tracking.bill_product_detail_id')
                ->where('tracking.id', $id)->value('bill_product_detail.bill_product_id');
            if ($bill === null) {
                return;
            }
            OrderTotals::saveTotal($bill);
            $isVat = DB::table('bill_product')->where('id', $bill)->value('is_vat');
            OrderTotals::applyVat($bill, Php74::eq($isVat, '1'));
        });

        return response('');
    }

    /**
     * POST /_admin/_order/save_cashback — คืนเงินบางรายการ (ลบรายการคืนเงินเดิมของสินค้านี้แล้วสร้างใหม่) + อีเมล
     * เดิมไม่พบรายการก็ยัง INSERT balance ขยะ (user/ใบว่าง) → ไม่เขียนอะไร
     */
    public function saveCashback(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $cashback = $this->in($request, 'cashback');
        $remark = $this->in($request, 'cashback_remark');
        $item = $this->in($request, 'cashback_item');
        $color = $this->in($request, 'color');
        $uid = Legacy::userId();
        $data = DB::table('bill_product_detail')->where('id', $id)->first();
        $newId = '';
        if ($data !== null) {
            $newId = DB::transaction(function () use ($data, $id, $cashback, $remark, $item, $color, $uid) {
                $pid = OrderLookups::prefixId('order');
                DB::table('balance')->where('prefix', $pid)->where('prefix_ref_id', $data->bill_product_id)
                    ->where('ref_id_2', $data->id)->update(['is_delete' => '1']);
                DB::table('bill_product_detail')->where('id', $id)->update(['color_tr' => $color, 'cashback_remark' => $remark]);

                return DB::table('balance')->insertGetId([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'user' => $data->user, 'amount' => $cashback, 'detail' => 'ยกเลิกรายการ '.$item,
                    'type' => 2, 'prefix' => $pid, 'prefix_ref_id' => $data->bill_product_id, 'ref_id_2' => $data->id,
                ]);
            });
            if (! config('legacy.debug_mail')) {
                OrderMailer::orderCashback((int) $data->bill_product_id, (int) $data->id, $cashback, $item);
            }
        }

        return response(Alerts::success('LoadDetail();', Lang::get('success_process_no').OrderLookups::prefix('balance').$newId));
    }

    /** POST /_admin/_order/save_cashback_cancel — ยกเลิกการคืนเงินของรายการ (เดิมไม่พบรายการ = ลบ balance ที่ ref_id_2 = 0 ทั้งหมด → ข้าม) */
    public function saveCashbackCancel(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $data = DB::table('bill_product_detail')->where('id', $id)->first();
        if ($data !== null) {
            DB::transaction(function () use ($data, $id) {
                DB::table('bill_product_detail')->where('id', $id)->update(['amount' => $data->amount_before ?? '']);
                DB::table('balance')->where('prefix', OrderLookups::prefixId('order'))->where('ref_id_2', $data->id)
                    ->update(['is_delete' => '1']);
            });
        }

        return response(Alerts::success('LoadDetail();', Lang::get('success_process')));
    }

    /** POST /_admin/_order/save_color */
    public function saveColor(Request $request): Response
    {
        DB::table('bill_product_detail')->where('id', $this->in($request, 'id'))->update([
            'color_tr' => $this->in($request, 'value'), 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId(),
        ]);

        return response(Alerts::success('LoadDetail();'));
    }

    /** POST /_admin/_order/save_craft — สลับตีลังไม้ (รายการ + tracking ที่ผูกอยู่) */
    public function saveCraft(Request $request): Response
    {
        $this->toggle($request, 'is_craft');

        return response('');
    }

    /** POST /_admin/_order/save_qc */
    public function saveQc(Request $request): Response
    {
        $this->toggle($request, 'is_qc');

        return response('');
    }

    /** เดิม UPDATE tracking ทีละแถวในลูป → UPDATE เดียวด้วย sub-query (ผลเท่ากัน) */
    private function toggle(Request $request, string $col): void
    {
        $id = $this->in($request, 'id');
        DB::transaction(function () use ($id, $col) {
            $cur = DB::table('bill_product_detail')->where('id', $id)->value($col);
            $value = Php74::eq($cur, 0) ? 1 : 0;
            DB::table('bill_product_detail')->where('id', $id)->update([$col => $value]);
            $trackIds = DB::table('bill_product_on_tracking')->where('bill_product_detail_id', $id)->where('is_delete', 0)->pluck('tracking_id')->all();
            if ($trackIds !== []) {
                DB::table('tracking')->whereIn('id', $trackIds)->where('is_delete', 0)->update([$col => $value]);
            }
        });
    }

    /** POST /_admin/_order/save_remark_product */
    public function saveRemarkProduct(Request $request): Response
    {
        DB::table('bill_product_detail')->where('id', $this->in($request, 'id'))->update([
            'color_tr' => $this->in($request, 'color'), 'admin_remark' => $this->in($request, 'value'),
            'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId(),
        ]);

        return response(Alerts::success('LoadDetail();'));
    }

    /** POST /_admin/_order/save_remark_shop — หมายเหตุร้าน (เก็บที่ tracking แรกของร้าน) */
    public function saveRemarkShop(Request $request): Response
    {
        $this->updateTracking($request, 'remark_shop', $this->in($request, 'value'));

        return response(Alerts::success('LoadDetail();'));
    }

    /** POST /_admin/_order/save_product_type — แล้วคำนวณราคาขนส่งใหม่ (CalPriceAutoDiff) */
    public function saveProductType(Request $request): Response
    {
        $id = $this->in($request, 'id');
        DB::transaction(function () use ($request, $id) {
            $this->updateTracking($request, 'product_type_id', $this->in($request, 'value'));
            Pricing::calPriceAutoDiff($id);
        });

        return response('');
    }

    /** POST /_admin/_order/save_order_number — เลขออเดอร์ร้าน (ตัดช่องว่างทั้งหมด) */
    public function saveOrderNumber(Request $request): Response
    {
        $this->updateTracking($request, 'order', trim(str_replace(' ', '', $this->in($request, 'value'))));

        return response('');
    }

    /** POST /_admin/_order/save_tracking */
    public function saveTracking(Request $request): Response
    {
        $this->updateTracking($request, 'tracking', $this->in($request, 'value'));

        return response('');
    }

    /** POST /_admin/_order/save_quote — หมายเหตุของ tracking */
    public function saveQuote(Request $request): Response
    {
        $this->updateTracking($request, 'quote', $this->in($request, 'value'));

        return response('');
    }

    private function updateTracking(Request $request, string $col, string $value): void
    {
        DB::table('tracking')->where('id', $this->in($request, 'id'))->update([
            $col => $value, 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId(),
        ]);
    }

    /** POST /_admin/_order/save_valid_track — สลับ "ไม่ซื้อร้านนี้" (tracking = -2) */
    public function saveValidTrack(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $pay = DB::table('tracking')->where('id', $id)->value('payment_id');
        $uid = Legacy::userId();
        if (Php74::eq($pay, '-2')) {
            $set = ['tracking' => '', 'payment_id' => '0', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'date_warehouse' => '0000-00-00 00:00:00', 'date_outofchina' => '0000-00-00 00:00:00', 'date_thai' => '0000-00-00 00:00:00',
                'date_finish' => '0000-00-00 00:00:00'];
        } else {
            $set = ['tracking' => '-2', 'payment_id' => '-2', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'date_warehouse' => DB::raw('NOW()'), 'date_outofchina' => DB::raw('NOW()'), 'date_thai' => DB::raw('NOW()'),
                'date_finish' => DB::raw('NOW()')];
        }
        DB::table('tracking')->where('id', $id)->update($set);

        return response('');
    }

    /**
     * POST /_admin/_order/save_add_new_track — เพิ่ม tracking ว่างให้รายการ แล้วตอบแถว HTML ใหม่
     * (เดิมไม่พบรายการก็ INSERT tracking ขยะ → ไม่เขียนอะไร)
     */
    public function saveAddNewTrack(Request $request): Response
    {
        $ref = $this->in($request, 'ref');
        $order = $this->in($request, 'order');
        $uid = Legacy::userId();
        $data = DB::table('bill_product_detail')->leftJoin('bill_product', 'bill_product.id', '=', 'bill_product_detail.bill_product_id')
            ->where('bill_product_detail.id', $ref)
            ->first(['bill_product_detail.user', 'bill_product_detail.is_craft', 'bill_product.ship', 'bill_product.quote', 'bill_product.id as bill_product_id']);
        if ($data === null) {
            return response('');
        }
        $quote = str_replace("'", '', (string) ($data->quote ?? ''));
        [$trackId, $onId] = DB::transaction(function () use ($data, $ref, $uid, $quote) {
            $trackId = DB::table('tracking')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'tracking' => '', 'order' => '', 'price_kg' => '0.00', 'price_cubic' => '0.00', 'rate_kg' => '', 'rate_cubic' => '',
                'product_type_id' => '', 'lot_id' => '0', 'user' => $data->user ?? '', 'weight' => '0.0', 'width' => '0.0', 'height' => '0.0',
                'long' => '0.0', 'amount' => '1', 'ship' => $data->ship ?? '', 'payment_id' => '0', 'is_craft' => $data->is_craft ?? '', 'quote' => $quote,
            ]);
            // เดิม bill_product_on_tracking.user = ผู้ที่กด (แอดมิน) ไม่ใช่ลูกค้า — คงไว้
            $onId = DB::table('bill_product_on_tracking')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid, 'is_delete' => 0,
                'bill_product_id' => $data->bill_product_id ?? '', 'bill_product_detail_id' => $ref, 'tracking_id' => $trackId, 'user' => $uid,
            ]);

            return [$trackId, $onId];
        });

        $r = fn ($k) => Lang::get($k);
        $js = Html::h(str_replace(['\\', "'"], ['\\\\', "\\'"], $order));

        return response('<tr class="tracking_list">
		<td class="bxr_form_left" style="vertical-align: top;padding-top: 15px;">'.$r('tracking').' : </td>
		<td class="bxr_form_right" style="vertical-align: top;">
			<div style="float:left;clear:both;"><input type="text"onblur="save_tracking($(this),'.$trackId.', \''.$js.'\')" value="" /></div>
			<div style="float:left;clear:both;margin-top: -1px;"><input type="text" class="" onblur="save_quote($(this),'.$trackId.')" value="'.Html::h($quote).'" /></div>
		</td>
		<td style="width: 110px;padding-top: 6px;">
			<div class="bxr_form_left" style="">'.$r('lot_list').' : </div>
			<div class="bxr_form_left" style="">'.$r('date_warehouse').' : </div>
			<div class="bxr_form_left" style="">'.$r('date_outofchina').' : </div>
			<div class="bxr_form_left" style="">'.$r('date_thai').' : </div>
		</td>
		<td style="width: 150px;padding-top: 0px;">
			<div class="" style="float:left;clear:both;">-/-</div>
			<div class="" style="float:left;clear:both;">-</div>
			<div class="" style="float:left;clear:both;">-</div>
			<div class="" style="float:left;clear:both;">-</div>
		</td>

		<td style="vertical-align:top;padding-top: 6px;"><button class="btn-danger" onclick="deleteline($(this), '.$trackId.', '.$onId.')"><i class="md md-exposure-minus-1"></i></button></td>
	</tr>');
    }

    /** POST /_admin/_order/save_remove_track — เดิม echo คำสั่ง SQL กลับไปด้วย (JS ไม่ใช้) → ตอบว่าง */
    public function saveRemoveTrack(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $ref = $this->in($request, 'ref');
        $uid = Legacy::userId();
        DB::transaction(function () use ($id, $ref, $uid) {
            DB::table('tracking')->where('id', $id)->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid]);
            DB::table('bill_product_on_tracking')->where('tracking_id', $id)->where('id', $ref)
                ->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid]);
        });

        return response('');
    }
}
