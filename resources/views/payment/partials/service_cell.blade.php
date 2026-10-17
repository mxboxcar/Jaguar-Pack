@php
    use App\Domain\Payment\PaymentLookups as P;
    use App\Support\Legacy\Format;
@endphp
{!! Format::numberformat(P::add($item, ['price_qc', 'price_repack', 'price_express', 'price_else', 'price_craft']), false, false) !!}
				<div class="text-right lot_table_in" style="display:none;position:absolute;background-color:#fff">
						<table class="lot_table ">
						<thead>
							<th class="text-center" style="width:50px;">{!! res('qc') !!}</th>
							<th class="text-center" style="width:50px;">{!! res('repack') !!}</th>
							<th class="text-center" style="width:50px;">{!! res('express') !!}</th>
							<th class="text-center" style="width:50px;">{!! res('else') !!}</th>
							<th class="text-center" style="width:50px;">{!! res('price_craft') !!}</th>
						</thead>
						<tr>
							<td class="text-right">{!! Format::numberformat($item->price_qc, true, false) !!}</td>
							<td class="text-right">{!! Format::numberformat($item->price_repack, true, false) !!}</td>
							<td class="text-right">{!! Format::numberformat($item->price_express, true, false) !!}</td>
							<td class="text-right">{!! Format::numberformat($item->price_else, true, false) !!}</td>
							<td class="text-right">{!! Format::numberformat($item->price_craft, true, false) !!}</td>
						</tr>
						</table>
					</div>
