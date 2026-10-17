{{-- _admin/_mall/form.php เดิม (page=admin_mall_detail) — id 0 = สร้างใหม่
     detail = HTML ของ CKEditor ที่ JS htmlEscape() ก่อนส่ง (เก็บแบบ escape แล้ว) → แสดงดิบใน textarea แบบเดิม --}}
@extends('legacy.layout')

@php
    // เดิม $id != "" กับ $id int: PHP 7.4 0 == "" → id 0 ไม่แสดงเลขรายการ
    $isNew = $id === 0;
@endphp

@section('content')
@if ($id === 0)

<div class="col-sm-7">
	<h4 class="page-title">{!! res('mall_new') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('mall_list') !!}
		</li>
		<li class="active">
			{!! res('mall_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('mall_edit') !!} <small>{{ $prefix }}{{ $data->id ?? '' }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('mall') !!}
		</li>
		<li class="active">
			{{ $prefix }}{{ $data->id ?? '' }}
		</li>
	</ol>
</div>
@endif

<form action="{{ legacy_href() }}_admin/_mall/save" method="post">

<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>

		    <a type="button" href="{{ legacy_href() }}admin/mall" class="pull-right btn btn-warning waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
			    {!! res('back_to_list') !!}
		    </a>
		</div>
	</div>


	<div class="col-sm-6">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">
				@if (! $isNew)
				<div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-7  form-control-static">
		                {{ $prefix }}{{ $data->id ?? '' }}
		            </div>
		        </div>
@endif
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('product_name') !!}</label>
		            <div class="col-md-7">
		                <input type="text" name="name" class="form-control validate[required]" value="{{ $data->name ?? '' }}" >
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('product_code') !!}</label>
		            <div class="col-md-7">
		                <input type="text" name="code" class="form-control validate[required]" value="{{ $data->code ?? '' }}" >
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('detail_desc') !!}</label>
		            <div class="col-md-7">
		                <textarea name="desc" row="6" class="form-control">{{ $data->desc ?? '' }}</textarea>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('mall_category') !!}</label>
		            <div class="col-md-5">
		                <select class="form-control" name="category_id">
@foreach ($categories as $row)
@if ($data !== null && \App\Domain\Shop\Php74::looseEq($row->id, $data->category_id))<option value="{{ $row->id }}" selected="selected">{{ $row->name }}</option>@else<option value="{{ $row->id }}">{{ $row->name }}</option>@endif
@endforeach

		                </select>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('main_image') !!}</label>
		            <div class="col-md-7">
{!! $upload !!}
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label">{!! res('weight') !!}</label>
		            <div class="col-md-5">
		            	<div class="input-group">
		            	<input type="text" name="weight" class="form-control" value="{{ $data->weight ?? '' }}" />
		            		<span class="input-group-addon">{!! res('kg') !!}</span>
		            	</div>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label">{!! res('width') !!}</label>
		            <div class="col-md-5">
		            	<div class="input-group">
		            	<input type="text" name="width" class="form-control" value="{{ $data->width ?? '' }}" />
		            		<span class="input-group-addon">{!! res('cm') !!}</span>
		            	</div>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label">{!! res('height') !!}</label>
		            <div class="col-md-5">
		            	<div class="input-group">
		            	<input type="text" name="height" class="form-control" value="{{ $data->height ?? '' }}" />
		            		<span class="input-group-addon">{!! res('cm') !!}</span>
		            	</div>
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label">{!! res('long') !!}</label>
		            <div class="col-md-5">
		            	<div class="input-group">
		            	<input type="text" name="long" class="form-control" value="{{ $data->long ?? '' }}" />
		            		<span class="input-group-addon">{!! res('cm') !!}</span>
		            	</div>
		            </div>
		        </div>


		        
		    </div>
		</div>
	</div>

	@if ($id !== 0)
	<div class="col-sm-6">
		<div class="card-box">
			<h4 class="m-t-0 header-title pull-left"><b>{!! res('sizecolor') !!}</b></h4>
			
			<div class="button-list pull-right">
			    <button type="button" onclick="on_addinv(0);" class="pull-right btn btn-info waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-plus"></i></span>
			    {!! res('add_new_list') !!}
				</button>
			</div>


			<div class="form-horizontal" role="form">
		        <div class="form-group" id="sizecolor">
		            

		        </div>
		    </div>
		</div>
	</div>
@endif


	<div class="col-sm-12">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">
		        <div class="form-group">
		            <div class="col-md-12">
						<textarea name="txtMessage" id="txtMessage" class="ckeditor " cols="45" rows="20">{!! $data->detail ?? '' !!}</textarea>
						<input type="hidden" name="editor" id="editor" />
					</div>
		        </div>
		    </div>
		</div>
	</div>
</form>
<div class="col-sm-12">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('adj_stock') !!}</b></h4>
			<div class="form-horizontal" role="form">
				<div class="form-group">
					<div class="col-xs-12" id="stock"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<form action="{{ legacy_href() }}_admin/_mall/save_sizecolor" method="post">
	<div id="panel-modal" class="sizecolor_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
	    <div class="modal-dialog modal-xs">
	        <div class="modal-content p-0 b-0">
	            <div class="panel panel-color panel-primary">
	                <div class="panel-heading"> 
	                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
	                    <h3 class="panel-title">{!! res('sizecolor') !!}</h3> 
	                </div> 
	                <div class="panel-body"> 

	                </div> 
	                <div class="panel-footer text-right">
	                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
	                    <button type="submit" class="btn btn-primary waves-effect waves-light" >{!! res('ok') !!}</button>
	                </div>
	                
	            </div>

	        </div><!-- /.modal-content -->
	    </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
</form>

<script>
	on_loadstock();
	on_loaddetail();
	CKEDITOR.replace('txtMessage');

    function htmlEscape(str) {
        return str
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/([\uE000-\uF8FF]|\uD83C[\uDF00-\uDFFF]|\uD83D[\uDC00-\uDDFF])/g, '')
        .replace(/🇨🇳/g, '')
        .replace(/♀/g, '')
        .replace(/♂/g, '')
        .replace(/🧚/g, '')
        .replace(/🧚/g, '')
        .replace(/🚛/g, '')
        .replace(/🌈/g, '')
        .replace(/🌠/g, '')
        .replace(/🍦/g, '')
        .replace(/🍨/g, '')
        .replace(/🍭/g, '')
        .replace(/💢/g, '')
        .replace(/🧝/g, '')
        .replace(/&zwj;/g, '');
    }

    function htmlUnescape(str){
      return str
        .replace(/&quot;/g, '"')
        .replace(/&#39;/g, "'")
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&amp;/g, '&');
    }
    
	function sumiting() {
		var editor = htmlEscape(CKEDITOR.instances['txtMessage'].getData());
		$("#editor").val(editor);
		if($("form").eq(0).validationEngine('validate') == false) 
		{ 
			CloseLoading(); 
		} else {
			ConfirmBox(function () {
				$("form").eq(0).submit();
			});
		}
	}


	$("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	    }
	});

	function on_loaddetail() {
		$.ajax({
	        type: 'POST',
	        async: true,
	        data : 'id={{ $id }}',
	        url: rooturl + '_admin/_mall/load_detail',
	        success: function(data) {
	            $("#sizecolor").html(data);
	        }
	    });
	}

	function on_loadstock() {
		$.ajax({
	        type: 'POST',
	        async: true,
	        data : 'id={{ $id }}',
	        url: rooturl + '_admin/_mall/load_stock',
	        success: function(data) {
	            $("#stock").html(data);
	        }
	    });
	}
	function on_addinv(id) {
		// if(id == 0) {
		// 	ErrorBox('{!! res('please_add_product_first') !!}');
		// } else {
			$.ajax({
		        type: 'POST',
		        async: true,
		        data : 'id=' + id + '&mall_id={{ $id }}',
		        url: rooturl + '_admin/_mall/load_sizecolor',
		        success: function(data) {
		            $(".sizecolor_form .panel-body").html(data);
		            $(".sizecolor_form").modal('show');
		        }
		    });
		//}
	}


	function on_delinv(id) {
		// if(id == 0) {
		// 	ErrorBox('{!! res('please_add_product_first') !!}');
		// } else {
			$.ajax({
		        type: 'POST',
		        async: true,
		        data : 'id=' + id + '&mall_id={{ $id }}',
		        url: rooturl + '_admin/_mall/save_delsizecolor',
		        success: function(data) {
		            eval(data);
		        }
		    });
		//}
	}

</script>
@endsection
