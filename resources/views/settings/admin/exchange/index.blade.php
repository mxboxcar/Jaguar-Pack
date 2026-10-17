{{-- _admin/_exchange/index.php เดิม (สิทธิ์ admin_exchange ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
    <h4 class="page-title">{!! res('exchange_list') !!}</h4>
    <ol class="breadcrumb">
        <li>
            <a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
        </li>
        <li class="active">
            {!! res('exchange_list') !!}
        </li>
    </ol>
</div>
<div class="col-sm-5 pull-right">
    <div class="button-list">
        
    </div>
</div>



<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_admin/_exchange/grid', 1, '|{{ $status }}');
</script>
@endsection
