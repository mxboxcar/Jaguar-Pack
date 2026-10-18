{{-- _shop_cashback/form.php เดิม (id 0 = สร้างใหม่; ไม่ใช่ของตัวเอง → controller ตอบหน้าไม่มีสิทธิ์แบบเดิม) --}}
@extends('legacy.layout')
@php use App\Support\Legacy\Format; use App\Support\Legacy\Status; use App\Domain\Points\Grid; use App\Domain\Points\Php74; $no = $prefix.($data->id ?? ''); @endphp

@section('content')
@if($id === 0)
<style>
.datepicker { padding:0px; }

</style>


<div class="col-sm-7">
	<h4 class="page-title">{!! res('shop_cashback') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('shop_cashback') !!}
		</li>
		<li class="active">
			{!! res('shop_cashback_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('shop_cashback_edit') !!} <small>{{ $no }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('shop_cashback') !!}
		</li>
		<li class="active">
			{{ $no }}
		</li>
	</ol>
</div>
@endif

<form action="{{ legacy_href() }}_shop_cashback/save" method="post">
	<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

		<div class="col-sm-5 pull-right">
			<div class="button-list">
@if($id === 0)
				<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
				    <span class="btn-label"><i class="fa fa-save"></i></span>
				    {!! res('save_button') !!}
			    </button>
@endif

			    <a type="button" href="{{ legacy_href() }}shop_cashback" class="pull-right btn btn-warning waves-effect waves-light">
				    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
				    {!! res('back_to_list') !!}
			    </a>
			</div>
		</div>

@include('points.partials.cashback_nav', ['base' => ''])


<div class="col-sm-12">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
		<p class="text-muted font-13 m-b-30">
        </p>
		<div class="form-horizontal" role="form">
@if($id !== 0)
			<div class="form-group">
	            <label class="col-md-2 control-label"> {!! res('no_for_grid') !!}</label>
	            <div class="col-md-4 form-control-static">
	                {{ $no }}
	            </div>
	        </div>
@endif

	        <div class="form-group">
	            <label class="col-md-2 control-label"> {!! res('remark_for_this_order') !!}</label>
	            <div class="col-md-4">
	                <input type="text" name="remark" class="form-control" value="{{ $data->remark ?? '' }}">
	            </div>
	        </div>
	    </div>
	</div>
</div>
			<div class="col-sm-12">
				<div class="card-box">
					<h4 class="m-t-0 header-title"><b>{!! res('list') !!}</b></h4>
					<p class="text-muted font-13 m-b-30">
			        </p>
					<div class="form-horizontal" role="form">
@if($id === 0)
				      	<table id="data_table" class="table  table-bordered m-t-20">
				      		<thead>
				      			<tr>
				      				<th class="text-center" style="width:10px">{!! res('no') !!}</th>
				      				<th class="text-center">{!! res('order') !!}</th>
				      				<th class="text-center">{!! res('working_date_shop_cashback') !!}</th>
				      				<th class="text-center" style="width:205px">{!! res('attachment_shop_cashback') !!}</th>
				      				<th class="text-center" style="width:70px">{!! res('price') !!}</th>
				      				<th class="text-center" style="width:70px">{!! res('action') !!}</th>
				      			</tr>
				      		</thead>
				      		<tbody>
				      			<tr>
				      				<td class="no"></td>
				      				<td><input style="width: 100%;" name="order[]" type="text" class="order_number" value="" /></td>
				      				<td><input style="width: 100%;" name="date[]" type="text" class="datepicker" value="" /></td>
				      				<td>{!! $upload !!}</td>
				      				<td>{!! res('cn_currency') !!}<input style="width: 80%;margin-left:3px;" onkeyup="CalculatePrice();" name="price[]" type="text" class="" value="" /></td>
				      				<td class="text-center">
				      					<button type="button" class="btn-success" onclick="on_addnewline()"><i class="md md-exposure-plus-1"></i></button>
				      				</td>
				      			</tr>
				      		</tbody>
				      		
				      		<thead>
				      				<th class="text-right" colspan="4" >{!! res('total_to_paid') !!}</th>
				      				<th class="text-right"    id="total_price"></th>
				      				<th class="text-right" ></th>
				      		</thead>
				      	</table>
@else
				        <table id="data_table" class="table  table-bordered m-t-20">
				      		<thead>
				      			<tr>
				      				<th class="text-center" style="width:10px">{!! res('no') !!}</th>
				      				<th class="text-center">{!! res('order') !!}</th>
				      				<th class="text-center">{!! res('working_date_shop_cashback') !!}</th>
				      				<th class="text-center" style="width:205px">{!! res('attachment_shop_cashback') !!}</th>
				      				<th class="text-center" style="width:70px">{!! res('price') !!}</th>
				      				<th class="text-center" style="width: 200px">{!! res('status') !!}</th>
				      			</tr>
				      		</thead>
				      		<tbody>
@foreach($details as $item)
<tr>
							      				<td class="no"></td>
							      				<td>{{ $item->order }}</td>
							      				<td class="text-center">{!! Grid::dateOrDash($item->submit_date) !!}</td>
							      				<td class="text-center">{!! Format::getFancyBox(e((string) $item->attachment)) !!}</td>
							      				<td class="text-right">{!! Format::numberformat($item->price, true, true, true) !!}</td><td class="text-left">{!! Status::getstatus($item->status) !!}@if(Php74::eq($item->status, -1))<span style='margin-top: 10px;' class='text-danger'>* {{ explode(',ยกเลิกโดย', (string) $item->cancel_remark)[0] }}</span>@endif</td></tr>
@endforeach
				      			
				      		</tbody>
				      		<thead>
				      				<th class="text-right" colspan="4" >{!! res('total_to_paid_back') !!}</th>
				      				<th class="text-right" id="total_price">{!! Format::numberformat($prices) !!}</th>
				      				<th></th>
				      		</thead>

				      		
				      	</table>
@endif
				    </div>
				</div>
			</div>


@include('points.partials.ledger', ['boxClass' => 'col-xs-12', 'cnRate' => true])
</form>

<script>

	$(function() {
		on_makeno();
@if(($data->id ?? null) === null)
		CalculatePrice();
@endif

		initialDatepicker();
		Triggerupload();
	})

	function Triggerupload() {
		$(".dropper-dropzone").addClass("btn-xs");
		$(".dropper-dropzone").parent().parent().css("height", "22px");
		$(".dropper").parent().find('img').parent().parent().css({'clear':'none', 'margin-top':'0px', 'margin-left':'10px' });
		$(".dropper").parent().find('img').css({'max-width': '21px', 'max-height': '21px'} );
		$(".dropper-input").css({ 'margin-top':'-22px', 'height':'22px', 'width':'82px'});
		$(".dropper").parent().find('div').eq(3).css("margin-top", "0px");
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


	$("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	    }
	});

	function CalculatePrice() {
		var prices = 0;

		$("input[name='price[]']").each(function() {
			if($(this).val() != "") {
				prices = prices + eval($(this).val());
			}
		});

		$.ajax({
            type: 'POST',
            async: true,
            data : 'order=' + $("input[name='order[]']").length + '&price=' + prices,
            url: rooturl + '_shop_cashback/load_calculator',
            success: function(data) {
            	data = $.parseJSON(data);
            	$("#total_price").html(data.price_cn);
            }
        });
	}



	function on_addnewline() {
		$.ajax({
            type: 'POST',
            async: true,
            data : 'item=' + $(".order_number").length,
            url: rooturl + '_shop_cashback/load_new_line',
            success: function(data) {
            	$("#data_table tbody").append(data);
            	on_makeno();
            	CalculatePrice();
            	CloseLoading();
            	Triggerupload();
            	initialDatepicker();
            }
        });
	}

	function on_makeno() {
		var count = 1;
		$("#data_table tbody tr").each(function() {
			$(this).find('td').eq(0).text(count + ".");
			count++;
		});
	}

	function on_deleteline(ele) {
		ele.parents('tr').remove();
		CalculatePrice();
	}

</script>
@endsection
