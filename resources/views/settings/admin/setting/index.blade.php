{{-- _admin/_setting/index.php เดิม (สิทธิ์ admin_setting ตรวจที่ middleware legacy.perm) — ค่า setting escape ด้วย {{ }} (เดิม echo ดิบ) --}}
@php use App\Support\Legacy\Format; @endphp
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('setting') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			{!! res('setting') !!}
		</li>
	</ol>
</div>

<form action="{{ legacy_href() }}_admin/_setting/save" method="post">

<input type="hidden" name="id" value="">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>
		</div>
	</div>


	<div class="col-sm-8">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('rate') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">                                    
		        
		        
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('rate_order') !!}</label>
		            <div class="col-md-4">
			            <div class="input-group ">
			            	<input  type="text" name="rate_order" class="addcomma form-control text-right validate[required]" value="{{ Format::numberformat($s['rate_order'] ?? null, false, false) }}" >
			                	<span class="input-group-addon">{!! res('thai_currency') !!}</span>
			        	</div>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('rate_refill') !!}</label>
		            <div class="col-md-4">
			            <div class="input-group ">
			            	<input  type="text" name="rate_alipay" class="addcomma form-control text-right validate[required]" value="{{ Format::numberformat($s['rate_alipay'] ?? null, false, false) }}" >
			                	<span class="input-group-addon">{!! res('thai_currency') !!}</span>
			        	</div>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('rate_transfer') !!}</label>
		            <div class="col-md-4">
			            <div class="input-group ">
			            	<input  type="text" name="rate_transfer" class="addcomma form-control text-right validate[required]" value="{{ Format::numberformat($s['rate_transfer'] ?? null, false, false) }}" >
			                	<span class="input-group-addon">{!! res('thai_currency') !!}</span>
			        	</div>
		            </div>
		        </div>
		    </div>

		    <div class=" col-xs-12 dashed"></div>

		    <h4 class="m-t-0 header-title"><b>{!! res('bank') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">                                    
		        
		        
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('bank_acc') !!}</label>
		            <div class="col-md-6">
		            	<input  type="text" name="bank_acc" class="addcomma form-control text-left validate[required]" value="{{ $s['bank_acc'] ?? '' }}" >
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('bank_no') !!}</label>
		            <div class="col-md-6">
		            	<input  type="text" name="bank_no" class="addcomma form-control text-left validate[required]" value="{{ $s['bank_no'] ?? '' }}" >
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('bank_name') !!}</label>
		            <div class="col-md-6">
		            	<input  type="text" name="bank_name" class="addcomma form-control text-left validate[required]" value="{{ $s['bank_name'] ?? '' }}" >
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('bank_branch') !!}</label>
		            <div class="col-md-6">
		            	<input  type="text" name="bank_branch" class="addcomma form-control text-left validate[required]" value="{{ $s['bank_branch'] ?? '' }}" >
		            </div>
		        </div>
		    </div>

		    <div class=" col-xs-12 dashed"></div>

		    <h4 class="m-t-0 header-title"><b>{!! res('dashboard') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">                                    
		        
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('how_to_cal_wood_protect') !!}</label>
		            <div class="col-md-6">
		            	<input  type="text" name="calculate_woodprotect" class="addcomma form-control text-left validate[required]" value="{{ $s['calculate_woodprotect'] ?? '' }}" >
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('cn_address') !!}</label>
		            <div class="col-md-6">
		            	<textarea rows="5" name="address_cn" class="addcomma form-control text-left validate[required]" >{{ $s['address_cn'] ?? '' }}</textarea>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('cn_tel') !!}</label>
		            <div class="col-md-6">
		            	<input  type="text" name="phone_cn" class="addcomma form-control text-left validate[required]" value="{{ $s['phone_cn'] ?? '' }}" >
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('th_tel') !!}</label>
		            <div class="col-md-6">
		            	<input  type="text" name="phone_th" class="addcomma form-control text-left validate[required]" value="{{ $s['phone_th'] ?? '' }}" >
		            </div>
		        </div>

		    </div>

		    <div class=" col-xs-12 dashed"></div>

		    <h4 class="m-t-0 header-title"><b>{!! res('else') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">                                    
		        
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('service_send_else_name') !!}</label>
		            <div class="col-md-6">
		            	<input  type="text" name="change_sender" class="addcomma form-control text-left validate[required]" value="{{ $s['change_sender'] ?? '' }}" >
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('exchange_rate') !!}</label>
		            <div class="col-md-6">
		            	<input  type="text" name="exchange_rate" class="addcomma form-control text-left validate[required]" value="{{ $s['exchange_rate'] ?? '' }}" >
		            </div>
		        </div>


		        <div class="form-group">
		            <label class="col-md-4 control-label"> ล๊อตเข้าไทยวันนี้ (แดง)</label>
		            <div class="col-md-6">
		            	<textarea rows="10" name="today_lot" class=" form-control text-left" >{{ $s['today_lot'] ?? '' }}</textarea>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> ล๊อตเข้าไทยวันนี้ (เหลือง)</label>
		            <div class="col-md-6">
		            	<textarea rows="10" name="today_lot_yellow" class=" form-control text-left" >{{ $s['today_lot_yellow'] ?? '' }}</textarea>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> ล๊อตเข้าไทยวันนี้ (เขียว)</label>
		            <div class="col-md-6">
		            	<textarea rows="10" name="today_lot_green" class=" form-control text-left" >{{ $s['today_lot_green'] ?? '' }}</textarea>
		            </div>
		        </div>
		    </div>


		</div>
	</div>

</form>

<script>
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


	$("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	    }
	});

</script>
@endsection
