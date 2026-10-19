{{-- _admin/_withdrawpoint/form.php เดิม (สิทธิ์ admin_withdrawpoint ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')
@php use App\Support\Legacy\Format; use App\Support\Legacy\Status; use App\Domain\Points\Php74; $no = $prefix.($data->id ?? ''); $status = $data->status ?? null; @endphp

@section('content')

<div class="col-sm-7">
	<h4 class="page-title">{!! res('withdrawpoint_edit') !!} <small>{{ $no }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('withdrawpoint') !!}
		</li>
		<li class="active">
			{{ $no }}
		</li>
	</ol>
</div>

@if($id == 0)
<form action="{{ legacy_href() }}_admin/_refill/save" method="post">
@endif

<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
@if($id == 0)
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>
@endif

@if($status !== null && Php74::eq($status, 1))
			
		    <button onclick="approving()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('approved') !!}
		    </button>

		    <button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-close"></i></span>
			    {!! res('cancel_this') !!}
		    </button>
@endif



		    <a type="button" href="{{ legacy_href() }}admin/withdrawpoint" class="pull-right btn btn-warning waves-effect waves-light">
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
		            	{{ $no }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('customer_code') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	<a href="{{ legacy_href() }}admin/user/{{ $data->user_id ?? '' }}">{{ $data->code ?? '' }}</a>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('status') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	{!! Status::getstatus($status) !!}@if($status !== null && Php74::eq($status, -1))<div style='margin-top: 10px;' class='text-danger'>* {{ $data->cancel_remark }}</div>@endif
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('withdrawpoint_want') !!}</label>
		            <div class="col-md-4 form-control-static">
		                {!! Format::numberformatpoint($data->amount ?? null) !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('working_date') !!}</label>
		            <div class="col-md-6 form-control-static">
@php $cd = (string) ($data->create_date ?? ''); $exp = explode(' ', $cd); @endphp
@if($cd === '0000-00-00 00:00:00' || $cd === '')
-<br />-
@else
{!! Format::getthaidate($exp[0], true, true) !!} / {{ $exp[1] ?? '' }}
@endif
		            </div>
		        </div>
		        
		    </div>
		</div>
	</div>


@include('points.partials.ledger', ['boxClass' => 'col-xs-12 col-md-6', 'cnRate' => false])


@if($id == 0)
</form>
@endif

<div id="panel-modal" class="cancel_remark_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading"> 
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
                    <h3 class="panel-title">{!! res('cancel_this') !!}</h3> 
                </div> 
                <div class="panel-body"> 
	                <textarea class="form-control" id="cancel_remark"></textarea>
                </div> 
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="canceling2();" type="button">{!! res('ok') !!}</button>
                </div>
                
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>

	initialDatepicker();

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
	            url: rooturl + '_admin/_withdrawpoint/cancel',
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
	            url: rooturl + '_admin/_withdrawpoint/save_approved',
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
