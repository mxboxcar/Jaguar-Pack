{{-- ท้ายตารางของ _payment/form.php ($admin = false) และ _admin/_payment/form.php ($admin = true)
     PACK: แถว Discount (ลูกค้า), ส่วนลดท้ายบิล (คูปองที่ผูกกับบิล), VAT; แอดมินเห็นราคา/ยอดเฉพาะผู้มีสิทธิ์ admin_viewprice_on_payment ($viewPrice) --}}
@php
    use App\Domain\Payment\Php74;
    use App\Support\Legacy\Format;
    $t = $top;
    $sumAll = $t['service'] + $t['kg'] + $t['pcubic'] + $t['pamount'];
    $showPrice = ! $admin || $viewPrice;
@endphp
			<thead>
	            <th class="text-right " colspan="4">@if ($admin)
					<input type="hidden" class="hid_service" value="{!! Format::numberformat($sumAll, false, false) !!}" />
					<input type="hidden" class="hid_price_thai_shipping" value="0" />
					<input type="hidden" class="hid_price_thai_storage" value="0" />
				@endif</th>
	            <th class="text-right" >{!! Format::numberformatzero($t['amount']) !!}</th>
	            <th class="text-right" >{!! Format::numberformatone($t['weight']) !!}</th>
	            <th class="text-right" >{!! Format::numberformatone($t['width']) !!}</th>
	            <th class="text-right" >{!! Format::numberformatone($t['height']) !!}</th>
	            <th class="text-right" >{!! Format::numberformatone($t['long']) !!}</th>
	            <th class="text-right" >{!! Format::cubicformat($t['cubic']) !!}</th>
	            <th class="text-right" ></th>
	            <th class="text-right" >{!! Format::numberformat($t['service'], true, false) !!}</th>
	            @if ($showPrice)
	            <th class="text-right" >{!! Format::numberformat($t['kg'], true, false) !!}</th>
	            <th class="text-right" >{!! Format::numberformat($t['pcubic'], true, false) !!}</th>
	            @endif
	        </thead>
	        @if ($showPrice)
	        <thead>
	            <th class="text-right" colspan="11">{!! res('total') !!}
	            </th>
	            <th class="text-right" colspan="8">{!! Format::numberformat($sumAll, true, false) !!}</th>
	        </thead>
	        <thead>
	            <th class="text-right" colspan="11">{!! res('price_thai_storage') !!}
	            </th>
	            <th class="text-right price_thai_storage addcomma" colspan="4">{!! Format::numberformat($data->price_thai_storage, true, false) !!}</th>
	        </thead>
	        <thead>
	            <th class="text-right" colspan="11">@if (! $admin)<small class="text-danger">({!! res('price_shipping_remark') !!})</small> @endif{!! res('price_thai_shipping') !!}
	            </th>
	            <th class="text-right price_thai_shipping addcomma" colspan="4">{!! Format::numberformat($data->price_thai_shipping, true, false) !!}</th>
	        </thead>
	        @if (! $admin)
	        <thead>
	            <th class="text-right" colspan="11">Discount
	            </th>
	            <th class="text-right discount addcomma" colspan="3">{!! Format::numberformat($data->discount, true, false) !!}</th>
	        </thead>
	        @endif
	        @if ($coupon !== null)
	        	<thead>
		            <th class="text-right " colspan="11"><span class="text-danger">ส่วนลดท้ายบิล</span>
		            </th>
		            <th class="text-right   " colspan="4"><span class="text-danger addcomma">{!! Format::numberformat($coupon->price, true, false) !!}</span></th>
		        </thead>
	        @endif
	        @if (Php74::eq($data->is_vat, 1))
	        <thead>
	            <th class="text-right " colspan="11"><span class="text-danger">ภาษีมูลค่าเพิ่ม {{ $data->v }}%</span>
	            </th>
	            <th class="text-right   " colspan="4"><span class="text-danger addcomma">{!! Format::numberformat($data->vat_before, true, false) !!}</span></th>
	        </thead>

	        <thead>
	            <th class="text-right" colspan="11">{!! res('total_net') !!}</th>
	            <th class="text-right " style="font-size: 20px!important"  colspan="4">{!! Format::numberformat($data->total, true, false) !!}</th>
	        </thead>
	        @else
        	<thead>
	            <th class="text-right" colspan="11">{!! res('total_net') !!}</th>
	            <th class="text-right total_net addcomma" style="font-size: 20px!important"  colspan="4">{!! Format::numberformat($data->total, true, false) !!}</th>
	        </thead>
	        @endif
	        @endif
