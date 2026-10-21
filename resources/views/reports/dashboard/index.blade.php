{{-- _dashboard/index.php ของ PACK (สี #eb4823, ตารางชั้นวางสำหรับ admin_warehouse, ข้อความโกดังจีน/ไทยฝังในไฟล์เดิม) --}}
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
				<i class="md   md-local-shipping text-gray" style="background-color: #eb4823!important;"></i>
				<h2 class="m-0 text-dark counter font-600">{{ $user->code }}</h2>
				<div class="text-muted m-t-5">{!! res('call_send_car') !!}</div>
			</div>
		</div>

		<div class="col-sm-3 col-xs-12">
			<div class="widget-panel widget-style-2 bg-white">
				<i class="md   md-directions-ferry text-gray" style="background-color: #eb4823!important;"></i>
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

	{{-- PACK: เดิม query warehouse_section / warehouse_section_detail ซึ่งไม่มีใน DB ของ PACK (mysqli คืน false → ไม่มีแถว)
	     → แสดงแค่หัวตารางแบบเดิม ไม่ query --}}
	@perm('admin_warehouse')
	<div class="col-xs-12">
	    <div class="col-xs-12">
	    	<table class="table table-striped table-bordered">
	    		<tr>
					<th class="text-center" style="width:165px"><strong>{!! res('shelf_manage') !!}</strong></th>
					<th class="text-center">{!! res('remain') !!}</th>
					<th class="text-center">{!! res('all') !!}</th>
					<th class="text-center">{!! res('useded') !!}</th>
				</tr>
				
			</table>
	    </div>
	</div>
	@endperm

	<div class="col-xs-12">
		<div class="col-xs-9">
			<table class="table table-striped table-bordered">
				<tr>
					<td class="text-left" style="width:200px"><strong>{!! res('how_to_cal_wood_protect') !!}</strong></td>
					<td class="text-left">คิว*1,500 = ราคาตีลังไม้ เริ่มต้น 200฿</td>
				</tr>
				<tr>
					<td class="text-left"><strong>{!! res('cn_address') !!}</strong></td>
					<td class="text-left">收件人：PACK(ใส่รหัสในระบบ)<br/>
收货地址 : 广州市白云区石井街道夏茅机场路2721号广花货运市场A1号，PACK 仓库<br/>
电话13763392483<br/>
PACK仓库 510000<br/>
<br/>
贴唛头:PACK(ใส่รหัสในระบบ)<br/></td>
				</tr>
				<tr>
					<td class="text-left"><strong>{!! res('cn_tel') !!}</strong></td>
					<td class="text-left">13763392483</td>
				</tr>
				<tr>
					<td class="text-left"><strong>{!! res('contact_thai_warehouse') !!}</strong></td>
					<td class="text-left">ลูกค้าสามารถฝากข้อความ เเละติดต่อเข้ามาได้ที่เมนูข้อความ ได้เลยค่า หรือ ด่วน โทร 063-789-9461 / 062-261-8927</td>
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

</div>




<script>

</script>
@endsection
