{{-- _payment/load_receipt_address.php เดิม — ตาราง user_receipt_address ไม่มีใน DB: ผลเดิมคือฟอร์มว่าง + ไม่มีข้อมูล --}}

<input type="hidden" id="address_id" value="" />
<div class="form-horizontal" role="form">                                    
    <div class="form-group">
        <label class="col-md-3 control-label">{!! res('personal_type') !!}</label>
        <div class="col-md-9" style="    padding: 6px;">
            <input type="radio" name="type" value="0" /> {!! res('personal') !!}<br/>
            <input type="radio" name="type" value="1" /> {!! res('companys') !!} <br/>
        </div>
    </div>
    <div class="form-group">
        <label class="col-md-3 control-label">{!! res('com_name') !!}</label>
        <div class="col-md-4">
        	<input type="text" name="re_name" class="form-control" value="" >
        </div>
    </div>
    <div class="form-group">
        <label class="col-md-3 control-label">{!! res('address') !!}</label>
        <div class="col-md-4">
        	<textarea class="form-control" rows="5" name="re_address"></textarea>
        </div>
    </div>
    <div class="form-group">
        <label class="col-md-3 control-label">{!! res('phone') !!}</label>
        <div class="col-md-4">
        	<input type="text" name="re_phone" class="form-control" value="" >
        </div>
    </div>
    <div class="form-group">
        <label class="col-md-3 control-label">{!! res('tax') !!}</label>
        <div class="col-md-4">
        	<input type="text" name="re_tax" class="form-control" value="" >
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-3 control-label">{!! res('tax_proof') !!}</label>
        <div class="col-md-8 ">
        	{!! $upload !!}
    	</div>
    </div>

    <div class="form-group">
        <label class="col-md-3 control-label"></label>
        <div class="col-md-8 text-danger">
        	(บัตรประจำตัวประชาชน หรือ ใบรับรองบริษัท)
    	</div>
    </div>

    
</div>

<table class="table table-striped table-bordered">
    <thead>
        <tr>
        	<th style="width:60px"></th>
            <th class="text-center">{!! res('com_name') !!}</th>
            <th class="text-center">{!! res('address') !!}</th>
            <th class="text-center">{!! res('phone') !!}</th>
            <th class="text-center">{!! res('tax') !!}</th>
            <th class="text-center">{!! res('personal_type') !!}</th>
            <th class="text-center" style="width:80px">{!! res('document') !!}</th>
            <th class="text-center" style="width:80px">{!! res('action') !!}</th>
        </tr>
    </thead>
   	<tbody>


		<tr>
			<td class="text-center" colspan="7">{!! res('no_data') !!}</td>
		</tr>

	</tbody>
</table>
