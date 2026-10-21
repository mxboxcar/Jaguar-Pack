{{-- _admin/_lot/form.php เดิม (สิทธิ์ตรวจที่ middleware; $lotView = มีสิทธิ์ admin_lot_view → ซ่อนช่องราคา)
     PACK: แม่แบบ jl + $allowEdit (เดิม checkUser()['id'] == 5 — ฟอร์มเพิ่ม/แก้ tracking, ช่องแก้หัวล็อต และปุ่มตั้งวันที่/พิมพ์ แสดงเฉพาะ user 5;
           คนอื่นเห็นหัวล็อตแบบอ่านอย่างเดียว), รูปพัสดุ (tracking_image), รายการลูกค้าค้างจ่าย (load_unpaid), modal แนบรูปด่วน
           (load_quickfile/save_quickfile), รูปเล็กข้างเลขพัสดุ (load_image) --}}
@extends('legacy.layout')

@php
    use App\Support\Legacy\Status;
    $no = $prefix.($d['id'] ?? '');
@endphp

@section('content')
<style> .content-page { margin-left: 0px; } </style>
<div class="col-sm-7">
	<h4 class="page-title">{!! res('lot') !!} <small>{{ $no }}</small></h4> 
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('lot') !!}
		</li>
		<li class="active">
			{{ $no }}
		</li>
	</ol>
</div>

@if ($allowEdit)
<form action="{{ legacy_href() }}_admin/_lot/save" method="post">

<input type="hidden" name="id" value="{{ $d['id'] ?? '' }}">
<input type="hidden" name="diff" value="">
<input type="hidden" name="method" value="">

<div class="col-sm-5 pull-right">
	<div class="button-list">
	    <a type="button" href="#" onclick="onhideshow();return false;" class="pull-right btn btn-default waves-effect waves-light">
		    <span class="btn-label"><i class="fa  fa-bars"></i></span>
		    {!! res('hide_and_search_lot') !!}
	    </a>
	</div>
</div>

<div class="col-sm-12" id="searchform" style="display:none" >
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('add_tracking_detail') !!}</b></h4>
		<p class="text-muted font-13 m-b-30">
        </p>
        <div id="user_rate"></div>
        <div class="form-horizontal" role="form">     
        	<div class="col-xs-12 col-md-6">
        		<div class="form-group">
		            <label class="col-xs-3 control-label"> {!! res('tracking') !!}</label>
		            <div class="col-xs-7">
		            	<input autocomplete='off'  type="text" name="tracking" id="find_track" class="form-control" value="" onblur="getTracking()" />
		            	<input type="hidden" name="tracking_id" />
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-3 control-label"> {!! res('order') !!}</label>
		            <div class="col-xs-7">
		            	<input autocomplete='off'  type="text" name="order"  class="form-control" value=""  />
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-3 control-label"> {!! res('no') !!}</label>
		            <div class="col-xs-3">
		            	<input autocomplete='off'  type="text" name="no"  class="form-control  validate[required]" value="{{ \App\Domain\Lot\Php74::str($nextNo) }}" />
		            </div>
		            <div class="col-xs-2">
		            	<button type="button" class="btn btn-default" onclick="LoadLastLot();">{!! res('load_lastest_lot') !!}</button>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-3 control-label"> {!! res('amount') !!}</label>
		            <div class="col-xs-3">
		            	<input autocomplete='off' onkeyup="ontempcal()"  type="text" name="amount"  class="form-control  validate[required]" value="1" />
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-3 control-label"> {!! res('product_type') !!}</label>
		            <div class="col-xs-7 product_type_html">
		            	
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-3 control-label"> {!! res('wood_protect') !!}</label>
		            <div class="col-xs-7">
		            	<select name="is_craft" class="form-control " onchange="trigger_crafting()">
		            		<option value="0">{!! res('disable') !!}</option> 
                        	<option value="1">{!! res('active') !!}</option> 
                        </select>
		            </div>
		        </div>

		        <div class="form-group"  id="is_craft_tracklist" style="display:none">
		        	<div class="row">
			        	<label class="col-xs-3 control-label"> {!! res('craft_tracklist') !!}</label>
			        	<div class="col-xs-7">
			        		<input autocomplete='off'  type="text" name="craft_tracklist[]"  class="form-control" />
			        	</div>
			        	<div class="col-xs-2" id="is_craft_tracklist">
			        		<button type="button" class="btn btn-default" onclick="addcraft('');">+</button>
			        	</div>
		        	</div>
		        </div>

		        <div class="form-group">
		            <label class="col-xs-3 control-label"> {!! res('qc') !!}</label>
		            <div class="col-xs-7">
		            	<select name="is_qc" class="form-control " >
		            		<option value="0">{!! res('disable') !!}</option> 
                        	<option value="1">{!! res('active') !!}</option> 
                        </select>
		            </div>
		        </div>
        	</div>        
        	<div class="col-xs-12 col-md-6">
        		<div class="form-group">
		            <label class="col-xs-3 control-label"> {!! res('customer_code') !!}</label>
		            <div class="col-xs-7">
		            	<input autocomplete='off'  type="text" name="code" onblur="onload_userrate()" class="form-control validate[required]" value="" />
		            </div>
		        </div>
		        <div class="form-group">
		             <label class="col-xs-3 control-label"> {!! res('remark') !!}</label>
		            <div class="col-xs-7 ">
		            	<textarea name="remark" class="form-control"></textarea>
		            </div>
		        </div>


		        {{-- เดิมแสดงรูปของ tracking_id = id ล็อต (ตัวแปร $data ของหน้าเป็นแถว lot) = รูปของพัสดุอื่น → เริ่มว่าง (ดู docs/modules/lot.md) --}}
		        <div class="form-group" id="attachment_tracking">
		            @include('lot.admin.attachment', ['list' => [], 'wrap' => true])
		        </div>
		        <div class="form-group" {!! $lotView ? "style='display:none;'" : '' !!}>
		        	<label class="col-xs-3 control-label"> {!! res('price_saved') !!}</label>
		        	<div class="col-xs-7 price_saved form-control-static">

		        	</div>
		        </div>
		        <div class="form-group" {!! $lotView ? "style='display:none;'" : '' !!}>
		        	<label class="col-xs-3 control-label"> {!! res('choose_price') !!}</label>
		            <div class="col-xs-7 ">
		            	<div class="radio radio-primary">
	                        <input type="radio" name="choose_price" id="choose_price_1" value="1">
	                        <label for="choose_price_1">
	                            {!! res('price_kg') !!} <span class="get_tracking_kg"></span>
	                        </label>
	                    </div>
	                    <div class="radio radio-primary">
	                        <input type="radio" name="choose_price" id="choose_price_2" value="2">
	                        <label for="choose_price_2">
	                            {!! res('price_cubic') !!} <span class="get_tracking_cubic"></span>
	                        </label>
	                    </div>
		            </div>
		        </div>
        	</div>                         
	        

	        <div class="col-xs-12" style="margin-top:10px;">
	        	<div class="col-md-4 col-lg-3 col-xs-6">
	        		<label class="col-md-4 control-label"> {!! res('weight') !!}</label>
		            <div class="col-md-8">
			            <div class="input-group">
			            	<input type="text" name="weight" class="addcomma form-control validate[required]" onkeyup="ontempcal()" value=""  />
			            	<span class="input-group-addon">{!! res('kg') !!}</span>
			            </div>
		            </div>
	        	</div>
	        	<div class="col-md-4 col-lg-3 col-xs-6">
	        		<label class="col-md-4 control-label"> {!! res('width') !!}</label>
		            <div class="col-md-8">
			            <div class="input-group">
			            	<input type="text" name="width" onkeyup="ontempcal()" class="addcomma form-control validate[required]" value=""  />
			            	<span class="input-group-addon">{!! res('cm') !!}</span>
			            </div>
		            </div>
	        	</div>

	        	<div class="col-md-4 col-lg-3 col-xs-6">
	        		<label class="col-md-4 control-label"> {!! res('long') !!}</label>
		            <div class="col-md-8">
			            <div class="input-group">
			            	<input type="text" name="long" onkeyup="ontempcal()" class="addcomma form-control validate[required]" value=""  />
			            	<span class="input-group-addon">{!! res('cm') !!}</span>
			            </div>
		            </div>
	        	</div>
	        	
	            <div class="col-md-4 col-lg-3 col-xs-6">
	        		<label class="col-md-4 control-label"> {!! res('height') !!}</label>
		            <div class="col-md-8">
			            <div class="input-group">
			            	<input type="text" name="height" onkeyup="ontempcal()" class="addcomma form-control validate[required]" value=""  />
			            	<span class="input-group-addon">{!! res('cm') !!}</span>
			            </div>
		            </div>
	        	</div>
	        	
	        </div>

	        <div class=" col-xs-12 dashed"></div>

	        <div class="col-xs-12">
	        	<div class="col-md-4 col-lg-3 col-xs-6">
	        		<label class="col-md-4 control-label"> {!! res('qc') !!}</label>
		            <div class="col-md-8">
			            <div class="input-group">
			            	<input type="text" name="qc" class="addcomma form-control validate[required]" value=""  />
			            	<span class="input-group-addon">{!! res('thai_currency') !!}</span>
			            </div>
		            </div>
	        	</div>
	        	<div class="col-md-4 col-lg-3 col-xs-6">
	        		<label class="col-md-4 control-label"> {!! res('repack') !!}</label>
		            <div class="col-md-8">
			            <div class="input-group">
			            	<input type="text" name="repack" class="addcomma form-control validate[required]" value=""  />
			            	<span class="input-group-addon">{!! res('thai_currency') !!}</span>
			            </div>
		            </div>
	        	</div>
	            <div class="col-md-4 col-lg-3 col-xs-6">
	        		<label class="col-md-4 control-label"> {!! res('express') !!}</label>
		            <div class="col-md-8">
			            <div class="input-group">
			            	<input type="text" name="express" class="addcomma form-control validate[required]" value=""  />
			            	<span class="input-group-addon">{!! res('thai_currency') !!}</span>
			            </div>
		            </div>
	        	</div>
	        	<div class="col-md-4 col-lg-3 col-xs-6">
	        		<label class="col-md-4 control-label"> {!! res('else') !!}</label>
		            <div class="col-md-8">
			            <div class="input-group">
			            	<input type="text" name="else" class="addcomma form-control validate[required]" value=""  />
			            	<span class="input-group-addon">{!! res('thai_currency') !!}</span>
			            </div>
		            </div>
	        	</div>
	        </div>
	        <div class="col-xs-12">
	        	<div class="col-md-4 col-lg-3 col-xs-6">
	        		<label class="col-md-4 control-label"> {!! res('price_craft') !!}</label>
		            <div class="col-md-8">
			            <div class="input-group">
			            	<input type="text" name="price_craft" class="addcomma form-control validate[required]" value=""  />
			            	<span class="input-group-addon">{!! res('thai_currency') !!}</span>
			            </div>
		            </div>
	        	</div>
	        </div>


	        <div class="form-group">
	        	<div class="col-md-6 col-xs-offset-6">
		        	<div class="button-list">
					    <button onclick="onclearform()" type="button" class="pull-right btn btn-warning waves-effect waves-light">
						    <span class="btn-label"><i class="fa fa-close"></i></span>
						    {!! res('clear') !!}
					    </button>

					    <button onclick="submitting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
						    <span class="btn-label"><i class="fa fa-check"></i></span>
						    {!! res('save_button') !!}
					    </button>
				    </div>
	        	</div>
	        </div>
	    </div>
	</div>
</div>
</form>
@endif

<div class="col-sm-12">
	<div class="card-box col-xs-12">
		<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b> </h4>
		<p class="text-muted font-13 m-b-30">
        </p>
        <div class="form-horizontal" role="form"> 
	        @if ($allowEdit)
	        <div class="col-xs-6">
	        	<div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-xs-8  form-control-static">
		            	{{ $no }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('name') !!}</label>
		            <div class="col-xs-6 ">
		            	<input type="text" class="form-control" onblur="onsavelotname()" value="{{ $d['name'] ?? '' }}" id="lot_name" />
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('remark') !!}</label>
		            <div class="col-xs-6 ">
		            	<textarea class="form-control" onblur="onsavelotnameadmin()" name="name_admin" rows="5" id="admin_name">{{ $d['name_admin'] ?? '' }}</textarea>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('ship_type') !!}</label>
		            <div class="col-xs-6  form-control-static">
		            	{!! Status::getShipType($d['type'] ?? null) !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('amount_shipping') !!}</label>
		            <div class="col-xs-4 ">
		            	<input type="text" class="form-control" onblur="onsavelotshipamount()" value="{{ $d['shipping_amount'] ?? '' }}" id="shipping_amount" />
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('attachment') !!}</label>
		            <div class="col-xs-6  form-control-static">
		            	@include('lot.admin.upload_box', ['rand' => 'lot_'.($d['id'] ?? ''), 'filename' => (string) ($d['attachment_name'] ?? '')])
		            </div>
		        </div>
        	</div> 
        	@else
        	<div class="col-xs-6">
	        	<div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-xs-8  form-control-static">
		            	{{ $no }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('name') !!}</label>
		            <div class="col-xs-6 form-control-static">
		            	{{ $d['name'] ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('remark') !!}</label>
		            <div class="col-xs-6 form-control-static">{{ $d['name_admin'] ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('ship_type') !!}</label>
		            <div class="col-xs-6  form-control-static">
		            	{!! Status::getShipType($d['type'] ?? null) !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('amount_shipping') !!}</label>
		            <div class="col-xs-4 form-control-static">
		            	{{ $d['shipping_amount'] ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('attachment') !!}</label>
		            <div class="col-xs-6  form-control-static">
		            	{{-- เดิมต่อ path/filename ดิบลง href (ไม่ urlencode) — ค่าถูก escape --}}
		            	<a style='float:left;' href='{{ legacy_href() }}download_file?path={{ $d['attachment'] ?? '' }}&filename={{ $d['attachment_name'] ?? '' }}'>{{ $d['attachment_name'] ?? '' }}</a>
		            </div>
		        </div>
	        </div>
	        @endif
	    </div>

        <div id="lot_detail" >
	    </div>

	    <div class="form-horizontal col-xs-12" role="form" style="margin-top:10px;z-index: 0">   
	    	@if ($allowEdit)
			<div class="form-group">
				<div class="col-xs-2 col-xs-offset-2">
					<select class="form-control " id="status">
						<option value="0">{!! res('date_warehouse') !!}</option>
						<option value="1">{!! res('date_outofchina') !!}</option>
						<option value="2">{!! res('date_thai') !!}</option>
					</select>
				</div>
		        <div class="col-xs-3">
		        	<input autocomplete='off'  type="text" id="status_date" class="form-control datepicker" value="" onblur="getTracking()" />
		        </div>
		        <div class="col-xs-4">
		        	<button type="button" class="btn btn-default" onclick="onstatussave()">{!! res('save') !!}</button>
		        	<button type="button" class="btn btn-danger" onclick="onstatusreset()">{!! res('reset') !!}</button>
		        	<a href="{{ legacy_href() }}_admin/_lot/print?lot={{ $id }}" class="btn btn-primary" >{!! res('print') !!}</a>
		        	<a href="{{ legacy_href() }}_admin/_lot/print_receive?id={{ $id }}" class="btn btn-primary" >{!! res('receive_page') !!}</a>
		        </div>
			</div>
			@endif
			<div class="form-group text-center col-xs-12">
				<span style="padding:2px 5px;background-color:#f9ffa5;">{!! res('qc') !!}&{!! res('wood_protect') !!}</span>
				<span style="padding:2px 5px;background-color:#a5ffc5;">{!! res('qc') !!}</span>
				<span style="padding:2px 5px;background-color:#ffdfa5;">{!! res('wood_protect') !!}</span>
				<span style="padding:2px 5px;background-color:#ff0c00;">{!! res('no_price') !!}</span>
				<span style="padding:2px 5px;background-color:#e789ff;">claim</span>
			</div>
		</div>

	</div>
</div>

<div id="lot_memo">
	    </div>

<div id="lot_unpayment">
	
</div>


<div id="panel-modal" class="upload_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading"> 
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
                    <h3 class="panel-title">{!! res('attachment') !!}</h3> 
                </div> 
                <div class="panel-body"> 
	                
                </div> 
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="quicksavefile();" type="button">{!! res('ok') !!}</button>
                </div>
                
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<script>
$(function() {
	LoadDetail();
	LoadMemo();
	LoadUnpaid();
	getTracking();
	initialDatepicker2();

	setTimeout(function() { $("#upload_upload_track").removeClass('validate[requiredattachment]'); }, 1000);

	$("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	        $('#searchform').css('display', 'none');
	        onclearform();
	    }
	});
});

function submitting() {
	if($("form").eq(0).validationEngine('validate') == false) 
	{ 
		CloseLoading(); 
	} else {
		//ConfirmBox(function () {
			$("form").eq(0).submit();
			is_edit = true;
		//});
	}
}

var is_edit = true;
function getTracking(is_editz) {
	if(is_edit) {

		if($("#find_track").val() != "") {
			OpenLoading();
		    $.ajax({
		        type: 'POST',
		        async: true,
		        data : 'tracking=' + $("#find_track").val() + '&lot_id={{ $id }}' ,
		        url: rooturl + '_admin/_lot/load_tracking',
		        success: function(data) {
		        	data = $.parseJSON(data);

		        	var is_alert = false;


		        	if(data[0] == null) {
		        		$("input[name='code']").val('');
			            $("input[name='order']").val('');
			            $("textarea[name='remark']").val('');
			            $("input[name='weight']").val(0.00);
			            $("input[name='width']").val(0.00);
			            $("input[name='height']").val(0.00);
			            $("input[name='long']").val(0.00);
			            $("input[name='qc']").val(0.00);
			            $("input[name='repack']").val(0.00);
			            $("input[name='express']").val(0.00);
			            $("input[name='else']").val(0.00);
			            $("input[name='price_craft']").val(0.00);
			            $("input[name='tracking_id']").val('');
			            $("input[name='amount']").val(1);
			            $("textarea[name='remark']").val('');

			            $(".product_type_html").html(data[1]);
			            $("select[name='is_craft']").val(0);
			            $("select[name='is_qc']").val(0);

			            
			            $(".get_tracking_kg").text('');
		            	$(".get_tracking_cubic").text('');
		            	$(".price_saved").text('-');

		            	var html = '';
						html += '<div class="row">';
						html += '<div class="col-xs-7 col-xs-offset-3">';
						html += '<input autocomplete="off"  type="text" name="craft_tracklist[]"  class="form-control" value="" />';
						html += '</div>';
						html += '<div class="col-xs-2" id="is_craft_tracklist">';
						html += '<button type="button" class="btn btn-primary" onclick="addcraft(\'\');">+</button>';
						html += '</div>';
						html += '</div>';

						$("#is_craft_tracklist").html(html);
			            LoadLastLot();
		        	} else {

		        		if(data[0].is_craft == "1") {
		        			ErrorBox("{!! res('wood_protect') !!}");
		        			is_alert = true;
		        			$("button[class='confirm']").click(function() {
			                    setTimeout(function() { $(window).scrollTop($("#searchform").offset().top - 60); }, 500);
			                });
		        		}
		        		if(data[0].is_qc == "1") {
		        			ErrorBox("{!! res('qc') !!}");
		        			is_alert = true;
		        			$("button[class='confirm']").click(function() {
			                    setTimeout(function() { $(window).scrollTop($("#searchform").offset().top - 60); }, 500);
			                });
		        		}
		        		//checking match lot
		        		if(data[0].ship != eval(data[6].type)) {
		        			if(data[0].ship == 0) {
		        				ErrorBox("{!! res('wrong_shipping_type_1') !!}");
		        			} else {
		        				ErrorBox("{!! res('wrong_shipping_type_2') !!}");
		        			}
		        			is_alert = true;
		        		} else {
		        			if(!is_alert) {
		        				CloseLoading();
		        			}
		        		}
						$("#attachment_tracking").html(data[7]);
		        		$("input[name='code']").val(data[0].code);
			            $("input[name='order']").val(data[0].order);
			            if(data[0].no != "0") {
			            	$("input[name='no']").val(data[0].no);
			            }
			            $("textarea[name='remark']").val(data[0].remark);
			            $("input[name='weight']").val(data[0].weight);
			            $("input[name='width']").val(data[0].width);
			            $("input[name='height']").val(data[0].height);
			            $("input[name='long']").val(data[0].long);
			            $("input[name='qc']").val(data[0].price_qc);
			            $("input[name='repack']").val(data[0].price_repack);
			            $("input[name='express']").val(data[0].price_express);
			            $("input[name='else']").val(data[0].price_else);
			            $("input[name='price_craft']").val(data[0].price_craft);
			            $("input[name='tracking_id']").val(data[0].id);
			            $("input[name='amount']").val(data[0].amount);
			            $("textarea[name='remark']").val(data[0].remark);

			            $(".product_type_html").html(data[1]);
			            $("select[name='is_craft']").val(data[0].is_craft);
			            $("select[name='is_qc']").val(data[0].is_qc);

			            if(data[2] != "") {
			            	var exp = data[2].split("|");
			            	if(data[4] == 0) {
			            		$(".get_tracking_kg").parent().parent().find('input').prop('checked', true);
			            	} else {
			            		$(".get_tracking_cubic").parent().parent().find('input').prop('checked', true);
			            	}
			            	$(".get_tracking_kg").text(" - " + currency_number(eval(exp[0]).toFixed(2)) + " {!! res('thai_currency') !!}");
			            	$(".get_tracking_cubic").text(" - " + currency_number(eval(exp[1]).toFixed(2)) + " {!! res('thai_currency') !!}")
			            }

			            if(data[3] != "") {
			            	var exp = data[3].split("|");
			            	if(data[4] == 0) {
			            		$(".price_saved").text("{!! res('price_kg') !!} - " + currency_number(eval(exp[1]).toFixed(2)));
			            		$("#choose_price_1").prop('checked', true);
			            		$("#choose_price_2").prop('checked', false);
			            	} else {
			            		$(".price_saved").text("{!! res('price_cubic') !!} - " + currency_number(eval(exp[0]).toFixed(2)));
			            		$("#choose_price_1").prop('checked', false);
			            		$("#choose_price_2").prop('checked', true);
			            	}
			            }

			            if(data[0].is_craft == 1) {
			            	$("#is_craft_tracklist").css("display", "block");

			            	if(data[5].length == 1) {
			            		var html = '';
								html += '<div class="row">';
								html += '<label class="col-xs-3 control-label"> {!! res('craft_tracklist') !!}</label>';
	        					html += '<div class="col-xs-7">';
								html += '<input autocomplete="off"  type="text" name="craft_tracklist[]"  class="form-control" value="" />';
								html += '</div>';
								html += '<div class="col-xs-2" id="is_craft_tracklist">';
								html += '<button type="button" class="btn btn-primary" onclick="addcraft(\'\');">+</button>';
								html += '</div>';
								html += '</div>';

								$("#is_craft_tracklist").html(html);
			            	} else {
			            		var html = '';
			            		for(var k = 0; k < data[5].length; k++) {
			            			if(data[5][k].length != 0) {
			            				if(k == 1) {
				            				html += '<div class="row">';
											html += '<label class="col-xs-3 control-label"> {!! res('craft_tracklist') !!}</label>';
				        					html += '<div class="col-xs-7">';
											html += '<input autocomplete="off"  type="text" name="craft_tracklist[]"  class="form-control" value="'+ data[5][k]['tracking'] +'" />';
											html += '</div>';
											html += '<div class="col-xs-2" id="is_craft_tracklist">';
											html += '<button type="button" class="btn btn-primary" onclick="addcraft(\'\');">+</button>';
											html += '</div>';
											html += '</div>';
				            			} else {
				            				html += '<div class="row">';
											html += '<div class="col-xs-7 col-xs-offset-3">';
											html += '<input autocomplete="off"  type="text" name="craft_tracklist[]"  class="form-control" value="'+ data[5][k]['tracking'] +'" />';
											html += '</div>';
											html += '<div class="col-xs-2" id="is_craft_tracklist">';
											html += '<button type="button" class="btn btn-danger" onclick="deletecraft(\'\');">-</button>';
											html += '</div>';
											html += '</div>';
				            			}
			            			}
			            		}
			            		$("#is_craft_tracklist").html(html);
			            	}
			            	
			            } else {
			            	$("#is_craft_tracklist").css("display", "none");
			            }
						setTimeout(function() { $("#upload_upload_track").removeClass('validate[requiredattachment]'); }, 1000);	
		        	}
		        	if(is_editz) {
		        		is_edit = false;
		        	}
		        	onload_userrate();
		        	if(!is_alert) {
		        		CloseLoading();
		            }
		    	}
			})
		}
		
	}
}


function LoadMemo() {
    OpenLoading();
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id={{ $id }}',
        url: rooturl + '_admin/_lot/load_lot_memo',
        success: function(data) {
            $("#lot_memo").html(data);
			CloseLoading();
        }
    });
}

{{-- เดิม LoadUnpaid() ใส่ผลลงกล่อง #lot_memo (ชนกับ LoadMemo — กล่องไหนตอบทีหลังทับอีกกล่อง) ทั้งที่มี #lot_unpayment ว่างไว้
     → ใส่ลง #lot_unpayment ให้เห็นทั้งสองกล่อง (เหมือน SR port — ดู docs/modules/lot.md) --}}
function LoadUnpaid() {
    OpenLoading();
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id={{ $id }}',
        url: rooturl + '_admin/_lot/load_unpaid',
        success: function(data) {
            $("#lot_unpayment").html(data);
			CloseLoading();
        }
    });
}

function on_addmemo() {
	OpenLoading();
	$.ajax({
		type: 'POST',
		async: true,
		data : 'no=' + $("#no").val() + '&no_max=' + $("#no_max").val()
				+ '&memo_detail=' + $("#memo_detail").val()
				+ '&memo_code=' + $("#memo_code").val()
				+ '&memo_weight=' + $("#memo_weight").val()
				+ '&memo_cubic=' + $("#memo_cubic").val()
				+ '&lot_id={{ $id }}' ,
	  	url: rooturl + '_admin/_lot/save_lot_memo',
		success: function(data) {
			SuccessBox();
			LoadMemo();
			CloseLoading();
		}
	});
}

function on_deletememo(id) {
	ConfirmBox(function() {
		OpenLoading();
		$.ajax({
			type: 'POST',
			async: true,
			data : 'id=' + id,
			url: rooturl + '_admin/_lot/save_delete_lot_memo',
			success: function(data) {
				SuccessBox();
				LoadMemo();
				CloseLoading();
			}
		});
	});
}

function LoadDetail() {
    OpenLoading();
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id={{ $id }}&search=' + $("#searching_lot_detail").val(),
        url: rooturl + '_admin/_lot/load_lot_detail',
        success: function(data) {
            $("#lot_detail").html(data);

            $('.showdetailbox').on('touchstart mouseover', function (e) {
			    $(this).css({'color':  '#ff0000'});
				$(this).find('.lot_table_in').css('display', 'block');
			});
			$('.showdetailbox').on('touchend mouseout', function (e) {
			    $(this).css({'color': '#797979'});
				$(this).find('.lot_table_in').css('display', 'none');
			});

            makeCurrency();
            CloseLoading();
            makeTopBottom();
			loadImage();
        }
    });
}


function load_quickfile(id) {
	$.ajax({
		type: 'POST',
		async: true,
		data : 'id=' + id,
		url: rooturl + '_admin/_lot/load_quickfile',
		success: function(data) {
			$(".upload_form .panel-body").html(data);
			$(".upload_form").modal('show');
		}
	});
}

function quicksavefile() {
	var str = '';
	$("input[name='upload_upload_trackquick_mul[]']").each(function() {
		var t = $(this);
		str += ',' + t.val();
	}); 

	$.ajax({
		type: 'POST',
		async: true,
		data : 'id=' + $("#quicksave_tracking_id").val() + '&image=' + str,
		url: rooturl + '_admin/_lot/save_quickfile',
		success: function(data) {
			data = $.parseJSON(data);
			eval(data[0]);
			$(".upload_form").modal('hide');
			setTimeout(function () { $("#" + $("#quicksave_tracking_id").val()).html(data[1]); }, 1000);
		}
	});
}

{{-- เดิมยิง load_image ทีละแถว — load_lot_detail ใส่รูปมาพร้อมแถวแล้ว (div.img_track ไม่ว่าง → JS นี้ข้าม; ดู docs/modules/lot.md) --}}
function loadImage() {
	$(".img_track").each(function() {
		var th = $(this);
		var id = $(this).attr('id');
		if(th.html() == '') {
			$.ajax({
				type: 'POST',
				async: true,
				data : 'id=' + id,
				url: rooturl + '_admin/_lot/load_image',
				success: function(data) {
					th.html(data);
				}
			});
		}
	});
}


function makeTopBottom() {
	$(".sum_amount").text($(".hid_sum_amount").val());
	$(".sum_weight").text($(".hid_sum_weight").val());
	$(".sum_cubic").text($(".hid_sum_cubic").val());
	$(".sum_width").text($(".hid_sum_width").val());
	$(".sum_height").text($(".hid_sum_height").val());
	$(".sum_long").text($(".hid_sum_long").val());
	$(".sum_kg").text($(".hid_sum_kg").val());
	$(".sum_pcubic").text($(".hid_sum_pcubic").val());

	$(".sum_qc").text($(".hid_sum_qc").val());
	$(".sum_repack").text($(".hid_sum_repack").val());
	$(".sum_express").text($(".hid_sum_express").val());
	$(".sum_else").text($(".hid_sum_else").val());
	$(".sum_craft").text($(".hid_sum_craft").val());
}

function LoadLastLot() {
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id={{ $id }}',
        url: rooturl + '_admin/_lot/load_lastest_lot',
        success: function(data) {
            $("input[name='no']").val(data);
        }
    });
}
function onhideshow() {
	if($("#searchform").css("display") == "none") {
		$("#searchform").slideDown('fast');
		
	} else {
		$("#searchform").slideUp('fast');
		is_edit = true;
		onclearform();
	}
	onload_userrate();
}
function onclearform() {
	$("input[name='method']").val('');
	$("input[name='tracking']").val('');
	$("input[name='order']").val('');
	$("input[name='code']").val('');
	$("input[name='remark']").val('');

	$("input[name='weight']").val('0.0');
	$("input[name='width']").val('0.0');
	$("input[name='height']").val('0.0');
	$("input[name='long']").val('0.0');
	$("input[name='qc']").val('0.00');
	$("input[name='repack']").val('0.00');
	$("input[name='express']").val('0.00');
	$("input[name='price_craft']").val('0.00');
	$("input[name='tracking_id']").val('');
	$("textarea[name='remark']").val('');
	$("input[name='diff']").val('');
	LoadLastLot();
}

$("input[name='code']").typeahead({
  source: function (query, process) {
    $.ajax({
      url: rooturl + '_admin/_lot/load_autocomplete_user',
      type: 'POST',
      dataType: 'JSON',
      data: 'query=' + query,
      success: function(data) {
        process(data.code);
      }
    })
  }
});
// $("input[name='tracking']").typeahead({
//   source: function (query, process) {
//     $.ajax({
//       url: rooturl + '_admin/_lot/load_autocomplete_tracking',
//       type: 'POST',
//       dataType: 'JSON',
//       data: 'query=' + query,
//       success: function(data) {
//         process(data.code);
//       }
//     })
//   }
// });

function CheckAll() {
	if($(".cbxitem_head").prop('checked')) {
		$(".cbxitem").each(function() {
			$(this).prop('checked', true);
		});
	} else {
		$(".cbxitem").each(function() {
			$(this).attr('checked', false);
		});
	}
}
function onedit(tracking) {
	is_edit = true;
	$("#searchform").slideDown('fast');
	$("input[name='tracking']").val(tracking);
	getTracking(true);

	setTimeout(function() { $(window).scrollTop($("#searchform").offset().top - 60); }, 500);
	
}

function ondelete(id) {
	ConfirmBox(function() {
		$.ajax({
	        type: 'POST',
	        async: true,
	        data : 'id=' + id ,
	        url: rooturl + '_admin/_lot/save_remove_tracking',
	        success: function(data) {
	            LoadDetail();
	        }
	    });
	});
}

function trigger_crafting() {
	var val = $("select[name='is_craft']").val();

	if(val == "1") {
		$("#is_craft_tracklist").css("display", "block");
	} else {
		$("#is_craft_tracklist").css("display", "none");
	}
}

function addcraft(track) {
	var html = '';
	html += '<div class="row">';
	html += '<div class="col-xs-7 col-xs-offset-3">';
	html += '<input autocomplete="off"  type="text" name="craft_tracklist[]"  class="form-control" value="'+ track +'" />';
	html += '</div>';
	html += '<div class="col-xs-2" id="is_craft_tracklist">';
	html += '<button type="button" class="btn btn-danger" onclick="deletecraft($(this));">-</button>';
	html += '</div>';
	html += '</div>';

	$("#is_craft_tracklist").append(html);
}

function deletecraft(ele) {
	ele.parents('.row').remove();
}

function onstatussave() {
	var allVals = [];
     $('.cbxitem:checked').each(function() {
       allVals.push($(this).val());
     });

    $.ajax({
        type: 'POST',
        async: true,
        data : 'id=' + allVals.toString() + '&status=' + $("#status").val() + '&date=' + $("#status_date").val(),
        url: rooturl + '_admin/_lot/save_status_tracking',
        success: function(data) {
            LoadDetail();
        }
    });
}
function onstatusreset() {
	var allVals = [];
     $('.cbxitem:checked').each(function() {
       allVals.push($(this).val());
     });

    $.ajax({
        type: 'POST',
        async: true,
        data : 'id=' + allVals.toString() + '&status=' + $("#status").val() + '&date=' + $("#status_date").val(),
        url: rooturl + '_admin/_lot/save_status_reset_tracking',
        success: function(data) {
            LoadDetail();
        }
    });
}

function onsavelotstatus_1() {
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id={{ $id }}&method=1&date=' + $("#date_in").val() ,
        url: rooturl + '_admin/_lot/save_status_lot_date',
        success: function(data) {
            
        }
    });
}

function onsavelotstatus_2() {
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id={{ $id }}&method=2&date=' + $("#date_out").val() ,
        url: rooturl + '_admin/_lot/save_status_lot_date',
        success: function(data) {
            
        }
    });
}

function onsavelotstatus_3() {
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id={{ $id }}&method=3&date=' + $("#date_arrive").val() ,
        url: rooturl + '_admin/_lot/save_status_lot_date',
        success: function(data) {
            
        }
    });
}
function onsavelotstatus() {
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id={{ $id }}&status=' + $("#lot_status").val() ,
        url: rooturl + '_admin/_lot/save_status_lot',
        success: function(data) {
            
        }
    });
}
function onsavelotshipamount() {
	$.ajax({
        type: 'POST',
        async: true,
        data : 'id={{ $id }}&amount=' + $("#shipping_amount").val() ,
        url: rooturl + '_admin/_lot/save_lot_ship_amount',
        success: function(data) {
            
        }
    });
}
function onsavelotname() {
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id={{ $id }}&name=' + $("#lot_name").val() ,
        url: rooturl + '_admin/_lot/save_lot_name',
        success: function(data) {
            
        }
    });
}
function onsavelotnameadmin() {
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id={{ $id }}&name_admin=' + $("#admin_name").val() ,
        url: rooturl + '_admin/_lot/save_lot_name_admin',
        success: function(data) {
            
        }
    });
}
function on_uploadcomplate(lot, value)
{
	var x = value.split("[NIDA]");
	$.ajax({
        type: 'POST',
        async: true,
        data : 'id=' + lot + '&value=' + x[0] + '&filename=' + x[1],
        url: rooturl + '_admin/_lot/save_file_lot',
        success: function(data) {
            eval(data);
        }
    });
}

function ontempcal() {
	var weight = parseFloat($("input[name='weight']").val());
	var width = parseFloat($("input[name='width']").val());
	var long = parseFloat($("input[name='long']").val());
	var height = parseFloat($("input[name='height']").val());
	var type = parseFloat($("select[name='product_type_id']").val());
	var amount = parseFloat($("input[name='amount']").val());

	var kg = parseFloat($(".product_type_rate_" + type).attr('kg'));
	var cubic = parseFloat($(".product_type_rate_" + type).attr('cubic'));

	var price_kg = (weight * kg) * amount;
	var price_cubic = (makecubicformat((width * height * long) / 1000000) * cubic) * amount;
	var diff = parseFloat($("input[name='diff']").val());

	if(price_kg > price_cubic) {
		$(".get_tracking_kg").parent().parent().find('input').prop('checked', true);
		$(".get_tracking_cubic").parent().parent().find('input').prop('checked', false);
	} else {
		//if((price_cubic - price_kg) > diff) {
			$(".get_tracking_kg").parent().parent().find('input').prop('checked', false);
			$(".get_tracking_cubic").parent().parent().find('input').prop('checked', true);
		// } else {
		// 	$(".get_tracking_kg").parent().parent().find('input').prop('checked', true);
		// 	$(".get_tracking_cubic").parent().parent().find('input').prop('checked', false);
		// }
		
	}
	$(".get_tracking_kg").text(currency_number(parseFloat(price_kg.toFixed(2)))  + ' ฿');
	$(".get_tracking_cubic").text(currency_number(parseFloat(price_cubic.toFixed(2))) + ' ฿');
}

function onload_userrate() {
	var code = $("input[name='code']").val();

	$.ajax({
        type: 'POST',
        async: true,
        data : 'code=' + code,
        url: rooturl + '_admin/_lot/load_user_rate',
        success: function(data) {
        	data = $.parseJSON(data);
            $("#user_rate").html(data[0]);
            $("input[name='diff']").val(data[1]);

			ontempcal();
        }
    });

    
}
</script>
@endsection
