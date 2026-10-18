{{-- ส่วน HTML ท้าย _admin/_warehouse/save_lot_detail.php เดิม (หน้าเว็บ reload ทิ้งผลนี้) --}}



<div style="margin-top:10px;">
	<input type="hidden" id="ids" values="{{ $names }}" />
	{!! res('you_have_choose') !!} : {{ $names }}
	<button style="margin-left: 10px;" class="btn-danger" type="button" onclick="go_cancel_warehouse_calculate()">{!! res('cancel_warehouse_calculate') !!}</button>
</div>

<table class="lot_table" style="margin-top:10px;clear:both;float: left;">
    <thead>
        <tr>
        	<th class="text-center" style="width: 70px;">{!! res('lot') !!}</th>
            <th class="text-center" style="width: 70px;">{!! res('no') !!}</th>
            <th class="text-center">{!! res('tracking') !!}</th>
            <th class="text-center" style="width: 73px;">{!! res('date_warehouse') !!}</th>
            <th class="text-center" style="width: 73px;">{!! res('date_outofchina') !!}</th>
            <th class="text-center" style="width: 73px;">{!! res('date_thai') !!}</th>
            <th class="text-center" style="width: 70px;">{!! res('customer_code') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('amount') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('weight') !!}</th>
            <th class="text-center" style="width: 100px;">{!! res('shelf_manage') !!}</th>
        </tr>
    </thead>

    <tbody>
{!! $body !!}
	</tbody>

</table>
