{{-- _admin/_balance_company/index.php เดิม (สิทธิ์ admin_balance_company ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('balance_company') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li class="active">
			{!! res('balance_company') !!}
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
<div id="gridAjax" class="col-xs-12"></div>

<script type="text/javascript">
    loadAjaxGrid('_admin/_balance_company/grid', 1, '');
</script>
@endsection
