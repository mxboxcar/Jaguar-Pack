{{-- _admin/_payment/create_form.php เดิม (GET admin/payment/create/{user_id}/{random}) --}}
@extends('legacy.layout')
@php
    use App\Domain\Payment\PaymentLookups as P;
    use App\Domain\Payment\Php74;
    use App\Support\Legacy\Format;
@endphp

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('payment_new_customer') !!} {{ $user?->code }}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li class="active">
			{!! res('payment_new_customer') !!} {{ $user?->code }}
		</li>
	</ol>
</div>

<form action="{{ legacy_href() }}_admin/_payment/save_submit_payment" method="post">
<input type="hidden" name="id" value="{{ $user?->id }}">
<input type="hidden" name="ref_id" value="{{ $random }}">
<input type="hidden" name="user_id" value="{{ $user?->id }}">

<div class="col-sm-5 pull-right">
	<div class="button-list">
		<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-save"></i></span>
		    {!! res('save_button') !!}
	    </button>

	    <a type="button" href="{{ legacy_href() }}admin/payment/create/{{ $userId }}" class="pull-right btn btn-warning waves-effect waves-light">
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
	            <div class="col-md-8">
                	<input type="text" name="name" class="form-control  validate[required]" value="{{ $user?->name }}" >
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('address') !!}</label>
	            <div class="col-md-8">
	            	<textarea class="form-control" rows="5" name="address">{{ $user?->address }}</textarea>
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('phone') !!}</label>
	            <div class="col-md-8">
                	<input type="text" name="phone" class="form-control  validate[required]" value="{{ $user?->phone }}" >
	            </div>
	        </div>

	        

	        <div class="form-group else_shipping" style="display:block">
	            <label class="col-md-4 control-label">
	            	{!! res('shipping_type') !!}
	            </label>
	            <div class="col-md-8">
                	<select class="form-control  " name="shipping_type" onchange="getShippingPrice($(this))">
                	<option selected="selected" value="">{!! res('please_select') !!}</option>@foreach ($shippingTypes as $row)<option value="{{ $row->id }}">{{ $row->name }}</option>@endforeach
                	</select>
	            </div>
	        </div>

	        <div class="form-group" style="border-top:1px #ccc dashed;margin-top:10px;margin-bottom:10px;" ></div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('sender') !!}</label>
	            <div class="col-md-5">
	            	<select class="form-control  " name="sender_type" onchange="getSenderType($(this))">
	            		<option selected="selected" value="">{!! res('please_select') !!}</option>
	            		@if (! $hideOwnSender)
	            		<option value="0">{{ config('legacy.project_name') }}</option>
	            		@endif
	            		<option value="1">{!! res('write_by_user') !!}</option>
	            	</select>
	            </div>
	            
	        </div>

	        <div class="custom_sender" style="display:none">
	        	<div class="form-group">
        			<div class="col-md-8 col-md-offset-4">
        				<span class="text-danger">{!! res('extra_charge_sender') !!} {!! Format::numberformat($changeSender) !!}</span>
        			</div>
        		</div>
	            <div class="form-group">
		            <label class="col-md-4 control-label">{!! res('sender_name') !!}</label>
		            <div class="col-md-8">
	                	<input type="text" name="sender_name" class="form-control" value="" >
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label">{!! res('sender_address') !!}</label>
		            <div class="col-md-8">
		            	<textarea class="form-control" rows="5" name="sender_address"></textarea>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label">{!! res('sender_phone') !!}</label>
		            <div class="col-md-8">
	                	<input type="text" name="sender_phone" class="form-control" value="" >
		            </div>
		        </div>
	        </div>

	    </div>
	</div>
</div>



	<input type="hidden" name="id" value="{{ $random }}">

	<div class="col-xs-12 col-md-6">
    <div class="card-box">
    <h4 class="m-t-0 header-title"><b>{!! res('payment_thing') !!}</b></h4>
    <p class="text-muted font-13 m-b-30">
    </p>
        <div class="form-horizontal" role="form">
            <div class="form-group">
                <label class="col-md-4 control-label"> {!! res('pay_now') !!}</label>
                <div class="col-md-7 form-control-static">
                    <input type="checkbox" name="pay_now" value="1">
                </div>
            </div>
            <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('total_have_to_paid') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	<span class=" total_net addcomma currency"></span> {!! res('thai_currency') !!}
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('customer_cash') !!}</label>
	            <div class="col-md-7 form-control-static">{!! Format::numberformat($cash) !!}
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('payment_typing') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	<span class="label label-inverse">{!! res('using_system_cash') !!}</span>
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	<textarea class="form-control" name="submit_remark" rows="5"></textarea>
	            </div>
	        </div>
        </div>
    </div>
</div>


<div class="col-sm-12">
	<div class="card-box" >
		<h4 class="m-t-0 header-title"><b>{!! res('tracking_list') !!}</b></h4>
		<p class="text-muted font-13 m-b-30">
        </p>
		<table class="lot_table form-group">
		    <thead>
		        <tr>
		            <th class="text-center">{!! res('lot_list').'/'.res('no') !!}</th>
		            <th class="text-center">{!! res('tracking') !!}</th>
		            <th class="text-center">{!! res('order') !!}</th>
					<th class="text-center">รหัสย่อย</th>
		            <th class="text-center">{!! res('remark_for_this_order') !!}</th>
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
		            <!-- <th class="text-center" style="width: 80px;">{!! res('price_amount') !!}</th> -->
		        </tr>
		    </thead>

		    <tbody>
		@forelse ($items as $item)
@php
    $bp = $orderIds[(int) $item->id] ?? null;
    $bp = $bp === null ? '' : (string) $bp;
@endphp
<tr><td class="text-center">{{ P::lotLabel($item) }}</td><td style="white-space: nowrap;"><a href="{{ legacy_href().'order/'.$bp.'/'.$item->tracking }}">{{ $item->tracking }}</a></td><td style="white-space: nowrap;">@if ($bp !== '')<a href="{{ legacy_href().'order/'.$bp }}">{{ $orderPrefix.$bp }}</a>@else-@endif</td><td style="vertical-align:top"  style="white-space: nowrap;">{{ $item->subuser }}</td><td style="white-space: nowrap;">{{ $item->quote }}</td><td class="text-right">{!! Format::numberformatzero($item->amount) !!}</td><td class="text-right">{!! Format::numberformatone(Php74::num($item->weight) * Php74::num($item->amount)) !!}</td><td class="text-right">{!! Format::numberformatone($item->width) !!}</td><td class="text-right">{!! Format::numberformatone($item->height) !!}</td><td class="text-right">{!! Format::numberformatone($item->long) !!}</td><td class="text-right">{!! Format::cubicformat(P::cubic($item)) !!}</td><td class="text-center">{{ $item->type_name }}</td><td class="text-right showdetailbox">@include('payment.partials.service_cell')
							</td><td class="text-right">{!! Format::getBold(Format::numberformat($item->price_kg, true, false)) !!}</td><td class="text-right">{!! Format::getBold(Format::numberformat($item->price_cubic, true, false)) !!}</td></tr>
@empty
<tr><td style="height: 100px;" colspan="14" class="text-center">{!! res('no_data') !!}</td></tr>
@endforelse
		</tbody>
			<thead>
	            <th class="text-right " colspan="5"></th>
	            <th class="text-right" >{!! Format::numberformatzero($sum['amount']) !!}</th>
	            <th class="text-right" >{!! Format::numberformatone($sum['weight']) !!}</th>
	            <th class="text-right" >{!! Format::cubicformat($sum['width']) !!}</th>
	            <th class="text-right" >{!! Format::cubicformat($sum['height']) !!}</th>
	            <th class="text-right" >{!! Format::cubicformat($sum['long']) !!}</th>
	            <th class="text-right" >{!! Format::cubicformat($sum['cubic']) !!}</th>
	            <th class="text-right" ></th>
	            <th class="text-right" >{!! Format::numberformat($sum['service'], true, false) !!}</th>
	            <th class="text-right" >{!! Format::numberformat($sum['kg'], true, false) !!}</th>
	            <th class="text-right" >{!! Format::numberformat($sum['pcubic'], true, false) !!}</th>
	            <!-- <th class="text-right" >{!! Format::numberformat($sum['pamount'], true, false) !!}</th> -->
	        </thead>
	        <thead>
	            <th class="text-right" colspan="12">{!! res('total') !!}
	            	<input type="hidden" class="hid_service" value="{!! Format::numberformat($sum['service'] + $sum['kg'] + $sum['pcubic'] + $sum['pamount'], false, false) !!}" />
	            </th>
	            <th class="text-right" colspan="8">{!! Format::numberformat($sum['service'] + $sum['kg'] + $sum['pcubic'] + $sum['pamount'], true, false) !!}</th>
	        </thead>
	        <thead>
	            <th class="text-right" colspan="12">{!! res('price_thai_storage') !!}
	            	<input type="hidden" class="hid_price_thai_storage" value="0" />
	            </th>
	            <th class="text-right price_thai_storage addcomma" colspan="3"></th>
	        </thead>
	        <thead>
	            <th class="text-right" colspan="12">{!! res('price_thai_shipping') !!}
	            	<input type="hidden" class="hid_price_thai_shipping" value="0" />
	            </th>
	            <th class="text-right price_thai_shipping addcomma" colspan="3"></th>
	        </thead>
	        <thead>
	            <th class="text-right" colspan="12">{!! res('total_net') !!}</th>
	            <th class="text-right total_net addcomma" colspan="4"></th>
	        </thead>
		</table>
</form>



@include('payment.partials.example_modal')

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
	CalculateTotal();
});
function sumiting() {
	if($("form").validationEngine('validate') == false) 
	{ 
		CloseLoading(); 
	} else {
		ConfirmBox(function () {
			$("form").eq(0).submit();
		});
	}
}

function canceling() {
	ConfirmBox(function () {
		$.ajax({
            type: 'POST',
            async: true,
            data : 'id={!! \App\Support\Legacy\Alerts::js(e($random)) !!}' ,
            url: rooturl + '_refill/cancel',
            success: function(data) {
            	eval(data);
            }
        });
	});
}


$("form").validationEngine();
$("form").ajaxForm({ 
    success: function(data) {
        eval(data);
    }
});


function getShippingPrice(ele) {
	$.ajax({
        type: 'POST',
        async: true,
        data : 'id=' + ele.val() + '&payment_ref=' + $("input[name='ref_id']").val() + '&sender=' + $("select[name='sender_type']").val(),
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

function getSenderType(val) {
	//var shipping_type = $("input[name='shipping_type_radio']:checked").val();
	//var shipping_type = $("select[name='shipping_type']").val();
	// if(shipping_type == "") {
	 	getShippingPrice($("select[name='shipping_type']"));
	// } else {
	// 	getShippingPrice($("input[name='shipping_type_radio']:checked"));
	// }
	if(val.val() == 1) {
		$(".custom_sender").css('display', 'block');
	} else {
		$(".custom_sender").css('display', 'none');
	}
}

</script>@endsection
