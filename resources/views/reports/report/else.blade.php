{{-- _admin/_report/else.php เดิม (ตาราง HTML ส่งเป็น export.xls) — เลขรายการเดิมใช้ $types[0] (ตัวอักษรตัวแรก) == 99 จึงเป็น prefix balance เสมอ --}}
@php
    use App\Domain\Reports\BalanceReport;
    use App\Domain\Reports\Php74;
    use App\Domain\Reports\Rows;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $is = fn ($v) => Php74::eq($types, $v);
@endphp


<table >
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

@foreach ($rows as $item)
@php
    $exp = explode(' ', (string) $item->create_date);
    $e = fn (string $k) => BalanceReport::ex($extras, $item->prefix_ref_id ?? null, $k);
@endphp
<tr>
    <td>
        {{ Rows::prefix('balance').$item->id }}
    </td>
    <td>{!! Format::getthaidate($exp[0]) !!} {{ $exp[1] ?? '' }}</td>
    <td class="text-center">{{ $item->code }}</td>
    @if ($is(3))<td class="text-right">{{ $e('price_shipping') }}</td><td class="text-right">{{ $e('price_thai_storage') }}</td><td class="text-right">{{ $e('price_thai_shipping') }}</td><td class="text-center">{{ $e('ship_name') }}</td>@elseif ($is(5))<td class="text-right">{{ $e('total') }}</td><td class="text-center">{!! Status::getstatus($item->status ?? null) !!}</td><td class="text-right">{{ $e('rate') }}</td>@elseif ($is(6))<td class="text-right">{{ $e('total') }}</td><td class="text-right">{{ $e('rate') }}</td>@elseif ($is(7))<td class="text-right">{{ $e('charge') }}</td><td class="text-right">{{ $e('sum') }}</td><td class="text-right">{{ $e('rate') }}</td>@endif
    <td class="text-left">{{ $item->detail }}</td>
    <td class="text-right">
        @if (Php74::num($item->amount) >= 0)<span class="text-success">+{{ $item->amount }}</span>@else<span class="text-danger">{{ $item->amount }}</span>@endif
    </td>
</tr>
@endforeach

</tbody>
</table>
