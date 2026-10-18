{{-- _lost_package/index.php / _admin/_lost_package/index.php เดิม --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('lost_package_list') !!}</h4>
	<ol class="breadcrumb">
		<li>
@if ($admin)
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
@else
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
@endif
		</li>
		<li>
			{!! res('lost_package') !!}
		</li>
		<li class="active">
			{!! res('lost_package_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">
@if ($admin)

	    <!-- <a type="button" href="{{ legacy_href() }}lost_package/create" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-plus"></i></span>
		    {!! res('add_new_list') !!}
	    </a> -->
@else

	    <a type="button" href="{{ legacy_href() }}lost_package/create" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-plus"></i></span>
		    {!! res('add_new_list') !!}
	    </a>
@endif
	</div>
</div>

<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('{{ $admin ? '_admin/_lost_package/grid' : '_lost_package/grid' }}', 1, '|{!! \App\Support\Legacy\Alerts::js(e($status)) !!}');
</script>
@endsection
