{{-- _point/index.php เดิม (PACK = jl: ตรวจช่องว่าง, ส่ง paymentpin, มี withdrawpointing() ให้กล่อง PIN) — สิทธิ์ point ตรวจที่ middleware legacy.perm --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('point') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li class="active">
			{!! res('balance') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">
	    <button onclick="openpoint_transfer()" type="button" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-reply-all"></i></span>
		    {!! res('transfer_point2') !!}
	    </button>
	</div>
</div>



@include('points.partials.transfer_modal')

<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_point/grid', 1, '');
</script>

<script>
function openpoint_transfer() {
	$(".cashback_form").modal('show');
	$.ajax({
        type: 'POST',
        async: true,
        data : 'wantcash=' + $("#wantcash").val(),
        url: rooturl + '_point/load_withdraw',
        success: function(data) {
        	$(".cashback_form .panel-body").html(data);
        }
    });
}

function withdrawpoint() {
	if($("#wantcash").val() == "") {
		ErrorBox('กรุณากรอกจำนวน');
	} else {
		ConfirmBox(function() {
			withdrawpointing();
		});
	}
}
function withdrawpointing() {
	$.ajax({
        type: 'POST',
        async: true,
        data : 'wantcash=' + $("#wantcash").val() + '&paymentpin=' + $("input[name='paymentpin']").val() ,
        url: rooturl + '_point/save_withdraw',
        success: function(data) {
        	eval(data);
        }
    });
}
</script>
@endsection
