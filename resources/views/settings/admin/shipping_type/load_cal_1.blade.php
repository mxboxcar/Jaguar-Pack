{{-- _admin/_shipping_type/load_cal_1.php เดิม --}}
@php use App\Support\Legacy\Format; @endphp

<div class="form-group">
    <label class="col-md-3 control-label"> {!! res('min_value') !!}</label>
    <div class="col-md-7">
        <div class="  input-group ">
            <input type="text" readonly="readonly" class="min_value form-control " value="{{ $minValue }}" >
            <input type="hidden" name="min_value" value="{{ $minValue }}" />
            <span class="input-group-addon">
        		{!! res('kg') !!}
        	</span>
        </div>
    </div>
</div>
<div class="form-group">
    <label class="col-md-3 control-label"> {!! res('max_value') !!}</label>
    <div class="col-md-7">
        <div class="  input-group ">
            <input type="text" name="max_value" class="form-control " value="" >
            <span class="input-group-addon">
        		{!! res('kg') !!}
        	</span>
        </div>
    </div>
</div>
<div class="form-group">
    <label class="col-md-3 control-label"> {!! res('price') !!}</label>
    <div class="col-md-7">
        <div class="  input-group ">
            <input type="text" name="cal_1_price" class="form-control " value="" >
            <span class="input-group-addon">
        		{!! res('thai_currency') !!}
        	</span>
        </div>
    </div>
</div>
<div class="form-group">
    <label class="col-md-3 control-label"> </label>
    <div class="col-md-7">
        <button onclick="addnew1()" type="button" class="btn btn-success waves-effect waves-light">
			<span class="btn-label"><i class="fa fa-save"></i></span>
			{!! res('add_new_list') !!}
		</button>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th class="text-center" >{!! res('min_value') !!}</th>
                <th class="text-center" >{!! res('max_value') !!}</th>
                <th class="text-center" style="width: 120px;">{!! res('price') !!}</th>
            	<th class="text-center" style="width: 60px;"></th>
            </tr>
        </thead>

        <tbody>
@foreach($rates as $data)
	<tr>
		<td class="text-center">
            {!! Format::numberformat($data->min, true, false, false) !!}
            <input type="hidden" name="rate_id[]" value="{{ $data->id }}" />
        </td>
		<td class="text-center">{!! Format::numberformat($data->max, true, false, false) !!}</td>
		<td class="text-center">{!! Format::numberformat($data->value) !!}</td>
		<td class="text-center">
			<a type="button" href="#" onclick="delete_cal({{ $data->id }});return false;" class="table-action-btn"><i class="md  md-cancel text-danger"></i></a>
		</td>
	</tr>        	
@endforeach

		</tbody>
    </table>
</div>
