{{-- _admin/_user/index.php เดิม: หัวหน้าแสดงก่อน แล้วตรวจ admin_user_view || admin_user --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('employee_list') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('employee') !!}
		</li>
		<li class="active">
			{!! res('employee_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">
	   <!--  <button onclick="DeleteAll()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-times"></i></span>
		    {!! res('delete_at_select') !!}
	    </button> -->

	    <a type="button" href="{{ legacy_href() }}admin/user/create" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-plus"></i></span>
		    {!! res('add_new_list') !!}
	    </a>
	</div>
</div>

<div id="gridAjax" class="col-sm-12"></div>
@if (perm('admin_user_view', 'admin_user'))
<script type="text/javascript">
    loadAjaxGrid('_admin/_user/grid', 1, '|||{{ $status }}');
</script>
@else
{!! \App\Support\Legacy\Alerts::permissionPage('admin_user') !!}
@endif
@endsection
