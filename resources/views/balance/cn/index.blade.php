{{-- _balance_cn/index.php เดิม (สิทธิ์ balance_cn ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('balance_cn') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li class="active">
			{!! res('balance_cn') !!}
		</li>
	</ol>
</div>

<div class="col-sm-12">
    <div class="button-list text-center">
        <a type="button" href="{{ legacy_href() }}taobao" class=" btn btn-inverse  waves-effect waves-light">
            <span class="btn-label"><i class="fa  fa-list-ol"></i></span>
            {!! res('taobao') !!}
        </a>
        <a type="button" href="{{ legacy_href() }}shop_cashback" class=" btn btn-inverse  waves-effect waves-light">
            <span class="btn-label"><i class="fa  fa-undo"></i></span>
            {!! res('shop_cashback') !!}
        </a>
        <a type="button" href="{{ legacy_href() }}balance_cn" class=" btn btn-primary  waves-effect waves-light">
            <span class="btn-label"><i class="fa fa-cny"></i></span>
            {!! res('balance_cn') !!}
        </a>

        <button onclick="opencash_transfer()" type="button" class="btn btn-info waves-effect waves-light">
            <span class="btn-label"><i class="fa fa-reply-all"></i></span>
            {!! res('exchange') !!}
        </button>
    </div>
</div>

<div id="gridAjax" class="col-xs-12"></div>

<div id="panel-modal" class="cashback_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading"> 
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
                    <h3 class="panel-title">{!! res('exchange') !!}</h3> 
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

<script type="text/javascript">
    loadAjaxGrid('_balance_cn/grid', 1, '');
</script>

<script>
function opencash_transfer() {
    $(".cashback_form").modal('show');
    $.ajax({
        type: 'POST',
        async: true,
        data : 'wantcash=' + $("#wantcash").val(),
        url: rooturl + '_balance_cn/load_withdraw',
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
            $.ajax({
                type: 'POST',
                async: true,
                data : 'wantcash=' + $("#wantcash").val(),
                url: rooturl + '_balance_cn/save_withdraw',
                success: function(data) {
                    eval(data);
                }
            });
        });
    }
}
</script>
@endsection
