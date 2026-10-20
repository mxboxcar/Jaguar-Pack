{{-- SR: _admin/_export/receipt_1.php ($type 1 = บวก VAT 7%) / receipt_2.php ($type 2 = รวม VAT แล้ว) — ตารางในฟอร์มใบเสร็จ --}}
@php
    use App\Domain\Reports\Receipt;
    use App\Support\Legacy\Format;
@endphp
<table style="width: 100%;">
	<thead>
		<th style="text-align: center;width: 15%;">ลำดับ</th>
		<th style="text-align: center;" colspan="2">รายการ</th>
		<th style="text-align: center;width: 15%;">จำนวนเงิน</th>
	</thead>
	<tbody>
		<tr>
			<td style="text-align: center;vertical-align: top">1</td>
			<td style="vertical-align: top" colspan="2">ค่าบริการขนส่ง <br/><br/><br/><br/><br/><br/><br/></td>
			<td style="text-align: right;vertical-align: top">
                <input type="text" name="receipt_total" oninput="calvat()" style="text-align: right;" id="receipt_total" value="{{ $v['input'] }}" />
            </td>
		</tr>
		<tr>
			<td colspan="2" style="width: 35%;text-align: center;" id="v_th">{{ Receipt::thaiBaht($v['e']) }}</td>
			<td style="text-align: right;">
				รวม<br/>
				ภาษีมูลค่าเพิ่ม 7%<br/>
				รวมทั้งสิ้น
			</td>
			<td style="text-align: right;" id="totals">
				<span class="currency" id="v_c">{!! Format::numberformat($v['c']) !!}</span><br/>
				<span class="currency" id="v_d">{!! Format::numberformat($v['d']) !!}</span><br/>
				<b><span class="currency" id="v_e">{!! Format::numberformat($v['e']) !!}</span></b>
			</td>
		</tr>
	</tbody>

</table>

<script>
    function calvat() {
        $.ajax({
            type: 'POST',
            async: true,
            data : { total : $("#receipt_total").val(), type : {{ $type }} } ,
            url: rooturl + '_admin/_export/calvat',
            success: function(data) {
                data = $.parseJSON(data);
                $("#v_c").text((data.c) + " ฿");
                $("#v_d").text((data.d)+ " ฿");
                $("#v_e").text((data.e)+ " ฿");
                $("#v_th").text((data.th));

                makeCurrency();
            }
        });


    }
</script>
