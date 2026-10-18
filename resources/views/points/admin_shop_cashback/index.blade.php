{{-- _admin/_shop_cashback/index.php เดิม (สิทธิ์ admin_shop_cashback ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('shop_cashback') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
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
	
</div>

@include('points.partials.cashback_nav', ['base' => 'admin/'])

<div id="gridAjax" class="col-xs-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_admin/_shop_cashback/grid', 1, '|{!! \App\Support\Legacy\Alerts::js($status) !!}');
</script>
@endsection
