@php $__d = (string) ($d ?? ''); $__exp = explode(' ', $__d); @endphp
@if($__d === '0000-00-00 00:00:00' || $__d === '')
-<br />-
@else
{!! App\Support\Legacy\Format::getthaidate($__exp[0], true) !!}<br />{!! App\Support\Legacy\Format::gettime(e($__exp[1] ?? '')) !!}
@endif
