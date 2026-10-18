{{-- _admin/_taobao/grid.php เดิม --}}
@php
    use App\Domain\Order\Html;
    use App\Domain\Order\Php74;
    use App\Support\Legacy\Alerts;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Paging;
    $sel = fn ($v, $x) => Php74::eq($v, $x) ? "selected='selected'" : '';
@endphp


<div class="row">
	<div class="col-xs-12">
		<div class="card-box">
			<div class="row m-t-10 m-b-10">
            	<div class="col-sm-6 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                    	<input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{!! Html::h($search) !!}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
            	</div>

                <div class="col-xs-2 col-sm-4 col-lg-4">
                    <select name="role" class="form-control " id="status" onchange="GenerateWhere()">
                        <option {!! $sel($status, '-2') !!} value="-2">- {!! res('all') !!} -</option>  
                        <option {!! $sel($status, '-1') !!} value="-1">{!! res('cancel') !!}</option>    
                        <option {!! $sel($status, '0') !!} value="0">{!! res('no_paid') !!}</option>   
                        <option {!! $sel($status, '1') !!} value="1">{!! res('wait_for_approve') !!}</option>   
                        <option {!! $sel($status, '2') !!} value="2">{!! res('paid') !!}</option>   
                    </select>
                </div>
            </div>

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 120px;">{!! res('working_date') !!}</th>
                            <th class="text-center" style="width: 120px;">{!! res('customer_code') !!}</th>
                            <th class="text-center" >{!! res('admin_remark') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('use_cash_cn_pay') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('using_system_cash') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('use_cash_cn_short') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @if (count($items) !== 0)
                        @foreach ($items as $item)
                        @php $exp = explode(' ', (string) $item->create_date); @endphp
                        <tr>
                            <td><a target="_blank" href="{{ legacy_href() }}admin/taobao/{{ $item->id }}">
                                {{ $prefix }}{{ $item->id }}</a></td>
                            <td class="text-center">{!! Format::getthaidate($exp[0], true) !!}<br />{!! Format::gettime($exp[1] ?? '') !!}</td>
                            <td class="text-center">{!! Html::h($item->code) !!}</td>
                            <td class="text-left">
                                <input onblur="on_saveremark($(this))" onkeydown="javascript: if(event.keyCode == 13) on_saveremark($(this));" class="editable" type="text" value="{!! Html::h($item->remark_admin) !!}" ref="{{ $item->id }}">
                            </td>
                            <td class="text-right">
                                @foreach ($prices[(int) $item->id] ?? [] as $d)<div>¥{{ $d->price }}{!! Php74::eq($d->status, 2) ? ' <i class="fa fa-check text-success"></i>' : (Php74::eq($d->status, 1) ? ' <i class="fa fa-circle-o-notch text-info"></i>' : (Php74::eq($d->status, -1) ? ' <i class="fa fa-close text-danger"></i>' : '')) !!} </div>@endforeach

                            </td>
                            <td class="text-right">{!! Format::numberformat($item->usecash) !!}</td>
                            <td class="text-right">{!! Format::numberformat($item->usecash_cn, true, true, true) !!}</td>
                            <td class="text-center">
                            	<a href="{{ legacy_href() }}admin/taobao/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    @else
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
                    @endif
                    </tbody>
                </table>
                <div class="pull-right">
{!! Paging::getpaging($page, $rows, 10, $ajaxpage) !!}
                </div>
            </div>
		</div>
        
    </div> <!-- end col -->
</div>

<input type="hidden" id="Checking" />
<input type="hidden" id="txtSearch" value="{!! Html::h($txtsearch) !!}">

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
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{!! Html::jsNested($page) !!}',  $('#txtSearch').val());";
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
            fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{!! Html::jsNested($page) !!}',  $('#txtSearch').val());";
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
</script>