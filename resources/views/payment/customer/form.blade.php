{{-- _payment/form.php เดิม (GET payment/{id}) — บิลของผู้ใช้นี้เท่านั้น --}}
@extends('legacy.layout')
@php
    use App\Domain\Payment\Php74;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $no = $prefix.$data->id;
@endphp

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('payment_list') !!} <small>{{ $no }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li class="active">
			{!! res('payment_list') !!} <small>{{ $no }}</small>
		</li>
	</ol>
</div>

<input type="hidden" name="id" value="{{ $data->id }}">

<div class="col-sm-5 pull-right">
	<div class="button-list">
		
	    <a type="button" href="{{ legacy_href() }}payment" class="pull-right btn btn-warning waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
		    {!! res('back_to_list') !!}
	    </a>

	    @if (Php74::eq($data->status, 1))
        <button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
            <span class="btn-label"><i class="fa fa-save"></i></span>
            {!! res('cancel_this') !!}
        </button>
        @endif
	</div>
</div>


<div class="col-sm-6">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('address_for_shipping') !!}</b></h4>
		<p class="text-muted font-13 m-b-30">
        </p>
		<div class="form-horizontal" role="form">                                    
	        
	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('name_lastname') !!}</label>
	            <div class="col-md-8 form-control-static">
                	{{ $data->send_name }}
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('address') !!}</label>
	            <div class="col-md-8 form-control-static">
                	{!! nl2br(e($data->send_address)) !!}
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('phone') !!}</label>
	            <div class="col-md-8 form-control-static">
                	{{ $data->send_phone }}
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('shipping_type') !!}</label>
	            <div class="col-md-8 form-control-static">
                	{{ $shipName }}
	            </div>
	        </div>

	        <div class="form-group" style="border-top:1px #ccc dashed"></div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('sender') !!}</label>
	            <div class="col-md-8 form-control-static">
	            	{!! Status::getsender($data->sender_type) !!}
	            </div>
	        </div>

	        @if (Php74::eq($data->sender_type, 1))
	        @include('payment.partials.sender_static')
	        @endif
	    </div>
	</div>
</div>



	<input type="hidden" name="id" value="{{ $id }}">
	<div class="col-xs-12 col-md-6">
    <div class="card-box">
    <h4 class="m-t-0 header-title"><b>{!! res('payment_thing') !!}</b></h4>
    <p class="text-muted font-13 m-b-30">
    </p>
    	<div class="form-horizontal" role="form">
    		<div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('no_for_grid') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	{{ $no }}
	            </div>
	        </div>
            <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('total_have_to_paided') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	<span class=" total_net addcomma currency">{{ $data->total }}</span> {!! res('thai_currency') !!}
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('payment_typing') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	<span class="label label-inverse">{!! res('using_system_cash') !!}</span>
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('status') !!}</label>
	            <div class="col-md-7 form-control-static">
	                {!! Status::getstatus($data->status) !!}@if (Php74::eq($data->status, -1))<div style='margin-top: 10px;' class='text-danger'>* {{ explode(',ยกเลิกโดย', (string) $data->cancel_remark)[0] }}</div>@endif

	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	{!! nl2br(e($data->submit_remark)) !!}
	            </div>
	        </div>

        </div>
    </div>
</div>




<div class="col-sm-12">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('tracking_list') !!}</b></h4>
		<p class="text-muted font-13 m-b-30">
        </p>
		<table class="lot_table_cus form-group" >
		    <thead>
		        <tr>
		            <th class="text-center">{!! res('lot_list').'/'.res('no') !!}</th>
		            <th class="text-center">{!! res('tracking') !!}</th>
		            <th class="text-center">{!! res('order') !!}</th>
		            <th class="text-center">{!! res('remark_for_this_order') !!}</th>
		            <th class="text-center">{!! res('amount') !!}</th>
		            <th class="text-center">{!! res('weight') !!}</th>
		            <th class="text-center">{!! res('width') !!}</th>
		            <th class="text-center">{!! res('height') !!}</th>
		            <th class="text-center">{!! res('long') !!}</th>
		            <th class="text-center">{!! res('cubic') !!}</th>
		            <th class="text-center">{!! res('type') !!}</th>
		            <th class="text-center">{!! res('service_price') !!}</th>
		            <th class="text-center">{!! res('price_kg') !!}</th>
		            <th class="text-center">{!! res('price_cubic') !!}</th>
		        </tr>
		    </thead>

		    <tbody>
		@include('payment.partials.detail_rows', ['orderBase' => 'order/', 'orderWithTracking' => true, 'note' => 'quote', 'emptyCols' => 11])
		</tbody>
		@include('payment.partials.detail_totals', ['admin' => false])
		</table>
	</div>
</div>
@include('payment.partials.export_box', ['withProof' => true])

@include('payment.partials.balance_list', ['colClass' => 'col-xs-12 col-md-6', 'w1' => 130, 'w2' => 80, 'br' => '<br/>'])
<script>
$(function() {
	$('.showdetailbox').on('touchstart mouseover', function (e) {
	    $(this).css({'color':  '#ff0000'});
		$(this).find('.lot_table_in').css('display', 'block');
	});
	$('.showdetailbox').on('touchend mouseout', function (e) {
	    $(this).css({'color': '#797979'});
		$(this).find('.lot_table_in').css('display', 'none');
	});

	initialAddcommaText();
});

function canceling() {
    ConfirmBox(function() {
        OpenLoading();
        $.ajax({
            type: 'POST',
            async: true,
            data : 'id={{ $id }}',
            url: rooturl + '_payment/save_delete',
            success: function(data) {
                eval(data);
            }
        });
    })
}

</script>
@endsection
