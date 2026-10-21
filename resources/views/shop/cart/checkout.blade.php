{{-- _cart/checkout.php เดิม (page=checkout) — PACK = แบบ jl (กล่องหมายเหตุว่าง, ไม่มีบริการถ่ายรูป) + ข้อความ "ไลน์ @packcargo" --}}
@use('App\Support\Legacy\Alerts')
@extends('legacy.layout')

@section('content')
@if ($tooMany !== null)
<script>ErrorBox('สามารถเลือกได้เพียง 50 รายการ ขณะนี้คุณได้เลือกเกินมา {{ 50 - $tooMany }} รายการ', 'window.location.href = \'{{ Alerts::js(legacy_href()) }}?page=checkout_product\'');</script>
@else

<div class="col-sm-7">
	<h4 class="page-title">{!! res('cart') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li class="active">
			{!! res('cart') !!}
		</li>
	</ol>
</div>

<div class="col-xs-12 col-md-6">
	<div class="card-box">
	<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
	<p class="text-muted font-13 m-b-30">
    </p>
    <div class="form-horizontal" role="form">    
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('today_rate_order') !!}</label>
            <div class="col-md-4 form-control-static">
                    {!! res('cn_currency') !!}1 <i class='fa fa-arrows-h'></i> {!! \App\Support\Legacy\Format::numberformat(\App\Support\Legacy\Lookup::getSetting('rate_order')) !!}
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('remark_for_this_order') !!}</label>
            <div class="col-md-7 ">
                    <input type="text" class="form-control" id="quote" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('shipping_type') !!}</label>
            <div class="col-md-3 ">
                <select name="role" class="form-control " id="ship">
                    <option value="0">{!! res('car') !!}</option>
                    <option value="1">{!! res('ship') !!}</option>
                </select>
            </div>
        </div>





    </div>
    </div>
</div>

<div class="col-xs-12 col-md-6">
    <div class="card-box">
    <h4 class="m-t-0 header-title"><b><span class="text-danger">{!! res('remark') !!}</span></b></h4>
    <p class="text-muted font-13 m-b-30">
    </p>
        <div class="text-center">
            
        </div>
    </div>
</div>


<div class=" col-xs-12">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('cart_detail') !!}</b></h4>
		<p class="text-muted font-13 m-b-30">
	    </p>
	    <div id="fullcart"></div>
	</div>
</div>

<div class="col-xs-6 col-xs-offset-3 text-center">
	<button type="button" onclick="submitting('{{ Alerts::js($cartId) }}', '{{ Alerts::js($ids) }}')" class="btn btn-default form-control">{!! res('confirm_order_last') !!}</button>
</div>


<div id="panel-modal" class="remark modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading"> 
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
                    <h3 class="panel-title">คำเตือน</h3> 
                </div> 
                <div class="panel-body"> 
                    มีค่าใช้จ่าย ติดต่อสอบถามราคาเงื่อนไขได้ที่ ไลน์ @packcargo
                </div> 
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                </div>
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script>

$(function() {
	LoadFullCart();
})

function SaveCartAmount(ele, id) {
	$.ajax({
        type: 'POST',
        async: true,
        data : 'id=' + id + '&value=' + ele.val(),
        url: rooturl + '_cart/save_amount',
        success: function(data) {
          	LoadFullCart();
        }
    });
}
function SaveCartCraft(ele, id) {
    if(ele.val() == 1) {
        $(".remark").modal('show');
    }
    
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id=' + id + '&value=' + ele.val(),
        url: rooturl + '_cart/save_craft',
        success: function(data) {
            LoadFullCart();
        }
    });
}
function SaveCartQc(ele, id) {
    if(ele.val() == 1) {
        $(".remark").modal('show');
    }
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id=' + id + '&value=' + ele.val(),
        url: rooturl + '_cart/save_qc',
        success: function(data) {
            LoadFullCart();
        }
    });
}
function submitting(id, ids) {
    ConfirmBox(function () {
        $.ajax({
            type: 'POST',
            async: true,
            data : 'id=' + id + '&quote=' + $("#quote").val() + '&ship=' + $("#ship").val() + '&ids=' + ids,
            url: rooturl + '_cart/save_checkout',
            success: function(data) {
                eval(data);
            }
        });
    });
}
function LoadFullCart() {
	$.ajax({
        type: 'POST',
        async: true,
        data : 'ids={!! Alerts::js($ids) !!}',
        url: rooturl + '_cart/load_full_cart',
        success: function(data) {
          	$("#fullcart").html(data);
          	CloseLoading();
            Switcher();
        }
    });
}

</script>
@endif
@endsection
