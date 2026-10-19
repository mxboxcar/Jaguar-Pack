{{-- _payment/create_form.php เดิม (GET payment/create/{random}) — แสดงเฉพาะ tracking ของผู้ใช้นี้ --}}
@extends('legacy.layout')
@php
    use App\Support\Legacy\Format;
@endphp

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('payment_new') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li class="active">
			{!! res('payment_new') !!}
		</li>
	</ol>
</div>

<form action="{{ legacy_href() }}_payment/save_submit_payment" method="post">
<input type="hidden" name="id" value="">
<input type="hidden" name="ref_id" value="{{ $random }}">

<div class="col-sm-5 pull-right">
	<div class="button-list">
		<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-save"></i></span>
		    {!! res('save_button') !!}
	    </button>

	    <a type="button" href="{{ legacy_href() }}payment/create" class="pull-right btn btn-warning waves-effect waves-light">
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


	        <div class="form-group" style="border-top:1px #ccc dashed"></div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('sender') !!}</label>
	            <div class="col-md-3">
	            	<select class="form-control  " name="sender_type" onchange="getSenderType($(this))">
	            		<option selected="selected" value="">{!! res('please_select') !!}</option>
	            		@if (! $hideOwnSender)
	            		<option value="0">{{ config('legacy.project_name') }}</option>
	            		@endif
	            		<option value="1">{!! res('write_by_user') !!}</option>
	            	</select>
	            </div>
	            <div class="col-xs-4">
	            	<button onclick="view_example()" type="button" class="pull-right btn btn-primary waves-effect waves-light">
		    			<span class="btn-label"><i class="fa fa-eye"></i></span>
		    			{!! res('view_example') !!}
		    		</button>
	            </div>
	        </div>

	        <div class="custom_sender" style="display:none">
	        	<div class="form-group">
        			<div class="col-md-8 col-md-offset-4">
        				<span class="text-danger">{!! res('extra_charge_sender') !!} {!! Format::numberformat($senderCharge) !!}</span>
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
	            <label class="col-md-4 control-label"> {!! res('total_have_to_paid') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	<span class=" total_net addcomma currency"></span> {!! res('thai_currency') !!}
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('your_balance') !!}</label>
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
		@include('payment.partials.detail_rows', ['orderBase' => 'order/', 'orderWithTracking' => false, 'note' => 'quote', 'emptyCols' => 11])
		</tbody>
			<thead>
	            <th class="text-right " colspan="5"></th>
	            <th class="text-right" >{!! Format::numberformatzero($sum['amount']) !!}</th>
	            <th class="text-right" >{!! Format::numberformatone($sum['weight']) !!}</th>
	            <th class="text-right" >{!! Format::numberformatone($sum['width']) !!}</th>
	            <th class="text-right" >{!! Format::numberformatone($sum['height']) !!}</th>
	            <th class="text-right" >{!! Format::numberformatone($sum['long']) !!}</th>
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
	            <th class="text-right" colspan="12"><small class="text-danger">({!! res('price_shipping_remark') !!})</small> {!! res('price_thai_shipping') !!}
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

$(function() {
	$('#my_multi_select3').multiSelect({
        selectableHeader: "<input type='text' class='form-control search-input' autocomplete='off' placeholder='{!! res('search') !!}'>",
        selectionHeader: "<input type='text' class='form-control search-input' autocomplete='off' placeholder='{!! res('search') !!}'>",
        afterInit: function (ms) {
            var that = this,
                $selectableSearch = that.$selectableUl.prev(),
                $selectionSearch = that.$selectionUl.prev(),
                selectableSearchString = '#' + that.$container.attr('id') + ' .ms-elem-selectable:not(.ms-selected)',
                selectionSearchString = '#' + that.$container.attr('id') + ' .ms-elem-selection.ms-selected';

            that.qs1 = $selectableSearch.quicksearch(selectableSearchString)
                .on('keydown', function (e) {
                    if (e.which === 40) {
                        that.$selectableUl.focus();
                        return false;
                    }
                });

            that.qs2 = $selectionSearch.quicksearch(selectionSearchString)
                .on('keydown', function (e) {
                    if (e.which == 40) {
                        that.$selectionUl.focus();
                        return false;
                    }
                });
        },
        afterSelect: function () {
            this.qs1.cache();
            this.qs2.cache();
        },
        afterDeselect: function () {
            this.qs1.cache();
            this.qs2.cache();
        }
    });
});

function getShippingPrice(ele) {
	$.ajax({
        type: 'POST',
        async: true,
        data : 'id=' + ele.val() + '&payment_ref=' + $("input[name='ref_id']").val() + '&sender=' + $("select[name='sender_type']").val(),
        url: rooturl + '_payment/load_shipping_price',
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

	$(".total_net").text((eval(service) + eval(price_thai_storage) + eval(price_thai_shipping)).toFixed(2) );
	initialAddcommaText();
}

function getSenderType(val) {
	//var shipping_type = $("input[name='shipping_type_radio']:checked").val();
	//if(shipping_type == "") {
		getShippingPrice($("select[name='shipping_type']"));
	//} else {
	//	getShippingPrice($("input[name='shipping_type_radio']:checked"));
	//}
	if(val.val() == 1) {
		$(".custom_sender").css('display', 'block');
	} else {
		$(".custom_sender").css('display', 'none');
	}
}

function view_example() {
	$.ajax({
        type: 'POST',
        async: true,
        url: rooturl + '_payment/view_example',
        success: function(data) {
        	var url = '{{ legacy_href() }}_payment/view_example';
        	url += '?cus_name=' + $("input[name='name']").val();
        	url += '&cus_address=' + $("textarea[name='address']").val();
        	url += '&cus_phone=' + $("input[name='phone']").val();
        	url += '&ship_type=' + $("input[name='shipping_type_radio']:checked").val();
        	url += '&send_name=' + $("input[name='sender_name']").val();
        	url += '&send_address=' + $("textarea[name='sender_address']").val();
        	url += '&send_phone=' + $("input[name='sender_phone']").val();
        	url += '&submit_remark=' + $("textarea[name='submit_remark']").val();
        	url += '&sender_type=' + $("select[name='sender_type']").val();
        	url += '&ref_id=' + $("input[name='ref_id']").val();
        	url += '&service=' + $(".price_thai_storage").text();
        	url += '&shipping=' + $(".price_thai_shipping").text();
        	url += '&total=' + $(".total_net").text();
        	$("iframe").attr('src', url);
        	$(".example_form").modal('show');
        }
    });
}
</script>@endsection
