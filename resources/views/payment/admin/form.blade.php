{{-- _admin/_payment/form.php เดิม (GET admin/payment/{id}) --}}
@extends('legacy.layout')
@php
    use App\Domain\Payment\PaymentLookups as P;
    use App\Domain\Payment\Php74;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $no = $prefix.$data->id;
@endphp

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('payment_list') !!} <small>{{ $no }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			{!! res('payment_list') !!} <small>{{ $no }}</small>
		</li>
	</ol>
</div>

<input type="hidden" name="id" value="{{ $data->id }}">

<div class="col-sm-5 pull-right">
	<div class="button-list">
		
		@if (Php74::eq($data->status, 1))
            <button onclick="approved()" type="button" class="pull-right btn btn-success waves-effect waves-light">
            <span class="btn-label"><i class="fa fa-check"></i></span>
            {!! res('approved') !!}
        </button>
        @endif

        @if (Php74::neq($data->status, 3))
        <button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
            <span class="btn-label"><i class="fa fa-save"></i></span>
            {!! res('cancel_this') !!}
        </button>
        <button onclick="nocanceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
            <span class="btn-label"><i class="fa fa-save"></i></span>
            ยกเลิกรายการไม่คืนพัสดุ
        </button>
        @endif

	    <a type="button" href="{{ legacy_href() }}admin/payment" class="pull-right btn btn-warning waves-effect waves-light">
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
			@if (! $canChangeSender)
	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('name_lastname') !!}</label>
	            <div class="col-md-8 form-control-static">
                	{{ $data->send_name }}
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('address') !!}</label>
	            <div class="col-md-8 form-control-static">
                	{!! nl2br(e($data->send_address)) !!}
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
			@include('payment.partials.sender_static')
			@endif
			@else
			<div class="form-group">
	            <label class="col-md-4 control-label">{!! res('name_lastname') !!}</label>
	            <div class="col-md-8">
					<input class="form-control" type="text" id="a_name" value="{{ $data->send_name }}" />
	            </div>
	        </div>

	        <div class="form-group">
				<label class="col-md-4 control-label">{!! res('address') !!}</label>
				<div class="col-md-8">
					<textarea class="form-control" rows="5" id="a_address">{{ $data->send_address }}</textarea>
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('phone') !!}</label>
				<div class="col-md-8">
					<input class="form-control" type="text" id="a_phone" value="{{ $data->send_phone }}" />
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('shipping_type') !!}</label>
	            <div class="col-md-8 ">
				<select class="form-control  " id="a_type" onchange="getShippingPrice($(this))">
                	<option selected="selected" value="">{!! res('please_select') !!}</option>@foreach ($shippingTypes as $row)@if (Php74::eq($data->shipping_type, $row->id))<option selected="selected" value="{{ $row->id }}">{{ $row->name }}</option>@else<option value="{{ $row->id }}">{{ $row->name }}</option>@endif @endforeach
                	</select>
	            </div>
			</div>


			@if (Php74::eq($data->sender_type, 1))
			<div class="form-group">
        			<div class="col-md-8 col-md-offset-4">
        				<span class="text-danger">{!! res('extra_charge_sender') !!} {!! Format::numberformat($data->sender_charge) !!}</span>
        			</div>
        		</div>
        		<div class="form-group">
		            <label class="col-md-4 control-label">{!! res('sender_name') !!}</label>
					<div class="col-md-8">
						<input class="form-control" type="text" id="b_name" value="{{ $data->sender_name }}" />
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label">{!! res('sender_address') !!}</label>
					<div class="col-md-8">
						<textarea class="form-control" rows="5" id="b_address">{{ $data->sender_address }}</textarea>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label">{!! res('sender_phone') !!}</label>
					<div class="col-md-8 form-control-static">
						<input class="form-control" type="text" id="b_phone" value="{{ $data->sender_phone }}" />
		            </div>
		        </div>
			@endif
			
			<div class="form-group">
	            <label class="col-md-4 control-label"></label>
				<div class="col-md-8">
					<button class="btn btn-primary" type="button" onclick="on_save_newsender()">{!! res('save_new_sender') !!}</button>
	            </div>
	        </div>
			@endif

	        
	    </div>
	</div>
</div>



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
	            	{{ $no }}
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('customer_code') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	{{ $data->code }}
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
	            <div class="col-md-7 form-control-static">{!! Status::getstatus($data->status) !!}@if (Php74::eq($data->status, -1))<div style='margin-top: 10px;' class='text-danger'>* {{ $data->cancel_remark }}</div>@endif

	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	{!! nl2br(e($data->submit_remark)) !!}
	            </div>
	        </div>
        </div>
    </div>
</div>


<div class="col-sm-12">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('tracking_list') !!}</b></h4>
		<p class="text-muted font-13 m-b-30">
        </p>
		<table class="lot_table_cus form-group">
		    <thead>
		        <tr>
		            <th class="text-center">{!! res('lot_list').'/'.res('no') !!}</th>
		            <th class="text-center">{!! res('tracking') !!}</th>
		            <th class="text-center">{!! res('order') !!}</th>
					<th class="text-center">รหัสย่อย</th>
		            <th class="text-center">{!! res('remark') !!}</th>
		            <th class="text-center">{!! res('amount') !!}</th>
		            <th class="text-center">{!! res('weight') !!}</th>
		            <th class="text-center">{!! res('width') !!}</th>
		            <th class="text-center">{!! res('height') !!}</th>
		            <th class="text-center">{!! res('long') !!}</th>
		            <th class="text-center">{!! res('cubic') !!}</th>
		            <th class="text-center">{!! res('type') !!}</th>
		            <th class="text-center">{!! res('service_price') !!}</th>
		            <th class="text-center">{!! res('price_kg') !!}</th>
		            <th class="text-center">{!! res('price_cubic') !!}</th>
		        </tr>
		    </thead>

		    <tbody>
		@include('payment.partials.detail_rows', ['orderBase' => 'admin/order/', 'orderWithTracking' => true, 'note' => 'remark', 'emptyCols' => 11])
		</tbody>
		@include('payment.partials.detail_totals', ['admin' => true])

	        
		</table>
	</div>
</div>
@include('payment.partials.export_box', ['withProof' => false])

@include('payment.partials.balance_list', ['colClass' => 'col-xs-12 col-md-6', 'w1' => 130, 'w2' => 80, 'br' => '<br/>'])

<div id="panel-modal2" class="cancel_remark_form2 modal fade" tabindex="-1" role="dialog" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading"> 
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
                    <h3 class="panel-title">ยกเลิกรายการไม่คืนพัสดุ</h3> 
                </div> 
                <div class="panel-body"> 
	                <textarea class="form-control" id="cancel_remark2"></textarea>
                </div> 
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="nocanceling2();" type="button">{!! res('ok') !!}</button>
                </div>
            </div>
        </div>
    </div>
</div>

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
	$('.showdetailbox').on('touchstart mouseover', function (e) {
	    $(this).css({'color':  '#ff0000'});
		$(this).find('.lot_table_in').css('display', 'block');
	});
	$('.showdetailbox').on('touchend mouseout', function (e) {
	    $(this).css({'color': '#797979'});
		$(this).find('.lot_table_in').css('display', 'none');
	});

	initialAddcommaText();
});

function approved() {
    ConfirmBox(function() {
        $.ajax({
            type: 'POST',
            async: true,
            data : 'id={{ $data->id }}',
            url: rooturl + '_admin/_payment/save_approved',
            success: function(data) {
                eval(data);
            }
        });
    });
}

function canceling() {
	$(".cancel_remark_form").modal('show');
}
function nocanceling() {
	$(".cancel_remark_form2").modal('show');
}
function nocanceling2() {
    ConfirmBox(function() {
        OpenLoading();
        $.ajax({
            type: 'POST',
            async: true,
            data : 'id={{ $id }}&cancel_remark=' + $("#cancel_remark2").val(),
            url: rooturl + '_admin/_payment/save_deletenopush',
            success: function(data) {
                eval(data);
            }
        });
    })
}

function canceling2() {
    ConfirmBox(function() {
        OpenLoading();
        $.ajax({
            type: 'POST',
            async: true,
            data : 'id={{ $id }}&cancel_remark=' + $("#cancel_remark").val(),
            url: rooturl + '_admin/_payment/save_delete',
            success: function(data) {
                eval(data);
            }
        });
    })
}

@if ($canChangeSender)
function on_save_newsender() {
	ConfirmBox(function() {
        OpenLoading();
        $.ajax({
            type: 'POST',
            async: true,
			data : 'id={{ $id }}' + 
				'&a_name=' + encodeURI($("#a_name").val()) +
				'&a_address=' + encodeURI($("#a_address").val()) + 
				'&a_phone=' + encodeURI($("#a_phone").val()) + 
				'&a_type=' + encodeURI($("#a_type").val()) + 

				'&b_name=' + encodeURI($("#b_name").val()) + 
				'&b_address=' + encodeURI($("#b_address").val()) + 
				'&b_phone=' + encodeURI($("#b_phone").val()) 
				,
            url: rooturl + '_admin/_payment/save_new_sender',
            success: function(data) {
                eval(data);
            }
        });
    })
}
function getShippingPrice(ele) {
	$.ajax({
        type: 'POST',
        async: true,
        data : 'id=' + ele.val() + '&payment_ref={{ $id }}&sender=' + $("#a_type").val(),
        url: rooturl + '_admin/_payment/load_shipping_price',
        success: function(data) {
        	data = $.parseJSON(data);
        	if(data == false) {
        		$(".price_thai_storage").text(eval(0));
	        	$(".price_thai_shipping").text(eval(0));
	        	
				$(".hid_price_thai_storage").val(eval(0));
	        	$(".hid_price_thai_shipping").val(eval(0));
        	} else {
        		$(".price_thai_storage").text(eval(data.price_thai_storage));
	        	$(".price_thai_shipping").text(eval(data.price_thai_shipping));
	        	
				$(".hid_price_thai_storage").val(eval(data.price_thai_storage));
	        	$(".hid_price_thai_shipping").val(eval(data.price_thai_shipping));
        	}
        	
        	CalculateTotal();
        }
    });
}

function CalculateTotal() {
	var service = $(".hid_service").val();
	var price_thai_shipping = $(".hid_price_thai_shipping").val();
	var price_thai_storage = $(".hid_price_thai_storage").val();

	$(".total_net").text(eval(service) + eval(price_thai_storage) + eval(price_thai_shipping));
	initialAddcommaText();
}
@endif


</script>


<script>
@php
    // SR: console.log ราคาต่อแถวเทียบเรท user_type_detail ของลูกค้า (ค่าในสตริง JS escape — เดิมต่อดิบ)
    $jq = fn ($v) => str_replace(['\\', '"', "'", "\r", "\n", '</'], ['\\\\', '\\"', "\\'", '\\r', '\\n', '<\\/'], (string) $v);
    $count = 0;
@endphp
@foreach ($items as $item)
@php
    $count++;
    $rate = $rates[(string) $item->product_type_id] ?? null;
    $w = (Php74::num($item->weight) * Php74::num($item->amount)) * Php74::num($rate->kg ?? null);
    $c = P::cubic($item) * Php74::num($rate->cubic ?? null);
@endphp
console.log("{!! $jq($item->tracking) !!}");
console.log('round:{!! $jq($count.':kg  '.$item->price_kg) !!}-{!! $jq(P::s($w)) !!}');
console.log('round:{!! $jq($count.':cb  '.$item->price_cubic) !!}-{!! $jq(P::s($c)) !!}');
console.log("--");
@endforeach
</script>@endsection
