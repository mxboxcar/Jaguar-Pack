{{-- _tracking/index.php เดิม (สิทธิ์ tracking ตรวจที่ middleware legacy.perm; PACK: ปุ่มเพิ่มเฉพาะ $canAdd = ผู้ใช้ประเภท 2/ลูกของ 2) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('tracking_list') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('tracking') !!}
		</li>
		<li class="active">
			{!! res('tracking_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">
<!-- 	    <button onclick="DeleteAll()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-times"></i></span>
		    {!! res('delete_at_select') !!}
	    </button> -->
@if ($canAdd)
	    <a type="button" href="{{ legacy_href() }}tracking/create" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-plus"></i></span>
		    {!! res('add_new_list') !!}
	    </a>
@endif
	</div>
</div>

<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_tracking/grid', 1, '');
</script>
@endsection
