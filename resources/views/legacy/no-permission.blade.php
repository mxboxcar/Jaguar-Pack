{{-- getPermissionAlert_Page($module) เดิม: เปิด layout แล้วเด้ง ErrorBox → dashboard --}}
@extends('legacy.layout')

@section('content')
{!! \App\Support\Legacy\Alerts::permissionPage($module) !!}
@endsection
