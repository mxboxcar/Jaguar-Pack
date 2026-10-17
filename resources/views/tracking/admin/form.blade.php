{{-- _admin/_tracking/form.php เดิม --}}
@extends('legacy.layout')

@php
    use App\Domain\Tracking\Php74;
    use App\Support\Legacy\Alerts;
    use App\Support\Legacy\Format;
    $g = fn (string $k) => $d[$k] ?? null;
    $inputDate = function ($v) {
        if (Php74::neq($v, '0000-00-00 00:00:00') && Php74::neq($v, '')) {
            return Format::getthaidate(explode(' ', (string) $v)[0], false, true);
        }
        return '';
    };
    $sel = fn ($k, $v) => Php74::eq($g($k), $v) ? "selected='selected'" : '';
@endphp

@section('content')
@if ($id === 0)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('tracking') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('tracking') !!}
		</li>
		<li class="active">
			{!! res('tracking_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('tracking_edit') !!} <small>{{ $prefix.$g('id') }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('tracking') !!}
		</li>
		<li class="active">
			{{ $prefix.$g('id') }}
		</li>
	</ol>
</div>
@endif

<form action="{{ legacy_href() }}_admin/_tracking/save" method="post">

<input type="hidden" name="id" value="{{ $g('id') }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>

		    <a type="button" href="{{ legacy_href() }}admin/tracking" class="pull-right btn btn-warning waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
			    {!! res('back_to_list') !!}
		    </a>
		</div>
	</div>


	<div class="col-sm-6">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">
				@if ($id !== 0)
				<div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-8  form-control-static">
		                {{ $prefix.$g('id') }}
		            </div>
		        </div>         
		        @endif  
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('member_code') !!}</label>
		            <div class="col-md-8">
		                <input type="text" name="code" class="form-control validate[required]" value="{{ $g('code') }}" >
		            </div>
		        </div>                         
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('tracking') !!}</label>
		            <div class="col-md-8">
		                <input type="text" name="tracking" class="form-control validate[required]" value="{{ $g('tracking') }}" >
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('order_number') !!}</label>
		            <div class="col-md-8">
		                <input type="text" name="order" class="form-control" value="{{ $g('order') }}" >
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('remark_for_this_order') !!}</label>
		            <div class="col-md-8">
		                <input type="text" name="quote" class="form-control" value="{{ $g('quote') }}" >
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('ship_type') !!}</label>
		            <div class="col-md-5">
		                <select name="ship" class="form-control " onchange="GetProductType($('select[name=\'ship\'').val())" >
	                        <option {!! $sel('ship', '0') !!} value="0">{!! res('car') !!}</option>
	                        <option {!! $sel('ship', '1') !!} value="1">{!! res('ship') !!}</option>
	                    </select>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('type') !!}</label>
		            <div class="col-md-5" id="product_type">
		                
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('wood_protect') !!}</label>
		            <div class="col-md-5">
		                <select name="is_craft" class="form-control " >
	                        <option {!! $sel('is_craft', '0') !!} value="0">{!! res('disable') !!}</option>
	                        <option {!! $sel('is_craft', '1') !!} value="1">{!! res('active') !!}</option>
	                    </select>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('qc_product') !!}</label>
		            <div class="col-md-5">
		                <select name="is_qc" class="form-control " >
	                        <option {!! $sel('is_qc', '0') !!} value="0">{!! res('disable') !!}</option>
	                        <option {!! $sel('is_qc', '1') !!} value="1">{!! res('active') !!}</option>
	                    </select>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> รับเข้าไทยแล้ว?</label>
		            <div class="col-md-5">
		                <select name="is_checked" class="form-control " >
	                        <option {!! $sel('is_checked', '0') !!} value="0">{!! res('disable') !!}</option>
	                        <option {!! $sel('is_checked', '1') !!} value="1">{!! res('active') !!}</option>
	                    </select>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('payment_list') !!}</label>
		            <div class="col-md-8 form-control-static">
		            @if (Php74::eq($g('id'), ''))
-
		            @else
		            	<a target="_blank" href="{{ legacy_href() }}admin/export/{{ $g('payment_id') }}">{{ $payPrefix.$g('payment_id') }}</a>
		            @endif
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('lot_list').'/'.res('no') !!}</label>
		            <div class="col-md-8 form-control-static">
		            @if (Php74::eq($g('id'), ''))
-
		            @else
		            	<a target="_blank" href="{{ legacy_href() }}admin/lot/{{ $g('lot_id') }}">{{ $g('lot_name') }}</a>/{{ $g('no') }}
		            @endif
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
		            <div class="col-md-8" >
		                <textarea name="remark" rows="5" class="form-control">{{ $g('remark') }}</textarea>
		            </div>
		        </div>
		    </div>
		</div>

		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('pricing') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">
@foreach (['price_kg' => ['price_kg', true], 'price_cubic' => ['price_cubic', true], 'price_qc' => ['qc', false], 'price_repack' => ['repack', false], 'price_express' => ['express', false], 'price_else' => ['else', false], 'price_craft' => ['price_craft', false]] as $col => [$label, $req])
@if ($col === 'price_qc')

		        <div class="dashed"></div>

@endif
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res($label) !!}</label>
		            <div class="col-md-6">
			            <div class="input-group">
			            	<input type="text" name="{{ $col }}" class=" form-control{{ $req ? ' validate[required]' : '' }}" value="{{ $g($col) }}"  />
			            	<span class="input-group-addon">{!! res('thai_currency') !!}</span>
			            </div>
		            </div>
		        </div>
@endforeach
		    </div>
		</div>
	</div>


	<div class="col-sm-6">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('dimenstion') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">    
@foreach (['weight' => 'kg', 'width' => 'cm', 'height' => 'cm', 'long' => 'cm'] as $col => $unit)
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res($col) !!}</label>
		            <div class="col-md-6">
			            <div class="input-group">
			            	<input type="text" name="{{ $col }}" class="text-right addcomma form-control" onblur="Calculate();getPricing()" value="{{ $g($col) }}"  />
			            	<span class="input-group-addon">{!! res($unit) !!}</span>
			            </div>
		            </div>
		        </div>
@endforeach
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('amount') !!}</label>
		            <div class="col-md-4">
		            	<input type="text" name="amount"  class="text-right addcomma form-control" onblur="Calculate();getPricing()" value="{{ $g('amount') }}"  />
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('total_kg') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	<span class=" total_kg addcomma"></span>
		            	<span>{!! res('kg') !!}</span>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('total_cubic') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	<span class=" total_cubic addcommacubic"></span>
		            	<span>{!! res('cubic') !!}</span>
		            </div>
		        </div>

		        <div class="form-group">
		        	<label class="col-md-4 control-label"> </label>
		            <div class="col-md-4 form-control-static">
		            	<div class="checkbox checkbox-primary">
                            <input name="is_auto_price" type="checkbox" value="1" checked="checked" >
                            <label for="checkbox1">
                                คิดราคาอัตโนมัติหลังบันทึก
                            </label>
                        </div>
		            </div>
		        </div>
			</div>
	    </div>


	    <div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('detail_date') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">    
@foreach (['date_warehouse' => ['date_in', 'date_warehouse', 1], 'date_outofchina' => ['date_outofchina', 'date_outofchina', 2], 'date_thai' => ['date_arrive', 'date_arrive', 3]] as $col => [$label, $name, $n])
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res($label) !!}</label>
		            <div class="col-xs-6">
		            	<input autocomplete='off'  type="text" name="{{ $name }}" class="form-control datepicker" onchange="onsavelotstatus_{{ $n }}()" value="{!! $inputDate($g($col)) !!}"  />
		            </div>
		        </div>
@endforeach
			</div>
	    </div>
	</div>
</div>

</form>

<script>
$(function() {
	//Calculate();
	initialDatepicker2();
	GetProductType('{!! Alerts::js(e($g('ship'))) !!}', false);
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

	function Calculate() {
		var amount = $("input[name='amount']").val();
		var weight = $("input[name='weight']").val();

		var width = $("input[name='width']").val();
		var height = $("input[name='height']").val();
		var long = $("input[name='long']").val();
		var x = eval(amount) * ((eval(width) * eval(height) * eval(long) / 1000000));

		$(".total_kg").text(eval(amount) * eval(weight));
		$(".total_cubic").text(x)
		initialAddcommaText();
	}

	function GetProductType(id, get_price = true) {
		OpenLoading();
		$.ajax({
            type: 'POST',
            async: true,
            data : 'id={{ $id }}&ship=' + id,
            url: rooturl + '_admin/_tracking/load_product_type',
            success: function(data) {
            	$("#product_type").html(data);
            	CloseLoading();
            	if(get_price) {
            		getPricing();
            	}
            }
        });
	}

	function getPricing() {
		$.ajax({
            type: 'POST',
            async: true,
            data : 'product_type_id=' + $("select[name='shipping_type']").val() +
            		'&weight=' + $("input[name='weight']").val() + 
            		'&width=' + $("input[name='width']").val() +
            		'&height=' + $("input[name='height']").val() + 
            		'&long=' + $("input[name='long']").val() +
            		'&amount=' + $("input[name='amount']").val() + 
            		'&id=' + $("input[name='id']").val(),
            url: rooturl + '_admin/_tracking/load_pricing',
            success: function(data) {
            	data = $.parseJSON(data);

            	$("input[name='price_kg']").val(currency_number(eval(data.kg).toFixed(2)));
            	$("input[name='price_cubic']").val(currency_number(eval(data.cubic).toFixed(2)));
            }
        });
	}


	$("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	    }
	});

</script>
@endsection
