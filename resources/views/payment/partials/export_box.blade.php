{{-- ส่วน "ข้อมูลการส่งออก" ของ form.php (ลูกค้า: มีหลักฐาน, แอดมิน: ไม่มี) --}}
@php
    use App\Support\Legacy\Format;
    $exportDate = '';
    if ($data->export_date !== '0000-00-00 00:00:00' && (string) $data->export_date !== '') {
        $exportDate = Format::getthaidate(explode(' ', (string) $data->export_date)[0]);
    }
@endphp

	<input type="hidden" name="id" value="{{ $id }}">
	<div class="col-xs-12 col-md-6">
    <div class="card-box">
    <h4 class="m-t-0 header-title"><b>{!! res('export_thing') !!}</b></h4>
    <p class="text-muted font-13 m-b-30">
    </p>
    	<div class="form-horizontal" role="form">
            <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('export_tracking') !!}</label>
	            <div class="col-md-7 form-control-static">{{ $data->export_tracking }}</div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('export_price') !!}</label>
	            <div class="col-md-5">
	            	<div class="input-group form-control-static"><span class=" addcomma currency">{{ $data->export_price }}</span> {!! res('thai_currency') !!}</div>
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
	            <div class="col-md-7 form-control-static">{!! nl2br(e($data->export_remark)) !!}</div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('export_date') !!}</label>
	            <div class="col-md-7 form-control-static">{!! $exportDate !!}</div>
	        </div>
	        @if ($withProof)
	        <div class="form-group">
	            <label class="col-md-4 control-label"> {!! res('approved_proof') !!}</label>
	            <div class="col-md-7 form-control-static">{!! Format::getFancyBox($data->export_attachment) !!}</div>
	        </div>
	        @endif
        </div>
    </div>
</div>

