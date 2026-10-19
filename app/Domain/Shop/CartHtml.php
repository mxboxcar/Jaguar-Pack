<?php

namespace App\Domain\Shop;

use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;

/**
 * HTML ของ _cart/load_cart.php และ _cart/load_full_cart.php เดิม (สร้างด้วยการต่อ string แบบเดิม ให้ได้ HTML เท่าเดิม)
 * ต่างจากเดิม: ค่าจาก DB/ผู้ใช้ทุกตัวผ่าน htmlspecialchars (เดิม echo ดิบ = stored XSS จากชื่อสินค้า/หมายเหตุ/URL)
 */
final class CartHtml
{
    private static function e(mixed $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML401, 'UTF-8');
    }

    private static function n(mixed $v): int|float
    {
        return Php74::num($v);
    }

    /**
     * @param list<object> $items
     * @param list<string> $shops
     */
    public static function loadCart(object $cart, array $items, array $shops, mixed $rate): string
    {
        $e = self::e(...);
        $r = fn (string $k) => Lang::get($k);
        $html = '<div class="row" style="font-size: 14px;">
    <button onclick="selectallscart(0)" class="btn btn-primary btn-xs" style="position: absolute;margin: -15px 0px 0px 6px;">เลือกทั้งหมด</button>
    <button onclick="selectallscart(1)" class="btn btn-primary btn-xs" style="position: absolute;margin: -15px 0px 0px 86px;">ยกเลิกการเลือก</button>
    <button onclick="deleteselectcart()" class="btn btn-danger btn-xs" style="position: absolute;margin: -15px 0px 0px 179px;">ลบที่เลือก</button>';

        if (count($items) === 0) {
            $html .= '<div class="text-center col-xs-12">'.$r('no_data').'</div>';
        } else {
            foreach ($shops as $key) {
                $html .= '
                <input type="hidden" id="cart_ref" value="'.$e($cart->random).'" />';
                $html .= '<div class="col-xs-12" style="margin-top:10px;border-top:1px solid #c5c5c5;padding-top:10px;">
                <button onclick="selectallscart(\''.$e(Alerts::js($key)).'\')" class="btn btn-primary btn-xs" style="">เลือก</button>
                '.$e($key).' </div>';

                foreach ($items as $item) {
                    if ((string) $item->shop_name !== $key) {
                        continue;
                    }
                    $sub = self::n($item->price) * self::n($item->amount);
                    $totalCn = Format::numberformat($sub, false);
                    $totalThai = Format::numberformat($sub * self::n($rate), false);
                    $color = (string) $item->color;
                    $size = (string) $item->size;

                    $html .= '
                <div class="col-xs-12 '.$e($item->shop_name).'" style="margin:10px 0px">
                <input onclick="calselectcart()" type="checkbox" price="'.$totalThai.'" pricecn="'.$totalCn.'" value="'.$e($item->id).'" name="selected_cart[]" style="position: absolute;margin-left: -5px;z-index: 999;" />
                <div class="col-xs-3">
                    <a class="fancybox" href="'.$e($item->main_image).'">
                        <img style="border:1px #ccc solid" src="'.$e($item->main_image).'" class="img-responsive" />
                    </a>
                </div>
                <div class="col-xs-9">';

                    $html .= '<div style="margin:0px 5px;float:left">';
                    if (str_contains($color, '//')) {
                        $html .= 'สี : <a class="fancybox" href="'.$e($color).'">
                                <img src="'.$e($color).'" style="width:20px;height:20px;border:1px #ccc solid" /> <span>'.$e(explode('?r=', $color)[1] ?? '').'</span>
                            </a>';
                    } elseif ($color !== '') {
                        $html .= 'สี : '.$e($color);
                    }
                    $html .= '</div>';

                    $html .= '<div style="margin:0px 5px;float:left">';
                    if (str_contains($size, '//')) {
                        $html .= 'ขนาด : <a class="fancybox" href="'.$e($size).'">
                                <img src="'.$e($size).'" style="width:20px;height:20px;border:1px #ccc solid" />
                            </a>';
                    } elseif ($size !== '') {
                        $html .= 'ขนาด : '.$e($size);
                    }
                    if ($size === '' && $color === '') {
                        $html .= ' - ';
                    }
                    $html .= '</div>';

                    if ((string) $item->upload_file !== '') {
                        $blob = $e(Legacy::hrefBlob((string) $item->upload_file));
                        $html .= '<div style="margin:0px 5px;float:left">';
                        $html .= 'รูปแนบ : <a class="fancybox" href="'.$blob.'">
                                    <img src="'.$blob.'" style="width:20px;height:20px;border:1px #ccc solid" />
                                </a>';
                        $html .= '</div>';
                    }
                    $html .= ' '.$e($item->remark).'';
                    $html .= '<p style="margin:0px 5px;float;left"><a href="'.$e($item->url).'"><i class="fa fa-external-link"></i></a></p>';
                    $html .= '</div>
                <div class="col-xs-9" style="margin-top:5px">
                    <div class="pull-left" style="color: #f40;font-size: 18px;font-weight: bold;">
                        '.Format::numberformat(self::n($item->price) * self::n($rate)).'
                    </div>

                    <button style="height: 28px;width:28px;margin-left:5px" onclick="RemoveCart('.$e($item->id).');return false;" class="pull-right btn btn-xs btn-danger waves-effect waves-light">'.$r('delete').'</button>

                    <div class="pull-right" style="width:100px">
                        <div class="input-group ">
                            <span class="input-group-addon" onclick="switchpricedown_in($(this), '.$e($item->id).');" style="cursor:pointer;padding: 6px 6px;">
                                <i class="fa  fa-minus"></i>
                            </span>
                            <input style="font-weight: normal;text-align:center;height: 28px;" type="text" onblur="switchpriceupdate_in($(this), '.$e($item->id).')" name="search_amount" class="form-control" value="'.$e($item->amount).'">
                            <span class="input-group-addon" onclick="switchpriceup_in($(this), '.$e($item->id).');" style="cursor:pointer;padding: 6px 6px;">
                                <i class="fa  fa-plus"></i>
                            </span>
                        </div>
                    </div>

                </div>

                <div class="col-xs-9" style="margin-top:5px">
                    <div class="pull-left" style="font-size: 13px;font-weight: bold;">
                        '.Format::numberformat($item->price, true, true, true).'
                    </div>
                </div>
                </div>';
                }
            }
        }

        $html .= '<div class="col-xs-12 row" style="border-top: 1px dotted #ccc;margin: 10px 0px 15px;" ></div>';
        $html .= '  <div class="col-xs-12">
				<div class="col-xs-3">'.$r('total').'</div>
				<div class="col-xs-9 text-right currency" id="cartsumcn">'.Format::numberformat('0', true, true, true).'</div>
        	</div>
        	<div class="col-xs-12" style="margin-bottom: 10px;">
				<div  id="cartsumth" class="col-xs-12  currency text-right" style="color: #f40;font-size: 18px;font-weight: bold;">'.Format::numberformat('0').'</div>
        	</div>
        	<div class="col-xs-12 text-center" style="margin-bottom: 10px;">
				<button onclick="cartgostep2()" style="padding: 10px 50px;" type="button" class="btn btn-success waves-effect waves-light">'.$r('checkout_item').'</a>
            </div>
        	';
        $html .= '</div>';

        return $html;
    }

    /** @param list<object> $items */
    public static function fullCart(array $items, mixed $rate): string
    {
        $e = self::e(...);
        $r = fn (string $k) => Lang::get($k);
        $out = '<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th class="text-center">
				<div class="text-center"></div>
			</th>
			<th class="text-center"></th>
			<th class="text-center">'.$r('shop_name').'</th>
			<th class="text-center">'.$r('detail').'</th>
			<th class="text-center">'.$r('wood_protect').'</th>
			<th class="text-center">'.$r('qc').'</th>
			<th class="text-center" style="width: 80px;">'.$r('amount').'</th>
			<th class="text-center">'.$r('by_piece').$r('cn_currency').'</th>
			<th class="text-center">'.$r('total').$r('cn_currency').'</th>
			<th class="text-center">'.$r('total').$r('thai_currency').'</th>
			<th class="text-center">'.$r('manage').'</th>
		</tr>
	</thead>

	<tbody>
		';
        $tAmount = 0;
        $tPerItem = 0;
        $tTotal = 0;
        $no = 0;
        $html = '';

        if (count($items) === 0) {
            $html .= '<tr><td class="text-center" colspan="11" style="line-height: 100px;">'.$r('no_data').'</td></tr>';
        } else {
            $shopName = '';
            foreach ($items as $item) {
                $price = self::n($item->price);
                $amount = self::n($item->amount);
                $tAmount = $tAmount + $amount;
                $tPerItem = $tPerItem + ($price * $amount);
                $tTotal = $tTotal + ($price * $amount * self::n($rate));

                // เดิม if (count($item['product_name']) != 0) — PHP 7.4 count(string) = 1 → ตัดทุกชื่อที่ไม่ใช่ NULL
                $productName = $item->product_name === null ? null : mb_substr((string) $item->product_name, 0, 65).'...';

                $haveSize = false;
                $haveRemark = false;
                $haveColor = false;
                $no++;
                $size = (string) $item->size;
                $color = (string) $item->color;
                $second = '';

                if (str_contains($size, '//')) {
                    $second .= '<a class="fancybox" href="'.$e($size).'">
                                    <img src="'.$e($size).'" style="margin-top: -5px;border:1px solid #a0a0a0;max-width: 13px;max-height: 13px;"> <span>'.$e(explode('?r=', $color)[1] ?? '').'</span>
                                </a>';
                } elseif ($size !== '') {
                    $haveSize = true;
                    $second .= $r('size').':'.$e($size);
                }

                if (str_contains($color, '//')) {
                    $second .= '<a class="fancybox" href="'.$e($color).'">
                                    <img src="'.$e($color).'" style="margin-top: -5px;border:1px solid #a0a0a0;max-width: 13px;max-height: 13px;"> <span>'.$e(explode('?r=', $color)[1] ?? '').'</span>
                                </a>';
                } elseif (str_contains($color, '#') && strlen($color) === 7) {
                    if ($haveSize) {
                        $second .= ' / ';
                    }
                    $second .= '<span style="background-color:'.$e($color).'">----</span>';
                } else {
                    if ($haveSize) {
                        $second .= ' / ';
                    }
                    $haveColor = true;
                    $second .= $e($color);
                }

                if ((string) $item->remark !== '') {
                    $haveRemark = true;
                    if ($haveSize || $haveColor) {
                        $second .= ' / ';
                    }
                    $second .= $e($item->remark);
                }

                if ((string) $item->upload_file !== '') {
                    if ($haveSize || $haveRemark || $haveColor) {
                        $second .= ' / ';
                    }
                    $blob = $e(Legacy::hrefBlob((string) $item->upload_file));
                    $second .= '<a class="fancybox" href="'.$blob.'">
			                        <img src="'.$blob.'" style="margin-top: -5px;border:1px solid #a0a0a0;max-width: 13px;max-height: 13px;">
			                    </a>';
                }

                $newShop = $shopName != $item->shop_name;
                $html .= '<tr>';
                $html .= '<td>'.$no.'.</td>';
                $html .= '<td class="text-center"><a class="fancybox" href="'.$e($item->main_image).'">
                        <img src="'.$e($item->main_image).'" style="max-width: 46px;max-height: 46px;">
                    </a></td>';
                if ($newShop) {
                    $html .= '<td rowspan="'.$e($item->count).'"><div style="width: 150px;overflow: hidden;word-break: break-all;">'.$e($item->shop_name).'</div></td>';
                }
                $html .= '<td ><a href="'.$e($item->url).'">'.$e($productName).'</a>';
                if ($second !== '') {
                    $html .= '<div class="cd-price">'.$second.'</div>';
                }
                $html .= '</td>';
                if ($newShop) {
                    $html .= (int) $item->is_craft === 1
                        ? '<td style="padding-top: 18px;" rowspan="'.$e($item->count).'" class="ver_top text-center"><input type="checkbox" checked class="js-switch" onchange="SaveCartCraft($(this), '.$e($item->id).')" value="0" data-color="#f05050" data-size="small"/></td>'
                        : '<td style="padding-top: 18px;" rowspan="'.$e($item->count).'" class="ver_top text-center"><input type="checkbox"  class="js-switch" value="1" onchange="SaveCartCraft($(this), '.$e($item->id).')" data-color="#f05050" data-size="small"/></td>';
                    $html .= (int) $item->is_qc === 1
                        ? '<td style="padding-top: 18px;" rowspan="'.$e($item->count).'" class="ver_top text-center"><input type="checkbox" checked class="js-switch" onchange="SaveCartQc($(this), '.$e($item->id).')" value="0" data-color="#f05050" data-size="small"/></td>'
                        : '<td style="padding-top: 18px;" rowspan="'.$e($item->count).'" class="ver_top text-center"><input type="checkbox"  class="js-switch" value="1" onchange="SaveCartQc($(this), '.$e($item->id).')" data-color="#f05050" data-size="small"/></td>';
                }
                $html .= '<td class="text-right"><input type="text" class="form-control text-right" onblur="SaveCartAmount($(this),'.$e($item->id).')" value="'.$e($item->amount).'" /></td>';
                $html .= '<td class="text-right">'.Format::numberformat($item->price, true, true, true).'</td>';
                $html .= '<td class="text-right">'.Format::numberformat($price * $amount, true, true, true).'</td>';
                $html .= '<td class="text-right">'.Format::numberformat($amount * ($price * self::n($rate))).'</td>';
                $html .= '<td class="text-center">
    					<a type="button" href="#" onclick="RemoveCart('.$e($item->id).');return false;" class="table-action-btn"><i class="md  md-cancel text-danger"></i></a>
    					</td>';
                $html .= '</tr>';
                $shopName = $item->shop_name;
            }
        }

        return $out.$html."\t</tbody>".'
	<tfoot>
		<th>
		<td colspan="6" class="text-right">'.$r('total').'</td>
		<td class="text-right">'.Php74::str($tAmount).'</td>
		<td class="text-right">'.Format::numberformat($tPerItem, true, true, true).'</td>
		<td class="text-right">'.Format::numberformat($tTotal).'</td>
		<td class="text-right"></td>
		</th>
	</tfoot>
</table>';
    }
}
