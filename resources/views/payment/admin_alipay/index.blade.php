{{-- _admin/_alipay/index.php เดิม (สิทธิ์ admin_alipay ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('alipay_list') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('alipay') !!}
		</li>
		<li class="active">
			{!! res('alipay_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">
	    
	</div>
</div>

<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_admin/_alipay/grid', 1, '|{{ $status }}');
</script>
@endsection
