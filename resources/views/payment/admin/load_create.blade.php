{{-- _admin/_payment/load_create.php เดิม (N+1 เดิม: เรท 2 query + เลขออเดอร์/tracking ลูกทีละแถว → ดึงครั้งเดียวใน controller) --}}
@php
    use App\Domain\Payment\PaymentLookups as P;
    use App\Domain\Payment\Php74;
    use App\Support\Legacy\Format;
    $foot = '<th class="text-right " colspan="5">'.res('total').'</th>
        <th class="text-right red" >'.Format::numberformatzero($sum['amount'], false).'</th>
        <th class="text-right red" >'.Format::numberformatone($sum['weight'], false).'</th>
        <th class="text-right red" >'.Format::numberformatone($sum['width'], false).'</th>
        <th class="text-right red" >'.Format::numberformatone($sum['height'], false).'</th>
        <th class="text-right red" >'.Format::numberformatone($sum['long'], false).'</th>
		<th class="text-right red" >'.Format::cubicformat($sum['cubic'], false).'</th>
        <th class="text-right" colspan="9"></th>';
    $inp = fn (string $class, mixed $v, int $w, string $extra = '') => '<input '.$extra.'class="'.$class.'" style="height: 20px;width: '.$w.'px;text-align:right;background-color: transparent;border: 0px solid;" type="text" value="'.e((string) $v).'" />';
@endphp

<div class="col-xs-12">
@if ($rates !== null)
@foreach (['car' => 'car', 'ship' => 'ship'] as $key => $label)

	<div class="col-xs-3"><table class="table table-striped table-bordered"{!! $key === 'ship' ? ' ' : '' !!}>
				<thead>
					<tr>
						<th class="text-center" style="width:70px">{!! res($label) !!}</th>
						<th class="text-center">{!! res('kg') !!}</th>
						<th class="text-center">{!! res('cubic') !!}</th>
					</tr>
				</thead>
				<tbody>@foreach ($rates[$key] as $row)<tr>
					<td class="text-center">{{ $row->name }}</td>
					<td class="text-right">{!! Format::numberformat($row->kg, true, true) !!}</td>
					<td class="text-right">{!! Format::numberformat($row->cubic, true, false) !!}</td>
				</tr>@endforeach</tbody>
			</table>
	</div>
@endforeach
@endif
</div>


<table class="lot_table form-group" >
    <thead>
        <tr>
            <th class="text-center "> 
            	<div class="text-center">
            		<input type="checkbox" class="cbxitem_head" onclick="CheckAll()" />
            	</div>
            </th>
            <th class="text-center">{!! res('lot_list').'/'.res('no') !!}</th>
            <th class="text-center">{!! res('tracking') !!}</th>
            <th class="text-center">{!! res('order') !!}</th>
            <th class="text-center">{!! res('customer_code') !!}</th>
            <th class="text-center">{!! res('amount') !!}</th>
            <th class="text-center">{!! res('weight') !!}</th>
            <th class="text-center">{!! res('width') !!}</th>
            <th class="text-center">{!! res('height') !!}</th>
            <th class="text-center">{!! res('long') !!}</th>
            <th class="text-center">{!! res('cubic') !!}</th>
            <th class="text-center">{!! res('type') !!}</th>
            <th class="text-center">{!! res('date_thai') !!}</th>
			<th class="text-center" style="width:50px;">{!! res('qc') !!}</th>
			<th class="text-center" style="width:50px;">{!! res('repack') !!}</th>
			<th class="text-center" style="width:50px;">{!! res('express') !!}</th>
			<th class="text-center" style="width:50px;">{!! res('else') !!}</th>
			<th class="text-center" style="width:50px;">{!! res('price_craft') !!}</th>
            <th class="text-center">{!! res('price_kg') !!}</th>
            <th class="text-center">{!! res('price_cubic') !!}</th>
        </tr>
    </thead>

    <thead>
        {!! $foot !!}
    </thead>

    <tbody>
@forelse ($items as $item)
@php
    $rs = (int) $item->header_count + 1;
    $bp = $orderIds[(int) $item->id] ?? null;
    $bp = $bp === null ? '' : (string) $bp;
    $link = legacy_href().'order/'.$bp.'/'.$item->tracking;
@endphp
@if (Php74::eq($item->is_craft, 1))<tr style="background-color:#ffdfa5">@else<tr>@endif
@if ($item->date_thai !== '0000-00-00 00:00:00')<td style="vertical-align:top" rowspan="{{ $rs }}" class="text-center ">
						<input type="hidden" class="s_total" value="{{ P::s(P::add($item, ['price_kg', 'price_cubic', 'price_amount', 'price_qc', 'price_repack', 'price_express', 'price_else', 'price_craft'])) }}" />
						<input type="checkbox" name="tracking_id[]" class="cbxitem" value="{{ $item->id }}" onclick="Calculate()"/>
				</td>@else<td rowspan="{{ $rs }}" class="text-center "></td>@endif
<td style="vertical-align:top" rowspan="{{ $rs }}" class="text-center" style="white-space: nowrap;">{{ P::lotLabel($item, false) }}</td><td style="white-space: nowrap;"><a href="{{ $link }}" target="_blank">{{ $item->tracking }}</a></td><td style="vertical-align:top" rowspan="{{ $rs }}" style="white-space: nowrap;">@if ($bp !== '')<a target="_blank" href="{{ $link }}">{{ $orderPrefix.$bp }}</a>@else-@endif</td><td style="vertical-align:top" rowspan="{{ $rs }}" style="white-space: nowrap;padding: 0px">{{ $item->code }}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">{!! Format::getZero(Format::numberformatzero($item->amount)) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">{!! Format::getZero(Format::numberformatone(Php74::num($item->weight) * Php74::num($item->amount))) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">{!! Format::getZero(Format::numberformatone($item->width)) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">{!! Format::getZero(Format::numberformatone($item->height)) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">{!! Format::getZero(Format::numberformatone($item->long)) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">{!! Format::cubicformat(P::cubic($item)) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-center">{{ $item->type_name }}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-center"><strong style="color:#008000">{!! Format::getthaidate(explode(' ', (string) $item->date_thai)[0], true, true) !!}</strong></td><td class="text-right" style="vertical-align:top">
						{!! $inp('hid_price_qc', $item->price_qc, 60) !!}
					</td>
					<td class="text-right" style="vertical-align:top">
						{!! $inp('hid_price_repack', $item->price_repack, 60) !!}
					</td>
					<td class="text-right" style="vertical-align:top">
						{!! $inp('hid_price_express', $item->price_express, 60) !!}
					</td>
					<td class="text-right" style="vertical-align:top">
						{!! $inp('hid_price_else', $item->price_else, 60) !!}
					</td>
					<td class="text-right" style="vertical-align:top">
						{!! $inp('hid_price_craft', $item->price_craft, 60) !!}
					</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">
                        {!! $inp('hid_price_kg', $item->price_kg, 90) !!}
                    </td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">
                        {!! $inp('hid_price_cubic', $item->price_cubic, 90, ' ') !!}
                    </td></tr>
@include('payment.partials.children')
@empty
<tr><td style="height: 100px;" colspan="20" class="text-center">{!! res('no_data') !!}</td></tr>
@endforelse
	</tbody>

	<thead>
		{!! $foot !!}
	</thead>
</table>
<script>
$(function() {
	$(".hid_price_kg").keyup (function (e) {
        Calculate();
    });

    $(".hid_price_cubic").keyup (function (e) {
        Calculate();
    });

	$(".hid_price_qc").keyup (function (e) {
        Calculate();
    });

	$(".hid_price_repack").keyup (function (e) {
        Calculate();
    });

	$(".hid_price_express").keyup (function (e) {
        Calculate();
    });

	$(".hid_price_else").keyup (function (e) {
        Calculate();
    });

	$(".hid_price_craft").keyup (function (e) {
        Calculate();
    });

});
</script>
