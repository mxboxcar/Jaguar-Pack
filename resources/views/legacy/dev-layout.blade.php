{{-- หน้าตัวอย่าง (dev เท่านั้น): layout เดิม + เนื้อหาตัวอย่าง ไว้เทียบหน้าตากับ index.php เดิม --}}
@extends('legacy.layout')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <h4 class="page-title">{!! res('dashboard') !!}</h4>
    </div>
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="card-box">
            <p>Laravel foundation layout — user #{{ $user?->id }} ({{ $user?->username }}), lang {{ $lang }}, page {{ $page }}</p>
            <p>{!! \App\Support\Legacy\Format::numberformat('1234.567') !!} · {!! \App\Support\Legacy\Format::getthaidate('2026-10-17') !!} · {!! \App\Support\Legacy\Status::getstatus(2) !!}</p>
        </div>
    </div>
</div>
@endsection
