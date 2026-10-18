<?php

namespace App\Domain\Warehouse;

use App\Domain\Warehouse\WarehouseLookups as W;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use Illuminate\Support\Facades\DB;

/**
 * _admin/_warehouse/load_lot_detail.php (ยังไม่ถึงไทย) และ load_lot_detail_thai.php (ถึงไทยแล้ว)
 *
 * - เดิมต่อ selected_print[] ลง "lot_id IN (...)" ตรงๆ (SQL injection) → bind เฉพาะค่าที่เป็นจำนวนเต็ม
 *   ค่าที่เดิมทำให้ SQL error (ว่าง/ไม่ใช่ตัวเลข) = ไม่มีแถว และรายชื่อล็อตว่าง เหมือนเดิม
 * - คอลัมน์ tracking.header_count / agent_code ไม่มีในตาราง → เดิมได้ null: rowspan="1", ไม่มีแถวย่อย, ไม่มี [agent]
 *   (ยังคงรองรับถ้าคอลัมน์มีในอนาคต: แถวย่อยดึงทีเดียวทั้งหน้าแทน 1 query ต่อแถว)
 * - ค่าจาก DB ถูก escape (เดิม echo ดิบ)
 * - $search เดิมมาจาก extract($_GET) และต่อท้ายหลัง ORDER BY (= SQL error ถ้าส่งมา) → ไม่รองรับ
 */
final class LotDetailRenderer
{
    public static function render(?array $selected, bool $thai): string
    {
        // เดิม: foreach ($selected_print as $item) $val .= ','.$item;  (ค่าที่เป็น array → "Array")
        $parts = array_map(fn ($v) => is_array($v) ? 'Array' : (string) $v, $selected ?? []);
        $joined = implode(',', $parts);
        $ids = null;
        if ($joined !== '') {
            $ids = [];
            foreach ($parts as $p) {
                if (! preg_match('/^\s*-?\d+\s*$/', $p)) {
                    $ids = null; // เดิม SQL error
                    break;
                }
                $ids[] = (int) $p;
            }
        }

        $rows = [];
        $names = '';
        if ($ids !== null) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $sql = $thai
                ? "SELECT tracking.* , user.code ,  lot.name as lot_name, t4.name as section_name, weight_thai
                    FROM tracking
                    LEFT JOIN user ON tracking.user = user.id
                    LEFT JOIN lot ON lot.id = tracking.lot_id
                    LEFT JOIN warehouse_section_detail as t3 ON t3.tracking_id = tracking.id
                    LEFT JOIN warehouse_section as t4 ON t4.id = t3.section_id
                    WHERE tracking.date_section != '0000-00-00 00:00:00' AND t3.is_delete = 0
                    AND tracking.lot_id IN ($in) AND tracking.is_delete = 0
                    AND (tracking.header = 0 OR tracking.header IS NULL) GROUP BY tracking.id ORDER BY tracking.date_section desc"
                : "SELECT tracking.* , user.code ,  lot.name as lot_name, t4.name as section_name, weight_thai
                    FROM tracking
                    LEFT JOIN user ON tracking.user = user.id
                    LEFT JOIN lot ON lot.id = tracking.lot_id
                    LEFT JOIN warehouse_section_detail as t3 ON t3.tracking_id = tracking.id
                    LEFT JOIN warehouse_section as t4 ON t4.id = t3.section_id
                    WHERE tracking.date_section = '0000-00-00 00:00:00'  AND tracking.lot_id IN ($in)
                    AND tracking.is_delete = 0 AND  t3.is_delete = 0 and  (tracking.header = 0 OR tracking.header IS NULL) GROUP BY tracking.id  ORDER BY tracking.user asc";
            $rows = array_map(fn ($r) => W::strings($r), DB::select($sql, $ids));
            foreach (DB::table('lot')->whereIn('id', $ids)->orderBy('id')->pluck('name') as $n) {
                $names .= ', '.$n;
            }
        }

        $children = self::children($rows);
        $html = $thai ? self::rowsThai($rows, $children) : self::rows($rows, $children);

        return view('warehouse.admin.warehouse.lot_detail', [
            'thai' => $thai,
            'showHeader' => $joined !== '',
            'names' => substr($names, 1),
            'body' => $html,
        ])->render();
    }

    /** @return array<int, list<string>> tracking ลูกของแถวที่ header_count != 0 (ทีเดียวทั้งหน้า) */
    private static function children(array $rows): array
    {
        $ids = [];
        foreach ($rows as $r) {
            if (Php74::neq($r['header_count'] ?? null, 0)) {
                $ids[] = (int) $r['id'];
            }
        }
        if ($ids === []) {
            return [];
        }
        $out = [];
        foreach (DB::table('tracking')->whereIn('header', array_map('strval', $ids))->orderBy('id')->get(['header', 'tracking']) as $c) {
            $out[(int) $c->header][] = (string) $c->tracking;
        }

        return $out;
    }

    private static function trOpen(array $item, string $idAttr): string
    {
        if (Php74::eq($item['is_craft'], 1) && Php74::eq($item['is_qc'], 1)) {
            return '<tr'.$idAttr.' style="background-color:#f9ffa5">';
        }
        $html = '';
        $found = false;
        if (Php74::eq($item['payment_id'], '-3')) {
            $html .= '<tr'.$idAttr.' style="background-color:#e789ff">';
            $found = true;
        }
        if (Php74::eq($item['price_kg'], 0) && Php74::eq($item['price_cubic'], 0)) {
            $html .= '<tr'.$idAttr.' style="background-color:#ff0c00">';
            $found = true;
        }
        if (Php74::eq($item['is_craft'], 1)) {
            $html .= '<tr'.$idAttr.' style="background-color:#ffdfa5">';
            $found = true;
        }
        if (Php74::eq($item['is_qc'], 1)) {
            $html .= '<tr'.$idAttr.' style="background-color:#a5ffc5">';
            $found = true;
        }

        return $found ? $html : '<tr'.$idAttr.'>';
    }

    /** escape แบบ htmlspecialchars() ของ 7.4 (ไม่แตะ ') — ใช้ใน element / attribute ที่ครอบด้วย " เท่านั้น */
    private static function h(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_COMPAT | ENT_HTML401);
    }

    private static function n(mixed $v): int|float
    {
        return Php74::num($v);
    }

    private static function date(?string $v): string
    {
        return Format::getthaidate(explode(' ', (string) $v)[0], true, true);
    }

    private static function cubic(array $item): int|float
    {
        return ((self::n($item['width']) * self::n($item['height']) * self::n($item['long'])) / 1000000) * self::n($item['amount']);
    }

    private static function code(array $item): string
    {
        $agent = (string) ($item['agent_code'] ?? '');

        return self::h($item['code']).($agent === '' ? '' : "<span class='text-danger'>[".self::h($agent).']</span>');
    }

    private static function subRows(array $item, array $children): string
    {
        $html = '';
        foreach ($children[(int) $item['id']] ?? [] as $t) {
            $html .= '<tr style="background-color:#ffdfa5">
								<td style="white-space: nowrap;" class="showdetailbox">'.self::h($t).'</td>
							</tr>';
        }

        return $html;
    }

    /** @param array<string, int|float|null> $s */
    private static function hidden(array $s): string
    {
        $html = '<input type="hidden" class="hid_sum_amount" value="'.Format::numberformatzero($s['amount'], false).'" />';
        $html .= '<input type="hidden" class="hid_sum_weight" value="'.Format::numberformatone($s['weight'], false).'" />';
        $html .= '<input type="hidden" class="hid_sum_cubic" value="'.Format::cubicformat($s['cubic'], false).'" />';
        $html .= '<input type="hidden" class="hid_sum_kg" value="'.Format::numberformat($s['kg'], false, false).'" />';
        $html .= '<input type="hidden" class="hid_sum_pcubic" value="'.Format::numberformat($s['pcubic'], false, false).'" />';

        $html .= '<input type="hidden" class="hid_sum_width" value="'.Format::numberformatone($s['width'], false).'" />';
        $html .= '<input type="hidden" class="hid_sum_height" value="'.Format::numberformatone($s['height'], false).'" />';
        $html .= '<input type="hidden" class="hid_sum_long" value="'.Format::numberformatone($s['long'], false).'" />';
        $html .= '<input type="hidden" class="hid_sum_qc" value="'.Format::numberformat($s['qc'], false, false).'" />';
        $html .= '<input type="hidden" class="hid_sum_repack" value="'.Format::numberformat($s['repack'], false, false).'" />';
        $html .= '<input type="hidden" class="hid_sum_express" value="'.Format::numberformat($s['express'], false, false).'" />';
        $html .= '<input type="hidden" class="hid_sum_else" value="'.Format::numberformat($s['else'], false, false).'" />';
        $html .= '<input type="hidden" class="hid_sum_craft" value="'.Format::numberformat($s['craft'], false, false).'" />';

        return $html;
    }

    /** @return array<string, int|float|null> */
    private static function emptySums(): array
    {
        return array_fill_keys(['amount', 'weight', 'cubic', 'kg', 'pcubic', 'width', 'height', 'long', 'qc', 'repack', 'express', 'else', 'craft'], null);
    }

    private static function addSums(array &$s, array $item, bool $thai): void
    {
        if (! Php74::eq($item['header'], 0)) {
            return;
        }
        $n = fn (string $k) => self::n($item[$k]);
        $s['amount'] = self::n($s['amount']) + $n('amount');
        $s['weight'] = self::n($s['weight']) + ($n('weight') * $n('amount'));
        $s['cubic'] = self::n($s['cubic']) + self::cubic($item);
        $s['kg'] = self::n($s['kg']) + $n('price_kg');
        $s['pcubic'] = self::n($s['pcubic']) + $n('price_cubic');
        $s['width'] = self::n($s['width']) + $n('width');
        // เดิม (thai): $sum_height = $sum_heigh + ... (พิมพ์ชื่อตัวแปรผิด) → ได้ความสูงของแถวสุดท้าย
        $s['height'] = ($thai ? 0 : self::n($s['height'])) + $n('height');
        $s['long'] = self::n($s['long']) + $n('long');
        $s['qc'] = self::n($s['qc']) + $n('price_qc');
        $s['repack'] = self::n($s['repack']) + $n('price_repack');
        $s['express'] = self::n($s['express']) + $n('price_express');
        $s['else'] = self::n($s['else']) + $n('price_else');
        $s['craft'] = self::n($s['craft']) + $n('price_craft');
    }

    private static function rows(array $rows, array $children): string
    {
        $s = self::emptySums();
        if ($rows === []) {
            return '<tr><td style="height: 100px;" colspan="14" class="text-center">'.res('no_data').'</td></tr>'.self::hidden($s);
        }
        $mem = [];
        foreach ($rows as $item) {
            $mem[$item['code'].'-'.($item['agent_code'] ?? '')] = '';
        }
        $html = '';
        foreach ($rows as $item) {
            $rs = 'rowspan="'.Php74::str(self::n($item['header_count'] ?? null) + 1).'"';
            $html .= self::trOpen($item, ' id="'.self::h($item['tracking']).'"');
            $js = self::h("go_receive('".Alerts::js($item['id'])."', '".Alerts::js($item['weight'])."')");
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top"><i class="fa fa-check text-success" onclick="'.$js.'"></i></td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::h($item['lot_name']).'</td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::h($item['no']).'</td>';
            $html .= '<td '.$rs.'  style="vertical-align:top">'.self::h($item['tracking']).'</td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::date($item['date_warehouse']).'</td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::date($item['date_outofchina']).'</td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::date($item['date_thai']).'</td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::code($item).'</td>';
            $html .= '<td '.$rs.' class="text-right" style="vertical-align:top">'.Format::getZero(Format::numberformatzero($item['amount'])).'</td>';
            $html .= '<td '.$rs.' class="text-right" style="vertical-align:top">'.Format::getZero(Format::numberformatone(self::n($item['weight']) * self::n($item['amount']))).'</td>';
            $html .= '<td '.$rs.' class="text-right" style="vertical-align:top">'.Format::cubicformat(self::cubic($item)).'</td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::h($item['section_name']).'</td>';

            $key = $item['code'].'-'.($item['agent_code'] ?? '');
            if ($mem[$key] === '') {
                $js = self::h("go_editshelf('".Alerts::js($item['id'])."', '".Alerts::js($item['weight'])."')");
                $html .= '<td '.$rs.' class="text-center" style="vertical-align:top"><i class="md  md-mode-edit text-primary" onclick="'.$js.'"></i></td>';
                $mem[$key] = '1';
            } else {
                $html .= '<td '.$rs.' class="text-center" style="vertical-align:top"></td>';
            }
            self::addSums($s, $item, false);
            $html .= '</tr>';
            $html .= self::subRows($item, $children);
        }

        return $html.self::hidden($s);
    }

    private static function rowsThai(array $rows, array $children): string
    {
        $s = self::emptySums();
        if ($rows === []) {
            return '<tr><td style="height: 100px;" colspan="14" class="text-center">'.res('no_data').'</td></tr>'.self::hidden($s);
        }
        $index = count($rows) + 1;
        $html = '';
        foreach ($rows as $item) {
            $index--;
            $rs = 'rowspan="'.Php74::str(self::n($item['header_count'] ?? null) + 1).'"';
            $html .= self::trOpen($item, '');

            // เดิม: ($weight ± 0.2) เทียบกับ weight_thai (NULL เทียบแบบ bool เหมือนกันทั้ง 7.4/8.4)
            $w = self::n($item['weight']);
            $wt = $item['weight_thai'];
            $color = '';
            if (($w + 0.2) < $wt) {
                $color = 'background-color:#ff8c8c';
            }
            if (($w - 0.2) > $wt) {
                $color = 'background-color:#ff8c8c';
            }
            $js = self::h("go_edittracking('".Alerts::js($item['id'])."')");
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top"><i class="md  md-mode-edit text-primary" onclick="'.$js.'"></i></td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.$index.'</td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::h($item['lot_name']).'</td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::h($item['no']).'</td>';
            $html .= '<td '.$rs.'  style="vertical-align:top">'.self::h($item['tracking']).'</td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::date($item['date_warehouse']).'</td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::date($item['date_outofchina']).'</td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::date($item['date_thai']).'</td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::code($item).'</td>';
            $html .= '<td '.$rs.' class="text-right" style="vertical-align:top">'.Format::getZero(Format::numberformatzero($item['amount'])).'</td>';
            $html .= '<td '.$rs.' class="text-right" style="vertical-align:top;'.$color.'">'.Format::getZero(Format::numberformatone($item['weight'])).'</td>';
            $html .= '<td '.$rs.' class="text-right" style="vertical-align:top;'.$color.'">'.Format::getZero(Format::numberformatone($item['weight_thai'])).'</td>';
            $html .= '<td '.$rs.' class="text-right" style="vertical-align:top">'.Format::cubicformat(self::cubic($item)).'</td>';
            $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::h($item['section_name']).'</td>';
            self::addSums($s, $item, true);
            $html .= '</tr>';
            $html .= self::subRows($item, $children);
        }

        return $html.self::hidden($s);
    }
}
