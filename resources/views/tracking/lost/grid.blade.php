{{-- _lost_package/grid.php / _admin/_lost_package/grid.php เดิม --}}
@php
    use App\Domain\Tracking\Php74;
    use App\Support\Legacy\Alerts;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Paging;
    use App\Support\Legacy\Status;
    $st = $status;
    $base = $admin ? 'admin/' : '';
    $sel = fn ($v) => Php74::eq($st, $v) ? "selected='selected'" : '';
    $dt = function ($v) {
        if (Php74::eq($v, '0000-00-00 00:00:00') || Php74::eq($v, '')) {
            return '-<br />-';
        }
        $exp = explode(' ', (string) $v);
        return Format::getthaidate($exp[0], true).'<br />'.Format::gettime($exp[1] ?? null);
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
                        <option {!! $sel('-2') !!} value="-2">- {!! res('all') !!} -</option>  
                        <option {!! $sel('-1') !!} value="-1">{!! res('cancel') !!}</option>    
                        <option {!! $sel('0') !!} value="0">{!! res('no_paid') !!}</option>   
                        <option {!! $sel('1') !!} value="1">{!! res('wait_for_approve') !!}</option>   
                        <option {!! $sel('2') !!} value="2">{!! res('paid') !!}</option>   
                    </select>
                </div>
            </div>

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('working_date') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('approved_date') !!}</th>
@if ($admin)
                            <th class="text-center" style="width: 93px;">{!! res('order') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('customer_code') !!}</th>
                            <th class="text-center" style="width: 105px;">{!! res('admin_remark') !!}</th>
@else
                            <th class="text-center">{!! res('order') !!}</th>
@endif
                            <th class="text-center">{!! res('detail') !!}</th>
                            <th class="text-center">{!! res('amount') !!}</th>
                            <th class="text-center" >{!! res('attachment') !!}</th>
                            <th class="text-center" style="width: 92px;">{!! res('status') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td><a href="{{ legacy_href() }}{{ $base }}lost_package/{{ $item->id }}">{{ $prefix.$item->id }}</a></td>
                            <td class="text-center">
                                {!! $dt($item->create_date) !!}
                            </td>
                            <td class="text-center">
                                {!! $dt($item->approved_date) !!}
                            </td>
@if ($admin)
                            {{-- แอดมินมีช่องหมายเหตุแอดมิน (lost_package.admin_quote) แก้ในตาราง → save_remark --}}
                            <td class="text-left"><a href="{{ legacy_href() }}{{ $base }}order/{{ $item->order_id }}" >{{ $orderPrefix.$item->order_id }}</a></td>
                            <td class="text-center">{{ $item->code }}</td>
                            <td class="text-center">
                                <input onblur="on_saveremark($(this))" onkeydown="javascript: if(event.keyCode == 13) on_saveremark($(this));" class="editable" type="text" value="{{ $item->admin_quote }}" ref="{{ $item->id }}">
                            </td>
                            <td class="text-left">{{ $item->submit_remark }}</td>
@else
                            <td class="text-left"><a href="{{ legacy_href() }}{{ $base }}order/{{ $item->order_id }}" >{{ $orderPrefix.$item->order_id }}</a></td>
                            <td class="text-left">{{ $item->submit_remark }}</td>
@endif
                            <td class="text-right">{!! Format::numberformat($item->cashback, true, true, true) !!}</td>
                            <td class="text-center">
                            {!! Format::getFancyBox(isset($images[(int) $item->id]) ? e($images[(int) $item->id]) : null) !!}</td>
                            <td class="text-center">{!! Status::getstatus($item->status) !!}</td>
                            <td class="text-center">
                            	<a href="{{ legacy_href() }}{{ $base }}lost_package/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
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
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{!! Alerts::js(e($page)) !!}',  $('#txtSearch').val());";
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
            fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{!! Alerts::js(e($page)) !!}',  $('#txtSearch').val());";
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

@if ($admin)
    function on_saveremark(ele) {
        OpenLoading();
        var vals = ele.val();
        var ids = ele.attr('ref');

        $.ajax({
            type: 'POST',
            async: true,
            data : 'id=' + ids + '&remark=' + vals,
            url: rooturl + '{{ $ajaxpage }}/../save_remark',
            success: function(data) {
                eval(data);
            }
        });
    }

@endif
	function GenerateWhere() {
		$("#txtSearch").val($("#txtsearch").val() + '|' + $("#status").val());
	}
</script>
