{{-- _lot/grid.php เดิม --}}
@php
    use App\Domain\Lot\Php74;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Paging;
    use App\Support\Legacy\Status;
    $date = function ($v) {
        $v = (string) $v;
        return ($v === '0000-00-00 00:00:00' || $v === '') ? '-' : Format::getthaidate(explode(' ', $v)[0]);
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

            </div>

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" >{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 120px;">{!! res('date_in') !!}</th>
                            <th class="text-center" style="width: 120px;">{!! res('date_out') !!}</th>
                            <th class="text-center" style="width: 120px;">{!! res('date_arrive') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('amount') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('status') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td><a target="_blank" href="{{ legacy_href() }}lot/{{ $item->id }}">
                            @if (Php74::neq($item->name, ''))
{!! Status::getShipType($item->type) !!} {{ $item->name }}@endif </a></td>
                            <td class="text-left">
                                {!! $date($item->date_in) !!}
                            </td>
                            <td class="text-left">
                                {!! $date($item->date_out) !!}
                            </td>
                            <td class="text-left">
                                {!! $date($item->date_arrive) !!}
                            </td>
                            <td class="text-center">{!! Format::numberformatzero($item->count) !!}</td>
                            <td class="text-center">{!! Status::getlotstatus($item->status) !!}</td>
                            <td class="text-center">
                            	<a href="{{ legacy_href() }}lot/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
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
			ErrorBox('{!! res("add_more_then_one") !!}');
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
		$("#txtSearch").val($("#txtsearch").val());
	}
</script>
