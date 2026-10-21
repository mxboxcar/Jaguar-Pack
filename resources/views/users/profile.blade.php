{{-- _profile/index.php เดิม --}}
@extends('legacy.layout')

@section('content')

<div class="col-sm-7">
	<h4 class="page-title">{!! res('edit_profile') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li class="active">
			{{ $prefix }}{{ $data->id ?? '' }}
		</li>
	</ol>
</div>
<form action="{{ legacy_href() }}_profile/save" method="post" >

<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>
		</div>
	</div>




<div class="row">
	<div class="col-xs-6">
		<div class="col-lg-12">
			<div class="card-box">
				<h4 class="m-t-0 header-title"><b>{!! res('profile_info') !!}</b></h4>
				<p class="text-muted font-13 m-b-30">
		        </p>
				<div class="form-horizontal" role="form">                                    
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('name_lastname') !!}</label>
			            <div class="col-md-8">
			                <input type="text" name="name" class="form-control validate[required]" value="{{ $data->name ?? '' }}" >
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('customer_code') !!}</label>
			            <div class="col-md-8 form-control-static">
			                {{ $data->code ?? '' }}
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('email') !!}</label>
			            <div class="col-md-8">
			                <input type="email" name="email" class="form-control validate[required, custom[email]]" value="{{ $data->email ?? '' }}" >
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('phone') !!}</label>
			            <div class="col-md-8">
			                <input type="text" name="phone" class="form-control  validate[required]" value="{{ $data->phone ?? '' }}" >
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('phone') !!}</label>
			            <div class="col-md-8">
			                <input type="text" name="phone2" class="form-control  validate[required]" value="{{ $data->phone2 ?? '' }}" >
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> LINE ID</label>
			            <div class="col-md-8">
			                <input type="text" name="lineid" class="form-control" value="{{ $data->lineid ?? '' }}" >
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> WeChat ID</label>
			            <div class="col-md-8">
			                <input type="text" name="wechatid" class="form-control" value="{{ $data->wechatid ?? '' }}" >
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('address') !!}</label>
			            <div class="col-md-8">

			            	<textarea rows="5" name="address" class="form-control validate[required]">{{ $data->address ?? '' }}</textarea>
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('identity') !!}</label>
			            <div class="col-md-8">
			                {{-- PACK: สำเนาบัตรประชาชนแสดงด้วย getupload() เดิม (ไฟล์บน Azure — href_blob) --}}
			                @php($idf = (string) ($data->identity ?? ''))
			                @if ($idf === '')<div class="col-md-7  form-control-static">-</div>@else{!! \App\Domain\Settings\UploadBox::getupload('file', $idf) !!}@endif

			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('status') !!}</label>
			            <div class="col-md-8 form-control-static">
							{!! \App\Support\Legacy\Status::getstatususer($data->is_approved ?? null) !!}			                
			            </div>
			        </div>
			    </div>
			</div>
		</div>
	</div>

	<div class="col-xs-6">
		<div class="col-lg-12">
			<div class="card-box">
				<h4 class="m-t-0 header-title"><b>{!! res('login_info') !!}</b></h4>
				<p class="text-muted font-13 m-b-30">
		        </p>
		        
				<div class="form-horizontal" role="form">                                    
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('username') !!}</label>
			            <div class="col-md-8 form-control-static">
			            	{{ $data->username ?? '' }}
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('password') !!}</label>
			            <div class="col-md-8">
			                <input type="text" value="" name="password" class="form-control " >
			            </div>
			        </div>
			        <div class="form-group">
			        	 <div class="col-md-8 col-md-offset-4 text-danger"><small>{!! res('password_remark') !!}</small></div>
			        </div>
			    </div>
			</div>
		</div>
	</div>
</div>
<div class="row">

	<div class="col-lg-12">
		<div class="col-lg-12">
			<div class="card-box">
			        <h4 class="m-t-0 header-title"><b>{!! res('pricing') !!}</b> <small>({{ $userTypeName }})</small></h4>
					<p class="text-muted font-13 m-b-30">
			        </p>
			        <div class="form-group" id="user_type_detail">

			        </div>
			    </div>
			</div>
		</div>
</div>
</form>

<div id="panel-modal" class="cancel_remark_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading"> 
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
                    <h3 class="panel-title">{!! res('cancel_this') !!}</h3> 
                </div> 
                <div class="panel-body"> 
	                <textarea class="form-control" id="cancel_remark"></textarea>
                </div> 
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="canceling2();" type="button">{!! res('ok') !!}</button>
                </div>
                
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
	$(function() {
		LoadUserType({{ $data->type ?? '' }});
	})

	function sumiting() {
		if($("form").eq(0).validationEngine('validate') == false) 
		{ 
			CloseLoading(); 
		} else {
			ConfirmBox(function () {
				$("form").eq(0).submit();
			});
		}
	}

	function canceling() {
		$(".cancel_remark_form").modal('show');
	}

	function canceling2() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id=&cancel_remark=' + $("#cancel_remark").val(),
	            url: rooturl + '_admin/_user/cancel',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}

	function approved() {
	    ConfirmBox(function() {
	        $.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $data->id ?? '' }}',
	            url: rooturl + '_admin/_user/save_approved',
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

	$("select[name='user_type']").chnage(function() {
		LoadUserType(e.val);
	});
	

	function LoadUserType(e) {
		OpenLoading();
		$.ajax({
            type: 'POST',
            async: true,
            data : 'id=' + e,
            url: rooturl + '_profile/load_user_type_detail',
            success: function(data) {
            	$("#user_type_detail").html(data);
            	CloseLoading();
            }
        });
	}

</script>
@endsection
