{{-- _payment/grid.php เดิม --}}
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
                        <option {!! $sel('2') !!} value="2">{!! res('wait_for_export') !!}</option>   
                        <option {!! $sel('3') !!} value="3">{!! res('export_complete') !!}</option>  
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:120px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 120px;">{!! res('create_date') !!}</th>
                            <th class="text-center" style="width: 120px;">{!! res('export_date') !!}</th>
                            <!-- <th class="text-center">{!! res('export_track') !!}</th> -->
                            <th class="text-center">{!! res('shipping_type') !!}</th>
                            <th class="text-center">{!! res('sender') !!}</th>
                            <th class="text-center">{!! res('approved_proof') !!}</th>
                            <th class="text-center">{!! res('amount') !!}</th>
                            <th class="text-center">{!! res('export_price') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('status') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td><a href="{{ legacy_href() }}payment/{{ $item->id }}">{{ $prefix.$item->id }}</a></td>
                            <td class="text-left">
                                {!! P::dateCell($item->create_date, false) !!}
                            </td>
                            <td class="text-left">
                                {!! P::dateCell($item->export_date, false) !!}
                            </td>
                            <!-- <td class="text-center">
                                {!! $item->export_tracking === '' || $item->export_tracking === null ? '-<br />-' : e($item->ship_name.'/'.$item->export_tracking) !!}
                            </td> -->
                            <td class="text-center">{{ $item->ship_name }}</td>
                            <td class="text-center">{!! Status::getsender($item->sender_type) !!}</td>
                            <td class="text-center">{!! Format::getFancyBox($item->export_attachment) !!}</td>
                            <td class="text-right">{!! Format::numberformat($item->total) !!}</td>
                            <td class="text-right">{!! Format::numberformat($item->export_price) !!}</td>
                            <td class="text-center">{!! Status::getstatusexport($item->status) !!}</td>
                            <td class="text-center">
                                <a href="{{ legacy_href() }}payment/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
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
