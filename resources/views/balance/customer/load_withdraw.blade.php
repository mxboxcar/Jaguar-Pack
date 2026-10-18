{{-- _balance/load_withdraw.php เดิม --}}
<div class="form-horizontal col-xs-12" role="form">    
	<div class="form-group">
	    <label class="col-md-4 control-label"> {!! res('your_balance') !!}</label>
	    <div class="col-md-7">
	    	<div class="input-group">
	        	<input type="text" id="wantcash" class="form-control" value="{{ $current }}" >
	    		<span class="input-group-addon">฿</span>
	    	</div>
	    </div>
	</div>

	<div class="form-group">
	    <label class="col-md-4 control-label"> {!! res('bank_no') !!}</label>
	    <div class="col-md-7">
	        	<input type="text" id="bank_no" class="form-control"  >
	    </div>
	</div>

	<div class="form-group">
	    <label class="col-md-4 control-label"> {!! res('bank_name') !!}</label>
	    <div class="col-md-7">
	        	<input type="text" id="branch_bank" class="form-control"  >
	    </div>
	</div>

	<div class="form-group">
	    <label class="col-md-4 control-label"> {!! res('bank_acc') !!}</label>
	    <div class="col-md-7">
	        	<input type="text" id="branch_name" class="form-control"  >
	    </div>
	</div>
</div>
