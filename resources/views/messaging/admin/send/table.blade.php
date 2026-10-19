@use('App\Support\Legacy\Format')
@use('App\Support\Legacy\Lookup')
@php $payPrefix = (string) Lookup::getPrefix('payment'); @endphp
<div class="col-sm-7">
	<h4 class="page-title">{!! res('send_list') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			{!! res('send_list') !!}
		</li>
	</ol>
</div>

<div class="col-sm-12">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
		<p class="text-muted font-13 m-b-30"></p>
		<table class="table table-striped table-bordered" style="margin-top:40px;">
		    <thead>
		        <tr>
		            <th class="text-center" style="width: 80px;">{!! res('no_for_grid') !!}</th>
		            <th class="text-center" style="width: 120px;">{!! res('customer_code') !!}</th>
		            <th class="text-center" >{!! res('send_name') !!}</th>
		            <th class="text-center" style="width: 250px;">{!! res('shipping_type') !!}</th>
		            <th class="text-center" style="width: 100px;">{!! res('total') !!}</th>
		            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
		        </tr>
		    </thead>
		    <tbody>
		    	@foreach($rows as $item)@if((int) $item->id !== 0)<tr><td><a href="{{ legacy_href() }}admin/payment/{{ (int) $item->id }}" >{{ $payPrefix }}{{ (int) $item->id }}</a></td><td class="text-center">{{ $item->code }}</td><td >{{ $item->send_name ?? '' }}</td><td class="text-center">{{ $item->ship_name }}</td><td class="text-right">{!! Format::numberformat($item->total) !!}</td><td class="text-center"><a  href="{{ legacy_href() }}_admin/_export/print/?id={{ (int) $item->id }}" class="table-action-btn"><i class="fa fa-print text-primary"></i></a></td></tr>@endif @endforeach

		    </tbody>
		</table>
	</div>
</div>
