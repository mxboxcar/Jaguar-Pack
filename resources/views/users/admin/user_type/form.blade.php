{{-- _admin/_user_type/form.php เดิม (สิทธิ์ admin_user_type ตรวจที่ middleware) --}}
@php($eq = [\App\Domain\Users\Php74::class, 'eq'])
@extends('legacy.layout')

@section('content')
@if ($id === 0)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('user_type') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('user_type') !!}
		</li>
		<li class="active">
			{!! res('user_type_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('user_type_edit') !!} <small>{{ $prefix }}{{ $data->id ?? '' }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('user_type') !!}
		</li>
		<li class="active">
			{{ $prefix }}{{ $data->id ?? '' }}
		</li>
	</ol>
</div>
@endif

<form action="{{ legacy_href() }}_admin/_user_type/save" method="post">

<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>

		    <a type="button" href="{{ legacy_href() }}admin/user_type" class="pull-right btn btn-warning waves-effect waves-light">
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
				@if (! $eq($id, ''))
				<div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-7  form-control-static">
		                {{ $productPrefix }}{{ $data->id ?? '' }}
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
		            <label class="col-md-3 control-label"> </label>
		            <div class="col-md-7">
		                <div class="checkbox checkbox-primary">
                            <input name="is_default" type="checkbox" value="1" @if ($eq($data->is_default ?? null, 1))checked="checked"@endif>
                            <label for="checkbox1">
                                {!! res('defaulting') !!}
                            </label>
                        </div>

		            </div>
		        </div>


		        <div class="form-group">
		            <label class="col-md-3 control-label"> </label>
		            <div class="col-md-7">
		                <div class="checkbox checkbox-primary">
                            <input name="is_show" type="checkbox" value="1" @if ($eq($data->is_show ?? null, 1))checked="checked"@endif>
                            <label for="checkbox1">
                                {!! res('is_show') !!}
                            </label>
                        </div>

		            </div>
		        </div>
		        
		    </div>
		</div>
	</div>


	<div class="col-sm-12">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('pricing') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
@include('users.admin.partials.pricing', ['m' => $m, 'amount' => true])

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
