{{-- SR: _admin/_report/printtax.php (รายงานภาษีขายประจำเดือน) — print_header.php ของ SR + ค่าจาก DB escape (เดิม echo ดิบ) --}}
@include('order.print_header')
@php
    use App\Domain\Reports\Php74;
    use App\Domain\Reports\Receipt;
    use App\Support\Legacy\Format;
@endphp

<style>
    body, td, th {
        font-size:15px!important;
    }
    th, td {
        padding:3px;
    }

    .lot_table_cuss, .lot_table_cuss th  {
        border:1px solid #000;
    }

    .lot_table_cuss td {
        border:1px solid #000;
        border-top:none;
        border-bottom: none;
    }

   .lot_table_cuss th {
        background-color: #e6e6e6;
    }
</style>
<h2 style="text-align: center;"><b>รายงานภาษีขาย</b></h2>
<p style="text-align: center;">เดือนภาษี {{ $monthName.' '.Php74::str(Php74::num($year) + 543) }}</p>

<table style="width: 100%;">
    <tr>
        <td style="width: 150px">ชื่อผู้ประกอบการ</td>
        <td>บริษัท โกโก-คาร์โก้ จำกัด</td>
        <td>เลขประจำตัวผู้เสียภาษี  0105560117993</td>
    </tr>
    <tr>
        <td style="width: 150px">ชื่อสถานประกอบการ</td>
        <td>บริษัท โกโก-คาร์โก้ จำกัด</td>
        <td>
            <div style="float: left;"><div style="border: 1px #000 solid;width: 20px;height: 20px;float: left;text-align: center;margin-right:  10px;">✔</div>สำนักงานใหญ่</div>
            <div style="float: left;margin-left: 40px;"><div style="border: 1px #000 solid;width: 20px;height: 20px;float: left;margin-right: 10px;"></div>สาขาที่</div>
        </td>
    </tr>
    <tr>
        <td style="width: 150px">ที่อยู่</td>
        <td colspan="2">12 ซอยเพชรเกษม 54 แยก 1 แขวงบางด้วน เขตภาษีเจริญ กรุงเทพมหานคร 10160</td>
    </tr>
</table>


    <table style="clear: both;width: 100%;" class="lot_table_cuss form-group">
        <thead>
            <th style="text-align: center;width: 30px;">ลำดับ</th>
            <th style="text-align: center;width: 120px;">วันที่</th>
            <th style="text-align: center;width: 120px;">เลขที่ใบกำกับ</th>
            <th style="text-align: center;">ชื่อผู้ซื้อสินค้า/ผู้รับบริการ</th>
            <th style="text-align: center;width: 150px;">มูลค่าสินค้า/บริการ</th>
            <th style="text-align: center;width: 150px;">จำนวนเงินภาษี</th>
        </thead>
        <tbody>
            @php
                $index = 0;
                $totalD = 0;
                $totalE = 0;
            @endphp
            @foreach ($rows as $row)
            @php
                $index++;
                $date = explode('-', explode(' ', (string) $row->receipt_date)[0]);
                $t = Receipt::taxRow($row);
                $totalD = $totalD + Php74::num($t['d']);
                $totalE = $totalE + Php74::num($t['e']);
            @endphp
            <tr>
                <td style="text-align: center;vertical-align: top">{{ $index }}</td>
                <td style="text-align: center;vertical-align: top">{{ ($date[2] ?? '').'/'.($date[1] ?? '').'/'.Php74::str(Php74::num($date[0]) + 543) }}</td>
                <td style="text-align: center;vertical-align: top">{{ $row->receipt_id }}</td>
                <td style="text-align: left;vertical-align: top">{{ $row->receipt_name }}</td>
                <td style="text-align: right;vertical-align: top">{!! Format::numberformat($t['e']) !!}</td>
                <td style="text-align: right;vertical-align: top">{!! Format::numberformat($t['d']) !!}</td>

            </tr>
            @endforeach
        </tbody>

        <thead>
            <th style="text-align: right;" colspan="4">รวมทั้งสิ้น</th>
            <th style="text-align: right;">{!! Format::numberformat($totalE) !!}</th>
            <th style="text-align: right;">{!! Format::numberformat($totalD) !!}</th>
        </thead>

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
