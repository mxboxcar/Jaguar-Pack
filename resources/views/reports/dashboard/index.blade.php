{{-- _dashboard/index.php เดิม --}}
@extends('legacy.layout')

@php
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Lookup;
@endphp

@section('content')
<style>
	.widget-panel .text-gray {
		color: #fff !important;
	}

	.table {
		font-size: 13px !important;
	}
</style>
<div class="col-sm-7">
	<h4 class="page-title">{!! res('dashboard') !!}</h4>
	<ol class="breadcrumb">
		<li class="active">
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
	</ol>
</div>

<input type="hidden" name="id" value="{{ $user->id }}">

<div class="row">

	<div class="col-xs-12">

		<div class="col-sm-3 col-xs-12">
			<div class="widget-panel widget-style-2 bg-white">
				<i class="md   md-local-shipping text-gray" style="background-color: #ae75ff!important;"></i>
				<h2 class="m-0 text-dark counter font-600">{{ $user->code }}</h2>
				<div class="text-muted m-t-5">{!! res('call_send_car') !!}</div>
			</div>
		</div>

		<div class="col-sm-3 col-xs-12">
			<div class="widget-panel widget-style-2 bg-white">
				<i class="md   md-directions-ferry text-gray" style="background-color: #ae75ff!important;"></i>
				<h2 class="m-0 text-dark counter font-600">{{ $user->code }} (SEA)</h2>
				<div class="text-muted m-t-5">{!! res('call_send_ship') !!}</div>
			</div>
		</div>

		<div class="col-sm-2 col-xs-12">
			<div class="widget-panel widget-style-2 bg-white" style="padding: 9px 22px;">

				<h2 class="m-0 text-dark counter font-600">{!! Format::numberformat(Lookup::getCurrentCash()) !!}</h2>
				<div class="text-muted m-t-5"><span class="md md-attach-money"></span> <a style="font-size: 11px!important;" href="{{ legacy_href() }}balance">{!! res('your_balance') !!}</a></div>

			</div>
		</div>

		<div class="col-sm-2 col-xs-12">
			<div class="widget-panel widget-style-2 bg-white" style="padding: 9px 22px;">

				<h2 class="m-0 text-dark counter font-600">{!! Format::numberformat(Lookup::getCurrentCashCN(), true, true, true) !!}</h2>
				<div class="text-muted m-t-5"><span class="md md-attach-money"></span> <a style="font-size: 11px!important;" href="{{ legacy_href() }}balance_cn">{!! res('cash_cn') !!}</a></div>

			</div>
		</div>


		<div class="col-sm-2 col-xs-12">
            <div class="widget-panel widget-style-2 bg-white" style="padding: 9px 22px;">
                <a href="{{ legacy_href() }}point" >
                    <h2 class="m-0 text-dark counter font-600">{!! Format::numberformatpoint(Lookup::getCurrentPoint()) !!}</h2>
                    <div class="text-muted m-t-5" style="font-size: 11px;">{!! res('your_point1').'<br/>'.e((string) Lookup::getSetting('divide_point')).res('your_point2') !!}</div>
            	</a>
            </div>
        </div>

	</div>

	<div class="col-xs-12">
		<div class="col-xs-9">
			<table class="table table-striped table-bordered">
				<tr>
					<td class="text-left" style="width:165px"><strong>{!! res('how_to_cal_wood_protect') !!}</strong></td>
					<td class="text-left">{!! Lookup::getSetting('calculate_woodprotect') !!}</td>
				</tr>
				<tr>
					<td class="text-left"><strong>{!! res('cn_address') !!}</strong></td>
					<td class="text-left">{!! Lookup::getSetting('address_cn') !!}</td>
				</tr>
				<tr>
					<td class="text-left"><strong>{!! res('cn_tel') !!}</strong></td>
					<td class="text-left">{!! Lookup::getSetting('phone_cn') !!}</td>
				</tr>
				<tr>
					<td class="text-left"><strong>{!! res('th_tel') !!}</strong></td>
					<td class="text-left">{!! Lookup::getSetting('phone_th') !!}</td>
				</tr>
			</table>
		</div>

		<div class="col-xs-3">
			<table class="table table-striped table-bordered">
				<thead>
					<tr>
						<th class="text-center" style="width:120px">{!! res('rate') !!}</th>
						<th class="text-center">{!! res('price') !!}</th>
					</tr>
				</thead>
				<tbody><tr>
						<td class="text-center">{!! res('rate_order') !!}</td>
						<td class="text-right">{!! Format::numberformat(Lookup::getSetting('rate_order')) !!}</td>
					</tr><tr>
						<td class="text-center">{!! res('rate_refill') !!}</td>
						<td class="text-right">{!! Format::numberformat(Lookup::getSetting('rate_alipay')) !!}</td>
					</tr><tr>
						<td class="text-center">{!! res('rate_transfer') !!}</td>
						<td class="text-right">{!! Format::numberformat(Lookup::getSetting('rate_transfer')) !!}</td>
					</tr></tbody>
			</table>
		</div>
	</div>

	{{-- SR: ซ่อนตารางราคาสำหรับผู้ใช้ id 891 (checkUser()['id'] != 891 เดิม) --}}
	@if ((int) $user->id !== 891)
	<div class="col-xs-12">
		
		<div class="col-xs-6"><table class="table table-striped table-bordered">
					<thead>
						<tr>
							<th class="text-center" style="width:70px">{!! res('car') !!}</th>
							<th class="text-center">{!! res('description') !!}</th>
							<th class="text-center">{!! res('kg') !!}</th>
							<th class="text-center">{!! res('cubic') !!}</th>
						</tr>
					</thead>
					<tbody>@foreach ($car as $item)<tr>
						<td class="text-center">{{ $item->name }}</td>
						<td class="text-left">{{ $item->desc }}</td>
						<td class="text-right">{!! Format::numberformat($item->kg, true, true) !!}</td>
						<td class="text-right">{!! Format::numberformat($item->cubic, true, false) !!}</td>
					</tr>@endforeach</tbody>
				</table>
		</div>

		<div class="col-xs-6"><table class="table table-striped table-bordered" >
					<thead>
						<tr>
							<th class="text-center" style="width:70px">{!! res('ship') !!}</th>
							<th class="text-center">{!! res('description') !!}</th>
							<th class="text-center">{!! res('kg') !!}</th>
							<th class="text-center">{!! res('cubic') !!}</th>
						</tr>
					</thead>
					<tbody>@foreach ($ship as $item)<tr>
						<td class="text-center">{{ $item->name }}</td>
						<td class="text-left">{{ $item->desc }}</td>
						<td class="text-right">{!! Format::numberformat($item->kg, true, true) !!}</td>
						<td class="text-right">{!! Format::numberformat($item->cubic, true, false) !!}</td>
					</tr>@endforeach</tbody>
				</table>
		</div>
		
	</div>
	@endif

</div>
@endsection
