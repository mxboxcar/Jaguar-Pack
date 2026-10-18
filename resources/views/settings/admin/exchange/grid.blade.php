{{-- _admin/_exchange/grid.php เดิม --}}
@php use App\Support\Legacy\Format; use App\Support\Legacy\Paging; use App\Support\Legacy\Status; use App\Domain\Settings\Php74; use App\Domain\Settings\Input; @endphp

<div class="row">
    <div class="col-lg-12">
        <div class="card-box">
            <div class="row m-t-10 m-b-10">
                <div class="col-sm-6 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                        <input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{{ $termPhp }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
                </div>

                <div class="col-xs-2 col-sm-4 col-lg-4">
                    <select name="role" class="form-control " id="status" onchange="GenerateWhere()">
                        <option @if($status !== null && Php74::eq($status, '-2'))selected='selected'@endif value="-2">- {!! res('all') !!} -</option>  
                        <option @if($status !== null && Php74::eq($status, '-1'))selected='selected'@endif value="-1">{!! res('cancel') !!}</option>    
                        <option @if($status !== null && Php74::eq($status, '0'))selected='selected'@endif value="0">{!! res('no_paid') !!}</option>   
                        <option @if($status !== null && Php74::eq($status, '1'))selected='selected'@endif value="1">{!! res('wait_for_approve') !!}</option>   
                        <option @if($status !== null && Php74::eq($status, '2'))selected='selected'@endif value="2">{!! res('paid') !!}</option>   
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('customer_code') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('working_date') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('approved_date') !!}</th>
                            <th class="text-center" >{!! res('amount') !!}</th>
                            <th class="text-center" style="width: 92px;">{!! res('status') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
@forelse($items as $item)
                        <tr>
                            <td><a target="_blank" href="{{ legacy_href() }}admin/exchange/{{ $item->id }}">{{ $prefix.$item->id }}</a></td>
                            <td class="text-center">{{ $item->code }}</td>
                            <td class="text-center">
@include('settings.partials.grid-date', ['d' => $item->date])
                            </td>
                            <td class="text-center">
@include('settings.partials.grid-date', ['d' => $item->approved_date])
                            </td>
                            <td class="text-right">{!! Format::numberformat($item->amount, true, true, true) !!}</td>
                            
                            <td class="text-center">{!! Status::getstatus($item->status) !!}</td>
                            <td class="text-center">
                                <a href="{{ legacy_href() }}admin/exchange/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
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
<input type="hidden" id="txtSearch" value="{{ $raw }}">

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


    function Delete(id) {
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{!! Input::js($page) !!}',  $('#txtSearch').val());";
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
            fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{!! Input::js($page) !!}',  $('#txtSearch').val());";
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

    function GenerateWhere() {
        $("#txtSearch").val($("#txtsearch").val() + '|' + $("#status").val());
    }
</script>