{{-- _admin/_mallstock/index.php เดิม (page=admin_mall_stock) — route ตรวจ legacy.perm:admin_mall_stock --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('stock_remain') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			{!! res('stock_remain') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">
	    <a type="button" href="#" onclick="load_amount(0);return false;" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-plus"></i></span>
		    {!! res('add_new_list') !!}
	    </a>
	</div>
</div>
<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_admin/_mallstock/grid', 1, '');

</script>
@endsection
