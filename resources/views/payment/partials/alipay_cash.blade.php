@php use App\Support\Legacy\Format; @endphp
@if ($isNew)
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('want_to_refill') !!}</label>
		            <div class="col-md-6">
		            	<div class="input-group">
		            		<span class="input-group-addon">
				        		{!! res('cn_currency') !!}
				        	</span>
	                		<input  type="text" name="total" class="form-control addcomma  text-right validate[required]" value="" >
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
	                	{!! Format::numberformat($est, true, true, true) !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('your_balance') !!}</label>
		            <div class="col-md-7 form-control-static">{!! Format::numberformat($cash) !!}</div>
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
		            	{!! Format::numberformat($data->total ?? null, true, true, true) !!}
		            </div>
		        </div>
		        
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('rate') !!}</label>
		            <div class="col-md-4 form-control-static">
	                	 ¥1 <i class="fa fa-arrows-h"></i> <span class="currency">{{ $data->rate ?? "" }}</span>&nbsp;฿
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('using_system_cash') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{!! Format::numberformat($data->usecash ?? null) !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
		            <div class="col-md-8 form-control-static">
		            	{!! nl2br(e($data->remark ?? "")) !!}
		            </div>
		        </div>
@endif
