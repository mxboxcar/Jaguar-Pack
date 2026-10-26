{{-- _refill/grid.php / _admin/_refill/grid.php เดิม --}}
@php
    use App\Domain\MoneyMove\Lookups;
    use App\Support\Legacy\Alerts;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Legacy;
    use App\Support\Legacy\Status;
    $base = $admin ? 'admin/' : '';
@endphp
@include('money-move.partials.grid_head')

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
@if ($admin)
                            <th class="text-center" style="width: 100px;">{!! res('customer_code') !!}</th>
@endif
                            <th class="text-center" style="width: 93px;">{!! res('working_date') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('approved_date') !!}</th>
                            <th class="text-center"{{ $admin ? ' ' : '' }}>{!! res('amount') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('attachment') !!}</th>
                            <th class="text-center" style="width: 92px;">{!! res('status') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td><a target="_blank" href="{{ legacy_href() }}{{ $base }}refill/{{ $item->id }}">{{ $prefix }}{{ $item->id }}</a></td>
@if ($admin)
                            <td class="text-center">{{ $item->code }}</td>
@endif
                            <td class="text-center">
                                {!! Lookups::gridDate($admin ? $item->create_date : $item->date) !!}
                            </td>
                            <td class="text-center">
                                {!! Lookups::gridDate($item->approved_date) !!}
                            </td>
                            
                            <td class="text-right">{!! Format::numberformat($item->amount) !!}</td>
                            <td class="text-center" >
@if ((string) $item->attachment === '')
                                -
@elseif ($admin)
@php $fname = pathinfo($item->attachment, PATHINFO_FILENAME); $u = Legacy::hrefBlob($item->attachment); @endphp
                                <a class="fancybox" href="{{ $u }}">
                                                <img id="{{ $fname }}" src="{{ $u }}" style="max-width: 50px;max-height: 33px;">
                                            </a>
                                            <script>
                                                var d = new Date();
                                                $.ajax({
                                                    type: "POST", 
                                                    async: true, 
                                                    data : "path=../upload/temp/{!! Alerts::js(e($item->attachment)) !!}", 
                                                    url: rooturl + "_api/blockblob_ajax", 
                                                    success: function(data) { 
                                                        $("#{!! Alerts::js(e($fname)) !!}").attr("src", "{!! Alerts::js(e($u)) !!}");
                                                    } })
                                            </script>
@else
                                <a class="fancybox" href="{{ Legacy::hrefBlob($item->attachment) }}">
                                                <img src="{{ Legacy::hrefBlob($item->attachment) }}" style="max-width: 50px;max-height: 33px;">
                                            </a>
@endif
                            </td>
                            <td class="text-center">{!! Status::getstatus($item->status) !!}</td>
                            <td class="text-center">
                            	<a href="{{ legacy_href() }}{{ $base }}refill/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                            </td>
                        </tr>
                    @empty
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
                    @endforelse
@include('money-move.partials.grid_foot')
