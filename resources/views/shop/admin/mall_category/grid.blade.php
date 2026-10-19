{{-- _admin/_mall_category/grid.php เดิม --}}
@use('App\Support\Legacy\Alerts')
@use('App\Support\Legacy\Format')
@use('App\Support\Legacy\Paging')

<div class="row">
	<div class="col-lg-12">
		<div class="card-box">
			<div class="row m-t-10 m-b-10">
            	<div class="col-sm-6 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                    	<input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{{ $txtSearch[0] }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
            	</div>

                <!-- <div class="col-sm-6 col-lg-4">
                    <div class="h5 m-0">
                        <span class="vertical-middle">Sort By:</span>
                        <div class="btn-group vertical-middle" data-toggle="buttons">
                             <label class="btn btn-white btn-md waves-effect active">
                                <input type="radio" autocomplete="off" checked=""> Status
                             </label>
                             <label class="btn btn-white btn-md waves-effect">
                                <input type="radio" autocomplete="off"> Type
                             </label>
                             <label class="btn btn-white btn-md waves-effect">
                                <input type="radio" autocomplete="off"> Name
                             </label>
                        </div>
                    </div>
                </div> -->
            </div>

			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center">{!! res('name') !!}</th>
                            <th class="text-center" style="width: 80px;">{!! res('inuse') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
@if (count($data) !== 0)
@foreach ($data as $item)
                        <tr>
                            <td><a href="{{ legacy_href() }}admin/mall_category/{{ $item->id }}">
                                {{ $prefix }}{{ $item->id }}</a></td>
                            <td>{{ $item->name }}</td>
                            <td class="text-center">{!! Format::numberformatzero($count[$item->id] ?? 0) !!}</td>
                            <td class="text-center">
                            	<a href="{{ legacy_href() }}admin/mall_category/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                            	<a type="button" href="#" onclick="Delete({{ $item->id }});return false;" class="table-action-btn"><i class="md  md-cancel text-danger"></i></a>
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
</script>