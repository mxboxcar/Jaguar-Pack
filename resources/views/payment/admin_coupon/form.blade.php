{{-- _admin/_coupon/form.php เดิม (PACK) — GET admin/coupon/create (id 0) และ admin/coupon/{id}; สิทธิ์ admin_coupon ที่ middleware
     เดิม: id = 0 → ฟอร์มเพิ่ม (ชื่อ = randd(6)); id อื่น (แม้ไม่พบแถว) → ฟอร์มแก้: คูปองที่ยังไม่ถูกใช้แก้ชื่อ/ราคาได้, ที่ใช้แล้วแสดงอย่างเดียว
     (ลิงก์ "เลขบิล" เดิมชี้ admin/payment/<id ของคูปอง> แต่ข้อความเป็นเลขบิลจริง — คงไว้แบบเดิม ดูรายงาน) --}}
@extends('legacy.layout')
@php
    use App\Domain\Payment\Php74;
@endphp

@section('content')
@if ($id === 0)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('coupon') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('coupon') !!}
		</li>
		<li class="active">
			{!! res('coupon_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('coupon_edit') !!} <small>{{ $prefix.($data->id ?? '') }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('coupon') !!}
		</li>
		<li class="active">
			{{ $prefix.($data->id ?? '') }}
		</li>
	</ol>
</div>
@endif

<form action="{{ legacy_href() }}_admin/_coupon/save" method="post">

<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>

		    <a type="button" href="{{ legacy_href() }}admin/coupon" class="pull-right btn btn-warning waves-effect waves-light">
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
				@if ($id !== 0)
				<div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-7  form-control-static">
		                {{ $prefix.($data->id ?? '') }}
		            </div>
		        </div>         

		        @if (Php74::eq($data->payment_id ?? null, 0))
		        	<div class="form-group">
			            <label class="col-md-3 control-label"> {!! res('name') !!}</label>
			            <div class="col-md-7">
			                <input type="text" name="name" class="form-control validate[required]" value="{{ $data->name ?? '' }}" >
			            </div>
			        </div>

			        <div class="form-group">
			            <label class="col-md-3 control-label"> ส่วนลด</label>
			            <div class="col-md-7">
			                <div class="input-group">
				            	<input type="text" name="price" onkeyup="ontempcal()" class="addcomma form-control validate[required]" value="{{ $data->price ?? '' }}"  />
				            	<span class="input-group-addon">{!! res('thai_currency') !!}</span>
				            </div>
			            </div>
			        </div>
		        @else

		        	<div class="form-group">
			            <label class="col-md-3 control-label"> รหัส</label>
			            <div class="col-md-7  form-control-static">
			                {{ $data->name }}
			            </div>
			        </div>   

		        	<div class="form-group">
			            <label class="col-md-3 control-label"> ส่วนลด</label>
			            <div class="col-md-7  form-control-static">
			                <span class="  addcomma currency">{{ $data->price }}</span> {!! res('thai_currency') !!}
			            </div>
			        </div>      

			        <div class="form-group">
			            <label class="col-md-3 control-label"> เลขบิล</label>
			            <div class="col-md-7  form-control-static">
			               	<a href="{{ legacy_href() }}admin/payment/{{ $data->id }}">{{ $payPrefix.$data->payment_id }}</a>
			            </div>
			        </div>      
		        @endif
		        @else

		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('name') !!}</label>
		            <div class="col-md-7">
		                <input type="text" name="name" class="form-control validate[required]" value="{{ $newName }}" >
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label"> ส่วนลด</label>
		            <div class="col-md-7">
		                <div class="input-group">
			            	<input type="text" name="price" onkeyup="ontempcal()" class="addcomma form-control validate[required]" value=""  />
			            	<span class="input-group-addon">{!! res('thai_currency') !!}</span>
			            </div>
		            </div>
		        </div>



		    	@endif

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
