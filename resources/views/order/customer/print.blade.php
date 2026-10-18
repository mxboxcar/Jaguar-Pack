{{-- _order/print.php เดิม (ต่อท้าย print_header) --}}
@php
    use App\Domain\Order\Html;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
@endphp


<style>

.external_table td {
    padding: 2px 5px 2px 5px;
    border: none!important;
}

</style>

<h4 class="m-t-0 header-title"><b>ใบรายการสั่งซื้อ - </b></h4>
<table class="external_table" style="margin:10px;">
	<tr>
		<td>เลขที่ใบรายการสั่งซื้อ : </td>
		<td></td>
	</tr>
	<tr>
		<td>Reference : </td>
		<td>{{ $prefix }}{{ $data['id'] }}</td>
	</tr>
	
	<tr>
		<td>{!! res('customer_code') !!} : </td>
		<td>{!! Html::h($data['code']) !!}</td>
	</tr>
	<tr>
		<td>{!! res('shipping_type') !!} : </td>
		<td>{!! Status::getShipType($data['ship']) !!}</td>
	</tr>
	<tr>
		<td>{!! res('status') !!} : </td>
		<td>{!! Status::getstatusorder($data['status']) !!}</td>
	</tr>
	<tr>
		<td>{!! res('this_order_rate') !!} : </td>
		<td>{!! res('cn_currency') !!}1 <i class='fa fa-arrows-h'></i> {!! Format::numberformat($data['exchange_rate']) !!}</td>
	</tr>
	<tr>
		<td>{!! res('remark_for_this_order') !!} : </td>
		<td>{!! Html::h($data['quote']) !!}</td>
	</tr>
	<tr>
		<td>{!! res('admin_remark') !!} : </td>
		<td>{!! Html::h($data['admin_quote']) !!}</td>
	</tr>
	<tr>
		<td>{!! res('edit_last') !!} : </td>
		<td>{!! Html::h($editLast) !!}</td>
	</tr>
</table>





{!! $detailHtml !!}

<script>
	$(".label-success").removeClass('label').removeClass('label-success');
	$(".label-inverse").removeClass('label').removeClass('label-inverse');
	window.print();
</script>
