{{-- _admin/_shipping_type/form.php เดิม (สิทธิ์ admin_shipping_type ที่ middleware) — ค่าจาก DB escape ด้วย {{ }} (เดิม echo ดิบ) --}}
@php use App\Domain\Settings\Php74; @endphp
@extends('legacy.layout')

@section('content')
@if($id === 0)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('shipping_type') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('shipping_type') !!}
		</li>
		<li class="active">
			{!! res('shipping_type_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('shipping_type_edit') !!} <small>{{ $prefix.($data->id ?? '') }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('shipping_type') !!}
		</li>
		<li class="active">
			{{ $prefix.($data->id ?? '') }}
		</li>
	</ol>
</div>
@endif

<form action="{{ legacy_href() }}_admin/_shipping_type/save" method="post">

<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>

		    <a type="button" href="{{ legacy_href() }}admin/shipping_type" class="pull-right btn btn-warning waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
			    {!! res('back_to_list') !!}
		    </a>
		</div>
	</div>


	<div class="col-sm-7">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">
				@if($id !== 0)
				<div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-7  form-control-static">
		                {{ $prefix.($data->id ?? '') }}
		            </div>
		        </div>         
		        @endif                           
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('name') !!}</label>
		            <div class="col-md-7">
		                <input type="text" name="name" class="form-control" value="{{ $data->name ?? '' }}" >
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('price_thai_storage') !!}</label>
		            <div class="col-md-7">
			            <div class="  input-group ">
			                <input type="text" name="price_thai_storage" class="form-control" value="{{ $data->price_thai_storage ?? '' }}" >
			                <span class="input-group-addon">
				        		{!! res('thai_currency') !!}
				        	</span>
		                </div>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-3 control-label"> </label>
		            <div class="col-md-7">
		                <div class="checkbox checkbox-primary">
                            <input name="is_quick_show" type="checkbox" value="1" @if(Php74::eq($data->is_quick_show ?? null, 1))checked="checked"@endif>
                            <label for="checkbox1">
                                {!! res('defaulting') !!}
                            </label>
                        </div>

		            </div>
		        </div>
		        

		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('type') !!}</label>
		            <div class="col-md-7">
			            <div class="radio radio-primary" onclick="ChangeType(0)">
                            <input type="radio" name="is_use_rate" id="radio1" value="0" checked="checked">
                            <label for="radio1">
                                {!! res('use_by_fixed') !!}
                            </label>
                        </div>
                        <div class="radio radio-primary" onclick="ChangeType(1)">
                            <input type="radio" name="is_use_rate" id="radio2" value="1" @if(Php74::eq($data->is_use_rate ?? null, 1))checked='checked'@endif>
                            <label for="radio2">
                                {!! res('use_by_calculate_kg') !!}
                            </label>
                        </div>
                        <div class="radio radio-primary" onclick="ChangeType(2)">
                            <input type="radio" name="is_use_rate" id="radio3" value="2" @if(Php74::eq($data->is_use_rate ?? null, 2))checked='checked'@endif>
                            <label for="radio3">
                                {!! res('use_by_calculate_cubic') !!}
                            </label>
                        </div>
		            </div>
		        </div>


			<div class="by_fixed" {!! Php74::eq($data->is_use_rate ?? null, 0) ? "style='display:block'" : "style='display:none'" !!}>
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('price_extened_want') !!}</label>
		            <div class="col-md-7">
			            <div class="  input-group ">
			                <input type="text" name="price_extened" class="form-control" value="{{ $data->price_extened ?? '' }}" >
			                <span class="input-group-addon">
				        		{!! res('thai_currency') !!}
				        	</span>
		                </div>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('price_thai_shipping') !!}</label>
		            <div class="col-md-7">
			            <div class="  input-group ">
			                <input type="text" name="price_thai_shipping" class="form-control" value="{{ $data->price_thai_shipping ?? '' }}" >
			                <span class="input-group-addon">
				        		{!! res('thai_currency') !!}
				        	</span>
		                </div>
		            </div>
		        </div>

		        
		    </div>


		    <div class="by_cal_1" {!! Php74::eq($data->is_use_rate ?? null, 1) ? "style='display:block'" : "style='display:none'" !!}>
		    	
		    </div>
		    <div class="by_cal_2" {!! Php74::eq($data->is_use_rate ?? null, 2) ? "style='display:block'" : "style='display:none'" !!}>
		    	
		    </div>

		</div>
	</div>
</div>

</form>

<script>
	ChangeType({{ $data->is_use_rate ?? '' }});
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

	function ChangeType(value) {
		if(value == 0) {
			$('.by_fixed').css('display', 'block');
			$('.by_cal_1').css('display', 'none');
			$('.by_cal_2').css('display', 'none');
		}
		if(value == 1) {
			$('.by_fixed').css('display', 'none');
			$('.by_cal_1').css('display', 'block');
			$('.by_cal_2').css('display', 'none');
			LoadCal1();
		}
		if(value == 2) {
			$('.by_fixed').css('display', 'none');
			$('.by_cal_1').css('display', 'none');
			$('.by_cal_2').css('display', 'block');
			LoadCal2();
		}
	}

	function LoadCal1() {
		$.ajax({
			type: 'POST',
			async: true,
			data : 'id=' + $("input[name='id']").val() ,
		  	url: rooturl + '_admin/_shipping_type/load_cal_1',
			success: function(data) {
				$(".by_cal_1").html(data);
			}
		});
	}
	function LoadCal2() {
		$.ajax({
			type: 'POST',
			async: true,
			data : 'id=' + $("input[name='id']").val() ,
		  	url: rooturl + '_admin/_shipping_type/load_cal_2',
			success: function(data) {
				$(".by_cal_2").html(data);
			}
		});
	}

	function addnew1() {
		ConfirmBox(function() {
			var min = $("input[name='min_value']").val();
			var max = $("input[name='max_value']").val();
			var price = $("input[name='cal_1_price']").val();
			var isresult = true;

			if(isresult) {
				$.ajax({
					type: 'POST',
					async: true,
					data : 'id=' + $("input[name='id']").val() + 
							'&min=' + $("input[name='min_value']").val() + 
							'&max=' + $("input[name='max_value']").val() +
							'&price=' + $("input[name='cal_1_price']").val() ,
				  	url: rooturl + '_admin/_shipping_type/save_new_price_by_kg',
					success: function(data) {
						eval(data);
					}
				});
			}
		});
	}

	function addnew2() {
		ConfirmBox(function() {
			var min = $("input[name='min_value']").val();
			var max = $("input[name='max_value']").val();
			var price = $("input[name='cal_1_price']").val();
			var isresult = true;

			if(isresult) {
				$.ajax({
					type: 'POST',
					async: true,
					data : 'id=' + $("input[name='id']").val() + 
							'&min=' + $("input[name='min_value_2']").val() + 
							'&max=' + $("input[name='max_value_2']").val() +
							'&price=' + $("input[name='cal_2_price']").val() ,
				  	url: rooturl + '_admin/_shipping_type/save_new_price_by_cubic',
					success: function(data) {
						eval(data);
					}
				});
			}
		});
	}

	function delete_cal(id) {
		ConfirmBox(function() {
			$.ajax({
				type: 'POST',
				async: true,
				data : 'id=' + id ,
			  	url: rooturl + '_admin/_shipping_type/delete_cal',
				success: function(data) {
					eval(data);
					LoadCal1();
					LoadCal2();
				}
			});
		});
	}



	

</script>
@endsection
