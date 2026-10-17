{{-- _refill/form.php เดิม (ลูกค้า) — $id = (int) ของ GET id; 0 = สร้างใหม่ --}}
@extends('legacy.layout')
@php
    use App\Domain\MoneyMove\Php74;
    use App\Domain\MoneyMove\UploadBox;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Lookup;
    use App\Support\Legacy\Status;
    $new = $id === 0;
    $disabled = $new ? '' : 'disabled';
@endphp

@section('content')
@if ($new)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('refill') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
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
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
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
<form action="{{ legacy_href() }}_refill/save" method="post">
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



		    <a type="button" href="{{ legacy_href() }}refill" class="pull-right btn btn-warning waves-effect waves-light">
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
		            <label class="col-md-5 control-label"> {!! res('your_balance') !!}</label>
		            <div class="col-md-4 form-control-static">{!! Format::numberformat(Lookup::getCurrentCash()) !!}</div>
		        </div>
{{-- เดิม if($id != "") — PHP 7.4: (int)0 != "" เป็น false → id 0 = ฟอร์มกรอก --}}
@if (! $new)
		        <div class="form-group">
		            <label class="col-md-5 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	{{ $prefix }}{{ $d['id'] ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label"> {!! res('status') !!}</label>
		            <div class="col-md-4 form-control-static">
		            	{!! Status::getstatus($d['status'] ?? null) !!}@if (Php74::eq($d['status'] ?? null, -1))<div style='margin-top: 10px;' class='text-danger'>* {{ explode(',ยกเลิกโดย', (string) ($d['cancel_remark'] ?? ''))[0] }}</div>@endif

		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label"> {!! res('add_balance') !!}</label>
		            <div class="col-md-4 form-control-static">{!! Format::numberformat($d['amount'] ?? null) !!}</div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label"> {!! res('working_date') !!}</label>
		            <div class="col-md-6">
		            	{!! Format::getthaidate(explode(' ', (string) ($d['date'] ?? ''))[0]) !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label"> {!! res('attachment') !!}</label>
		            <div class="col-md-7">
		            	{!! UploadBox::single('file', $d['attachment'] ?? '') !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-5 control-label"> {!! res('remark') !!}</label>
		            <div class="col-md-6">
		            	{!! nl2br(e($d['remark'] ?? '')) !!}
		            </div>
		        </div>
@else
		        <div class="form-group">
		            <label class="col-md-5 control-label"> {!! res('add_balance') !!}</label>
		            <div class="col-md-4">
		            	<div class="input-group">
		                	<input {{ $disabled }} type="text" name="amount" class="form-control addcomma  text-right validate[required, custom[number]]" value="{{ $d['amount'] ?? '' }}" >
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
		                	<input {{ $disabled }}  type="text" class="form-control datepicker" name="date" >
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
		            	<textarea {{ $disabled }} name="remark" rows="5" class="form-control">{{ $d['remark'] ?? '' }}</textarea>
		            </div>
		        </div>
@endif

		    </div>
		</div>
	</div>

<div class="col-sm-6">
	<div class="col-lg-12">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('bank_haveto_transfer') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
				<div class="form-horizontal" role="form">                                    
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('acc_name') !!}</label>
			            <div class="col-md-4 form-control-static">
			            	{!! Lookup::getSetting('bank_acc') !!}
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('acc_no') !!}</label>
			            <div class="col-md-4 form-control-static">
			            	{!! Format::bankformat(Lookup::getSetting('bank_no'), false) !!}
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('acc_bank') !!}</label>
			            <div class="col-md-4 form-control-static">
			            	{!! Lookup::getSetting('bank_name') !!}
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('acc_branch') !!}</label>
			            <div class="col-md-4 form-control-static">
			            	{!! Lookup::getSetting('bank_branch') !!}
			            </div>
			        </div>
				</div>
		    </div>
		</div>
	{!! $balanceTable !!}
</div>
@if ($new)
</form>
@endif


<script>
	initialDatepicker();
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


	$(function() {
		$("form").validationEngine();
		$("form").ajaxForm({ 
		    success: function(data) {
		        eval(data);
		    }
		});
	});
	

@include('money-move.partials.multiselect')
</script>
@endsection
