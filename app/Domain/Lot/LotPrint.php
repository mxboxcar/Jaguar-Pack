<?php

namespace App\Domain\Lot;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Status;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * หน้าพิมพ์ของล็อต: _admin/_lot/print.php, print_all.php (Excel), print_receive.php เดิม
 * HTML สร้างแบบเดียวกับไฟล์เดิม (ต่อ string) แต่ค่าจาก DB ผ่าน e() — เดิม echo ดิบ
 * แถวลูก (header = id) เดิม query ทีละแถว → ดึงครั้งเดียว (LotLookups::childTrackings)
 */
final class LotPrint
{
    private static function r(string $k): string
    {
        return Lang::get($k);
    }

    private static function n(mixed $v): int|float
    {
        return Php74::num($v);
    }

    /**
     * UPDATE is_print + หัวล็อต + tracking + memo ของล็อตใน $ids (ลำดับ ORDER BY ตาม $orderByUser)
     * $markPrinted = false: อ่านอย่างเดียว (print_all ของผู้ถือ admin_lot_view — ไม่เขียน is_print)
     */
    private static function load(array $ids, bool $orderByUser, bool $markPrinted = true): array
    {
        if ($ids === []) {
            return [[], '', collect(), collect(), []];
        }
        if ($markPrinted) {
            DB::table('lot')->whereIn('id', $ids)->update(['is_print' => '1']);
        }
        $head = DB::table('lot')->whereIn('id', $ids)->get();
        $strHead = '';
        foreach ($head as $row) {
            $strHead .= ', '.Status::getShipType($row->type, false).' '.$row->name;
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $order = $orderByUser
            ? 'user.code ASC, tracking.user ASC, tracking.lot_id ASC,  tracking.`no` ASC'
            : 'tracking.lot_id ASC,  tracking.`no` ASC';
        // SQL เดิม (GROUP BY tracking.tracking กับ sql_mode ว่าง) — ค่าที่เคยฝังตรงๆ เปลี่ยนเป็น placeholder
        // ยกเว้น header_count: เดิม subquery (t2.header = tracking.id) ต่อแถว เทียบ TEXT กับ int → ใช้ index ไม่ได้
        // = สแกน tracking ทั้งตารางต่อแถว (ล็อต ~100 tracking ≈ 75 วินาที → 504) — นับแยกครั้งเดียวด้านล่าง
        $detail = collect(DB::select("SELECT tracking.* , user.code , product_type.name as type_name , lot.name as lot_name
		, (SELECT COUNT(*) FROM
			(SELECT COUNT(id), USER FROM tracking AS t WHERE t.lot_id IN ($in)  AND t.is_delete = 0  AND (t.header = 0 OR t.header IS NULL) GROUP BY t.`tracking`) ts
		 WHERE ts.user = tracking.`user`) AS `count`
		FROM tracking
		LEFT JOIN user ON tracking.user = user.id
		LEFT JOIN product_type ON tracking.product_type_id = product_type.id
		LEFT JOIN lot ON tracking.lot_id = lot.id
		WHERE tracking.lot_id IN ($in) AND tracking.is_delete = 0 AND (tracking.header = 0 OR tracking.header IS NULL)
		GROUP BY tracking.tracking	ORDER BY  $order", array_merge($ids, $ids)));
        self::withHeaderCount($detail);

        $memo = DB::table('lot_memo')->leftJoin('user', 'lot_memo.user', '=', 'user.id')
            ->whereIn('lot_memo.lot_id', $ids)->where('lot_memo.is_delete', 0)
            ->orderByDesc('lot_memo.no')
            ->get(['lot_memo.*', 'user.code', 'user.id as user_id']);

        $withChildren = $detail->filter(fn ($r) => Php74::neq($r->header_count, 0))->pluck('id')->all();

        return [$head, substr($strHead, 1), $detail, $memo, LotLookups::childTrackings($withChildren)];
    }

    /**
     * header_count = (SELECT count(id) FROM tracking as t2 WHERE t2.header = tracking.id) เดิม (นับทุกแถว รวมที่ลบแล้ว)
     * นับครั้งเดียวด้วย header IN ('id', ...) ซึ่งใช้ idx_tracking_header ได้ — ค่า header ในระบบเป็นตัวเลขล้วน → ผลเท่ากัน
     * (แบบเดียวกับ PaymentLookups::withHeaderCount; แถวลูกที่พิมพ์ก็ใช้ header = 'id' แบบข้อความอยู่แล้ว)
     *
     * @param  Collection<int, object>  $detail
     */
    private static function withHeaderCount(Collection $detail): void
    {
        $ids = array_values(array_unique(array_map(fn ($r) => (string) (int) $r->id, $detail->all())));
        $counts = $ids === [] ? [] : DB::table('tracking')->whereIn('header', $ids)->groupBy('header')
            ->pluck(DB::raw('count(id) as c'), 'header')->all();
        foreach ($detail as $r) {
            $r->header_count = (string) ($counts[(string) $r->id] ?? 0);
        }
    }

    /** print.php — แยกตารางต่อลูกค้า + ขึ้นหน้าใหม่ */
    public static function print(array $ids): string
    {
        [, $head, $detail, $memo, $children] = self::load($ids, true);
        $e = fn ($v) => e((string) $v);

        $html = view('lot.print.header')->render();
        $html .= '<style>
@media print{
	@page {size: landscape;
	}
}
</style>';
        if (count($memo) !== 0) {
            $html .= '<table class="lot_table_cus" style="margin-top:20px;">
	<thead>
		<th style="font-size:40px!important" colspan="5">'.$e($head).'</th>
	</thead>
	<thead>
		<tr>
			<th style="width:100px;"  class="text-center">'.self::r('no').'</th>
			<th class="text-center">'.self::r('detail').'</th>
			<th class="text-center">'.self::r('weight').'</th>
			<th class="text-center">'.self::r('cubic').'</th>
			<th class="text-center">'.self::r('customer_code').'</th>
		</tr>
	</thead>
	<tbody style="width:100%">';
            foreach ($memo as $item) {
                $html .= '<tr style="width:100%">';
                $html .= Php74::eq($item->no, $item->no_max)
                    ? '<td style="width:100px;"  class="sorting_tracking text-center">'.$e($item->no).'</td>'
                    : '<td style="width:100px;"  class="sorting_tracking text-center">'.$e($item->no).'-'.$e($item->no_max).'</td>';
                $html .= '<td class="text-left">'.$e($item->detail).'</td>';
                $html .= '<td class="text-right">'.$e($item->weight).'</td>';
                $html .= '<td class="text-right">'.$e($item->cubic).'</td>';
                $html .= '<td class="text-center">'.$e($item->code).'</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody>
	</table>';
            $html .= "<p style='page-break-before: always;'></p>";
        }

        $cache = '';
        $count = 0;
        $t = self::zeroTotals();
        foreach ($detail as $item) {
            $count++;
            $cubic = ((self::n($item->width) * self::n($item->height) * self::n($item->long)) / 1000000) * self::n($item->amount);
            self::addTotals($t, $item, $cubic);
            $rs = $e(Php74::str(self::n($item->header_count) + 1));

            if (Php74::neq($item->user, $cache) || $cache === '') {
                $html .= '

		<table class=" lot_table_cus" style="width:100%;margin-top:10px;">
			<thead>
				<th class="text-left" style="font-size:40px!important" colspan="7">'.$e($item->code).'</th>
				<th class="text-center" style="font-size:40px!important" colspan="3">'.$e($head).'</th>
			</thead>
			<thead>
				<th class="text-center" >'.self::r('no').'</th>
				<th class="text-center" >'.self::r('lot').'/'.self::r('no').'</th>
				<th class="text-center" >'.self::r('tracking').'</th>
				<th class="text-center">'.self::r('date_thai').'</th>
				<th class="text-center">'.self::r('amount').'</th>
				<th class="text-center">'.self::r('weight').'</th>
				<th class="text-center">'.self::r('cubic').'</th>
				<th class="text-center">'.self::r('type').'</th>
				<th class="text-center" >'.self::r('price_kg').'</th>
				<th class="text-center" >'.self::r('price_cubic').'</th>
			</thead>
		';
            }

            $craft = Php74::eq($item->is_craft, 1);
            $qc = Php74::eq($item->is_qc, 1);
            if ($craft && $qc) {
                $html .= '<tr style="background-color:#f9ffa5!important;-webkit-print-color-adjust: exact; ">';
            } else {
                if ($craft) {
                    $html .= '<tr style="background-color:#ffdfa5!important;-webkit-print-color-adjust: exact; ">';
                }
                if ($qc) {
                    $html .= '<tr style="background-color:#a5ffc5!important;-webkit-print-color-adjust: exact; ">';
                }
                if (! $craft && ! $qc) {
                    $html .= '<tr>';
                }
            }

            $html .= '


		<td style="vertical-align:top"  rowspan="'.$rs.'" class="text-center">'.$count.'</td>
		<td style="vertical-align:top" rowspan="'.$rs.'" class="text-center">'.$e($item->lot_name).'/'.$e($item->no).'</td>
		<td>'.(Php74::eq($item->is_checked, 0) ? '' : '&#10004;').$e($item->tracking).'</td>
		<td style="vertical-align:top" rowspan="'.$rs.'" class="text-center">'.Format::getthaidate(explode(' ', (string) $item->date_thai)[0], true, true).'</td>
		<td style="vertical-align:top"  rowspan="'.$rs.'" class="text-right">'.Format::numberformatzero($item->amount).'</td>
		<td style="vertical-align:top" rowspan="'.$rs.'" class="text-right">'.Format::numberformatone(self::n($item->weight) * self::n($item->amount)).'</td>
		<td style="vertical-align:top" rowspan="'.$rs.'" class="text-right">'.Format::cubicformat($cubic).'</td>
		<td style="vertical-align:top" rowspan="'.$rs.'" class="text-center">'.$e($item->type_name).'</td>
		<td style="vertical-align:top" rowspan="'.$rs.'" class="text-right">'.Format::numberformat($item->price_kg).'</td>
		<td style="vertical-align:top" rowspan="'.$rs.'" class="text-right">'.Format::numberformat($item->price_cubic).'</td>
	</tr>
	';
            foreach ($children[(string) $item->id] ?? [] as $child) {
                $html .= '<tr style="background-color:#ffdfa5!important;-webkit-print-color-adjust: exact;">
						<td style="white-space: nowrap;" class="showdetailbox">'.$e($child).'</td>
					</tr>';
            }

            $cache = (string) $item->user;

            if (Php74::eq($item->count, $count)) {
                $html .= '
			<thead>
				<th class="text-center">'.$count.'</th>
				<th colspan="3"></th>
				<th class="text-right">'.Format::numberformatzero($t['amount']).'</th>
				<th class="text-right">'.Format::numberformatone($t['weight']).'</th>
				<th class="text-right">'.Format::cubicformat($t['cubic']).'</th>
				<th ></th>
				<th class="text-right">'.Format::numberformat($t['price_kg']).'</th>
				<th class="text-right">'.Format::numberformat($t['price_cubic']).'</th>
			</thead>
			</table>
		';
                $html .= "<p style='page-break-before: always;'></p>";
                $count = 0;
                $t = self::zeroTotals(0);
            }
        }

        $html .= '
<script>
window.print();
</script>';

        return $html;
    }

    /** print_all.php — ตารางเดียวทั้งล็อต (ส่งเป็นไฟล์ .xls แบบ HTML) */
    public static function printAll(array $ids, bool $markPrinted = true): string
    {
        [, $head, $detail, $memo, $children] = self::load($ids, false, $markPrinted);
        $e = fn ($v) => e((string) $v);

        $html = view('lot.print.header')->render();
        $html .= '<style>
@media print{
	@page {size: landscape;
	}
}
</style>';
        if (count($memo) !== 0) {
            $html .= '<table>
	<thead>
		<th colspan="5">'.$e($head).'</th>
	</thead>
	<thead>
		<tr>
			<th>'.self::r('no').'</th>
			<th >'.self::r('detail').'</th>
			<th >'.self::r('weight').'</th>
			<th >'.self::r('cubic').'</th>
			<th >'.self::r('customer_code').'</th>
		</tr>
	</thead>
	<tbody style="width:100%">';
            foreach ($memo as $item) {
                $html .= '<tr style="width:100%">';
                $html .= Php74::eq($item->no, $item->no_max)
                    ? '<td>'.$e($item->no).'</td>'
                    : '<td>'.$e($item->no).'-'.$e($item->no_max).'</td>';
                $html .= '<td >'.$e($item->detail).'</td>';
                $html .= '<td>'.$e($item->weight).'</td>';
                $html .= '<td >'.$e($item->cubic).'</td>';
                $html .= '<td >'.$e($item->code).'</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody>
	</table>';
            $html .= "<p style='page-break-before: always;'></p>";
        }

        $html .= '

		<table >
			<tr>
				<th colspan="11">'.$e($head).'</th>
			</tr>
			<tr>
				<th>'.self::r('no').'</th>
				<th>'.self::r('lot').'/'.self::r('no').'</th>
				<th style="width:200px;">'.self::r('tracking').'</th>
				<th>'.self::r('customer_code').'</th>
				<th >'.self::r('date_thai').'</th>
				<th >'.self::r('amount').'</th>
				<th >'.self::r('weight').'</th>
				<th >'.self::r('cubic').'</th>
				<th >'.self::r('type').'</th>
				<th >'.self::r('price_kg').'</th>
				<th >'.self::r('price_cubic').'</th>
			</tr>
		';
        $count = 0;
        $t = self::zeroTotals();
        foreach ($detail as $item) {
            $count++;
            $cubic = ((self::n($item->width) * self::n($item->height) * self::n($item->long)) / 1000000) * self::n($item->amount);
            self::addTotals($t, $item, $cubic);
            $rs = $e(Php74::str(self::n($item->header_count) + 1));
            $html .= '<tr>';
            $html .= '


		<td rowspan="'.$rs.'" class="text-center">'.$count.'</td>
		<td rowspan="'.$rs.'" class="text-center">'.$e($item->lot_name).'/'.$e($item->no).'</td>
		<td>="'.$e($item->tracking).'"</td>
		<td>="'.$e($item->code).'"</td>
		<td  rowspan="'.$rs.'" class="text-center">'.Format::getthaidate(explode(' ', (string) $item->date_thai)[0], true, true).'</td>';
            $html .= '<td style="vertical-align:top"  rowspan="'.$rs.'" class="text-right">'.Format::numberformatzero($item->amount).'</td>';
            $html .= '<td style="vertical-align:top" rowspan="'.$rs.'" class="text-right">'.Format::numberformatone(self::n($item->weight) * self::n($item->amount)).'</td>';
            $html .= '<td style="vertical-align:top" rowspan="'.$rs.'" class="text-right">'.Format::cubicformat($cubic).'</td>';
            $html .= '

		<td  rowspan="'.$rs.'" class="text-center">'.$e($item->type_name).'</td>
		<td  rowspan="'.$rs.'" class="text-right">'.Format::numberformat($item->price_kg).'</td>
		<td  rowspan="'.$rs.'" class="text-right">'.Format::numberformat($item->price_cubic).'</td>
	</tr>
	';
            foreach ($children[(string) $item->id] ?? [] as $child) {
                $html .= '<tr>
						<td style="white-space: nowrap;" class="showdetailbox">'.$e($child).'</td>
					</tr>';
            }
        }
        $html .= '
			<thead>
				<th>'.$count.'</th>
				<th colspan="4"></th>
				<th>'.Format::numberformatzero($t['amount']).'</th>
				<th >'.Format::numberformatone($t['weight']).'</th>
				<th >'.Format::cubicformat($t['cubic']).'</th>
				<th ></th>
				<th >'.Format::numberformat($t['price_kg']).'</th>
				<th >'.Format::numberformat($t['price_cubic']).'</th>

		';
        $html .= '
</thead>
			</table>
';

        return $html;
    }

    /** print_receive.php — ใบรับสินค้าของล็อตเดียว (เดิมไม่กรอง is_delete) */
    public static function printReceive(int $id): string
    {
        $e = fn ($v) => e((string) $v);
        $lot = DB::table('lot')->where('id', $id)->first();
        $rows = $id > 0 ? DB::table('tracking as t')
            ->leftJoin('user', 't.user', '=', 'user.id')
            ->leftJoin('lot', 'lot.id', '=', 't.lot_id')
            ->leftJoin('product_type', 't.product_type_id', '=', 'product_type.id')
            ->where('t.lot_id', $id)->orderBy('t.no')
            ->get(['t.*', 'lot.name as lot_name', 'user.code', 'user.id as user_id', 'product_type.name']) : collect();

        $str = '<meta charset="utf-8">
<style>
@page { size: landscape; }
table { border-collapse: collapse; }
</style>
<div style="text-align:center"><h1>'.self::r('receive_page').' '.config('legacy.project_name').'</h1></div>';
        $str .= '<table style="float:left;">';
        $str .= '<tr>';
        $str .= '<td class="text-right" style="font-size:20px!important;width:110px">ประเภท :</td>';
        $str .= '<td style="font-size:20px!important">';
        $str .= Status::getShipType($lot->type ?? null);
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td style="font-size:20px!important" class="text-right">'.self::r('lot').' :</td>';
        $str .= '<td style="font-size:20px!important">'.$e($lot->name ?? '').'</td>';
        $str .= '</tr>';
        $str .= '</table>';
        $str .= '<div style="clear:both"></div>';
        $str .= '<table style="font-size:15px;border-spacing: 0;border-collapse: collapse;width:100%;margin-top:20px" border="1" >';
        $str .= '<tr>';
        $str .= '<td style="width:20px;text-align:center;background-color:#ddd!important;padding:3px;" ><strong>'.self::r('no').'</strong></td>';
        $str .= '<td style="text-align:center;background-color:#ddd!important;padding:3px;"><strong>'.self::r('customer_code').'</strong></td>';
        $str .= '<td style="width:80px;text-align:center;background-color:#ddd!important;padding:3px;"><strong>'.self::r('type').'</strong></td>';
        $str .= '<td style="text-align:center;background-color:#ddd!important;padding:3px;" ><strong>'.self::r('tracking').'</strong></td>';
        $str .= '<td style="text-align:center;background-color:#ddd!important;padding:3px;" ><strong>'.self::r('remark').'</strong></td>';
        foreach (['weight', 'width', 'long', 'height'] as $k) {
            $str .= '<td style="width:45px;text-align:center;background-color:#ddd!important;padding:3px;"><strong>'.self::r($k).'</strong></td>';
        }
        $str .= '<td style="width:45px;text-align:center;background-color:#ddd!important;padding:3px;" ><strong>'.self::r('wood_protect').'</strong></td>';
        foreach (['qc', 'express', 'repack', 'else'] as $k) {
            $str .= '<td style="width:45px;text-align:center;background-color:#ddd!important;padding:3px;"><strong>'.self::r($k).'</strong></td>';
        }
        $str .= '</tr>';

        foreach ($rows as $it) {
            $str .= '<tr style="width:100%" >';
            $str .= '<td style="text-align:center;padding:3px;">'.$e($it->no).'</td>';
            $str .= '<td style="padding:3px;text-align:center">'.$e($it->code).'</td>';
            $str .= '<td style="width:80px;padding:3px;text-align:center">'.$e($it->name).'</td>';
            $str .= '<td style="padding:3px;">'.$e($it->tracking).'</td>';
            $str .= '<td style="padding:3px;">'.$e($it->remark).'</td>';
            foreach (['weight', 'width', 'long', 'height'] as $k) {
                $str .= '<td style="text-align:right;padding:3px;">'.$e($it->$k).'</td>';
            }
            $str .= Php74::eq($it->is_craft, 0)
                ? '<td style="padding:3px;text-align:center">-</td>'
                : '<td style="padding:3px;text-align:center">o</td>';
            foreach (['price_qc', 'price_express', 'price_repack', 'price_else'] as $k) {
                $str .= '<td style="text-align:right;padding:3px;">'.$e($it->$k).'</td>';
            }
        }
        $str .= '</table>';

        return $str.'<script>window.print();</script>';
    }

    /** ผลรวมเริ่มต้น: รอบแรกเดิมเป็นตัวแปรที่ยังไม่กำหนด (null) ส่วน price_* หลังขึ้นตารางใหม่เป็น 0 */
    private static function zeroTotals(mixed $price = null): array
    {
        return ['amount' => 0, 'weight' => 0, 'cubic' => 0, 'price_kg' => $price, 'price_cubic' => $price];
    }

    private static function addTotals(array &$t, object $item, int|float $cubic): void
    {
        $t['cubic'] = $t['cubic'] + $cubic;
        $t['amount'] = $t['amount'] + self::n($item->amount);
        $t['weight'] = $t['weight'] + (self::n($item->weight) * self::n($item->amount));
        $t['price_kg'] = self::n($t['price_kg']) + self::n($item->price_kg);
        $t['price_cubic'] = self::n($t['price_cubic']) + self::n($item->price_cubic);
    }
}
