{{-- _admin/_printorder/index.php (SR) — สิทธิ์ admin_printorder ตรวจที่ middleware legacy.perm --}}
@extends('legacy.layout')
@php
    use App\Domain\Order\Html;
@endphp

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('printorder_list') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			{!! res('printorder_list') !!}
		</li>
	</ol>
</div>


<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_admin/_printorder/grid', 1, '|-2|{!! Html::js($status) !!}');
</script>
@endsection
