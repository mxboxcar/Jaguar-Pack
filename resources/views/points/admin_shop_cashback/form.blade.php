{{-- _admin/_shop_cashback/form.php เดิม (สิทธิ์ admin_shop_cashback ตรวจที่ middleware legacy.perm) --}}
@extends('legacy.layout')
@php use App\Support\Legacy\Format; use App\Support\Legacy\Status; use App\Domain\Points\Grid; use App\Domain\Points\Php74; $no = $prefix.($data->id ?? ''); @endphp

@section('content')
<style>
.datepicker { padding:0px; }
</style>


<div class="col-sm-7">
	<h4 class="page-title">{!! res('shop_cashback_edit') !!} <small>{{ $no }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('shop_cashback') !!}
		</li>
		<li class="active">
			{{ $no }}
		</li>
	</ol>
</div>
<form action="{{ legacy_href() }}_admin/_shop_cashback/save" method="post">
	<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

		<div class="col-sm-5 pull-right">
			<div class="button-list">
				<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
				    <span class="btn-label"><i class="fa fa-save"></i></span>
				    {!! res('save_button') !!}
			    </button>

			    <a type="button" href="{{ legacy_href() }}admin/shop_cashback" class="pull-right btn btn-warning waves-effect waves-light">
				    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
				    {!! res('back_to_list') !!}
			    </a>
			</div>
		</div>

@include('points.partials.cashback_nav', ['base' => 'admin/'])

<div class="col-sm-12">
	<div class="card-box">
		<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
		<p class="text-muted font-13 m-b-30">
        </p>
		<div class="form-horizontal" role="form">
			<div class="form-group">
	            <label class="col-md-2 control-label"> {!! res('no_for_grid') !!}</label>
	            <div class="col-md-4 form-control-static">
	                {{ $no }}
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-2 control-label"> {!! res('customer_code') !!}</label>
	            <div class="col-md-4 form-control-static">
	                {{ $data->code ?? '' }}
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-2 control-label"> {!! res('remark_for_this_order') !!}</label>
	            <div class="col-md-4 form-control-static">
	                {{ $data->remark ?? '' }}
	            </div>
	        </div>

	        <div class="form-group">
	            <label class="col-md-2 control-label"> {!! res('admin_remark') !!}</label>
	            <div class="col-md-4">
	                <input type="text" name="remark_admin" class="form-control" value="{{ $data->remark_admin ?? '' }}">
	            </div>
	        </div>
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
						      				<th class="text-center">{!! res('working_date_shop_cashback') !!}</th>
						      				<th class="text-center" style="width:205px">{!! res('attachment_shop_cashback') !!}</th>
						      				<th class="text-center" style="width:70px">{!! res('price') !!}</th>
						      				<th class="text-center" style="width: 200px">{!! res('status') !!}</th>
						      			</tr>
						      		</thead>
						      		<tbody>
@foreach($details as $item)
<tr>
									      				<td class="no"></td>
									      				<td>{{ $item->order }}</td>
									      				<td class="text-center">{!! Grid::dateOrDash($item->submit_date) !!}</td>
									      				<td class="text-center">{!! Format::getFancyBox(e((string) $item->attachment)) !!}</td>
									      				<td class="text-right">{!! Format::numberformat($item->price, true, true, true) !!}</td>
@if(Php74::eq($item->status, 1))
<td class="text-center">
									      					<button onclick="on_cancel({{ (int) $item->id }})" type="button" class=" btn btn-xs btn-danger waves-effect waves-light">
				    										<span class="btn-label"><i class="fa fa-close"></i></span>{!! res('cancel') !!}</button>

									      					<button onclick="on_approved({{ (int) $item->id }})" type="button" class=" btn btn-xs btn-success waves-effect waves-light">
				    										<span class="btn-label"><i class="fa fa-save"></i></span>{!! res('paidd22') !!}</button>
									      				</td>
@else
<td class="text-left">
									      					{!! Status::getstatus($item->status) !!}@if(Php74::eq($item->status, -1))<span style='margin-top: 10px;' class='text-danger'>* {{ $item->cancel_remark }}</span>@endif

									      				</td>
@endif
</tr>
@endforeach
						      			
						      		</tbody>
						      		<thead>
						      				<th class="text-right" colspan="4" >{!! res('total_to_paid_back') !!}</th>
						      				<th class="text-right" id="total_price">{!! Format::numberformat($prices, true, true, true) !!}</th>
						      				<th></th>
						      		</thead>

						      		
						      	</table>
						                   
						    </div>
						</div>
					</div>


@include('points.partials.ledger', ['boxClass' => 'col-xs-12', 'cnRate' => false])
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
@if(($data->id ?? null) === null)
		CalculatePrice();
@endif

		initialDatepicker();
	});

	$("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	    }
	});


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
	            url: rooturl + '_admin/_shop_cashback/save_approved',
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
	            url: rooturl + '_admin/_shop_cashback/save_cancel',
	            success: function(data) {
	            	eval(data);
	            }
	        });
		});
	}

</script>
@endsection
