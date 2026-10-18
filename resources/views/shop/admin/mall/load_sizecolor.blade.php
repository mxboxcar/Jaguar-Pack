{{-- _admin/_mall/load_sizecolor.php เดิม (mall_id 0 = ตอบว่าง: controller) --}}
<div class="form-horizontal">
    <div class="form-group">
        <label class="col-md-4 control-label"> {!! res('color') !!}</label>
        <div class="col-md-4">
            <input type="hidden" name="id" value="{{ $id }}" />
            <input type="hidden" name="mall_id" value="{{ $mallId }}" />
            <input type="text" class="form-control" name="color" value="{{ $data->color ?? '' }}" />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 control-label"> {!! res('size') !!}</label>
        <div class="col-md-4">
            <input type="text" class="form-control" name="size" value="{{ $data->size ?? '' }}" />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 control-label"> {!! res('stock_remain') !!}</label>
        <div class="col-md-4">
            
            <div class="input-group">
                <input type="text" class="form-control" name="stock"  value="{{ $stock }}" />
                <span class="input-group-addon">{!! res('piece') !!}</span>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 control-label"> {!! res('price') !!}</label>
        <div class="col-md-4">
            <div class="input-group">
                <input type="text" class="form-control" name="price" value="{{ $data->price ?? '' }}" />
                <span class="input-group-addon">{!! res('thai_currency') !!}</span>
            </div>
        </div>

    </div>

    <div class="form-group">
        <label class="col-md-4 control-label"> {!! res('point') !!}</label>
        <div class="col-md-4">
            <div class="input-group">
                <input type="text" class="form-control" name="point" value="{{ $data->point ?? '' }}" />
                <span class="input-group-addon">p.</span>
            </div>
            
        </div>
    </div>


</div>
