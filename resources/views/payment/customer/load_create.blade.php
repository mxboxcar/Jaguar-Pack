{{-- _payment/load_create.php เดิม (N+1 เดิม: เลขออเดอร์ + tracking ลูก ทีละแถว → ดึงครั้งเดียวใน controller) --}}
@php
    use App\Domain\Payment\PaymentLookups as P;
    use App\Domain\Payment\Php74;
    use App\Support\Legacy\Format;
@endphp
@php
$foot = function () use ($sum) {
    return '<th class="text-right " colspan="5">'.res('total').'</th>
        <th class="text-right red" >'.Format::numberformatzero($sum['amount'], false).'</th>
        <th class="text-right red" >'.Format::numberformatone($sum['weight'], false).'</th>
        <th class="text-right red" >'.Format::numberformatone($sum['width'], false).'</th>
        <th class="text-right red" >'.Format::numberformatone($sum['height'], false).'</th>
        <th class="text-right red" >'.Format::numberformatone($sum['long'], false).'</th>
        <th class="text-right red" >'.Format::cubicformat($sum['cubic'], false).'</th>
        <th class="text-right" ></th>
        <th class="text-right red" >'.Format::numberformat($sum['service'], true, false).'</th>
        <th class="text-right" colspan="3"></th>
        <th class="text-right red" >'.Format::numberformat($sum['kg'], true, false).'</th>
        <th class="text-right red" >'.Format::numberformat($sum['pcubic'], true, false).'</th>';
};
@endphp
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
            <th class="text-center">{!! res('remark_for_this_order') !!}</th>
            <th class="text-center">{!! res('amount') !!}</th>
            <th class="text-center">{!! res('weight') !!}</th>
            <th class="text-center">{!! res('width') !!}</th>
            <th class="text-center">{!! res('height') !!}</th>
            <th class="text-center">{!! res('long') !!}</th>
            <th class="text-center">{!! res('cubic') !!}</th>
            <th class="text-center">{!! res('type') !!}</th>
            <th class="text-center">{!! res('service_price') !!}
            <th class="text-center">{!! res('date_warehouse') !!}</th>
            <th class="text-center">{!! res('date_outofchina') !!}</th>
            <th class="text-center">{!! res('date_thai') !!}</th>
            <th class="text-center">{!! res('price_kg') !!}</th>
            <th class="text-center">{!! res('price_cubic') !!}</th>
        </tr>
    </thead>

    <thead>
        {!! $foot() !!}
    </thead>

    <tbody>
@forelse ($items as $item)
@php
    $rs = (int) $item->header_count + 1;
    $bp = $orderIds[(int) $item->id] ?? null;
    $bp = $bp === null ? '' : (string) $bp;
    $link = legacy_href().'order/'.$bp.'/'.$item->tracking;
    $d = fn ($v) => Format::getthaidate(explode(' ', (string) $v)[0], true, true);
@endphp
@if (Php74::eq($item->is_craft, 1))<tr style="background-color:#ffdfa5">@else<tr>@endif
@if ($item->date_thai !== '0000-00-00 00:00:00')<td style="vertical-align:top" rowspan="{{ $rs }}" class="text-center ">
						<input type="hidden" class="s_total" value="{{ P::s(P::add($item, ['price_kg', 'price_cubic', 'price_amount', 'price_qc', 'price_repack', 'price_express', 'price_else', 'price_craft'])) }}" />
						<input type="checkbox" name="tracking_id[]" class="cbxitem" value="{{ $item->id }}" onclick="Calculate()"/>
				</td>@else<td rowspan="{{ $rs }}" class="text-center "></td>@endif
<td style="vertical-align:top" rowspan="{{ $rs }}" class="text-center" style="white-space: nowrap;">{{ P::lotLabel($item) }}</td><td style="white-space: nowrap;"><a href="{{ $link }}">{{ $item->tracking }}</a></td><td style="vertical-align:top" rowspan="{{ $rs }}" style="white-space: nowrap;">@if ($bp !== '')<a href="{{ $link }}">{{ $orderPrefix.$bp }}</a>@else-@endif</td><td style="vertical-align:top" rowspan="{{ $rs }}" style="white-space: nowrap;padding: 0px"><input onblur="SaveQuickQuote('{{ $item->id }}', $(this).val())" ref="{{ $item->id }}" class="quote_tracking" style="height: 20px;width: 100%;background-color: transparent;border: 0px solid;" type="text" value="{{ $item->quote }}" /></td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">{!! Format::getZero(Format::numberformatzero($item->amount)) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">{!! Format::getZero(Format::numberformatone(Php74::num($item->weight) * Php74::num($item->amount))) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">{!! Format::getZero(Format::numberformatone($item->width)) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">{!! Format::getZero(Format::numberformatone($item->height)) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">{!! Format::getZero(Format::numberformatone($item->long)) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">{!! Format::cubicformat(P::cubic($item)) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-center">{{ $item->type_name }}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right showdetailbox">@include('payment.partials.service_cell')
					</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-center"><strong style="color:#008000">{!! $d($item->date_warehouse) !!}</strong></td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-center"><strong style="color:#008000">{!! $d($item->date_outofchina) !!}</strong></td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-center"><strong style="color:#008000">{!! $d($item->date_thai) !!}</strong></td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">{!! Format::getBold(Format::getZero(Format::numberformat($item->price_kg, true, false))) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}" class="text-right">{!! Format::getBold(Format::getZero(Format::numberformat($item->price_cubic, true, false))) !!}</td></tr>
@include('payment.partials.children')
@empty
<tr><td style="height: 100px;" colspan="18" class="text-center">{!! res('no_data') !!}</td></tr>
@endforelse
	</tbody>

	<thead>
        {!! $foot() !!}
    </thead>
</table>
