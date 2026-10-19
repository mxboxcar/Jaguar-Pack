{{-- _admin/_combine_track/load_tracking.php เดิม (แถว <tr> ใส่ใน tbody#result) --}}
@php
    use App\Domain\Tracking\Php74;
    use App\Support\Legacy\Status;
@endphp
@forelse ($items as $item)
@php $tid = (int) $item->id; @endphp
    <tr>
    	<td><input type="checkbox" name="tracking_id" value="{{ $item->id }}" /></td>
        <td>
            <table  class="shipping_detail" style="margin: 0 auto 0 auto;">

                <tr>
                    <td class="text-right left" style="vertical-align: top;">{!! res('order_number') !!} : </td>
                    <td style="padding-left:3px" class="text-left">
                        {{ Php74::eq($item->order, '') ? '-' : $item->order }}
                    </td>
                </tr>
                @include('tracking.partials.order_links', ['links' => $orderIds[$tid] ?? []])
                <tr>
                    <td class="text-right left">{!! res('tracking') !!} : </td>
                    <td style="padding-left:3px" class="text-left">
                    {{ Php74::eq($item->tracking, '') ? '-' : $item->tracking }}
                   </td>
                </tr>
                <tr>
                    <td class="text-right left">{!! res('lot_and_no') !!} : </td>
                    <td style="padding-left:3px" class="text-left">
                        {!! Status::getShipType($item->lot_type) !!} <a href='{{ legacy_href() }}admin/lot/{{ $item->lot_id }}'>{{ $item->lot_name }}</a>/{{ $item->no }}
                    </td>
                </tr>
                @include('tracking.partials.flags', ['typeName' => $item->product_type_name])
                <tr>
                    <td class="text-right left">{!! res('admin_remark') !!} : </td>
                    <td style="padding-left:3px" class="text-left">
                    {{ $quotes[$tid] ?? '' }}
                   </td>
                </tr>
                <tr>
                    <td class="text-right left">{!! res('remark') !!} : </td>
                    <td style="padding-left:3px" class="text-left">
                    {{ $item->remark }}
                   </td>
                </tr>

            </table>
        </td>
        <td style="vertical-align: top;text-align:center">
            <div style="padding: 0 10px 0 10px;">{{ $item->code }}</div>
        </td>
        <td>
@include('tracking.partials.prices', ['admin' => true])
        </td>
        

        <td class="text-center boxcar-td" style="vertical-align: top;">
@include('tracking.partials.dates', ['admin' => true])
        </td>

        <td class="text-center boxcar-td" style="vertical-align:top">
            @if (Php74::eq($item->payment_id, 0) || Php74::eq($item->payment_id, '-2'))
<i class='fa  fa-close text-danger' ></i>
            @else
<i class='fa fa-check-circle text-success' ></i><div><a href='{{ legacy_href() }}admin/payment/{{ $item->payment_id }}'>{{ $payPrefix.$item->payment_id }}</a></div>
            @endif
        </td>
        
        <td class="text-center">
            <a href="{{ legacy_href() }}admin/tracking/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
        </td>
        
  </tr>
@empty
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
@endforelse
