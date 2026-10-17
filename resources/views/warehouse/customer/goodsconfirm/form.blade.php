{{-- _goodsconfirm/form.php เดิม (สิทธิ์ goodsconfirm ตรวจที่ middleware legacy.perm, เจ้าของตรวจใน controller) --}}
@extends('legacy.layout')
@php use App\Domain\Warehouse\Php74; @endphp

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('goodsconfirm_edit') !!} <small>{{ $prefix }}{{ $d['id'] ?? '' }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('goodsconfirm') !!}
		</li>
		<li class="active">
			{{ $prefix }}{{ $d['id'] ?? '' }}
		</li>
	</ol>
</div>


<input type="hidden" name="id" value="{{ $d['id'] ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			@if($id === 0)
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>
		    @endif

		    @if(Php74::eq($d['status'] ?? null, 0))
			<button onclick="approving()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('owner_this_item') !!}
		    </button>
		    @endif



		    <a type="button" href="{{ legacy_href() }}admin/goodsconfirm" class="pull-right btn btn-warning waves-effect waves-light">
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

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{{ $prefix }}{{ $d['id'] ?? '' }}
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('tracking') !!}</label>
		            <div class="col-md-8  form-control-static">
		            	{{ $d['tracking'] ?? '' }}
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('status') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{!! \App\Support\Legacy\Status::getstatusgoods($d['status'] ?? null) !!}@if(Php74::eq($d['status'] ?? null, -1))<div style='margin-top: 10px;' class='text-danger'>* {{ explode(',ยกเลิกโดย', (string) ($d['cancel_remark'] ?? ''))[0] }}</div>@endif
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('detail') !!}</label>
		            <div class="col-md-8 form-control-static">
		            	{!! nl2br(e($d['submit_remark'] ?? '')) !!}
		            </div>
		        </div>
		       


			    <div id="detail_area">

			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('lot_list') !!}/{!! res('no') !!}</label>
			            <div class="col-md-8 form-control-static">-</div>
			        </div>

			        <div class="form-group">
			            <label class="col-xs-4 control-label"> {!! res('date_in') !!}</label>
			            <div class="col-xs-6 form-control-static">
			            	-
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-xs-4 control-label"> {!! res('date_outofchina') !!}</label>
			            <div class="col-xs-6 form-control-static">
			            	-
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-xs-4 control-label"> {!! res('date_arrive') !!}</label>
			            <div class="col-xs-6 form-control-static">
			            -
			            </div>
			        </div>

			        

			    </div>

			    <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('attachment_goods') !!}</label>
		            <div class="col-md-7">
		            	{!! $approvedUpload !!}
		            </div>
		        </div>
		    </div>
		</div>
	</div>

	<div class="col-sm-6">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('image_list') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">
			@if($id === 0)
		        <div class="form-group">
		            <div class="col-xs-3 control-label text-right"><strong>{!! res('attachment') !!} : </strong></div>
				    @if($id === 0)
				        <button class="btn btn-primary" type="button" onclick="$('.image_form').modal('show');LoadUpload()">เพิ่มรายการรูป</button>
				    @else{!! \App\Domain\Warehouse\UploadBox::single('file', $d['slip'] ?? '') !!}@endif

		        </div>
		        <div class="form-group">
		            <div class="col-xs-9 col-xs-offset-3" id="image_list">
				        @foreach($images as $img)<div class="img_list_detail" style="float:left;margin-left: 10px;width:85px"><a style="float:left" class="fancybox" target="_blank" href="{{ legacy_blob((string) $img) }}">
				                <img src="{{ legacy_blob((string) $img) }}" style="max-width: 50px;max-height: 50px;">
				                <input type="hidden" name="img[]" value="{{ $img }}" />
				                </a>
				                
				                </div>@endforeach
				    </div>
		        </div>
	        @else
	        	<div class="form-group">
		            <div class="col-xs-3 control-label text-right"><strong>{!! res('attachment') !!} : </strong></div>
				   
		        <div class="form-group">
		            <div class="col-xs-9 col-xs-offset-3" id="image_list">
				        @foreach($images as $img)<div class="img_list_detail" style="float:left;margin-left: 10px;width:85px"><a style="float:left" class="fancybox" target="_blank" href="{{ legacy_blob((string) $img) }}">
				                <img src="{{ legacy_blob((string) $img) }}" style="max-width: 50px;max-height: 50px;">
				                <input type="hidden" name="img[]" value="{{ $img }}" />
				                </a>
				                
				                </div>@endforeach
				    </div>
		        </div>
	        @endif
		    </div>
		</div>
	</div>
</div>


<script>
	$(function() {
		@if($id !== 0)
			on_load_tracking_info('{!! \App\Support\Legacy\Alerts::js($d['tracking'] ?? '') !!}');
		@endif
	});

	function approving() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $id }}&attachment=' + $("#upload_file").val(),
	            url: rooturl + '_goodsconfirm/save_approved',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}
	function on_load_tracking_info(item) {
		$.ajax({
          url: rooturl + '_goodsconfirm/load_tracking_by_id',
          type: 'POST',
	      async: true,
          data: 'data=' + item,
          success: function(data) {
          	//data = $.parseJSON(data);
          	$("#detail_area").html(data);
          }
        });
	}
</script>
@endsection
