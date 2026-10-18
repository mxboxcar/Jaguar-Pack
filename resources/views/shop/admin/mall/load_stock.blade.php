{{-- _admin/_mall/load_stock.php เดิม — getPayType() ไม่มีจริง: เดิม fatal ที่แถวแรก (ได้ HTML ถึงช่องสุดท้ายของแถวแรก) --}}
@use('App\Support\Legacy\Format')

<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
            <th class="text-center" style="width: 200px;">{!! res('working_date') !!}</th>
            <th class="text-center">{!! res('detail') !!}</th>
            <th class="text-center">{!! res('color') !!}</th>
            <th class="text-center">{!! res('size') !!}</th>
            <th class="text-center" style="width: 90px;">{!! res('stock_remain') !!}</th>
            <th class="text-center" style="width: 90px;">{!! res('pay_type') !!}</th>
        </tr>
    </thead>
    <tbody>
@if ($item !== null)
	<tr>
		<td>{{ $prefix }}{{ $item->id }}</td>
		<td class="text-center">
@php $exp = explode(' ', (string) $item->create_date); @endphp
@if ((string) $item->create_date === '0000-00-00 00:00:00' || (string) $item->create_date === '')-<br />-@else{!! Format::getthaidate($exp[0], true) !!}<br />{!! Format::gettime($exp[1] ?? '') !!}@endif
        </td>
        <td>{{ $item->detail }}</td>
		<td>{{ $item->color }}</td>
		<td>{{ $item->size }}</td>
		<td class="text-center">{{ $item->amount }}</td>
		<td class="text-center">@else

	</tbody>
</table>
@endif