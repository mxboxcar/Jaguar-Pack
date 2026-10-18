{{-- _linenotify/index.php เดิม (สิทธิ์ profile ตรวจที่ middleware) --}}
@extends('legacy.layout')

@section('content')
<style>
  .sweet-alert {
    background-color: white;
    font-family: 'Open Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
     width: auto!important; 
    padding: 17px;
     border-radius: 5px; 
    text-align: center;
     position: relative!important; 
     left: auto!important; 
     top: auto!important; 
     margin-left: auto!important; 
     margin-top: auto!important; 
     overflow: hidden; 
     display: block; 
     z-index: 99999; 
}

</style>



<div class="col-sm-7">
	<h4 class="page-title">เชื่อมต่อ LINE NOTIFY เพื่อรับการแจ้งเตือน</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
	</ol>
</div>




<div class="row text-center">
  @if($connected)
  <div class="sweet-alert ">
    <div class="sa-icon sa-success animate" style="display: block;">
      <span class="sa-line sa-tip animateSuccessTip"></span>
      <span class="sa-line sa-long animateSuccessLong"></span>

      <div class="sa-placeholder"></div>
      <div class="sa-fix"></div>
    </div>
  </div>
    <h2>เชื่อมต่อแล้ว!</h2>
    <a href="{{ legacy_href() }}_linenotify/revoke"  type="button" class="btn btn-danger waves-effect waves-light"><span class="btn-label"><i class="fa fa-arrow-right"></i></span>ยกเลิกเชื่อมต่อ Line Notify</a>
  @else
    <h2>ลูกค้าสามารถเข้าถึงการแจ้งเตือนผ่านแอปพลิเคชั่น Line โดยคลิกเชื่อต่อ Line Notify</h2>
    <a href="{{ $url }}"  type="button" class="btn btn-success waves-effect waves-light"><span class="btn-label"><i class="fa fa-arrow-left"></i></span>คลิกเพื่อเชื่อมต่อ Line Notify</a>
  @endif
</div>


@endsection
