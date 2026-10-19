{{-- เนื้อหาฟอร์มแจ้งไม่ได้รับสินค้า (ลูกค้า/แอดมินต่างกันตาม $admin) — $id = int|null แบบ $_GET['id'] เดิม --}}
@extends('legacy.layout')

@php
    use App\Domain\Tracking\Php74;
    use App\Domain\Tracking\UploadBox;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $g = fn (string $k) => $d[$k] ?? null;
    $base = $admin ? 'admin/' : '';
    $isZero = Php74::eq($id, 0);     // เดิม $id == 0  (null == 0 เป็นจริงด้วย)
    $isEmpty = Php74::eq($id, '');   // เดิม $id == "" (0 == "" เป็นจริงใน 7.4)
    $editable = Php74::neq($g('status'), 2) && Php74::neq($g('status'), -1);
    $jsId = $id === null ? '' : (string) $id;
@endphp

@section('content')
@if (Php74::eq($id, '0'))
<div class="col-sm-7">
	<h4 class="page-title">{!! res('lost_package') !!}</h4>
	<ol class="breadcrumb">
		<li>
@if ($admin)
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
@else
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
@endif
		</li>
		<li>
			{!! res('lost_package') !!}
		</li>
		<li class="active">
			{!! res('lost_package_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('lost_package_edit') !!} <small>{{ $prefix.$g('id') }}</small></h4>
	<ol class="breadcrumb">
		<li>
			{{-- แอดมินเดิมก็ลิงก์ไป dashboard ของลูกค้าในหัวเรื่องแบบแก้ไข (คัดลอกมาไม่ครบ) --}}
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('lost_package') !!}
		</li>
		<li class="active">
			{{ $prefix.$g('id') }}
		</li>
	</ol>
</div>
@endif

@if ($isZero)
<form action="{{ legacy_href() }}{{ $admin ? '_admin/_lost_package/save' : '_lost_package/save' }}" method="post">
@endif

<input type="hidden" name="id" value="{{ $g('id') }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			@if ($isZero)
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>
		    @endif

		    @if (Php74::eq($g('status'), 1))
@if ($admin)
			
		    <button onclick="approving()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('approved') !!}
		    </button>

		    <button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-close"></i></span>
			    {!! res('cancel_this') !!}
		    </button>
@else
			<button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('cancel_this') !!}
		    </button>
@endif
		    @endif

@if ($admin)

		    @if (Php74::eq($g('status'), '-1') || Php74::eq($g('status'), '2'))
		    <button onclick="rollback()" type="button" class="pull-right btn btn-info waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-rotate-left"></i></span>
			    RollBack
		    </button>
		    @endif
@endif

		    <a type="button" href="{{ legacy_href() }}{{ $base }}lost_package" class="pull-right btn btn-warning waves-effect waves-light">
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
		        @if (! $isEmpty)
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{{ $prefix.$g('id') }}
		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-4 control-label">{!! $admin ? '' : ' ' !!}{!! res('status') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	{!! Status::getstatus($g('status')) !!}@if (Php74::eq($g('status'), -1))<div style='margin-top: 10px;' class='text-danger'>* {{ $admin ? $g('cancel_remark') : explode(',ยกเลิกโดย', (string) $g('cancel_remark'))[0] }}</div>@endif

		            </div>
		        </div>
@if ($admin)

		        <div class="form-group">
		            <label class="col-md-4 control-label">{!! res('customer_code') !!}</label>
		            <div class="col-md-6 form-control-static">
		            	<a href="{{ legacy_href() }}admin/user/{{ $g('user_id') }}">{{ $g('code') }}</a>
		            </div>
		        </div>
@endif

		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('order') !!}</label>
		            <div class="col-md-8  form-control-static">
		            	<a href="{{ legacy_href() }}{{ $base }}order/{{ $g('order_id') }}" >
		            	{{ $orderPrefix.$g('order_id') }}
		            	</a>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('detail') !!}</label>
		            <div class="col-md-8 form-control-static">
		            	{!! nl2br(e($g('submit_remark'))) !!}
		            </div>
		        </div>
		        <div class="form-group ">
		            <label class="col-md-4 control-label"> {!! res('cashback') !!}</label>
@if ($admin && $editable)
		            <div class="col-md-4">
		            	<div class="input-group">
		            		<span class="input-group-addon">
				        		{!! res('cn_currency') !!}
				        	</span>
				        	<input type="text" value="{{ $g('cashback') }}" class="form-control" name="cashback" />
		            	</div>
		            </div>
@else
		            <div class="col-md-4 form-control-static">
		           		{!! Format::numberformat($g('cashback'), true, true, true) !!}
		            </div>
@endif
		        </div>

		        <div class="form-group ">
		            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
@if ($admin && $editable)
		            <div class="col-md-8">
		            	<textarea class="form-control" rows="5" name="approved_remark"></textarea>
		            </div>
@else
		            <div class="col-md-8 form-control-static">
		           		{!! nl2br(e($g('approved_remark'))) !!}
		            </div>
@endif
		        </div>

		        @else
		        <div class="form-group">
		            <label class="col-md-{{ $admin ? 4 : 3 }} control-label"> {!! res('order') !!}</label>
		            <div class="col-md-8">
		            	<input type="text" class="form-control autocomplete" name="order" />
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('detail') !!}</label>
		            <div class="col-md-8">
		            	<textarea class="form-control" rows="5" name="submit_remark"></textarea>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('cashback') !!}</label>
		            <div class="col-md-4">
		            	<div class="input-group">
		            		<span class="input-group-addon">
				        		{!! res('cn_currency') !!}
				        	</span>
				        	<input type="text" class="form-control" name="cashback" />
		            	</div>
		            </div>
		        </div>
@if ($admin)
		        <div class="form-group">
		            <label class="col-md-3 control-label"> {!! res('remark') !!}</label>
		            <div class="col-md-8">
		            	<textarea class="form-control" rows="5" name="approved_remark"></textarea>
		            </div>
		        </div>
@endif
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
			@if ($isEmpty)
		        <div class="form-group">
		            <div class="col-xs-3 control-label text-right"><strong>{!! res('attachment') !!} : </strong></div>
@if ($admin)
				    <div class="col-xs-3">
				    @if ($isZero)
				        <button class="btn btn-primary" type="button" onclick="$('.image_form').modal('show');LoadUpload()">เพิ่มรายการรูป</button>
				    @else
{!! UploadBox::single('file', $g('slip')) !!}
				    @endif

				    </div>
@else
				    <div class="col-xs-9">
				    		(ให้ลูกค้าเเนบรูปภาพรายละเอียดสินค้าที่ได้รับ <br/>และภาพถุงที่ใส่สินค้ามา สามารถเเนบได้หลายรูป)
				    	</div>
				    <div class="col-xs-offset-3 col-xs-5">

				    {!! UploadBox::multiple('file', $g('slip'), true) !!}

				    </div>
@endif
		        </div>
		        <div class="form-group">
		            <div class="col-xs-9 col-xs-offset-3" id="image_list">
				        @include('tracking.lost.images')
				    </div>
		        </div>
	        @else
	        	<div class="form-group">
		            <div class="col-xs-3 control-label text-right"><strong>{!! res('attachment') !!} : </strong></div>
				    <div class="col-xs-3">
		        </div>
		        <div class="form-group">
		            <div class="col-xs-9 col-xs-offset-3" id="image_list">
				        @include('tracking.lost.images')
				    </div>
		        </div>
	        @endif
		    </div>
		</div>
	</div>
</div>
@if ($isZero)
</form>
@else
<div class="col-xs-12 col-md-12">
        <div class="card-box">
            <h4 class="m-t-0 header-title"><b>{!! res('ref_balance_list') !!}</b></h4>
            <p class="text-muted font-13 m-b-30">
            </p>
            <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 190px;">{!! res('working_date') !!}</th>
                            <th class="text-center">{!! res('detail') !!}</th>
                            <th class="text-center" style="width: 140px;">{!! res('amount') !!}</th>
                        </tr>
                    </thead>
                    <tbody>@forelse ($balances as $item)@php $exp = explode(' ', (string) $item->create_date); @endphp<tr><td>{!! Format::getthaidate($exp[0]) !!}<br />{{ $exp[1] ?? '' }}</td><td class="text-left"><a href="{{ legacy_href() }}{{ $item->link }}{{ $item->prefix_ref_id }}">{{ $item->detail }}</a></td><td class="text-right">@if ((float) $item->amount >= 0)<span class="text-success">+{!! Format::numberformat($item->amount) !!}</span>@else<span class="text-danger">{!! Format::numberformat($item->amount) !!}</span>@endif</td>@empty<tr><td colspan="3" class="text-center">{!! res('no_data') !!}</td></tr>@endforelse

                </tbody>
            </table>
        </div>
    </div>
@endif


@if ($admin)
<div id="panel-modal" class="cancel_remark_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
@else
<div id="panel-modal" class="image_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
@endif
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading"> 
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
                    <h3 class="panel-title">{!! res($admin ? 'cancel_this' : 'image_list') !!}</h3> 
                </div> 
                <div class="panel-body"> 
@if ($admin)
	                <textarea class="form-control" id="cancel_remark"></textarea>
@endif
                </div> 
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="{{ $admin ? 'canceling2();' : 'addimage();' }}" type="button">{!! res('ok') !!}</button>
                </div>
                
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
	$(function() {
		$("#upload_file").removeClass("validate[requiredattachment]");
	});

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

	function canceling() {
@if ($admin)
		$(".cancel_remark_form").modal('show');
	}

	function canceling2() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $jsId }}&cancel_remark=' + $("#cancel_remark").val(),
	            url: rooturl + '_admin/_lost_package/cancel',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}
	function approving() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $jsId }}&cashback=' + $("input[name='cashback']").val()
	            + '&approved_remark=' + $("textarea[name='approved_remark']").val() ,
	            url: rooturl + '_admin/_lost_package/save_approved',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}


	function rollback() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $jsId }}',
	            url: rooturl + '_admin/_lost_package/rollback',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}
@else
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id={{ $jsId }}' ,
	            url: rooturl + '_lost_package/cancel',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}
@endif


	$("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	    }
	});

	$(function() {
		$('#my_multi_select3').multiSelect({
	        selectableHeader: "<input type='text' class='form-control search-input' autocomplete='off' placeholder='{!! res('search') !!}'>",
	        selectionHeader: "<input type='text' class='form-control search-input' autocomplete='off' placeholder='{!! res('search') !!}'>",
	        afterInit: function (ms) {
	            var that = this,
	                $selectableSearch = that.$selectableUl.prev(),
	                $selectionSearch = that.$selectionUl.prev(),
	                selectableSearchString = '#' + that.$container.attr('id') + ' .ms-elem-selectable:not(.ms-selected)',
	                selectionSearchString = '#' + that.$container.attr('id') + ' .ms-elem-selection.ms-selected';

	            that.qs1 = $selectableSearch.quicksearch(selectableSearchString)
	                .on('keydown', function (e) {
	                    if (e.which === 40) {
	                        that.$selectableUl.focus();
	                        return false;
	                    }
	                });

	            that.qs2 = $selectionSearch.quicksearch(selectionSearchString)
	                .on('keydown', function (e) {
	                    if (e.which == 40) {
	                        that.$selectionUl.focus();
	                        return false;
	                    }
	                });
	        },
	        afterSelect: function () {
	            this.qs1.cache();
	            this.qs2.cache();
	        },
	        afterDeselect: function () {
	            this.qs1.cache();
	            this.qs2.cache();
	        }
	    });
	});

	$('.autocomplete').typeahead({
		  source: function (query, process) {
		    $.ajax({
		      url: rooturl + '_lost_package/load_order_number',
		      type: 'POST',
		      dataType: 'JSON',
		      data: 'query=' + query,
		      success: function(data) {
		        process(data.code);
		        cache_search = data.user;
		      }
		    })
		  }
		});
		function LoadUpload() {
		    $.ajax({
		        type: 'POST',
		        async: true,
		        url: rooturl + '_lost_package/load_upload',
		        success: function(data) {
		            $(".image_form .panel-body").html(data);
		        }
		    });
		}

		function addimage() {
		    $.ajax({
		        type: 'POST',
		        async: true,
		        data: 'image=' + $("#upload_uploadfile").val() + '&id={{ $jsId }}',
		        url: rooturl + '_lost_package/save_image',
		        success: function(data) {
		            $("#image_list").append(data);
		            $(".modal").modal('hide');
		        }
		    });
		}
		function on_uploadcomplate() {

		}
</script>
@endsection
