{{-- _admin/_order/grid.php เดิม --}}
@php
    use App\Domain\Order\Html;
    use App\Domain\Order\Php74;
    use App\Support\Legacy\Alerts;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $sel = fn ($v, $x) => Php74::eq($v, $x) ? "selected='selected'" : '';
    $btn = fn ($v, $x) => Php74::eq($v, $x) ? ' btn-danger ' : ' btn-primary ';
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="card-box">
            <div class="row m-t-10 m-b-10">
                <div class="col-xs-10 col-sm-8 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                        <input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{!! Html::h($search) !!}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
                </div>

                <div class="col-xs-2 col-sm-2 col-lg-2">
                    <select name="ship" class="form-control " id="ship" onchange="GenerateWhere();loadAjaxGrid('{{ $ajaxpage }}', '{!! Alerts::js($page) !!}',  $('#txtSearch').val());">
                        <option {!! $sel($ship, '-2') !!} value="-2">- {!! res('all') !!} -</option>  
                        <option {!! $sel($ship, '0') !!} value="0">{!! res('car') !!}</option>
                        <option {!! $sel($ship, '1') !!} value="1">{!! res('ship') !!}</option>
                    </select>
                </div>

                <div class="col-xs-2 col-sm-2 col-lg-2">
                    <select name="status" class="form-control " id="status" onchange="GenerateWhere();loadAjaxGrid('{{ $ajaxpage }}', '{!! Alerts::js($page) !!}',  $('#txtSearch').val());">
                        <option {!! $sel($status, '-2') !!} value="-2">- {!! res('all') !!} -</option>  
                        <option {!! $sel($status, '-1') !!} value="-1">{!! res('order_status_4') !!}</option>    
                        <option {!! $sel($status, '0') !!} value="0">{!! res('order_status_0') !!}</option>    
                        <option {!! $sel($status, '1') !!} value="1">{!! res('order_status_1') !!}</option>   
                        <option {!! $sel($status, '2') !!} value="2">{!! res('order_status_2') !!}</option>   
                        <option {!! $sel($status, '3') !!} value="3">{!! res('order_status_3') !!}</option>   
                    </select>
                </div>
                
            </div>
            <div class="row m-t-10 m-b-10">
                <div class="col-xs-12" style="font-weight: bold">
                    <button style="margin-left:5px;" type="button" onclick="loadAjaxGrid('{{ $ajaxpage }}', '1', '|-2|');" class="btn btn-xs {!! $btn($status, '') !!}">{!! res('all') !!}<span class="" ref="99"></span></a>
                    <button style="margin-left:5px;" type="button" onclick="loadAjaxGrid('{{ $ajaxpage }}', '1', '|-2|0');" class="btn btn-xs {!! $btn($status, '0') !!}">{!! res('order_status_0') !!}<span class="getstatus" ref="0"></span></a>
                    <button style="margin-left:5px;" type="button" onclick="loadAjaxGrid('{{ $ajaxpage }}', '1', '|-2|1');" class="btn btn-xs {!! $btn($status, '1') !!}">{!! res('order_status_1') !!}<span class="getstatus" ref="1"></span></a></a>
                    <button style="margin-left:5px;" type="button" onclick="loadAjaxGrid('{{ $ajaxpage }}', '1', '|-2|2');" class="btn btn-xs {!! $btn($status, '2') !!}">{!! res('order_status_2') !!}<span class="getstatus" ref="2"></span></a></a>
                    <button style="margin-left:5px;" type="button" onclick="loadAjaxGrid('{{ $ajaxpage }}', '1', '|-2|3');" class="btn btn-xs {!! $btn($status, '3') !!}">{!! res('order_status_3') !!}<span class="getstatus" ref="3"></span></a></a>
                    <button style="margin-left:5px;" type="button" onclick="loadAjaxGrid('{{ $ajaxpage }}', '1', '|-2|-1');" class="btn btn-xs {!! $btn($status, '-1') !!}">{!! res('order_status_4') !!}<span class="" ref="4"></span></a></a>
                    <button style="margin-left:5px;" type="button" onclick="loadAjaxGrid('{{ $ajaxpage }}', '1', '|-2|5');" class="btn btn-xs {!! $btn($status, '5') !!}">{!! res('order_status_5') !!}<span class="getstatus" ref="5"></span></a></a>
                    <button style="margin-left:5px;" type="button" onclick="loadAjaxGrid('{{ $ajaxpage }}', '1', '|-2|6');" class="btn btn-xs {!! $btn($status, '6') !!}">{!! res('order_status_6') !!}<span class="" ref="6"></span></a></a>
                </div>
            </div>
            

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th  class="text-center" style="width: 111px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('customer_code') !!}</th>
                            <th class="text-center" >{!! res('admin_remark') !!}</th>
                            <th class="text-center" style="width: 70px;">{!! res('amount_shop') !!}</th>
                            <th class="text-center" style="width: 70px;">{!! res('no_tracking') !!}</th>
                            <th class="text-center" style="width: 70px;">{!! res('no_warehouse') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('total') !!}</th>
                            <th class="text-center" style="width: 70px;">{!! res('shipping_type') !!}</th>
                            <th class="text-center" style="width: 92px;">{!! res('status') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @if (count($items) !== 0)
                        @foreach ($items as $item)
                        @php
                            $locked = Php74::eq($item->status, 0) && Php74::eq($item->is_lock, 1);
                            $exp = explode(' ', (string) $item->create_date);
                        @endphp
                        <tr {!! $locked ? "class='danger'" : '' !!}>
                            
                            <td><a target="_blank" href="{{ legacy_href() }}admin/order/{{ $item->id }}">{{ $prefix }}{{ $item->id }}</a>
                            <div class="text-center">
                                @if ($item->create_date === '0000-00-00 00:00:00' || (string) $item->create_date === '')
-&nbsp;|&nbsp;-
                                @else
{!! Format::getthaidate($exp[0], true) !!}&nbsp;|&nbsp;{!! Format::gettime($exp[1] ?? '') !!}
                                @endif
                            </div>
                            </td>
                            <td class="text-center">{!! Html::h($item->code) !!}</td>
                            <td class="text-center">
                                <input onblur="on_saveremark($(this))" onkeydown="javascript: if(event.keyCode == 13) on_saveremark($(this));" class="editable" type="text" value="{!! Html::h($item->admin_quote) !!}" ref="{{ $item->id }}">
                            </td>
                            <td class="text-right">{!! Format::numberformatzero($item->shop_count) !!}</td>
                            <td class="text-right">
                            {!! $item->no_track === null ? '0' : Format::numberformatzero($item->no_track) !!}</td>
                            <td class="text-right">{!! $item->no_warehouse === null ? '0' : Format::numberformatzero($item->no_warehouse) !!}</td>
                            <td class="text-right">{!! Format::numberformat($item->total) !!}</td>
                            <td class="text-center">{!! Status::getShipType($item->ship) !!}</td>
                            <td class="text-center">{!! Status::getstatusorder($item->status) !!}<br /><small>{!! $locked ? Html::h($lockers[$item->mod_by] ?? '') : '' !!}</small></td>
                            <td class="text-center">
                                <a href="{{ legacy_href() }}admin/order/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary "></i></a>
                                @if (Php74::eq($item->status, 0))
@if (Php74::eq($item->is_lock, 0))<a href="#" onclick="locking({{ $item->id }}, 1);" class="table-action-btn"><i class="md  md-lock-open text-primary "></i></a>@else<a href="#" onclick="locking({{ $item->id }}, 0);" class="table-action-btn"><i class="md  md-lock-outline text-primary "></i></a>@endif
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    @else
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
                    @endif
                    </tbody>
                </table>
                <div class="pull-right" id="paging">
                    กำลังจัดทำหน้า...
                </div>
            </div>
        </div>
        
    </div> <!-- end col -->
</div>

<input type="hidden" id="Checking" />
<input type="hidden" id="txtSearch" value="{!! Html::h($txtsearch) !!}">

<script>


    $.ajax({
        type: 'POST',
        async: true,
        data : 'txtsearch=' + $('#txtSearch').val() + '&page={!! Alerts::js($page) !!}',
        url: rooturl + '{{ $ajaxpage }}/../load_paging',
        success: function(data) {
            $("#paging").html(data);
        }
    });


    $(function() {
        $(".getstatus").each(function() {
            var ref = $(this).attr('ref');
            var t = $(this);

            $.ajax({
                type: 'POST',
                async: true,
                data : 'ref=' + ref + '&search=' + $('#txtSearch').val(),
                url: rooturl + '_admin/_order/load_total_order',
                success: function(data) {
                    t.text(" (" + data + ")");
                }
            });

        });
    });

    function keypressInBox(e) {
        GenerateWhere();
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code == 13) { //Enter keycode                        
            e.preventDefault();
            loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());
        }
    }
    $("#txtsearch").bind("keypress", {}, keypressInBox);

    function Delete(id) {
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{!! Alerts::js($page) !!}',  $('#txtSearch').val());";
        ConfirmBox(function() {
            $.ajax({
                type: 'POST',
                async: true,
                data : 'id=' + id,
                url: rooturl + '{{ $ajaxpage }}/../delete',
                success: function(data) {
                    eval(data);
                }
            });
        });
    }

    function DeleteAll() {
        if($("#Checking").val() == "") {
            ErrorBox('{!! res("add_more_then_one") !!}');
        } else {
            fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{!! Alerts::js($page) !!}',  $('#txtSearch').val());";
            ConfirmBox(function() {
                $.ajax({
                    type: 'POST',
                    async: true,
                    data : 'arr_id=' + $("#Checking").val(),
                    url: rooturl + '{{ $ajaxpage }}/../delete',
                    success: function(data) {
                        eval(data);
                    }
                });
            });
        }
    }

    function locking(id, value) {
        ConfirmBox(function() {
            $.ajax({
                type: 'POST',
                async: true,
                data : 'id=' + id + '&value=' + value,
                url: rooturl + '_admin/_order/save_lock',
                success: function(data) {
                    eval(data);
                    if(data.trim().search("ErrorBox") == -1) {
                        loadAjaxGrid('{{ $ajaxpage }}', '{!! Alerts::js($page) !!}',  $('#txtSearch').val());
                    }
                }
            });
        });
    }

    

    function GenerateWhere() {
        $("#txtSearch").val($("#txtsearch").val() + "|" + $("#ship").val() + "|" + $("#status").val());
    }

    function on_saveremark(ele) {
        OpenLoading();
        var vals = ele.val();
        var ids = ele.attr('ref');

        $.ajax({
            type: 'POST',
            async: true,
            data : 'id=' + ids + '&remark=' + vals,
            url: rooturl + '{{ $ajaxpage }}/../save_remark',
            success: function(data) {
                eval(data);
            }
        });
    }
</script>
