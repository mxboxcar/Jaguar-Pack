{{-- แถว tracking ของ create_form.php / form.php (ลูกค้า) และ _admin/_payment/form.php
     $orderBase: 'order/' | 'admin/order/', $orderWithTracking: ลิงก์ออเดอร์ต่อ /tracking, $note: 'quote' | 'remark', $emptyCols
     $showPrice (PACK แอดมิน): false = ไม่แสดงช่องราคา kg/คิว (ไม่มีสิทธิ์ admin_viewprice_on_payment) --}}
@php
    use App\Domain\Payment\PaymentLookups as P;
    use App\Domain\Payment\Php74;
    use App\Support\Legacy\Format;
@endphp
@forelse ($items as $item)
@php
    $rs = (int) $item->header_count + 1;
    $bp = $orderIds[(int) $item->id] ?? null;
    $bp = $bp === null ? '' : (string) $bp;
    $tlink = legacy_href().$orderBase.$bp.'/'.$item->tracking;
    $olink = $orderWithTracking ? $tlink : legacy_href().$orderBase.$bp;
@endphp
@if (Php74::eq($item->is_craft, 1))<tr style="background-color:#ffdfa5">@else<tr>@endif
<td style="vertical-align:top" rowspan="{{ $rs }}"  class="text-center">{{ P::lotLabel($item) }}</td><td style="white-space: nowrap;"><a href="{{ $tlink }}">{{ $item->tracking }}</a></td><td style="vertical-align:top" rowspan="{{ $rs }}"  style="white-space: nowrap;">@if ($bp !== '')<a href="{{ $olink }}">{{ $orderPrefix.$bp }}</a>@else-@endif</td><td style="vertical-align:top" rowspan="{{ $rs }}" style="white-space: nowrap;">{{ $item->{$note} }}</td><td style="vertical-align:top" rowspan="{{ $rs }}"  class="text-right">{!! Format::numberformatzero($item->amount) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}"  class="text-right">{!! Format::numberformatone(Php74::num($item->weight) * Php74::num($item->amount)) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}"  class="text-right">{!! Format::numberformatone($item->width) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}"  class="text-right">{!! Format::numberformatone($item->height) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}"  class="text-right">{!! Format::numberformatone($item->long) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}"  class="text-right">{!! Format::cubicformat(P::cubic($item)) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}"  class="text-center">{{ $item->type_name }}</td><td style="vertical-align:top" rowspan="{{ $rs }}"  class="text-right showdetailbox">@include('payment.partials.service_cell')
							</td>@if ($showPrice ?? true)<td style="vertical-align:top" rowspan="{{ $rs }}"  class="text-right">{!! Format::getBold(Format::numberformat($item->price_kg, true, false)) !!}</td><td style="vertical-align:top" rowspan="{{ $rs }}"  class="text-right">{!! Format::getBold(Format::numberformat($item->price_cubic, true, false)) !!}</td>@endif</tr>
@include('payment.partials.children')
@empty
<tr><td style="height: 100px;" colspan="{{ $emptyCols }}" class="text-center">{!! res('no_data') !!}</td></tr>
@endforelse
