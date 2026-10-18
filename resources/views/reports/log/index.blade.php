{{-- _admin/_log/index.php เดิม (สิทธิ์ admin_log ตรวจที่ route) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('log') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			{!! res('log') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	
</div>
<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_admin/_log/grid', 1, '');

</script>
@endsection
