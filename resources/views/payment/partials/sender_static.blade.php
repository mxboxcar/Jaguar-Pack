@php use App\Support\Legacy\Format; @endphp
<div class="form-group">
        			<div class="col-md-8 col-md-offset-4">
        				<span class="text-danger">{!! res('extra_charge_sender') !!} {!! Format::numberformat($data->sender_charge) !!}</span>
        			</div>
        		</div>
        		<div class="form-group">
		            <label class="col-md-4 control-label">{!! res('sender_name') !!}</label>
		            <div class="col-md-8 form-control-static">
	                	{{ $data->sender_name }}
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label">{!! res('sender_address') !!}</label>
		            <div class="col-md-8 form-control-static">
		            	{{ $data->sender_address }}
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label">{!! res('sender_phone') !!}</label>
		            <div class="col-md-8 form-control-static">
	                	{{ $data->sender_phone }}
		            </div>
		        </div>
