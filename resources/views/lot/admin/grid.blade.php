{{-- _admin/_lot/grid.php เดิม --}}
@php
    use App\Domain\Lot\Php74;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Paging;
    use App\Support\Legacy\Status;
    $sel = fn ($v) => $type !== null && Php74::eq($type, $v) ? "selected='selected'" : '';
    $firstDate = function ($row, $col) {
        return $row === null ? '-' : Format::getthaidate(explode(' ', (string) $row->$col)[0], true, true);
    };
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
                        <option {!! $sel("-2") !!} value="-2">- {!! res('all') !!} -</option>  
                        <option {!! $sel("0") !!} value="0">{!! res('car') !!}</option>   
                        <option {!! $sel("1") !!} value="1">{!! res('ship') !!}</option>
                    </select>
                </div>
            </div>

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" >{!! res('remark') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('date_in') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('date_out') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('date_arrive') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('amount') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('amount_shipping') !!}</th>
                            
                            <th class="text-center" style="width: 100px;">{!! res('edit_last') !!}</th>
                            <th class="text-center" style="width: 70px;">{!! res('attachment') !!}</th>
                            <!-- <th class="text-center" style="width: 100px;">{!! res('status') !!}</th> -->
                            <th class="text-center" style="width: 95px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($items as $item)
                        @php
                            $fr = $first[(int) $item->id] ?? null;
                            $count = $counts[$item->id] ?? null;
                        @endphp
                        <tr>
                            <td><a href="{{ legacy_href() }}admin/lot/{{ $item->id }}">
                            @if (Php74::neq($item->name, ''))
{!! Status::getShipType($item->type) !!} {{ $item->name }}@endif </a></td>
                            <td class="text-left">{{ $item->name_admin }}</td>
                            <td class="text-center">
                                {!! $firstDate($fr, 'date_warehouse') !!}
                            </td>
                            <td class="text-center">
                                {!! $firstDate($fr, 'date_outofchina') !!}
                            </td>
                            <td class="text-center">
                                {!! $firstDate($fr, 'date_thai') !!}
                            </td>
                            <td class="text-center">{!! Format::numberformatzero($count) !!}</td>
                            <td class="text-center">{!! Format::numberformatzero($item->shipping_amount) !!}</td>
                            <td class="text-center">{{ $item->code }}</td>
                            <td class="text-center">
                            @if (Php74::eq($item->attachment, ''))
<div>-</div>@else
<div><a style="    font-size: 9px;
    display: inline-block;
    width: 60px;
    white-space: nowrap;
    overflow: hidden !important;
    text-overflow: ellipsis;" href="{{ legacy_href() }}download_file?path={{ urlencode(legacy_blob().$item->attachment) }}&filename={{ urlencode((string) $item->attachment_name) }}">{{ $item->attachment_name }}</a></div>@endif
                            </td>
                            <!-- <td class="text-center">{!! Status::getlotstatus($item->status) !!}</td> -->
                            <td class="text-right">
                            	<a href="{{ legacy_href() }}admin/lot/{{ $item->id }}" data-toggle="tooltip" title="แก้ไข" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                                @if (Php74::eq($count, 0))
<a type="button" href="#" onclick="Delete({{ $item->id }});return false;" data-toggle="tooltip" title="ลบ" class="table-action-btn"><i class="md  md-cancel text-danger"></i></a>@endif

                                
                                @if (Php74::eq($item->is_mail, 1))
                                <a  href="#" onclick="Sendmail({{ $item->id }});return false;"  data-toggle="tooltip" title="ส่งอีเมล์แล้ว"  class="table-action-btn"><i class="fa fa-envelope text-danger"></i></a>
                                @else
                                <a  href="#" onclick="Sendmail({{ $item->id }});return false;"  data-toggle="tooltip" title="ส่งอีเมล์"  class="table-action-btn"><i class="fa fa-envelope-o text-primary"></i></a>
                                @endif

                                <a href="{{ legacy_href() }}/_admin/_lot/print_all?lot={{ $item->id }}"  data-toggle="tooltip" title="ดาวน์โหลดไฟล์ Lot"  class="table-action-btn" ><i class="fa fa-file-o text-primary"></i></a>

                                @if (Php74::eq($item->is_print, 1))
                                <a  href="#" onclick="LoadRefPrint({{ $item->id }});return false;" data-toggle="tooltip" title="พิมพ์แล้ว" class="table-action-btn"><i class="fa fa-print text-danger"></i></a>
                                @else
                                <a  href="#" onclick="LoadRefPrint({{ $item->id }});return false;" data-toggle="tooltip" title="พิมพ์" class="table-action-btn"><i class="fa fa-print text-primary"></i></a>
                                @endif
                                
                                <a  href="{{ legacy_href() }}_admin/_lot/print_receive?id={{ $item->id }}" data-toggle="tooltip" title="พิมพ์ใบรับสินค้า" class="table-action-btn"><i class="fa fa-print text-danger"></i></a>

                                
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
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ $page }}',  $('#txtSearch').val());";
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
            fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ $page }}',  $('#txtSearch').val());";
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