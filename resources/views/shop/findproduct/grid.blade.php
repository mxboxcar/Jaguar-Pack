{{-- _findproduct/grid.php ของ SR — ตาราง find_product แต่คอลัมน์/ลิงก์เป็นของ product_type (สำเนาที่ยังทำไม่เสร็จ: kg/cubic/sale_* ไม่มีใน find_product = ว่าง) --}}
@use('App\Support\Legacy\Alerts')
@use('App\Support\Legacy\Format')
@use('App\Support\Legacy\Paging')
@use('App\Support\Legacy\Status')

<div class="row">
	<div class="col-lg-12">
		<div class="card-box">
			<div class="row m-t-10 m-b-10">
            	<div class="col-sm-6 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                    	<input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{{ $txtSearch[0] }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
            	</div>
            </div>

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center">{!! res('name') !!}</th>
                            <th class="text-center" style="width: 90px;">{!! res('type') !!}</th>
                            <th class="text-center" style="width: 90px;">{!! res('kg') !!}</th>
                            <th class="text-center" style="width: 90px;">{!! res('cubic') !!}</th>
                            <th class="text-center" style="width: 90px;">{!! res('sale_kg') !!}</th>
                            <th class="text-center" style="width: 90px;">{!! res('sale_kg') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
@if (count($data) !== 0)
@foreach ($data as $item)
                        <tr>
                            <td><a href="{{ legacy_href() }}admin/product_type/{{ $item->id }}">
                                {{ $prefix }}{{ $item->id }}</a></td>
                            <td>{{ $item->name }}</td>
                            <td class="text-center">{!! Status::getShipType($item->type) !!}</td>
                            <td class="text-right">{!! Format::numberformat($item->kg ?? null) !!}</td>
                            <td class="text-right">{!! Format::numberformat($item->cubic ?? null) !!}</td>
                            <td class="text-right">{!! Format::numberformat($item->sale_kg ?? null) !!}</td>
                            <td class="text-right">{!! Format::numberformat($item->sale_cubic ?? null) !!}</td>
                            <td class="text-center">
                            	<a href="{{ legacy_href() }}admin/product_type/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                            	<a type="button" href="#" onclick="Delete({{ (int) $item->id }});return false;" class="table-action-btn"><i class="md  md-cancel text-danger"></i></a>
                            </td>
                        </tr>
@endforeach
@else
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
@endif
                    </tbody>
                </table>
                <div class="pull-right">
                    {!! Paging::getpaging($Page, $rows, 10, $ajaxpage) !!}
                </div>
            </div>
		</div>
        
    </div> <!-- end col -->
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
			ErrorBox('{!! res("add_more_then_one") !!}');
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
		$("#txtSearch").val($("#txtsearch").val());
	}
</script>
