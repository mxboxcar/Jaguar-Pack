{{-- รายการ balance อ้างอิง (form.php ของ payment / alipay) — $colClass, $w1, $w2, $br --}}
@php
    use App\Support\Legacy\Format;
    use App\Domain\Payment\Php74;
@endphp
<div class="{{ $colClass }}">
        <div class="card-box">
            <h4 class="m-t-0 header-title"><b>{!! res('ref_balance_list') !!}</b></h4>
            <p class="text-muted font-13 m-b-30">
            </p>
            <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: {{ $w1 }}px;">{!! res('working_date') !!}</th>
                            <th class="text-center">{!! res('detail') !!}</th>
                            <th class="text-center" style="width: {{ $w2 }}px;">{!! res('amount') !!}</th>
                        </tr>
                    </thead>
                    <tbody>@forelse ($balance as $item)@php $exp = explode(' ', (string) $item->create_date); @endphp<tr><td>{!! Format::getthaidate($exp[0]) !!}{!! $br !!}{{ $exp[1] ?? '' }}</td><td class="text-left"><a href="{{ legacy_href().$item->link.$item->prefix_ref_id }}">{{ $item->detail }}</a></td><td class="text-right">@if (Php74::num($item->amount) >= 0)<span class="text-success">+{!! Format::numberformat($item->amount) !!}</span>@else<span class="text-danger">{!! Format::numberformat($item->amount) !!}</span>@endif</td>@empty<tr><td colspan="3" class="text-center">{!! res('no_data') !!}</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
