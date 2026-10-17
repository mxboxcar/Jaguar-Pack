{{-- _admin/_warehouse/index.php เดิม (สิทธิ์ admin_warehouse ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('warehouse_list') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			{!! res('warehouse_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">
	    <a type="button" href="#" onclick="Create();return false;" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-plus"></i></span>
		    {!! res('add_new_list') !!}
	    </a>
	</div>
</div>

<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_admin/_warehouse/grid', 1, '|{!! \App\Support\Legacy\Alerts::js($status) !!}');

    function Create() {
    	$.ajax({
            type: 'POST',
            async: true,
            url: rooturl + '_admin/_warehouse/save_new_warehouse',
            success: function(data) {
            	eval(data);
            }
        });
    }
</script>
@endsection
