{{-- _admin/_mallstock/grid.php เดิม --}}
@use('App\Support\Legacy\Alerts')
@use('App\Support\Legacy\Format')
@use('App\Support\Legacy\Paging')

<div class="row">
    <div class="col-lg-12">
        <div class="card-box">
            <div class="row m-t-10 m-b-10">
                <div class="col-xs-10 col-sm-8 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                        <input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{{ $txtSearch[0] }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
                </div>

                <div class="col-xs-4 col-sm-4 col-lg-4">
                    <select name="status" class="form-control " id="status" onchange="GenerateWhere()">
                        <option {!! ($txtSearch[1] ?? null) == '-2' ? "selected='selected'" : '' !!} value="-2">- {!! res('all') !!} -</option>  
@foreach ($typesActive as $row)
@if (($txtSearch[1] ?? null) == (string) $row->id)<option selected="selected" value="{{ $row->id }}">{{ $row->name }}</option>@else<option value="{{ $row->id }}">{{ $row->name }}</option>@endif
@endforeach
                    </select>
                </div>
                
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 105px;">{!! res('working_date') !!}</th>
                            <th class="text-center">{!! res('name') !!}</th>
                            <th class="text-center">{!! res('code') !!}</th>
                            <th class="text-center">{!! res('detail') !!}</th>
                            <th class="text-center">{!! res('color') !!}</th>
                            <th class="text-center">{!! res('size') !!}</th>
                            <th class="text-center" style="width: 120px;">จำนวนการใช้สต๊อค</th>
                            <th class="text-center" style="width: 90px;">{!! res('stock_remain') !!}</th>
                        </tr>
                    </thead>
                    <tbody>
@if (count($data) !== 0)
@foreach ($data as $item)
                        <tr>
                            <td>{{ $prefix }}{{ $item->id }}</td>
                            <td class="text-center">
@php $exp = explode(' ', (string) $item->create_date); @endphp
@if ((string) $item->create_date === '0000-00-00 00:00:00' || (string) $item->create_date === '')-<br />-@else{!! Format::getthaidate($exp[0], true) !!}<br />{!! Format::gettime($exp[1] ?? '') !!}@endif
                            </td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->code }}</td>
                            <td>{{ $item->detail }}</td>
                            <td>{{ $item->color }}</td>
                            <td>{{ $item->size }}</td>
                            <td class="text-center">{{ $item->amount }}</td>
                            <td class="text-center">
@php $amount = \App\Http\Controllers\Shop\Admin\MallStockController::remain($item->amount, $before[$item->id] ?? null); @endphp
@if ((float) $amount >= 0){{ $amount }}@else<span class="text-danger">{{ $amount }}</span>@endif
                            </td>
                        </tr>
@endforeach
@else
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
@endif
                    </tbody>
                </table>
                <div class="pull-right">
                    {!! Paging::getpaging($Page, $rows, 10, $ajaxpage) !!}
                </div>
            </div>
        </div>
        
    </div> <!-- end col -->
</div>

<input type="hidden" id="Checking" />
<input type="hidden" id="txtSearch" value="{{ $txtsearchRaw }}">


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
                        <div class="form-group">
                            <label class="col-md-4 control-label"> {!! res('customer_code') !!}</label>
                            <div class="col-md-4">
                                <input type="hidden" name="amount_id" value="" />
                                <input type="text" class="form-control" name="amount_code" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-4 control-label"> {!! res('amount') !!}</label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="amount_value" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-4 control-label"> {!! res('type') !!}</label>
                            <div class="col-md-4">
                                <select class="form-control" name="type" id="amount_select">
@foreach ($types as $row)
<option  value='{{ $row->id }}'>{{ $row->name }}</option>
@endforeach
                                </select>
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

    $("input[name='amount_code']").typeahead({
      source: function (query, process) {
        $.ajax({
          url: rooturl + '_admin/_lot/load_autocomplete_user',
          type: 'POST',
          dataType: 'JSON',
          data: 'query=' + query,
          success: function(data) {
            process(data.code);
          }
        })
      }
    });

    
    function Delete(id) {
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ Alerts::js($Page) }}',  $('#txtSearch').val());";
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
            ErrorBox('{!! res('add_more_then_one') !!}');
        } else {
            fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ Alerts::js($Page) }}',  $('#txtSearch').val());";
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
            url: rooturl + '_admin/_balance/load_amount',
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
                url: rooturl + '_admin/_balance/save_amount',
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