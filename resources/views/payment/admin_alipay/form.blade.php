{{-- _admin/_alipay/form.php เดิม (GET admin/alipay/create = id 0, admin/alipay/{id}) --}}
@extends('legacy.layout')
@php
    use App\Domain\Payment\Php74;
    use App\Domain\Payment\UploadBox;
    use App\Support\Legacy\Status;
    $isNew = $id === 0;
    $no = $prefix.($data->id ?? '');
    $status = $data->status ?? null;
@endphp

@section('content')
@if ($isNew)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('alipay') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('alipay') !!}
		</li>
		<li class="active">
			{!! res('alipay_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('alipay_edit') !!} <small>{{ $no }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('alipay') !!}
		</li>
		<li class="active">
			{{ $no }}
		</li>
	</ol>
</div>
@endif

<form action="{{ legacy_href() }}_admin/_alipay/save" method="post">

<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">

		    <button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>

		    @if (Php74::eq($status, 1))
			<button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('cancel_this') !!}
		    </button>
		    @endif


		    @if (Php74::eq($status, '-1') || Php74::eq($status, '2'))
		    <button onclick="rollback()" type="button" class="pull-right btn btn-info waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-rotate-left"></i></span>
			    RollBack
		    </button>
		    @endif


		    <a type="button" href="{{ legacy_href() }}admin/alipay" class="pull-right btn btn-warning waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
			    {!! res('back_to_list') !!}
		    </a>
		</div>
	</div>


	<div class="col-sm-6">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('alipay_detail') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">       
		        @if (! $isNew)
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{{ $no }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('member_code') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{{ $data->code ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('status') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{!! Status::getstatus($status) !!}@if (Php74::eq($status, -1))<div style='margin-top: 10px;' class='text-danger'>* {{ $data->cancel_remark }}</div>@endif
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('taobao_id') !!}</label>
		            <div class="col-md-6 form-control-static">
	                	{{ $data->taobao_id ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('taobao_password') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{{ $data->taobao_password ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('alipay_id') !!}</label>
		            <div class="col-md-6 form-control-static">
	                	{{ $data->alipay_id ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('alipay_password') !!}</label>
		            <div class="col-md-6 form-control-static">
	                	{{ $data->alipay_password ?? '' }}
		            </div>
		        </div>
		        
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('password_alipay') !!}</label>
		            <div class="col-md-6 form-control-static">
	                	{{ $data->password ?? '' }}
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('attachment') !!}</label>
		            <div class="col-md-6 form-control-static">
	                	{!! UploadBox::single('bank_file', $data->attachment ?? '') !!}
		            </div>
		        </div>

		        
		        @else
		        @include('payment.partials.alipay_inputs')

		        
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
			@include('payment.partials.alipay_cash')
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
		            	{!! UploadBox::single('file', $data->approved_upload ?? '', true) !!}
		            </div>
		        </div>
		        
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
		            <div class="col-md-8 ">
	                	<textarea name="approved_remark" rows="5" class="form-control">{{ $data->approved_remark ?? '' }}</textarea>
		            </div>
		        </div>
		    </div>
		</div>
	</div>

</form>
@include('payment.partials.balance_list', ['colClass' => 'col-xs-12 col-md-12', 'w1' => 190, 'w2' => 140, 'br' => '<br />'])

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
	$(function() {
		$("#upload_file").removeClass("validate[requiredattachment]");
		$("#upload_bank_file").removeClass("validate[requiredattachment]");
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

	function rollback() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $id }}',
	            url: rooturl + '_admin/_alipay/rollback',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}

	function canceling2() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $id }}&cancel_remark=' + $("#cancel_remark").val(),
	            url: rooturl + '_admin/_alipay/cancel',
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

	$(function() {
		$('#my_multi_select3').multiSelect({
	        selectableHeader: "<input type='text' class='form-control search-input' autocomplete='off' placeholder='{!! res('search') !!}'>",
	        selectionHeader: "<input type='text' class='form-control search-input' autocomplete='off' placeholder='{!! res('search') !!}'>",
	        afterInit: function (ms) {
	            var that = this,
	                $selectableSearch = that.$selectableUl.prev(),
	                $selectionSearch = that.$selectionUl.prev(),
	                selectableSearchString = '#' + that.$container.attr('id') + ' .ms-elem-selectable:not(.ms-selected)',
	                selectionSearchString = '#' + that.$container.attr('id') + ' .ms-elem-selection.ms-selected';

	            that.qs1 = $selectableSearch.quicksearch(selectableSearchString)
	                .on('keydown', function (e) {
	                    if (e.which === 40) {
	                        that.$selectableUl.focus();
	                        return false;
	                    }
	                });

	            that.qs2 = $selectionSearch.quicksearch(selectionSearchString)
	                .on('keydown', function (e) {
	                    if (e.which == 40) {
	                        that.$selectionUl.focus();
	                        return false;
	                    }
	                });
	        },
	        afterSelect: function () {
	            this.qs1.cache();
	            this.qs2.cache();
	        },
	        afterDeselect: function () {
	            this.qs1.cache();
	            this.qs2.cache();
	        }
	    });
	});
</script>@endsection
