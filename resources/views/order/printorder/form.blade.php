{{-- _admin/_printorder/form.php (SR) — สิทธิ์ admin_order ตามไฟล์เดิม; ไม่พบใบ = ค่าว่างแบบเดิม --}}
@extends('legacy.layout')
@php
    use App\Domain\Order\Html;
    use App\Domain\Order\Php74;
    use App\Domain\Order\PrintOrder;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $run = Html::h($data['run_id'] ?? '');
@endphp

@section('content')

<div class="col-sm-7">
	<h4 class="page-title">ใบรายการสั่งซื้อ <small>{!! $run !!}</small></h4>
	<ol class="breadcrumb">
		<li>
            <a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
        </li>
		<li>
			<a href="{{ legacy_href() }}admin/printorder">{!! res('printorder_list') !!}</a>
		</li>
		<li class="active">
			{!! $run !!}
		</li>
	</ol>
</div>

<div class="col-sm-5 pull-right">
		<div class="button-list">

            @if (Php74::eq($data['is_print'] ?? null, 0))
            <button onclick="goprint(1)" type="button" class="pull-right btn btn-success waves-effect waves-light">
                <span class="btn-label"><i class="fa fa-save"></i></span>
                บันทึกพิมพ์แล้ว
            </button>
            @endif


            @if (Php74::eq($data['is_print'] ?? null, 1))
            <button onclick="goprint(0)" type="button" class="pull-right btn btn-danger waves-effect waves-light">
                <span class="btn-label"><i class="fa fa-save"></i></span>
                บันทึกยังไม่ได้พิมพ์
            </button>
            @endif

            <a class="pull-right btn btn-inverse waves-effect waves-light" 
                href="{{ legacy_href() }}_admin/_printorder/print/?id={{ $id }}">
                <span class="btn-label"><i class="fa fa-print"></i></span>
                {!! res('print') !!}
            </a>

            <a type="button" href="{{ legacy_href() }}admin/printorder" class="pull-right btn btn-warning waves-effect waves-light">
                <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
                {!! res('back_to_list') !!}
            </a>


		</div>
	</div>

<div class="col-xs-12 col-md-6">
	<div class="card-box">
	<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
	<p class="text-muted font-13 m-b-30">
    </p>
    <div class="form-horizontal" role="form">

        <div class="form-group">
            <label class="col-md-4 control-label"> เลขที่ใบรายการสั่งซื้อ</label>
            <div class="col-md-7 form-control-static">
                {!! $run !!}
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('status') !!}พิมพ์</label>
            <div class="col-md-7 form-control-static">
                {!! PrintOrder::getstatusprint($data['is_print'] ?? null) !!}
            </div>
        </div>

    	<div class="form-group">
            <label class="col-md-4 control-label"> Reference</label>
            <div class="col-md-7 form-control-static">
                    {{ $prefix }}{{ $data['id'] ?? '' }}
            </div>
        </div>   
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('customer_code') !!}</label>
            <div class="col-md-7 form-control-static">
                <a href="{{ legacy_href() }}admin/user/{{ $data['user'] ?? '' }}">{!! Html::h($data['code'] ?? '') !!}</a>
            </div>
        </div> 
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('shipping_type') !!}</label>
            <div class="col-md-7 form-control-static">
                {!! Status::getShipType($data['ship'] ?? null) !!}
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('status') !!}</label>
            <div class="col-md-7 form-control-static">
                {!! Status::getstatusorder($data['status'] ?? null) !!}@if (Php74::eq($data['status'] ?? null, -1))<div style='margin-top: 10px;' class='text-danger'>* {!! Html::h($data['cancel_remark'] ?? '') !!}</div>@endif
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('this_order_rate') !!}</label>
            <div class="col-md-4 form-control-static">
                    {!! res('cn_currency') !!}1 <i class='fa fa-arrows-h'></i> {!! Format::numberformat($data['exchange_rate'] ?? null) !!}
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('remark_for_this_order') !!}</label>
            <div class="col-md-7 form-control-static">
                {!! Html::h($data['quote'] ?? '') !!}
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('admin_remark') !!}</label>
            <div class="col-md-7 form-control-static">
                {!! Html::h($data['admin_quote'] ?? '') !!}
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('edit_last') !!}</label>
            <div class="col-md-7 form-control-static">
                    <a href="{{ legacy_href() }}admin/user/{{ $editLast->id ?? '' }}">{!! Html::h($editLast->code ?? '') !!}</a>
            </div>
        </div>
    </div>
    </div>
</div>




<div class=" col-xs-12">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('order_detail') !!}</b></h4>
		<p class="text-muted font-13 m-b-30">
	    </p>
	    
	    <div id="order_detail">

	    </div>
		

		    
	</div>
</div>


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

<div id="panel-modal" class="cashback_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading">
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 class="panel-title">{!! res('cashback') !!}</h3>
                </div>
                <input type="hidden" class="form-control" name="cashback_id" />
                <input type="hidden" class="form-control" name="cashback_rate" />
                <input type="hidden" class="form-control" name="cashback_price" />
                <input type="hidden" class="form-control" name="cashback_item" />
                <div class="panel-body">
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label class="col-md-4 control-label"> {!! res('amount_before') !!}</label>
                            <div class="col-md-4 form-control-static amount_before">

                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
                            <div class="col-md-4">
                                <textarea rows="4" class="form-control" name="cashback_remark"></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-4 control-label"> {!! res('not_receive_goods') !!}</label>
                            <div class="col-md-4">
                                <input type="text" onblur="on_recal_cashback()" class="form-control" name="cashback_amount" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-4 control-label"> {!! res('cashback_number') !!}</label>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="cashback_number" />
                                    <span class="input-group-addon">{!! res('thai_currency') !!}</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-4 control-label"> {!! res('color') !!}</label>
                            <div class="col-md-7 form-control-static">
                                <div class="radio radio-primary">
                                    <input checked="checked" type="radio" name="color_tr" id="radio5" value="#ffffff">
                                    <label for="radio5">{!! res('no_color') !!}</label>
                                </div>
                                <div class="radio radio-primary">
                                    <input type="radio" name="color_tr" id="radio6" value="{!! res('color_tr_1_color') !!}">
                                    <label for="radio6">
                                        <div style="width:120px;height: 17px;background-color:{!! res('color_tr_1_color') !!}"></div>
                                    </label>
                                </div>
                                <div class="radio radio-primary">
                                    <input type="radio" name="color_tr" id="radio7" value="{!! res('color_tr_2_color') !!}">
                                    <label for="radio7">
                                        <div style="width:120px;height: 17px;background-color:{!! res('color_tr_2_color') !!}"></div>
                                    </label>
                                </div>
                                <div class="radio radio-primary">
                                    <input type="radio" name="color_tr" id="radio8" value="{!! res('color_tr_3_color') !!}">
                                    <label for="radio8">
                                        <div style="width:120px;height: 17px;background-color:{!! res('color_tr_3_color') !!}"></div>
                                    </label>
                                </div>
                                <div class="radio radio-primary">
                                    <input type="radio" name="color_tr" id="radio9" value="{!! res('color_tr_4_color') !!}">
                                    <label for="radio9">
                                        <div style="width:120px;height: 17px;background-color:{!! res('color_tr_4_color') !!}"></div>
                                    </label>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
            <div class="panel-footer text-right">
                <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                <button type="button" class="btn btn-warning waves-effect" type="button" onclick="cancel_cashback()">{!! res('cancel_cashback') !!}</button>
                <button type="button" class="btn btn-primary waves-effect waves-light" onclick="save_cashback();" type="button">{!! res('ok') !!}</button>
            </div>

        </div>

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
</div><!-- /.modal -->



<div id="panel-modal" class="remark_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading">
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 class="panel-title">{!! res('remark') !!}</h3>
                </div>
                <input type="hidden" class="form-control" name="remark_id" />
                <div class="panel-body">
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
                            <div class="col-md-7 form-control-static">
                                <textarea name="remark_remark" class="form-control" rows="5"></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-4 control-label"> {!! res('color') !!}</label>
                            <div class="col-md-7 form-control-static">
                                <div class="radio radio-primary">
                                    <input checked="checked" type="radio" name="color_tr" id="radio5" value="#ffffff">
                                    <label for="radio5">{!! res('no_color') !!}</label>
                                </div>
                                <div class="radio radio-primary">
                                    <input type="radio" name="color_tr" id="radio6" value="{!! res('color_tr_1_color') !!}">
                                    <label for="radio6">
                                        <div style="width:120px;height: 17px;background-color:{!! res('color_tr_1_color') !!}"></div>
                                    </label>
                                </div>
                                <div class="radio radio-primary">
                                    <input type="radio" name="color_tr" id="radio7" value="{!! res('color_tr_2_color') !!}">
                                    <label for="radio7">
                                        <div style="width:120px;height: 17px;background-color:{!! res('color_tr_2_color') !!}"></div>
                                    </label>
                                </div>
                                <div class="radio radio-primary">
                                    <input type="radio" name="color_tr" id="radio8" value="{!! res('color_tr_3_color') !!}">
                                    <label for="radio8">
                                        <div style="width:120px;height: 17px;background-color:{!! res('color_tr_3_color') !!}"></div>
                                    </label>
                                </div>
                                <div class="radio radio-primary">
                                    <input type="radio" name="color_tr" id="radio9" value="{!! res('color_tr_4_color') !!}">
                                    <label for="radio9">
                                        <div style="width:120px;height: 17px;background-color:{!! res('color_tr_4_color') !!}"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="save_remark();" type="button">{!! res('ok') !!}</button>
                </div>

            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<div id="panel-modal" class="remark_shop_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading">
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 class="panel-title">{!! res('remark') !!}</h3>
                </div>
                <input type="hidden" class="form-control" name="remark_shop_id" />
                <div class="panel-body">
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
                            <div class="col-md-7 form-control-static">
                                <textarea name="remark_shop_remark" class="form-control" rows="5"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="save_shop_remark();" type="button">{!! res('ok') !!}</button>
                </div>

            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div id="panel-modal" class="color_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading">
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 class="panel-title">{!! res('color') !!}</h3>
                </div>
                <input type="hidden" class="form-control" name="color_id" />
                <div class="panel-body">
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label class="col-md-4 control-label"> {!! res('color') !!}</label>
                            <div class="col-md-7 form-control-static">
                                <div class="radio radio-primary">
                                    <input checked="checked" type="radio" name="color_tr" id="radio0" value="#ffffff">
                                    <label for="radio0">{!! res('no_color') !!}</label>
                                </div>
                                <div class="radio radio-primary">
                                    <input type="radio" name="color_tr" id="radio1" value="{!! res('color_tr_1_color') !!}">
                                    <label for="radio1">
                                        <div style="width:120px;height: 17px;background-color:{!! res('color_tr_1_color') !!}"></div>
                                    </label>
                                </div>
                                <div class="radio radio-primary">
                                    <input type="radio" name="color_tr" id="radio2" value="{!! res('color_tr_2_color') !!}">
                                    <label for="radio2">
                                        <div style="width:120px;height: 17px;background-color:{!! res('color_tr_2_color') !!}"></div>
                                    </label>
                                </div>
                                <div class="radio radio-primary">
                                    <input type="radio" name="color_tr" id="radio3" value="{!! res('color_tr_3_color') !!}">
                                    <label for="radio3">
                                        <div style="width:120px;height: 17px;background-color:{!! res('color_tr_3_color') !!}"></div>
                                    </label>
                                </div>
                                <div class="radio radio-primary">
                                    <input type="radio" name="color_tr" id="radio4" value="{!! res('color_tr_4_color') !!}">
                                    <label for="radio4">
                                        <div style="width:120px;height: 17px;background-color:{!! res('color_tr_4_color') !!}"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="save_color();" type="button">{!! res('ok') !!}</button>
                </div>

            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>

LoadDetail();
var freagment = '{!! Html::js($div) !!}';

function LoadDetail() {
	$.ajax({
        type: 'POST',
        async: true,
        data : 'id={{ $id }}'  ,
        url: rooturl + '_admin/_printorder/load_detail',
        success: function(data) {
          	$("#order_detail").html(data);
          	CloseLoading();
            Switcher();
            setTimeout(function() { $(window).scrollTop($('#' + freagment).offset().top - 60); }, 500);
        }
    });
}

function goprint(status) {
    ConfirmBox(function () {
        $.ajax({
            type: 'POST',
            async: true,
            data : 'id={{ $id }}&status=' + status,
            url: rooturl + '_admin/_printorder/save_print',
            success: function(data) {
                eval(data);
            }
        });
    });
}

</script>
@endsection
