{{-- SR _admin/_lot/load_unpaid.php เดิม: รายการลูกค้าค้างจ่ายในล็อต --}}

<div class="card-box col-xs-3">
	<h4 class="m-t-0 header-title"><b>รายการลูกค้าค้างจ่าย</b> </h4>
	<p class="text-muted font-13 m-b-30">
    </p>
	<table class="lot_table" style="margin-top:20px;clear:both;float: left;">
		<thead>
			<th class="text-center">ลำดับ</th>
			<th class="text-center">รหัสลูกค้า</th>
			<th class="text-center">จำนวนพัสดุที่ค้าง</th>
		</thead>

		@foreach ($rows as $i => $row)<tr><td class="text-center">{{ $i + 1 }}.</td><td class="text-center">{{ $row->code }}</td><td class="text-center">{{ $row->counts }}</td></tr>@endforeach
	</table>
</div>
