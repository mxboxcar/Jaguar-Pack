{{-- _balance/index.php เดิม (สิทธิ์ balance ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('balance') !!}</h4>
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
	    <button onclick="opencash_transfer()" type="button" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-reply-all"></i></span>
		    {!! res('withdraw_cash') !!}
	    </button>
	</div>
</div>


<div id="panel-modal" class="cashback_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading">
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 class="panel-title">{!! res('withdraw_cash') !!}</h3>
                </div>
                <div class="panel-body">

                </div>
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="withdrawcash();" type="button">{!! res('ok') !!}</button>
                </div>

            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_balance/grid', 1, '');
</script>


<script>
function opencash_transfer() {
	$(".cashback_form").modal('show');
	$.ajax({
        type: 'POST',
        async: true,
        data : 'wantcash=' + $("#wantcash").val(),
        url: rooturl + '_balance/load_withdraw',
        success: function(data) {
        	$(".cashback_form .panel-body").html(data);
        }
    });
}

function withdrawcash() {
	if($("#bank_no").val() == "" || $("#branch_bank").val() == "" || $("#branch_name").val() == "") {
		ErrorBox('กรุณากรอกข้อมูลบัญชีโอนคืน');
	} else {
		ConfirmBox(function() {
			withdrawcashing();
		});
	}
}
function withdrawcashing() {
	$.ajax({
        type: 'POST',
        async: true,
        data : 'wantcash=' + $("#wantcash").val() + '&bank_no=' + $("#bank_no").val() + '&branch_bank=' + $("#branch_bank").val()
        	+ '&branch_name=' + $("#branch_name").val() + '&paymentpin=' + $("input[name='paymentpin']").val() ,
        url: rooturl + '_balance/save_withdraw',
        success: function(data) {
        	eval(data);
        }
    });
}
</script>
@endsection
