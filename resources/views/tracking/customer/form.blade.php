{{-- _tracking/form.php เดิม --}}
@extends('legacy.layout')

@php
    use App\Domain\Tracking\Php74;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $g = fn (string $k) => $d[$k] ?? null;
    $thaiDate = function ($v) {
        if (Php74::neq($v, '0000-00-00 00:00:00') && Php74::neq($v, '')) {
            return Format::getthaidate(explode(' ', (string) $v)[0]);
        }
        return '';
    };
@endphp

@section('content')
@if ($isNew)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('tracking') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
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
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
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

<form action="{{ legacy_href() }}_tracking/save" method="post">

<input type="hidden" name="id" value="{{ $g('id') }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>

		    <a type="button" href="{{ legacy_href() }}tracking" class="pull-right btn btn-warning waves-effect waves-light">
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
				@if (! $isNew)
				<div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-8  form-control-static">
		                {{ $prefix.$g('id') }}
		            </div>
		        </div>         
		        @endif                           
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('tracking') !!}</label>
		            @if ($allowEdit)
<div class="col-md-8">
				                <input type="text" name="tracking" class="form-control validate[required]" value="{{ $g('tracking') }}" >
				            </div>
		            @else
<div class="col-md-8 form-control-static">
		            			{{ $g('tracking') }}
		            			</div>
		            @endif
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('order_number') !!}</label>
		            @if ($allowEdit)
<div class="col-md-8">
				                <input type="text" name="order" class="form-control " value="{{ $g('order') }}" >
				            </div>
		            @else
<div class="col-md-8 form-control-static">
		            			{{ $g('order') }}
		            			</div>
		            @endif
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('remark_for_this_order') !!}</label>
		            <div class="col-md-8">
		                <input type="text" name="quote" class="form-control" value="{{ $g('quote') }}" >
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('ship_type') !!}</label>
		            @if ($allowEdit)
		            		<div class="col-md-5">
				                <select name="ship" class="form-control " onchange="GetProductType($('select[name=\'ship\'').val())" >
			                        <option {!! Php74::eq($g('ship'), '0') ? "selected='selected'" : '' !!} value="0">{!! res('car') !!}</option>
			                        <option {!! Php74::eq($g('ship'), '1') ? "selected='selected'" : '' !!} value="1">{!! res('ship') !!}</option>
			                    </select>
				            </div>
		            @else
<div class="col-md-8 form-control-static">
		            			{!! Status::getShipType($g('ship')) !!}
		            			</div>
		            @endif
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('type') !!}</label>
		            <div class="col-md-5 form-control-static" >
		                @if (Php74::eq($g('product_type_id'), 0))
{!! res('not_set') !!}
		                @else
{{ $g('product_type_name') }}
		                @endif
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('wood_protect') !!}</label>
		            
		            @if (Php74::eq($g('lot_id'), 0))
		            <div class="col-md-5">
		                <select name="is_craft" class="form-control " >
	                        <option {!! Php74::eq($g('is_craft'), '0') ? "selected='selected'" : '' !!} value="0">{!! res('disable') !!}</option>
	                        <option {!! Php74::eq($g('is_craft'), '1') ? "selected='selected'" : '' !!} value="1">{!! res('active') !!}</option>
	                    </select>
		            </div>
		            @else
<div class="col-md-5 form-control-static">{!! Php74::eq($g('is_craft'), 0) ? res('disable') : '' !!}{!! Php74::eq($g('is_craft'), 1) ? res('active') : '' !!}</div>
		            @endif
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('qc') !!}</label>
		            @if (Php74::eq($g('lot_id'), 0))
		            <div class="col-md-5">
		                <select name="is_qc" class="form-control " >
	                        <option {!! Php74::eq($g('is_qc'), '0') ? "selected='selected'" : '' !!} value="0">{!! res('disable') !!}</option>
	                        <option {!! Php74::eq($g('is_qc'), '1') ? "selected='selected'" : '' !!} value="1">{!! res('active') !!}</option>
	                    </select>
		            </div>
		            @else
<div class="col-md-5 form-control-static">{!! Php74::eq($g('is_qc'), 0) ? res('disable') : '' !!}{!! Php74::eq($g('is_qc'), 1) ? res('active') : '' !!}</div>
		            @endif
		            
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('payment_list') !!}</label>
		            <div class="col-md-8 form-control-static">
		            	@if ($isNew)
-
		            	@else
<a href="{{ legacy_href() }}export/{{ $g('payment_id') }}">{{ $payPrefix.$g('payment_id') }}</a>
		            	@endif
		                
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('lot_list').'/'.res('no') !!}</label>
		            <div class="col-md-8 form-control-static">
		            	@if ($isNew)
-/-
		            	@else
<a href="{{ legacy_href() }}lot/{{ $g('lot_id') }}">{{ $g('lot_name') }}</a>/{{ $g('no') }}
		            	@endif
		            </div>
		        </div>
		    </div>
		</div>

		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('price') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('price_kg') !!}</label>
		            <div class="col-md-6 form-control-static">
			            <div class="input-group currency">
			            	{!! Format::numberformat($g('price_kg')) !!}
			            </div>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('price_cubic') !!}</label>
		            <div class="col-md-6 ">
			            <div class="input-group form-control-static">
			            	{!! Format::numberformat($g('price_cubic')) !!}
			            </div>
		            </div>
		        </div>

		        <div class="dashed"></div>
@foreach (['price_qc' => 'qc', 'price_repack' => 'repack', 'price_express' => 'express', 'price_else' => 'else', 'price_craft' => 'price_craft'] as $col => $label)

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res($label) !!}</label>
		            <div class="col-md-6">
		            	<div class="input-group form-control-static">
			            	{!! Format::numberformat($g($col)) !!}
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
@foreach (['weight', 'width', 'height', 'long'] as $col)
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res($col) !!}</label>
		            <div class="col-md-6">
		            	<div class="input-group form-control-static">
			            	{!! Format::numberformatone($g($col)) !!}
			            	{!! res('kg') !!}
			            </div>
		            </div>
		        </div>
@endforeach
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('amount') !!}</label>
		            <div class="col-md-4">
		            	<div class="input-group form-control-static">
			            	{!! Format::numberformatzero($g('amount')) !!}
			            	{!! res('piece') !!}
			            </div>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('total_kg') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	<span class="addcomma">{{ Php74::str(Php74::num($g('weight')) * Php74::num($g('amount'))) }}</span>
		            	<span>{!! res('kg') !!}</span>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('total_cubic') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	<span class="addcommacubic">{{ Php74::str(((Php74::num($g('width')) * Php74::num($g('height')) * Php74::num($g('long'))) / 1000000) * Php74::num($g('amount'))) }}</span>
		            	<span>{!! res('cubic') !!}</span>
		            </div>
		        </div>
			</div>
	    </div>


	    <div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('detail_date') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">    
@foreach (['date_warehouse' => 'date_in', 'date_outofchina' => 'date_outofchina', 'date_thai' => 'date_arrive'] as $col => $label)
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res($label) !!}</label>
		            <div class="col-xs-6 form-control-static">
		            	@php $date = $thaiDate($g($col)); @endphp
{!! $date !== '' ? $date : '-' !!}
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
	Calculate();
	initialDatepicker2();
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
