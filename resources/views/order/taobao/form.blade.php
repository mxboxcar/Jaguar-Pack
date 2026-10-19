{{-- _admin/_taobao/form.php เดิม (สิทธิ์ admin_taobao ที่ middleware; /admin/taobao/create = id 0) --}}
@extends('legacy.layout')
@php
    use App\Domain\Order\Html;
    use App\Domain\Order\Php74;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Status;
    $did = $data['id'] ?? '';
    $rate = $data['rate'] ?? '';
    $n = fn ($v) => Php74::num($v);
@endphp

@section('content')

<div class="col-sm-7">
	<h4 class="page-title">{!! res('taobao_edit') !!} <small>{{ $prefix }}{{ $did }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('taobao') !!}
		</li>
		<li class="active">
			{{ $prefix }}{{ $did }}
		</li>
	</ol>
</div>

<form action="{{ legacy_href() }}_admin/_taobao/save" method="post">
	<input type="hidden" name="id" value="{{ $did }}">

		<div class="col-sm-5 pull-right">
			<div class="button-list">
				<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
				    <span class="btn-label"><i class="fa fa-save"></i></span>
				    {!! res('save_button') !!}
			    </button>

			    <a type="button" href="{{ legacy_href() }}admin/taobao" class="pull-right btn btn-warning waves-effect waves-light">
				    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
				    {!! res('back_to_list') !!}
			    </a>
			</div>
		</div>

<div class="col-sm-12">
	<div class="button-list text-center">
		<a type="button" href="{{ legacy_href() }}admin/taobao" class=" btn btn-primary  waves-effect waves-light">
		    <span class="btn-label"><i class="fa  fa-list-ol"></i></span>
		    {!! res('taobao') !!}
	    </a>
	    <a type="button" href="{{ legacy_href() }}admin/shop_cashback" class=" btn btn-inverse  waves-effect waves-light">
		    <span class="btn-label"><i class="fa  fa-undo"></i></span>
		    {!! res('shop_cashback') !!}
	    </a>
	    <a type="button" href="{{ legacy_href() }}admin/balance_cn" class=" btn btn-inverse  waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-cny"></i></span>
		    {!! res('balance_cn') !!}
	    </a>
	</div>
</div>

					<div class="col-sm-12">
						<div class="card-box">
							<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
							<p class="text-muted font-13 m-b-30">
					        </p>
							<div class="form-horizontal" role="form">
								@if ($id !== 0)
								<div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('no_for_grid') !!}</label>
						            <div class="col-md-4 form-control-static">
						                {{ $prefix }}{{ $did }}
						            </div>
						        </div>

						        <div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('customer_code') !!}</label>
						            <div class="col-md-4 form-control-static">
						                {!! Html::h($data['code'] ?? '') !!}
						            </div>
						        </div>

						        <div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('taobao_id') !!}</label>
						            <div class="col-md-4 form-control-static">
						                {!! Html::h($data['taobao_name'] ?? '') !!}
						            </div>
						        </div>

						        <div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('taobao_password') !!}</label>
						            <div class="col-md-4 form-control-static">
						                {!! Html::h($data['taobao_password'] ?? '') !!}
						            </div>
						        </div>

						        <div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('remark_for_this_order') !!}</label>
						            <div class="col-md-4 form-control-static">
						                {!! Html::h($data['remark'] ?? '') !!}
						            </div>
						        </div>

						        <div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('admin_remark') !!}</label>
						            <div class="col-md-4">
						                <input type="text" name="remark_admin" class="form-control" value="{!! Html::h($data['remark_admin'] ?? '') !!}">
						            </div>
						        </div>

						        @else

						        <div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('taobao_id') !!}</label>
						            <div class="col-md-4">
						                <input type="text" name="taobao_name" class="form-control" value="">
						            </div>
						            
						        </div>

						        <div class="form-group">
						            <label class="col-md-2 control-label"> {!! res('taobao_password') !!}</label>
						            <div class="col-md-4">
						                <input type="password" name="taobao_password" class="form-control" value="">
						            </div>
						        </div>
						        <div class="form-group" >

						        </div>
						        @endif                         
						    </div>
						</div>
					</div>

					<div class="col-sm-12">
						<div class="card-box">
							<h4 class="m-t-0 header-title"><b>{!! res('list') !!}</b></h4>
							<p class="text-muted font-13 m-b-30">
					        </p>
							<div class="form-horizontal" role="form">
								      
						        <table id="data_table" class="table  table-bordered m-t-20">
						      		<thead>
						      			<tr>
						      				<th class="text-center" style="width:10px">{!! res('no') !!}</th>
						      				<th class="text-center">{!! res('order') !!}</th>
						      				<th class="text-center" style="width: 70px;">{!! res('record_by') !!}</th>
						      				<th class="text-center" style="width: 130px;">{!! res('working_date') !!}</th>
						      				<th class="text-center" style="width:70px">{!! res('price') !!}</th>
						      				<th class="text-center" style="width: 200px">{!! res('status') !!}</th>
						      			</tr>
						      		</thead>
						      		<tbody>
@foreach ($details as $item)@php $exp = explode(' ', (string) $item->mod_date); @endphp<tr>
									      				<td class="no"></td>
									      				<td >{!! Html::h($item->order) !!}</td>
									      				<td class="text-center">{!! Html::h($item->code) !!}</td>
									      				<td class="text-center">{!! Format::getthaidate($exp[0]) !!}<br/>{{ $exp[1] ?? '' }}</td>
									      				<td class="text-right">{!! Format::numberformat($item->price, true, true, true) !!}</td>
									      				@if (Php74::eq($item->status, 1))<td class="text-center">
									      					<button onclick="on_cancel({{ $item->id }})" type="button" class=" btn btn-xs btn-danger waves-effect waves-light">
				    										<span class="btn-label"><i class="fa fa-close"></i></span>{!! res('cancel') !!}</button>

									      					<button onclick="on_approved({{ $item->id }})" type="button" class=" btn btn-xs btn-success waves-effect waves-light">
				    										<span class="btn-label"><i class="fa fa-save"></i></span>{!! res('paidd22') !!}</button>
									      				</td>@else<td class="text-left">
									      					{!! Status::getstatus($item->status) !!}@endif
@if (Php74::eq($item->status, -1))
							            			<button onclick="rollback({{ $item->id }})" type="button" class=" btn btn-xs btn-info waves-effect waves-light">
													    RollBack
												    </button>
												    <span style="margin-top: 10px;" class="text-danger">* {!! Html::h($item->cancel_remark) !!}</span>@endif
@if (Php74::eq($item->status, 2))
							            			<button onclick="rollback({{ $item->id }})" type="button" class=" btn btn-xs btn-info waves-effect waves-light">
													    RollBack
												    </button>@endif
</td></tr>@endforeach
						      			
						      		</tbody>

						      		<thead>
						      			<th class="text-right" colspan="4" >{!! res('cn_cash') !!}</th>
						      			<th class="text-right" colspan="4" id="total_order_cal">{{ $agg->sum ?? '' }} x {{ $rate }} = {!! Format::numberformat($n($agg->sum ?? null) * $n($rate)) !!}</th>
						      		</thead>
						      		<thead>
						      				<th class="text-right" colspan="4" >{!! res('cash_cn') !!}</th>
						      				
						      				<th class="text-right" colspan="4"  id="total_price_thai">{{ $data['usecash_cn'] ?? '' }} x {{ $rate }} = -{!! Format::numberformat($n($data['usecash_cn'] ?? null) * $n($rate)) !!}</th>
						      		</thead>
						      		<thead>
						      				<th class="text-right" colspan="4" >{!! res('total_service') !!}</th>
						      				
						      				<th class="text-right" colspan="4"  id="total_price_thai">{{ $agg->count ?? '' }} x {{ $data['charge'] ?? '' }} = {!! Format::numberformat($n($data['charge'] ?? null) * $n($agg->count ?? null)) !!}</th>
						      		</thead>
						      		<thead>
						      				<th class="text-right" colspan="4" >{!! res('total_to_paid') !!}</th>
						      				<th class="text-right" colspan="4"   id="total_price">{!! Format::numberformat($data['usecash'] ?? null) !!}</th>
						      		</thead>
						      	</table>                 
						    </div>
						</div>
					</div>



					<input type="hidden" name="id" value="{{ $id }}">
					<div class="col-xs-6">
				    <div class="card-box">
				    <h4 class="m-t-0 header-title"><b>{!! res('payment_thing') !!}</b></h4>
				    <p class="text-muted font-13 m-b-30">
				    </p>
				    	<div class="form-horizontal" role="form">
				    		<div class="form-group">
					            <label class="col-md-4 control-label"> {!! res('no_for_grid') !!}</label>
					            <div class="col-md-7 form-control-static">
					            	{{ $prefix }}{{ $did }}
					            </div>
					        </div>
				            <div class="form-group">
					            <label class="col-md-4 control-label"> {!! res('total_have_to_paided') !!}</label>
					            <div class="col-md-7 form-control-static">
					            	<span class=" total_net addcomma currency">{{ $data['usecash'] ?? '' }}</span> {!! res('thai_currency') !!}
					            </div>
					        </div>
					        <div class="form-group">
					            <label class="col-md-4 control-label"> {!! res('use_cash_cn_short') !!}</label>
					            <div class="col-md-7 form-control-static">{!! Format::numberformat($data['usecash_cn'] ?? null, true, true, true) !!}
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
					            	{!! Html::nl2br($data['submit_remark'] ?? '') !!}
					            </div>
					        </div>
				        </div>
				    </div>
				</div>
				
				


<div class="col-xs-6">
				        <div class="card-box">
				            <h4 class="m-t-0 header-title"><b>{!! res('ref_balance_list') !!}</b></h4>
				            <p class="text-muted font-13 m-b-30">
				            </p>
				            <table class="table table-striped table-bordered">
				                    <thead>
				                        <tr>
				                            <th class="text-center" style="width: 130px;">{!! res('working_date') !!}</th>
				                            <th class="text-center">{!! res('detail') !!}</th>
				                            <th class="text-center" style="width: 80px;">{!! res('amount') !!}</th>
				                        </tr>
				                    </thead>
				                    <tbody>@if (count($balances) !== 0)@foreach ($balances as $item)@php $exp = explode(' ', (string) $item->create_date); $cn = $item->types === 'balance_cn'; @endphp<tr><td>{!! Format::getthaidate($exp[0]) !!}<br/>{{ $exp[1] ?? '' }}</td><td class="text-left"><a href="{{ legacy_href() }}{!! Html::h($item->link) !!}{{ $item->prefix_ref_id }}">{!! Html::h($item->detail) !!}</a>@if ($cn) ({!! Format::numberformat($n($item->amount) * $n($rate)) !!})@endif</td><td class="text-right">@if ($n($item->amount) >= 0)<span class="text-success">+{!! Format::numberformat($item->amount, true, true, $cn) !!}</span>@else<span class="text-danger">{!! Format::numberformat($item->amount, true, true, $cn) !!}</span>@endif</td>@endforeach @else<tr><td colspan="3" class="text-center">{!! res('no_data') !!}</td></tr>@endif

				                </tbody>
				            </table>
				        </div>
				    </div>    
</form>
<div id="panel-modal" class="cancel_remark_form modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xs">
        <div class="modal-content p-0 b-0">
            <div class="panel panel-color panel-primary">
                <div class="panel-heading"> 
                    <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
                    <h3 class="panel-title">{!! res('cancel_this') !!}</h3> 
                </div> 
                <div class="panel-body"> 
                	<input type="hidden" id="cancel_id" />
	                <textarea class="form-control" id="cancel_remark"></textarea>
                </div> 
                <div class="panel-footer text-right">
                    <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="canceling2();" type="button">{!! res('ok') !!}</button>
                </div>
                
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script>

	$(function() {
		on_makeno();
	})

	function sumiting() {
		if($("form").validationEngine('validate') == false) 
		{ 
			CloseLoading(); 
		} else {
			ConfirmBox(function () {
				$("form").eq(0).submit();
			});
		}
	}


	$("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	    }
	});

	function on_makeno() {
		var count = 1;
		$("#data_table tbody tr").each(function() {
			$(this).find('td').eq(0).text(count + ".");
			count++;
		});
	}

	function on_approved(id) {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id=' + id,
	            url: rooturl + '_admin/_taobao/save_approved',
	            success: function(data) {
	            	eval(data);
	            }
	        });
        });
	}

	function on_cancel(id) {
		$("#cancel_id").val(id);
		$(".cancel_remark_form").modal('show');
	}

	function canceling2() {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id='+ $("#cancel_id").val() +'&cancel_remark=' + $("#cancel_remark").val(),
	            url: rooturl + '_admin/_taobao/save_cancel',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}

	function rollback(id) {
		ConfirmBox(function () {
			$.ajax({
	            type: 'POST',
	            async: true,
	            data : 'id=' + id,
	            url: rooturl + '_admin/_taobao/rollback',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}

</script>
@endsection
