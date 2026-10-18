{{-- _admin/_warehouse/load_tracking.php / load_tracking_edit.php เดิม --}}
    <input type="hidden" name="id" value="{{ $d['id'] }}" />
    <input type="hidden" name="warehouse_id" value="{{ $warehouseId }}" />
    <div class="form-horizontal" role="form">
                 
        <div class="form-group">
            <label class="col-md-3 control-label"> {!! res('tracking') !!}</label>
            <div class="col-md-7">
                <input type="text" name="tracking" class="form-control validate[required]" value="{{ $d['tracking'] }}" >
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-3 control-label"> {!! res('customer_code') !!}</label>
            <div class="col-md-7 form-control-static">{{ $d['code'] }}</div>
        </div>


        <div class="form-group">
            <label class="col-md-3 control-label"> {!! res('shelf_manage') !!}</label>
            <div class="col-md-7 form-control-static">
            {{ $d['section_name'] }}</div>
        </div>
        <div class="form-group">
            <label class="col-md-3 control-label"> {!! res('amount') !!}</label>
            <div class="col-md-7 form-control-static">{!! \App\Support\Legacy\Format::numberformatzero($d['amount']) !!}</div>
        </div>
        <div class="form-group">
            <label class="col-md-3 control-label"> {!! res('weight_cn') !!}</label>
            <div class="col-md-7 form-control-static">{!! \App\Support\Legacy\Format::numberformatone($d['weight']) !!} {!! res('kg') !!}</div>
        </div>

        <div class="form-group">
            <label class="col-md-3 control-label"> {!! res('weight_thai') !!}</label>
            <div class="col-md-5">
                <div class="input-group">
                    <input type="text" name="weight" class="addcomma form-control validate[required]" value="{{ $weightValue }}"  />
                    <span class="input-group-addon">{!! res('kg') !!}</span>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="col-md-3 control-label"> {!! res('cubic') !!}</label>
            <div class="col-md-7 form-control-static">{!! \App\Support\Legacy\Format::cubicformat(((\App\Domain\Warehouse\Php74::num($d['width']) * \App\Domain\Warehouse\Php74::num($d['height']) * \App\Domain\Warehouse\Php74::num($d['long'])) / 1000000) * \App\Domain\Warehouse\Php74::num($d['amount'])) !!}</div>
        </div>

    </div>
    
