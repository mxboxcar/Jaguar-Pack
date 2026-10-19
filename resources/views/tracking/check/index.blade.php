{{-- SR-only: _admin/_check/index.php เดิม (รายการรอเช็ค) — ลิงก์เดิมเปิดแท็บใหม่ (target blank — ตัดออกตามเจ้าของ) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">รายการรอเช็ค</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			รายการรอเช็ค
		</li>
	</ol>
</div>

<div class="col-sm-12">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
		<p class="text-muted font-13 m-b-30"></p>
		<button onclick="on_approvedall()" type="button" style="float: right;margin-top: -45px;" class=" btn btn-xs btn-info ">Clear All Selected</button>
		<table class="table table-striped table-bordered" style="margin-top:40px;">
		    <thead>
		        <tr>
		        	<th class="text-center" style="width: 60px;"><input type="checkbox" class="cbxitem_head" onclick="CheckAll()" /></th>
		            <th class="text-center" style="width: 120px;">{!! res('no_for_grid') !!}</th>
		            <th class="text-center" style="width: 120px;">{!! res('customer_code') !!}</th>
		            <th class="text-center" >{!! res('tracking') !!}</th>
		            <th class="text-center" style="width: 100px;">{!! res('qc') !!}</th>
		            <th class="text-center" style="width: 100px;">{!! res('craft') !!}</th>
		            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
		        </tr>
		    </thead>
		    <tbody>
@foreach ($items as $item)
@if ((string) $item->id !== '0')
<tr><td class="text-center">
		    								<input type="checkbox" name="tracking_id[]" class="cbxitem" value="{{ $item->main_id }}" onclick=""/>
		    							</td><td><a href="{{ legacy_href() }}admin/tracking/{{ $item->id }}" >{{ $prefix.$item->id }}</a></td><td class="text-center">{{ $item->code }}</td><td >{{ $item->tracking }}</td><td class="text-center">{!! (string) $item->is_qc === '0' ? '' : '<i class="fa fa-check"></i>' !!}</td><td class="text-center">{!! (string) $item->is_craft === '0' ? '' : '<i class="fa fa-check"></i>' !!}</td><td class="text-center">
			    					<button onclick="on_approved({{ $item->main_id }})" type="button" class=" btn btn-xs btn-info">Clear</button>
			    				</td></tr>
@endif
@endforeach
		    </tbody>
		</table>
	</div>
</div>

<script>

function CheckAll() {
	if($(".cbxitem_head").prop('checked')) {
		$(".cbxitem").each(function() {
			$(this).prop('checked', true);
		});
	} else {
		$(".cbxitem").each(function() {
			$(this).attr('checked', false);
		});
	}
}

function on_approved(id) {
	ConfirmBox(function () {
		$.ajax({
            type: 'POST',
            async: true,
            data : 'id=' + id,
            url: rooturl + '_admin/_check/save_clear',
            success: function(data) {
            	eval(data);
            }
        });
    });
}


function on_approvedall() {
	var str = '';
    $("input[name='tracking_id[]']").each(function() {
        var t = $(this);
        if(t.prop('checked')) {
            str += ", " + t.val() + " ";
        }
    });

	ConfirmBox(function () {
		$.ajax({
            type: 'POST',
            async: true,
            data : 'id=' + str,
            url: rooturl + '_admin/_check/save_clearall',
            success: function(data) {
            	eval(data);
            }
        });
    });
}


</script>
@endsection
