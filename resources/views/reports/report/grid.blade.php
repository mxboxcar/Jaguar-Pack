{{-- _admin/_report/grid.php เดิม --}}
@php
    use App\Domain\Reports\BalanceReport;
    use App\Domain\Reports\Php74;
    use App\Domain\Reports\Rows;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Paging;
    use App\Support\Legacy\Status;
    $is = fn ($v) => Php74::eq($type, $v);
@endphp

<input type="hidden" name="type" value="{{ $typePhp }}" />
<div class="row">
	<div class="col-lg-12">
		<div class="card-box">
			<div class="row m-t-10 m-b-10">
                <div class="form-horizontal">
                    <div class="form-group">
                        <label class="col-md-2 control-label"> {!! res('start_date') !!}</label>
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="text" onblur="GenerateWhere();Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());" class="form-control datepicker" name="date1" value="{{ $date1 }}">
                                <span class="input-group-addon "><i class="icon-calender"></i></span>

                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"> {!! res('end_date') !!}</label>
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="text" onblur="GenerateWhere();Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());" class="form-control datepicker" name="date2" value="{{ $date2 }}">
                                <span class="input-group-addon "><i class="icon-calender"></i></span>

                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-3 col-md-offset-2">
                            <button onclick="createreport()" type="button" class="pull-right btn btn-success waves-effect waves-light">
                                <span class="btn-label"><i class="fa fa-file-excel-o"></i></span>
                                Download Excel File
                            </button>
                        </div>
                    </div>
                </div>
            </div>

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th  class="text-center" style="width: 120px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 105px;">{!! res('working_date') !!}</th>
                            <th class="text-center">{!! res('customer_code') !!}</th>
                            @if ($is(3))<th class="text-center" >{!! res('price_shipping') !!}</th><th class="text-center" >{!! res('price_thai_storage') !!}</th><th class="text-center" >{!! res('price_thai_shipping') !!}</th><th class="text-center" >{!! res('shipping_type') !!}</th>@elseif ($is(5))<th class="text-center" >{!! res('total') !!}</th><th class="text-center" >{!! res('status') !!}</th><th class="text-center" >{!! res('rate') !!}</th>@elseif ($is(6))<th class="text-center" >{!! res('total') !!}</th><th class="text-center" >{!! res('rate') !!}</th>@elseif ($is(7))<th class="text-center" >{!! res('service_price') !!}</th><th class="text-center" >{!! res('total') !!}</th><th class="text-center" >{!! res('rate') !!}</th>@endif
                            <th class="text-center" >{!! res('detail') !!}</th>
                            <th class="text-center" style="width: 130px;">{!! res('amount') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    <tr style="font-size:17px;font-weight: bold;">
                    @php $span = $is(3) ? 8 : ($is(5) ? 7 : ($is(6) ? 6 : ($is(7) ? 7 : 4))); @endphp
<td class="text-right" colspan="{{ $span }}">{!! res('total').Format::numberformatzero($sum->count ?? null).' '.res('list_word') !!}</td>
                        <td class="text-right">{!! Format::numberformat($sum->sum ?? null) !!}</td>
                    </tr>
                    @forelse ($items as $item)
                        @php
                            $exp = explode(' ', (string) $item->create_date);
                            $ref = $item->prefix_ref_id ?? null;
                            $e = fn (string $k) => BalanceReport::ex($extras, $ref, $k);
                        @endphp
                        <tr>
                            <td>
                                {{ ($is(99) || $is(98) ? Rows::prefix('payment') : Rows::prefix('balance')).$item->id }}
                                 

                             </td>
                            <td>{!! Format::getthaidate($exp[0]) !!}<br />{{ $exp[1] ?? '' }}</td>
                            <td class="text-center">{{ $item->code }}</td>
                            @if ($is(3))<td class="text-right">{!! Format::numberformat($e('price_shipping'), true, true, false) !!}</td><td class="text-right">{!! Format::numberformat($e('price_thai_storage'), true, true, false) !!}</td><td class="text-right">{!! Format::numberformat($e('price_thai_shipping'), true, true, false) !!}</td><td class="text-center">{{ $e('ship_name') }}</td>@elseif ($is(5))<td class="text-right">{!! Format::numberformat($e('total'), true, true, true) !!}</td><td class="text-center">{!! Status::getstatus($item->status ?? null) !!}</td><td class="text-right">{!! Format::numberformat($e('rate'), true, true, false) !!}</td>@elseif ($is(6))<td class="text-right">{!! Format::numberformat($e('total'), true, true, true) !!}</td><td class="text-right">{!! Format::numberformat($e('rate'), true, true, false) !!}</td>@elseif ($is(7))<td class="text-right">{!! Format::numberformat($e('charge')) !!}</td><td class="text-right">{!! Format::numberformat($e('sum'), true, true, true) !!}</td><td class="text-right">{!! Format::numberformat($e('rate'), true, true, false) !!}</td>@endif
                            <td class="text-left"><a target="_blank" href="{{ legacy_href().($item->link ?? '').$ref }}">{{ $item->detail }}</a></td>
                            <td class="text-right">
                                @if (Php74::num($item->amount) >= 0)<span class="text-success">+{!! Format::numberformat($item->amount) !!}</span>@else<span class="text-danger">{!! Format::numberformat($item->amount) !!}</span>@endif
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
<input type="hidden" id="txtSearch" value="{{ $txtsearch }}">

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

    initialDatepicker();

	function GenerateWhere() {
		$("#txtSearch").val($("input[name='type']").val() + "[]" + $("input[name='date1']").val() 
            + "[]" + $("input[name='date2']").val());
	}

    function createreport() {
        window.open('{{ legacy_href() }}_admin/_report/file?type='+ $("input[name='type']").val() +'&start='+ $("input[name='date1']").val() +'&end=' + $("input[name='date2']").val())
    }
</script>
