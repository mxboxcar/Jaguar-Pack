{{-- ตารางขนาด/ราคา (price_shipping_detail) ของ grid เดิม — $item, $admin --}}
@php use App\Domain\Tracking\Php74; use App\Support\Legacy\Format; @endphp
                                <table  class="price_shipping_detail" {!! $admin ? '' : ' ' !!}style="margin: 0 auto 0 auto;">
                                    <tr>
                                        <td  class="text-right left">{!! res('wlh') !!}&nbsp;:</td>
                                        <td style="padding-left:3px" class="text-left">
                                           ({{ $item->width.'*'.$item->long.'*'.$item->height }}) * {{ $item->amount }}
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
