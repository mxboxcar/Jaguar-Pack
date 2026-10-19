{{-- _admin/_exchange/form.php เดิม (สิทธิ์ admin_exchange ที่ middleware) — ค่าจาก DB escape ด้วย {{ }} (เดิม echo ดิบ) --}}
@php use App\Support\Legacy\Format; use App\Support\Legacy\Status; use App\Domain\Settings\Php74; @endphp
@extends('legacy.layout')

@section('content')

<div class="col-sm-7">
	<h4 class="page-title">{!! res('exchange_edit') !!} <small>{{ $prefix.($data->id ?? '') }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('exchange') !!}
		</li>
		<li class="active">
			{{ $prefix.($data->id ?? '') }}
		</li>
	</ol>
</div>



<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			@if($id === 0)
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>
		    @endif

		    
			
		    <button onclick="approving()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('approved') !!}
		    </button>

		    @if(Php74::eq($data->status ?? null, 1))

		    <button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-close"></i></span>
			    {!! res('cancel_this') !!}
		    </button>
		    @endif



		    <a type="button" href="{{ legacy_href() }}admin/exchange" class="pull-right btn btn-warning waves-effect waves-light">
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
		            	{{ $prefix.($data->id ?? '') }}
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
		            	{!! Status::getstatus($data->status ?? null) !!}@if(Php74::eq($data->status ?? null, -1))<div style='margin-top: 10px;' class='text-danger'>* {{ $data->cancel_remark }}</div>@endif

		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('exchange_want') !!}</label>
		            <div class="col-md-4 form-control-static">
		                {!! Format::numberformat($data->amount ?? null, true, true, true) !!}
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-5 control-label">{!! res('exchange_rate') !!}</label>
		            <div class="col-md-4 form-control-static">
		                {!! Format::numberformat($exchangeRate) !!}
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


<div class="col-xs-12 col-md-6">
        <div class="card-box">
            <h4 class="m-t-0 header-title"><b>{!! res('ref_balance_list') !!}</b></h4>
            <p class="text-muted font-13 m-b-30">
            </p>
            <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 130px;">{!! res('working_date') !!}</th>
                            <th class="text-center">{!! res('detail') !!}</th>
                            <th class="text-center" style="width: 80px;">{!! res('amount') !!}</th>
                        </tr>
                    </thead>
                    <tbody>@forelse($balances as $item)@php $exp = explode(' ', (string) $item->create_date); @endphp<tr><td>{!! Format::getthaidate($exp[0]) !!}<br/>{{ $exp[1] ?? '' }}</td><td class="text-left"><a href="{{ legacy_href() }}{{ $item->link }}{{ $item->prefix_ref_id }}">{{ $item->detail }}</a></td><td class="text-right">@if(Php74::cmp($item->amount, 0) >= 0)<span class="text-success">+{!! Format::numberformat($item->amount, true, true, true) !!}</span>@else<span class="text-danger">{!! Format::numberformat($item->amount, true, true, true) !!}</span>@endif</td>@empty<tr><td colspan="3" class="text-center">{!! res('no_data') !!}</td></tr>@endforelse

                </tbody>
            </table>
        </div>
    </div>


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


	function canceling() {
		$(".cancel_remark_form").modal('show');
	}

	function canceling2() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $id }}&cancel_remark=' + $("#cancel_remark").val(),
	            url: rooturl + '_admin/_exchange/cancel',
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
	            url: rooturl + '_admin/_exchange/save_approved',
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
