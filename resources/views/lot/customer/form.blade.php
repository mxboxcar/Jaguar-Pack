{{-- _lot/form.php เดิม --}}
@extends('legacy.layout')

@php
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $date = function ($v) {
        $v = (string) $v;
        return ($v !== '0000-00-00 00:00:00' && $v !== '') ? Format::getthaidate(explode(' ', $v)[0]) : '';
    };
    $no = $prefix.($d['id'] ?? '');
@endphp

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('lot') !!} <small>{{ $no }}</small></h4> 
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('lot') !!}
		</li>
		<li class="active">
			{{ $no }}
		</li>
	</ol>
</div>


<input type="hidden" name="id" value="{{ $d['id'] ?? '' }}">
<input type="hidden" name="method" value="">

<div class="col-sm-5 pull-right">
	<div class="button-list">
	    <a type="button" href="{{ legacy_href() }}lot" class="pull-right btn btn-warning waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
		    {!! res('back_to_list') !!}
	    </a>
	</div>
</div>


<div class="col-sm-12">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b> </h4>
		<p class="text-muted font-13 m-b-30">
        </p>
        <div class="form-horizontal" role="form"> 
	        <div class="col-xs-6">
	        	<div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-xs-8  form-control-static">
		            	{{ $no }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('name') !!}</label>
		            <div class="col-xs-6  form-control-static">
		            	{{ $d['name'] ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('ship_type') !!}</label>
		            <div class="col-xs-6  form-control-static">
		            	{!! Status::getShipType($d['type'] ?? null) !!}
		            </div>
		        </div>
        	</div>                                   
	        <div class="col-xs-6">
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('status') !!}</label>
		            <div class="col-xs-8 form-control-static">
		            	{!! Status::getlotstatus($d['status'] ?? null) !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('date_in') !!}</label>
		            <div class="col-xs-6  form-control-static">
		            	{!! $date($d['date_in'] ?? '') !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('date_out') !!}</label>
		            <div class="col-xs-6 form-control-static">
		            	{!! $date($d['date_out'] ?? '') !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('date_arrive') !!}</label>
		            <div class="col-xs-6 form-control-static">
		            	{!! $date($d['date_arrive'] ?? '') !!}
		            </div>
		        </div>
		    </div>
	    </div>

        <div id="lot_detail" >
	    </div>


	    <div class="form-horizontal" role="form" style="margin-top:10px;z-index: 0">     
			<div class="form-group text-center">
				<span style="padding:2px 5px;background-color:#f9ffa5;">{!! res('qc') !!}&{!! res('wood_protect') !!}</span>
				<span style="padding:2px 5px;background-color:#a5ffc5;">{!! res('qc') !!}</span>
				<span style="padding:2px 5px;background-color:#768cf2;">ถ่ายรูป</span>
				<span style="padding:2px 5px;background-color:#ffdfa5;">{!! res('wood_protect') !!}</span>
				<span style="padding:2px 5px;background-color:#ff0c00;">{!! res('no_price') !!}</span>
				<span style="padding:2px 5px;background-color:#e789ff;">claim</span>
			</div>
		</div>
	</div>
</div>



<script>
$(function() {
	LoadDetail();

	$("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	        $('#searchform').css('display', 'none');
	        onclearform();
	    }
	});
});


function LoadDetail() {
    OpenLoading();
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id={{ $id }}',
        url: rooturl + '_lot/load_lot_detail',
        success: function(data) {
            $("#lot_detail").html(data);

            $('.showdetailbox').on('touchstart mouseover', function (e) {
			    $(this).css({'color':  '#ff0000'});
				$(this).find('.lot_table_in').css('display', 'block');
			});
			$('.showdetailbox').on('touchend mouseout', function (e) {
			    $(this).css({'color': '#797979'});
				$(this).find('.lot_table_in').css('display', 'none');
			});

            makeCurrency();
            CloseLoading();
        }
    });
}

</script>
@endsection
