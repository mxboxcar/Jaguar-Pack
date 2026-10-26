{{-- _shop_cashback/grid.php และ _admin/_shop_cashback/grid.php เดิม ($admin = คอลัมน์รหัสลูกค้า, หมายเหตุแอดมิน, dropdown สถานะ) --}}
@php use App\Support\Legacy\Format; use App\Support\Legacy\Paging; use App\Domain\Points\Cashback; $admin = $admin ?? false; $base = $admin ? 'admin/' : ''; @endphp

<div class="row">
	<div class="col-xs-12">
		<div class="card-box">
			<div class="row m-t-10 m-b-10">
            	<div class="col-sm-6 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                    	<input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{{ $s['termPhp'] }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
            	</div>
@if($admin)

                <div class="col-xs-2 col-sm-4 col-lg-4">
@include('points.partials.status_select')
                </div>
@endif
            </div>

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
@if($admin)

                            <th class="text-center">{!! res('working_date') !!}</th>
                            <th class="text-center">{!! res('customer_code') !!}</th>
                            <th class="text-center" >{!! res('admin_remark') !!}</th>
@else
                            <th class="text-center" style="width: 120px;">{!! res('working_date') !!}</th>
                            <th class="text-center" >{!! res('remark_for_this_order') !!}</th>
@endif
                            <th class="text-center" style="width: 100px;">{!! res('use_cash_cn_pay') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('use_cash_cn_short') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
@forelse($items as $item)
@php $exp = explode(' ', (string) $item->create_date); @endphp
                        <tr>
@if($admin)

@endif
                            <td><a target="_blank" href="{{ legacy_href() }}{{ $base }}shop_cashback/{{ $item->id }}">
                                {{ $prefix.$item->id }}</a></td>
                            <td class="text-center">{!! Format::getthaidate($exp[0], true) !!}<br />{!! Format::gettime(e($exp[1] ?? '')) !!}</td>
@if($admin)
                            <td class="text-center">{{ $item->code }}</td>
@endif
                            <td class="text-left">
                                <input onblur="on_saveremark($(this))" onkeydown="javascript: if(event.keyCode == 13) on_saveremark($(this));" class="editable" type="text" value="{{ $admin ? $item->remark_admin : $item->remark }}" ref="{{ $item->id }}">
                            </td>
                            <td class="text-right">
                                {!! Cashback::detailHtml($details->get($item->id)) !!}
                            </td>
                            <td class="text-right">{!! Format::numberformat($item->total, true, true, true) !!}</td>
                            <td class="text-center">
                            	<a href="{{ legacy_href() }}{{ $base }}shop_cashback/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                            </td>
                        </tr>
@empty
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
@endforelse
                    </tbody>
                </table>
                <div class="pull-right">
                    {!! Paging::getpaging($page, $total, 10, $ajaxpage) !!}
                </div>
            </div>
		</div>
        
    </div> <!-- end col -->
</div>

@include('points.partials.grid_script', ['withStatus' => $admin, 'saveRemark' => true])
