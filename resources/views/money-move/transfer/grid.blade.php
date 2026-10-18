{{-- _transfer/grid.php / _admin/_transfer/grid.php เดิม --}}
@php
    use App\Domain\MoneyMove\Lookups;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $base = $admin ? 'admin/' : '';
@endphp
@include('money-move.partials.grid_head')

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('working_date') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('approved_date') !!}</th>
@if ($admin)
                            <th class="text-center" style="width: 100px;">{!! res('customer_code') !!}</th>
@endif
                            <th class="text-center">{!! res('detail') !!}</th>
@unless ($admin)
                            <th class="text-center">{!! res('amount') !!}</th>
@endunless
                            <th class="text-center" >{!! res('attachment') !!}</th>
@if ($admin)
                            <th class="text-center">{!! res('amount') !!}</th>
@endif
                            <th class="text-center" style="width: 92px;">{!! res('status') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td><a target="_blank" href="{{ legacy_href() }}{{ $base }}transfer/{{ $item->id }}">{{ $prefix }}{{ $item->id }}</a></td>
                            <td class="text-center">
                                {!! Lookups::gridDate($item->create_date) !!}
                            </td>
                            <td class="text-center">
                                {!! Lookups::gridDate($item->approved_date) !!}
                            </td>
@if ($admin)
                            <td class="text-center">{{ $item->code }}</td>
@endif
                            <td class="text-left">{{ $item->acc_name }}</td>
@unless ($admin)
                            <td class="text-right">{!! Format::numberformat($item->total, true, true, true) !!}</td>
@endunless
                            <td class="text-center">{!! Format::getFancyBox($item->approved_upload === null ? null : e($item->approved_upload)) !!}</td>
@if ($admin)
                            <td class="text-right">{!! Format::numberformat($item->total, true, true, true) !!}</td>
@endif
                            <td class="text-center">{!! Status::getstatus($item->status) !!}</td>
                            <td class="text-center">
                            	<a href="{{ legacy_href() }}{{ $base }}transfer/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                            </td>
                        </tr>
                    @empty
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
                    @endforelse
@include('money-move.partials.grid_foot')
