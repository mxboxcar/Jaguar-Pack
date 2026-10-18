{{-- _admin/_tracking/grid.php เดิม (จำนวนหน้าโหลดแยกจาก load_paging) --}}
@php
    use App\Domain\Tracking\Php74;
    use App\Support\Legacy\Alerts;
    use App\Support\Legacy\Status;
    $st = $s[1] ?? null;
@endphp

<div class="row">
	<div class="col-lg-12">
		<div class="card-box">
			<div class="row m-t-10 m-b-10">
            	            	<div class="col-lg-3">
                    <div class="form-group contact-search m-b-30">
                    	<input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('tracking') !!}" value="{{ $s[0] ?? '' }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
            	</div>
            	<div class="col-lg-3">
                    <div class="form-group contact-search m-b-30">
                    	<input type="text" id="txtsearch2" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('order') !!}" value="{{ $s[2] ?? '' }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
            	</div>
            	<div class="col-lg-3">
                    <div class="form-group contact-search m-b-30">
                    	<input type="text" id="txtsearch3" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('customer_code') !!}" value="{{ $s[3] ?? '' }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
            	</div>
            	<div class="col-lg-3">
                    <select name="ship" class="form-control" id="ship" onchange="GenerateWhere()">
                        <option {!! Php74::eq($st, '') ? "selected='selected'" : '' !!} value="">- {!! res('all') !!} -</option>
                        <option {!! Php74::eq($st, 0) && Php74::neq($st, '') ? "selected='selected'" : '' !!} value="0">{!! res('car') !!}</option>
                        <option {!! Php74::eq($st, 1) ? "selected='selected'" : '' !!} value="1">{!! res('ship') !!}</option>
                    </select>
            	</div>
            </div>

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                                <th style="min-width: 230px;"> <div class="text-center" >{!! res('detail') !!}</div></th>
                                <th> <div class="text-center" >{!! res('customer_code') !!}</div></th>
                                <th> <div class="text-center" >{!! res('calculate_price') !!}</div></th>
                                <th style="min-width:150px;"> <div class="text-center" >{!! res('detail') !!}</div></th>
                                <th> <div class="text-center" >{!! res('shelf_manage') !!}</div></th>
                                <th style="width:70px;"> <div class="text-center" >{!! res('invoice') !!}</div></th>
                                <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                          </tr>
                    </thead>

                    <tbody>
                    @forelse ($items as $item)
                        @php
                            $tid = (int) $item->id;
                            $section = $sections[$tid] ?? null;
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
                                    @include('tracking.partials.order_links', ['links' => $orderIds[$tid] ?? []])
                                    <tr>
                                        <td class="text-right left">{!! res('tracking') !!} : </td>
                                        <td style="padding-left:3px" class="text-left">
                                        {{ Php74::eq($item->tracking, '') ? '-' : $item->tracking }}
                                       </td>
                                    </tr>
                                    <tr>
                                        <td class="text-right left">{!! res('lot_and_no') !!} : </td>
                                        <td style="padding-left:3px" class="text-left">
                                            {!! Status::getShipType($item->ship) !!} <a target='_blank' href='{{ legacy_href() }}admin/lot/{{ $item->lot_id }}'>{{ $item->lot_name }}</a>/{{ $item->no }}
                                        </td>
                                    </tr>
                                    @include('tracking.partials.flags', ['typeName' => $item->product_type_name])
                                    <tr>
                                        <td class="text-right left">{!! res('admin_remark') !!} : </td>
                                        <td style="padding-left:3px" class="text-left">
                                        {{ $quotes[$tid] ?? '' }}@isset($goods[$tid]) <a target='_blank' href='{{ legacy_href() }}admin/goodsconfirm/{{ $goods[$tid] }}'>{{ $goodsPrefix.$goods[$tid] }}</a>@endisset

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
                                <div style="padding: 0 10px 0 10px;">{{ $item->code }}</div>
                            </td>
                            <td>
@include('tracking.partials.prices', ['admin' => true])
                            </td>
                            

                            <td class="text-center boxcar-td" style="vertical-align: top;">
@include('tracking.partials.dates', ['admin' => true])
                            </td>

                            <td class="text-center boxcar-td" style="vertical-align:top">
                                @if (Php74::neq($item->payment_id, 0) || Php74::eq($item->payment_id, '-2') || Php74::eq($item->payment_id, '-3'))
ตัดออกจากโกดังแล้ว<br/>{{ $section }}
                                @else
{{ Php74::eq($section, '') ? '-' : $section }}
                                @endif
                            </td>

                            <td class="text-center boxcar-td" style="vertical-align:top">
                                @if (Php74::eq($item->payment_id, 0) || Php74::eq($item->payment_id, '-2'))
<i class='fa  fa-close text-danger' ></i>
                                @elseif (Php74::eq($item->payment_id, '-3'))
<i class='text-danger' >claim</i>
                                @else
<i class='fa fa-check-circle text-success' ></i><div><a href='{{ legacy_href() }}admin/payment/{{ $item->payment_id }}' target='_blank'>{{ $payPrefix.$item->payment_id }}</a></div>
                                @endif
                            </td>

                            <td class="text-center">
                                @if (Php74::eq($item->payment_id, 0) || Php74::eq($item->payment_id, -2))

                                @if (Php74::eq($item->payment_id, '-2'))
                                        <a type="button" href="#" onclick="Lock({{ $item->id }}, '0');return false;" class="table-action-btn"><i class="fa fa-unlock text-danger"></i></a>
                                @else
                                        <a type="button" href="#" onclick="Lock({{ $item->id }}, '-2');return false;" class="table-action-btn"><i class="fa  fa-lock text-danger"></i></a>
                                @endif
                                
                                @endif
                                <a href="{{ legacy_href() }}admin/tracking/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                                <a type="button" href="#" onclick="Delete({{ $item->id }});return false;" class="table-action-btn"><i class="md  md-cancel text-danger"></i></a>
                            </td>
                      </tr>
                    @empty
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
                    @endforelse
                    </tbody>
                </table>
                <div class="pull-right" id="paging">
                    กำลังจัดทำหน้า...
                </div>
            </div>
		</div>
        
    </div> <!-- end col -->
</div>

<input type="hidden" id="Checking" />
<input type="hidden" id="txtSearch" value="{{ $txtsearch }}">

<script>

    $.ajax({
                type: 'POST',
                async: true,
                data : 'txtsearch=' + $('#txtSearch').val() + '&page={!! Alerts::js(e($page)) !!}',
                url: rooturl + '{{ $ajaxpage }}/../load_paging',
                success: function(data) {
                    $("#paging").html(data);
                }
            });
    
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

    function Lock(id, value) {
        ConfirmBox(function() {
            $.ajax({
                type: 'POST',
                async: true,
                data : 'id=' + id + '&value=' + value,
                url: rooturl + '{{ $ajaxpage }}/../save_lock',
                success: function(data) {
                    eval(data);
                    loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());
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
		$("#txtSearch").val($("#txtsearch").val() + '|' + $('#ship').val() + '|' + $("#txtsearch2").val() + '|' + $("#txtsearch3").val());
	}
</script>
