{{-- _alipay/form.php เดิม (GET alipay/create = id 0, alipay/{id} = บิลของผู้ใช้นี้) --}}
@extends('legacy.layout')
@php
    use App\Domain\Payment\Php74;
    use App\Domain\Payment\UploadBox;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $isNew = $id === 0;
    $no = $prefix.($data->id ?? '');
@endphp

@section('content')
@if ($isNew)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('alipay') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}">{!! res('dashboard') !!}</a>
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
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
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

@if ($isNew)
<form action="{{ legacy_href() }}_alipay/save" method="post">
@endif

<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			@if ($isNew)
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>
		    @endif

		    @if (Php74::eq($data->status ?? null, 1))
			<button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('cancel_this') !!}
		    </button>
		    @endif



		    <a type="button" href="{{ legacy_href() }}alipay" class="pull-right btn btn-warning waves-effect waves-light">
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
		            <label class="col-md-4 control-label"> {!! res('status') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{!! Status::getstatus($data->status) !!}@if (Php74::eq($data->status, -1))<div style='margin-top: 10px;' class='text-danger'>* {{ explode(',ยกเลิกโดย', (string) $data->cancel_remark)[0] }}</div>@endif
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('taobao_id') !!}</label>
		            <div class="col-md-6 form-control-static">
	                	{{ $data->taobao_id }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('taobao_password') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	******
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('alipay_id') !!}</label>
		            <div class="col-md-6 form-control-static">
	                	{{ $data->alipay_id }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('alipay_password') !!}</label>
		            <div class="col-md-6 form-control-static">
	                	******
		            </div>
		        </div>
		        
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('password_alipay') !!}</label>
		            <div class="col-md-6 form-control-static">
	                	******
		            </div>
		        </div>
		        
		        @else
		        @include('payment.partials.alipay_inputs')
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('attachment') !!}</label>
		            <div class="col-md-6 form-control-static">
	                	{!! UploadBox::single('file', $data->attachment ?? '', true) !!}
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
			@include('payment.partials.alipay_cash')
		    </div>
		</div>
	</div>
	@if (Php74::eq($data->status ?? null, 2))
	<div class="col-sm-6">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('approved_proof') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">
	        	<div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('attachment') !!}</label>
		            <div class="col-md-8 ">
		            	{!! UploadBox::single('file', $data->approved_upload) !!}
		            </div>
		        </div>
		        
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
		            <div class="col-md-8 form-control-static">
	                	{!! Php74::eq($data->approved_remark, '') ? '-<br />-' : nl2br(e($data->approved_remark)) !!}
		            </div>
		        </div>
		    </div>
		</div>
	</div>
	@endif
@if ($isNew)
</form>
@else
@include('payment.partials.balance_list', ['colClass' => 'col-xs-12 col-md-12', 'w1' => 190, 'w2' => 140, 'br' => '<br />'])
@endif

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
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $id }}' ,
	            url: rooturl + '_alipay/cancel',
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
