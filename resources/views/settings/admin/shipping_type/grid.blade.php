{{-- _admin/_shipping_type/grid.php เดิม --}}
@php use App\Support\Legacy\Format; use App\Support\Legacy\Paging; use App\Support\Legacy\Status; use App\Domain\Settings\Input; @endphp

<div class="row">
	<div class="col-lg-12">
		<div class="card-box">
			<div class="row m-t-10 m-b-10">
            	<div class="col-sm-6 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                    	<input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{{ $termPhp }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
            	</div>
            </div>

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 55px;">{!! res('no') !!}</th>
                            <th class="text-center">{!! res('name') !!}</th>
                            <th class="text-center" style="width: 200px;">{!! res('type') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
@forelse($items as $item)
                        <tr>
                            <td><a target="_blank" href="{{ legacy_href() }}admin/shipping_type/{{ $item->id }}">
                                {{ $prefix.$item->id }}</a></td>
                            <td class="text-center"><input style="font-size: 10px;height: 20px;width: 30px;text-align: center;" type="text" class="form-control" onblur="onchangelistno($(this), '{{ $item->id }}')" value="{{ $item->no }}" /></td>
                            <td>{{ $item->name }}</td>
                            <td class="text-center">{!! Status::getShippingTypeName($item->is_use_rate) !!}</td>
                            <td class="text-center">
                            	<a href="{{ legacy_href() }}admin/shipping_type/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                            	<a type="button" href="#" onclick="Delete({{ $item->id }});return false;" class="table-action-btn"><i class="md  md-cancel text-danger"></i></a>
                            </td>
                        </tr>
@empty
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
@endforelse
                    </tbody>
                </table>
                <div class="pull-right">
{!! Paging::getpaging($page, $total, 10, $ajaxpage) !!}
                </div>
            </div>
		</div>
        
    </div> <!-- end col -->
</div>

<input type="hidden" id="Checking" />
<input type="hidden" id="txtSearch" value="{{ $raw }}">

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
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{!! Input::js($page) !!}',  $('#txtSearch').val());";
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
            fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{!! Input::js($page) !!}',  $('#txtSearch').val());";
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

    function onchangelistno(val, id) {
        $.ajax({
            type: 'POST',
            async: true,
            data : 'id=' + id + '&val=' + val.val(),
            url: rooturl + '{{ $ajaxpage }}/../save_listno',
            success: function(data) {
                loadAjaxGrid('{{ $ajaxpage }}', '{!! Input::js($page) !!}',  $('#txtSearch').val());
            }
        });
    }

	function GenerateWhere() {
		$("#txtSearch").val($("#txtsearch").val());
	}
</script>
