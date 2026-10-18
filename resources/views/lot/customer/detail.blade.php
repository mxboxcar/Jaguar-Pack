{{-- _lot/load_lot_detail.php เดิม (เฉพาะ tracking ของผู้ใช้) --}}
@php
    use App\Domain\Lot\Php74;
    use App\Support\Legacy\Format;
    $n = fn ($v) => Php74::num($v);
@endphp
<table class="lot_table">
    <thead>
        <tr>
            <th class="text-center" style="width: 30px;">{!! res('no') !!}</th>
            <th class="text-center">{!! res('tracking') !!}</th>
            <th class="text-center" style="width:115px;">{!! res('date_warehouse') !!}</th>
            <th class="text-center" style="width:115px;">{!! res('date_outofchina') !!}</th>
            <th class="text-center" style="width:115px;">{!! res('date_thai') !!}</th>
            <th class="text-center" style="width: 70px;">{!! res('bill_no') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('amount') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('weight') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('cubic') !!}</th>
            <th class="text-center" style="width: 70px;">{!! res('type') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('service_price') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('price_kg') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('price_cubic') !!}</th>
        </tr>
    </thead>

    <tbody>
@foreach ($rows as $item)
@include('lot.partials.row_color', ['item' => $item])
<td class="text-center">{{ $item->no }}</td><td class="showdetailbox">{{ $item->tracking }}
				<div class="text-right lot_table_in" style="display:none;position:absolute;background-color:#fff">
						<table class="lot_table ">
						<thead>
							<th class="text-center" style="width:200px;">{!! res('order_number') !!}</th>
							<th class="text-center" style="width:200px;">{!! res('remark') !!}</th>
						</thead>
						<tr>
							<td class="text-left">{{ $item->order }}</td>
							<td class="text-left">{!! nl2br(e((string) $item->remark)) !!}</td>
						</tr>
						</table>
					</div>
			</td><td class="text-center">{!! Format::getthaidate(explode(' ', (string) $item->date_warehouse)[0], true) !!}</td><td class="text-center">{!! Format::getthaidate(explode(' ', (string) $item->date_outofchina)[0], true) !!}</td><td class="text-center">{!! Format::getthaidate(explode(' ', (string) $item->date_thai)[0], true) !!}</td><td class="text-center">@if (Php74::eq($item->payment_id, '0'))-@else<a href="{{ legacy_href() }}export/{{ $item->payment_id }}" target="_blank" >{{ $payPrefix.$item->payment_id }}</a>@endif</td><td class="text-right">{!! Format::numberformatzero($item->amount) !!}</td><td class="text-right">{!! Format::numberformatone($n($item->weight) * $n($item->amount)) !!}</td><td class="text-right showdetailbox">{!! Format::cubicformat((($n($item->width) * $n($item->height) * $n($item->long)) / 1000000) * $n($item->amount)) !!}
				<div class="text-right lot_table_in" style="display:none;position:absolute;background-color:#fff">
						<table class="lot_table ">
						<thead>
							<th class="text-center" style="width:50px;">{!! res('width') !!}</th>
							<th class="text-center" style="width:50px;">{!! res('height') !!}</th>
							<th class="text-center" style="width:50px;">{!! res('long') !!}</th>
						</thead>
						<tr>
							<td class="text-right">{!! Format::numberformatone($item->width) !!}</td>
							<td class="text-right">{!! Format::numberformatone($item->height) !!}</td>
							<td class="text-right">{!! Format::numberformatone($item->long) !!}</td>
						</tr>
						</table>
					</div>
			</td><td class="text-center">{{ $item->type_name }}</td><td class="text-right showdetailbox">{!! Format::numberformat($n($item->price_qc) + $n($item->price_repack) + $n($item->price_express) + $n($item->price_else) + $n($item->price_craft), true, false) !!}
				<div class="text-right lot_table_in" style="display:none;position:absolute;background-color:#fff">
						<table class="lot_table ">
						<thead>
							<th class="text-center" style="width:50px;">{!! res('qc') !!}</th>
							<th class="text-center" style="width:50px;">{!! res('repack') !!}</th>
							<th class="text-center" style="width:50px;">{!! res('express') !!}</th>
							<th class="text-center" style="width:50px;">{!! res('else') !!}</th>
							<th class="text-center" style="width:50px;">{!! res('price_craft') !!}</th>
						</thead>
						<tr>
							<td class="text-right">{!! Format::numberformat($item->price_qc, true, false) !!}</td>
							<td class="text-right">{!! Format::numberformat($item->price_repack, true, false) !!}</td>
							<td class="text-right">{!! Format::numberformat($item->price_express, true, false) !!}</td>
							<td class="text-right">{!! Format::numberformat($item->price_else, true, false) !!}</td>
							<td class="text-right">{!! Format::numberformat($item->price_craft, true, false) !!}</td>
						</tr>
						</table>
					</div>
					</td><td class="text-right">{!! Format::numberformat($item->price_kg, true, false) !!}</td><td class="text-right">{!! Format::numberformat($item->price_cubic, true, false) !!}</td></tr>
@endforeach
	</tbody>
</table>
