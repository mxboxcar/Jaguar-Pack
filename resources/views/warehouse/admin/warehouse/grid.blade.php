{{-- _admin/_warehouse/grid.php เดิม --}}
@php
    use App\Domain\Warehouse\Php74;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Alerts;
@endphp
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

                <div class="col-xs-2 col-sm-4 col-lg-4">
                    <select name="role" class="form-control " id="status" onchange="GenerateWhere()">
                        <option @if(Php74::eq($type, '-2')) selected='selected' @endif value="-2">- {!! res('all') !!} -</option>  
                        <option @if(Php74::eq($type, '0')) selected='selected' @endif value="0">{!! res('car') !!}</option>   
                        <option @if(Php74::eq($type, '1')) selected='selected' @endif value="1">{!! res('ship') !!}</option>
                    </select>
                </div>
            </div>

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" >{!! res('remark') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('working_date') !!}</th>
                            <th class="text-center" style="width: 120px;">{!! res('amount_of_china') !!}</th>
                            <th class="text-center" style="width: 120px;">{!! res('amount_in_thai') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('edit_last') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('status') !!}</th>
                            <th class="text-center" style="width: 95px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($items as $item)
                        @php
                            $cd = $item->create_date === null ? '' : (string) $item->create_date;
                            $exp = explode(' ', $cd);
                            $c = $counts[(int) $item->id] ?? null;
                        @endphp
                            <tr>
                                <td><a href="{{ legacy_href() }}admin/warehouse/{{ $item->id }}">{{ $prefix }}{{ $item->id }}</a></td>
                                <td class="text-left">{{ implode(',', $lots[(int) $item->id] ?? []) }}</td>
                                <td class="text-center">
                                    @if($cd === '0000-00-00 00:00:00' || $cd === '')
-<br />-
                                    @else
{!! Format::getthaidate($exp[0], true) !!}<br />{!! Format::gettime($exp[1] ?? null) !!}
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{ $c ? (int) $c->cn : 0 }}
                                </td>
                                <td class="text-center">
                                    {{ $c ? (int) $c->th : 0 }}
                                </td>
                                <td class="text-center">{{ $item->code }}</td>
                                <td class="text-center">
                                    {{-- เดิม: $data1['counts'] != $data2['counts'] โดย $data1/$data2 เป็น int → null != null → "รับครบ" เสมอ --}}
                                    <span class='label label-success'>{!! res('finish_receive_package') !!}</span>
                                </td>
                                <td class="text-right">
                                	<a href="{{ legacy_href() }}admin/warehouse/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
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

    $('[data-toggle="tooltip"]').tooltip(); 
    
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
		$("#txtSearch").val($("#txtsearch").val() + '|' + $("#status").val());
	}
</script>
