{{-- SR: _admin/_export/print_receipt.php (พิมพ์ใบเสร็จ) — print_header.php ของ SR + ค่าจาก DB escape (เดิม echo ดิบ) --}}
@include('order.print_header')
@php
    use App\Domain\Reports\Php74;
    use App\Domain\Reports\Receipt;
    use App\Support\Legacy\Format;
    $d = fn (string $k) => $data->{$k} ?? null;
    $exportDate = '';
    if (Php74::neq($d('receipt_date'), '0000-00-00 00:00:00') && Php74::neq($d('receipt_date'), '')) {
        $exportDate = Format::getthaidate(explode(' ', (string) $d('receipt_date'))[0], false, true);
    }
    $v = Receipt::printed($data);
@endphp

<style>
    body, td, th {
        font-size:15px!important;
    }
     td {
        padding:3px;
    }

    .lot_table_cuss, .lot_table_cuss th , .lot_table_cuss td {
        border:1px solid #000;
    }

   .lot_table_cuss th {
        background-color: #e6e6e6;
    }
</style>

<div style="    height: 40px;">
        <div style="text-align: center;float: left; width: 20%;">
            <img src="{{ legacy_href() }}assets/images/logo.png" style="width: 165px;" />
        </div>
        <div style="text-align: center;float: left; width: 79%;">
            <b>บริษัท โกโก-คาร์โก้ จำกัด (สำนักงานใหญ่)</b><br/>
            12 ซอยเพชรเกษม 54 แยก 1 แขวงบางด้วน เขตภาษีเจริญ กรุงเทพมหานคร 10160<br/>
            เลขประจำตัวผู้เสียภาษี 0105560117993<br/>
        </div>
</div>
<br />
        <div class="form-horizontal" role="form" style="clear:both;margin-top:20px;">
            <p style="text-align: center;"><b>ใบเสร็จรับเงิน/ใบกำกับภาษี</b></p>
            <table id="headersss">
                <tbody>
                    <tr>
                        <td style="text-align: right;font-weight: bold;width: 180px;">เลขที่ใบเสร็จ : </td>
                        <td>{{ $d('receipt_id') }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;font-weight: bold">วันที่ : </td>
                        <td>{!! $exportDate !!}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;font-weight: bold">ชื่อ : </td>
                        <td>{{ $d('receipt_name') }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;font-weight: bold;vertical-align: top">ที่อยู่ : </td>
                        <td>{{ $d('receipt_address') }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;font-weight: bold">เลขประจำตัวผู้เสียภาษี : </td>
                        <td>{{ $d('receipt_tax') }}</td>
                    </tr>
                </tbody>
            </table>

        </div>

<br />
    <table style="clear: both;width: 100%;" class="lot_table_cuss form-group">
        <thead>
            <th style="text-align: center;width: 10%;">ลำดับ</th>
            <th style="text-align: center;" colspan="2">รายการ</th>
            <th style="text-align: center;width: 15%;">จำนวนเงิน</th>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;vertical-align: top">1</td>
                <td style="vertical-align: top" colspan="2">ค่าบริการขนส่ง <br/><br/><br/></td>
                @if (Php74::eq($d('receipt_is_vat'), 1))
                <td style="text-align: right;vertical-align: top">
                    {!! Format::numberformat($v['a']) !!}
                </td>
                @else
                <td style="text-align: right;vertical-align: top">{!! Format::numberformat($v['a']) !!}</td>
                @endif
            </tr>
            <tr>
                <td colspan="2" style="width: 50%;text-align: center;">{{ Receipt::thaiBaht($v['b']) }}</td>
                <td style="text-align: right;">
                    รวม<br/>
                    ภาษีมูลค่าเพิ่ม 7%<br/>
                    รวมทั้งสิ้น
                </td>
                <td style="text-align: right;" id="totals">
                    {!! Format::numberformat($v['c']) !!}<br/>
                    {!! Format::numberformat($v['d']) !!}<br/>
                    <b>{!! Format::numberformat($v['e']) !!}</b>
                </td>
            </tr>
        </tbody>

    </table>


<style>
@media print{@page {size: landscape}}
body { font-family: 'Noto Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif; }
h4 { line-height: 33px!important; }
</style>



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
