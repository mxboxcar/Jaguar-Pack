{{-- _admin/_warehouse_section_move/grid.php เดิม --}}
@php
    use App\Domain\Warehouse\Php74;
    use App\Support\Legacy\Format;
    // เดิม: $item['x'] >= 0 (NULL >= 0 = true)
    $ge0 = fn ($v) => $v === null || Php74::num($v) >= 0;
@endphp
<div class="row">
    <div class="col-lg-12">
        <div class="card-box">
            <div class="row m-t-10 m-b-10">
                <div class="col-xs-10 col-sm-8 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                        <input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{{ $search }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
                </div>

            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th  class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('working_date') !!}</th>
                            <th class="text-center">{!! res('name') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('width') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('height') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('long') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('cubic') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($items as $item)
                        @php $exp = explode(' ', (string) $item['create_date']); @endphp
                        <tr>
                            <td>{{ $prefix }}{{ $item['id'] }}</td>
                            <td class="text-center">{!! Format::getthaidate($exp[0], true) !!}<br />{!! Format::gettime($exp[1] ?? null) !!}</td>
                            <td class="text-center">{{ $item['name'] }}</td>
                            <td class="text-right">
                                {{-- เดิม: เทียบ weight แต่แสดง amount (ไม่มีคอลัมน์ amount → ว่าง) --}}
                                @if($ge0($item['weight']))<span class="text-success">+{!! Format::numberformatone($item['amount'] ?? null) !!}</span>@else<span class="text-danger">{!! Format::numberformatone($item['amount'] ?? null) !!}</span>@endif
                            </td>
                            <td class="text-right">
                                @if($ge0($item['height']))<span class="text-success">+{!! Format::numberformatone($item['height']) !!}</span>@else<span class="text-danger">{!! Format::numberformatone($item['height']) !!}</span>@endif
                            </td>
                            <td class="text-right">
                                @if($ge0($item['long']))<span class="text-success">+{!! Format::numberformatone($item['long']) !!}</span>@else<span class="text-danger">{!! Format::numberformatone($item['long']) !!}</span>@endif
                            </td>
                            <td class="text-right">
                                {!! Format::cubicformat((Php74::num($item['width']) * Php74::num($item['height']) * Php74::num($item['long'])) / 1000000) !!}
                            </td>
                            <td class="text-center">
                                <a target="_blank"  href="#" onclick="load_amount({{ $item['id'] }});return false;" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                            </td>
                        </tr>
                    @empty
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
                    @endforelse
                    </tbody>
                </table>
                <div class="pull-right">
                    {!! \App\Support\Legacy\Paging::getpaging($page, $total, 10, $ajaxpage) !!}
                </div>
            </div>
        </div>
        
    </div> <!-- end col -->
</div>

<input type="hidden" id="Checking" />
<input type="hidden" id="txtSearch" value="{{ $txtsearch }}">


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
                                    @foreach($types as $t)<option  value='{{ $t->id }}'>{{ $t->name }}</option>@endforeach
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
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ preg_replace('/[^0-9]/', '', (string) $page) }}',  $('#txtSearch').val());";
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
            fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ preg_replace('/[^0-9]/', '', (string) $page) }}',  $('#txtSearch').val());";
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