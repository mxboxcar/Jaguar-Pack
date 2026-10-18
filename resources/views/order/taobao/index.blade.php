{{-- _admin/_taobao/index.php เดิม (สิทธิ์ admin_taobao ตรวจที่ middleware) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('taobao') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
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
	
</div>

<div class="col-sm-12">
	<div class="button-list text-center">
		<a type="button" href="{{ legacy_href() }}admin/taobao" class=" btn btn-primary  waves-effect waves-light">
		    <span class="btn-label"><i class="fa  fa-list-ol"></i></span>
		    {!! res('taobao') !!}
	    </a>
	    <a type="button" href="{{ legacy_href() }}admin/shop_cashback" class=" btn btn-inverse  waves-effect waves-light">
		    <span class="btn-label"><i class="fa  fa-undo"></i></span>
		    {!! res('shop_cashback') !!}
	    </a>
	    <a type="button" href="{{ legacy_href() }}admin/balance_cn" class=" btn btn-inverse  waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-cny"></i></span>
		    {!! res('balance_cn') !!}
	    </a>
	</div>
</div>

<div id="gridAjax" class="col-xs-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_admin/_taobao/grid', 1, '|{{ $status }}');
</script>
@endsection
