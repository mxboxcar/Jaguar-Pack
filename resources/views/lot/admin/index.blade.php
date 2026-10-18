{{-- _admin/_lot/index.php เดิม (สิทธิ์ admin_lot / admin_lot_view ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('lot_list') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			{!! res('lot_list') !!}
		</li>
	</ol>
</div>
<div class="col-sm-5 pull-right">
	<div class="button-list">
	    <a type="button" href="#" onclick="$('#lot_create .modal').modal('show');return false;" class="pull-right btn btn-info waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-plus"></i></span>
		    {!! res('add_new_list') !!}
	    </a>
	</div>
</div>

<div class="col-sm-12">
	<div class="col-sm-4">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>รับพัสดุเข้าไทย</b></h4>
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
		                <button type="button" class="btn btn-primary waves-effect waves-light" onclick="on_search();">ค้นหาและรับเข้า</button>
		            </div>
		        </div>
		    </div>
		</div>
	</div>

	<div class="col-sm-8">
		<div class="card-box"    >
			<table class="table table-striped table-bordered">
			    <thead>
			        <tr>
			                <th style="min-width: 230px;"> <div class="text-center" >{!! res('detail') !!}</div></th>
			                <th> <div class="text-center" >{!! res('customer_code') !!}</div></th>
			                <th> <div class="text-center" >{!! res('calculate_price') !!}</div></th>
			                <th style="min-width:150px;"> <div class="text-center" >{!! res('detail') !!}</div></th>
			                <th style="width:70px;"> <div class="text-center" >{!! res('invoice') !!}</div></th>
			                <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
			          </tr>
			    </thead>

			    <tbody id="resultserach">
			    	<tr>
			    		<td colspan="5" class="text-center">{!! res('no_data') !!}</td>
			    	</tr>
			    </tbody>
			</table>
		</div>
	</div>
</div>

<div id="gridAjax" class="col-sm-12"></div>

<form id="lot_create" action="{{ legacy_href() }}_admin/_lot/save_new_lot" method="post" >
	<div id="panel-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
	    <div class="modal-dialog modal-sm">
	        <div class="modal-content p-0 b-0">
	            <div class="panel panel-color panel-primary">
	                <div class="panel-heading"> 
	                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
	                    <h3 class="panel-title">{!! res('lot') !!}</h3> 
	                </div> 
	                <div class="panel-body"> 
		                <select name="ship" class="form-control " >
		                    <option value="0">{!! res('car') !!}</option>
	                        <option value="1">{!! res('ship') !!}</option>
	                    </select>
	                </div> 
	                <div class="panel-footer text-right">
	                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
	                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="add_lot()" type="button">{!! res('ok') !!}</button>
	                </div>
	                
	            </div>

	        </div><!-- /.modal-content -->
	    </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
</form>

<form id="lot_print" action="{{ legacy_href() }}_admin/_lot/save_print" method="post" >
	<div id="panel-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
	    <div class="modal-dialog modal-xs">
	        <div class="modal-content p-0 b-0">
	            <div class="panel panel-color panel-primary">
	                <div class="panel-heading"> 
	                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
	                    <h3 class="panel-title">{!! res('lot') !!}</h3> 
	                </div> 
	                <div class="panel-body"> 
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
	                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="printing()" type="button">{!! res('ok') !!}</button>
	                </div>
	                
	            </div>

	        </div><!-- /.modal-content -->
	    </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
</form>

<script type="text/javascript">
    loadAjaxGrid('_admin/_lot/grid', 1, '|{!! \App\Support\Legacy\Alerts::js($status) !!}');

    $().ready(function() {  
     $('#add').click(function() {  
      return !$('#selected_print option:selected').remove().appendTo('#result');  
     });  
     $('#remove').click(function() {  
      return !$('#result option:selected').remove().appendTo('#selected_print');  
     });  



     $("input[name='tracking']").bind("keypress", {}, keypressInBox5);
    });

    $("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	    }
	});

	function LoadRefPrint(id) {
		$.ajax({
            type: 'POST',
            async: true,
            data : 'id=' + id + '&search=' + $("#search_lot").val(),
            url: rooturl + '_admin/_lot/load_ref_print',
            success: function(data) {
            	$("#lot_print .detail").html(data);
            	$("#search_lot").bind("keypress", {}, keypressInBox2);

            	$('#lot_print .modal').modal('show');
            }
        });
	}

	function Sendmail(id) {
		ConfirmBox(function() {
        	window.open(rooturl + '_admin/_lot/email_lot?id=' + id)
	        CloseLoading();
        })
	}

	function Verified(id) {
		ConfirmBox(function() {
        	$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id=' + id,
	            url: rooturl + '_admin/_lot/save_verified',
	            success: function(data) {
	            	eval(data);
	            }
	        });
        })
	}


	function keypressInBox2(e) {
		GenerateWhere();
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

    function add_lot() {
    	if($("#lot_create").validationEngine('validate') == false) 
		{ 
			CloseLoading(); 
		} else {
			ConfirmBox(function () {
				$("#lot_create").submit();
			});
		}
    }

    function printing() {
    	if($("#lot_print").validationEngine('validate') == false) 
		{ 
			CloseLoading(); 
		} else {
			ConfirmBox(function () {
				$("#lot_print").submit();
			});
		}
    }

    function on_search() {
		OpenLoading();
		$.ajax({
	        type: 'POST',
	        async: true,
	        data :  'tracking=' + $("input[name='tracking']").val() + '&order=' + $("input[name='order']").val() ,
	        url: rooturl + '_admin/_lot/load_check_tracking',
	        success: function(data) {
	        	$("#resultserach").html(data);
	        	CloseLoading();

	        	setTimeout(function() {
	        		$("input[name='tracking']").val('');
	        		$("input[name='tracking']").focus();
	        	}, 500);
	        }
	    });
	}


	function keypressInBox5(e) {
	    var code = (e.keyCode ? e.keyCode : e.which);
	    if (code == 13) { //Enter keycode                        
	        e.preventDefault();
	        on_search();
	    }
	}
</script>
@endsection
