{{-- _admin/_transfer/form.php เดิม — $id = (int) ของ GET id; 0 = "สร้างใหม่" (เดิมไม่มีปุ่มสร้างใน list) --}}
@extends('legacy.layout')
@php
    use App\Domain\MoneyMove\Lookups;
    use App\Domain\MoneyMove\Php74;
    use App\Domain\MoneyMove\UploadBox;
    use App\Support\Legacy\Lookup;
    use App\Support\Legacy\Status;
    $new = $id === 0;
    $st = $d['status'] ?? null;
    $rate = Lookup::getSetting('rate_transfer');
    $tp = Lookups::prefix('transfer');
@endphp

@section('content')
@if ($new)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('transfer') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('transfer') !!}
		</li>
		<li class="active">
			{!! res('transfer_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('transfer_edit') !!} <small>{{ $tp }}{{ $d['id'] ?? '' }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('transfer') !!}
		</li>
		<li class="active">
			{{ $tp }}{{ $d['id'] ?? '' }}
		</li>
	</ol>
</div>
@endif


<form action="{{ legacy_href() }}_admin/_transfer/save" method="post">

<input type="hidden" name="id" value="{{ $d['id'] ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>

@if (Php74::eq($st, 1))
			<button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('cancel_this') !!}
		    </button>
@endif


@if (Php74::eq($st, '-1') || Php74::eq($st, '2'))
		    <button onclick="rollback()" type="button" class="pull-right btn btn-info waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-rotate-left"></i></span>
			    RollBack
		    </button>
@endif


		    <a type="button" href="{{ legacy_href() }}admin/transfer" class="pull-right btn btn-warning waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
			    {!! res('back_to_list') !!}
		    </a>
		</div>
	</div>


	<div class="col-sm-6">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('detail_bank') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">       
@if (! $new)
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{{ $tp }}{{ $d['id'] ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('member_code') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{{ $d['code'] ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('status') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{!! Status::getstatus($st) !!}@if (Php74::eq($st, -1))<div style='margin-top: 10px;' class='text-danger'>* {{ $d['cancel_remark'] ?? '' }}</div>@endif

		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('detail') !!}</label>
		            <div class="col-md-6 form-control-static" >
		            	{{ $d['acc_name'] ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('attachment') !!}</label>
		            <div class="col-md-6 form-control-static">
	                	{!! UploadBox::single('file', $d['attachment'] ?? '') !!}
		            </div>
		        </div>
		        
@else
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('attachment') !!}</label>
		            <div class="col-md-6 form-control-static">
	                	{!! UploadBox::single('file', $d['attachment'] ?? '') !!}
		            </div>
		        </div>
		        
@endif
		    </div>
		</div>
	</div>

	<div class="col-sm-6">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('detail_using_cash') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">
@include('money-move.transfer.cash_box')
		    </div>
		</div>
	</div>

	<div class="col-sm-6">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('approved_proof') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">
	        	<div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('attachment') !!}</label>
		            <div class="col-md-8 ">
		            	{!! UploadBox::single('approved_file', $d['approved_upload'] ?? '', true) !!}
		            </div>
		        </div>
		        
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
		            <div class="col-md-8 ">
	                	<textarea name="approved_remark" rows="5" class="form-control">{{ $d['approved_remark'] ?? '' }}</textarea>
		            </div>
		        </div>
		    </div>
		</div>
	</div>

</form>
{!! $balanceTable !!}

@include('money-move.partials.cancel_modal')

<script>
	$(function() {
		$("#upload_file").removeClass("validate[requiredattachment]");
	});

	function sumiting() {
		if($("form").validationEngine('validate') == false) 
		{ 
			CloseLoading(); 
		} else {
			ConfirmBox(function () {
				$("form").eq(0).submit();
			});
		}
	}

	function canceling() {
		$(".cancel_remark_form").modal('show');
	}

	function canceling2() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $id }}&cancel_remark=' + $("#cancel_remark").val(),
	            url: rooturl + '_admin/_transfer/cancel',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}

	function rollback() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $id }}',
	            url: rooturl + '_admin/_transfer/rollback',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}

	$("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	    }
	});

@include('money-move.partials.multiselect')
</script>
@endsection
