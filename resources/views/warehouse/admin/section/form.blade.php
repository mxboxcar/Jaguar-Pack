{{-- _admin/_warehouse_section/form.php เดิม (สิทธิ์ admin_warehouse_section ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')

@section('content')
@if($id === 0)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('warehouse_section') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('warehouse_section') !!}
		</li>
		<li class="active">
			{!! res('warehouse_section_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('warehouse_section_edit') !!} <small>{{ $prefix }}{{ $d['id'] ?? '' }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('warehouse_section') !!}
		</li>
		<li class="active">
			{{ $prefix }}{{ $d['id'] ?? '' }}
		</li>
	</ol>
</div>
@endif

<form action="{{ legacy_href() }}_admin/_warehouse_section/save" method="post">

<input type="hidden" name="id" value="{{ $d['id'] ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>

		    <a type="button" href="{{ legacy_href() }}admin/warehouse_section" class="pull-right btn btn-warning waves-effect waves-light">
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
				@if($id !== 0)
				<div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-7  form-control-static">
		                {{ $prefix }}{{ $d['id'] ?? '' }}
		            </div>
		        </div>         
		        @endif                           
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('name') !!}</label>
		            <div class="col-md-7">
		                <input type="text" name="name" class="form-control validate[required]" value="{{ $d['name'] ?? '' }}" >
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('width') !!}</label>
		            <div class="col-md-7">
		                <div class="input-group">
	                		<input  type="text" name="width" class="form-control addcomma  text-right validate[required]" value="{{ $d['width'] ?? '' }}" >
		                	<span class="input-group-addon">
				        		{!! res('cm') !!}
				        	</span>
		                </div>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('height') !!}</label>
		            <div class="col-md-7">
		                <div class="input-group">
	                		<input  type="text" name="height" class="form-control addcomma  text-right validate[required]" value="{{ $d['height'] ?? '' }}" >
		                	<span class="input-group-addon">
				        		{!! res('cm') !!}
				        	</span>
		                </div>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('long') !!}</label>
		            <div class="col-md-7">
		                <div class="input-group">
	                		<input  type="text" name="long" class="form-control addcomma  text-right validate[required]" value="{{ $d['long'] ?? '' }}" >
		                	<span class="input-group-addon">
				        		{!! res('cm') !!}
				        	</span>
		                </div>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('map') !!}</label>
		            <div class="col-md-7">
		            	{!! $upload !!}
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
