{{-- ตาราง "รายการบัญชีที่เกี่ยวข้อง" ($html_payment เดิม) — $ledger แต่ละแถวมี types/typess บอกตาราง, $cnRate = แสดง (ยอด × rate) ต่อท้ายรายการหยวน --}}
@php use App\Support\Legacy\Format; use App\Domain\Points\Grid; @endphp
<div class="{{ $boxClass }}">
        <div class="card-box">
            <h4 class="m-t-0 header-title"><b>{!! res('ref_balance_list') !!}</b></h4>
            <p class="text-muted font-13 m-b-30">
            </p>
            <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 130px;">{!! res('working_date') !!}</th>
                            <th class="text-center">{!! res('detail') !!}</th>
                            <th class="text-center" style="width: 80px;">{!! res('amount') !!}</th>
                        </tr>
                    </thead>
                    <tbody>
@forelse($ledger as $item)
@php $exp = explode(' ', (string) $item->create_date); $fmt = $item->kind === 'point' ? Format::numberformatpoint($item->amount) : ($item->kind === 'balance' ? Format::numberformat($item->amount) : Format::numberformat($item->amount, true, true, true)); @endphp
<tr><td>{!! Format::getthaidate($exp[0]) !!}<br/>{{ $exp[1] ?? '' }}</td><td class="text-left"><a href="{{ legacy_href() }}{{ $item->link }}{{ $item->prefix_ref_id }}">{{ $item->detail }}</a>@if(! empty($cnRate) && $item->kind === 'balance_cn') ({!! Format::numberformat((float) $item->amount * 0) !!})@endif</td><td class="text-right">@if(Grid::nonNegative($item->amount))<span class="text-success">+{!! $fmt !!}</span>@else<span class="text-danger">{!! $fmt !!}</span>@endif</td>
@empty
<tr><td colspan="3" class="text-center">{!! res('no_data') !!}</td></tr>
@endforelse

                </tbody>
            </table>
        </div>
    </div>
