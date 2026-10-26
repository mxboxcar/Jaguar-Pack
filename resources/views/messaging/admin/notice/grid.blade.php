@use('App\Domain\Messaging\NoticeText')
@use('App\Support\Legacy\Format')
@use('App\Support\Legacy\Paging')
{{-- _admin/_notice/grid.php เดิม --}}

<div class="row">
	<div class="col-lg-12">
		<div class="card-box">
			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center">{!! res('text') !!}</th>
                            <th class="text-center" style="width: 93px;">{!! res('create_date') !!}</th>
                            <th class="text-center" style="width: 90px;">{!! res('inuse') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
@if(count($rows) != 0)
@foreach($rows as $item)
                        <tr>
                            <td><a target="_blank" href="{{ legacy_href() }}admin/notice/{{ (int) $item->id }}">
                                {{ $prefix }}{{ (int) $item->id }}</a></td>
                            <td>{!! NoticeText::excerpt($item->text) !!}</td>
                            <td class="text-center">
@php $exp = explode(' ', (string) $item->create_date); @endphp
@if($item->create_date === '0000-00-00 00:00:00' || (string) $item->create_date === '')
-<br />-
@else
{!! Format::getthaidate($exp[0], true).'<br />'.Format::gettime($exp[1] ?? '') !!}
@endif
                            </td>
                            <td class="text-center">
@if((int) $item->is_show === 0)
                                <input type="checkbox" class="js-switch" onchange="onchangeisshow('{{ (int) $item->id }}', '1')" value="0" data-color="#f05050" data-size="small"/>
@else
                                <input type="checkbox" checked class="js-switch" onchange="onchangeisshow('{{ (int) $item->id }}', '0')" value="1" data-color="#f05050" data-size="small"/>
@endif
                            </td>
                            <td class="text-center">
                                <a  href="#" onclick="Sendmail({{ (int) $item->id }});return false;" class="table-action-btn"><i class="fa fa-envelope-o text-primary"></i></a>
                            	<a href="{{ legacy_href() }}admin/notice/{{ (int) $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary"></i></a>
                            	<a type="button" href="#" onclick="Delete({{ (int) $item->id }});return false;" class="table-action-btn"><i class="md  md-cancel text-danger"></i></a>
                            </td>
                        </tr>
@endforeach
@else
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
@endif
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
    $(function() {
        Switcher();
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
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ preg_replace('/[^0-9.+\-eE]/', '', (string) $page) }}',  $('#txtSearch').val());";
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
			ErrorBox('{!! res("add_more_then_one") !!}');
		} else {
            fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ preg_replace('/[^0-9.+\-eE]/', '', (string) $page) }}',  $('#txtSearch').val());";
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

    function onchangeisshow(id, val) {
        OpenLoading();
        $.ajax({
            type: 'POST',
            async: true,
            data : 'id=' + id + '&value=' + val,
            url: rooturl + '{{ $ajaxpage }}/../save_isshow',
            success: function(data) {
                loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());
            }
        });
    }

    function Sendmail(id) {
        ConfirmBox(function() {
            window.open(rooturl + '_admin/_notice/email_notice?id=' + id)
            CloseLoading();
        })
    }

	function GenerateWhere() {
		$("#txtSearch").val($("#txtsearch").val());
	}
</script>
