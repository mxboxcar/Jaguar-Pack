{{-- _admin/_send/index.php เดิม (สิทธิ์ admin_export ตรวจที่ middleware) --}}
@extends('legacy.layout')

@section('content')
@include('messaging.admin.send.table')
@endsection
