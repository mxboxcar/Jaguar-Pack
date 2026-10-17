{{-- _admin/_role/form.php เดิม (สิทธิ์ admin_role ตรวจที่ middleware) --}}
@php($eq = [\App\Domain\Users\Php74::class, 'eq'])
@extends('legacy.layout')

@section('content')
@if ($id === 0)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('role') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('role') !!}
		</li>
		<li class="active">
			{!! res('role_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('role_edit') !!} <small>{{ $prefix }}{{ $data->id ?? '' }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('role') !!}
		</li>
		<li class="active">
			{{ $prefix }}{{ $data->id ?? '' }}
		</li>
	</ol>
</div>
@endif

<form action="{{ legacy_href() }}_admin/_role/save" method="post">

<input type="hidden" name="id" value="{{ $data->id ?? '' }}">

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			<button onclick="sumiting()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>

		    <a type="button" href="{{ legacy_href() }}admin/role" class="pull-right btn btn-warning waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
			    {!! res('back_to_list') !!}
		    </a>
		</div>
	</div>


	<div class="col-sm-6">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('permission') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">                                    
		        <div class="form-group">
		        	@if (! $eq($id, ''))
					<div class="form-group">
			            <label class="col-md-4 control-label"> {!! res('no_for_grid') !!}</label>
			            <div class="col-md-7  form-control-static">
			                {{ $prefix }}{{ $data->id ?? '' }}
			            </div>
			        </div>         
			        @endif    
		            <label class="col-md-4 control-label">{!! res('role_name') !!}</label>
		            <div class="col-md-7">
		                <input type="text" name="name" class="form-control validate[required]" value="{{ $data->role_name ?? '' }}" >
		            </div>
		        </div>
		    </div>
		</div>
	</div>


	<div class="col-sm-12">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
			<div style="width:55%;float:left;padding:10px;">{!! res('all_module_list') !!}</div>
			<div style="width:45%;float:left;padding:10px;">{!! res('choosen_list') !!}</div>
			<p class="text-muted font-13 m-b-30">
	        </p>
				<div class="form-horizontal" role="form">                                    
			        <select name="role[]" class="multi-select" multiple="multiple" id="my_multi_select3" >
@foreach ($modules as $row)@if ($active->has((string) $row->id))<option selected="selected" value="{{ $row->id }}">{!! res((string) $row->role_detail_name) !!}</option>@else<option value="{{ $row->id }}">{!! res((string) $row->role_detail_name) !!}</option>@endif @endforeach                        
	                </select>
				</div>
		    </div>
		</div>
	</div>

</form>

<script>
	function sumiting() {
		if($("form").eq(0).validationEngine('validate') == false) 
		{ 
			CloseLoading(); 
		} else {
			ConfirmBox(function () {
				$("form").eq(0).submit();
			});
		}
	}



	$(function() {
		$('#my_multi_select3').multiSelect({
	        selectableHeader: "<input type='text' class='form-control search-input' autocomplete='off' placeholder='{!! res('search') !!}'>",
	        selectionHeader: "<input type='text' class='form-control search-input' autocomplete='off' placeholder='{!! res('search') !!}'>",
	        afterInit: function (ms) {
	            var that = this,
	                $selectableSearch = that.$selectableUl.prev(),
	                $selectionSearch = that.$selectionUl.prev(),
	                selectableSearchString = '#' + that.$container.attr('id') + ' .ms-elem-selectable:not(.ms-selected)',
	                selectionSearchString = '#' + that.$container.attr('id') + ' .ms-elem-selection.ms-selected';

	            that.qs1 = $selectableSearch.quicksearch(selectableSearchString)
	                .on('keydown', function (e) {
	                    if (e.which === 40) {
	                        that.$selectableUl.focus();
	                        return false;
	                    }
	                });

	            that.qs2 = $selectionSearch.quicksearch(selectionSearchString)
	                .on('keydown', function (e) {
	                    if (e.which == 40) {
	                        that.$selectionUl.focus();
	                        return false;
	                    }
	                });
	        },
	        afterSelect: function () {
	            this.qs1.cache();
	            this.qs2.cache();
	        },
	        afterDeselect: function () {
	            this.qs1.cache();
	            this.qs2.cache();
	        }
	    });
	});

	
	$("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	    }
	});
</script>
@endsection
