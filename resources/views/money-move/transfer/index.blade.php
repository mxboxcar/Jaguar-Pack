{{-- _transfer/index.php / _admin/_transfer/index.php เดิม --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('transfer_list') !!}</h4>
	<ol class="breadcrumb">
		<li>
@if ($admin)
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
@else
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
@endif
		</li>
		<li>
			{!! res('transfer') !!}
		</li>
		<li class="active">
			{!! res('transfer_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">
@unless ($admin)

	    <a type="button" href="{{ legacy_href() }}transfer/create" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-plus"></i></span>
		    {!! res('add_new_list') !!}
	    </a>
@endunless
	</div>
</div>

<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('{{ $admin ? '_admin/_transfer/grid' : '_transfer/grid' }}', 1, '|{!! \App\Support\Legacy\Alerts::js(e($status)) !!}');
</script>
@endsection
