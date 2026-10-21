{{-- _admin/_order/form.php เดิม — PACK = แบบ jl ไม่มี VAT + ปุ่มเปลี่ยนประเภทส่งของ SR (ไม่พบใบ = หน้าเปล่าแบบเดิม; สิทธิ์ admin_order ที่ middleware) --}}
@extends('legacy.layout')
@php
    use App\Domain\Order\Html;
    use App\Domain\Order\Php74;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $did = $data['id'] ?? '';
@endphp

@section('content')

<div class="col-sm-7">
    {{-- tag เสีย "<small?php ..." ในไฟล์เดิมของ PACK (หัวข้อไม่แสดงเลขใบ) — คงไว้ตามเดิม --}}
    <h4 class="page-title">{!! res('order') !!}<small?php echo getPrefix('order').$data['id']; ?></small></h4>
    <ol class="breadcrumb">
        <li>
            <a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
        </li>
        <li>
            <a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
        </li>
        <li>
            <a href="{{ legacy_href() }}admin/order">{!! res('order') !!}</a>
        </li>
        <li class="active">
            {{ $prefix }}{{ $did }}
        </li>
    </ol>
</div>

<div class="col-sm-5 pull-right">
    <div class="button-list">

        @if (Php74::eq($st, 0))
            <button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
                <span class="btn-label"><i class="fa fa-save"></i></span>
                {!! res('approved') !!}
            </button>
        @endif

        @if (Php74::eq($st, 2))
            <button onclick="approved()" type="button" class="pull-right btn btn-success waves-effect waves-light">
                <span class="btn-label"><i class="fa fa-check"></i></span>
                {!! res('approved') !!}
            </button>
        @endif

        @if (Php74::num($st) < 2)
            <button onclick="canceling()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
                <span class="btn-label"><i class="fa fa-save"></i></span>
                {!! res('cancel_this') !!}
            </button>
        @endif

        {{-- ปุ่มเปลี่ยนประเภทส่ง แสดงเฉพาะผู้ใช้ id 84 และ 3 (ตายตัวในไฟล์เดิม) --}}
        @if (in_array((int) ($user->id ?? 0), [84, 3], true))
            <button onclick="change_sender_type('{{ Php74::eq($data['ship'] ?? null, '0') ? '1' : '0' }}')" type="button" class="pull-right btn btn-danger waves-effect waves-light">
                <span class="btn-label"><i class="fa fa-save"></i></span>
                เปลี่ยนเป็นส่ง{!! Php74::eq($data['ship'] ?? null, '0') ? res('ship') : res('car') !!}
            </button>
        @endif

        <a type="button" href="{{ legacy_href() }}admin/order" class="pull-right btn btn-warning waves-effect waves-light">
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
                <label class="col-md-4 control-label"> {!! res('no_for_grid') !!}</label>
                <div class="col-md-7 form-control-static">
                    {{ $prefix }}{{ $did }}
                </div>
            </div>
            <div class="form-group">
                <label class="col-md-4 control-label"> Reference</label>
                <div class="col-md-7 form-control-static">
                    {!! Html::h($data['code'] ?? '') !!}#{{ $did }}{!! Php74::eq($data['ship'] ?? null, 1) ? '(SEA)' : '' !!}
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
                    {!! Status::getstatusorder($st) !!}@if (Php74::eq($st, -1))<div style='margin-top: 10px;' class='text-danger'>* {!! Html::h($data['cancel_remark'] ?? '') !!}</div>@endif
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
                <div class="col-md-7">
                    <input type="text" onblur="onsavequote($(this))" class="form-control" id="quote" value="{!! Html::h($data['admin_quote'] ?? '') !!}" />
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

@include('order.admin.form_payment')


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
    {!! Php74::eq($st, -1) ? 'Switcher(false);' : 'Switcher();' !!}
    LoadDetail();
    var freagment = '{!! Html::js($div) !!}';

    function canceling() {
        $(".cancel_remark_form").modal('show');
    }

    function canceling2() {
        ConfirmBox(function() {
            OpenLoading();
            $.ajax({
                type: 'POST',
                async: true,
                data: 'id={{ $id }}&cancel_remark=' +
                    $("#cancel_remark").val(),
                url: rooturl + '_admin/_order/delete',
                success: function(data) {
                    eval(data);
                }
            });
        })
    }

    function SaveCartCraft(ele, id) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + id + '&value=' + ele.val(),
            url: rooturl + '_admin/_order/save_craft',
            success: function(data) {
                LoadDetail();
            }
        });
    }

    function SaveCartQc(ele, id) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + id + '&value=' + ele.val(),
            url: rooturl + '_admin/_order/save_qc',
            success: function(data) {
                LoadDetail();
            }
        });
    }

    function SaveCartPrice(ele, id) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + id + '&value=' + ele.val(),
            url: rooturl + '_admin/_order/save_price',
            success: function(data) {
                LoadDetail();
            }
        });
    }

    function SaveCartAmount(ele, id) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + id + '&value=' + ele.val(),
            url: rooturl + '_admin/_order/save_amount',
            success: function(data) {
                LoadDetail();
            }
        });
    }

    function LoadDetail() {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id={{ $id }}',
            url: rooturl + '_admin/_order/load_detail',
            success: function(data) {
                $("#order_detail").html(data);
                CloseLoading();
                Switcher();
                setTimeout(function() {
                    $(window).scrollTop($('#' + freagment).offset().top - 60);
                }, 500);
            }
        });
    }

    function save_order_number(ele, id) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + id + '&value=' + ele.val(),
            url: rooturl + '_admin/_order/save_order_number',
            success: function(data) {

            }
        });
    }

    function save_shipping(ele, id) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + id + '&value=' + ele.val(),
            url: rooturl + '_admin/_order/save_shipping',
            success: function(data) {
                LoadDetail();
            }
        });
    }

    function save_tracking(ele, id) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + id + '&value=' + ele.val(),
            url: rooturl + '_admin/_order/save_tracking',
            success: function(data) {

            }
        });
    }

    function save_quote(ele, id) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + id + '&value=' + ele.val(),
            url: rooturl + '_admin/_order/save_quote',
            success: function(data) {

            }
        });
    }

    function onsavequote(ele) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id={{ $did }}&value=' + ele.val(),
            url: rooturl + '_admin/_order/save_quote_admin',
            success: function(data) {

            }
        });
    }

    function sumiting() {
        ConfirmBox(function() {
            $.ajax({
                type: 'POST',
                async: true,
                data: 'id={{ $did }}',
                url: rooturl + '_admin/_order/save_approved',
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
                data: 'id={{ $did }}',
                url: rooturl + '_admin/_order/save_finish',
                success: function(data) {
                    eval(data);
                }
            });
        });
    }

    function deleteline(ele, id, bill_product_on_tracking_id) {
        //console.log(ele.parents('.tracking_list').remove());
        ConfirmBox(function() {
            $.ajax({
                type: 'POST',
                async: true,
                data: 'id=' + id + '&ref=' + bill_product_on_tracking_id,
                url: rooturl + '_admin/_order/save_remove_track',
                success: function(data) {
                    ele.parents('.tracking_list').remove();
                    CloseLoading();
                }
            });
        });
    }

    function addnewline(ele, bill_product_detail_id) {
        //console.log(ele.parent().parent().parent().find('.tracking_list'));
        $.ajax({
            type: 'POST',
            async: true,
            data: 'value=' + ele.val() + '&ref=' + bill_product_detail_id,
            url: rooturl + '_admin/_order/save_add_new_track',
            success: function(data) {
                ele.parent().parent().parent().find('.tracking_list').last().after(data);
            }
        });
    }

    function save_cashback() {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + $("input[name='cashback_id']").val() +
                '&amount=' + $("input[name='cashback_amount']").val() +
                '&cashback=' + $("input[name='cashback_number']").val() +
                '&cashback_remark=' + $("textarea[name='cashback_remark']").val() +
                '&cashback_item=' + $("input[name='cashback_item']").val() +
                '&color=' + $("input[name='color_tr']:checked").val(),
            url: rooturl + '_admin/_order/save_cashback',
            success: function(data) {
                eval(data);
                $('.modal').modal('hide');
            }
        });
    }

    function save_remark() {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + $("input[name='remark_id']").val() +
                '&value=' + encodeURIComponent($("textarea[name='remark_remark']").val()) +
                '&color=' + $("input[name='color_tr']:checked").val(),
            url: rooturl + '_admin/_order/save_remark_product',
            success: function(data) {
                eval(data);
                $('.modal').modal('hide');
            }
        });
    }

    function save_shop_remark() {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + $("input[name='remark_shop_id']").val() +
                '&value=' + $("textarea[name='remark_shop_remark']").val(),
            url: rooturl + '_admin/_order/save_remark_shop',
            success: function(data) {
                eval(data);
                $('.modal').modal('hide');
            }
        });
    }

    function save_color() {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + $("input[name='color_id']").val() +
                '&value=' + $("input[name='color_tr']:checked").val(),
            url: rooturl + '_admin/_order/save_color',
            success: function(data) {
                eval(data);
                $('.modal').modal('hide');
            }
        });
    }

    function onchageproducttype(id, ele) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + id + '&value=' + ele.val(),
            url: rooturl + '_admin/_order/save_product_type',
            success: function(data) {

            }
        });
    }

    function cancel_cashback() {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + $("input[name='cashback_id']").val(),
            url: rooturl + '_admin/_order/save_cashback_cancel',
            success: function(data) {
                eval(data);
                $('.modal').modal('hide');
            }
        });
    }

    function cashback_form(id, amount, rate, cn_price, amount_before, cashback_remark, item) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'amount=' + amount + '&rate=' + rate + '&cn_price=' + cn_price,
            url: rooturl + '_admin/_order/load_calculate_cashback',
            success: function(data) {
                $("input[name='cashback_id']").val(id);
                $("input[name='cashback_amount']").val(amount);
                $("input[name='cashback_number']").val(data);
                $("input[name='cashback_rate']").val(rate);
                $("input[name='cashback_price']").val(cn_price);
                $("textarea[name='cashback_remark']").val(cashback_remark);
                $("input[name='cashback_item']").val(item);
                $(".amount_before").text(amount_before);
                $(".cashback_form").modal('show');

                $.ajax({
                    type: 'POST',
                    async: true,
                    data: 'id=' + id,
                    url: rooturl + '_admin/_order/load_color_form',
                    success: function(data) {
                        data = $.parseJSON(data);
                        $(".cashback_form input[name='color_tr']").each(function() {
                            if ($(this).val() == data.color_tr) {
                                $(this).prop('checked', true);
                            } else {
                                $(this).prop('checked', false);
                            }
                        });
                    }
                });
            }
        });
    }

    function remark_form(id) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + id,
            url: rooturl + '_admin/_order/load_remark_product',
            success: function(data) {
                data = $.parseJSON(data);
                $("input[name='remark_id']").val(data.id);
                $("textarea[name='remark_remark']").val(data.admin_remark);
                $(".remark_form").modal('show');

                $.ajax({
                    type: 'POST',
                    async: true,
                    data: 'id=' + id,
                    url: rooturl + '_admin/_order/load_color_form',
                    success: function(data) {
                        data = $.parseJSON(data);
                        $(".remark_form input[name='color_tr']").each(function() {
                            if ($(this).val() == data.color_tr) {
                                $(this).prop('checked', true);
                            } else {
                                $(this).prop('checked', false);
                            }
                        });
                    }
                });
            }
        });
    }


    function remark_shop_form(id) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + id,
            url: rooturl + '_admin/_order/load_remark_shop',
            success: function(data) {
                data = $.parseJSON(data);
                $("input[name='remark_shop_id']").val(data.id);
                $("textarea[name='remark_shop_remark']").val(data.remark_shop);
                $(".remark_shop_form").modal('show');
            }
        });
    }

    function color_form(id) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + id,
            url: rooturl + '_admin/_order/load_color_form',
            success: function(data) {
                data = $.parseJSON(data);
                $("input[name='color_id']").val(data.id);

                $(".color_form input[name='color_tr']").each(function() {
                    if ($(this).val() == data.color_tr) {
                        $(this).prop('checked', true);
                    } else {
                        $(this).prop('checked', false);
                    }
                });

                $(".color_form").modal('show');
            }
        });
    }

    function on_recal_cashback() {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'amount=' + $("input[name='cashback_amount']").val() +
                '&rate=' + $("input[name='cashback_rate']").val() +
                '&cn_price=' + $("input[name='cashback_price']").val(),
            url: rooturl + '_admin/_order/load_calculate_cashback',
            success: function(data) {
                $("input[name='cashback_number']").val(data);
            }
        });
    }

    function make_no_payment_track(id) {
        ConfirmBox(function() {
            OpenLoading();
            $.ajax({
                type: 'POST',
                async: true,
                data: 'id=' + id,
                url: rooturl + '_admin/_order/save_valid_track',
                success: function(data) {
                    LoadDetail();
                }
            });
        })
    }

    function change_sender_type(t) {
        ConfirmBox(function() {
            OpenLoading();
            $.ajax({
                type: 'POST',
                async: true,
                url: rooturl + '_admin/_order/save_change_send_type',
                data: 'id={{ $did }}' +
                    '&type=' + t,
                success: function(data) {
                    window.location.reload();
                }
            });
        });
    }
</script>
@endsection
