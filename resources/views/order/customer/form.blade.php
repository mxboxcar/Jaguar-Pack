{{-- _order/form.php เดิม (ไม่ใช่ใบของตัวเอง → getPermissionAlert_Page ที่ controller) --}}
@extends('legacy.layout')
@php
    use App\Domain\Order\Html;
    use App\Domain\Order\Php74;
    use App\Support\Legacy\Alerts;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $st = $data['status'];
@endphp

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('order') !!} <small>{{ $prefix }}{{ $data['id'] }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('order') !!}
		</li>
		<li class="active">
			{{ $prefix }}{{ $data['id'] }}
		</li>
	</ol>
</div>

<div class="col-sm-5 pull-right">
		<div class="button-list">
@if (Php74::eq($st, 0) && Php74::eq($data['is_lock'], 0))
<button onclick="cancel()" type="button" class="pull-right btn btn-danger waves-effect waves-light">
						    <span class="btn-label"><i class="fa fa-close"></i></span>
						    {!! res('cancel_this') !!}
					    </button>
					    <script>
					    function cancel() {
					    	ConfirmBox(function() {
					    		OpenLoading();
					    		$.ajax({
						            type: 'POST',
						            async: true,
						            data : 'id={{ $data['id'] }}',
						            url: rooturl + '_order/delete',
						            success: function(data) {
						            	eval(data);
						            }
						        });
					    	})
					    }
					    </script>
@endif
@if (Php74::eq($st, 1))
<button onclick="submitpayment()" type="button" class="pull-right btn btn-success waves-effect waves-light">
							<span class="btn-label"><i class="fa fa-dollar"></i></span>{!! res('submit_payment') !!}
						</button>
@endif

			

		    <a type="button" href="{{ legacy_href() }}order" class="pull-right btn btn-warning waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
			    {!! res('back_to_list') !!}
		    </a>
		</div>
	</div>

<div class="col-xs-12 col-md-6">
	<div class="card-box">
	<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
	<p class="text-muted font-13 m-b-30">
    </p>
    <div class="form-horizontal" role="form">
    	<div class="form-group">
            <label class="col-md-4 control-label"> {!! res('no_for_grid') !!}</label>
            <div class="col-md-7 form-control-static">
                    {{ $prefix }}{{ $data['id'] }}
            </div>
        </div>    
        <div class="form-group">
            <label class="col-md-4 control-label"> Reference</label>
            <div class="col-md-7 form-control-static">
                    {!! Html::h($data['code']) !!}#{{ $data['id'] }}{!! Php74::eq($data['ship'], 1) ? '(SEA)' : '' !!}
            </div>
        </div>   
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('shipping_type') !!}</label>
            <div class="col-md-7 form-control-static">
                {!! Status::getShipType($data['ship']) !!}
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('status') !!}</label>
            <div class="col-md-7 form-control-static">
                {!! Status::getstatusorder($st) !!}@if (Php74::eq($st, -1))<div style='margin-top: 10px;' class='text-danger'>* {!! Html::h(explode(',ยกเลิกโดย', (string) $data['cancel_remark'])[0]) !!}</div>@endif
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('this_order_rate') !!}</label>
            <div class="col-md-4 form-control-static">
                    {!! res('cn_currency') !!}1 <i class='fa fa-arrows-h'></i> {!! Format::numberformat($data['exchange_rate']) !!}
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-4 control-label"> {!! res('remark_for_this_order') !!}</label>
            <div class="col-md-7 ">
                    <input type="text" onblur="onsavequote($(this))" class="form-control" id="quote" value="{!! Html::h($data['quote']) !!}" />
            </div>
        </div>
    </div>
    </div>
</div>

@if (Php74::eq($st, 0))
<div class="col-xs-12 col-md-6">
	    <div class="card-box">
	    <h4 class="m-t-0 header-title"><b><span class="text-danger">{!! res('remark') !!}</span></b></h4>
	    <p class="text-muted font-13 m-b-30">
	    </p>
	        <div class="text-left">
	            1. {!! res('order_remark_status_1') !!}
	        </div>
	    </div>
	</div>
@endif
@if (Php74::eq($st, 1))
<form action="{{ legacy_href() }}_order/save_payment" method="post">
		<input type="hidden" name="id" value="{{ $data['id'] }}">
		<div class="col-xs-12 col-md-6">
	    <div class="card-box">
	    <h4 class="m-t-0 header-title"><b>{!! res('payment_thing') !!}</b></h4>
	    <p class="text-muted font-13 m-b-30">
	    </p>
	    	<div class="form-horizontal" role="form">
	            <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('total_have_to_paid') !!}</label>
		            <div class="col-md-7 form-control-static">{!! Format::numberformat($data['total']) !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('your_balance') !!}</label>
		            <div class="col-md-7 form-control-static">{!! Format::numberformat($cash) !!}
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('payment_typing') !!}</label>
		            <div class="col-md-7 form-control-static">
		            	<span class="label label-inverse">{!! res('using_system_cash') !!}</span>
		            </div>
		        </div>
		        <div class="form-group">
		            <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
		            <div class="col-md-7 form-control-static">
		            	<textarea class="form-control" name="submit_remark" rows="5"></textarea>
		            </div>
		        </div>
	        </div>
	    </div>
	</div>
	</form>
@endif
@if (Php74::num($st) >= 2)
<div class="col-xs-12 col-md-6">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('ref_balance_list') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
	        <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 190px;">{!! res('working_date') !!}</th>
                            <th class="text-center">{!! res('detail') !!}</th>
                            <th class="text-center" style="width: 140px;">{!! res('amount') !!}</th>
                        </tr>
                    </thead>
                    <tbody>@if (count($balances) !== 0)@foreach ($balances as $item)@php $exp = explode(' ', (string) $item->create_date); @endphp<tr><td>{!! Format::getthaidate($exp[0]) !!}<br />{{ $exp[1] ?? '' }}</td><td class="text-left"><a target="_blank" href="{{ legacy_href() }}{!! Html::h($item->link) !!}{{ $item->prefix_ref_id }}">{!! Html::h($item->detail) !!}</a></td><td class="text-right">@if (Php74::num($item->amount) >= 0)<span class="text-success">+{!! Format::numberformat($item->amount) !!}</span>@else<span class="text-danger">{!! Format::numberformat($item->amount) !!}</span>@endif</td>@endforeach @else<tr><td colspan="3" class="text-center">{!! res('no_data') !!}</td></tr>@endif

				</tbody>
			</table>
		</div>
	</div>
@endif


<div class=" col-xs-12">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('order_detail') !!}</b></h4>
		<p class="text-muted font-13 m-b-30">
	    </p>
	    
{!! $detailHtml !!}

		    <!-- </tbody>
		    <tfoot>
		    	<th>
		    		<td colspan="4" class="text-right">{!! res('total') !!}</td>
		    		<td class="text-right">{{ $tail[0] === null ? '' : \App\Domain\Order\Php74::str(\App\Domain\Order\Php74::num($tail[0])) }}</td>
		    		<td class="text-right">{!! Format::numberformat($tail[1], true, true, true) !!}</td>
		    		<td class="text-right">{!! Format::numberformat($tail[2]) !!}</td>
		    	</th>
		    </tfoot>
		</table> -->
	</div>
</div>

<script>
var freagment = '{!! Html::js($div) !!}';
$(function() {
	Switcher(true);
	setTimeout(function() { $(window).scrollTop($('#' + freagment).offset().top - 60); }, 500);
});


$("form").validationEngine();
$("form").ajaxForm({ 
    success: function(data) {
        eval(data);
    }
});

function submitpayment() {
	if($("form").eq(0).validationEngine('validate') == false) 
	{ 
		CloseLoading(); 
	} else {
		ConfirmBox(function () {
			$("form").eq(0).submit();
		});
	}
}

function onsavequote(ele) {
	$.ajax({
        type: 'POST',
        async: true,
        data : 'id={{ $id }}&quote=' + ele.val(),
        url: rooturl + '_order/save_quote',
        success: function(data) {
            
        }
    });
}

function save_quote(ele, id) {
	$.ajax({
        type: 'POST',
        async: true,
        data : 'id=' + id + '&value=' + ele.val(),
        url: rooturl + '_order/save_quote_tracking',
        success: function(data) {
            
        }
    });
}
</script>
@endsection
