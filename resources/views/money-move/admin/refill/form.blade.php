{{-- _admin/_refill/form.php เดิม — $id = (int) ของ GET id; 0 = สร้างใหม่ --}}
@extends('legacy.layout')
@php
    use App\Domain\MoneyMove\Lookups;
    use App\Domain\MoneyMove\Php74;
    use App\Domain\MoneyMove\UploadBox;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $new = $id === 0;
    $st = $d['status'] ?? null;
@endphp

@section('content')
@if ($new)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('refill') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('refill') !!}
		</li>
		<li class="active">
			{!! res('refill_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('refill_edit') !!}<small>{{ $prefix }}{{ $d['id'] ?? '' }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('refill') !!}
		</li>
		<li class="active">
			{{ $prefix }}{{ $d['id'] ?? '' }}
		</li>
	</ol>
</div>
@endif

@if ($new)
<form action="{{ legacy_href() }}_admin/_refill/save" method="post">
@endif

<input type="hidden" name="id" value="{{ $d['id'] ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
@if ($new)
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>
@endif

@if (Php74::eq($st, 1))
			
		    <button onclick="approving()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('approved') !!}
		    </button>

		    <button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-close"></i></span>
			    {!! res('cancel_this') !!}
		    </button>
@endif



		    <a type="button" href="{{ legacy_href() }}admin/refill" class="pull-right btn btn-warning waves-effect waves-light">
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
		        
@if (! $new)
		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('no_for_grid') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	{{ $prefix }}{{ $d['id'] ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('customer_code') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	<a target="_blank" href="{{ legacy_href() }}admin/user/{{ $d['user_id'] ?? '' }}">{{ $d['code'] ?? '' }}</a>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('status') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	{!! Status::getstatus($st) !!}@if (Php74::eq($st, -1))<div style='margin-top: 10px;' class='text-danger'>* {{ $d['cancel_remark'] ?? '' }}</div>@endif

		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('add_balance') !!}</label>
		            <div class="col-md-4 form-control-static">
		                {!! Format::numberformat($d['amount'] ?? null) !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('working_date') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{!! Lookups::formDate($d['create_date'] ?? null) !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('attachment') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	{!! UploadBox::single('file', $d['attachment'] ?? '') !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('remark') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{!! nl2br(e($d['remark'] ?? '')) !!}
		            </div>
		        </div>

@else


		        <div class="form-group">
		            <label class="col-md-5 control-label"> {!! res('customer_code') !!}</label>
		            <div class="col-md-4">
		                	<input  type="text" name="code" class="form-control addcomma  text-left validate[required]" value="{{ $d['code'] ?? '' }}" >
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-5 control-label"> {!! res('add_balance') !!}</label>
		            <div class="col-md-4">
		            	<div class="input-group">
		                	<input  type="text" name="amount" class="form-control addcomma  text-right validate[required, custom[number]]" value="{{ $d['amount'] ?? '' }}" >
		                	<span class="input-group-addon">
				        		{!! res('thai_currency') !!}
				        	</span>
		                </div>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label"> {!! res('working_date') !!}</label>
		            <div class="col-md-6">
		            	<div class="input-group">
		                	<input  type="text" class="form-control datepicker" name="date" >
							<span class="input-group-addon "><i class="icon-calender"></i></span>

		                </div>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label"> {!! res('attachment') !!}</label>
		            <div class="col-md-7">
		            	{!! UploadBox::single('file', $d['attachment'] ?? '', true) !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label"> {!! res('remark') !!}</label>
		            <div class="col-md-6">
		            	<textarea  name="remark" rows="5" class="form-control">{{ $d['remark'] ?? '' }}</textarea>
		            </div>
		        </div>

@endif

		        

		    </div>
		</div>
	</div>


{!! $balanceTable !!}


@if ($new)
</form>
@endif

@include('money-move.partials.cancel_modal')

<script>

	initialDatepicker();

	$(function() {
		$("input[name='code']").typeahead({
	      source: function (query, process) {
	        $.ajax({
	          url: rooturl + '_admin/_lot/load_autocomplete_user',
	          type: 'POST',
	          dataType: 'JSON',
	          data: 'query=' + query,
	          success: function(data) {
	            process(data.code);
	          }
	        })
	      }
    });

	})
	function sumiting() {
		if($("form").validationEngine('validate') == false) 
		{ 
			CloseLoading(); 
		} else {
			ConfirmBox(function () {
				$("form").submit();
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
	            url: rooturl + '_admin/_refill/cancel',
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
	            data : 'id={{ $id }}' ,
	            url: rooturl + '_admin/_refill/save_approved',
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
