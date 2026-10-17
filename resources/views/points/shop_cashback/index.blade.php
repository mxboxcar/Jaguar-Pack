{{-- _shop_cashback/index.php เดิม (สิทธิ์ shop_cashback ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('shop_cashback') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('shop_cashback') !!}
		</li>
		<li class="active">
			{!! res('shop_cashback_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">

	    <a type="button" href="{{ legacy_href() }}shop_cashback/create" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-plus"></i></span>
		    {!! res('add_new_list') !!}
	    </a>
	</div>
</div>

@include('points.partials.cashback_nav', ['base' => ''])

<div id="gridAjax" class="col-xs-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_shop_cashback/grid', 1, '');
</script>
@endsection
