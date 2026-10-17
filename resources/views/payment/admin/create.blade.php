{{-- _admin/_payment/create.php เดิม (สิทธิ์ admin_payment_create ตรวจที่ middleware) --}}
@extends('legacy.layout')

@section('content')
<style> .content-page { margin-left: 0px; } </style>
<style>
.widget-panel .text-gray{
	color:#fff!important;
}
</style>
<form action="{{ legacy_href() }}_admin/_payment/save_create" method="post">
	<div class="col-sm-6">
		<h4 class="page-title">{!! res('export_creating') !!}</h4>
		<ol class="breadcrumb">
        <li>
            <a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
        </li>
			<li class="active">
				{!! res('export_creating') !!}
			</li>
		</ol>
	</div>
	<div class="col-sm-6 pull-right">
		<div class="button-list">

		    <button onclick="sumiting()" type="button" class="pull-right btn btn-info waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-plus"></i></span>
			    {!! res('checkout_payment') !!}
		    </button>

		</div>
	</div>

	<div class="col-sm-12">
		<div class="card-box" >
			<div class="row">
				<div class="col-xs-12">
                    <div class="col-xs-12" style="margin: 10px 0px 10px 0px;padding: 0px;">
                        <b>{!! res('choose_users') !!} : </b>
                        <input style="width:180px" type="text" class="autocomplete" id="search_user" value="{{ $code }}" placeholder="{!! res('customer_code') !!}" >
                        <button style="margin-left: 20px;" class="btn-danger" type="button" onclick="getuser()">{!! res('load_user_tracking') !!}</button>
                    </div>

                    <div class="col-sm-3 col-xs-5">
		                <div class="widget-panel widget-style-2 bg-white">
		                	<i class="md  md-local-library text-gray" style="background-color: #991b1e!important;"></i>
		                    <h2 class=" m-0 text-dark counter font-600">
                                {{ $code }}
                            </h2>
		                    <div  class="text-muted m-t-5 ">{!! res('customer_code') !!}</div>
		                </div>

		            </div>
                    
			    	<div class="col-sm-3 col-xs-5">
		                <div class="widget-panel widget-style-2 bg-white">
		                	<i class="fa  fa-list-alt text-gray" style="background-color: #991b1e!important;"></i>
		                    <h2 id="total_amount" class="addcommazero m-0 text-dark counter font-600">0</h2>
		                    <div  class="text-muted m-t-5 ">{!! res('total_amount_list') !!}</div>
		                </div>

		            </div>
		            
		            <div class="col-sm-3 col-xs-5">
		                <div class="widget-panel widget-style-2 bg-white">
		                	<i class="fa fa-btc text-gray" style="background-color: #991b1e!important;"></i>
		                    <h2   class=" m-0 text-dark counter font-600"><span class="addcomma" id="total_price"></span> {!! res('thai_currency') !!}</h2>
		                    <div  class="text-muted m-t-5 addcommazero">{!! res('total_price_list') !!}</div>
		                </div>
		            </div>

                    <div class="col-sm-3 col-xs-5">
		                <div class="widget-panel widget-style-2 bg-white">
		                	<i class="fa fa-btc text-gray" style="background-color: #991b1e!important;"></i>
		                    <h2   class=" m-0 text-dark counter font-600"><span class="addcomma" >{{ $cash }}</span></h2>
		                    <div  class="text-muted m-t-5 addcommazero">{!! res('customer_cash') !!}</div>
		                </div>
		            </div>
			    </div>
		    </div>
				


			<div class="col-xs-12" style="margin: 10px 0px 10px 0px;padding: 0px;">
				<h4 class="m-t-0 header-title"><b>{!! res('header_create_header') !!}</b></h4>
				<b>{!! res('searching') !!} : </b>
				<input style="width:180px" type="text" id="search_track" value="" placeholder="{!! res('tracking') !!}" >
				<input style="width:180px" type="text" id="search_quote" value="" placeholder="{!! res('remark_for_this_order') !!}" >
				<b style="margin-left:10px;">{!! res('lot') !!} : </b>
				<span id="select_lot"></span>
				<input type="checkbox" onclick="loaddata();" id="search_admit_thai" > {!! res('show_admit_thai') !!}

				<button style="margin-left: 20px;" class="btn-danger" type="button" onclick="loaddata()">{!! res('search_list') !!}</button>
			</div>


		    <div id="tracking_list"></div>
		</div>
	</div>
    <input type="hidden" name="user_id" value="{{ $userId }}" />
    <input type="hidden" name="prices" />
	</form>

<script>


function keypressInBox(e) {
    var code = (e.keyCode ? e.keyCode : e.which);
    if (code == 13) { //Enter keycode                        
        e.preventDefault();
        loaddata();
    }
}
$("#search_track").bind("keypress", {}, keypressInBox);
$("#search_quote").bind("keypress", {}, keypressInBox);

$("form").eq(0).validationEngine();
	$("form").eq(0).ajaxForm({ 
	    success: function(data) {
	        eval(data);
	    }
	});
$(function() {
	
	loadselect();
	
	$('.showdetailbox').on('touchstart mouseover', function (e) {
	    $(this).css({'color':  '#ff0000'});
		$(this).find('.lot_table_in').css('display', 'block');
	});
	$('.showdetailbox').on('touchend mouseout', function (e) {
	    $(this).css({'color': '#797979'});
		$(this).find('.lot_table_in').css('display', 'none');
	});

    $('.autocomplete').typeahead({
        source: function (query, process) {
            $.ajax({
            url: rooturl + '_admin/_payment/load_autocomplete_user',
            type: 'POST',
            dataType: 'JSON',
            data: 'query=' + query,
            success: function(data) {
                process(data.code);
                cache_search = data.user;
            }
            })
        }
    });

    
});


function loadselect() {
	$.ajax({
        type: 'POST',
        async: true,
        data: 'user_id={{ $userId }}',
        url: rooturl + '_admin/_payment/load_select_lot',
        success: function(data) {
           $("#select_lot").html(data);
           setTimeout(function() { loaddata(); }, 200);
        }
    });
}

function getuser() {
    $.ajax({
        type: 'POST',
        async: true,
        data: 'code=' + $("#search_user").val(),
        url: rooturl + '_admin/_payment/load_user_by_id',
        success: function(data) {
            if(data != "") {
                window.location.href = rooturl + 'admin/payment/create/' + data;
            }
          
        }
    });
}

function loaddata() {
	OpenLoading();
	var chk = $("#search_admit_thai").prop('checked');
	var admit_thai = 0;
	if(chk) {
		admit_thai = 1;
	}

	$.ajax({
        type: 'POST',
        async: true,
        data : 'user_id={{ $userId }}&track=' + $("#search_track").val() + '&quote=' + $("#search_quote").val() + '&admit_thai=' + admit_thai + '&search_lot=' + $("#search_lot").val() ,
        url: rooturl + '_admin/_payment/load_create',
        success: function(data) {
            $("#tracking_list").html(data);
            CloseLoading();
        }
    });
}

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
	Calculate();
}

function Calculate() {
	var total = 0;
	var amount = 0;

	$(".cbxitem").each(function() {
		if($(this).prop('checked') == true) {
			var kg = $(this).parents('tr').find('.hid_price_kg').val();
            var cubic = $(this).parents('tr').find('.hid_price_cubic').val();
            var qc = $(this).parents('tr').find('.hid_price_qc').val();
            var repack = $(this).parents('tr').find('.hid_price_repack').val();
            var express = $(this).parents('tr').find('.hid_price_express').val();
            var elses = $(this).parents('tr').find('.hid_price_else').val();
            var craft = $(this).parents('tr').find('.hid_price_craft').val();

			total = total + eval(kg) + eval(cubic) + eval(qc) + eval(repack) + eval(express) + eval(elses) + eval(craft);
			amount++;
		}
	});

	$("#total_amount").html(amount);
	$("#total_price").html(currency_number(total.toFixed(2)));

	initialAddcomma();
    CreatePriceString();
}

function CreatePriceString() {
    var strs = '';
    $(".cbxitem").each(function() {
		if($(this).prop('checked') == true) {
			var kg = $(this).parents('tr').find('.hid_price_kg').val();
            var cubic = $(this).parents('tr').find('.hid_price_cubic').val();
            var qc = $(this).parents('tr').find('.hid_price_qc').val();
            var repack = $(this).parents('tr').find('.hid_price_repack').val();
            var express = $(this).parents('tr').find('.hid_price_express').val();
            var elses = $(this).parents('tr').find('.hid_price_else').val();
            var craft = $(this).parents('tr').find('.hid_price_craft').val();

            var str = kg + '*' + cubic + '*' + qc + '*' + repack + '*' + express + '*' + elses + '*' + craft;

            strs = strs + "|" + str;
		}
	});
    
    $("input[name='prices']").val(strs);
}

</script>
@endsection
