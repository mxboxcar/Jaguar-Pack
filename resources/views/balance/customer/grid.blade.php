{{-- _balance/grid.php เดิม --}}
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
@include('balance.partials.type-select', ['selectClass' => 'form-control '])
                </div>

            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th  class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('working_date') !!}</th>
                            <th class="text-center" >{!! res('type') !!}</th>
                            <th class="text-center" >{!! res('detail') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('amount') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('remain') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
@forelse($items as $item)
@php $exp = explode(' ', (string) $item->create_date); $remain = Ledger::remain($item->amount, $item->prev_sum); @endphp
                        <tr>
                            <td>{{ $prefix.$item->id }}</td>
                            <td class="text-center">{!! Format::getthaidate($exp[0], true) !!}<br />{!! Format::gettime(e($exp[1] ?? '')) !!}</td>
                            <td class="text-center">{{ $item->name }}</td>
                            <td class="text-left">
                                <a href="{{ legacy_href() }}{{ $item->link }}{{ $item->prefix_ref_id }}">
                                    {{ $item->detail }}
                                </a>
@if((string) $item->type === '12' && (string) $item->w_upload !== '')
<a class="fancybox" href="{{ legacy_blob($item->w_upload) }}">
                                            <img src="{{ legacy_blob($item->w_upload) }}" style="border:1px solid #a0a0a0;max-width: 26px;max-height: 26px;margin-right: 10px;">
                                        </a>
@endif
                                </td>
                            <td class="text-right">
@if(Ledger::nonNegative($item->amount))
<span class="text-success">+{!! Format::numberformat($item->amount) !!}</span>
@else
<span class="text-danger">{!! Format::numberformat($item->amount) !!}</span>
@endif
                            </td>
                            <td class="text-right">
@if($remain >= 0)
{!! Format::numberformat($remain) !!}
@else
<span class="text-danger">{!! Format::numberformat($remain) !!}</span>
@endif
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

    function GenerateWhere() {
        $("#txtSearch").val($("#txtsearch").val() + "|" + $("#status").val());
    }
</script>
