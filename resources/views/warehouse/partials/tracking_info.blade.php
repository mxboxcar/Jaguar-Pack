{{-- _admin/_goodsconfirm/load_tracking_by_id.php / _goodsconfirm/load_tracking_by_id.php เดิม (ต่างกันแค่ class ของ label) --}}
@php
    use App\Domain\Warehouse\Php74;
    use App\Support\Legacy\Format;
    $dateOf = function (?string $v) {
        if ($v !== null && $v !== '0000-00-00 00:00:00' && $v !== '') {
            $t = Format::getthaidate(explode(' ', $v)[0]);
            if ($t !== '') {
                return $t;
            }
        }

        return '-';
    };
@endphp

<div class="form-group">
    <label class="{{ $lotLabel }} control-label"> {!! res('lot_list') !!}/{!! res('no') !!}</label>
    <div class="col-md-8 form-control-static">
@if(Php74::eq($d['lot_id'] ?? null, '0'))
-
@else
    	<a target="_blank" href="{{ legacy_href() }}admin/lot/{{ $d['lot_id'] ?? '' }}">{{ $d['lot_name'] ?? '' }}</a>/{{ $d['no'] ?? '' }}
@endif
    </div>
</div>


<div class="form-group">
    <label class="{{ $dateLabel }} control-label"> {!! res('date_in') !!}</label>
    <div class="col-xs-6 form-control-static">
    	{!! $dateOf($d['date_warehouse'] ?? null) !!}
    </div>
</div>
<div class="form-group">
    <label class="{{ $dateLabel }} control-label"> {!! res('date_outofchina') !!}</label>
    <div class="col-xs-6 form-control-static">
    	{!! $dateOf($d['date_outofchina'] ?? null) !!}
    </div>
</div>
<div class="form-group">
    <label class="{{ $dateLabel }} control-label"> {!! res('date_arrive') !!}</label>
    <div class="col-xs-6 form-control-static">
    {!! $dateOf($d['date_thai'] ?? null) !!}
    </div>
</div>
