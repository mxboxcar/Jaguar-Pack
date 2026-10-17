{{-- _balance_cn/load_withdraw.php เดิม --}}
<div class="form-horizontal col-xs-12" role="form">    
	<div class="form-group">
	    <label class="col-md-4 control-label"> {!! res('exchange_rate') !!}</label>
	    <div class="col-md-7 form-control-static">
	    	{!! res('cn_currency') !!}1 = {!! \App\Support\Legacy\Format::numberformat(e((string) $rate)) !!}
	    </div>
	</div>
	<div class="form-group">
	    <label class="col-md-4 control-label"> {!! res('cash_cn') !!}</label>
	    <div class="col-md-7">
	    	<div class="input-group">
	    		<span class="input-group-addon">{!! res('cn_currency') !!}</span>
	        	<input type="text" id="wantcash" class="form-control" value="{{ $current }}" >
	    		
	    	</div>
	    </div>
	</div>
</div>
