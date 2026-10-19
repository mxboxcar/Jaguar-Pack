{{-- _taobao/grid.php เดิม --}}
@use('App\Support\Legacy\Alerts')
@use('App\Support\Legacy\Format')
@use('App\Support\Legacy\Paging')

<div class="row">
	<div class="col-xs-12">
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
                            <th class="text-center" style="width: 120px;">{!! res('working_date') !!}</th>
                            <th class="text-center" >{!! res('remark_for_this_order') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('use_cash_cn_pay') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('using_system_cash') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('use_cash_cn_short') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
@if (count($data) !== 0)
@foreach ($data as $item)
@php $exp = explode(' ', (string) $item->create_date); @endphp
                        <tr>
                            <td><a href="{{ legacy_href() }}taobao/{{ $item->id }}">
                                {{ $prefix }}{{ $item->id }}</a></td>
                            <td class="text-center">{!! Format::getthaidate($exp[0], true) !!}<br />{!! Format::gettime($exp[1] ?? '') !!}</td>
                            <td class="text-left">
                                <input onblur="on_saveremark($(this))" onkeydown="javascript: if(event.keyCode == 13) on_saveremark($(this));" class="editable" type="text" value="{{ $item->remark }}" ref="{{ $item->id }}">
                            </td>
                            <td class="text-right">
@foreach ($details[$item->id] ?? [] as $dataIn)<div>¥{{ $dataIn->price }}@if ((int) $dataIn->status === 2) <i class="fa fa-check text-success"></i>@elseif ((int) $dataIn->status === 1) <i class="fa fa-circle-o-notch text-info"></i>@elseif ((int) $dataIn->status === -1) <i class="fa fa-close text-danger"></i>@endif </div>@endforeach
                            </td>
                            <td class="text-right">{!! Format::numberformat($item->usecash) !!}</td>
                            <td class="text-right">{!! Format::numberformat($item->usecash_cn, true, true, true) !!}</td>
                            <td class="text-center">
                            	<a href="{{ legacy_href() }}taobao/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
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
		$("#txtSearch").val($("#txtsearch").val());
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