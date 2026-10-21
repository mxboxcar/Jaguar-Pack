{{-- _admin/_export/form.php ของ PACK (สิทธิ์ admin_payment ตรวจที่ route): ปุ่มพิมพ์ใบปะหน้าปุ่มเดียว, คูปองส่วนลด + VAT,
     ราคา/ยอดรวม/กล่อง "รายละเอียดการชำระ" เฉพาะผู้มีสิทธิ์ admin_viewprice_on_payment ($prices) — ค่าจาก DB escape ทั้งหมด (เดิม echo ดิบ) --}}
@extends('legacy.layout')

@php
    use App\Domain\Reports\Php74;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
@endphp

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('payment_list') !!} <small>{{ $prefix.$data->id }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			{!! res('payment_list') !!} <small>{{ $prefix.$data->id }}</small>
		</li>
	</ol>
</div>

<form action="{{ legacy_href() }}_admin/_export/save_approved" method="post">

<input type="hidden" name="id" value="{{ $data->id }}">

<div class="col-sm-5 pull-right">
	<div class="button-list">
		
		<a class="pull-right btn btn-inverse waves-effect waves-light" 
			href="{{ legacy_href() }}_admin/_export/print/?id={{ $data->id }}">
            <span class="btn-label"><i class="fa fa-print"></i></span>
            {!! res('print') !!}
        </a>

        <button onclick="approved()" type="button" class="pull-right btn btn-success waves-effect waves-light">
            <span class="btn-label"><i class="fa fa-check"></i></span>
            {!! res('save_button') !!}
        </button>

        <button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
            <span class="btn-label"><i class="fa fa-save"></i></span>
            {!! res('cancel_this') !!}
        </button>

	    <a type="button" href="{{ legacy_href() }}payment" class="pull-right btn btn-warning waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
		    {!! res('back_to_list') !!}
	    </a>
	</div>
</div>


<div class="col-sm-6">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('address_for_shipping') !!}</b></h4>
		<p class="text-muted font-13 m-b-30">
        </p>
		<div class="form-horizontal" role="form">                                    
	        
	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('name_lastname') !!}</label>
	            <div class="col-md-8 form-control-static">
                	{{ $data->send_name }}
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('address') !!}</label>
	            <div class="col-md-8 form-control-static">
                	{!! nl2br(e((string) $data->send_address)) !!}
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('phone') !!}</label>
	            <div class="col-md-8 form-control-static">
                	{{ $data->send_phone }}
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('shipping_type') !!}</label>
	            <div class="col-md-8 form-control-static">
                	{{ $shipName }}
	            </div>
	        </div>


	        <div class="form-group" style="border-top:1px #ccc dashed"></div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('sender') !!}</label>
	            <div class="col-md-8 form-control-static">
	            	{!! Status::getsender($data->sender_type) !!}
	            </div>
	        </div>

	        @if (Php74::eq($data->sender_type, 1))
<div class="form-group">
        			<div class="col-md-8 col-md-offset-4">
        				<span class="text-danger">{!! res('extra_charge_sender').' '.Format::numberformat($data->sender_charge) !!}</span>
        			</div>
        		</div>
        		<div class="form-group">
		            <label class="col-md-4 control-label">{!! res('sender_name') !!}</label>
		            <div class="col-md-8 form-control-static">
	                	{{ $data->sender_name }}
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label">{!! res('sender_address') !!}</label>
		            <div class="col-md-8 form-control-static">
		            	{{ $data->sender_address }}
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label">{!! res('sender_phone') !!}</label>
		            <div class="col-md-8 form-control-static">
	                	{{ $data->sender_phone }}
		            </div>
		        </div>
	        @endif
	    </div>
	</div>
</div>



	<div class="col-xs-12 col-md-6">
    <div class="card-box">
    <h4 class="m-t-0 header-title"><b>{!! res('export_thing') !!}</b></h4>
    <p class="text-muted font-13 m-b-30">
    </p>
    	<div class="form-horizontal" role="form">
            <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('export_tracking') !!}</label>
	            <div class="col-md-7">
	            	<input type="text" name="export_tracking" class="form-control" value="{{ $data->export_tracking }}" />
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('export_price') !!}</label>
	            <div class="col-md-5">
	            	<div class="input-group">
	            	<input type="text" name="export_price" class="form-control" value="{{ $data->export_price }}" />
	            		<span class="input-group-addon">{!! res('thai_currency') !!}</span>
	            	</div>
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('remark') !!}</label>
	            <div class="col-md-7">
	            	<textarea class="form-control" rows="5" name="export_remark">{{ nl2br((string) $data->export_remark) }}</textarea>
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('approved_proof') !!}</label>
	            <div class="col-md-8 ">
	            	{!! $upload !!}
            	</div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('export_date') !!}</label>
	            <div class="col-md-7">
	            	<input autocomplete="off"  type="text" name="export_date" class="form-control datepicker" value="{{ $exportDate }}"  />
	            </div>
	        </div>
        </div>
    </div>
</div>


@include('reports.partials.export_table', ['admin' => true])


@if ($prices)
	<input type="hidden" name="id" value="{{ $id }}">
	<div class="col-xs-12 col-md-6">
    <div class="card-box">
    <h4 class="m-t-0 header-title"><b>{!! res('payment_thing') !!}</b></h4>
    <p class="text-muted font-13 m-b-30">
    </p>
    	<div class="form-horizontal" role="form">
    		<div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('no_for_grid') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	{{ $prefix.$data->id }}
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('customer_code') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	{{ $data->code }}
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> รหัสคูปองส่วนลด</label>
	            <div class="col-md-7 form-control-static">
	            	{{ $coupon === null ? '-' : $coupon->name }}
	            </div>
	        </div>
            <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('total_have_to_paided') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	<span class=" total_net addcomma currency">{{ $data->total }}</span> {!! res('thai_currency') !!}
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('payment_typing') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	<span class="label label-inverse">{!! res('using_system_cash') !!}</span>
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('status') !!}</label>
	            <div class="col-md-7 form-control-static">
	                {!! Status::getstatus($data->status) !!}
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	{!! nl2br(e((string) $data->submit_remark)) !!}
	            </div>
	        </div>
        </div>
    </div>
</div>
@endif

{!! $balance !!}

</form>



<div id="panel-modal" class="cancel_remark_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading"> 
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
                    <h3 class="panel-title">{!! res('cancel_this') !!}</h3> 
                </div> 
                <div class="panel-body"> 
	                <textarea class="form-control" id="cancel_remark"></textarea>
                </div> 
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="canceling2();" type="button">{!! res('ok') !!}</button>
                </div>
                
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
$(function() {

	$("#upload_file").removeClass("validate[requiredattachment]");

	$('.showdetailbox').on('touchstart mouseover', function (e) {
	    $(this).css({'color':  '#ff0000'});
		$(this).find('.lot_table_in').css('display', 'block');
	});
	$('.showdetailbox').on('touchend mouseout', function (e) {
	    $(this).css({'color': '#797979'});
		$(this).find('.lot_table_in').css('display', 'none');
	});

	initialAddcommaText();
	initialDatepicker();

	$("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	    }
	});
});


function approved() {
    ConfirmBox(function () {
		$("form").eq(0).submit();
	});
}

function canceling() {
	$(".cancel_remark_form").modal('show');
}

function canceling2() {
	ConfirmBox(function () {
		$.ajax({
            type: 'POST',
            async: true,
            data : 'id={{ $id }}&cancel_remark=' + $("#cancel_remark").val(),
            url: rooturl + '_admin/_export/save_delete',
            success: function(data) {
            	eval(data);
            }
        });
	});
}


</script>
@endsection
