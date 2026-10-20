{{-- _findproduct/form.php ของ SR (page=findproduct_detail) — ฟอร์มสำเนาของ product_type (desc/kg/cubic/amount/sale_*/is_default ไม่มีใน find_product = ว่าง) --}}
@extends('legacy.layout')

@section('content')
@if ($id === '0')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('findproduct') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('findproduct') !!}
		</li>
		<li class="active">
			{!! res('findproduct_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('findproduct_edit') !!} <small>{{ $prefixFind }}{{ $data->id ?? '' }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('findproduct') !!}
		</li>
		<li class="active">
			{{ $prefixFind }}{{ $data->id ?? '' }}
		</li>
	</ol>
</div>
@endif

<form action="{{ legacy_href() }}_findproduct/save" method="post">

<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>

		    <a type="button" href="{{ legacy_href() }}findproduct" class="pull-right btn btn-warning waves-effect waves-light">
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
				@if ($id !== '')
				<div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-7  form-control-static">
		                {{ $prefixType }}{{ $data->id ?? '' }}
		            </div>
		        </div>         
		        @endif
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('name') !!}</label>
		            <div class="col-md-7">
		                <input type="text" name="name" class="form-control validate[required]" value="{{ $data->name ?? '' }}" >
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('description') !!}</label>
		            <div class="col-md-7">
		                <input type="text" name="desc" class="form-control" value="{{ $data->desc ?? '' }}" >
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('type') !!}</label>
		            <div class="col-md-7">
		                <select name="ship" class="form-control " >
	                        <option @if ($data !== null && (string) $data->type === '0')selected='selected'@endif value="0">{!! res('car') !!}</option>
	                        <option @if ($data !== null && (string) $data->type === '1')selected='selected'@endif value="1">{!! res('ship') !!}</option>
	                    </select>
		            </div>
		        </div>


		        <div class="form-group">
		            <label class="col-md-3 control-label"> </label>
		            <div class="col-md-7">
		                <div class="checkbox checkbox-primary">
                            <input name="is_default" type="checkbox" value="1" @if ((string) ($data->is_default ?? '') === '1')checked="checked"@endif>
                            <label for="checkbox1">
                                {!! res('defaulting') !!}
                            </label>
                        </div>

		            </div>
		        </div>
		    </div>
		</div>
	</div>


	<div class="col-sm-6">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('pricing') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
				<div class="form-horizontal" role="form">    
			        <div class="form-group">
			            <label class="col-md-4 control-label">{!! res('kg') !!}</label>
			            <div class="col-md-7">
		                	<input type="text" name="kg" class="form-control validate[required]" value="{{ $data->kg ?? '' }}" >
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label">{!! res('cubic') !!}</label>
			            <div class="col-md-7">
			                <input type="text" name="cubic" class="form-control validate[required]" value="{{ $data->cubic ?? '' }}" >
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label">{!! res('amount') !!}</label>
			            <div class="col-md-7">
			                <input type="text" name="amount" class="form-control validate[required]" value="{{ $data->amount ?? '' }}" >
			            </div>
			        </div>

			        <p class="text-muted font-13 m-b-30">
						{!! res('sale_give') !!}
			        </p>                                
			        <div class="form-group">
			            <label class="col-md-4 control-label">{!! res('kg') !!}</label>
			            <div class="col-md-7">
		                	<input type="text" name="sale_kg" class="form-control validate[required]" value="{{ $data->sale_kg ?? '' }}" >
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label">{!! res('cubic') !!}</label>
			            <div class="col-md-7">
			                <input type="text" name="sale_cubic" class="form-control validate[required]" value="{{ $data->sale_cubic ?? '' }}" >
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label">{!! res('amount') !!}</label>
			            <div class="col-md-7">
			                <input type="text" name="sale_amount" class="form-control validate[required]" value="{{ $data->sale_amount ?? '' }}" >
			            </div>
			        </div>
				</div>
		    </div>


		</div>
	</div>

</form>

<script>
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

</script>
@endsection
