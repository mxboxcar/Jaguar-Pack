{{-- _admin/_user/form.php เดิม — admin_user = แก้ไขได้, admin_user_view = ดูอย่างเดียว (ตรวจที่ middleware + controller) --}}
@php($eq = [\App\Domain\Users\Php74::class, 'eq'])
@extends('legacy.layout')

@section('content')
@if ($id === 0)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('employee_new') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('employee') !!}
		</li>
		<li class="active">
			{!! res('employee_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('employee_edit') !!} <small>{{ $prefix }}{{ $data->id ?? '' }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('employee') !!}
		</li>
		<li class="active">
			{{ $prefix }}{{ $data->id ?? '' }}
		</li>
	</ol>
</div>
@endif

@if (! $permission)
	<div class="col-sm-5 pull-right">
		<div class="button-list">
		    <a type="button" href="{{ legacy_href() }}admin/user" class="pull-right btn btn-warning waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
			    {!! res('back_to_list') !!}
		    </a>
		</div>
	</div>



<div class="row">
	<div class="col-lg-6">
		<div class="col-lg-12">
			<div class="card-box">
				<h4 class="m-t-0 header-title"><b>{!! res('profile_info') !!}</b></h4>
				<p class="text-muted font-13 m-b-30">
		        </p>
				<div class="form-horizontal" role="form">
					<div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('customer_code') !!}</label>
			            <div class="col-md-8 form-control-static">
			                {{ $data->code ?? '' }}
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('name_lastname') !!}</label>
			            <div class="col-md-8 form-control-static">
			               {{ $data->name ?? '' }}
			            </div>
			        </div>

			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('email') !!}</label>
			            <div class="col-md-8 form-control-static">
			                {{ $data->email ?? '' }}
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('phone') !!}</label>
			            <div class="col-md-8 form-control-static">
			                {{ $data->phone ?? '' }}
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('phone') !!}</label>
			            <div class="col-md-8 form-control-static">
			                {{ $data->phone2 ?? '' }}
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('address') !!}</label>
			            <div class="col-md-8 form-control-static">
							{{ $data->address ?? '' }}
			            </div>
			        </div>

			        <div class="form-group">
			            <label class="col-md-4 control-label"> รหัสผู้แนะนำ</label>
			            <div class="col-md-8">
			                <input type="text" name="commission" class="form-control  " value="{{ $memberCode }}" >
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

	<div class="col-lg-6">
		<div class="col-lg-12">
			<div class="card-box">
			        <h4 class="m-t-0 header-title"><b>{!! res('user_type') !!}</b></h4>
					<p class="text-muted font-13 m-b-30">
			        </p>
			        <div class="form-horizontal" role="form">
				        <div class="form-group">
				            <label class="col-md-4 control-label"> {!! res('user_type') !!}</label>
				            <div class="col-md-8 form-control-static">
@foreach ($userTypes as $row)@if ($eq($data->type ?? null, $row->id) || $eq($parentTypeId, $row->id)){{ $row->name }}@endif @endforeach
				            </div>
				        </div>
				        <div class="form-group">
				            <label class="col-md-4 control-label"> {!! res('type') !!}</label>
				            <div class="col-md-8 form-control-static">
				                <div class="radio radio-primary" style="padding:0px">
								@if ($eq($data->option_price ?? null, 1)){!! res('use_price_2') !!}@else{!! res('use_price_1') !!}@endif
				                </div>
				            </div>
				        </div>
			        </div>
			        <div class="form-group" id="user_type_detail" @if ($eq($data->option_price ?? null, 0))style="display:block"@else style="display:none"@endif>

			        </div>
			        <div class="form-group" id="user_type_detail_own" @if ($eq($data->option_price ?? null, 1))style="display:block"@else style="display:none"@endif>
@include('users.admin.partials.pricing', ['m' => $m, 'amount' => false])
			        </div>
			    </div>
			</div>
		</div>
</div>


@else
<form action="{{ legacy_href() }}_admin/_user/save" method="post" >
<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
		@if ($eq($data->is_approved ?? null, 0) && $id !== 0)
			<button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('cancel_this') !!}
		    </button>
			<button onclick="approved()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-check"></i></span>
			    {!! res('approved') !!}
		    </button>
		@endif

			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>
		    <a type="button" href="{{ legacy_href() }}admin/user" class="pull-right btn btn-warning waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
			    {!! res('back_to_list') !!}
		    </a>
		</div>
	</div>


<div class="row">
	<div class="col-lg-6">
		<div class="col-lg-12">
			<div class="card-box">
				<h4 class="m-t-0 header-title"><b>{!! res('profile_info') !!}</b></h4>
				<p class="text-muted font-13 m-b-30">
		        </p>
				<div class="form-horizontal" role="form">
					<div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('customer_code') !!}</label>
			            <div class="col-md-8">
			                <input type="text" name="code" class="form-control validate[required]" value="{{ $data->code ?? '' }}" >
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('name_lastname') !!}</label>
			            <div class="col-md-8">
			                <input type="text" name="name" class="form-control validate[required]" value="{{ $data->name ?? '' }}" >
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
			            <label class="col-md-4 control-label"> {!! res('address') !!}</label>
			            <div class="col-md-8">

			            	<textarea rows="5" name="address" class="form-control validate[required]">{{ $data->address ?? '' }}</textarea>
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('identity2') !!}</label>
			            <div class="col-md-8">
			                @if ((string) ($data->identity ?? '') === '')<div class="col-md-7  form-control-static">-</div>@else{!! \App\Domain\Users\UploadBox::single('file', $data->identity) !!}@endif

			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> รหัสผู้แนะนำ</label>
			            <div class="col-md-8">
			                <input type="text" name="commission" class="form-control  " value="{{ $memberCode }}" >
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

	<div class="col-lg-6">
		<div class="col-lg-12">
			<div class="card-box">
				<h4 class="m-t-0 header-title"><b>{!! res('login_info') !!}</b></h4>
				<p class="text-muted font-13 m-b-30">
		        </p>

				<div class="form-horizontal" role="form">
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('username') !!}</label>
			            <div class="col-md-8">
			            	@if ($eq($id, ''))
			                	<input type="text" value="{{ $data->username ?? '' }}" name="username" class="form-control  validate[required, ajax[checkusername]]" >
			            	@else
			            		<input type="text" value="{{ $data->username ?? '' }}" name="username" class="form-control  validate[required]" >
		            		@endif
			            </div>
			        </div>
			        <div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('password') !!}</label>
			            <div class="col-md-8">
			            	@if ($allowpass)
			            	<button onclick="viewpassword()" type="button" class="btn-default ">
				    			view pass</button>

				            <button onclick="resetpass()" type="button" class=" btn-default ">
				    			reset pass</button>

				            <button onclick="pinreset()" type="button" class=" btn-default ">
				    			pin reset</button>

				    		<button onclick="unbanned()" type="button" class=" btn-default ">
				    			unbanned</button>

				            <p id="viewpassword" style="margin-top:20px"></p>
				            </div>
				        	@endif
			        </div>

			    </div>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-lg-6">
		<div class="col-lg-12">
			<div class="card-box">
			        <h4 class="m-t-0 header-title"><b>{!! res('module_detail') !!}</b></h4>
					<p class="text-muted font-13 m-b-30">
			        </p>
			        <div class="form-horizontal" role="form">
				        <div class="form-group">
				            <label class="col-md-4 control-label"> {!! res('role') !!}</label>
				            <div class="col-md-4">
				                <select name="role" class="form-control ">
<option selected="selected" value="0">{!! res('please_select') !!}</option>@foreach ($roles as $row)@if ($eq($data->role ?? null, $row->id))<option selected="selected" value="{{ $row->id }}">{{ $row->role_name }}</option>@else<option value="{{ $row->id }}">{{ $row->role_name }}</option>@endif @endforeach
			                    </select>
				            </div>
				        </div>
			        </div>
			        <div class="form-group" id="role_detail">

			        </div>
			    </div>
			</div>
		</div>

		<div class="col-lg-6">
		<div class="col-lg-12">
			<div class="card-box">
			        <h4 class="m-t-0 header-title"><b>{!! res('user_type') !!}</b></h4>
					<p class="text-muted font-13 m-b-30">
			        </p>
			        <div class="form-horizontal" role="form">
				        <div class="form-group">
				            <label class="col-md-4 control-label"> {!! res('user_type') !!}</label>
				            <div class="col-md-4">
				                <select name="user_type" class="form-control ">
@foreach ($userTypes as $row)@if ($eq($data->type ?? null, $row->id) || $eq($parentTypeId, $row->id))<option selected="selected" value="{{ $row->id }}">{{ $row->name }}</option>@else<option value="{{ $row->id }}">{{ $row->name }}</option>@endif @endforeach
			                    </select>
				            </div>
				        </div>
				        <div class="form-group">
				            <label class="col-md-4 control-label"> {!! res('type') !!}</label>
				            <div class="col-md-8">
				                <div class="radio radio-primary" style="padding:0px">
				                	<div class="radio" onclick="onchangetype(0)"><input type="radio" id="radio_1" checked="checked" name="option_price" value="0">
                                    <label for="radio_1">
                                        {!! res('use_price_1') !!}
                                    </label></div>
                                    <div class="radio" onclick="onchangetype(1)"><input type="radio" id="radio_2" @if ($eq($data->option_price ?? null, 1))checked="checked"@endif name="option_price" value="1">
                                    <label for="radio_2">
                                        {!! res('use_price_2') !!}
                                    </label></div>
				                </div>
				            </div>
				        </div>
			        </div>
			        <div class="form-group" id="user_type_detail" @if ($eq($data->option_price ?? null, 0))style="display:block"@else style="display:none"@endif>

			        </div>
			        <div class="form-group" id="user_type_detail_own" @if ($eq($data->option_price ?? null, 1))style="display:block"@else style="display:none"@endif>
@include('users.admin.partials.pricing', ['m' => $m, 'amount' => false])
			        </div>
			    </div>
			</div>
		</div>

	</div>
</form>
@endif

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
		LoadRoleDetail($("select[name='role']").val());
		@if ($eq($data->option_price ?? null, 0))
		LoadUserType($("select[name='user_type']").val());
		@endif

		@if ($permissionView)
		LoadUserType('{{ $data->type ?? '' }}');
		@endif
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
	            data : 'id={{ $id }}&cancel_remark=' + $("#cancel_remark").val(),
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

	$("select[name='role']").change(function() {
		LoadRoleDetail($(this).val());
	});
	$("select[name='user_type']").change(function() {
		LoadUserType($(this).val());
	});


	function LoadUserType(e) {
		OpenLoading();
		$.ajax({
            type: 'POST',
            async: true,
            data : 'id=' + e,
            url: rooturl + '_admin/_user/load_user_type_detail',
            success: function(data) {
            	$("#user_type_detail").html(data);
            	CloseLoading();
            }
        });
	}

	function LoadRoleDetail(e) {
		OpenLoading();
		$.ajax({
            type: 'POST',
            async: true,
            data : 'id=' + e,
            url: rooturl + '_admin/_user/load_role_detail',
            success: function(data) {
            	$("#role_detail").html(data);
            	CloseLoading();
            }
        });
	}

	function onchangetype(type) {
		if(type == 0) {
			LoadUserType($("select[name='user_type']").val());
			$("#user_type_detail").css('display', 'block');
			$("#user_type_detail_own").css('display', 'none');
		}

		if(type == 1) {
			$("#user_type_detail").css('display', 'none');
			$("#user_type_detail_own").css('display', 'block');
		}
	}

	@if ($allowpass)
		function viewpassword() {
			OpenLoading();
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $data->id ?? '' }}' + '&paymentpin=' + $("input[name='paymentpin']").val()  ,
	            url: rooturl + '_admin/_user/btn_viewpassword',
	            success: function(data) {
					if(data.indexOf('pass=') != -1){
					    $("#viewpassword").html(data);
					} else {
						eval(data);
					}

	            	$(".modal-backdrop").remove();
	            	CloseLoading();
	            	$("body").removeClass('modal-open');
	            }
	        });
		}

		function resetpass() {
			OpenLoading();
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $data->id ?? '' }}' + '&paymentpin=' + $("input[name='paymentpin']").val()  ,
	            url: rooturl + '_admin/_user/btn_resetpassword',
	            success: function(data) {
					eval(data);
	            }
	        });
		}

		function pinreset() {
			OpenLoading();
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $data->id ?? '' }}'  + '&paymentpin=' + $("input[name='paymentpin']").val()  ,
	            url: rooturl + '_admin/_user/btn_pinreset',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		}

		function unbanned() {
			OpenLoading();
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $data->id ?? '' }}' + '&paymentpin=' + $("input[name='paymentpin']").val()  ,
	            url: rooturl + '_admin/_user/btn_unbanned',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		}
	@endif
</script>

@if ((string) ($data->id ?? '') !== '')
<script>
	/* ประวัติการแก้ไขราคา (17 ต.ค. 2026) */
	$(function() {
		var box = '<div class="form-group"><label class="col-md-4 control-label">ประวัติราคา</label><div class="col-md-8"><button type="button" class="btn btn-default" id="btn_rate_history">ดูประวัติการแก้ไขราคา</button><div id="rate_history_box" style="margin-top:10px"></div></div></div>';
		$("input[name='option_price']").closest('.form-group').after(box);
		$("#btn_rate_history").on('click', function() {
			$("#rate_history_box").html('กำลังโหลด...');
			$.ajax({
				type: 'POST',
				data: 'id={{ (int) $data->id }}',
				url: rooturl + '_admin/_user/rate_history',
				success: function(h) { $("#rate_history_box").html(h); },
				error: function() { $("#rate_history_box").html('<div class="alert alert-danger">โหลดประวัติไม่สำเร็จ</div>'); }
			});
		});
	});
</script>
@endif
@endsection
