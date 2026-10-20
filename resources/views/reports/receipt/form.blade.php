{{-- SR: _admin/_export/receipt.php (ใบเสร็จรับเงิน/ใบกำกับภาษี — สิทธิ์ admin_payment ตรวจที่ route) — ค่าจาก DB escape (เดิม echo ดิบ) --}}
@extends('legacy.layout')

@php
    use App\Domain\Reports\Php74;
    use App\Support\Legacy\Format;
    $exportDate = '';
    if (Php74::neq($data->receipt_date, '0000-00-00 00:00:00') && Php74::neq($data->receipt_date, '')) {
        $exportDate = Format::getthaidate(explode(' ', (string) $data->receipt_date)[0], false, true);
    }
@endphp

@section('content')


<style>

	table {
		border-collapse: collapse;
    	border: 1px #000 solid;
	}

	th, td {
		border: 1px #000 solid;
		padding:5px;
	}
</style>
<div class="col-sm-7">
	<h4 class="page-title">{!! res('payment_list') !!} <small>{{ $prefix.$data->id }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			{!! res('payment_list') !!} <small>{{ $prefix.$data->id }}</small>
		</li>
	</ol>
</div>

<form action="{{ legacy_href() }}_admin/_export/save_receipt" method="post">

<input type="hidden" name="id" value="{{ $data->id }}">

<div class="col-sm-5 pull-right">
	<div class="button-list">

		<a class="pull-right btn btn-inverse waves-effect waves-light"
			href="{{ legacy_href() }}_admin/_export/print_receipt/?id={{ $data->id }}">
            <span class="btn-label"><i class="fa fa-print"></i></span>
            {!! res('print') !!}
        </a>


        <button  type="submit" class="pull-right btn btn-success waves-effect waves-light">
            <span class="btn-label"><i class="fa fa-check"></i></span>
            {!! res('save_button') !!}
        </button>


	    <a type="button" href="{{ legacy_href() }}payment" class="pull-right btn btn-warning waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
		    {!! res('back_to_list') !!}
	    </a>
	</div>
</div>


<div class="col-sm-7">
	<div class="card-box">

		<div style="width: 100%;margin-bottom: 20px;">
			<input name="is_vat" value="1" type="checkbox" onclick="vating();" {!! Php74::eq($data->receipt_is_vat, 1) ? "checked='checked'" : '' !!} /> หัก vat จากลูกค้า
		</div>

		<div style="text-align: center;float: left; width: 20%;">
			<img src="{{ legacy_href() }}assets/images/logo.png" style="width: 165px;" />
		</div>
		<div style="text-align: center;float: left; width: 79%;">
			<b>บริษัท โกโก-คาร์โก้ จำกัด (สำนักงานใหญ่)</b><br/>
			12 ซอยเพชรเกษม 54 แยก 1 แขวงบางด้วน เขตภาษีเจริญ กรุงเทพมหานคร 10160<br/>
			เลขประจำตัวผู้เสียภาษี 0105560117993<br/>
		</div>


		<div class="form-horizontal" role="form" style="clear:both;margin-top:20px;float: left;">
			<p style="text-align: center;"><b>ใบเสร็จรับเงิน/ใบกำกับภาษี</b></p>
			@if (Php74::neq($data->receipt_id, ''))
			<div class="form-group">
	            <label class="col-md-3 control-label">เลขที่ใบเสร็จ</label>
	            <div class="col-md-5 form-control-static">
                	{{ $data->receipt_id }}
	            </div>
	        </div>
	    	@endif
			<div class="form-group">
	            <label class="col-md-3 control-label">วันที่</label>
	            <div class="col-md-5 ">
                	<div class="input-group">
	                	<input   type="text" class="form-control datepicker" name="date" value="{{ $exportDate }}"  >
						<span class="input-group-addon "><i class="icon-calender"></i></span>

	                </div>
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-3 control-label">ชื่อ</label>
	            <div class="col-md-8 ">
                	<input type="text" class="form-control" name="receipt_name" value="{{ Php74::eq($data->receipt_name, '') ? $data->send_name : $data->receipt_name }}" />
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-3 control-label">ที่อยู่</label>
	            <div class="col-md-8 ">
                	<textarea class="form-control" name="receipt_address" row="5">{{ Php74::eq($data->receipt_address, '') ? $data->send_address : $data->receipt_address }}</textarea>
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-3 control-label">เลขประจำตัวผู้เสียภาษี</label>
	            <div class="col-md-4 ">
                	<input type="text" class="form-control" name="receipt_tax" value="{{ $data->receipt_tax }}" />
	            </div>
	        </div>
		</div>


		<div style=" width: 100%;clear: both;margin-top:20px;float: left;" id="tables">

		</div>



		<div style="clear:both"></div>
	</div>
</div>




</form>

<script>

	$(function() {

		initialDatepicker2();
		setTimeout(function() { vating(); }, 300);


		$("form").validationEngine();
		$("form").ajaxForm({
		    success: function(data) {
		        eval(data);
		    }
		});
	});




	function vating() {
		var is_vat = $("input[name='is_vat']").prop('checked');

		if(is_vat) {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : { id : '{{ $data->id }}' } ,
	            url: rooturl + '_admin/_export/receipt_1',
	            success: function(data) {
	            	$("#tables").html(data);
	            }
	        });
		} else {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : { id : '{{ $data->id }}' } ,
	            url: rooturl + '_admin/_export/receipt_2',
	            success: function(data) {
	            	$("#tables").html(data);
	            }
	        });
		}
	}

</script>
@endsection
