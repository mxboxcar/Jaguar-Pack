{{-- _admin/_withdrawcash/grid.php เดิม --}}
@php
    use App\Domain\MoneyMove\Lookups;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
@endphp
@include('money-move.partials.grid_head')

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('customer_code') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('working_date') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('approved_date') !!}</th>
                            <th class="text-center">{!! res('approved_proof') !!}</th>
                            <th class="text-center" >{!! res('amount') !!}</th>
                            <th class="text-center" style="width: 92px;">{!! res('status') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td><a target="_blank" href="{{ legacy_href() }}admin/withdrawcash/{{ $item->id }}">{{ $prefix }}{{ $item->id }}</a></td>
                            <td class="text-center">{{ $item->code }}</td>
                            <td class="text-center">
                                {!! Lookups::gridDate($item->date) !!}
                            </td>
                            <td class="text-center">
                                {!! Lookups::gridDate($item->approved_date) !!}
                            </td>
                            <td class="text-center">{!! Format::getFancyBox($item->approved_upload === null ? null : e($item->approved_upload)) !!}</td>
                            <td class="text-right">{!! Format::numberformat($item->amount) !!}</td>
                            
                            <td class="text-center">{!! Status::getstatus($item->status) !!}</td>
                            <td class="text-center">
                                <a href="{{ legacy_href() }}admin/withdrawcash/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                            </td>
                        </tr>
                    @empty
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
                    @endforelse
@include('money-move.partials.grid_foot')
