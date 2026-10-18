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
	    fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ preg_replace('/[^0-9A-Za-z.\-]/', '', (string) $page) }}',  $('#txtSearch').val());";
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
	        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ preg_replace('/[^0-9A-Za-z.\-]/', '', (string) $page) }}',  $('#txtSearch').val());";
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
