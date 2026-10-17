{{-- ตารางเรตราคา (รถ/เรือ) ของ _admin/_user/form.php และ _admin/_user_type/form.php เดิม — $m = PricingMatrix::forUserType(), $amount = แถวราคาต่อชิ้น (เฉพาะฟอร์มกลุ่มราคา) --}}
<table class="table table-striped table-bordered">
	<thead>
		<th class="text-center" style="width:100px;">{!! res('car') !!}</th>
		@foreach ($m['head_car'] as $item)<th>{{ $item->name }}</th>@endforeach

	</thead>
	<thead>
		<tr>
			<td>{!! res('kg') !!}</td>
			@foreach ($m['car'] as $item)<td><input class="form-control " name="product_type_kg_id_{{ $item->id }}" type="text" value="{{ $item->kg }}" /></td>@endforeach

		</tr>
		<tr>
			<td>{!! res('cubic') !!}</td>
			@foreach ($m['car'] as $item)<td><input class="form-control " name="product_type_cubic_id_{{ $item->id }}" type="text" value="{{ $item->cubic }}" /></td>@endforeach

		</tr>
@if ($amount)
		<tr>
			<td>{!! res('amount') !!}</td>
			@foreach ($m['car'] as $item)<td><input class="form-control" name="product_type_amount_id_{{ $item->id }}" type="text" value="{{ $item->amount }}" /></td>@endforeach

		</tr>
@endif
	</thead>
</table>
<table class="table table-striped table-bordered">
	<thead>
		<th class="text-center" style="width:100px;">{!! res('ship') !!}</th>
		@foreach ($m['head_ship'] as $item)<th >{{ $item->name }}</th>@endforeach

	</thead>
	<thead>
		<tr>
			<td>{!! res('kg') !!}</td>
			@foreach ($m['ship'] as $item)<td><input class="form-control" name="product_type_kg_id_{{ $item->id }}" type="text" value="{{ $item->kg }}" /></td>@endforeach

		</tr>
		<tr>
			<td>{!! res('cubic') !!}</td>
			@foreach ($m['ship'] as $item)<td><input class="form-control" name="product_type_cubic_id_{{ $item->id }}" type="text" value="{{ $item->cubic }}" /></td>@endforeach

		</tr>
@if ($amount)
		<tr>
			<td>{!! res('amount') !!}</td>
			@foreach ($m['ship'] as $item)<td><input class="form-control" name="product_type_amount_id_{{ $item->id }}" type="text" value="{{ $item->amount }}" /></td>@endforeach

		</tr>
@endif
	</thead>
</table>
