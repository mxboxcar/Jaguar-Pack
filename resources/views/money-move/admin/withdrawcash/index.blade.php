{{-- _admin/_withdrawcash/index.php เดิม --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
    <h4 class="page-title">{!! res('withdrawcash_list') !!}</h4>
    <ol class="breadcrumb">
        <li>
            <a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
        </li>
        <li class="active">
            {!! res('withdrawcash_list') !!}
        </li>
    </ol>
</div>
<div class="col-sm-5 pull-right">
    <div class="button-list">
        
    </div>
</div>



<div id="gridAjax" class="col-sm-12"></div>
@if ($denied ?? false)
{{-- SR: ตรวจ admin_withdrawpoint หลังพิมพ์หัวหน้า แล้วแจ้งปฏิเสธด้วยชื่อ admin_withdrawcash --}}
{!! \App\Support\Legacy\Alerts::permissionPage('admin_withdrawcash') !!}
@else
<script type="text/javascript">
    loadAjaxGrid('_admin/_withdrawcash/grid', 1, '|{!! \App\Support\Legacy\Alerts::js(e($status)) !!}');
</script>
@endif
@endsection
