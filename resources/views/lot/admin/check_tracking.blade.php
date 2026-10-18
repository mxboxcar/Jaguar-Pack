{{-- _admin/_lot/load_check_tracking.php เดิม (แถวใน tbody#resultserach ของหน้า admin/lot) --}}
@php
    use App\Domain\Lot\Php74;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $dt = fn ($v) => (string) $v === '0000-00-00 00:00:00' ? '-' : Format::getthaidate(explode(' ', (string) $v)[0], true, true);
    $onoff = fn ($v) => Php74::eq($v, 0) ? "<span class='text-danger'>".res('disable').'</span>' : "<span class='text-success'>".res('active').'</span>';
@endphp
@forelse ($rows as $item)
@php
    $links = $orders[(int) $item->id] ?? [];
    $orderLink = '';
    foreach ($links as $o) {
        $orderLink .= ' / <a target="_blank" href="'.e(legacy_href('admin/order/'.$o)).'">'.e($orderPrefix.$o).'</a>';
    }
@endphp
    <tr>
        <td>
            <table  class="shipping_detail" style="margin: 0 auto 0 auto;">

                <tr>
                    <td class="text-right left" style="vertical-align: top;">{!! res('order_number') !!} : </td>
                    <td style="padding-left:3px" class="text-left">
                        {{ Php74::eq($item->order, '') ? '-' : $item->order }}
                    </td>
                </tr>
                @if ($orderLink !== '')
                <tr>
                    <td class="text-right left" style="vertical-align: top;">{!! res('system_order_number') !!} : </td>
                    <td style="padding-left:3px" class="text-left">
                        {!! substr($orderLink, 2) !!}
                    </td>
                </tr>
                @endif
                <tr>
                    <td class="text-right left">{!! res('tracking') !!} : </td>
                    <td style="padding-left:3px" class="text-left">
                    {{ Php74::eq($item->tracking, '') ? '-' : $item->tracking }}
                   </td>
                </tr>
                <tr>
                    <td class="text-right left">{!! res('lot_and_no') !!} : </td>
                    <td style="padding-left:3px" class="text-left">
                        {!! Status::getShipType($item->lot_type) !!} <a target='_blank' href='{{ legacy_href() }}admin/lot/{{ $item->lot_id }}'>{{ $item->lot_name }}</a>/{{ $item->no }}
                    </td>
                </tr>
                <tr>
                    <td class="text-right left">{!! res('wood_protect') !!} : </td>
                    <td style="padding-left:3px" class="text-left">
                    {!! $onoff($item->is_craft) !!}</td>
                </tr>
                <tr>
                    <td class="text-right left">{!! res('qc') !!} : </td>
                    <td style="padding-left:3px" class="text-left">
                    {!! $onoff($item->is_qc) !!}</td>
                </tr>
                <tr>
                    <td class="text-right left">{!! res('type') !!} : </td>
                    <td style="padding-left:3px" class="text-left">
                    {{ $item->product_type_name }}
                   </td>
                </tr>
                <tr>
                    <td class="text-right left">{!! res('admin_remark') !!} : </td>
                    <td style="padding-left:3px" class="text-left">
                    {{ $quotes[(int) $item->id] ?? '' }}
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
            <table  class="price_shipping_detail" style="margin: 0 auto 0 auto;">
                <tr>
                    <td class="text-right left">{!! res('wlh') !!}&nbsp;:</td>
                    <td style="padding-left:3px" class="text-left">
                       ({{ $item->width }}*{{ $item->long }}*{{ $item->height }}) * {{ $item->amount }}
                    </td>
                </tr>
                <tr>
                    <td class="text-right left">{!! res('weight') !!}&nbsp;({!! res('kg') !!})&nbsp;:</td>
                    <td style="padding-left:3px" class="text-left">({{ $item->weight }}) * {{ $item->amount }}</td>
                </tr>
                <tr>
                    <td class="text-right left text-danger" colspan="2" style="height:10px;" ></td>
                </tr>
                
                <tr>
                    <td class="text-right left text-danger">{!! res('price_by_kg') !!} : </td>
                    <td style="padding-left:3px" class="text-left text-danger">
                    {!! Php74::neq($item->price_kg, 0) ? Format::numberformat($item->price_kg) : '-' !!}
                    </td>
                </tr>
                <tr>
                    <td class="text-right left text-danger">{!! res('price_by_cubic') !!} : </td>
                    <td style="padding-left:3px" class="text-left text-danger">
                    {!! Php74::neq($item->price_cubic, 0) ? Format::numberformat($item->price_cubic) : '-' !!}
                    </td>
                </tr>
            </table>
        </td>
        

        <td class="text-center boxcar-td" style="vertical-align: top;">
            <table  class="shipping_est" style="margin: 0 auto 0 auto;">
                <tr>
                    <td style="" class="text-right left">{!! res('date_warehouse') !!} : </td>
                    <td style="" style="padding-left:3px" class="text-left right">
                        {!! $dt($item->date_warehouse) !!}
                    </td>
                </tr>
                <tr>
                    <td class="text-right left">{!! res('date_outofchina') !!} : </td>
                    <td style="padding-left:3px" class="text-left right">
                        {!! $dt($item->date_outofchina) !!}
                    </td>
                </tr>
                <tr>
                    <td class="text-right left">{!! res('date_thai') !!} : </td>
                    <td style="padding-left:3px" class="text-left right">
                        {!! $dt($item->date_thai) !!}
                    </td>
                </tr>
                <tr>
                    <td class="text-right left">{!! res('date_finish') !!} : </td>
                    <td style="padding-left:3px" class="text-left right">
                        {!! $dt($item->date_finish) !!}
                    </td>
                </tr>
            </table>
        </td>

        <td class="text-center boxcar-td" style="vertical-align:top">
            @if (Php74::eq($item->payment_id, 0) || Php74::eq($item->payment_id, '-2'))
<i class='fa  fa-close text-danger' ></i>@else
<i class='fa fa-check-circle text-success' ></i><div><a href='{{ legacy_href() }}admin/payment/{{ $item->payment_id }}' target='_blank'>{{ $payPrefix.$item->payment_id }}</a></div>@endif
        </td>


        <td class="text-center">
            <a target="_blank" href="{{ legacy_href() }}admin/tracking/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
        </td>
        
        
  </tr>
@empty
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
@endforelse
