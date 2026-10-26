<?php

namespace App\Domain\Reports;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Lookup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ข้อมูล/ตาราง "รายการพัสดุ" ของบิลส่งสินค้า (bill_payment) ที่ _export/form.php, _admin/_export/form.php, print.php/print_2.php ใช้ร่วมกัน
 *
 * เดิมต่อแถวมี query ซ้อน (N+1): header_count (subquery ต่อแถว), bill_product_on_tracking (ต่อแถว), tracking ลูก (ต่อแถว)
 * (PACK print_2 ไม่มีคอลัมน์ชั้นวาง และไม่มีตาราง warehouse_section*) → ที่นี่โหลดเป็นชุดด้วย whereIn ครั้งละตาราง (จำนวน query คงที่) ลำดับเหมือนเดิม
 */
final class ExportBill
{
    /**
     * SELECT bill_payment_detail.*, lot.name as lot_name, lot.type as lot_type, product_type.name as type_name, header_count
     * + bill_product_id (แถวแรกของ bill_product_on_tracking) + children (tracking ที่ header = id)
     *
     * @return Collection<int, object>
     */
    public static function details(mixed $paymentId): Collection
    {
        $rows = Rows::strAll(DB::table('bill_payment_detail')
            ->leftJoin('lot', 'lot.id', '=', 'bill_payment_detail.lot_id')
            ->leftJoin('product_type', 'bill_payment_detail.product_type_id', '=', 'product_type.id')
            ->where('bill_payment_detail.payment_id', $paymentId)
            // เดิมไม่มี ORDER BY (ใช้ index payment_id → ลำดับ PK = tracking_id)
            ->orderBy('bill_payment_detail.tracking_id')
            ->get(['bill_payment_detail.*', 'lot.name as lot_name', 'lot.type as lot_type', 'product_type.name as type_name']));
        if ($rows->isEmpty()) {
            return $rows;
        }
        $ids = $rows->pluck('id')->filter(fn ($v) => $v !== null)->unique()->values()->all();

        // tracking ลูก: SELECT tracking FROM tracking WHERE header = X (ลำดับตาม index header → id)
        $children = [];
        foreach (DB::table('tracking')->whereIn('header', $ids)->orderBy('header')->orderBy('id')->get(['header', 'tracking']) as $c) {
            $children[(string) $c->header][] = (string) $c->tracking;
        }
        // เลขออเดอร์: แถวแรก (id น้อยสุด) ของ bill_product_on_tracking ที่ tracking_id = X AND is_delete = 0
        $orders = [];
        foreach (DB::table('bill_product_on_tracking')
            ->leftJoin('bill_product_detail', 'bill_product_detail.id', '=', 'bill_product_on_tracking.bill_product_detail_id')
            ->whereIn('bill_product_on_tracking.tracking_id', $ids)->where('bill_product_on_tracking.is_delete', 0)
            ->orderBy('bill_product_on_tracking.id')
            ->get(['bill_product_on_tracking.tracking_id', 'bill_product_detail.bill_product_id']) as $o) {
            $k = (string) $o->tracking_id;
            if (! array_key_exists($k, $orders)) {
                $orders[$k] = $o->bill_product_id === null ? null : (string) $o->bill_product_id;
            }
        }

        foreach ($rows as $r) {
            $k = (string) $r->id;
            $r->children = $children[$k] ?? [];
            $r->header_count = (string) count($r->children);
            $r->bill_product_id = $orders[$k] ?? null;
        }

        return $rows;
    }

    /**
     * PACK: คูปองส่วนลดของบิล — SELECT * FROM coupon WHERE payment_id = X AND is_delete = '0' (แถวแรก)
     * null = ไม่มีบิล/ไม่มีคูปอง (เดิมบิลที่ไม่มีอยู่ query ด้วย payment_id = '' = คูปองที่ยังไม่ถูกใช้ — ไม่ทำแบบนั้น)
     */
    public static function coupon(mixed $paymentId): ?object
    {
        if ($paymentId === null || Php74::eq($paymentId, '') || Php74::eq($paymentId, 0)) {
            return null;
        }

        return Rows::str(DB::table('coupon')->where('payment_id', $paymentId)->where('is_delete', '0')->orderBy('id')->first());
    }

    /** n() = ค่าตัวเลขแบบ PHP 7.4 ของค่าจาก DB */
    private static function n(mixed $v): int|float
    {
        return Php74::num($v);
    }

    /**
     * ผลรวมแบบลูปแรกของ form.php เดิม (width/height/long ใช้จากลูปนี้ — เดิมเริ่มจาก undefined = null)
     *
     * @return array<string, int|float|null>
     */
    public static function totals(Collection $details): array
    {
        $t = ['amount' => 0, 'weight' => 0, 'cubic' => 0, 'width' => null, 'height' => null, 'long' => null,
            'service' => 0, 'price_kg' => 0, 'price_cubic' => 0, 'price_amount' => 0];
        foreach ($details as $i) {
            $t['amount'] = $t['amount'] + self::n($i->amount);
            $t['weight'] = $t['weight'] + (self::n($i->weight) * self::n($i->amount));
            $t['cubic'] = $t['cubic'] + (((self::n($i->width) * self::n($i->height) * self::n($i->long)) / 1000000) * self::n($i->amount));
            $t['width'] = self::n($t['width']) + self::n($i->width);
            $t['height'] = self::n($t['height']) + self::n($i->height);
            $t['long'] = self::n($t['long']) + self::n($i->long);
            $t['service'] = $t['service'] + self::n($i->price_qc) + self::n($i->price_express) + self::n($i->price_repack) + self::n($i->price_else) + self::n($i->price_craft);
            $t['price_kg'] = $t['price_kg'] + self::n($i->price_kg);
            $t['price_cubic'] = $t['price_cubic'] + self::n($i->price_cubic);
            $t['price_amount'] = $t['price_amount'] + self::n($i->price_amount);
        }

        return $t;
    }

    /**
     * <tbody> ของตาราง tracking ใน _export/form.php (ลูกค้า) / _admin/_export/form.php (แอดมิน)
     * ต่างกัน: ลิงก์ tracking (order/ vs admin/order/), คอลัมน์หมายเหตุ (quote vs remark), ช่องว่างก่อน class
     * PACK: ไม่มีคอลัมน์ "รหัสย่อย" (ไม่มี bill_payment_detail.subuser); แอดมินเห็นช่องราคากิโล/คิวเฉพาะเมื่อมีสิทธิ์
     * admin_viewprice_on_payment ($prices = false = ไม่แสดง 2 ช่องท้าย)
     */
    public static function trackingRows(Collection $details, bool $admin, bool $prices = true): string
    {
        if ($details->isEmpty()) {
            return '<tr><td style="height: 100px;" colspan="11" class="text-center">'.Lang::get('no_data').'</td></tr>';
        }
        $href = Legacy::href();
        $sp = $admin ? '  ' : ' ';
        $html = '';
        foreach ($details as $item) {
            $str = Php74::eq($item->lot_type, 0) ? Lang::get('car') : Lang::get('ship');
            $html .= Php74::eq($item->is_craft, 1) ? '<tr style="background-color:#ffdfa5">' : '<tr>';
            $rs = 'style="vertical-align:top" rowspan="'.(self::n($item->header_count) + 1).'"'.$sp;
            $bp = $item->bill_product_id;
            $trk = e((string) $item->tracking);
            // PACK/SR: ไม่มีร้านค้า (MALL) — แสดง lot/no เสมอ
            $html .= '<td '.$rs.'class="text-center">'.$str.' '.e((string) $item->lot_name).'/'.e((string) $item->no).'</td>';
            $html .= '<td style="white-space: nowrap;"><a target="_blank" href="'.$href.($admin ? 'admin/order/' : 'order/').e((string) $bp).'/'.$trk.'">'.$trk.'</a></td>';
            $html .= '<td '.$rs.'style="white-space: nowrap;">';
            if (Php74::neq($bp, '')) {
                $html .= '<a target="_blank" href="'.$href.'order/'.e((string) $bp).'/'.$trk.'">'.Rows::prefix('order').e((string) $bp).'</a>';
            } else {
                $html .= '-';
            }
            $html .= '</td>';
            $html .= '<td '.$rs.'style="white-space: nowrap;">'.e((string) ($admin ? $item->remark : $item->quote)).'</td>';
            $html .= '<td '.$rs.'class="text-right">'.Format::numberformatzero($item->amount).'</td>';
            $html .= '<td '.$rs.'class="text-right">'.Format::numberformatone(self::n($item->weight) * self::n($item->amount)).'</td>';
            $html .= '<td '.$rs.'class="text-right">'.Format::numberformatone($item->width).'</td>';
            $html .= '<td '.$rs.'class="text-right">'.Format::numberformatone($item->height).'</td>';
            $html .= '<td '.$rs.'class="text-right">'.Format::numberformatone($item->long).'</td>';
            $html .= '<td '.$rs.'class="text-right">'.Format::cubicformat(((self::n($item->width) * self::n($item->height) * self::n($item->long)) / 1000000) * self::n($item->amount)).'</td>';
            $html .= '<td '.$rs.'class="text-center">'.e((string) $item->type_name).'</td>';
            $html .= '<td '.$rs.'class="text-right showdetailbox">'.Format::numberformat(self::n($item->price_qc)
                + self::n($item->price_repack) + self::n($item->price_express)
                + self::n($item->price_else) + self::n($item->price_craft), false, false).'
						<div class="text-right lot_table_in" style="display:none;position:absolute;background-color:#fff">
								<table class="lot_table ">
								<thead>
									<th class="text-center" style="width:50px;">'.Lang::get('qc').'</th>
									<th class="text-center" style="width:50px;">'.Lang::get('repack').'</th>
									<th class="text-center" style="width:50px;">'.Lang::get('express').'</th>
									<th class="text-center" style="width:50px;">'.Lang::get('else').'</th>
									<th class="text-center" style="width:50px;">'.Lang::get('price_craft').'</th>
								</thead>
								<tr>
									<td class="text-right">'.Format::numberformat($item->price_qc, true, false).'</td>
									<td class="text-right">'.Format::numberformat($item->price_repack, true, false).'</td>
									<td class="text-right">'.Format::numberformat($item->price_express, true, false).'</td>
									<td class="text-right">'.Format::numberformat($item->price_else, true, false).'</td>
									<td class="text-right">'.Format::numberformat($item->price_craft, true, false).'</td>
								</tr>
								</table>
							</div>
							</td>';
            if ($prices) {
                $html .= '<td '.$rs.'class="text-right">'.Format::getBold(Format::numberformat($item->price_kg, true, false)).'</td>';
                $html .= '<td '.$rs.'class="text-right">'.Format::getBold(Format::numberformat($item->price_cubic, true, false)).'</td>';
            }
            $html .= '</tr>';
            foreach ($item->children as $child) {
                $html .= '<tr style="background-color:#ffdfa5">
										<td style="white-space: nowrap;" class="showdetailbox">'.e($child).'</td>
									</tr>';
            }
        }

        return $html;
    }

    /**
     * รายการ balance ที่อ้างถึงบิล (prefix payment/export) — ส่วน "ref_balance_list" ท้ายฟอร์ม
     * PACK: แอดมินที่ไม่มีสิทธิ์ admin_viewprice_on_payment เห็นเฉพาะ prefix export ($exportOnly)
     */
    public static function balanceBox(mixed $id, bool $admin, bool $exportOnly = false): string
    {
        // null = ไม่มีบิล (ไม่ต้อง query — ดู ExportController::form)
        $rows = $id === null ? collect() : Rows::strAll(DB::table('balance')
            ->leftJoin('prefix', 'balance.prefix', '=', 'prefix.id')
            ->where(fn ($w) => $exportOnly ? $w->where('balance.prefix', Lookup::getPrefixID('export'))
                : $w->where('balance.prefix', Lookup::getPrefixID('payment'))->orWhere('balance.prefix', Lookup::getPrefixID('export')))
            ->where('balance.prefix_ref_id', $id)->where('balance.is_delete', 0)
            ->get(['balance.*', ($admin ? 'prefix.link_admin' : 'prefix.link').' as link']));
        $href = Legacy::href();
        $html = '<div class="col-xs-12 col-md-6">
        <div class="card-box">
            <h4 class="m-t-0 header-title"><b>'.Lang::get('ref_balance_list').'</b></h4>
            <p class="text-muted font-13 m-b-30">
            </p>
            <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 130px;">'.Lang::get('working_date').'</th>
                            <th class="text-center">'.Lang::get('detail').'</th>
                            <th class="text-center" style="width: 80px;">'.Lang::get('amount').'</th>
                        </tr>
                    </thead>
                    <tbody>';
        if ($rows->isNotEmpty()) {
            foreach ($rows as $item) {
                $exp = explode(' ', (string) $item->create_date);
                $html .= '<tr><td>'.Format::getthaidate($exp[0]).'<br/>'.e($exp[1] ?? '').'</td>';
                $html .= '<td class="text-left"><a target="_blank" href="'.$href.e((string) $item->link).e((string) $item->prefix_ref_id).'">'.e((string) $item->detail).'</a></td>';
                $html .= '<td class="text-right">';
                if (self::n($item->amount) >= 0) {
                    $html .= '<span class="text-success">+'.Format::numberformat($item->amount).'</span>';
                } else {
                    $html .= '<span class="text-danger">'.Format::numberformat($item->amount).'</span>';
                }
                $html .= '</td>';
            }
        } else {
            $html .= '<tr><td colspan="3" class="text-center">'.Lang::get('no_data').'</td></tr>';
        }
        $html .= '
                </tbody>
            </table>
        </div>
    </div>';

        return $html;
    }
}
