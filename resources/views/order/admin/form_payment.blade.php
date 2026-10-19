{{-- ส่วนกลางของ _admin/_order/form.php เดิม: หมายเหตุ (status 0) / การชำระเงิน + รายการเงินที่เกี่ยวข้อง (status >= 2 หรือ -1) --}}
@php
    use App\Domain\Order\Html;
    use App\Domain\Order\Php74;
    use App\Support\Legacy\Format;
@endphp
@if (Php74::eq($st, 0))
<div class="col-xs-12 col-md-6">
	    <div class="card-box">
	    <h4 class="m-t-0 header-title"><b><span class="text-danger">{!! res('remark') !!}</span></b></h4>
	    <p class="text-muted font-13 m-b-30">
	    </p>
	        <div class="text-left">
	            1. {!! res('order_remark_status_1') !!}
	        </div>
	    </div>
	</div>
@endif
@if (Php74::num($st) >= 2 || Php74::eq($st, -1))
<div class="col-xs-12 col-md-6">
        <div class="card-box">
        <h4 class="m-t-0 header-title"><b>{!! res('payment_thing') !!}</b></h4>
        <p class="text-muted font-13 m-b-30">
        </p>
            <div class="form-horizontal" role="form">
                <div class="form-group">
                    <label class="col-md-4 control-label"> {!! res('total_have_to_paid') !!}</label>
                    <div class="col-md-7 form-control-static">{!! Format::numberformat($data['total'] ?? null) !!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
                    <div class="col-md-7 form-control-static">
                        <textarea class="form-control" name="submit_remark" rows="5" disabled >{!! Html::h($data['submit_remark'] ?? '') !!}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div><div class="col-xs-12 col-md-6">
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
                    <tbody>@if (count($balances) !== 0)@foreach ($balances as $item)@php $exp = explode(' ', (string) $item->create_date); @endphp<tr><td>{!! Format::getthaidate($exp[0]) !!}<br />{{ $exp[1] ?? '' }}</td><td class="text-left"><a href="{{ legacy_href() }}{!! Html::h($item->link) !!}{{ $item->prefix_ref_id }}">{!! Html::h($item->detail) !!}</a></td><td class="text-right">@if (Php74::num($item->amount) >= 0)<span class="text-success">+{!! Format::numberformat($item->amount) !!}</span>@else<span class="text-danger">{!! Format::numberformat($item->amount) !!}</span>@endif</td>@endforeach @else<tr><td colspan="3" class="text-center">{!! res('no_data') !!}</td></tr>@endif

                </tbody>
            </table>
        </div>
    </div>
@endif
