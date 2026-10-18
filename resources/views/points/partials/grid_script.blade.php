{{-- ส่วนท้ายของ grid.php เดิมทุกไฟล์ในกลุ่ม (Delete/DeleteAll เรียก <grid>/../delete ซึ่งไม่มีไฟล์จริง = โค้ดตาย คงไว้เหมือนเดิม) --}}
@php use App\Support\Legacy\Alerts; @endphp
<input type="hidden" id="Checking" />
<input type="hidden" id="txtSearch" value="{{ $s['raw'] }}">

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
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{!! e(Alerts::js($page)) !!}',  $('#txtSearch').val());";
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
            fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{!! e(Alerts::js($page)) !!}',  $('#txtSearch').val());";
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
@if($withStatus)
        $("#txtSearch").val($("#txtsearch").val() + '|' + $("#status").val());
@else
        $("#txtSearch").val($("#txtsearch").val());
@endif
    }
@if($saveRemark)

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
@endif
</script>
