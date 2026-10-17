{{-- _admin/_warehouse_section/grid.php เดิม --}}
@php use App\Support\Legacy\Format; @endphp
<div class="row">
	<div class="col-lg-12">
		<div class="card-box">
			<div class="row m-t-10 m-b-10">
            	<div class="col-sm-6 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                    	<input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{{ $search }}">
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
                            <th class="text-center" style="width: 90px;">{!! res('map') !!}</th>
                            <th class="text-center" style="width: 90px;">{!! res('width') !!}</th>
                            <th class="text-center" style="width: 90px;">{!! res('height') !!}</th>
                            <th class="text-center" style="width: 90px;">{!! res('long') !!}</th>
                            <th class="text-center" style="width: 90px;">{!! res('cubic') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td><a target="_blank" href="{{ legacy_href() }}admin/warehouse_section/{{ $item['id'] }}">
                                {{ $prefix }}{{ $item['id'] }}</a></td>
                            <td>{{ $item['name'] }}</td>
                            <td class="text-center">{!! Format::getFancyBox($item['attachment'] === null ? null : e($item['attachment'])) !!}</td>
                            <td class="text-center">{!! Format::numberformatone($item['width']) !!}</td>
                            <td class="text-right">{!! Format::numberformatone($item['height']) !!}</td>
                            <td class="text-right">{!! Format::numberformatone($item['long']) !!}</td>
                            <td class="text-right">{!! Format::cubicformat((\App\Domain\Warehouse\Php74::num($item['width']) * \App\Domain\Warehouse\Php74::num($item['height']) * \App\Domain\Warehouse\Php74::num($item['long'])) / 1000000) !!}</td>
                            <td class="text-center">
                            	<a href="{{ legacy_href() }}admin/warehouse_section/{{ $item['id'] }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                            	<a type="button" href="#" onclick="Delete({{ $item['id'] }});return false;" class="table-action-btn"><i class="md  md-cancel text-danger"></i></a>
                            </td>
                        </tr>
                    @empty
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
                    @endforelse
                    </tbody>
                </table>
                <div class="pull-right">
                    {!! \App\Support\Legacy\Paging::getpaging($page, $total, 10, $ajaxpage) !!}
                </div>
            </div>
		</div>
        
    </div> <!-- end col -->
</div>

<input type="hidden" id="Checking" />
<input type="hidden" id="txtSearch" value="{{ $txtsearch }}">

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
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ preg_replace('/[^0-9]/', '', (string) $page) }}',  $('#txtSearch').val());";
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
            fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ preg_replace('/[^0-9]/', '', (string) $page) }}',  $('#txtSearch').val());";
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
