{{-- _admin/_withdrawcash/form.php เดิม --}}
@extends('legacy.layout')
@php
    use App\Domain\MoneyMove\Lookups;
    use App\Domain\MoneyMove\Php74;
    use App\Domain\MoneyMove\UploadBox;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $st = $d['status'] ?? null;
@endphp

@section('content')

<div class="col-sm-7">
	<h4 class="page-title">{!! res('withdrawcash_edit') !!} <small>{{ $prefix }}{{ $d['id'] ?? '' }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('withdrawcash') !!}
		</li>
		<li class="active">
			{{ $prefix }}{{ $d['id'] ?? '' }}
		</li>
	</ol>
</div>



<input type="hidden" name="id" value="{{ $d['id'] ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
@if ($id === 0)
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>
@endif

		    
			
		    <button onclick="approving()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('approved') !!}
		    </button>

@if (Php74::eq($st, 1))

		    <button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-close"></i></span>
			    {!! res('cancel_this') !!}
		    </button>
@endif



		    <a type="button" href="{{ legacy_href() }}admin/withdrawcash" class="pull-right btn btn-warning waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
			    {!! res('back_to_list') !!}
		    </a>
		</div>
	</div>


	<div class="col-sm-6">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">                                    
		        
		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('no_for_grid') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	{{ $prefix }}{{ $d['id'] ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('customer_code') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	<a href="{{ legacy_href() }}admin/user/{{ $d['user_id'] ?? '' }}">{{ $d['code'] ?? '' }}</a>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('status') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	{!! Status::getstatus($st) !!}@if (Php74::eq($st, -1))<div style='margin-top: 10px;' class='text-danger'>* {{ $d['cancel_remark'] ?? '' }}</div>@endif

		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('withdrawcash_want') !!}</label>
		            <div class="col-md-4 form-control-static">
		                {!! Format::numberformat($d['amount'] ?? null) !!}
		            </div>
		        </div>

		        <div class="form-group">
				    <label class="col-md-5 control-label"> {!! res('bank_no') !!}</label>
				    <div class="col-md-4 form-control-static">
				        	{{ $d['branch_no'] ?? '' }}
				    </div>
				</div>

				<div class="form-group">
				    <label class="col-md-5 control-label"> {!! res('bank_name') !!}</label>
				    <div class="col-md-4 form-control-static">
				        	{{ $d['branch_bank'] ?? '' }}
				    </div>
				</div>

				<div class="form-group">
				    <label class="col-md-5 control-label"> {!! res('bank_acc') !!}</label>
				    <div class="col-md-4 form-control-static">
				        	{{ $d['branch_name'] ?? '' }}
				    </div>
				</div>

		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('working_date') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{!! Lookups::formDate($d['create_date'] ?? null) !!}
		            </div>
		        </div>
		        
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

{!! $balanceTable !!}


@include('money-move.partials.cancel_modal')

<script>

	initialDatepicker();


	function canceling() {
		$(".cancel_remark_form").modal('show');
	}

	function canceling2() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $id }}&cancel_remark=' + $("#cancel_remark").val(),
	            url: rooturl + '_admin/_withdrawcash/cancel',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}



	function approving() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $id }}&approved_remark=' + $("textarea[name='approved_remark']").val() + '&approved_upload=' + $("#upload_approved_file").val() ,
	            url: rooturl + '_admin/_withdrawcash/save_approved',
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
</script>
@endsection
