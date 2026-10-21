{{-- _admin/_payment/grid.php เดิม --}}
@php
    use App\Domain\Payment\PaymentLookups as P;
    use App\Domain\Payment\Php74;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Paging;
    use App\Support\Legacy\Status;
    $sel = fn ($v) => Php74::eq($status, $v) ? "selected='selected'" : '';
@endphp

<div class="row">
	<div class="col-lg-12">
		<div class="card-box">
			<div class="row m-t-10 m-b-10">
            	<div class="col-sm-6 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                    	<input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{{ $search }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
            	</div>

                <div class="col-xs-2 col-sm-4 col-lg-4">
                    <select name="role" class="form-control " id="status" onchange="GenerateWhere()">
                        <option {!! $sel('-2') !!} value="-2">- {!! res('all') !!} -</option>  
                        <option {!! $sel('-1') !!} value="-1">{!! res('cancel') !!}</option>    
                        <option {!! $sel('0') !!} value="0">{!! res('no_paid') !!}</option>   
                        <option {!! $sel('1') !!} value="1">{!! res('wait_for_approve') !!}</option>   
                        <option {!! $sel('2') !!} value="2">{!! res('paid') !!}</option>   
                    </select>
                </div>
            </div>

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('customer_code') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('working_date') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('approved_date') !!}</th>
                            @if ($viewPrice)
                            <th class="text-center">{!! res('amount') !!}</th>
                            @endif
                            <th class="text-center" style="width: 92px;">{!! res('status') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td><a href="{{ legacy_href() }}admin/payment/{{ $item->id }}">{{ $prefix.$item->id }}</a></td>
                            <td class="text-center">{{ $item->code }}</td>
                            <td class="text-center">
                                {!! P::dateCell($item->submit_date, true) !!}
                            </td>
                            <td class="text-center">
                                {!! P::dateCell($item->approved_date, true) !!}
                            </td>
                            
                            @if ($viewPrice)
                            <td class="text-right">{!! Format::numberformat(Php74::num($item->total) - Php74::num($item->discount)) !!}</td>
                            @endif
                            <td class="text-center">
                                {!! Status::getstatus($item->status) !!}
                                @if (Php74::eq($item->is_vat, 1))<br/><span class='label label-danger'>VAT</span>@endif
                            </td>
                            <td class="text-center">
                            	<a href="{{ legacy_href() }}admin/payment/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
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

@include('payment.partials.grid_script')
