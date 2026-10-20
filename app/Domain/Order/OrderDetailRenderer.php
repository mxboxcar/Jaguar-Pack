<?php

namespace App\Domain\Order;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Status;
use Illuminate\Support\Facades\DB;

/**
 * ตารางรายการสินค้าในใบสั่งซื้อ (จัดกลุ่มตามร้าน) — คัดตรรกะทีละบรรทัดจาก 3 ไฟล์เดิมที่เขียนแยกกัน:
 *   customer() = ส่วนล่างของ _order/form.php
 *   admin()    = _admin/_order/load_detail.php (SR) และ _admin/_printorder/load_detail.php ($view = true)
 *   print()    = ส่วนล่างของ _admin/_printorder/print.php (SR — jl ใช้กับ _order/print.php ซึ่ง SR ไม่มี)
 *
 * HTML ต้องเหมือนเดิม (รวม tag ที่ไม่ปิด และ placeholder "[id_changeproducttypeoption_x]" ที่เหลือค้างใน <option>)
 * ต่างจากเดิมโดยตั้งใจ:
 *   - ค่าจาก DB ผ่าน Html::h() (เดิม echo ดิบ = stored XSS)
 *   - N+1 เดิม (tracking ต่อร้าน, balance ต่อสินค้า, product_type ต่อร้าน) → โหลดครั้งเดียวแล้วจัดกลุ่มในหน่วยความจำ
 *     ลำดับและค่าเหมือนเดิม (ORDER BY tracking.id ASC ต่อรายการ, balance แถวแรกตาม id)
 *   - เลขคำนวณใช้ Php74::num() (เดิม amount เป็น varchar — "" / "abc" ใน 8.4 = TypeError)
 */
final class OrderDetailRenderer
{
    /** @var array<string, string> */
    private array $r;

    private string $href;

    private string $blob;

    /** @var array{0: mixed, 1: mixed, 2: mixed} ค่า $t_amount/$t_per_item/$t_total สุดท้าย (form.php เดิม echo ใน HTML comment ท้ายตาราง) */
    public array $tail = [null, null, null];

    public function __construct()
    {
        $this->r = Lang::all();
        $this->href = Legacy::href();
        $this->blob = Legacy::hrefBlob();
    }

    private function res(string $k): string
    {
        return (string) ($this->r[$k] ?? '');
    }

    /** ตัวเลขที่ echo ตรงๆ แบบ PHP 7.4 */
    private static function n(int|float $v): string
    {
        return Php74::str($v);
    }

    private static function num(mixed $v): int|float
    {
        return Php74::num($v);
    }

    // ------------------------------------------------------------------ data

    /**
     * @return list<array<string, mixed>>
     */
    private function cart(int $id, bool $filterDeleted): array
    {
        $q = DB::table('bill_product_detail')->where('bill_product_detail.bill_product_id', $id);
        if ($filterDeleted) {
            $q->where('bill_product_detail.is_delete', 0)
                ->selectRaw('bill_product_detail.*, (SELECT COUNT(id) FROM `bill_product_detail` as t WHERE t.shop_name = bill_product_detail.shop_name AND t.is_delete = 0 AND bill_product_detail.bill_product_id = t.bill_product_id) as count');
        } else {
            $q->selectRaw('bill_product_detail.*, (SELECT COUNT(id) FROM `bill_product_detail` as t WHERE t.shop_name = bill_product_detail.shop_name AND bill_product_detail.bill_product_id = t.bill_product_id) as count');
        }

        return $q->orderBy('bill_product_detail.shop_name')->orderBy('bill_product_detail.id')->get()
            ->map(fn ($r) => Php74::row($r))->all();
    }

    /**
     * tracking ของทุกรายการในใบ (เดิม query ต่อร้านด้วย bill_product_detail_id ของแถวแรก)
     *
     * @param  list<array<string, mixed>>  $cart
     * @return array<int, list<array<string, mixed>>>
     */
    private function trackings(array $cart, bool $withLotType): array
    {
        $ids = array_values(array_unique(array_map(fn ($i) => (int) $i['id'], $cart)));
        if ($ids === []) {
            return [];
        }
        $cols = 'lot.name as lot_name, lot.id as lot_id, '.($withLotType ? 'lot.type as lot_type, ' : '')
            .'tracking.no, tracking.id, tracking.remark_shop, tracking.date_warehouse, tracking.payment_id,
             tracking.date_outofchina, tracking.date_thai, tracking.date_finish,
             tracking.product_type_id, bill_product_on_tracking.id as on_track_id , tracking.quote,
             tracking.order, tracking.price_shipping, tracking.tracking, bill_product_on_tracking.bill_product_id, tracking.ship,
             bill_product_on_tracking.bill_product_detail_id, bill_product_on_tracking.tracking_id';
        $rows = DB::table('bill_product_on_tracking')
            ->leftJoin('tracking', 'bill_product_on_tracking.tracking_id', '=', 'tracking.id')
            ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->whereIn('bill_product_detail_id', $ids)->where('bill_product_on_tracking.is_delete', 0)
            ->selectRaw($cols)
            // เดิม ORDER BY id ASC = tracking.id (คอลัมน์ id ใน select) — เพิ่ม bop.id ให้ลำดับของ NULL คงที่
            ->orderBy('tracking.id')->orderBy('bill_product_on_tracking.id')
            ->get();
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row->bill_product_detail_id][] = Php74::row($row);
        }

        return $out;
    }

    /**
     * balance คืนเงินของแต่ละรายการ (เดิม SELECT * ... WHERE ref_id_2 = item แล้วอ่านแถวแรก)
     *
     * @param  list<array<string, mixed>>  $cart
     * @return array<int, array<string, mixed>>
     */
    private function cashbacks(array $cart): array
    {
        $ids = array_values(array_unique(array_map(fn ($i) => (int) $i['id'], $cart)));
        if ($ids === []) {
            return [];
        }
        $out = [];
        $rows = DB::table('balance')->where('prefix', OrderLookups::prefixId('order'))->whereIn('ref_id_2', $ids)
            ->where('is_delete', '0')->orderBy('id')->get(['id', 'ref_id_2', 'amount']);
        foreach ($rows as $row) {
            $out[(int) $row->ref_id_2] ??= Php74::row($row);
        }

        return $out;
    }

    /**
     * SR: รูปของ tracking (tracking_image ที่ไม่ถูกลบ) ของ tracking แรกของแต่ละร้าน — เดิม query ต่อร้าน → ครั้งเดียว
     *
     * @param  list<array<string, mixed>>  $cart
     * @param  array<int, list<array<string, mixed>>>  $trackMap
     * @return array<int, list<string>> tracking_id => [attachment, ...] (ลำดับตาม id แบบ SELECT * เดิม)
     */
    private function trackingImages(array $cart, array $trackMap): array
    {
        $ids = [];
        foreach ($trackMap as $rows) {
            if (isset($rows[0]['id']) && $rows[0]['id'] !== null) {
                $ids[] = (int) $rows[0]['id'];
            }
        }
        if ($ids === []) {
            return [];
        }
        $out = [];
        foreach (DB::table('tracking_image')->whereIn('tracking_id', array_values(array_unique($ids)))->where('is_delete', '0')
            ->orderBy('id')->get(['tracking_id', 'attachment']) as $r) {
            $out[(int) $r->tracking_id][] = (string) $r->attachment;
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private function productTypes(mixed $ship): array
    {
        return DB::table('product_type')->where('is_delete', '0')->where('type', $ship ?? '')->orderBy('id')
            ->get(['id', 'name'])->map(fn ($r) => Php74::row($r))->all();
    }

    /** ยอดค่าส่งในจีนรวมของทั้งใบ — เดิมคำนวณไว้ใน $t_total ก่อนลูป (มีผลเฉพาะเมื่อร้านแรกชื่อว่าง) */
    private function shippingPreSum(int $id): int|float|null
    {
        $ids = DB::table('bill_product_detail')->where('bill_product_id', $id)->pluck('id')->all();
        if ($ids === []) {
            return null; // เดิม IN () = SQL error → ไม่บวกอะไร
        }
        $t = null;
        $rows = DB::table('bill_product_on_tracking')
            ->leftJoin('tracking', 'bill_product_on_tracking.tracking_id', '=', 'tracking.id')
            ->leftJoin('bill_product_detail', 'bill_product_on_tracking.bill_product_detail_id', '=', 'bill_product_detail.id')
            ->whereIn('bill_product_detail_id', $ids)->get(['tracking.price_shipping', 'bill_product_detail.exchange_rate']);
        foreach ($rows as $row) {
            $t = self::num($t) + (self::num($row->price_shipping) * self::num($row->exchange_rate));
        }

        return $t;
    }

    /** ส่วนบรรทัดที่สอง (ขนาด/สี/หมายเหตุ/ไฟล์แนบ) ของ form.php ลูกค้า และ load_detail.php */
    private function secondLine(array $item, bool $admin): string
    {
        $haveSize = false;
        $haveRemark = false;
        $haveColor = false;
        $s = '';
        $size = (string) ($item['size'] ?? '');
        $color = (string) ($item['color'] ?? '');
        if (str_contains($size, '//')) {
            $s .= $admin
                ? '<a class="fancybox" href="'.Html::url($size).'">
	                        <img src="'.Html::h($size).'" style="border:1px solid #a0a0a0;max-width: 26px;max-height: 26px;margin-right: 10px;">
	                    </a>'
                : '<a class="fancybox" href="'.Html::url($size).'">
                            <img src="'.Html::h($size).'" style="border:1px solid #a0a0a0;max-width: 26px;max-height: 26px;margin-right: 10px;">
                        </a>';
        } elseif ($size !== '') {
            $haveSize = true;
            $s .= $this->res('size').':'.Html::h($size);
        }
        if (str_contains($color, '//')) {
            $s .= $admin
                ? '<a class="fancybox" href="'.Html::url($color).'">
	                        <img src="'.Html::h($color).'" style="border:1px solid #a0a0a0;max-width: 26px;max-height: 26px;margin-right: 10px;">
	                    </a>'
                : '<a class="fancybox" href="'.Html::url($color).'">
                            <img src="'.Html::h($color).'" style="border:1px solid #a0a0a0;max-width: 26px;max-height: 26px;margin-right: 10px;">
                        </a>';
        } elseif (str_contains($color, '#') && strlen($color) === 7) {
            // SR (form ลูกค้า + load_detail): สีแบบ #rrggbb แสดงเป็นแถบสี (escape ค่าใน attribute)
            $s .= '<br/>'.$this->res('color').':'.' <span style="background-color:'.Html::h($color).'">----</span>';
        } else {
            if ($haveSize) {
                $s .= '<br/>'.$this->res('color').':';
            }
            $haveColor = true;
            $s .= Html::nl2br($color);
        }
        if ((string) ($item['remark'] ?? '') !== '') {
            $haveRemark = true;
            if ($haveSize || $haveColor) {
                $s .= '<br/>'.$this->res('remark').':';
            }
            $s .= Html::nl2br($item['remark']);
        }
        if ((string) ($item['upload_file'] ?? '') !== '') {
            if ($haveSize || $haveRemark || $haveColor) {
                $s .= '<br/>'.$this->res('attachment').':';
            }
            $u = Html::h($this->blob.$item['upload_file']);
            $s .= $admin
                ? '<a class="fancybox" href="'.$u.'">
	                        <img src="'.$u.'" style="border:1px solid #a0a0a0;width: 15px;height: 15px;margin-right: 10px;">
	                    </a>'
                : '<a class="fancybox" href="'.$u.'">
	                        <img src="'.$u.'" style="margin-top: -5px;border:1px solid #a0a0a0;width: 15px;height: 15px;margin-right: 10px;">
	                    </a>';
        }

        return $s;
    }

    /** @param bool $takeapic SR load_detail ของแอดมิน: บริการถ่ายรูป = สีม่วง (ทับสี QC/ตีลัง) */
    private static function craftQcColors(array $item, bool $takeapic = false): string
    {
        $craft = Php74::eq($item['is_craft'] ?? null, 1);
        $qc = Php74::eq($item['is_qc'] ?? null, 1);
        if ($craft && $qc) {
            return '#f9ffa5';
        }
        $colors = '';
        $found = false;
        if ($craft) {
            $colors .= 'background-color:#ffdfa5';
            $found = true;
        }
        if ($qc) {
            $colors = 'background-color:#a5ffc5';
            $found = true;
        }
        if ($takeapic && Php74::eq($item['is_takeapic'] ?? null, 1)) {
            $colors = 'background-color:#768cf2';
            $found = true;
        }

        return $found ? $colors : 'background-color:';
    }

    /** ชื่อสินค้า: เดิม count($s) != 0 (string ใดๆ = 1, null = 0) → ตัด 65 ตัวแล้วเติม ... */
    private static function productName(array $item): string
    {
        $n = $item['product_name'] ?? null;

        return $n === null ? '' : mb_substr((string) $n, 0, 65).'...';
    }

    private static function trColor(array $item): string
    {
        $c = (string) ($item['color_tr'] ?? '');

        return $c !== '' ? 'background-color:'.Html::h($c) : '';
    }

    // ------------------------------------------------------------------ _order/form.php (ลูกค้า)

    /** @param array<string, mixed> $data แถว bill_product ของหน้า (SR: $data_head = id, ship, status ของใบเดียวกัน) */
    public function customer(int $id, array $data): string
    {
        $cart = $this->cart($id, false);
        $trackMap = $this->trackings($cart, false);
        $cashMap = $this->cashbacks($cart);
        $imageMap = $this->trackingImages($cart, $trackMap);
        // SR มี $data_head (SELECT id,ship,status FROM bill_product WHERE id = $id) — jl ไม่มี (= null เสมอ)
        $headShip = $data['ship'] ?? null;
        $paid = self::num($data['status'] ?? null) >= 2;
        $types = $cart === [] ? [] : $this->productTypes($headShip);

        $html = '';
        $mainNo = 1;
        $no = 0;
        $totalChina = 0;
        $totalThai = 0;
        $tAmount = $tPerItem = $tTotal = null;
        $dataTracking = [];
        if (count($cart) === 0) {
            $html .= '<tr><td class="text-center" colspan="9" style="line-height: 100px;">'.$this->res('no_data').'</td></tr>';
        } else {
            $shopName = '';
            $countRow = 1;
            foreach ($cart as $item) {
                $newShop = Php74::neq($shopName, $item['shop_name']);
                if ($newShop) {
                    $tAmount = 0;
                    $tPerItem = 0;
                    $tTotal = 0;
                }
                $amount = self::num($item['amount']);
                $price = self::num($item['price']);
                $rate = self::num($item['exchange_rate']);
                $tAmount = self::num($tAmount) + $amount;
                $tPerItem = self::num($tPerItem) + ($price * $amount);
                $tTotal = self::num($tTotal) + ($price * $amount * $rate);
                $totalChina += ($price * $amount);
                $totalThai += ($price * $amount * $rate);
                $pname = self::productName($item);

                if ($newShop) {
                    foreach ($trackMap[(int) $item['id']] ?? [] as $row) {
                        $dataTracking[] = $row;
                    }
                    $t0 = $dataTracking[0] ?? [];
                    $html .= '<table id="'.Html::h($t0['tracking'] ?? '').'" class="table  table-bordered">
				    <thead>
				        <tr>

				            <th class="text-left" colspan="3">
				            	<div class="col-xs-5" style="padding:0px">
				            	<div style="float:left;margin-top: 0px;width:75px;overflow: hidden;height: 16px;">'.$mainNo.'.&nbsp;'.Html::h($item['shop_name']).'</div>
				            	('.($t0['id'] ?? '').')

				            	</div>
				            	<div style="float:left;padding:0px;margin-top: 3px;">'.$this->res('wood_protect').' : ';
                    $html .= Php74::eq($item['is_craft'], 1)
                        ? '<input type="checkbox" checked class="js-switch"  value="0" data-color="#f05050" data-size="small"/>'
                        : '<input type="checkbox"  class="js-switch" value="1"  data-color="#f05050" data-size="small"/>';
                    $html .= '</div>
				            	<div style="float:left;padding:0px;margin-top: 3px;">'.$this->res('qc').' : ';
                    $html .= Php74::eq($item['is_qc'], 1)
                        ? '<input type="checkbox" checked class="js-switch"  value="0" data-color="#f05050" data-size="small"/>'
                        : '<input type="checkbox"  class="js-switch" value="1"  data-color="#f05050" data-size="small"/>';
                    // SR: บริการถ่ายรูป (bill_product_detail.is_takeapic) แสดงอย่างเดียว
                    $html .= '</div>
				            	<div style="float:left;padding:0px;margin-top: 3px;">ถ่ายรูป : ';
                    $html .= Php74::eq($item['is_takeapic'] ?? null, 1)
                        ? '<input type="checkbox" checked class="js-switch"  value="0" data-color="#f05050" data-size="small"/>'
                        : '<input type="checkbox"  class="js-switch" value="1"  data-color="#f05050" data-size="small"/>';
                    $html .= '</div>';
                    $html .= '<div class="col-xs-2" style="padding:0px;margin-top: 3px;">'.$this->res('type').' : <select disabled="disabled" onchange="[changeproducttype]" >';
                    $html .= '<option value="0"> - </option>';
                    foreach ($types as $t) {
                        $html .= '<option ['.($t0['id'] ?? '').'_changeproducttypeoption_'.$t['id'].'] value="'.$t['id'].'">'.Html::h($t['name']).'</option>';
                    }
                    $html .= '</select><div>';
                    $html .= '
				            </th>
				            <th class="text-center" style="width: 80px;">'.$this->res('amount').'</th>
				            <th class="text-center" style="width: 80px;">'.$this->res('price_cn').'</th>
				            <th class="text-center" style="width: 80px;">'.$this->res('total_cn').'</th>
				            <th class="text-center" style="width: 80px;">'.$this->res('price_th').'</th>
				        </tr>
				    </thead>

				    <tbody>';
                }

                $no++;
                $second = $this->secondLine($item, false);
                $colors = self::craftQcColors($item);
                $color = self::trColor($item);
                $html .= '<tr>';
                $html .= '<td style="width:50px;'.$color.'">'.$mainNo.'.'.$no.'.</td>';
                $html .= '<td style="width:70px;'.$colors.'" class="text-center"><a class="fancybox" href="'.Html::url($item['main_image']).'">
                <img src="'.Html::h($item['main_image']).'" style="max-width: 46px;max-height: 46px;">
            </a></td>';
                $html .= '<td style="'.$color.'" ><a href="'.Html::url($item['url']).'">'.Html::h($pname).'</a>';
                if ($second !== '') {
                    $html .= '<div class="cd-price">'.$second.'</div>';
                }
                if ((string) ($item['admin_remark'] ?? '') !== '') {
                    $html .= '<div class="admin_remark" style="color:#fb6d9d">'.Html::h($item['admin_remark']).'</div>';
                }
                if (Php74::neq($item['amount_before'], $item['amount'])) {
                    $html .= '<div class="text-danger ">'.$this->res('amount_before').' : '.Html::h($item['amount_before']).'</div>';
                }
                if ((string) ($item['cashback_remark'] ?? '') !== '') {
                    $html .= '<div class="text-danger ">'.Html::h($item['cashback_remark']).'</div>';
                }
                if (isset($cashMap[(int) $item['id']])) {
                    $html .= '<div class="text-danger ">'.$this->res('cashback').' : '.Format::numberformat($cashMap[(int) $item['id']]['amount']).'</div>';
                }
                $html .= '</td>';
                // SR: $data_head['status'] >= 2 → ราคาแสดงสัญลักษณ์หยวน (จำนวนแสดง amount ทั้งสองกรณี)
                $html .= '<td style="width: 80px;'.$color.'" class="text-right">';
                $html .= Html::h($item['amount']);
                $html .= '</td>';
                $html .= '<td style="width: 80px;'.$color.'" class="text-right">';
                $html .= ($paid ? $this->res('cn_currency') : '').Html::h($item['price']);
                $html .= '</td>';
                $html .= '<td style="width: 80px;'.$color.'" class="text-right">'.Format::numberformat($amount * $price, true, true, true).'</td>';
                $html .= '<td style="width: 80px;'.$color.'" class="text-right">'.Format::numberformat($amount * ($price * $rate)).'</td>';
                $html .= '</tr>';

                if (Php74::eq($countRow, $item['count'])) {
                    $html .= '<tr>

					<td colspan="5">';
                    $html .= '<table class="external_table pull-left">

							';
                    if (count($dataTracking) !== 0) {
                        $countT = 0;
                        foreach ($dataTracking as $row) {
                            $html .= $this->customerTrackingRow($row, $countT === 0);
                            $countT++;
                        }
                    }
                    $countRow = 1;
                    $t0 = $dataTracking[0] ?? [];
                    $ship = self::num($t0['price_shipping'] ?? null);
                    $tPerItem = self::num($tPerItem) + $ship;
                    $tTotal = self::num($tTotal) + ($ship * $rate);
                    $totalThai += ($ship * $rate);
                    $totalChina += $ship;
                    $html = str_replace('['.($t0['id'] ?? '').'_changeproducttypeoption_'.($t0['product_type_id'] ?? '').']', 'selected="selected"', $html);

                    // SR: รูปพัสดุ (tracking_image ของ tracking แรกของร้าน) ใต้ตาราง tracking
                    $html .= '</table>
				<div class="pull-left ">';
                    foreach ($imageMap[(int) ($t0['id'] ?? 0)] ?? [] as $att) {
                        $u = Html::h($this->blob.$att);
                        $html .= '<a style="margin:3px;float:left" class="fancybox" href="'.$u.'">
                            <img src="'.$u.'" style="max-width: 50px;max-height: 33px;">
                        </a>';
                    }
                    $html .= '</div>
				<p class="pull-right text-right">'.$this->res('price_china_express').'</p>
				</td>


				<td class="text-right">'.Format::numberformat($tPerItem - $ship, true, true, true).'<br />'.Format::numberformat($t0['price_shipping'] ?? null, true, true, true).'</td>
				<td class="text-right">'.Format::numberformat($tTotal - ($ship * $rate)).'<br/>'.Format::numberformat($ship * $rate).'</td>
				</tr></tbody>
		    	<tfoot>

		    		<td colspan="3" >
			    		<div class="text-left" style="float:left">';
                    if ((string) ($t0['remark_shop'] ?? '') !== '') {
                        $html .= '<span class="text-danger" style="margin-left: 10px;">'.Html::h($t0['remark_shop']).'</span>';
                    }
                    $html .= '	</div>
		    		<div class="text-right">'.$this->res('total').'</div></td>
		    		<td class="text-right">'.self::n(self::num($tAmount)).'</td>
		    		<td class="text-right"></td>
		    		<td class="text-right">'.Format::numberformat($tPerItem, true, true, true).'</td>
		    		<td class="text-right">'.Format::numberformat($tTotal).'</td>

		    </tfoot>
</table>';
                    $dataTracking = [];
                    $mainNo++;
                    $no = 0;
                } else {
                    $countRow++;
                }
                $shopName = $item['shop_name'];
            }
        }

        $this->tail = [$tAmount, $tPerItem, $tTotal];

        $html .= '
<table class="table  table-bordered" style="font-size: 25px;margin-top:30px;margin-bottom: 0px;">
<tfoot>
		<td class="text-right">'.Format::numberformat($totalChina, true, true, true).'</td>
</tfoot>


<table class="table  table-bordered" style="font-size: 25px;">
<tfoot>
		<td class="text-right">'.Format::numberformat($totalThai).'</td>
</tfoot>
</table>

';
        // SR ไม่มี VAT (bill_product.is_vat/vat/total_vat ไม่มีใน schema)
        return $html;
    }

    /** แถว tracking ใน _order/form.php */
    private function customerTrackingRow(array $row, bool $first): string
    {
        [$exp0, $exp1, $exp2] = self::dates($row);
        $lot = Php74::eq($row['lot_name'] ?? null, '') ? '-' : Html::h($row['lot_name']);
        $tno = Php74::eq($row['no'] ?? null, '') ? '-' : Html::h($row['no']);
        $tid = (string) ($row['id'] ?? '');
        $html = '';
        if ($first) {
            $html .= '<tr class="tracking_list" >';
            if (Php74::eq($row['payment_id'] ?? null, '-2')) {
                $html .= '<td class="bxr_form_left" style="vertical-align: top;">'.$this->res('tracking').' : </td>';
                $html .= '<td class="bxr_form_right" style="vertical-align: top;">
								<div style="float:left;clear:both;">'.$this->res('not_buy').'</div>';
                $html .= '</td>';

                return $html;
            }
            // SR: แถวแรกแสดงเลข tracking เป็นข้อความ (ลูกค้าแก้ไม่ได้) และไม่มีปุ่ม + เพิ่มแถว
            $html .= '<td class="bxr_form_left" style="vertical-align: top;">'.$this->res('tracking').' : </td>';
            $html .= '<td class="bxr_form_right" style="vertical-align: top;">
								<div style="float:left;clear:both;">'.Html::h($row['tracking'] ?? '').'</div>
								<div style="float:left;clear:both;margin-top: -1px;"><input type="text" class="" onblur="save_quote($(this), \''.$tid.'\')" value="'.Html::h($row['quote'] ?? '').'" placeholder="- '.$this->res('remark_for_this_order').' -" /></div>';
            $html .= '</td>';
            $html .= '<td style="width: 105px;">';
        } else {
            $html .= '<tr class="tracking_list" >';
            $html .= '<td class="bxr_form_left" style="vertical-align: top;">'.$this->res('tracking').' : </td>';
            $html .= '<td class="bxr_form_right" style="vertical-align: top;">
							<div style="float:left;clear:both;padding-top: 0px;"><input type="text" class="" onblur="save_tracking($(this),\''.$tid.'\')" value="'.Html::h($row['tracking'] ?? '').'" placeholder="- '.$this->res('tracking').' -" /></div>
							<div style="float:left;clear:both;margin-top: -1px;"><input type="text" class="" onblur="save_quote($(this), \''.$tid.'\')" value="'.Html::h($row['quote'] ?? '').'" placeholder="- '.$this->res('remark_for_this_order').' -" /></div>';
            $html .= '</td>';
            $html .= '<td style="width: 105px;padding-top: 6px;">';
        }
        $pay = Php74::neq($row['payment_id'] ?? null, 0);
        $html .= '<div class="bxr_form_left" style="">'.$this->res('lot_list').' : </div>';
        $html .= '<div class="bxr_form_left" style="">'.$this->res('date_warehouse').' : </div>';
        $html .= '<div class="bxr_form_left" style="">'.$this->res('date_outofchina').' : </div>';
        $html .= '<div class="bxr_form_left" style="">'.$this->res('date_thai').' : </div>';
        if ($pay) {
            $html .= '<div class="bxr_form_left" style="">'.$this->res('export_thing').' : </div>';
        }
        $html .= '</td>';
        $html .= $first ? '<td>' : '<td style="padding-top: 0px;">';
        $html .= '<div class="" style="float:left;clear:both;">'.Status::getShipType($row['ship'] ?? null).$lot.'/'.$tno.'</div>';
        $html .= '<div class="" style="float:left;clear:both;">'.Format::getthaidate($exp0, true).'</div>';
        $html .= '<div class="" style="float:left;clear:both;">'.Format::getthaidate($exp1, true).'</div>';
        $html .= '<div class="" style="float:left;clear:both;">'.Format::getthaidate($exp2, true).'</div>';
        if ($pay) {
            $html .= '<div class="" style="float:left;clear:both;"><a href="'.$this->href.'admin/export/'.Html::h($row['payment_id']).'" >'.OrderLookups::prefix('payment').Html::h($row['payment_id']).'</a></div>';
        }
        $html .= '</td>';
        if (! $first) {
            $html .= '<td style="vertical-align:top;padding-top: 6px;">';
            $html .= '<button class="btn-danger" onclick="deleteline($(this), '.$tid.', '.Html::h($row['on_track_id'] ?? '').')"><i class="md md-exposure-minus-1"></i></button>';
            $html .= '</td>';
        }

        return $html;
    }

    /** @return array{0: string, 1: string, 2: string} ส่วนวันที่ของ date_warehouse/outofchina/thai */
    private static function dates(array $row): array
    {
        $d = fn ($k) => explode(' ', (string) ($row[$k] ?? ''))[0];

        return [$d('date_warehouse'), $d('date_outofchina'), $d('date_thai')];
    }

    // ------------------------------------------------------------------ _admin/_order/load_detail.php

    /**
     * _admin/_order/load_detail.php ของ SR ($view = false) และ _admin/_printorder/load_detail.php ($view = true: ฉบับอ่านอย่างเดียวของหน้าพิมพ์ใบสั่งซื้อ)
     * SR: ไม่มี VAT, มีบริการถ่ายรูป (is_takeapic), รูป tracking_image, สี #rrggbb, ไม่มีคอลัมน์ น้ำหนัก/ขนาด/ราคา/ประเภทสินค้า ของ jl
     */
    public function admin(int $id, bool $view = false): string
    {
        $head = Php74::row(DB::table('bill_product')->where('id', $id)->first(['id', 'ship', 'status']));
        $cart = $this->cart($id, true);
        $trackMap = $this->trackings($cart, true);
        $cashMap = $this->cashbacks($cart);
        $imageMap = $view ? [] : $this->trackingImages($cart, $trackMap);
        $types = $cart === [] ? [] : $this->productTypes($head['ship'] ?? null);
        $paid = Php74::num($head['status'] ?? null) >= 2 && ($head['status'] ?? null) !== null;
        $orderPrefix = OrderLookups::prefix('order');
        $onOff = fn ($v) => Php74::eq($v, 1) ? 'เปิด' : 'ปิด';

        // _admin/_order/load_detail.php ของ SR echo <style> ก่อนตาราง (ของ _printorder ไม่มี)
        $html = $view ? '' : "\n<style>\n\t.image_list_items {\n\t\tfloat: left;\n\t}\n</style>\n\n";
        $mainNo = 1;
        $no = 0;
        $totalChina = 0;
        $totalThai = 0;
        $tAmount = $tPerItem = null;
        $tTotal = $cart === [] ? null : $this->shippingPreSum($id);
        $dataTracking = [];
        if (count($cart) === 0) {
            $html .= '<tr><td class="text-center" colspan="9" style="line-height: 100px;">'.$this->res('no_data').'</td></tr>';
        } else {
            $shopName = '';
            $countRow = 1;
            foreach ($cart as $item) {
                $newShop = Php74::neq($shopName, $item['shop_name']);
                if ($newShop) {
                    $tAmount = 0;
                    $tPerItem = 0;
                    $tTotal = 0;
                }
                $amount = self::num($item['amount']);
                $price = self::num($item['price']);
                $rate = self::num($item['exchange_rate']);
                $tAmount = self::num($tAmount) + $amount;
                $tPerItem = self::num($tPerItem) + ($price * $amount);
                $tTotal = self::num($tTotal) + ($price * $amount * $rate);
                $totalChina += ($price * $amount);
                $totalThai += ($price * $amount * $rate);
                $pname = self::productName($item);

                if ($newShop) {
                    foreach ($trackMap[(int) $item['id']] ?? [] as $row) {
                        $dataTracking[] = $row;
                    }
                    $t0 = $dataTracking[0] ?? [];
                    $html .= '<table id="'.Html::h($t0['tracking'] ?? '').'" class="table  table-bordered">
				    <thead>
				        <tr>

				            <th class="text-left" colspan="3">';
                    if ($view) {
                        $html .= '
				            	<div class="col-xs-6" style="padding:0px">
				            	<div style="float:left;margin-top: 3px;height: 16px;">'.$mainNo.'.&nbsp;'.Html::h($t0['order'] ?? '').' ('.Html::h($item['shop_name']).')</div>
				            	</div>
				            	<div class="col-xs-2" style="padding:0px;margin-top: 3px;">'.$this->res('wood_protect').' : '.$onOff($item['is_craft']).'</div>
				            	<div class="col-xs-2" style="padding:0px;margin-top: 3px;">'.$this->res('qc').' : '.$onOff($item['is_qc']).'</div>';
                        $html .= '<div class="col-xs-2" style="padding:0px;margin-top: 3px;">'.$this->res('type').' : <select disabled="disabled" onchange="[changeproducttype]" >';
                    } else {
                        $sw = fn ($v, $fn) => Php74::eq($v, 1)
                            ? '<input type="checkbox" checked class="js-switch" onchange="'.$fn.'($(this), '.$item['id'].')" value="0" data-color="#f05050" data-size="small"/>'
                            : '<input type="checkbox"  class="js-switch" value="1" onchange="'.$fn.'($(this), '.$item['id'].')" data-color="#f05050" data-size="small"/>';
                        $html .= '
				            	<div class="col-xs-4" style="padding:0px">
				            	<div style="float:left;margin-top: 3px;width:75px;overflow: hidden;height: 16px;">'.$mainNo.'.&nbsp;'.Html::h($item['shop_name']).'</div>
				            	<input style="float: left;width: 60%;margin-left:3px;" type="text" class="" onblur="save_order_number($(this),'.($t0['id'] ?? '').')" value="'.Html::h($t0['order'] ?? '').'" />

				            	</div>
				            	<div class="col-xs-2" style="padding:0px;margin-top: 3px;">'.$this->res('wood_protect').' : '.$sw($item['is_craft'], 'SaveCartCraft').'</div>
				            	<div class="col-xs-2" style="padding:0px;margin-top: 3px;">'.$this->res('qc').' : '.$sw($item['is_qc'], 'SaveCartQc').'</div>
				            	<div class="col-xs-2" style="padding:0px;margin-top: 3px;">ถ่ายรูป : '.$sw($item['is_takeapic'] ?? null, 'SaveCartTakeapic').'</div>';
                        $html .= '<div class="col-xs-2" style="padding:0px;margin-top: 3px;">'.$this->res('type').' : <select onchange="[changeproducttype]" >';
                    }
                    $html .= '<option value="0"> - </option>';
                    foreach ($types as $t) {
                        $html .= '<option ['.($t0['id'] ?? '').'_changeproducttypeoption_'.$t['id'].'] value="'.$t['id'].'">'.Html::h($t['name']).'</option>';
                    }
                    $html .= '</select><div>';
                    $html .= '
				            </th>
				            <th class="text-center" style="width: 80px;">'.$this->res('amount').'</th>
				            <th class="text-center" style="width: 80px;">'.$this->res('price_cn').'</th>
				            <th class="text-center" style="width: 80px;">'.$this->res('total_cn').'</th>
				            <th class="text-center" style="width: 80px;">'.$this->res('price_th').'</th>'
                        .($view ? '' : '
				        	<th class="text-center" style="width: 40px;">'.$this->res('action').'</th>').'
				        </tr>
				    </thead>

				    <tbody>';
                }

                $no++;
                $second = $this->secondLine($item, true);
                $colors = self::craftQcColors($item, ! $view);
                $color = self::trColor($item);
                $html .= '<tr>';
                $html .= '<td style="width:50px;'.$color.'">'.$mainNo.'.'.$no.'.</td>';
                $html .= '<td style="width:70px;'.$colors.'" class="text-center"><a class="fancybox" href="'.Html::url($item['main_image']).'">
                <img src="'.Html::h($item['main_image']).'" style="max-width: 46px;max-height: 46px;">
            </a></td>';
                $html .= $view
                    ? '<td style="'.$color.'" >ชื่อ : '.Html::h($pname).'<br/>ลิ้งค์ : '.Html::h($item['url'])
                    : '<td style="'.$color.'" ><a href="'.Html::url($item['url']).'">'.Html::h($pname).'</a>';
                if ($second !== '') {
                    $html .= '<div class="cd-price">'.$second.'</div>';
                }
                if ((string) ($item['admin_remark'] ?? '') !== '') {
                    $html .= '<div class="admin_remark" style="color:#fb6d9d">'.Html::h($item['admin_remark']).'</div>';
                }
                if (Php74::neq($item['amount_before'], $item['amount'])) {
                    $html .= '<div class="text-danger ">'.$this->res('amount_before').' : '.Html::h($item['amount_before']).'</div>';
                }
                if ((string) ($item['cashback_remark'] ?? '') !== '') {
                    $html .= '<div class="text-danger ">'.Html::h($item['cashback_remark']).'</div>';
                }
                if (isset($cashMap[(int) $item['id']])) {
                    $html .= '<div class="text-danger ">'.$this->res('cashback').' : '.Format::numberformat($cashMap[(int) $item['id']]['amount']).'</div>';
                }
                $html .= '</td>';
                $html .= '<td style="width: 80px;'.$color.'" class="text-right">';
                $html .= $paid
                    ? Html::h($item['amount'])
                    : '<input type="text" class="form-control text-right" onblur="SaveCartAmount($(this),'.$item['id'].')" value="'.Html::h($item['amount']).'" />';
                $html .= '</td>';
                $html .= '<td style="width: 80px;'.$color.'" class="text-right">';
                $html .= $paid
                    ? $this->res('cn_currency').Html::h($item['price'])
                    : '<input type="text" class="form-control text-right" onblur="SaveCartPrice($(this),'.$item['id'].')" value="'.Html::h($item['price']).'" />';
                $html .= '</td>';
                $html .= '<td style="width: 80px;'.$color.'" class="text-right">'.Format::numberformat($amount * $price, true, true, true).'</td>';
                $html .= '<td style="width: 80px;'.$color.'" class="text-right">'.Format::numberformat($amount * ($price * $rate)).'</td>';
                if (! $view) {
                    // ค่าที่เดิมฝังใน string JS '...' ของ onclick: escape แบบ JS ก่อนแล้วค่อย escape แบบ HTML (เดิมไม่ escape)
                    // (encode entity ซ้ำเสมอ: ค่า "&#39;" ใน DB จะถูกถอดเป็น ' ใน attribute ก่อนรัน JS)
                    $js = fn ($v) => htmlspecialchars(str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $v), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', true);
                    $remark = implode('\n\r', array_map($js, preg_split('#\R+#', (string) ($item['cashback_remark'] ?? ''))));
                    $itemLabel = $orderPrefix.($head['id'] ?? '').' ลำดับ '.$mainNo.'.'.$no;
                    $html .= '<td style="width: 40px;'.$color.'" class="text-center"><button class="btn-warning" onclick="cashback_form('.$item['id'].', \''.$js($item['amount']).'\', \''.$js($item['exchange_rate']).'\', \''.$js($item['price']).'\', \''.$js($item['amount_before']).'\', \''.$remark.'\', \''.$js($itemLabel).'\')" ><i class="md md-attach-money"></i></button>
			<button style="margin-top: 2px;" class="btn-pink" onclick="remark_form(\''.$item['id'].'\')" ><i class="md  md-content-paste"></i></button>

			<button style="margin-top: 2px;" class="btn-info" onclick="color_form(\''.$item['id'].'\')" ><i class="md md-format-color-fill"></i></button>
		</td>';
                }
                $html .= '</tr>';

                if (Php74::eq($countRow, $item['count'])) {
                    $t0 = $dataTracking[0] ?? [];
                    $html .= '<tr>
					<td colspan="5">';
                    $html .= '<table class="external_table pull-left">

							';
                    if (! $paid) {
                        $html .= '<tr>
							<td class="bxr_form_left">'.$this->res('shipping_in_china').' : </td>
							<td class="bxr_form_right"><input type="text" class="" onblur="save_shipping($(this),\''.($t0['id'] ?? '').'\')" value="'.Format::numberformat($t0['price_shipping'] ?? null, false).'" /> <span style="margin-left:5px">'.$this->res('cn_currency').'</span>
							</td></tr>';
                    }
                    if (count($dataTracking) !== 0) {
                        $countT = 0;
                        foreach ($dataTracking as $row) {
                            $html .= $this->adminTrackingRow($row, $countT === 0, $view);
                            $countT++;
                        }
                    }
                    $countRow = 1;
                    $ship = self::num($t0['price_shipping'] ?? null);
                    $tPerItem = self::num($tPerItem) + $ship;
                    $tTotal = self::num($tTotal) + ($ship * $rate);
                    $totalThai += ($ship * $rate);
                    $totalChina += $ship;
                    $html = str_replace('[changeproducttype]', 'onchageproducttype('.($t0['id'] ?? '').', $(this))', $html);
                    $html = str_replace('['.($t0['id'] ?? '').'_changeproducttypeoption_'.($t0['product_type_id'] ?? '').']', 'selected="selected"', $html);

                    $html .= '</table>';
                    if (! $view) {
                        $html .= '
				<div class="pull-left ">';
                        foreach ($imageMap[(int) ($t0['id'] ?? 0)] ?? [] as $att) {
                            $u = Html::h($this->blob.$att);
                            $html .= '<a style="margin:3px;float:left" class="fancybox" href="'.$u.'">
                            <img src="'.$u.'" style="max-width: 50px;max-height: 33px;">
                        </a>';
                        }
                        $html .= '</div>';
                    }
                    $html .= '
				<p class="pull-right text-right">'.$this->res('price_china_express').'</p>
				</td>


				<td class="text-right">'.Format::numberformat($tPerItem - $ship, true, true, true).'<br />'.Format::numberformat($t0['price_shipping'] ?? null, true, true, true).'</td>
				<td class="text-right">'.Format::numberformat($tTotal - ($ship * $rate)).'<br/>'.Format::numberformat($ship * $rate).'</td>
				</tr></tbody>
		    	<tfoot>

		    		<td colspan="3" >
			    		<div class="text-left" style="float:left">';
                    if (! $view) {
                        $html .= '
				    		<button class="btn-pink" onclick="remark_shop_form(\''.($t0['id'] ?? '').'\')">
				    			<i class="md  md-content-paste"></i>
				    		</button>

				    		<button class="btn-danger" onclick="make_no_payment_track(\''.($t0['id'] ?? '').'\')">
				    			<i class="md  md-close"></i>
				    		</button>';
                    }
                    if ((string) ($t0['remark_shop'] ?? '') !== '') {
                        $html .= '<span class="text-danger" style="margin-left: 10px;">'.Html::h($t0['remark_shop']).'</span>';
                    }
                    $html .= '	</div>
		    		<div class="text-right">'.$this->res('total').'</div></td>
		    		<td class="text-right">'.self::n(self::num($tAmount)).'</td>
		    		<td class="text-right"></td>
		    		<td class="text-right">'.Format::numberformat($tPerItem, true, true, true).'</td>
		    		<td class="text-right">'.Format::numberformat($tTotal).'</td>

		    </tfoot>
</table>';
                    $dataTracking = [];
                    $mainNo++;
                    $no = 0;
                } else {
                    $countRow++;
                }
                $shopName = $item['shop_name'];
            }
        }

        $html .= '
<table class="table  table-bordered" style="font-size: 25px;margin-top:30px;margin-bottom: 0px;">
<tfoot>
		<td class="text-right">'.Format::numberformat($totalChina, true, true, true).'</td>
</tfoot>
<table class="table  table-bordered" style="font-size: 25px;">
<tfoot>
		<td class="text-right">'.Format::numberformat($totalThai).'</td>
</tfoot>
</table>';

        return $html;
    }

    /** แถว tracking ใน load_detail.php ของ SR ($view = _printorder/load_detail: แถวแรกเป็นข้อความ, ไม่มีลิงก์ล็อต/ปุ่ม +) */
    private function adminTrackingRow(array $row, bool $first, bool $view = false): string
    {
        [$exp0, $exp1, $exp2] = self::dates($row);
        $lot = Php74::eq($row['lot_name'] ?? null, '') ? '-' : Html::h($row['lot_name']);
        $tno = Php74::eq($row['no'] ?? null, '') ? '-' : Html::h($row['no']);
        $tid = (string) ($row['id'] ?? '');
        $html = '<tr class="tracking_list" >';
        if ($first) {
            if (Php74::eq($row['payment_id'] ?? null, '-2')) {
                $html .= '<td class="bxr_form_left" style="vertical-align: top;">'.$this->res('tracking').' : </td>';
                $html .= '<td class="bxr_form_right" style="vertical-align: top;">
								<div style="float:left;clear:both;">'.$this->res('not_buy').'</div>';
                $html .= '</td>';

                return $html;
            }
            $html .= '<td class="bxr_form_left" style="vertical-align: top;">'.$this->res('tracking').' : </td>';
            $html .= $view
                ? '<td class="bxr_form_right" style="vertical-align: top;">'.Html::h($row['tracking'] ?? '').'<br/>'.Html::h($row['quote'] ?? '').'</div>'
                : '<td class="bxr_form_right" style="vertical-align: top;">
								<div style="float:left;clear:both;"><input type="text" class="" onblur="save_tracking($(this),\''.$tid.'\')" value="'.Html::h($row['tracking'] ?? '').'" placeholder="- '.$this->res('tracking').' -" /></div>
								<div style="float:left;clear:both;margin-top: -1px;"><input type="text" class="" onblur="save_quote($(this), \''.$tid.'\')" value="'.Html::h($row['quote'] ?? '').'" placeholder="- '.$this->res('remark_for_this_order').' -" /></div>';
            $html .= '</td>';
            $html .= '<td style="width: 105px;">';
        } else {
            $html .= '<td class="bxr_form_left" style="vertical-align: top;">'.$this->res('tracking').' : </td>';
            $html .= '<td class="bxr_form_right" style="vertical-align: top;">
							<div style="float:left;clear:both;padding-top: 0px;"><input type="text" class="" onblur="save_tracking($(this),\''.$tid.'\')" value="'.Html::h($row['tracking'] ?? '').'" placeholder="- '.$this->res('tracking').' -" /></div>
							<div style="float:left;clear:both;margin-top: -1px;"><input type="text" class="" onblur="save_quote($(this), \''.$tid.'\')" value="'.Html::h($row['quote'] ?? '').'" placeholder="- '.$this->res('remark_for_this_order').' -" /></div>';
            $html .= '</td>';
            $html .= '<td style="width: 105px;padding-top: 6px;">';
        }
        $pay = Php74::neq($row['payment_id'] ?? null, 0);
        $html .= '<div class="bxr_form_left" style="">'.$this->res('lot_list').' : </div>';
        $html .= '<div class="bxr_form_left" style="">'.$this->res('date_warehouse').' : </div>';
        $html .= '<div class="bxr_form_left" style="">'.$this->res('date_outofchina').' : </div>';
        $html .= '<div class="bxr_form_left" style="">'.$this->res('date_thai').' : </div>';
        if ($pay) {
            $html .= '<div class="bxr_form_left" style="">'.$this->res('export_thing').' : </div>';
        }
        $html .= '</td>';
        $html .= $first ? '<td>' : '<td style="padding-top: 0px;">';
        $html .= ($view && $first)
            ? '<div class="" style="float:left;clear:both;">'.Status::getShipType($row['ship'] ?? null).$lot.'/'.$tno.'</div>'
            : '<div class="" style="float:left;clear:both;">'.Status::getShipType($row['ship'] ?? null).'<a href="'.$this->href.'admin/lot/'.Html::h($row['lot_id'] ?? '').'">'.$lot.'/'.$tno.'</a></div>';
        $html .= '<div class="" style="float:left;clear:both;">'.Format::getthaidate($exp0, true).'</div>';
        $html .= '<div class="" style="float:left;clear:both;">'.Format::getthaidate($exp1, true).'</div>';
        $html .= '<div class="" style="float:left;clear:both;">'.Format::getthaidate($exp2, true).'</div>';
        if ($pay) {
            $html .= '<div class="" style="float:left;clear:both;"><a href="'.$this->href.'admin/export/'.Html::h($row['payment_id']).'" >'.OrderLookups::prefix('payment').Html::h($row['payment_id']).'</a></div>';
        }
        $html .= '</td>';
        if ($first) {
            if (! $view) {
                $html .= '<td style="vertical-align:top">';
                $html .= '<button class="btn-success" onclick="addnewline($(this), '.Html::h($row['bill_product_detail_id'] ?? '').')"><i class="md md-exposure-plus-1"></i></button>';
                $html .= '</td>';
            }
        } else {
            $html .= '<td style="vertical-align:top;padding-top: 6px;">';
            $html .= '<button class="btn-danger" onclick="deleteline($(this), '.$tid.', '.Html::h($row['on_track_id'] ?? '').')"><i class="md md-exposure-minus-1"></i></button>';
            $html .= '</td>';
        }

        return $html;
    }

    // ------------------------------------------------------------------ _admin/_printorder/print.php (SR)

    /** ส่วนล่างของ _admin/_printorder/print.php ของ SR (jl มี _order/print.php ของลูกค้าแทน — SR ไม่มีไฟล์นั้น) */
    public function print(int $id): string
    {
        $head = Php74::row(DB::table('bill_product')->where('id', $id)->first(['id', 'ship', 'status']));
        $cart = $this->cart($id, true);
        $trackMap = $this->trackings($cart, true);
        $cashMap = $this->cashbacks($cart);
        $paid = Php74::num($head['status'] ?? null) >= 2 && ($head['status'] ?? null) !== null;
        $types = $cart === [] ? [] : $this->productTypes($head['ship'] ?? null);

        $html = '';
        $mainNo = 1;
        $no = 0;
        $totalChina = 0;
        $totalThai = 0;
        $tAmount = $tPerItem = null;
        $tTotal = $cart === [] ? null : $this->shippingPreSum($id);
        $dataTracking = [];
        if (count($cart) === 0) {
            $html .= '<tr><td class="text-center" colspan="9" style="line-height: 100px;">'.$this->res('no_data').'</td></tr>';
        } else {
            $shopName = '';
            $countRow = 1;
            foreach ($cart as $item) {
                $newShop = Php74::neq($shopName, $item['shop_name']);
                if ($newShop) {
                    $tAmount = 0;
                    $tPerItem = 0;
                    $tTotal = 0;
                }
                $amount = self::num($item['amount']);
                $price = self::num($item['price']);
                $rate = self::num($item['exchange_rate']);
                $tAmount = self::num($tAmount) + $amount;
                $tPerItem = self::num($tPerItem) + ($price * $amount);
                $tTotal = self::num($tTotal) + ($price * $amount * $rate);
                $totalChina += ($price * $amount);
                $totalThai += ($price * $amount * $rate);
                $pname = self::productName($item);

                if ($newShop) {
                    foreach ($trackMap[(int) $item['id']] ?? [] as $row) {
                        $dataTracking[] = $row;
                    }
                    $t0 = $dataTracking[0] ?? [];
                    $html .= '<table id="'.Html::h($t0['tracking'] ?? '').'" class="table  table-bordered">
				    <thead>
				        <tr>

				            <th class="text-left" colspan="3">
				            	<div class="col-xs-12" style="padding:0px">
				            	<div style="float:left;margin-top: 3px;">'.$mainNo.'.&nbsp;'.Html::h($t0['order'] ?? '').' ('.Html::h($item['shop_name']).')<br/>'.$this->res('wood_protect').' : ';
                    $html .= Php74::eq($item['is_craft'], 1) ? 'เปิด' : 'ปิด';
                    $html .= '&nbsp;|&nbsp;'.$this->res('qc').' : ';
                    $html .= Php74::eq($item['is_qc'], 1) ? 'เปิด' : 'ปิด';
                    $html .= '<br/>'.$this->res('type').' : <select disabled="disabled" onchange="[changeproducttype]" >';
                    $html .= '<option value="0"> - </option>';
                    foreach ($types as $tp) {
                        $html .= '<option ['.($t0['id'] ?? '').'_changeproducttypeoption_'.$tp['id'].'] value="'.$tp['id'].'">'.Html::h($tp['name']).'</option>';
                    }
                    $html .= '</select><div>';
                    $html .= '
				            </th>
				            <th class="text-center" style="width: 80px;">'.$this->res('amount').'</th>
				            <th class="text-center" style="width: 80px;">'.$this->res('price_cn').'</th>
				            <th class="text-center" style="width: 80px;">'.$this->res('total_cn').'</th>
				            <th class="text-center" style="width: 80px;">'.$this->res('price_th').'</th>
				        </tr>
				    </thead>

				    <tbody>';
                }

                $no++;
                $second = $this->printSecondLine($item);
                $colors = self::craftQcColors($item);
                $color = self::trColor($item);
                $html .= '<tr>';
                $html .= '<td style="width:50px;'.$color.'">'.$mainNo.'.'.$no.'.</td>';
                $html .= '<td style="width:70px;'.$colors.'" class="text-center">
                <img src="'.Html::h($item['main_image']).'" style="max-width: 46px;max-height: 46px;">
            </td>';
                $html .= '<td style="'.$color.'" >ชื่อ : '.Html::h($pname).'<br/>ลิ้งค์ : '.Html::h($item['url']);
                if ($second !== '') {
                    $html .= '<div class="cd-price">'.$second.'</div>';
                }
                if ((string) ($item['admin_remark'] ?? '') !== '') {
                    $html .= '<div class="admin_remark" style="color:#fb6d9d">'.Html::h($item['admin_remark']).'</div>';
                }
                if (Php74::neq($item['amount_before'], $item['amount'])) {
                    $html .= '<div class="text-danger ">'.$this->res('amount_before').' : '.Html::h($item['amount_before']).'</div>';
                }
                if ((string) ($item['cashback_remark'] ?? '') !== '') {
                    $html .= '<div class="text-danger ">'.Html::h($item['cashback_remark']).'</div>';
                }
                if (isset($cashMap[(int) $item['id']])) {
                    $html .= '<div class="text-danger ">'.$this->res('cashback').' : '.Format::numberformat($cashMap[(int) $item['id']]['amount']).'</div>';
                }
                $html .= '</td>';
                $html .= '<td style="width: 80px;'.$color.'" class="text-right">';
                $html .= Html::h($item['amount']);
                $html .= '</td>';
                $html .= '<td style="width: 80px;'.$color.'" class="text-right">';
                $html .= $paid ? $this->res('cn_currency').Html::h($item['price']) : Html::h($item['price']);
                $html .= '</td>';
                $html .= '<td style="width: 80px;'.$color.'" class="text-right">'.Format::numberformat($amount * $price, true, true, true).'</td>';
                $html .= '<td style="width: 80px;'.$color.'" class="text-right">'.Format::numberformat($amount * ($price * $rate)).'</td>';
                $html .= '</tr>';

                if (Php74::eq($countRow, $item['count'])) {
                    $t0 = $dataTracking[0] ?? [];
                    $html .= '<tr>
					<td colspan="5">';
                    $html .= '<table class="external_table pull-left">

							';
                    if (! $paid) {
                        $html .= '<tr>
							<td class="bxr_form_left">'.$this->res('shipping_in_china').' : </td>
							<td class="bxr_form_right">'.Format::numberformat($t0['price_shipping'] ?? null, false).'<span style="margin-left:5px">'.$this->res('cn_currency').'</span>
							</td></tr>';
                    }
                    if (count($dataTracking) !== 0) {
                        $countT = 0;
                        foreach ($dataTracking as $row) {
                            $html .= $this->printTrackingRow($row, $countT === 0);
                            $countT++;
                        }
                    }
                    $countRow = 1;
                    $ship = self::num($t0['price_shipping'] ?? null);
                    $tPerItem = self::num($tPerItem) + $ship;
                    $tTotal = self::num($tTotal) + ($ship * $rate);
                    $totalThai += ($ship * $rate);
                    $totalChina += $ship;
                    $html = str_replace('[changeproducttype]', 'onchageproducttype('.($t0['id'] ?? '').', $(this))', $html);
                    $html = str_replace('['.($t0['id'] ?? '').'_changeproducttypeoption_'.($t0['product_type_id'] ?? '').']', 'selected="selected"', $html);

                    $html .= '</table>
				<p class="pull-right text-right">'.$this->res('price_china_express').'</p>
				</td>


				<td class="text-right">'.Format::numberformat($tPerItem - $ship, true, true, true).'<br />'.Format::numberformat($t0['price_shipping'] ?? null, true, true, true).'</td>
				<td class="text-right">'.Format::numberformat($tTotal - ($ship * $rate)).'<br/>'.Format::numberformat($ship * $rate).'</td>
				</tr></tbody>
		    	<tfoot>

		    		<td colspan="3" >
			    		<div class="text-left" style="float:left">
				    		';
                    if ((string) ($t0['remark_shop'] ?? '') !== '') {
                        $html .= '<span class="text-danger" style="margin-left: 10px;">'.Html::h($t0['remark_shop']).'</span>';
                    }
                    $html .= '	</div>
		    		<div class="text-right">'.$this->res('total').'</div></td>
		    		<td class="text-right">'.self::n(self::num($tAmount)).'</td>
		    		<td class="text-right"></td>
		    		<td class="text-right">'.Format::numberformat($tPerItem, true, true, true).'</td>
		    		<td class="text-right">'.Format::numberformat($tTotal).'</td>

		    </tfoot>
</table>';
                    $dataTracking = [];
                    $mainNo++;
                    $no = 0;
                } else {
                    $countRow++;
                }
                $shopName = $item['shop_name'];
            }
        }

        $html .= '
<table class="table  table-bordered" style="font-size: 25px;margin-top:30px;margin-bottom: 0px;">
<tfoot>
		<td class="text-right">'.Format::numberformat($totalChina, true, true, true).'</td>
</tfoot>
<table class="table  table-bordered" style="font-size: 25px;">
<tfoot>
		<td class="text-right">'.Format::numberformat($totalThai).'</td>
</tfoot>
</table>';

        return $html;
    }

    private function printSecondLine(array $item): string
    {
        $haveSize = false;
        $haveRemark = false;
        $haveColor = false;
        $s = '';
        $size = (string) ($item['size'] ?? '');
        $color = (string) ($item['color'] ?? '');
        if (str_contains($size, '//')) {
            $s .= '
	                        <img src="'.Html::h($size).'" style="border:1px solid #a0a0a0;max-width: 26px;max-height: 26px;margin-right: 10px;">
	                    ';
        } elseif ($size !== '') {
            $haveSize = true;
            $s .= $this->res('size').':'.Html::h($size);
        }
        if (str_contains($color, '//')) {
            $s .= '
	                        <img src="'.Html::h($color).'" style="border:1px solid #a0a0a0;max-width: 26px;max-height: 26px;margin-right: 10px;">
	                    ';
        } elseif (str_contains($color, '#') && strlen($color) === 7) {
            $s .= '<br/>'.$this->res('color').':'.' <span style="background-color:'.Html::h($color).'">----</span>';
        } else {
            if ($haveSize) {
                $s .= '<br/>'.$this->res('color').':';
            }
            $haveColor = true;
            $s .= Html::nl2br($color);
        }
        if ((string) ($item['remark'] ?? '') !== '') {
            $haveRemark = true;
            if ($haveSize || $haveColor) {
                $s .= '<br/>'.$this->res('remark').':';
            }
            $s .= Html::nl2br($item['remark']);
        }
        if ((string) ($item['upload_file'] ?? '') !== '') {
            if ($haveSize || $haveRemark || $haveColor) {
                $s .= '<br/>'.$this->res('attachment').':';
            }
            $s .= '
	                        <img src="'.Html::h($this->blob.$item['upload_file']).'" style="border:1px solid #a0a0a0;width: 15px;height: 15px;margin-right: 10px;">
	                    ';
        }

        return $s;
    }

    private function printTrackingRow(array $row, bool $first): string
    {
        [$exp0, $exp1, $exp2] = self::dates($row);
        $lot = Php74::eq($row['lot_name'] ?? null, '') ? '-' : Html::h($row['lot_name']);
        $tno = Php74::eq($row['no'] ?? null, '') ? '-' : Html::h($row['no']);
        $html = '<tr class="tracking_list" >';
        if ($first) {
            if (Php74::eq($row['payment_id'] ?? null, '-2')) {
                $html .= '<td class="bxr_form_left" style="vertical-align: top;">'.$this->res('tracking').' : </td>';
                $html .= '<td class="bxr_form_right" style="vertical-align: top;">
								<div style="float:left;clear:both;">'.$this->res('not_buy').'</div>';
                $html .= '</td>';

                return $html;
            }
            $html .= '<td class="bxr_form_left" style="vertical-align: top;">'.$this->res('tracking').' : </td>';
            $html .= '<td class="bxr_form_right" style="vertical-align: top;">'.Html::h($row['tracking'] ?? '').'<br/>'.Html::h($row['quote'] ?? '').'</div>';
            $html .= '</td>';
            $html .= '<td style="width: 105px;">';
        } else {
            $html .= '<td class="bxr_form_left" style="vertical-align: top;">'.$this->res('tracking').' : </td>';
            $html .= '<td class="bxr_form_right" style="vertical-align: top;">
							<div style="float:left;clear:both;padding-top: 0px;">'.Html::h($row['tracking'] ?? '').'</div>
							<div style="float:left;clear:both;margin-top: -1px;">'.Html::h($row['quote'] ?? '').'</div>';
            $html .= '</td>';
            $html .= '<td style="width: 105px;padding-top: 6px;">';
        }
        $pay = Php74::neq($row['payment_id'] ?? null, 0);
        $html .= '<div class="bxr_form_left" style="">'.$this->res('lot_list').' : </div>';
        $html .= '<div class="bxr_form_left" style="">'.$this->res('date_warehouse').' : </div>';
        $html .= '<div class="bxr_form_left" style="">'.$this->res('date_outofchina').' : </div>';
        $html .= '<div class="bxr_form_left" style="">'.$this->res('date_thai').' : </div>';
        if ($pay) {
            $html .= '<div class="bxr_form_left" style="">'.$this->res('export_thing').' : </div>';
        }
        $html .= '</td>';
        $html .= $first ? '<td>' : '<td style="padding-top: 0px;">';
        $html .= '<div class="" style="float:left;clear:both;">'.Status::getShipType($row['ship'] ?? null).$lot.'/'.$tno.'</div>';
        $html .= '<div class="" style="float:left;clear:both;">'.Format::getthaidate($exp0, true).'</div>';
        $html .= '<div class="" style="float:left;clear:both;">'.Format::getthaidate($exp1, true).'</div>';
        $html .= '<div class="" style="float:left;clear:both;">'.Format::getthaidate($exp2, true).'</div>';
        if ($pay) {
            $html .= '<div class="" style="float:left;clear:both;">'.OrderLookups::prefix('payment').Html::h($row['payment_id']).'</div>';
        }
        $html .= '</td>';
        if (! $first) {
            // SR: ช่องปุ่มลบว่าง (หน้าพิมพ์)
            $html .= '<td style="vertical-align:top;padding-top: 6px;">';
            $html .= '</td>';
        }

        return $html;
    }
}
