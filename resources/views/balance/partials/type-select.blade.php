{{-- dropdown ประเภทรายการของ grid เดิม ($txtSearch[1] เทียบแบบ PHP 7.4) --}}
                    <select name="status" class="{{ $selectClass }}" id="status" onchange="GenerateWhere()">
                        <option @if(\App\Domain\Balance\Php74::eq($s['typePhp'], '-2') && $s['typePhp'] !== null)selected='selected'@endif value="-2">- {!! res('all') !!} -</option>
@foreach($types as $row)
@if($s['typePhp'] !== null && \App\Domain\Balance\Php74::eq($s['typePhp'], (string) $row->id))
<option selected="selected" value="{{ $row->id }}">{{ $row->name }}</option>
@else
<option value="{{ $row->id }}">{{ $row->name }}</option>
@endif
@endforeach

                    </select>
