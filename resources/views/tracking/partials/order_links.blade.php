{{-- แถว "เลขออเดอร์ในระบบ" แบบลิงก์แอดมิน — $links (bill_product_id), $orderPrefix --}}
@if ($links !== [])
                                    <tr>
                                        <td class="text-right left" style="vertical-align: top;">{!! res('system_order_number') !!} : </td>
                                        <td style="padding-left:3px" class="text-left">
                                            @foreach ($links as $o){{ $loop->first ? ' ' : ' / ' }}<a href="{{ legacy_href() }}admin/order/{{ $o }}">{{ $orderPrefix.$o }}</a>@endforeach

                                        </td>
                                    </tr>
@endif
