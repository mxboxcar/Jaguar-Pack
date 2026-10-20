{{-- _admin/_lot/load_lot_detail.php เดิม (SR: ทีละ 10 แถว + script เรียกหน้าถัดไป, หัวตารางอยู่ใน form.php)
     คอลัมน์ header_count ไม่มีในตาราง tracking → เดิม rowspan = 1 เสมอ และไม่เคยแสดงแถวลูก (query ลูกไม่เคยถูกเรียก) --}}
@php
    use App\Domain\Lot\Php74;
    use App\Support\Legacy\Alerts;
    use App\Support\Legacy\Format;
    $n = fn ($v) => Php74::num($v);
    $z = fn ($v) => Format::getZero($v);
    $d = fn ($v) => Format::getthaidate(explode(' ', (string) $v)[0], true, true);
@endphp
@foreach ($rows as $item)
@php
    $cubic = (($n($item->width) * $n($item->height) * $n($item->long)) / 1000000) * $n($item->amount);
    $trk = (string) $item->tracking;
@endphp
@include('lot.partials.row_color', ['item' => $item, 'takeapic' => true])
<td rowspan="1" class="text-center " style="vertical-align:top">
				<div style="float:left;margin-left: 1px;">
					<input type="checkbox" class="cbxitem" value="{{ $item->id }}" />
				</div>
				<div style="float:left;margin-left: 3px;font-size:11px;">
					<a href="#" onclick="onedit('{{ Alerts::js($trk) }}');return false;" class="table-action-btn"><i class="fa  fa-pencil text-primary"></i></a>
					<a href="#" onclick="ondelete({{ $item->id }});return false;" class="table-action-btn"><i class="fa  fa-close text-danger"></i></a>
				</div>
			</td><td rowspan="1" class="text-center" style="vertical-align:top">{{ $item->no }}</td><td style="white-space: nowrap;" class="showdetailbox"><a href="{{ legacy_href() }}admin/order/{{ $item->bill_product_id }}/{{ $trk }}">{{ $trk }}</a>
				
				<i style="float: right;" class="fa fa-plus text-danger" onclick="load_quickfile('{{ $item->id }}')"></i> <div class="img_track" style="float:right" id="{{ $item->id }}">@if (isset($images[(int) $item->id]))@include('lot.admin.img_track', ['list' => $images[(int) $item->id]])@else<!-- -->@endif</div>
				
				<div class="text-right lot_table_in" style="display:none;position:absolute;background-color:#fff">
						<table class="lot_table ">
						<thead>
              <th class="text-center" style="width:200px;">{!! res('order') !!}</th>
							<th class="text-center" style="width:200px;">{!! res('order_number') !!}</th>
							<th class="text-center" style="width:200px;">{!! res('remark') !!}</th>
						</thead>
						<tr>
              				<td class="text-left"><a href="{{ legacy_href() }}admin/order/{{ $item->bill_product_id }}">{{ $orderPrefix.$item->bill_product_id }}</td>
							<td class="text-left">{{ $item->order }}</td>
							<td class="text-left">{!! nl2br(e((string) $item->remark)) !!}</td>
						</tr>
						</table>
					</div>
			</td><td rowspan="1" class="text-center" style="vertical-align:top">{!! $d($item->date_warehouse) !!}</td><td rowspan="1" class="text-center" style="vertical-align:top">{!! $d($item->date_outofchina) !!}</td><td rowspan="1" class="text-center" style="vertical-align:top">{!! $d($item->date_thai) !!}</td><td rowspan="1" class="text-center" style="vertical-align:top">{{ $item->code }}<br/><span class="text-danger">{{ $item->subuser }}</span></td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformatzero($item->amount)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformatone($n($item->weight) * $n($item->amount))) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformatone($item->width)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformatone($item->height)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformatone($item->long)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! Format::cubicformat($cubic) !!}</td><td rowspan="1" class="text-center" style="vertical-align:top">{{ $item->type_name }}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformat($item->price_qc, true, false)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformat($item->price_repack, true, false)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformat($item->price_express, true, false)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformat($item->price_else, true, false)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformat($item->price_craft, true, false)) !!}</td><td class="text-right" rowspan="1" style="vertical-align:top">{!! Format::getBold($z(Format::numberformat($item->price_kg, true, false))) !!}</td><td class="text-right" rowspan="1" style="vertical-align:top">{!! Format::getBold($z(Format::numberformat($item->price_cubic, true, false))) !!}</td></tr>
@endforeach
@if (count($rows) !== 0)
<script>
	LoadDetail({{ $next }});
</script>
@else
<script>
	$("#loadinglot").html("");
	loadendtable();
</script>
@endif
