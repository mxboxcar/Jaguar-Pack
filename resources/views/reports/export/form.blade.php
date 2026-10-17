{{-- _export/form.php เดิม (ลูกค้า) — ค่าจาก DB escape ทั้งหมด (เดิม echo ดิบ) --}}
@extends('legacy.layout')

@php
    use App\Domain\Reports\Php74;
    use App\Domain\Reports\Rows;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $d = fn (string $k) => $data->{$k} ?? null;
    $exportDate = '';
    if (Php74::neq($d('export_date'), '0000-00-00 00:00:00') && Php74::neq($d('export_date'), '')) {
        $exportDate = Format::getthaidate(explode(' ', (string) $d('export_date'))[0]);
    }
@endphp

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('payment_list') !!} <small>{{ $prefix.$d('id') }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li class="active">
			{!! res('payment_list') !!} <small>{{ $prefix.$d('id') }}</small>
		</li>
	</ol>
</div>

<input type="hidden" name="id" value="{{ $d('id') }}">

<div class="col-sm-5 pull-right">
	<div class="button-list">
		
	    <a type="button" href="{{ legacy_href() }}export" class="pull-right btn btn-warning waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
		    {!! res('back_to_list') !!}
	    </a>

	    @if (Php74::eq($d('status'), 0))
        <button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
            <span class="btn-label"><i class="fa fa-save"></i></span>
            {!! res('cancel_this') !!}
        </button>
        @endif

		@if (Php74::eq($d('status'), 0))
            <button onclick="approved()" type="button" class="pull-right btn btn-success waves-effect waves-light">
            <span class="btn-label"><i class="fa fa-check"></i></span>
            {!! res('summittting_payment') !!}
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
                	{{ $d('send_name') }}
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('address') !!}</label>
	            <div class="col-md-8 form-control-static">
                	{!! nl2br(e((string) $d('send_address'))) !!}
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('phone') !!}</label>
	            <div class="col-md-8 form-control-static">
                	{{ $d('send_phone') }}
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-4 control-label">{!! res('shipping_type') !!}</label>
	            <div class="col-md-8 form-control-static">
                	{{ $shipName }}
	            </div>
	        </div>
	    </div>
	</div>
</div>


	<input type="hidden" name="id" value="{{ $id }}">
	<div class="col-xs-12 col-md-6">
    <div class="card-box">
    <h4 class="m-t-0 header-title"><b>{!! res('export_thing') !!}</b></h4>
    <p class="text-muted font-13 m-b-30">
    </p>
    	<div class="form-horizontal" role="form">
            <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('export_tracking') !!}</label>
	            <div class="col-md-7 form-control-static">{{ $d('export_tracking') }}</div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('export_price') !!}</label>
	            <div class="col-md-5">
	            	<div class="input-group form-control-static"><span class=" addcomma currency">{{ $d('export_price') }}</span> {!! res('thai_currency') !!}</div>
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
	            <div class="col-md-7 form-control-static">{!! nl2br(e((string) $d('export_remark'))) !!}</div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('export_date') !!}</label>
	            <div class="col-md-7 form-control-static">{!! $exportDate !!}</div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('approved_proof') !!}</label>
	            <div class="col-md-7 form-control-static">{!! Rows::fancy($d('export_attachment')) !!}</div>
	        </div>
        </div>
    </div>
</div>

@include('reports.partials.export_table', ['admin' => false])

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
	            	{{ $prefix.$d('id') }}
	            </div>
	        </div>
            <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('total_have_to_paided') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	<span class=" total_net addcomma currency">{{ $d('total') }}</span> {!! res('thai_currency') !!}
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
	                {!! Status::getstatus($d('status')) !!}@if (Php74::eq($d('status'), -1))<div style='margin-top: 10px;' class='text-danger'>* {{ explode(',ยกเลิกโดย', (string) $d('cancel_remark'))[0] }}</div>@endif
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
	            <div class="col-md-7 form-control-static">
	            	{!! nl2br(e((string) $d('submit_remark'))) !!}
	            </div>
	        </div>
        </div>
    </div>
</div>

{!! $balance !!}


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
            data : 'id={{ $id }}&cancel_remark=' + $("#cancel_remark").val(),
            url: rooturl + '_export/save_delete',
            success: function(data) {
                eval(data);
            }
        });
    })
}

function approved() {
    ConfirmBox(function() {
        $.ajax({
            type: 'POST',
            async: true,
            data : 'id={{ $d('id') }}',
            url: rooturl + '_export/save_approved',
            success: function(data) {
                eval(data);
            }
        });
    });
}

</script>
@endsection
