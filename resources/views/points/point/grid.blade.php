{{-- _point/grid.php และ _admin/_point/grid.php เดิม ($admin = มีคอลัมน์รหัสลูกค้า + ลิงก์ link_admin) --}}
@php use App\Support\Legacy\Format; use App\Support\Legacy\Paging; use App\Domain\Points\Grid; @endphp

<div class="row">
    <div class="col-lg-12">
        <div class="card-box">
            <div class="row m-t-10 m-b-10">
                <div class="col-xs-10 col-sm-8 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                        <input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{{ $s['termPhp'] }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
                </div>

                
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th  class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('working_date') !!}</th>
@if($admin)
                            <th class="text-center">{!! res('customer_code') !!}</th>
@endif
                            <th class="text-center" >{!! res('detail') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('amount') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('remain') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
@forelse($items as $item)
@php $exp = explode(' ', (string) $item->create_date); $remain = Grid::remain($item->amount, $item->prev_sum); @endphp
                        <tr>
                            <td>{{ $prefix.$item->id }}</td>
                            <td class="text-center">{!! Format::getthaidate($exp[0], true) !!}<br />{!! Format::gettime(e($exp[1] ?? '')) !!}</td>
@if($admin)
                            <td class="text-center">{{ $item->code }}</td>
@endif
                            <td class="text-left"><a href="{{ legacy_href() }}{{ $item->link }}{{ $item->prefix_ref_id }}">{{ $item->detail }}</a></td>
                            <td class="text-right">
@if(Grid::nonNegative($item->amount))
<span class="text-success">+{!! Format::numberformatpoint($item->amount) !!}</span>
@else
<span class="text-danger">{!! Format::numberformatpoint($item->amount) !!}</span>
@endif
                            </td>
                            <td class="text-right">
@if($remain >= 0)
{!! Format::numberformatpoint($remain) !!}
@else
<span class="text-danger">{!! Format::numberformatpoint($remain) !!}</span>
@endif
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

@include('points.partials.grid_script', ['withStatus' => false, 'saveRemark' => false])
