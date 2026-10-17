{{-- ตาราง "รายการพัสดุ" ของ _export/form.php ($admin = false) และ _admin/_export/form.php ($admin = true) — แถวสร้างใน ExportBill::trackingRows() --}}
@php
    use App\Domain\Reports\Php74;
    use App\Support\Legacy\Format;
    $d = fn (string $k) => $data->{$k} ?? null;
    $t = $totals;
@endphp
<div class="col-sm-12">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('tracking_list') !!}</b></h4>
		<p class="text-muted font-13 m-b-30">
        </p>
		<table class="lot_table_cus form-group">
		    <thead>
		        <tr>
		            <th class="text-center">{!! res('lot_list').'/'.res('no') !!}</th>
		            <th class="text-center">{!! res('tracking') !!}</th>
		            <th class="text-center">{!! res('order') !!}</th>
		            <th class="text-center">{!! $admin ? res('remark') : res('remark_for_this_order') !!}</th>
		            <th class="text-center">{!! res('amount') !!}</th>
		            <th class="text-center">{!! res('weight') !!}</th>
		            <th class="text-center">{!! res('width') !!}</th>
		            <th class="text-center">{!! res('height') !!}</th>
		            <th class="text-center">{!! res('long') !!}</th>
		            <th class="text-center">{!! res('cubic') !!}</th>
		            <th class="text-center">{!! res('type') !!}</th>
		            <th class="text-center">{!! res('service_price') !!}</th>
		            <th class="text-center">{!! res('price_kg') !!}</th>
		            <th class="text-center">{!! res('price_cubic') !!}</th>
		        </tr>
		    </thead>

		    <tbody>
		{!! $rows !!}
		</tbody>
			<thead>
	            <th class="text-right " colspan="4"></th>
	            <th class="text-right" >{!! Format::numberformatzero($t['amount']) !!}</th>
	            <th class="text-right" >{!! Format::numberformatone($t['weight']) !!}</th>
	            <th class="text-right" >{!! Format::numberformatone($t['width']) !!}</th>
	            <th class="text-right" >{!! Format::numberformatone($t['height']) !!}</th>
	            <th class="text-right" >{!! Format::numberformatone($t['long']) !!}</th>
	            <th class="text-right" >{!! Format::cubicformat($t['cubic']) !!}</th>
	            <th class="text-right" ></th>
	            <th class="text-right" >{!! Format::numberformat($t['service'], true, false) !!}</th>
	            <th class="text-right" >{!! Format::numberformat($t['price_kg'], true, false) !!}</th>
	            <th class="text-right" >{!! Format::numberformat($t['price_cubic'], true, false) !!}</th>
	        </thead>
	        <thead>
	            <th class="text-right" colspan="11">{!! res('total') !!}
	            </th>
	            <th class="text-right" colspan="8">{!! Format::numberformat($t['service'] + $t['price_kg'] + $t['price_cubic'] + $t['price_amount'], true, false) !!}</th>
	        </thead>
	        <thead>
	            <th class="text-right" colspan="11">{!! res('price_thai_storage') !!}
	            </th>
	            <th class="text-right price_thai_storage addcomma" colspan="4">{!! Format::numberformat($d('price_thai_storage'), true, false) !!}</th>
	        </thead>
	        <thead>
	            <th class="text-right" colspan="11">@unless ($admin)<small class="text-danger">({!! res('price_shipping_remark') !!})</small> @endunless{!! res('price_thai_shipping') !!}
	            </th>
	            <th class="text-right price_thai_shipping addcomma" colspan="4">{!! Format::numberformat($d('price_thai_shipping'), true, false) !!}</th>
	        </thead>
	        @if (Php74::eq($d('is_vat'), 1))
	        <thead>
	            <th class="text-right " colspan="11"><span class="text-danger">ภาษีมูลค่าเพิ่ม {{ $d('v') }}%</span>
	            </th>
	            <th class="text-right   " colspan="4"><span class="text-danger addcomma">{!! Format::numberformat($d('vat_before'), true, false) !!}</span></th>
	        </thead>

	        <thead>
	            <th class="text-right " colspan="11"><span class="text-danger">รวม</span>
	            </th>
	            <th class="text-right   " colspan="4"><span class="text-danger addcomma">{!! Format::numberformat($d('vat'), true, false) !!}</span></th>
	        </thead>

	        <thead>
	            <th class="text-right" colspan="11"><span class="text-danger">หัก ณ ที่จ่าย {{ $d('shipv') }}% (จากยอดที่รวม VAT แล้ว)</span>
	            </th>
	            <th class="text-right  " colspan="4"><span class="text-danger addcomma">{!! Format::numberformat($d('shipvat_before'), true, false) !!}</span></th>
	        </thead>

	        <thead>
	            <th class="text-right" colspan="11">{!! res('total_net') !!}</th>
	            <th class="text-right " style="font-size: 20px!important"  colspan="4">{!! Format::numberformat($d('total'), true, false) !!}</th>
	        </thead>

	        @else
        	<thead>
	            <th class="text-right" colspan="11">{!! res('total_net') !!}</th>
	            <th class="text-right total_net addcomma" style="font-size: 20px!important"  colspan="4">{!! Format::numberformat($d('total'), true, false) !!}</th>
	        </thead>

	    	@endif
		</table>
	</div>
</div>
