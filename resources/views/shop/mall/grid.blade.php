{{-- _mall/grid.php เดิม --}}
@use('App\Support\Legacy\Alerts')
@use('App\Support\Legacy\Paging')

<div class="row">
	<div class="col-lg-12">
			<div class="row m-t-10 m-b-10">
            	<div class="col-sm-6 col-lg-8">
                    <div class="form-group contact-search">
                    	<input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{{ $txtSearch[0] }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
            	</div>

            </div>



            <div class="row port">
@if (count($data) !== 0)
@foreach ($data as $item)
                        <div class="col-sm-6 col-lg-3 col-md-4 mobiles">
                            <div style="cursor: pointer;" class="product-list-box thumb" onclick="openmodal('{{ $item->id }}', '{{ Alerts::js($item->name) }} ({{ Alerts::js($item->code) }})')">
                                <div style="height: 200px;overflow: hidden;">
                                        <img src="{{ legacy_blob($item->image) }}" class="thumb-img" alt="{{ $item->name }}">
                                </div>
                                    

                               <!--  <div class="product-action">
                                    <div  class="btn btn-success btn-sm" style="font-size: 20px;">
                                        <i class="fa fa-cart-arrow-down"></i>
                                    </div>
                                </div> -->

                                <div class="detail">
                                    <h4 class="m-t-0 m-b-5"><a href="" class="text-dark">{{ $item->name }}
                                </a> </h4>
                                    
                                    <h5 class="m-0">

                                        <span class="text-custom">
                                        {{ $item->mins == $item->maxs ? $item->mins : $item->mins.'-'.$item->maxs }} บาท
                                        </span> 
                                        
                                    </h5>
                                </div>
                            </div>
                        </div>
@endforeach
@else
<h1 class='text-center'>ไม่มีสินค้าที่คุณค้นหา</h1>
@endif
            </div>


            <div class="pull-right">
                {!! Paging::getpaging($Page, $rows, 10, $ajaxpage) !!}
            </div>
		</div>
        
</div>

<input type="hidden" id="Checking" />
<input type="hidden" id="txtSearch" value="{{ $txtsearchRaw }}">

<script>
	function keypressInBox(e) {
		GenerateWhere();
	    var code = (e.keyCode ? e.keyCode : e.which);
	    if (code == 13) { //Enter keycode                        
	        e.preventDefault();
	        loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());
	    }
	}
	$("#txtsearch").bind("keypress", {}, keypressInBox);


	function Delete(id) {
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ Alerts::js($Page) }}',  $('#txtSearch').val());";
        ConfirmBox(function() {
        	$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id=' + id,
	            url: rooturl + '{{ $ajaxpage }}/../delete',
	            success: function(data) {
	            	eval(data);
	            }
	        });
        });
	}

	function DeleteAll() {
		if($("#Checking").val() == "") {
			ErrorBox('{!! res('add_more_then_one') !!}');
		} else {
            fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ Alerts::js($Page) }}',  $('#txtSearch').val());";
			ConfirmBox(function() {
				$.ajax({
					type: 'POST',
					async: true,
					data : 'arr_id=' + $("#Checking").val(),
				  	url: rooturl + '{{ $ajaxpage }}/../delete',
					success: function(data) {
						eval(data);
					}
				});
			});
		}
	}

	function GenerateWhere() {
		$("#txtSearch").val($("#txtsearch").val() + '|' + $("#status").val());
	}

    function openmodal(id, name) {
        $.ajax({
            type: 'POST',
            async: true,
            data : { id : id },
            url: '{{ legacy_href() }}_mall/load_detail',
            success: function(data) {
                $(".mall_detail_form .panel-body").html(data);
                $(".mall_detail_form .panel-title").text(name);

                $(".mall_detail_form").modal('show');
            }
        });
    }
</script>