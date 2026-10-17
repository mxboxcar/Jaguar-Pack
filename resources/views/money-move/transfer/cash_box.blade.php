{{-- กล่อง "รายละเอียดการใช้เงิน" ของ _transfer/form.php และ _admin/_transfer/form.php เดิม (เหมือนกันทุกตัวอักษร) --}}
@php
    $rateNum = \App\Domain\MoneyMove\Php74::num($rate);
@endphp
@if ($new)
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('want_to_transfer') !!}</label>
		            <div class="col-md-6">
		            	<div class="input-group">
		            		<span class="input-group-addon">
				        		{!! res('cn_currency') !!}
				        	</span>
	                		<input  type="text" name="total" class="form-control addcomma  text-right validate[required]" value="{{ $d['amount'] ?? '' }}" >
		                </div>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('rate') !!}</label>
		            <div class="col-md-4 form-control-static">
	                	 ¥1 <i class="fa fa-arrows-h"></i> <span class="currency">{{ $rate }}</span>&nbsp;฿
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('est_transfer') !!}</label>
		            <div class="col-md-4 form-control-static">
	                	{{-- เดิม getCurrentCash() / rate (rate = 0 → PHP 7.4 ได้ false + warning = 0) --}}
	                	{!! \App\Support\Legacy\Format::numberformat($rateNum == 0 ? 0 : \App\Domain\MoneyMove\Php74::num(\App\Support\Legacy\Lookup::getCurrentCash()) / $rateNum, true, true, true) !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('your_balance') !!}</label>
		            <div class="col-md-7 form-control-static">{!! \App\Support\Legacy\Format::numberformat(\App\Support\Legacy\Lookup::getCurrentCash()) !!}</div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
		            <div class="col-md-8">
		            	<textarea name="remark" class="form-control" rows="5"></textarea>
		            </div>
		        </div>
@else
	        	<div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('want_to_transfer') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{!! \App\Support\Legacy\Format::numberformat($d['total'] ?? null, true, true, true) !!}
		            </div>
		        </div>
		        
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('rate') !!}</label>
		            <div class="col-md-4 form-control-static">
	                	 ¥1 <i class="fa fa-arrows-h"></i> <span class="currency">{{ $d['rate'] ?? '' }}</span>&nbsp;฿
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('using_system_cash') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{!! \App\Support\Legacy\Format::numberformat($d['usecash'] ?? null) !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
		            <div class="col-md-8 form-control-static">
		            	{!! nl2br(e($d['remark'] ?? '')) !!}
		            </div>
		        </div>
@endif
