{{-- SR _admin/_lot/load_quickfile.php เดิม (เนื้อหา modal แนบรูปพัสดุ → quicksavefile() = save_quickfile) --}}

<input type="hidden" value="{{ $data->id ?? '' }}" id="quicksave_tracking_id" />
<div class="form-group" id="attachment_tracking">
    <div class="form-horizontal" role="form"> 
        <div class="form-group">
            <label class="col-xs-3 control-label">{!! res('tracking') !!}</label>
            <div class="col-xs-7 form-control-static">
                {{ $data->tracking ?? '' }}
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-3 control-label">{!! res('no') !!}</label>
            <div class="col-xs-7 form-control-static">
                {{ $data->no ?? '' }}
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-3 control-label">{!! res('attachment') !!}</label>
            <div class="col-xs-7 form-control-static">
                {!! \App\Domain\Tracking\UploadBox::multiple('upload_trackquick', '', true) !!}
            </div>
        </div>

        <div class="form-group">
            <div class="col-xs-9 col-xs-offset-3" id="image_list">
            @include('lot.admin.image_list', ['list' => $list, 'field' => 'upload_trackquick'])
            </div>
        </div>
            </div>
</div>
