{{-- _admin/_product_type/index.php เดิม (สิทธิ์ admin_product_type ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('product_type_list') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('role') !!}
		</li>
		<li class="active">
			{!! res('product_type_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">
	    <button onclick="DeleteAll()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-times"></i></span>
		    {!! res('delete_at_select') !!}
	    </button>

	    <a type="button" href="{{ legacy_href() }}admin/product_type/create" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-plus"></i></span>
		    {!! res('add_new_list') !!}
	    </a>
	</div>
</div>

<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_admin/_product_type/grid', 1, '');
</script>
@endsection
