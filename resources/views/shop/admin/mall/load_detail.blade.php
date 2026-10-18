{{-- _admin/_mall/load_detail.php เดิม --}}
@use('App\Support\Legacy\Format')

<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th class="text-center">{!! res('color') !!}</th>
            <th class="text-center">{!! res('size') !!}</th>
            <th class="text-center">{!! res('price') !!}</th>
            <th class="text-center">{!! res('point') !!}</th>
            <th class="text-center" style="width: 90px;">{!! res('stock_remain') !!}</th>
            <th class="text-center" style="width: 80px;">{!! res('action') !!}</th>
        </tr>
    </thead>
    <tbody>
@foreach ($items as $item)
	<tr>
		<td>{{ $item->color }}</td>
		<td>{{ $item->size }}</td>
		<td class="text-right">{!! Format::numberformat($item->price) !!}</td>
		<td class="text-right">{!! Format::numberformatpoint($item->point) !!}</td>
		<td class="text-center">{{ $stock[$item->mall_id.':'.$item->id] ?? 0 }}</td>
		<td class="text-center">
			<a type="button" href="#" onclick="on_addinv('{{ $item->id }}');return false;" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
	    	<a type="button" href="#" onclick="on_delinv('{{ $item->id }}');return false;" class="table-action-btn"><i class="md  md-cancel text-danger"></i></a>
    	</td>
	</tr>



@endforeach

	</tbody>
</table>