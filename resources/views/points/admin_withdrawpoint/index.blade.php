{{-- _admin/_withdrawpoint/index.php เดิม (สิทธิ์ admin_withdrawpoint ตรวจที่ middleware legacy.perm; /status/{n} เดิมไม่ได้ใช้ค่า status) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('withdrawpoint_list') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li class="active">
			{!! res('withdrawpoint_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">
	    
	</div>
</div>



@include('points.partials.transfer_modal')

<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_admin/_withdrawpoint/grid', 1, '');
</script>
@endsection
