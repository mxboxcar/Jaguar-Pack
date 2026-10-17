{{-- _goodsconfirm/index.php เดิม (สิทธิ์ goodsconfirm ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('goodsconfirm_list') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('goodsconfirm') !!}
		</li>
		<li class="active">
			{!! res('goodsconfirm_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	
</div>

<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_goodsconfirm/grid', 1, '|{!! \App\Support\Legacy\Alerts::js($status) !!}');
</script>
@endsection
