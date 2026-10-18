{{-- _admin/_report/index.php เดิม --}}
@extends('legacy.layout')

@php
    $months = ['01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน', '05' => 'พฤษภาคม', '06' => 'มิถุนายน',
        '07' => 'กรกฎาคม', '08' => 'สิงหาคม', '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'];
@endphp

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('report') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			{!! res('report') !!}
		</li>
	</ol>
</div>

<div class="col-sm-6">
	<div class="card-box">
		@foreach ($types as $row)<p><a target='_blank' href='{{ legacy_href() }}admin/report/{{ $row->id }}'>- {{ $row->name }}</a></p>@endforeach<p><a target='_blank' target='_blank' href='{{ legacy_href() }}admin/report/preorder_working' />- สรุปการทำงาน</a></p><p><a target='_blank' target='_blank' href='{{ legacy_href() }}_admin/_report/current_cash' />- {!! res('customer_balance') !!}</a></p><p><a target='_blank' target='_blank' href='{{ legacy_href() }}_admin/_report/current_cash_cn' />- {!! res('customer_balance_cn') !!}</a></p>@foreach ($months as $mm => $mname)<p><a target='_blank' target='_blank' href='{{ legacy_href() }}_admin/_report/cashmove?m={{ $year }}-{{ $mm }}' />- รายงานยอดเงินเคลื่อนไหวประจำเดือน {{ $mname }} {{ $year }}</a></p>@endforeach
	</div>
</div>
@endsection
