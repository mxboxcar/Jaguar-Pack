{{-- _admin/_goodsconfirm/form.php เดิม (สิทธิ์ admin_goodsconfirm ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')
@php use App\Domain\Warehouse\Php74; @endphp

@section('content')
@if($id === 0)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('goodsconfirm') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('goodsconfirm') !!}
		</li>
		<li class="active">
			{!! res('goodsconfirm_new') !!}
		</li>
	</ol>
</div>
@else
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
@endif

@if($id === 0)
<form action="{{ legacy_href() }}_admin/_goodsconfirm/save" method="post">
@endif

<input type="hidden" name="id" value="{{ $d['id'] ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			@if($id === 0)
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>
		    @endif

		    @if(Php74::eq($d['status'] ?? null, 0) && $id !== 0)
			<button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('cancel_this') !!}
		    </button>
		    @endif

		    @if(Php74::eq($d['status'] ?? null, 1))
			<button onclick="unapproved()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('unapproved') !!}
		    </button>
		    <button onclick="approved()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('approved') !!}
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
		        @if($id !== 0)
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{{ $prefix }}{{ $d['id'] ?? '' }}
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('tracking') !!}</label>
		            <div class="col-md-8  form-control-static">
		            	{{ $d['tracking'] ?? '' }}
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('status') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{!! \App\Support\Legacy\Status::getstatusgoods($d['status'] ?? null) !!}@if(Php74::eq($d['status'] ?? null, -1))<div style='margin-top: 10px;' class='text-danger'>* {{ explode(',ยกเลิกโดย', (string) ($d['cancel_remark'] ?? ''))[0] }}</div>@endif
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('detail') !!}</label>
		            <div class="col-md-8 form-control-static">
		            	{!! nl2br(e($d['submit_remark'] ?? '')) !!}
		            </div>
		        </div>
		       

		        @else
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('tracking') !!}</label>
		            <div class="col-md-8">
		            	<input onblur="on_load_tracking_info($(this).val())" type="text" class="form-control autocomplete" name="tracking" />
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('detail') !!}</label>
		            <div class="col-md-8">
		            	<textarea class="form-control" rows="5" name="submit_remark"></textarea>
		            </div>
		        </div>
		        
		        @endif

			    <div id="detail_area">

			        <div class="form-group">
			            <label class="col-md-3 control-label"> {!! res('lot_list') !!}/{!! res('no') !!}</label>
			            <div class="col-md-8 form-control-static">-</div>
			        </div>

			        <div class="form-group">
			            <label class="col-xs-3 control-label"> {!! res('date_in') !!}</label>
			            <div class="col-xs-6 form-control-static">
			            	-
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-xs-3 control-label"> {!! res('date_outofchina') !!}</label>
			            <div class="col-xs-6 form-control-static">
			            	-
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-xs-3 control-label"> {!! res('date_arrive') !!}</label>
			            <div class="col-xs-6 form-control-static">
			            -
			            </div>
			        </div>

			    </div>
			    @if(($d['id'] ?? '') !== '')
			    <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('attachment_goods') !!}</label>
		            <div class="col-md-7 form-control-static">
		            	{!! $approvedUpload !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('customer_code_goodsconfirm') !!}</label>
		            <div class="col-md-7 form-control-static">
		            	<a href="{{ legacy_href() }}admin/user/{{ $d['user'] ?? '' }}">{{ $d['code'] ?? '' }}</a>
		            </div>
		        </div>
		        @endif
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
				    <div class="col-xs-8">
				    {!! $uploadMultiple !!}

				    </div>
		        </div>
		        <div class="form-group">
		            <div class="col-xs-9 col-xs-offset-3" id="image_list">
				        @foreach($images as $img)<div class="img_list_detail" style="float:left;margin-left: 10px;width:85px"><a style="float:left" class="fancybox" href="{{ legacy_blob((string) $img) }}">
				                <img src="{{ legacy_blob((string) $img) }}" style="max-width: 50px;max-height: 50px;">
				                <input type="hidden" name="img[]" value="{{ $img }}" />
				                </a>
				                
				                </div>@endforeach
				    </div>
		        </div>
	        @else
	        	<div class="form-group">
		            <div class="col-xs-3 control-label text-right"><strong>{!! res('attachment') !!} : </strong></div>
				    <div class="col-xs-3">
		        </div>
		        <div class="form-group">
		            <div class="col-xs-9 col-xs-offset-3" id="image_list">
				        @foreach($images as $img)<div class="img_list_detail" style="float:left;margin-left: 10px;width:85px"><a style="float:left" class="fancybox" href="{{ legacy_blob((string) $img) }}">
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
@if($id === 0)
</form>
@endif



<div id="panel-modal" class="image_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading"> 
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
                    <h3 class="panel-title">{!! res('image_list') !!}</h3> 
                </div> 
                <div class="panel-body"> 

                </div> 
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="addimage();" type="button">{!! res('ok') !!}</button>
                </div>
                
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
	$(function() {
		$("#upload_file").removeClass("validate[requiredattachment]");
		@if($id !== 0)
			on_load_tracking_info('{!! \App\Support\Legacy\Alerts::js($d['tracking'] ?? '') !!}');
		@endif
	});

	setInterval(function() {
		var d = new Date();
		$(".img_list_detail img").each(function() {
			var x = $(this);
			$(this).attr('src', x.attr('src') + "?" + d.getTime());
		});
	}, 3000);

	function sumiting() {
		if($("form").validationEngine('validate') == false) 
		{ 
			CloseLoading(); 
		} else {
			ConfirmBox(function () {
				$("form").eq(0).submit();
			});
		}
	}

	function approved() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $id }}' ,
	            url: rooturl + '_admin/_goodsconfirm/save_approved',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}

	function unapproved() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $id }}' ,
	            url: rooturl + '_admin/_goodsconfirm/save_unapproved',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}

	function canceling() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $id }}' ,
	            url: rooturl + '_admin/_goodsconfirm/cancel',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}


	$("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	    }
	});


	$('.autocomplete').typeahead({
		  source: function (query, process) {
		    $.ajax({
		      url: rooturl + '_admin/_goodsconfirm/load_tracking',
		      type: 'POST',
		      dataType: 'JSON',
		      data: 'query=' + query,
		      success: function(data) {
		        process(data.code);
		        cache_search = data.user;
		      }
		    })
		  } ,
	      	updater: function(item) {
		       	on_load_tracking_info(item);
		        return item;
		    }
		});


		function on_load_tracking_info(item) {
			$.ajax({
	          url: rooturl + '_admin/_goodsconfirm/load_tracking_by_id',
	          type: 'POST',
		      async: true,
	          data: 'data=' + item,
	          success: function(data) {
	          	//data = $.parseJSON(data);
	          	$("#detail_area").html(data);
	          }
	        });
		}
		function LoadUpload() {
		    $.ajax({
		        type: 'POST',
		        async: true,
		        url: rooturl + '_admin/_goodsconfirm/load_upload',
		        success: function(data) {
		            $(".image_form .panel-body").html(data);
		        }
		    });
		}

		function addimage() {
		    $.ajax({
		        type: 'POST',
		        async: true,
		        data: 'image=' + $("#upload_uploadfile").val() + '&id={{ $id }}',
		        url: rooturl + '_admin/_goodsconfirm/save_image',
		        success: function(data) {
		        	setTimeout(function() {
		        		$("#image_list").append(data);
		            	$(".modal").modal('hide');
		        	}, 2000);
		            
		        }
		    });
		}
		function on_uploadcomplate() {

		}
</script>
@endsection
