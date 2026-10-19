{{-- _admin/_balance_company/grid.php เดิม (dropdown ประเภทแสดงแต่ไม่ได้ใช้กรอง เหมือนเดิม) --}}
@php use App\Support\Legacy\Format; use App\Support\Legacy\Paging; use App\Support\Legacy\Alerts; use App\Domain\Balance\Ledger; @endphp

<div class="row">
    <div class="col-lg-12">
        <div class="card-box">
            <div class="row m-t-10 m-b-10">
                <div class="col-xs-10 col-sm-8 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                        <input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{{ $s['termPhp'] }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
                </div>

                <div class="col-xs-4 col-sm-4 col-lg-4">
@include('balance.partials.type-select', ['selectClass' => 'form-control'])
                </div>

            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th  class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('working_date') !!}</th>
                            <th class="text-center">{!! res('add_by') !!}</th>
                            <th class="text-center" >{!! res('detail') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('amount') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('remain') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
@forelse($items as $item)
@php $exp = explode(' ', (string) $item->create_date); $remain = Ledger::remain($item->amount, $item->prev_sum); @endphp
                        <tr>
                            <td>{{ $prefix.$item->id }}</td>
                            <td class="text-center">{!! Format::getthaidate($exp[0], true) !!}<br />{!! Format::gettime(e($exp[1] ?? '')) !!}</td>
                            <td class="text-center">{{ $item->code }}</td>
                            <td class="text-left">{{ $item->detail }}</td>
                            <td class="text-right">
@if(Ledger::nonNegative($item->amount))
<span class="text-success">+{!! Format::numberformat($item->amount, true, true, true) !!}</span>
@else
<span class="text-danger">{!! Format::numberformat($item->amount, true, true, true) !!}</span>
@endif
                            </td>
                            <td class="text-right">
@if($remain >= 0)
{!! Format::numberformat($remain, true, true, true) !!}
@else
<span class="text-danger">{!! Format::numberformat($remain, true, true, true) !!}</span>
@endif
                            </td>
                            <td class="text-center">
                                <a  href="#" onclick="load_amount({{ (int) $item->id }});return false;" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                            </td>
                        </tr>
@empty
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
@endforelse
                    </tbody>
                </table>
                <div class="pull-right">
                    {!! Paging::getpaging($page, $total, 10, $ajaxpage) !!}
                </div>
            </div>
        </div>

    </div> <!-- end col -->
</div>

<input type="hidden" id="Checking" />
<input type="hidden" id="txtSearch" value="{{ $s['raw'] }}">


<div id="panel-modal" class="amount_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading">
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 class="panel-title">{!! res('cancel_this') !!}</h3>
                </div>
                <div class="panel-body">
                    <div class="form-horizontal">
                        <input type="hidden" name="amount_id" value="" />
                        
                        <div class="form-group">
                            <label class="col-md-4 control-label"> {!! res('amount') !!}</label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="amount_value" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-4 control-label"> {!! res('detail') !!}</label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="amount_detail" />
                            </div>
                        </div>


                    </div>
                </div>
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="save_amount();" type="button">{!! res('ok') !!}</button>
                </div>

            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
    function keypressInBox(e) {
        GenerateWhere();
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code == 13) { //Enter keycode
            e.preventDefault();
            loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());
        }
    }
    $("#txtsearch").bind("keypress", {}, keypressInBox);
    $(".select2").select2();

    function Delete(id) {
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{!! e(Alerts::js($page)) !!}',  $('#txtSearch').val());";
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
            fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{!! e(Alerts::js($page)) !!}',  $('#txtSearch').val());";
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



    function load_amount(id) {
        $.ajax({
            type: 'POST',
            async: true,
            data :  'id=' + id ,
            url: rooturl + '_admin/_balance_company/load_amount',
            success: function(data) {
                data = $.parseJSON(data);
                $("input[name='amount_id']").val(data.id);
                $("input[name='amount_code']").val(data.code);
                $("input[name='amount_value']").val(data.amount);
                $("input[name='amount_detail']").val(data.detail);
                $("#amount_select").val(data.type);
                $(".amount_form").modal('show');
            }
        });
    }

    function save_amount() {
        ConfirmBox(function() {
            $.ajax({
                type: 'POST',
                async: true,
                data :  'id=' + $("input[name='amount_id']").val()
                        + '&amount=' + $("input[name='amount_value']").val()
                        + '&code=' + $("input[name='amount_code']").val()
                        + '&type=' + $("#amount_select").val()
                        + '&detail=' + $("input[name='amount_detail']").val()  ,
                url: rooturl + '_admin/_balance_company/save_amount',
                success: function(data) {
                    if(data.trim().search("ErrorBox") != -1) {
                        eval(data);
                    } else {

                        $('.modal').modal('hide');
                        loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());
                        $(".modal-backdrop").remove();
                    }
                }
            });
        })
    }

    function GenerateWhere() {
        $("#txtSearch").val($("#txtsearch").val() + "|" + $("#status").val());
    }
</script>
