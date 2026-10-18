{{-- _admin/_warehouse/load_editshelf.php เดิม --}}
    <input type="hidden" name="id" value="{{ $d['id'] }}" />
    <input type="hidden" name="master_warehouse_id" value="{{ $warehouseId }}" />
    <input type="hidden" name="user_id" value="{{ $d['user_id'] }}" />
    <div class="form-horizontal" role="form">
                 
        <div class="form-group">
            <label class="col-md-3 control-label"> {!! res('member_code') !!}</label>
            <div class="col-md-7 form-control-static">{{ $d['code'] }}</div>
        </div>
        <div class="form-group">
            <label class="col-md-3 control-label"> จำนวนที่รอการนำเข้า</label>
            <div class="col-md-7 form-control-static">
                {{ $waiting }}
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-3 control-label"> {!! res('shelf_manage') !!}</label>
            <div class="col-md-4 ">
                <select name="warehouse_id" class="form-control " >
                    <option value="0">{!! res('please_select') !!}</option>@foreach($sections as $s)@if($current !== null && (string) $s->id === $current)<option selected="selected" value="{{ $s->id }}">{{ $s->name }}</option>@else<option value="{{ $s->id }}">{{ $s->name }}</option>@endif @endforeach
                </select>
            </div>
        </div>

    </div>
    
