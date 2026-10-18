{{-- _admin/_warehouse/form.php เดิม (สิทธิ์ admin_warehouse ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('edit_warehouse_list') !!} <small>{{ $prefix }}{{ $d['id'] ?? '' }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			{!! res('edit_warehouse_list') !!}
		</li>
	</ol>
</div>



<input type="hidden" name="id" value="{{ $d['id'] ?? '' }}">
	
	<div class="col-sm-5 pull-right">
		<div class="button-list">

		    <a type="button" href="{{ legacy_href() }}admin/warehouse" class="pull-right btn btn-warning waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
			    {!! res('back_to_list') !!}
		    </a>
		</div>
	</div>

	<div class="col-lg-12">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">
				 
		        <div class="form-group">
		            <label class="col-md-2 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-7 form-control-static">
		            	{{ $prefix }}{{ $d['id'] ?? '' }}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-2 control-label"> {!! res('remark') !!}</label>
		            <div class="col-md-7">
		            	<textarea class="form-control" name="remark" onblur="on_save_remark()">{{ $d['remark'] ?? '' }}</textarea>
		            </div>
		        </div>

		    </div>

		</div>
	</div>

	<div class="col-lg-12">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('choose_lot') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">
				 
				<div class="form-group">
		            <label class="col-md-2 control-label"> {!! res('choose_lot') !!}</label>
		            <div class="col-md-2">
		            	<button class="btn btn-primary" onclick="LoadRefPrint();return false;" type="button">{!! res('please_select') !!}</button>
		            </div>
		        </div>

		        <div class="form-group">
			        <div id="result_search">
			        	กำลังโหลดข้อมูล กรุณารอ...
			        </div>
			    </div>

		    </div>
		</div>
	</div>

	

	<div class="col-lg-12">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('receive_package') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>

	        <div class="form-horizontal" role="form">
				 
		        <div class="form-group">
		            <label class="col-md-2 control-label"> {!! res('tracking') !!}</label>
		            <div class="col-md-7">
		                <input type="text" id="search_tracking" class="form-control validate[required]" value="{{ $d['name'] ?? '' }}" >
		            </div>
		            <div class="col-md-2">
		            	<button type="button" onclick="go_loadtrack();" class="btn btn-primary">{!! res('receive_tracking') !!}</button>
		            </div>
		        </div>
		    </div>
			<div class="form-horizontal" role="form">
				 

		        <div class="form-group">
			        <div id="result_search_thai">
			        	กำลังโหลดข้อมูล กรุณารอ...
			        </div>
			    </div>

		    </div>
		</div>
	</div>


	<div id="lot_print" >
		<div id="panel-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
		    <div class="modal-dialog modal-xs">
		        <div class="modal-content p-0 b-0">
		            <div class="panel panel-color panel-primary">
		                <div class="panel-heading"> 
		                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
		                    <h3 class="panel-title">{!! res('lot') !!}</h3> 
		                </div> 
		                <div class="panel-body"> 
		                	<input type="hidden" name="id" value="{{ $d['id'] ?? '' }}" />
		                	<div class="col-xs-6 detail">

				          </div>
					          <div class="col-xs-6 ">
					          	<div class="form-group">
					            	<select class="form-control" name="selected_print[]" id="result" multiple style="height:214px;"></select>
					            	</div>
					          </div>
					          <div class="col-xs-12  text-center">
					          	<div class="form-group">
						            <button class="btn btn-info waves-effect waves-light" id="add">
						            	<span class="btn-label"><i class="fa fa-plus"></i></span>
						            	{!! res('add') !!}</button>
						            <button class="btn btn-danger waves-effect waves-light" id="remove">
						            <span class="btn-label"><i class="fa fa-close"></i></span>
						            	{!! res('delete') !!}</button>
					            </div>
					          </div>
		                </div> 
		                <div class="panel-footer text-right">
		                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
		                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="go_loadDetail()" type="button">{!! res('ok') !!}</button>
		                </div>
		                
		            </div>

		        </div><!-- /.modal-content -->
		    </div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
	</div>


	<form id="add_weight" action="{{ legacy_href() }}_admin/_warehouse/save_track_weight" method="post" >
		<div id="panel-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
		    <div class="modal-dialog modal-xs">
		        <div class="modal-content p-0 b-0">
		            <div class="panel panel-color panel-primary">
		                <div class="panel-heading"> 
		                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
		                    <h3 class="panel-title">{!! res('tracking') !!}</h3> 
		                </div> 
		                <div class="panel-body"> 
		                	
		                </div> 
		                <div class="panel-footer text-right">
		                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
		                    <button type="submit" class="btn btn-primary waves-effect waves-light" type="button">{!! res('ok') !!}</button>
		                </div>
		                
		            </div>

		        </div><!-- /.modal-content -->
		    </div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
	</form>


	<form id="edit_shelf" action="{{ legacy_href() }}_admin/_warehouse/save_editshelf" method="post" >
		<div id="panel-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
		    <div class="modal-dialog modal-xs">
		        <div class="modal-content p-0 b-0">
		            <div class="panel panel-color panel-primary">
		                <div class="panel-heading"> 
		                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
		                    <h3 class="panel-title">แก้ไขชั้นวาง</h3> 
		                </div> 
		                <div class="panel-body"> 
		                	
		                </div> 
		                <div class="panel-footer text-right">
		                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
		                    <button type="submit" class="btn btn-primary waves-effect waves-light" type="button">{!! res('ok') !!}</button>
		                </div>
		                
		            </div>

		        </div><!-- /.modal-content -->
		    </div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
	</form>

<script>
	


	$("form").validationEngine();
	// $("form").ajaxForm({ 
	// 	beforeSubmit : function(arr, $form, options){
	// 		OpenLoading();
	// 	},
	//     success: function(data) {
	//         //eval(data);
	//         $("#result_search").html(data);
	//         CloseLoading();
	//         $(".modal").modal('hide');
	//     }
	// });

	$("form").eq(0).ajaxForm({ 
		beforeSubmit : function(arr, $form, options){
			OpenLoading();
		},
	    success: function(data) {
	        eval(data);
	    }
	});

	$("form").eq(1).ajaxForm({ 
		beforeSubmit : function(arr, $form, options){
			OpenLoading();
		},
	    success: function(data) {
	        eval(data);
	    }
	});

	$().ready(function() {  
     $('#add').click(function() {  
      return !$('#selected_print option:selected').remove().appendTo('#result');  
     });  
     $('#remove').click(function() {  
      return !$('#result option:selected').remove().appendTo('#selected_print');  
     });  

     go_init();
     go_init_thai();

     $("#search_tracking").bind("keypress", {}, keypressInBoxWarehouse);
    });

	function keypressInBoxWarehouse(e) {
	    var code = (e.keyCode ? e.keyCode : e.which);
	    if (code == 13) { //Enter keycode                        
	        e.preventDefault();
	        go_loadtrack();
	    }
	}

	function go_init_thai() {
		OpenLoading();
		var strs = '{{ $strInit }}';
		var strs = strs.split(",");
		var s  = '';
		for(var i = 0; i < strs.length; i++) {
			s += '&selected_print[]=' + strs[i];
		}

		$.ajax({
	        type: 'POST',
	        async: true,
	        data : 'id={{ $id }}' + s ,
	        url: rooturl + '_admin/_warehouse/load_lot_detail_thai',
	        success: function(data) {
	            $("#result_search_thai").html(data);
	            CloseLoading();
	        }
	    });
	}

	function go_init() {
		OpenLoading();
		var strs = '{{ $strInit }}';
		var strs = strs.split(",");
		var s  = '';
		for(var i = 0; i < strs.length; i++) {
			s += '&selected_print[]=' + strs[i];
		}

		$.ajax({
	        type: 'POST',
	        async: true,
	        data : 'id={{ $id }}' + s ,
	        url: rooturl + '_admin/_warehouse/load_lot_detail',
	        success: function(data) {
	            $("#result_search").html(data);
	            CloseLoading();
	        }
	    });
	}

	function go_loadtrack() {
		OpenLoading();
		$.ajax({
	        type: 'POST',
	        async: true,
	        data : 'tracking=' + encodeURIComponent($("#search_tracking").val()) + '&warehouse_id={{ $id }}',
	        url: rooturl + '_admin/_warehouse/load_tracking',
	        success: function(data) {
	        	if(data.trim().search("ErrorBox") != -1) {
	                eval(data);
		        } else {
		        	$("#add_weight .panel-body").html(data);
		        	$("#add_weight .modal").modal('show');
		        	CloseLoading();
		        	setTimeout(function() { $("input[name='weight']").focus(); }, 1000);
		        }
	        	
	        }
	    });
	}

	function go_editshelf(id) {
		OpenLoading();
		$.ajax({
	        type: 'POST',
	        async: true,
	        data : 'id=' + id + '&warehouse_id={{ $id }}' ,
	        url: rooturl + '_admin/_warehouse/load_editshelf',
	        success: function(data) {
	        	if(data.trim().search("ErrorBox") != -1) {
	                eval(data);
		        } else {
		        	$("#edit_shelf .panel-body").html(data);
		        	$("#edit_shelf .modal").modal('show');
		        	CloseLoading();
		        }
	        	
	        }
	    });
	}

	function go_pushtrack() {
		OpenLoading();
		$.ajax({
	        type: 'POST',
	        async: true,
	        data : 'tracking=' + $("#search_tracking").val() ,
	        url: rooturl + '_admin/_warehouse/save_push_track',
	        success: function(data) {
	        	eval(data);
	        }
	    });
	}

	function go_edittracking(id) {
		OpenLoading();
		$.ajax({
	        type: 'POST',
	        async: true,
	        data : 'id=' + id + '&warehouse_id={{ $id }}',
	        url: rooturl + '_admin/_warehouse/load_tracking_edit',
	        success: function(data) {
	            if(data.trim().search("ErrorBox") != -1) {
	                eval(data);
		        } else {
		        	$("#add_weight .panel-body").html(data);
		        	$("#add_weight .modal").modal('show');
		        	CloseLoading();
		        	setTimeout(function() { $("input[name='weight']").focus(); }, 1000);
		        }
	        }
	    });
	}

	function go_receive(id, weight) {
		ConfirmBox(function () {
			OpenLoading();
			$.ajax({
		        type: 'POST',
		        async: true,
		        data : 'id=' + id + '&weight=' + weight + '&warehouse_id={{ $id }}',
		        url: rooturl + '_admin/_warehouse/save_track_weight',
		        success: function(data) {
		            if(data.trim().search("ErrorBox") != -1) {
		                eval(data);
			        } else {
	     				CloseLoading();
			        }
		        }
		    });
		});
	}

	function Calculate() {
		var amount = $("input[name='amount']").val();
		var weight = $("input[name='weight']").val();

		var width = $("input[name='width']").val();
		var height = $("input[name='height']").val();
		var long = $("input[name='long']").val();
		var x = eval(amount) * ((eval(width) * eval(height) * eval(long) / 1000000));

		$(".total_kg").text(eval(amount) * eval(weight));
		$(".total_cubic").text(x)
		initialAddcommaText();
	}

	function GetProductType(id) {
		OpenLoading();
		$.ajax({
            type: 'POST',
            async: true,
            data : 'id={{ $id }}&ship=' + id,
            url: rooturl + '_admin/_tracking/load_product_type',
            success: function(data) {
            	$("#product_type").html(data);
            	CloseLoading();
            	getPricing();
            }
        });
	}



	function LoadRefPrint(id) {
		$.ajax({
            type: 'POST',
            async: true,
            data : 'id=&search=',
            url: rooturl + '_admin/_lot/load_ref_print',
            success: function(data) {
            	$("#lot_print .detail").html(data);
            	$("#search_lot").bind("keypress", {}, keypressInBox2);

            	$('#lot_print .modal').modal('show');
            }
        });
	}

	function keypressInBox2(e) {
	    var code = (e.keyCode ? e.keyCode : e.which);
	    if (code == 13) { //Enter keycode                        
	        e.preventDefault();
	        $.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id=' + $("#search_id").val() + '&search=' + $("#search_lot").val(),
	            url: rooturl + '_admin/_lot/load_ref_print',
	            success: function(data) {
	            	$("#lot_print .detail").html(data);
	            	$("#search_lot").bind("keypress", {}, keypressInBox2);
	            }
	        });
	    }
	}

	// function go_loadDetail() {
 //    	if($("#lot_print").validationEngine('validate') == false) 
	// 	{ 
	// 		CloseLoading(); 
	// 	} else {
	// 		ConfirmBox(function () {
	// 			$("#lot_print").submit();
	// 		});
 //    	}
	// }

	function go_loadDetail() {
		ConfirmBox(function () {
			OpenLoading();
			$.ajax({
	            type: 'GET',
	            async: true,
	            data : 'id=' + $("input[name='id']").val() + '&prints=' + $("select[name='selected_print[]'").val(),
	            url: rooturl + '_admin/_warehouse/save_lot_detail',
	            success: function(data) {
	            	// eval(data);
	            	window.location.reload();
	            }
	        });
	    });
	}

	function go_cancel_warehouse_calculate() {
		ConfirmBox(function () {
			OpenLoading();
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'ids=' + $("#ids").val() + '&id=' + $("input[name='id']").val(),
	            url: rooturl + '_admin/_warehouse/save_cancel_warehouse_calculate',
	            success: function(data) {
	            	window.location.reload();
	            }
	        });
	    });
	}


</script>
@endsection
