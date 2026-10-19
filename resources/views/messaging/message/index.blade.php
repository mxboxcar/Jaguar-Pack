{{-- _message/index.php เดิม --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('message') !!}</h4>
	<ol class="breadcrumb">
		<li >
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li class="active">
			<a href="#">{!! res('message') !!}</a>
		</li>
	</ol>
</div>




<div class="row">
	<div class="col-xs-12 col-lg-4 col-sm-4">
			<div class="card-box" style="height: 469px;">
				<h4 class="m-t-0 header-title"><b>{!! res('type') !!}</b></h4>
				<p class="text-muted font-13 m-b-30">
					
		        </p>
				
                <div class="row" id="dept_list" class="list-group" ></div>

			</div>
	</div>

	<div class="col-xs-12 col-lg-8 col-sm-8">
    	<div class="card-box">
    		<h4 class="m-t-0 m-b-20 header-title" >{!! res('sending_with') !!} <span id="title"></span></h4>

    		<div class="chat-conversation" style="padding-top:10px;">
                <ul class="conversation-list nicescroll" id="message" tabindex="5002" style="overflow: hidden; outline: none;">
                    
                </ul>
                <div class="row">
                    <form action="{{ legacy_href() }}_message/send" method="post">
                        <input type="hidden" name="id" value="">
                        <div class="col-sm-7 chat-inputbar">
                            <input name="text"  type="text" class="form-control chat-input" />
                        </div>

                        <div class="col-sm-5 chat-send">
                            <div class="button-list">
                                <button type="submit" class="pull-left btn  btn-inverse waves-effect waves-light">{!! res('send') !!}</button>
                                <button type="button" onclick="End()" class="pull-left btn  btn-success  waves-effect waves-light">{!! res('end_conversation') !!}</button>
                                @include('messaging.partials.upload')
                            </div>
                        </div>
                    </form>
                </div>
            </div>
    	</div>
    </div>
</div>


<script>
$(function() {
    LoadDept(0);
    $("form").validationEngine();
    $("form").ajaxForm({ 
        beforeSubmit : function(arr, $form, options){
            if($("input[name='id']").val() == 0) {
                ErrorBox("กรุณาเลือกประเภทการสนทนา");
                return false;
            }
          },
        success: function(data) {
            Load(1, true);
            LoadDept($("input[name='id']").val());
            //$('#message').scrollTop($('#message')[0].scrollHeight);
            $(".chat-input").val('');
        }
    });

    $("input[name='text']").bind("keypress", {}, keypressInBox);

    function keypressInBox(e) {
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code == 13) {               
            e.preventDefault();
            $("form").eq(0).submit();
        }
    }

    var fn = window.setInterval(
        function () { 
            if(($('#message').prop("scrollHeight") - $('#message').height()) == Math.round($('#message').scrollTop())) {
                LoadDept($("input[name='id']").val());
                Load(1, true); 
            }
        }, 5000);
    
    $(window).focus(function() {
        window.clearInterval(fn);
        fn = window.setInterval(
        function () { 
            if(($('#message').prop("scrollHeight") - $('#message').height()) == Math.round($('#message').scrollTop())) {
                LoadDept($("input[name='id']").val());
                Load(1, true); 
            }
        }, 5000);
         eval(fn);
    }).blur(function() {
        window.clearInterval(fn);
    });
});
function LoadDept(id) {
    $.ajax({
        type: 'POST',
        async: true,
        data: 'id=' + id,
        url: rooturl + '_message/load_dept',
        success: function(data) {
            $("#dept_list").html(data);
            //$(".chat-input").val('');
        }
    });
}
function ChangeDept(id, ele) {
    $("input[name='id']").val(id);
    $("#dept_list .list-group-item").removeClass('active');
    ele.addClass('active');
}

function Load(page, isnewload = false) {
    if($("input[name='id']").val() != 0) {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + $("input[name='id']").val() + '&page=' + page,
            url: rooturl + '_message/load',
            success: function(data) {
                data = $.parseJSON(data);

                $("#title").html(data.title);
                if(isnewload) {
                    $("#message").html(decodeHtml(data.html));
                    $("#message").scrollTop($('#message').prop("scrollHeight"));
                } else {
                    $(".load_more_btn").remove();
                    $("#message").prepend(decodeHtml(data.html));
                    $("#message").scrollTop($("#message>div").eq(1).prop("scrollHeight"));
                }
            }
        });
    }
}

function decodeHtml(html) {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
}

function on_uploadcomplate(lot, value)
{
    var x = value.split("[NIDA]");
    var urls = rooturl_blob + x[0];

    

    if($("input[name='id']").val() == 0) {
        ErrorBox("กรุณาเลือกประเภทการสนทนา");
        return false;
    } else {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'value=' + x[0],
            url: rooturl + '_message/save_file',
            success: function(data) {
                eval(data);
            }
        });

        var htmls = '<a class="fancybox" href="' + urls +'"><img src="' + urls + '" style="max-width: 120px;max-height: 120px;"></a>';

        $(".chat-input").val(htmls);
        $("form").submit();
        $(".chat-input").val('');
        CloseLoading();
    }
}
function End() {
    ConfirmBox(function() {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + $("input[name='id']").val(),
            url: rooturl + '_message/send_endconversation',
            success: function(data) {
                LoadDept($("input[name='id']").val());
                CloseLoading();
            }
        }); 
    });
}
</script>
@endsection
