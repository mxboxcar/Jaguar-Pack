{{-- _payment/load_create_sum.php เดิม (แถวเดิมไม่มี <tr> เปิด) --}}
@php
    use App\Domain\Payment\Php74;
    use App\Support\Legacy\Format;
@endphp
<h4 class="m-t-0 header-title"><b>{!! res('dashboard_sum') !!}</b></h4>
<table class="lot_table form-group" >
    <thead>
        <tr>
            
            <th class="text-center">{!! res('lot_list') !!}</th>
            <th class="text-center">{!! res('weight') !!}</th>
            <th class="text-center">{!! res('cubic') !!}</th>
            <th class="text-center">{!! res('price_kg') !!}</th>
            <th class="text-center">{!! res('price_cubic') !!}</th>
        </tr>
    </thead>

    <tbody>
@forelse ($items as $item)
<td style="vertical-align:top" class="text-center">{!! Php74::eq($item->lot_type, 0) ? res('car') : res('ship') !!} {{ $item->lot_name }}</td><td style="vertical-align:top" class="text-right">{{ $item->weight }}</td><td style="vertical-align:top" class="text-right">{!! Format::cubicformat($item->cubic) !!}</td><td style="vertical-align:top" class="text-right">{!! Format::getBold(Format::getZero(Format::numberformat($item->price_kg, true, false))) !!}</td><td style="vertical-align:top" class="text-right">{!! Format::getBold(Format::getZero(Format::numberformat($item->price_cubic, true, false))) !!}</td></tr>
@empty
<tr><td style="height: 100px;" colspan="5" class="text-center">{!! res('no_data') !!}</td></tr>
@endforelse
	</tbody>

	
</table>
