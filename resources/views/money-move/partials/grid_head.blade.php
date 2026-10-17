{{-- ส่วนหัว grid (ช่องค้นหา + สถานะ) ที่ grid.php เดิมทุกไฟล์ของกลุ่มใช้เหมือนกัน --}}
@php
    $sel = fn ($v) => \App\Domain\MoneyMove\Php74::eq($status, $v) ? "selected='selected'" : '';
@endphp
<div class="row">
	<div class="col-lg-12">
		<div class="card-box">
			<div class="row m-t-10 m-b-10">
            	<div class="col-sm-6 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                    	<input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{{ $search }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
            	</div>

                <div class="col-xs-2 col-sm-4 col-lg-4">
                    <select name="role" class="form-control " id="status" onchange="GenerateWhere()">
                        <option {!! $sel('-2') !!} value="-2">- {!! res('all') !!} -</option>  
                        <option {!! $sel('-1') !!} value="-1">{!! res('cancel') !!}</option>    
                        <option {!! $sel('0') !!} value="0">{!! res('no_paid') !!}</option>   
                        <option {!! $sel('1') !!} value="1">{!! res('wait_for_approve') !!}</option>   
                        <option {!! $sel('2') !!} value="2">{!! res('paid') !!}</option>   
                    </select>
                </div>
            </div>
