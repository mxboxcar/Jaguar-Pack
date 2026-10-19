{{-- _admin/_lot/load_lot_detail.php เดิม
     คอลัมน์ header_count ไม่มีในตาราง tracking → เดิม rowspan = 1 เสมอ และไม่เคยแสดงแถวลูก (query ลูกไม่เคยถูกเรียก) --}}
@php
    use App\Domain\Lot\Php74;
    use App\Support\Legacy\Alerts;
    use App\Support\Legacy\Format;
    $n = fn ($v) => Php74::num($v);
    $s = ['amount' => null, 'weight' => null, 'cubic' => null, 'kg' => null, 'pcubic' => null, 'width' => null, 'height' => null, 'long' => null, 'qc' => null, 'repack' => null, 'express' => null, 'else' => null, 'craft' => null];
    $add = function (string $k, $v) use (&$s, $n) { $s[$k] = $n($s[$k]) + $n($v); };
    $z = fn ($v) => Format::getZero($v);
    $d = fn ($v) => Format::getthaidate(explode(' ', (string) $v)[0], true, true);
@endphp
<div class="col-xs-7" style="clear:both">
	<div class="form-group">
	    <label class="col-xs-4 control-label"> {!! res('customer_code') !!}/{!! res('tracking') !!}</label>
	    <div class="col-xs-6 ">
	    	<input type="text" class="form-control" value="{{ $search }}" id="searching_lot_detail">
	    </div>
	    <div class="col-xs-2 ">
	    	<button type="button" class="btn btn-primary" onclick="LoadDetail()">{!! res('search') !!}</button>
	    </div>
	</div>
</div>

<table class="lot_table" style="margin-top:20px;clear:both;float: left;">
    <thead>
        <tr>
            <th class="text-center " style="width:60px;"> 
            	<div class="text-center">
            		<input type="checkbox" class="cbxitem_head" onclick="CheckAll()" />
            	</div>
            </th>
            <th class="text-center" style="width: 30px;">{!! res('no') !!}</th>
            <th class="text-center">{!! res('tracking') !!}</th>
            <th class="text-center" style="width: 73px;">{!! res('date_warehouse') !!}</th>
            <th class="text-center" style="width: 73px;">{!! res('date_outofchina') !!}</th>
            <th class="text-center" style="width: 73px;">{!! res('date_thai') !!}</th>
            <th class="text-center" style="width: 70px;">{!! res('customer_code') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('amount') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('weight') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('width') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('height') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('long') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('cubic') !!}</th>
            <th class="text-center" style="width: 70px;">{!! res('type') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('qc') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('repack') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('express') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('else') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('price_craft') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('price_kg') !!}</th>
            <th class="text-center" style="width: 50px;">{!! res('price_cubic') !!}</th>
        </tr>
        <tr>
            <th colspan="7"></th>
            <th class="sum_amount text-right red"></th>
            <th class="sum_weight text-right red"></th>
            <th class="sum_width text-right red"></th>
            <th class="sum_height text-right red"></th>
            <th class="sum_long text-right red"></th>
            <th class="sum_cubic text-right red"></th>
            <th></th>
            <th class="sum_qc text-right red"></th>
            <th class="sum_repack text-right red"></th>
            <th class="sum_express text-right red"></th>
            <th class="sum_else text-right red"></th>
            <th class="sum_craft text-right red"></th>
            <th class="sum_kg text-right red"></th>
            <th class="sum_pcubic text-right red"></th>
        </tr>
    </thead>

    <tbody>
@forelse ($rows as $item)
@php
    $cubic = (($n($item->width) * $n($item->height) * $n($item->long)) / 1000000) * $n($item->amount);
    if (Php74::eq($item->header, 0)) {
        $add('amount', $item->amount);
        $add('weight', $n($item->weight) * $n($item->amount));
        $add('cubic', $cubic);
        $add('kg', $item->price_kg);
        $add('pcubic', $item->price_cubic);
        $add('width', $item->width);
        $add('height', $item->height);
        $add('long', $item->long);
        $add('qc', $item->price_qc);
        $add('repack', $item->price_repack);
        $add('express', $item->price_express);
        $add('else', $item->price_else);
        $add('craft', $item->price_craft);
    }
    $trk = (string) $item->tracking;
@endphp
@include('lot.partials.row_color', ['item' => $item])
<td rowspan="1" class="text-center " style="vertical-align:top">
				<div style="float:left;margin-left: 1px;">
					<input type="checkbox" class="cbxitem" value="{{ $item->id }}" />
				</div>
				<div style="float:left;margin-left: 3px;font-size:11px;">
					<a href="#" onclick="onedit('{{ Alerts::js($trk) }}');return false;" class="table-action-btn"><i class="fa  fa-pencil text-primary"></i></a>
					<a href="#" onclick="ondelete({{ $item->id }});return false;" class="table-action-btn"><i class="fa  fa-close text-danger"></i></a>
				</div>
			</td><td rowspan="1" class="text-center" style="vertical-align:top">{{ $item->no }}</td><td style="white-space: nowrap;{{ Php74::eq($item->is_checked, 0) ? '' : 'background-color:#eaeaea' }}" class="showdetailbox"><a href="{{ legacy_href() }}admin/order/{{ $item->bill_product_id }}/{{ $trk }}">{{ $trk }}</a>
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
			</td><td rowspan="1" class="text-center" style="vertical-align:top">{!! $d($item->date_warehouse) !!}</td><td rowspan="1" class="text-center" style="vertical-align:top">{!! $d($item->date_outofchina) !!}</td><td rowspan="1" class="text-center" style="vertical-align:top">{!! $d($item->date_thai) !!}</td><td rowspan="1" class="text-center" style="vertical-align:top">{{ $item->code }}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformatzero($item->amount)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformatone($n($item->weight) * $n($item->amount))) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformatone($item->width)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformatone($item->height)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformatone($item->long)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! Format::cubicformat($cubic) !!}</td><td rowspan="1" class="text-center" style="vertical-align:top">{{ $item->type_name }}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformat($item->price_qc, true, false)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformat($item->price_repack, true, false)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformat($item->price_express, true, false)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformat($item->price_else, true, false)) !!}</td><td rowspan="1" class="text-right" style="vertical-align:top">{!! $z(Format::numberformat($item->price_craft, true, false)) !!}</td><td class="text-right" rowspan="1" style="vertical-align:top">{!! Format::getBold($z(Format::numberformat($item->price_kg, true, false))) !!}</td><td class="text-right" rowspan="1" style="vertical-align:top">{!! Format::getBold($z(Format::numberformat($item->price_cubic, true, false))) !!}</td></tr>
@empty
<tr><td style="height: 100px;" colspan="14" class="text-center">{!! res('no_data') !!}</td></tr>
@endforelse
<input type="hidden" class="hid_sum_amount" value="{!! Format::numberformatzero($s['amount'], false) !!}" /><input type="hidden" class="hid_sum_weight" value="{!! Format::numberformatone($s['weight'], false) !!}" /><input type="hidden" class="hid_sum_cubic" value="{!! Format::cubicformat($s['cubic'], false) !!}" /><input type="hidden" class="hid_sum_kg" value="{!! Format::numberformat($s['kg'], false, false) !!}" /><input type="hidden" class="hid_sum_pcubic" value="{!! Format::numberformat($s['pcubic'], false, false) !!}" /><input type="hidden" class="hid_sum_width" value="{!! Format::numberformatone($s['width'], false) !!}" /><input type="hidden" class="hid_sum_height" value="{!! Format::numberformatone($s['height'], false) !!}" /><input type="hidden" class="hid_sum_long" value="{!! Format::numberformatone($s['long'], false) !!}" /><input type="hidden" class="hid_sum_qc" value="{!! Format::numberformat($s['qc'], false, false) !!}" /><input type="hidden" class="hid_sum_repack" value="{!! Format::numberformat($s['repack'], false, false) !!}" /><input type="hidden" class="hid_sum_express" value="{!! Format::numberformat($s['express'], false, false) !!}" /><input type="hidden" class="hid_sum_else" value="{!! Format::numberformat($s['else'], false, false) !!}" /><input type="hidden" class="hid_sum_craft" value="{!! Format::numberformat($s['craft'], false, false) !!}" />
	</tbody>
	<thead>
		<tr>
            <th colspan="7"></th>
            <th class="sum_amount text-right red"></th>
            <th class="sum_weight text-right red"></th>
            <th class="sum_width text-right red"></th>
            <th class="sum_height text-right red"></th>
            <th class="sum_long text-right red"></th>
            <th class="sum_cubic text-right red"></th>
            <th></th>
            <th class="sum_qc text-right red"></th>
            <th class="sum_repack text-right red"></th>
            <th class="sum_express text-right red"></th>
            <th class="sum_else text-right red"></th>
            <th class="sum_craft text-right red"></th>
            <th class="sum_kg text-right red"></th>
            <th class="sum_pcubic text-right red"></th>
        </tr>
	</thead>
</table>
