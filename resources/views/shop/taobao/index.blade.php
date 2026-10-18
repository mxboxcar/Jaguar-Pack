{{-- _taobao/index.php เดิม (page=taobao) --}}
@extends('legacy.layout')

@section('content')
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
			{!! res('taobao_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">

	    <a type="button" href="{{ legacy_href() }}taobao/create" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-plus"></i></span>
		    {!! res('add_new_list') !!}
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

<div id="gridAjax" class="col-xs-12"></div>
@if (! perm('taobao'))
{!! \App\Support\Legacy\Alerts::permissionPage('taobao') !!}
@else
<script type="text/javascript">
    loadAjaxGrid('_taobao/grid', 1, '');
</script>
@endif
@endsection
