{{-- SR: _admin/_export/print.php + print_2.php (ใบปะหน้า) และ printprice.php + print_3.php ($withPrice = บิลชำระเงิน)
     print_header.php ของ SR ใช้ไฟล์เดียวกับ _admin/_printorder/print (order.print_header); print_footer.php ไม่ถูก include ในไฟล์เดิม
     ค่าจาก DB escape (เดิม echo ดิบ) --}}
@include('order.print_header')
@php
    use App\Domain\Reports\Php74;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Lang;
    $d = fn (string $k) => $data->{$k} ?? null;
    $n = fn ($v) => Php74::num($v);
@endphp

<style>
@media print{@page {size: landscape}}
body { font-family: 'Noto Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif; }
h4 { line-height: 33px!important; }
</style>



<style>
    .lot_table_cus th { font-size:17px!important; }
    .table_send { font-size:20px!important; }
    .table_send tr {     line-height: 35px; }
</style>

<div style="float:right;top:0px">{{ $shipName }}</div>

@if (Php74::eq($d('sender_type'), 0) || Php74::eq($d('sender_type'), 2))
<table class="table_send" style="font-size:22px;font-weight: bold">
    <tr>
        <td rowspan="4" style="text-align: right;vertical-align: top">
            <img style="width: 200px;" class="icon-c-logo " src="{{ legacy_href() }}assets/images/logo.png">
            <br/>
            <div style="font-size:13px">083-824-4160</div>
        </td>
    </tr>
</table>
@else
<div style="width:40%;font-size:18px;">
<div style="text-decoration: underline;font-weight: bold">ผู้ส่ง</div>
<div>{{ $d('sender_name') }} {{ $d('sender_phone') }}</div>
<div>{{ $d('sender_address') }}</div>
</div>
@endif


<table class="table_send" style="margin-left: 25%;font-size:14px;">

    <tr >
        <td style="text-align: right;padding-top:10px;width: 175px;vertical-align: top;font-weight:bold">ชื่อผู้รับ&nbsp;:&nbsp;</td>
        <td style="padding-top:10px;">{{ $d('send_name') }}</td>
    </tr>
    <tr>
        <td style="text-align: right;vertical-align: top;font-weight:bold">โทร&nbsp;:&nbsp;</td>
        <td>{{ $d('send_phone') }}</td>
    </tr>
    <tr>
        <td style="text-align: right;vertical-align: top;font-weight:bold">ที่อยู่&nbsp;:&nbsp;</td>
        <td>{{ $d('send_address') }}</td>
    </tr>

    <tr >
        <td style="text-align: right;vertical-align: top;font-weight:bold">รหัสลูกค้า&nbsp;:&nbsp;</td>
        <td>{{ $d('code') }}</td>
    </tr>
    @if (Php74::neq($d('submit_remark'), ''))
    <tr >
        <td style="text-align: right;vertical-align: top;font-weight:bold">หมายเหตุ&nbsp;:&nbsp;</td>
        <td>{{ $d('submit_remark') }}</td>
    </tr>
    @endif

</table>



<table class="lot_table_cus form-group" style="margin-top:10px;font-size: 14px!important;">
    <thead>
        <tr>
            <th class="text-center" style="width: 20px;"></th>
            <th class="text-center" style="width: 55px;">{!! res('lot_list').'/'.res('no') !!}</th>
            <th class="text-center">{!! res('tracking') !!}</th>
            <th class="text-center">รหัสย่อย</th>
            <th class="text-center">{!! res('amount') !!}</th>
            <th class="text-center">{!! res('weight') !!}</th>
            <th class="text-center">{!! res('width') !!}</th>
            <th class="text-center">{!! res('height') !!}</th>
            <th class="text-center">{!! res('long') !!}</th>
            <th class="text-center">{!! res('cubic') !!}</th>
            @if ($withPrice)
            <th class="text-center">{!! res('service_price') !!}</th>
            <th class="text-center">{!! res('price_kg') !!}</th>
            <th class="text-center">{!! res('price_cubic') !!}</th>
            @endif
            <!-- <th class="text-center">{!! res('shelf_manage') !!}</th> -->
        </tr>
    </thead>

    <tbody>
        @php
            $amount = 0;
            $weight = 0;
            $cubic = 0;
            $priceKg = 0;
            $priceCubic = 0;
            $allService = 0;
            $count = 0;
            $html = '';
            foreach ($details as $item) {
                $count++;
                $amount = $amount + $n($item->amount);
                $weight = $weight + ($n($item->weight) * $n($item->amount));
                $cubic = $cubic + ((($n($item->width) * $n($item->height) * $n($item->long)) / 1000000) * $n($item->amount));
                $service = $n($item->price_qc) + $n($item->price_express) + $n($item->price_repack) + $n($item->price_else) + $n($item->price_craft);
                $allService = $allService + $service;
                $priceKg = $priceKg + $n($item->price_kg);
                $priceCubic = $priceCubic + $n($item->price_cubic);
                $str = Php74::eq($item->lot_type, 0) ? Lang::get('car') : Lang::get('ship');
                // เดิมแทนค่า 0 ด้วย "-" ก่อนแสดง (แล้วเอา "-" ไปคูณต่อ = 0)
                $a = Php74::eq($item->amount, 0) ? '-' : $item->amount;
                $w = Php74::eq($item->weight, 0) ? '-' : $item->weight;
                $wi = Php74::eq($item->width, 0) ? '-' : $item->width;
                $h = Php74::eq($item->height, 0) ? '-' : $item->height;
                $l = Php74::eq($item->long, 0) ? '-' : $item->long;
                $pk = Php74::eq($item->price_kg, 0) ? '-' : $item->price_kg;
                $pc = Php74::eq($item->price_cubic, 0) ? '-' : $item->price_cubic;
                if (Php74::eq($item->is_craft, 1) && Php74::eq($item->is_qc, 1)) {
                    $html .= '<tr style="background-color:#f9ffa5!important;-webkit-print-color-adjust: exact; ">';
                } else {
                    $found = false;
                    if (Php74::eq($item->is_craft, 1)) {
                        $html .= '<tr style="background-color:#ffdfa5!important;-webkit-print-color-adjust: exact; ">';
                        $found = true;
                    }
                    if (Php74::eq($item->is_qc, 1)) {
                        $html .= '<tr style="background-color:#a5ffc5!important;-webkit-print-color-adjust: exact; ">';
                        $found = true;
                    }
                    if (! $found) {
                        $html .= '<tr>';
                    }
                }
                $rs = 'style="vertical-align:top" rowspan="'.($n($item->header_count) + 1).'"';
                $html .= '<td '.$rs.' class="text-center">'.$count.'</td>';
                $html .= '<td '.$rs.' class="text-center">'.$str.' '.e((string) $item->lot_name).'/'.e((string) $item->no).'</td>';
                $html .= '<td>'.e((string) $item->tracking).'</td>';
                $html .= '<td class="text-center">'.e((string) ($item->subuser ?? '')).'</td>';
                $html .= '<td '.$rs.' class="text-right">'.Format::numberformatzero($a).'</td>';
                $html .= '<td '.$rs.' class="text-right">'.Format::numberformatone($n($w) * $n($a)).'</td>';
                $html .= '<td '.$rs.' class="text-right">'.Format::numberformatone($wi).'</td>';
                $html .= '<td '.$rs.' class="text-right">'.Format::numberformatone($h).'</td>';
                $html .= '<td '.$rs.' class="text-right">'.Format::numberformatone($l).'</td>';
                $html .= '<td '.$rs.' class="text-right">'.Format::cubicformat((($n($wi) * $n($h) * $n($l)) / 1000000) * $n($a)).'</td>';
                foreach ($item->children as $child) {
                    $html .= '<tr style="background-color:#ffdfa5!important;-webkit-print-color-adjust: exact;">
                                        <td style="white-space: nowrap;" class="showdetailbox">'.e($child).'</td>
                                    </tr>';
                }
                if ($withPrice) {
                    // print_3.php เดิมต่อช่องราคาหลังแถวลูก (ลำดับ HTML เดียวกับไฟล์เดิม)
                    $html .= '<td '.$rs.' class="text-right">'.Format::numberformat($service).'</td>';
                    $html .= '<td '.$rs.' class="text-right">'.Format::numberformat($pk).'</td>';
                    $html .= '<td '.$rs.' class="text-right">'.Format::numberformat($pc).'</td>';
                }
            }
        @endphp
        {!! $html !!}
    </tbody>
    <thead>
        <th class="text-center " >{!! Format::numberformatzero($count) !!}</th>
        <th class="text-right " ></th>
        <th class="text-right " ></th>
        @if (! $withPrice || Php74::eq($d('sender_type'), 0))<th class="text-right " ></th>@endif
        <th class="text-right" >{!! Format::numberformatzero($amount) !!}</th>
        <th class="text-right" >{!! Format::numberformatone($weight) !!}</th>
        <th class="text-right " colspan="3"></th>
        <th class="text-right" >{!! Format::cubicformat($cubic) !!}</th>
        @if ($withPrice)
        <th class="text-right " >{!! Format::numberformat($allService) !!}</th>
        <th class="text-right" >{!! Format::numberformat($priceKg) !!}</th>
        <th class="text-right" >{!! Format::numberformat($priceCubic) !!}</th>
        @endif
    </thead>
    @if ($withPrice)
    <thead>
        <th class="text-right " colspan="12" ></th>
        <th class="text-right" >{!! Format::numberformat($priceCubic + $priceKg) !!}</th>
    </thead>
    <thead>
        <th class="text-right " colspan="12" >ค่าบริการโกดังไทย</th>
        <th class="text-right" >{!! Format::numberformat($d('price_thai_storage')) !!}</th>
    </thead>
    <thead>
        <th class="text-right " colspan="12" >ค่าจัดส่งในไทย</th>
        <th class="text-right" >{!! Format::numberformat($d('price_thai_shipping')) !!}</th>
    </thead>
    <thead>
        <th class="text-right " colspan="12" >สุทธิ</th>
        <th class="text-right" >{!! Format::numberformat($d('total')) !!}</th>
    </thead>
    @endif
</table>

<script>
window.print();
$(function() {
    $(".currency").each(function() {
        if($(this).text() == "-.00") {
            $(this).parent().text("-");
        }
        if($(this).text() == "-.0") {
            $(this).parent().text("-");
        }
    })
})
</script>
