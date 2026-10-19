{{-- _admin/_receipt/grid.php เดิม (SR เท่านั้น) — ลิงก์ไปหน้าใบเสร็จ/พิมพ์ของกลุ่ม reports (_admin/_export/receipt, print_receipt) --}}
@php
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Paging;
    $pg = preg_replace('/[^0-9A-Za-z.\-]/', '', (string) $page);
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

            </div>

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 125px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 125px;">{!! res('payment_id') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('customer_code') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('working_date') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('approved_date') !!}</th>
                            <th class="text-center" >{!! res('com_name') !!}</th>
                            <th class="text-center" >{!! res('amount') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($items as $item)
@php
    $rd = (string) ($item->receipt_date ?? '');
    $cd = (string) ($item->create_date ?? '');
    $ce = explode(' ', $cd);
@endphp
                        <tr>
                            <td><a href="{{ legacy_href() }}admin/export/receipt/{{ $item->id }}">{{ $item->receipt_id }}</a></td>
                            <td class="text-center"><a href="{{ legacy_href() }}admin/payment/{{ $item->id }}">{{ $prefix.$item->id }}</a></td>
                            <td class="text-center">{{ $item->code }}</td>
                            <td class="text-center">
                                {!! $rd === '0000-00-00 00:00:00' || $rd === '' ? '-<br />-' : Format::getthaidate(explode(' ', $rd)[0], true) !!}
                            </td>
                            <td class="text-center">
                                {!! $cd === '0000-00-00 00:00:00' || $cd === '' ? '-<br />-' : Format::getthaidate($ce[0], true).'<br />'.Format::gettime($ce[1] ?? '') !!}
                            </td>
                            <td class="text-left">{{ $item->receipt_name }}</td>
                            
                            <td class="text-right">{!! Format::numberformat($item->total) !!}</td>
                               
                            <td class="text-center">
                            	<a href="{{ legacy_href() }}admin/export/receipt/{{ $item->id }}" class="table-action-btn"><i class="fa fa-file-code-o text-primary"></i></a>
                                <a href="{{ legacy_href() }}_admin/_export/print_receipt/?id={{ $item->id }}" class="table-action-btn"><i class="fa fa-print text-primary"></i></a>
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
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ $pg }}',  $('#txtSearch').val());";
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
            fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ $pg }}',  $('#txtSearch').val());";
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
