{{-- _findproduct/index.php ของ SR (page=findproduct) — สิทธิ์ findproduct (ไม่มีใน role_detail ของ SR: ไม่มีใครเข้าได้ ทั้งเว็บเดิมและที่นี่) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('findproduct') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('role') !!}
		</li>
		<li class="active">
			{!! res('findproduct') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">

	    <a type="button" href="{{ legacy_href() }}findproduct/create" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-plus"></i></span>
		    {!! res('add_new_list') !!}
	    </a>
	</div>
</div>

<div id="gridAjax" class="col-sm-12"></div>
@if (! perm('findproduct'))
{!! \App\Support\Legacy\Alerts::permissionPage('findproduct') !!}
@else
<script type="text/javascript">
    loadAjaxGrid('_findproduct/grid', 1, '');
</script>
@endif
@endsection
