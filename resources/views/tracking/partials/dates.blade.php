{{-- ตารางวันที่ (shipping_est) ที่ grid เดิมใช้ซ้ำ — $item, $admin (แอดมินมี style="" เกินมาในแถวแรกแบบเดิม) --}}
                                <table  class="shipping_est" style="margin: 0 auto 0 auto;">
@foreach (['date_warehouse', 'date_outofchina', 'date_thai', 'date_finish'] as $col)
                                    <tr>
@if ($admin && $loop->first)
                                        <td style="" class="text-right left">{!! res($col) !!} : </td>
                                        <td style="" style="padding-left:3px" class="text-left right">
@else
                                        <td class="text-right left">{!! res($col) !!} : </td>
                                        <td style="padding-left:3px" class="text-left right">
@endif
                                            @if ((string) $item->{$col} === '0000-00-00 00:00:00')
-
                                            @else
{!! \App\Support\Legacy\Format::getthaidate(explode(' ', (string) $item->{$col})[0], true, true) !!}
                                            @endif
                                        </td>
                                    </tr>
@endforeach
                                </table>
