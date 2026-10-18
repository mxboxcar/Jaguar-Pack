{{-- _payment/view_example.php เดิม (iframe ตัวอย่างใบปะหน้า, print_header.php) — ค่าจาก query string escape ทั้งหมด (เดิม reflected XSS) --}}
@php
    use App\Domain\Payment\PaymentLookups as P;
    use App\Domain\Payment\Php74;
    use App\Support\Legacy\Format;
    // ลูปเดิม: qc/express/repack/else อ่าน key ที่ไม่มี (null) และแทนค่า 0 ด้วย "-" ก่อนแสดง
    $t = ['count' => 0, 'amount' => 0, 'weight' => 0, 'cubic' => 0, 'qc' => 0, 'express' => 0, 'repack' => 0, 'else' => 0, 'kg' => 0, 'pcubic' => 0];
    $rowsHtml = '';
    foreach ($items as $item) {
        $t['count']++;
        $t['amount'] = $t['amount'] + Php74::num($item->amount);
        $t['weight'] = $t['weight'] + (Php74::num($item->weight) * Php74::num($item->amount));
        $t['cubic'] = $t['cubic'] + P::cubic($item);
        $t['kg'] = $t['kg'] + Php74::num($item->price_kg);
        $t['pcubic'] = $t['pcubic'] + Php74::num($item->price_cubic);
        $str = Php74::eq($item->lot_type, 0) ? res('car') : res('ship');
        $i = clone $item;
        foreach (['amount', 'weight', 'width', 'height', 'long'] as $k) {
            if (Php74::eq($i->{$k}, 0)) {
                $i->{$k} = '-';
            }
        }
        $i->price_qc = '-';
        $i->price_express = '-';
        $i->price_else = '-';
        foreach (['price_kg', 'price_cubic'] as $k) {
            if (Php74::eq($i->{$k}, 0)) {
                $i->{$k} = '-';
            }
        }
        $rowsHtml .= '<tr>';
        $rowsHtml .= '<td class="text-center">'.$t['count'].'</td>';
        $rowsHtml .= '<td class="text-center">'.e($str.' '.$i->lot_name.'/'.$i->no).'</td>';
        $rowsHtml .= '<td>'.e($i->tracking).'</td>';
        $rowsHtml .= '<td class="text-right">'.Format::numberformatzero($i->amount).'</td>';
        $rowsHtml .= '<td class="text-right">'.Format::numberformatone(Php74::num($i->weight) * Php74::num($i->amount)).'</td>';
        $rowsHtml .= '<td class="text-right">'.Format::numberformatone($i->weight).'</td>';
        $rowsHtml .= '<td class="text-right">'.Format::numberformatone($i->height).'</td>';
        $rowsHtml .= '<td class="text-right">'.Format::numberformatone($i->long).'</td>';
        $rowsHtml .= '<td class="text-right">'.Format::cubicformat(P::cubic($i)).'</td>';
        $rowsHtml .= '<td class="text-center">'.e($i->type_name).'</td>';
        if (Php74::eq($senderType, 0)) {
            foreach (['price_qc', 'price_express', 'price_repack', 'price_else', 'price_kg', 'price_cubic'] as $k) {
                $rowsHtml .= '<td class="text-right">'.Format::numberformat($i->{$k}).'</td>';
            }
        }
    }
@endphp
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="A fully featured admin theme which can be used to build CRM, CMS, etc.">
        <meta name="author" content="Coderthemes">

        <link rel="shortcut icon" href="{{ legacy_href() }}assets/images/logo_mini.png">

        <title>{{ config('legacy.project_name') }}</title>

        <link href="{{ legacy_href() }}assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/core.css" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/components.css" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/pages.css" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/responsive.css" rel="stylesheet" type="text/css" />

        <!-- HTML5 Shiv and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
        <![endif]-->

        <script src="{{ legacy_href() }}assets/js/modernizr.min.js"></script>
        <style>
            .content {
                margin-top: 0px!important;
                margin-bottom:  0px!important;
            }
            .content-page {
                margin-left: 0px!important;
            }
            .footer {
                position: relative!important;
                right: auto!important;
                left: auto!important;
            }
        </style>
    </head>


    <body class="fixed-left">
        
        <!-- Begin page -->
        <div id="wrapper">

            <!-- Top Bar Start -->
            
            <!-- Top Bar End -->
            <!-- ========== Left Sidebar Start ========== -->
            <!-- Left Sidebar End --> 



            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->     
            <div class="content-page">
            <!-- Start content -->
            <div class="content">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default" style="padding:5px 10px;"><style>
body {
    font-family: 'Noto Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
}
</style>

@if (Php74::eq($senderType, 0))
<div style="-webkit-transform: rotate(327deg);
    -moz-transform: rotate(327deg);
    -ms-transform: rotate(327deg);
    -o-transform: rotate(327deg);
    transform: rotate(327deg);
    text-align: center;
    position: absolute;
    float: left;
    top: 21%;
    right: 37%;
    font-size: 40px;
    color: #ff0000;
    border: 1px #ff0000 solid;
    padding: 10px 20px;
    ">{{ config('legacy.project_name') }}</div>
<table class="table_send" style="font-size:22px;font-weight: bold">
    <tr>
        <td rowspan="4" style="text-align: right;vertical-align: top">
            <img style="width: 95px;" class="icon-c-logo " src="{{ legacy_href() }}assets/images/fav.png">
        </td>
    </tr>
    <tr>
        <td style="text-align: right">หมายเลขรายการ&nbsp;:&nbsp;</td>
        <td>{{ $prefix }}000</td>
    </tr>
    <tr>
        <td style="text-align: right">รหัสลูกค้า&nbsp;:&nbsp;</td>
        <td>{{ $code }}</td>
    </tr>
    
</table>
<table class="table_send" style="font-size:14px;margin-top:20px;">
    
    <tr >
        <td style="text-align: right;padding-top:10px;">ชื่อผู้รับ&nbsp;:&nbsp;</td>
        <td style="padding-top:10px;">{{ $g('cus_name') }}</td>
    </tr>
    <tr>
        <td style="text-align: right">ที่อยู่ผู้รับ&nbsp;:&nbsp;</td>
        <td>{{ $g('cus_address') }}</td>
    </tr>
    <tr>
        <td style="text-align: right">เบอร์ติดต่อผู้รับ&nbsp;:&nbsp;</td>
        <td>{{ $g('cus_phone') }}</td>
    </tr>

    <tr >
        <td style="text-align: right;padding-top:10px;">ประเภทการส่ง&nbsp;:&nbsp;</td>
        <td style="padding-top:10px;">{{ $shipName }}</td>
    </tr>
    <tr>
        <td style="text-align: right">หมายเหตุ&nbsp;:&nbsp;</td>
        <td>{{ $g('submit_remark') === '' ? '-' : $g('submit_remark') }}</td>
    </tr>
    
</table>
@endif


@if (Php74::eq($senderType, 1))
<div style="-webkit-transform: rotate(327deg);
    -moz-transform: rotate(327deg);
    -ms-transform: rotate(327deg);
    -o-transform: rotate(327deg);
    transform: rotate(327deg);
    text-align: center;
    position: absolute;
    float: left;
    top: 21%;
    right: 41%;
    font-size: 40px;
    color: #ff0000;
    border: 1px #ff0000 solid;
    padding: 10px 20px;
    ">กรอกใหม่</div>
<div style="-webkit-transform: rotate(327deg);
    -moz-transform: rotate(327deg);
    -ms-transform: rotate(327deg);
    -o-transform: rotate(327deg);
    transform: rotate(327deg);
    text-align: center;
    position: absolute;
    float: left;
    top: 37%;
    right: 12%;
    font-size: 30px;
    color: #ff0000;
    border: 1px #ff0000 solid;
    padding: 10px 20px;
    ">มีค่าใช้จ่ายเพิ่มเติม จะถูกส่งให้โดยที่ผู้รับไม่ทราบ<br/>ว่าเป็น {{ config('legacy.project_name') }}ส่งให้</div>
<table class="table_send" style="font-size:30px">
    <tr>
        <td style="text-align: right">หมายเลขรายการ&nbsp;:&nbsp;</td>
        <td>{{ $prefix }}000</td>
    </tr>
    <tr>
        <td style="text-align: right">รหัสลูกค้า&nbsp;:&nbsp;</td>
        <td>{{ $code }}</td>
    </tr>
</table>
<table class="table_send" style="font-size:14px;margin-top:20px;">
    
    <tr>
        <td style="text-align: right">ชื่อผู้รับ&nbsp;:&nbsp;</td>
        <td>{{ $g('cus_name') }}</td>
    </tr>
    <tr>
        <td style="text-align: right">ที่อยู่ผู้รับ&nbsp;:&nbsp;</td>
        <td>{{ $g('cus_address') }}</td>
    </tr>
    <tr>
        <td style="text-align: right">เบอร์ติดต่อผู้รับ&nbsp;:&nbsp;</td>
        <td>{{ $g('cus_phone') }}</td>
    </tr>
</table>
<table class="table_send" style="font-size:14px;margin-top:20px;">
    <tr >
        <td style="text-align: right;padding-top:10px;">ชื่อผู้ส่ง&nbsp;:&nbsp;</td>
        <td style="padding-top:10px;">{{ $g('send_name') }}</td>
    </tr>
    <tr>
        <td style="text-align: right">ที่อยู่ผู้ส่ง&nbsp;:&nbsp;</td>
        <td>{{ $g('send_address') }}</td>
    </tr>
    <tr>
        <td style="text-align: right">เบอร์ติดต่อผู้ส่ง&nbsp;:&nbsp;</td>
        <td>{{ $g('send_phone') }}</td>
    </tr>

    <tr >
        <td style="text-align: right;padding-top:10px;">ประเภทการส่ง&nbsp;:&nbsp;</td>
        <td style="padding-top:10px;">{{ $shipName }}</td>
    </tr>
    <tr>
        <td style="text-align: right">หมายเหตุ&nbsp;:&nbsp;</td>
        <td>{{ $g('submit_remark') === '' ? '-' : $g('submit_remark') }}</td>
    </tr>
    
</table>
@endif


@if (Php74::eq($senderType, 2))
<div style="-webkit-transform: rotate(327deg);
    -moz-transform: rotate(327deg);
    -ms-transform: rotate(327deg);
    -o-transform: rotate(327deg);
    transform: rotate(327deg);
    text-align: center;
    position: absolute;
    float: left;
    top: 21%;
    right: 37%;
    font-size: 40px;
    color: #ff0000;
    border: 1px #ff0000 solid;
    padding: 10px 20px;
    ">ไม่แสดงราคา</div>
<div style="-webkit-transform: rotate(327deg);
    -moz-transform: rotate(327deg);
    -ms-transform: rotate(327deg);
    -o-transform: rotate(327deg);
    transform: rotate(327deg);
    text-align: center;
    position: absolute;
    float: left;
    top: 37%;
    right: 20%;
    font-size: 30px;
    color: #ff0000;
    border: 1px #ff0000 solid;
    padding: 10px 20px;
    ">ไม่แสดงราคา ผู้รับสินค้าจะไม่เห็นราคา</div>
<table class="table_send" style="font-size:30px">
    <tr>
        <td style="text-align: right">หมายเลขรายการ&nbsp;:&nbsp;</td>
        <td>{{ $prefix }}000</td>
    </tr>
    <tr>
        <td style="text-align: right">รหัสลูกค้า&nbsp;:&nbsp;</td>
        <td>{{ $code }}</td>
    </tr>
</table>
<table class="table_send" style="font-size:14px;margin-top:20px;">
    
    <tr >
        <td style="text-align: right;padding-top:10px;">ชื่อผู้รับ&nbsp;:&nbsp;</td>
        <td style="padding-top:10px;">{{ $g('cus_name') }}</td>
    </tr>
    <tr>
        <td style="text-align: right">ที่อยู่ผู้รับ&nbsp;:&nbsp;</td>
        <td>{{ $g('cus_address') }}</td>
    </tr>
    <tr>
        <td style="text-align: right">เบอร์ติดต่อผู้รับ&nbsp;:&nbsp;</td>
        <td>{{ $g('cus_phone') }}</td>
    </tr>

    <tr >
        <td style="text-align: right;padding-top:10px;">ประเภทการส่ง&nbsp;:&nbsp;</td>
        <td style="padding-top:10px;">{{ $shipName }}</td>
    </tr>
    <tr>
        <td style="text-align: right">หมายเหตุ&nbsp;:&nbsp;</td>
        <td>{{ $g('submit_remark') === '' ? '-' : $g('submit_remark') }}</td>
    </tr>
    
</table>
@endif


<table class="lot_table_cus form-group" style="margin-top:10px;">
    <thead>
        <tr>
            <th class="text-center" style="width: 20px;"></th>
            <th class="text-center" style="width: 80px;">{!! res('lot_list').'/'.res('no') !!}</th>
            <th class="text-center">{!! res('tracking') !!}</th>
            <th class="text-center" style="width: 60px;">{!! res('amount') !!}</th>
            <th class="text-center" style="width: 60px;">{!! res('weight') !!}</th>
            <th class="text-center" style="width: 60px;">{!! res('width') !!}</th>
            <th class="text-center" style="width: 60px;">{!! res('height') !!}</th>
            <th class="text-center" style="width: 60px;">{!! res('long') !!}</th>
            <th class="text-center" style="width: 70px;">{!! res('cubic') !!}</th>
            <th class="text-center" style="width: 70px;">{!! res('type') !!}</th>
            @if (Php74::eq($senderType, 0))
            <th class="text-center" style="width: 60px;">{!! res('qc') !!}</th>
            <th class="text-center" style="width: 60px;">{!! res('express') !!}</th>
            <th class="text-center" style="width: 60px;">{!! res('repack') !!}</th>
            <th class="text-center" style="width: 60px;">{!! res('else') !!}</th>
            <th class="text-center" style="width: 80px;">{!! res('price_kg') !!}</th>
            <th class="text-center" style="width: 80px;">{!! res('price_cubic') !!}</th>
            @endif
        </tr>
    </thead>

    <tbody>
        {!! $rowsHtml !!}
    </tbody>
    @if (Php74::eq($senderType, 0))
    <thead>
        <th class="text-center " >{!! Format::numberformatzero($t['count']) !!}</th>
        <th class="text-right " ></th>
        <th class="text-right " ></th>
        <th class="text-right" >{!! Format::numberformatzero($t['amount']) !!}</th>
        <th class="text-right" >{!! Format::numberformatone($t['weight']) !!}</th>
        <th class="text-right " colspan="3"></th>
        <th class="text-right" >{!! Format::cubicformat($t['cubic']) !!}</th>
        <th class="text-right " ></th>
        <th class="text-right" >{!! Format::numberformat($t['qc']) !!}</th>
        <th class="text-right" >{!! Format::numberformat($t['express']) !!}</th>
        <th class="text-right" >{!! Format::numberformat($t['repack']) !!}</th>
        <th class="text-right" >{!! Format::numberformat($t['else']) !!}</th>
        <th class="text-right" >{!! Format::numberformat($t['kg']) !!}</th>
        <th class="text-right" >{!! Format::numberformat($t['pcubic']) !!}</th>
    </thead>
    <thead>
        <th class="text-right " colspan="14" >{!! res('total') !!}</th>
        <th class="text-right" colspan="2">{!! Format::numberformat(Php74::num($t['kg']) + Php74::num($t['pcubic'])) !!}</th>
    </thead>
    <thead>
        <th class="text-right " colspan="14" >{!! res('price_thai_storage') !!}</th>
        <th class="text-right" colspan="2">{!! Format::numberformat(e($g('service'))) !!}</th>
    </thead>
    <thead>
        <th class="text-right " colspan="14" >{!! res('price_thai_shipping') !!}</th>
        <th class="text-right" colspan="2">{!! Format::numberformat(e($g('shipping'))) !!}</th>
    </thead>
    <thead>
        <th class="text-right " colspan="14" >{!! res('total') !!}</th>
        <th class="text-right" colspan="2" style="color:#f00">{!! Format::numberformat(e($g('total'))) !!}</th>
    </thead>
    @else
    <thead>
        <th class="text-center " >{!! Format::numberformatzero($t['count']) !!}</th>
        <th class="text-right " ></th>
        <th class="text-right " ></th>
        <th class="text-right" >{!! Format::numberformatzero($t['amount']) !!}</th>
        <th class="text-right" >{!! Format::numberformatone($t['weight']) !!}</th>
        <th class="text-right " colspan="3"></th>
        <th class="text-right" >{!! Format::cubicformat($t['cubic']) !!}</th>
        <th class="text-right" ></th>
    </thead>
    @endif
</table>


<script>
// window.print();
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