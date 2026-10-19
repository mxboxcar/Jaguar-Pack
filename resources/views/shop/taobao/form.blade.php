{{-- _taobao/form.php เดิม (page=taobao_detail) — id 0 = สร้างใหม่ --}}
@use('App\Support\Legacy\Format')
@use('App\Support\Legacy\Status')
@extends('legacy.layout')

@php
    $n = fn ($v) => \App\Domain\Shop\Php74::num($v);
    // เดิม $id == "" / $id != "" กับ $id ที่เป็น int: PHP 7.4 ให้ 0 == "" เป็นจริง → id 0 = หน้าสร้างใหม่
    $isNew = $id === 0;
@endphp

@section('content')
@if ($id === 0)

<div class="col-sm-7">
	<h4 class="page-title">{!! res('taobao') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('taobao') !!}
		</li>
		<li class="active">
			{!! res('taobao_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('taobao_edit') !!} <small>{{ $prefix }}{{ $data->id ?? '' }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('taobao') !!}
		</li>
		<li class="active">
			{{ $prefix }}{{ $data->id ?? '' }}
		</li>
	</ol>
</div>
@endif

<form action="{{ legacy_href() }}_taobao/save" method="post">
	<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

		<div class="col-sm-5 pull-right">
			<div class="button-list">
				
				<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
				    <span class="btn-label"><i class="fa fa-save"></i></span>
				    {!! res('save_button') !!}
			    </button>
			    
			    <a type="button" href="{{ legacy_href() }}taobao" class="pull-right btn btn-warning waves-effect waves-light">
				    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
				    {!! res('back_to_list') !!}
			    </a>
			</div>
		</div>

<div class="col-sm-12">
	<div class="button-list text-center">
		<a type="button" href="{{ legacy_href() }}taobao" class=" btn btn-primary  waves-effect waves-light">
		    <span class="btn-label"><i class="fa  fa-list-ol"></i></span>
		    {!! res('taobao') !!}
	    </a>
	    <a type="button" href="{{ legacy_href() }}shop_cashback" class=" btn btn-inverse  waves-effect waves-light">
		    <span class="btn-label"><i class="fa  fa-undo"></i></span>
		    {!! res('shop_cashback') !!}
	    </a>
	    <a type="button" href="{{ legacy_href() }}balance_cn" class=" btn btn-inverse  waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-cny"></i></span>
		    {!! res('balance_cn') !!}
	    </a>
	</div>
</div>

					<div class="col-sm-12">
						<div class="card-box">
							<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
							<p class="text-muted font-13 m-b-30">
					        </p>
							<div class="form-horizontal" role="form">
								@if (! $isNew)
								<div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('no_for_grid') !!}</label>
						            <div class="col-md-4 form-control-static">
						                {{ $prefix }}{{ $data->id ?? '' }}
						            </div>
						        </div>

						        <div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('taobao_id') !!}</label>
						            <div class="col-md-4 form-control-static">
						                {{ $data->taobao_name ?? '' }}
						            </div>
						        </div>

						        <div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('taobao_password') !!}</label>
						            <div class="col-md-4 form-control-static">
						                ******
						            </div>
						        </div>

						        <div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('remark_for_this_order') !!}</label>
						            <div class="col-md-4">
						                <input type="text" name="remark" class="form-control" value="{{ $data->remark ?? '' }}">
						            </div>
						        </div>

						        

						        @else

						        <div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('taobao_id') !!}</label>
						            <div class="col-md-4">
						                <input type="text" name="taobao_name" class="form-control" value="">
						            </div>
						            
						        </div>

						        <div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('taobao_password') !!}</label>
						            <div class="col-md-4">
						                <input type="password" name="taobao_password" class="form-control" value="">
						            </div>
						        </div>

						        <div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('remark_for_this_order') !!}</label>
						            <div class="col-md-4 form-control-static">
						            	<input type="text" name="remark" class="form-control" value="{{ $data->remark ?? '' }}">
						            </div>
						        </div>
 @endif
						    </div>
						</div>
					</div>

					<div class="col-sm-12">
						<div class="card-box">
							<h4 class="m-t-0 header-title"><b>{!! res('list') !!}</b></h4>
							<p class="text-muted font-13 m-b-30">
					        </p>
							<div class="form-horizontal" role="form">
								@if ($isNew)
						      	<table id="data_table" class="table  table-bordered m-t-20">
						      		<thead>
						      			<tr>
						      				<th class="text-center" style="width:10px">{!! res('no') !!}</th>
						      				<th class="text-center">{!! res('order') !!}</th>
						      				<th class="text-center" style="width:100px">{!! res('price') !!}</th>
						      				<th class="text-center" style="width:100px">{!! res('action') !!}</th>
						      			</tr>
						      		</thead>
						      		<tbody>
						      			<tr>
						      				<td class="no"></td>
						      				<td><input style="width: 100%;" name="order[]" type="text" class="" value="" /></td>
						      				<td>{!! res('cn_currency') !!}<input style="width: 80%;margin-left:3px;" onkeyup="CalculatePrice();" name="price[]" type="text" class="" value="" /></td>
						      				<td class="text-center">
						      					<button type="button" class="btn-success" onclick="on_addnewline()"><i class="md md-exposure-plus-1"></i></button>
						      				</td>
						      			</tr>
						      		</tbody>
						      		<thead>
						      			<th class="text-right" colspan="2" >{!! res('cn_cash') !!}</th>
						      			<th class="text-right" colspan="2" id="total_order_cal"></th>
						      		</thead>
						      		
						      		<thead>
						      				<th class="text-right" colspan="2" >{!! res('total_service') !!}</th>
						      				<th class="text-right" colspan="2"  id="total_price_thai"></th>
						      		</thead>
						      		<thead>
						      				<th class="text-right" colspan="2" >{!! res('total_to_paid') !!}</th>
						      				<th class="text-right" colspan="2"   id="total_price"></th>
						      		</thead>
						      	</table>
@else
						        <table id="data_table" class="table  table-bordered m-t-20">
						      		<thead>
						      			<tr>
						      				<th class="text-center" style="width:10px">{!! res('no') !!}</th>
						      				<th class="text-center">{!! res('order') !!}</th>
						      				<th class="text-center" style="width:100px">{!! res('price') !!}</th>
						      				<th class="text-center" style="width: 200px">{!! res('status') !!}</th>
						      			</tr>
						      		</thead>
						      		<tbody>
@foreach ($details as $item)
<tr>
									      				<td class="no"></td>
									      				<td>{{ $item->order }}</td>
									      				<td class="text-right">{!! Format::numberformat($item->price, true, true, true) !!}</td><td class="text-left">{!! Status::getstatus($item->status) !!}@if ((int) $item->status === -1)<span style='margin-top: 10px;' class='text-danger'>* {{ explode(',ยกเลิกโดย', (string) $item->cancel_remark)[0] }}</span>@endif</td></tr>
@endforeach
						      			
						      		</tbody>
						      		<thead>
						      			<th class="text-right" colspan="2" >{!! res('cn_cash') !!}</th>
						      			<th class="text-right" colspan="2" id="total_order_cal">{{ $sum->sum ?? '' }} x {{ $data->rate ?? '' }} = {!! Format::numberformat($n($sum->sum ?? '') * $n($data->rate ?? '')) !!}</th>
						      		</thead>
						      		<thead>
						      				<th class="text-right" colspan="2" >{!! res('cash_cn') !!}</th>
						      				
						      				<th class="text-right" colspan="2"  id="total_price_thai">{{ $data->usecash_cn ?? '' }} x {{ $data->rate ?? '' }} = -{!! Format::numberformat($n($data->usecash_cn ?? '') * $n($data->rate ?? '')) !!}</th>
						      		</thead>
						      		<thead>
						      				<th class="text-right" colspan="2" >{!! res('total_service') !!}</th>
						      				<th class="text-right" colspan="2"  id="total_price_thai">{{ $sum->count ?? '' }} x {{ $data->charge ?? '' }} = {!! Format::numberformat($n($data->charge ?? '') * $n($sum->count ?? '')) !!}</th>
						      		</thead>
						      		<thead>
						      				<th class="text-right" colspan="2" >{!! res('total_to_paid') !!}</th>
						      				<th class="text-right" colspan="2"   id="total_price">{!! Format::numberformat($data->usecash ?? '') !!}</th>
						      		</thead>

						      	</table>
@endif
						    </div>
						</div>
					</div>


@if ($data === null)

					<input type="hidden" name="id" value="{{ $id }}">

					<div class="col-xs-6">
				    <div class="card-box">
				    <h4 class="m-t-0 header-title"><b>{!! res('payment_thing') !!}</b></h4>
				    <p class="text-muted font-13 m-b-30">
				    </p>
				    	<div class="form-horizontal" role="form">
				            <div class="form-group">
					            <label class="col-md-4 control-label"> {!! res('total_have_to_paid') !!}</label>
					            <div class="col-md-7 form-control-static">
					            	<span class=" total_net "></span>
					            </div>
					        </div>
					        <div class="form-group">
					            <label class="col-md-4 control-label"> {!! res('your_balance') !!}</label>
					            <div class="col-md-7 form-control-static">{!! Format::numberformat($cash) !!}
					            </div>
					        </div>
					        <div class="form-group">
					            <label class="col-md-4 control-label"> {!! res('cash_cn') !!}</label>
					            <div class="col-md-7 form-control-static">{!! Format::numberformat($cashCn, true, true, true) !!}
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
				
@else

					<input type="hidden" name="id" value="{{ $id }}">
					<div class="col-xs-6">
				    <div class="card-box">
				    <h4 class="m-t-0 header-title"><b>{!! res('payment_thing') !!}</b></h4>
				    <p class="text-muted font-13 m-b-30">
				    </p>
				    	<div class="form-horizontal" role="form">
				    		<div class="form-group">
					            <label class="col-md-4 control-label"> {!! res('no_for_grid') !!}</label>
					            <div class="col-md-7 form-control-static">
					            	{{ $prefix }}{{ $data->id }}
					            </div>
					        </div>
				            <div class="form-group">
					            <label class="col-md-4 control-label"> {!! res('total_have_to_paided') !!}</label>
					            <div class="col-md-7 form-control-static">
					            	<span class=" total_net addcomma currency">{{ $data->usecash }}</span> {!! res('thai_currency') !!}
					            </div>
					        </div>
					        <div class="form-group">
					            <label class="col-md-4 control-label"> {!! res('use_cash_cn_short') !!}</label>
					            <div class="col-md-7 form-control-static">{!! Format::numberformat($data->usecash_cn, true, true, true) !!}
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
					            	{!! nl2br(e($data->submit_remark ?? null)) !!}
					            </div>
					        </div>
				        </div>
				    </div>
				</div>
				
@endif
				


<div class="col-xs-6">   <div class="card-box">
				            <h4 class="m-t-0 header-title"><b>{!! res('ref_balance_list') !!}</b></h4>
				            <p class="text-muted font-13 m-b-30">
				            </p>
				            <table class="table table-striped table-bordered">
				                    <thead>
				                        <tr>
				                            <th class="text-center" style="width: 130px;">{!! res('working_date') !!}</th>
				                            <th class="text-center">{!! res('detail') !!}</th>
				                            <th class="text-center" style="width: 80px;">{!! res('amount') !!}</th>
				                        </tr>
				                    </thead>
				                    <tbody>@if (count($balance) !== 0)
@foreach ($balance as $item)
@php $exp = explode(' ', (string) $item->create_date); @endphp
<tr><td>{!! Format::getthaidate($exp[0]) !!}<br/>{{ $exp[1] ?? '' }}</td><td class="text-left"><a href="{{ legacy_href() }}{{ $item->link }}{{ $item->prefix_ref_id }}">{{ $item->detail }}</a>@if ($item->types === 'balance_cn') ({!! Format::numberformat($n($item->amount) * $n($data->rate ?? '')) !!})@endif</td><td class="text-right">@if ($item->types === 'balance')
@if ($n($item->amount) >= 0)<span class="text-success">+{!! Format::numberformat($item->amount) !!}</span>@else<span class="text-danger">{!! Format::numberformat($item->amount) !!}</span>@endif
@else
@if ($n($item->amount) >= 0)<span class="text-success">+{!! Format::numberformat($item->amount, true, true, true) !!}</span>@else<span class="text-danger">{!! Format::numberformat($item->amount, true, true, true) !!}</span>@endif
@endif
</td>
@endforeach
@else
<tr><td colspan="3" class="text-center">{!! res('no_data') !!}</td></tr>
@endif
				                </tbody>
				            </table>
				        </div>
				    </div>
</form>

<script>

	$(function() {
		on_makeno();
		@if ($data === null)
		CalculatePrice();
		@endif
	})

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
            url: rooturl + '_taobao/load_calculator',
            success: function(data) {
            	data = $.parseJSON(data);

            	$("#total_order").text(data.order);
            	$("#total_order_cal").html(data.total_order);

            	$("#total_price").html(data.price_cn);
            	$("#total_price_thai").html(data.price_th);

            	$("#total_thai").html(data.total_thai);
            	$(".total_net").html(data.total_thai);
            	$("#cash_cn").html(data.cash_cn);
            	$("#rate").html(data.rate);
            }
        });
	}



	function on_addnewline() {
		$.ajax({
            type: 'POST',
            async: true,
            url: rooturl + '_taobao/load_new_line',
            success: function(data) {
            	$("#data_table tbody").append(data);
            	on_makeno();
            	CalculatePrice();
            	CloseLoading();
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
