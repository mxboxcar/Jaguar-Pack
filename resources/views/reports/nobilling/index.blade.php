{{-- _admin/_nobilling/index.php เดิม (?page=admin_nobilling) — เดิมไม่มีสิทธิ์ก็ยังแสดงข้อมูลต่อ (ไม่ exit) → ตรวจสิทธิ์ที่ route
     token ของ _api/sendmail_close_track เดิมฝังในโค้ด (และไฟล์ปลายทางไม่มีในเว็บเดิม = 404) → อ่านจาก config ถ้ามี ไม่งั้นว่าง --}}
@extends('legacy.layout')

@php
    use App\Support\Legacy\Format;
    use App\Domain\Reports\Php74;
@endphp

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('no_billing') !!}</h4>
	<ol class="breadcrumb">
		<li >
			<a href="#">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			<a href="#">{!! res('no_billing') !!}</a>
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">
	    <button type="button" onclick="sendemail()" class="pull-right btn btn-danger waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-email"></i></span>
		    ส่งอีเมล์เตือนลูกค้า
	    </button>
	</div>
</div>


<div class="col-sm-12">
	<div class="card-box"    >
		<div id="result">
		@if (count($groups))@foreach ($groups as $code => $rows)
		        <div class="row">
		            <h3>{{ $code }}</h3>
		            <table border="1" class="sortable table table-bordered boxcar_grid">
		                <thead>
		                    <tr>
		                        <th style="width:100px;"  class="text-center">{!! res('lot_list') !!}</th>
		                        <th style="width:100px;"  class="text-center">{!! res('no') !!}</th>
		                        <th class="text-center">{!! res('tracking') !!}</th>
		                        <th class="text-center" style="width:120px;">{!! res('date_thai') !!}</th>
		                        <th class="text-center" style="width:100px;">{!! res('weight') !!}</th>
		                        <th class="text-center" style="width:100px;">{!! res('cubic') !!}</th>
		                    </tr>
		                </thead><tbody>@foreach ($rows as $row)
		                
		                    <tr>
		                        <td class="text-center">{{ $row->lot_no }}</td>
		                        <td class="text-center">{{ $row->no }}</td>
		                        <td>{{ $row->tracking }}</td>
		                        <td class="text-center">{!! Format::getthaidate(explode(' ', (string) $row->date_thai)[0]) !!}</td>
		                        <td class="text-center">{!! Format::numberformatzero($row->weight) !!}</td>
		                        <td class="text-center">{!! Format::cubicformat((Php74::num($row->width) * Php74::num($row->height) * Php74::num($row->long)) / 1000000) !!}</td>
		                    </tr>
		                
		                @endforeach</tbody></table></div>@endforeach
		@else<h3 class='text-center'>{!! res('no_data') !!}</h3>@endif
	</div>
</div>

<script>
function sendemail() {
	ConfirmBox(function() {
		$.ajax({
			type: 'GET',
			async: true,
		  	url: rooturl + '_api/sendmail_close_track?token={{ rawurlencode((string) config('legacy.reports.close_track_token', '')) }}',
			success: function(data) {
				SuccessBox('', '{!! res('success_process') !!}');
			}
		});
	});
}
</script>
@endsection
