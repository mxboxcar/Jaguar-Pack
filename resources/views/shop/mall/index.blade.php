{{-- _mall/index.php เดิม (page=mall) --}}
@extends('legacy.layout')

@section('content')

<style>
body {
	background-color: #ebeff2!important;
}
</style>
<div class="col-sm-7">
	<h4 class="page-title">{!! res('mall') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('mall') !!}
		</li>
		<li class="active">
			{!! res('mall_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">

	</div>
</div>



<div id="panel-modal" class="mall_detail_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading"> 
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
                    <h3 class="panel-title">{!! res('cancel_this') !!}</h3> 
                </div> 
                <div class="panel-body"> 
	                
                </div> 
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                </div>
                
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->




<div id="gridAjax" class="col-sm-12"></div>
@if (! perm('mall'))
{!! \App\Support\Legacy\Alerts::permissionPage('mall') !!}
@else
<script type="text/javascript">
    loadAjaxGrid('_mall/grid', 1, '{!! \App\Support\Legacy\Alerts::js($search) !!}|{!! \App\Support\Legacy\Alerts::js($status) !!}');
</script>
@endif
@endsection
