{{-- _refill/index.php / _admin/_refill/index.php เดิม --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('refill_list') !!}</h4>
	<ol class="breadcrumb">
		<li>
@if ($admin ?? false)
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
@else
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('refill') !!}
@endif
		</li>
		<li class="active">
			{!! res('refill_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">

	    <a type="button" href="{{ legacy_href() }}{{ ($admin ?? false) ? 'admin/' : '' }}refill/create" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-plus"></i></span>
		    {!! res('add_new_list') !!}
	    </a>
	</div>
</div>

<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('{{ ($admin ?? false) ? '_admin/_refill/grid' : '_refill/grid' }}', 1, '|{!! \App\Support\Legacy\Alerts::js(e($status)) !!}');
</script>
@endsection
