{{-- _tracking/grid.php เดิม --}}
@php
    use App\Domain\Tracking\Php74;
    use App\Support\Legacy\Alerts;
    use App\Support\Legacy\Paging;
    use App\Support\Legacy\Status;
    $st = $status;
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

                <div class="col-xs-3 col-sm-3 col-lg-3">
                    <select name="status" class="form-control " id="status" onchange="GenerateWhere()">
                        <option  {!! Php74::eq($st, -1) ? "selected='selected'" : '' !!} value="-1">- {!! res('all') !!} -</option>
                        <option {!! Php74::eq($st, 0) && Php74::neq($st, '') ? "selected='selected'" : '' !!} value="0">{!! res('date_in') !!}</option>
                        <option {!! Php74::eq($st, 1) ? "selected='selected'" : '' !!} value="1">{!! res('date_out') !!}</option>
                        <option {!! Php74::eq($st, 2) ? "selected='selected'" : '' !!} value="2">{!! res('date_arrive') !!}</option>
                        <option {!! Php74::eq($st, 3) ? "selected='selected'" : '' !!} value="3">{!! res('no_payment') !!}</option>
                            
                    </select>
                </div>
            </div>

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                                <th  style="min-width: 230px;" > <div class="text-center">{!! res('detail') !!}</div></th>
                                <th> <div class="text-center" >{!! res('customer_code') !!}</div></th>
                                <th> <div class="text-center" >{!! res('calculate_price') !!}</div></th>
                                <th  style="min-width:150px;"> <div class="text-center" >{!! res('detail') !!}</div></th>
                                <th style="width:100px;"> <div class="text-center" >{!! res('attachment') !!}</div></th>
                                <th style="min-width:70px;"> <div class="text-center" >{!! res('invoice') !!}</div></th>
                                <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                          </tr>
                    </thead>

                    <tbody>
                    @forelse ($items as $item)
                        @php
                            $links = $orderIds[(int) $item->id] ?? [];
                            $orderLink = implode(' / ', array_map(fn ($o) => $orderPrefix.$o, $links));
                        @endphp
                        <tr>
                            <td>
                                <table  class="shipping_detail" style="margin: 0 auto 0 auto;">
                                    <tr>
                                        <td class="text-right left" style="vertical-align: top;">{!! res('order_number') !!} : </td>
                                        <td style="padding-left:3px" class="text-left">
                                            {{ Php74::eq($item->order, '') ? '-' : $item->order }}
                                        </td>
                                    </tr>
                                    @if ($orderLink !== '')
                                    <tr>
                                        <td class="text-right left" style="vertical-align: top;">{!! res('system_order_number') !!} : </td>
                                        <td style="padding-left:3px" class="text-left">
                                            {{ ' '.$orderLink }}
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td class="text-right left">{!! res('tracking') !!} : </td>
                                        <td style="padding-left:3px" class="text-left">
                                        {{ Php74::eq($item->tracking, '') ? '-' : $item->tracking }}
                                       </td>
                                    </tr>
                                    <tr>
                                        <td class="text-right left">{!! res('lot_and_no') !!} : </td>
                                        <td style="padding-left:3px" class="text-left">
                                            {!! Status::getShipType($item->ship) !!} {{ $item->lot_name }}/{{ $item->no }}
                                        </td>
                                    </tr>
                                    @include('tracking.partials.flags', ['typeName' => $item->protype_name, 'takeapic' => true])
                                    <tr>
                                        <td class="text-right left">{!! res('remark_for_this_order') !!} : </td>
                                        <td style="padding-left:3px" class="text-left">
                                        {{ $item->quote }}
                                       </td>
                                    </tr>
                                    <tr>
                                        <td class="text-right left">{!! res('remark') !!} : </td>
                                        <td style="padding-left:3px" class="text-left">
                                        {{ $item->remark }}
                                       </td>
                                    </tr>
                                </table>
                            </td>
                            <td style="vertical-align: top;text-align:center">
                                <div style="padding: 0 10px 0 10px;"><span class="">{{ $item->subuser }}</span></div>
                            </td>
                            <td>
@include('tracking.partials.prices', ['admin' => false])
                            </td>
                            

                            <td class="text-center boxcar-td" style="vertical-align: top;">
@include('tracking.partials.dates', ['admin' => false])
                            </td>

                            <td class="text-center boxcar-td" style="vertical-align:top">
@include('tracking.partials.images', ['list' => $images[(int) $item->id] ?? []])
                            </td>

                            <td class="text-center boxcar-td" style="vertical-align:top">
                                @if (Php74::eq($item->payment_id, 0) || Php74::eq($item->payment_id, '-2'))
<i class='fa  fa-close text-danger' ></i>
                                @elseif (Php74::eq($item->payment_id, '-3'))
<i class='text-danger' >claim</i>
                                @else
<i class='fa fa-check-circle text-success' ></i><div><a href='{{ legacy_href() }}payment/{{ $item->payment_id }}'>{{ $payPrefix.$item->payment_id }}</a></div>
                                @endif
                            </td>
                            
                            <td class="text-center">
                                <a href="{{ legacy_href() }}tracking/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                                @if (Php74::eq($item->lot_id, 0) && $orderLink === '')
                                <a type="button" href="#" onclick="Delete({{ $item->id }});return false;" class="table-action-btn"><i class="md  md-cancel text-danger"></i></a>
                                @endif
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

	function GenerateWhere() {
		$("#txtSearch").val($("#txtsearch").val() + '|' + $("#status").val());
	}
</script>
