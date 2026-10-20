{{-- _payment/create.php เดิม --}}
@extends('legacy.layout')

@section('content')
<style> .content-page { margin-left: 0px; } </style>
<style>
.widget-panel .text-gray{
	color:#fff!important;
}
</style>
<form action="{{ legacy_href() }}_payment/save_create" method="post">
	<div class="col-sm-6">
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
	<div class="col-sm-6 pull-right">
		<div class="button-list">

		    <button onclick="sumiting()" type="button" class="pull-right btn btn-info waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-plus"></i></span>
			    {!! res('checkout_payment') !!}
		    </button>

		    <a type="button" href="{{ legacy_href() }}payment" class="pull-right btn btn-warning waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
			    {!! res('back_to_list') !!}
		    </a>
		</div>
	</div>

	<div class="col-sm-12">
		<div class="card-box" >
			<div class="row">
				<div class="col-xs-12">
			    	<div class="col-sm-3 col-xs-5">
		                <div class="widget-panel widget-style-2 bg-white">
		                	<i class="fa  fa-list-alt text-gray" style="background-color: #a3b2f1!important;"></i>
		                    <h2 id="total_amount" class="addcommazero m-0 text-dark counter font-600">0</h2>
		                    <div  class="text-muted m-t-5 ">{!! res('total_amount_list') !!}</div>
		                </div>

		            </div>
		            
		            <div class="col-sm-3 col-xs-5">
		                <div class="widget-panel widget-style-2 bg-white">
		                	<i class="fa fa-btc text-gray" style="background-color: #a3b2f1!important;"></i>
		                    <h2   class=" m-0 text-dark counter font-600"><span class="addcomma" id="total_price"></span> {!! res('thai_currency') !!}</h2>
		                    <div  class="text-muted m-t-5 addcommazero">{!! res('total_price_list') !!}</div>
		                </div>
		            </div>
			    </div>
		    </div>
				


			<div class="col-xs-12" style="margin: 10px 0px 10px 0px;padding: 0px;">
				<h4 class="m-t-0 header-title"><b>{!! res('header_create_header') !!}</b></h4>
				<b>{!! res('searching') !!} : </b>
				<input style="width:180px" type="text" id="search_track" value="" placeholder="{!! res('tracking') !!}" >
				<input style="width:180px" type="text" id="search_quote" value="" placeholder="{!! res('remark_for_this_order') !!}" >
				<input style="width:180px" type="text" id="search_subuser" value="" placeholder="รหัสย่อย" >
				<b style="margin-left:10px;">{!! res('lot') !!} : </b>
				<span id="select_lot"></span>
				<input type="checkbox" onclick="loaddata();" id="search_admit_thai" > {!! res('show_admit_thai') !!}

				<button style="margin-left: 20px;" class="btn-danger" type="button" onclick="loaddata()">{!! res('search_list') !!}</button>
			</div>


		    <div id="tracking_list"></div>
		</div>
	</div>
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
$("#search_subuser").bind("keypress", {}, keypressInBox);

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
});


function loadselect() {
	OpenLoading();
	var chk = $("#search_admit_thai").prop('checked');
	var admit_thai = 0;
	if(chk) {
		admit_thai = 1;
	}
	$.ajax({
        type: 'POST',
        async: true,
		data : 'track=' + $("#search_track").val() + '&quote=' + $("#search_quote").val() + '&admit_thai=' + admit_thai + '&subuser=' + $("#search_subuser").val() ,
        url: rooturl + '_payment/load_select_lot',
        success: function(data) {
           $("#select_lot").html(data);
           setTimeout(function() { loaddata(); }, 200);
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
        data : 'track=' + $("#search_track").val() + '&quote=' + $("#search_quote").val() + '&admit_thai=' + admit_thai + '&search_lot=' + $("#search_lot").val() + '&subuser=' + $("#search_subuser").val() ,
        url: rooturl + '_payment/load_create',
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
			var val = $(this).parent().find('.s_total').val();
			total = total + eval(val);
			amount++;
		}
	});

	$("#total_amount").html(amount);
	$("#total_price").html(currency_number(total.toFixed(2)));

	initialAddcomma();
}

</script>
@endsection
