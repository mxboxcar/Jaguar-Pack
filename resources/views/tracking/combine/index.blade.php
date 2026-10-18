{{-- _admin/_combine_track/index.php เดิม --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('combine_track') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			{!! res('combine_track') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">
	   
	</div>
</div>

<div class="col-sm-8">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('search_tracking') !!}</b></h4>
		<p class="text-muted font-13 m-b-30">
	    </p>
		<div class="form-horizontal" role="form">                                    
	        <div class="form-group">
	            <label class="col-md-3 control-label">{!! res('search') !!}</label>
	            <div class="col-md-7">
	                <input type="text" name="tracking" class="form-control" value="">
	            </div>
	        </div>
	        <div class="form-group">
	            <label class="col-md-3 control-label"></label>
	            <div class="col-md-7">
	                <button type="button" class="btn btn-primary waves-effect waves-light" onclick="on_search();">{!! res('searching') !!}</button>
	                <button type="button" class="btn btn-primary waves-effect waves-light" onclick="do_combine();">{!! res('combine_track') !!}</button>
	            </div>
	        </div>
	    </div>
	</div>
</div>


<div class="col-sm-12">
	<div class="card-box"    >
		<table class="table table-striped table-bordered">
		    <thead>
		        <tr>
		        		<th></th>
		                <th style="min-width: 230px;"> <div class="text-center" >{!! res('detail') !!}</div></th>
		                <th> <div class="text-center" >{!! res('customer_code') !!}</div></th>
		                <th> <div class="text-center" >{!! res('calculate_price') !!}</div></th>
		                <th style="min-width:150px;"> <div class="text-center" >{!! res('detail') !!}</div></th>
		                <th style="width:70px;"> <div class="text-center" >{!! res('invoice') !!}</div></th>
		                <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
		          </tr>
		    </thead>

		    <tbody id="result">

		    </tbody>
		</table>
	</div>
</div>

<script>

function on_search() {
	OpenLoading();
	$.ajax({
        type: 'POST',
        async: true,
        data :  'tracking=' + $("input[name='tracking']").val() + '&order=' + $("input[name='order']").val() ,
        url: rooturl + '_admin/_combine_track/load_tracking',
        success: function(data) {
        	$("#result").html(data);
        	CloseLoading();
        }
    });
}


function do_combine() {
	var count =  $("input[name='tracking_id']:checked").length;

	var str = '';
	$("input[name='tracking_id']:checked").each(function() {
		str += ',' + $(this).val();
	});

	if(count != 2) {
		ErrorBox('{!! res('error_select_combine') !!}');
	} else {
		ConfirmBox(function() {
            $.ajax({
		        type: 'POST',
		        async: true,
		        data :  'tracking=' + str ,
		        url: rooturl + '_admin/_combine_track/save_combine',
		        success: function(data) {
		        	if(data.search("ErrorBox") != -1) { eval(data); return; }
		        	on_search();
		        	CloseLoading();
		        }
		    });
      	});
	}
}
</script>
@endsection
