{{-- _admin/_role/index.php เดิม: หัวหน้าแสดงก่อน แล้วตรวจ admin_role --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('role_list') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('role') !!}
		</li>
		<li class="active">
			{!! res('role_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">
	    <a type="button" href="{{ legacy_href() }}admin/role/create" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-plus"></i></span>
		    {!! res('add_new_list') !!}
	    </a>
	</div>
</div>

<div id="gridAjax" class="col-sm-12"></div>
@if (perm('admin_role'))
<script type="text/javascript">
    loadAjaxGrid('_admin/_role/grid', 1, '');
</script>
@else
{!! \App\Support\Legacy\Alerts::permissionPage('admin_role') !!}
@endif
@endsection
