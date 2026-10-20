{{-- _admin/_report/index.php ของ SR: ยอดเคลื่อนไหว/ภาษีขาย 6 เดือนล่าสุด (เก่า → ใหม่) + รายงานภาษีขายรวม (admin/report/97) --}}
@extends('legacy.layout')

@php
    // getmos() ของ SR
    $names = ['01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน', '05' => 'พฤษภาคม', '06' => 'มิถุนายน',
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
		@foreach ($types as $row)<p><a href='{{ legacy_href() }}admin/report/{{ $row->id }}'>- {{ $row->name }}</a></p>@endforeach<p><a href='{{ legacy_href() }}admin/report/preorder_working' />- สรุปการทำงาน</a></p><p><a href='{{ legacy_href() }}_admin/_report/current_cash' />- {!! res('customer_balance') !!}</a></p><p><a href='{{ legacy_href() }}_admin/_report/current_cash_cn' />- {!! res('customer_balance_cn') !!}</a></p><br /><br />@foreach ($months as $m)<p><a href='{{ legacy_href() }}_admin/_report/cashmove?m={{ $m['ym'] }}' />- รายงานยอดเงินเคลื่อนไหวประจำเดือน {{ $names[$m['m']] ?? '' }} {{ $m['y'] }}</a></p>@endforeach<br /><br /><p><a href='{{ legacy_href() }}admin/report/97'>- รายงานภาษีขายรวม</a></p>@foreach ($months as $m)<p><a href='{{ legacy_href() }}_admin/_report/printtax?m={{ $m['m'] }}&y={{ $m['y'] }}' />- รายงานภาษีขาย {{ $names[$m['m']] ?? '' }} {{ $m['y'] }}</a></p>@endforeach
	</div>
</div>
@endsection
