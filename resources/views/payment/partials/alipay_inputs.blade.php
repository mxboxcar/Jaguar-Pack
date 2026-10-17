<div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('taobao_id') !!}</label>
		            <div class="col-md-8">
	                	<input  type="text" name="taobao_id" class="form-control addcomma  text-left" value="{{ $data->taobao_id ?? '' }}" >
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('taobao_password') !!}</label>
		            <div class="col-md-8">
	                	<input  type="password" name="taobao_password" class="form-control addcomma  text-left" value="{{ $data->taobao_password ?? '' }}" >
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('alipay_id') !!}</label>
		            <div class="col-md-8">
	                	<input  type="text" name="alipay_id" class="form-control addcomma  text-left" value="{{ $data->alipay_id ?? '' }}" >
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('alipay_password') !!}</label>
		            <div class="col-md-8">
	                	<input  type="password" name="alipay_password" class="form-control addcomma  text-left" value="{{ $data->alipay_password ?? '' }}" >
		            </div>
		        </div>
		        
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('password_alipay') !!}</label>
		            <div class="col-md-8">
	                	<input  type="password" name="password_alipay" class="form-control addcomma  text-left" value="" >
		            </div>
		        </div>
