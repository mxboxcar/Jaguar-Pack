{{-- _admin/_message/index.php เดิม --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('admin_message') !!}</h4>
	<ol class="breadcrumb">
		<li >
			<a href="#">{!! res('administrator') !!}</a>
		</li>
		<li class="active">
			<a href="#">{!! res('admin_message') !!}</a>
		</li>
	</ol>
</div>




<div class="row" >
    <div class="col-xs-12 col-lg-4 col-sm-4"> 
        <ul class="nav nav-tabs" > 
            <li class="active"> 
                <a href="#dept_list" data-toggle="tab" aria-expanded="true"> 
                    <span class="visible-xs"><i class="fa fa-home"></i>
                        <span class="badge badge-xs badge-danger" id="total_count"></span>
                    </span> 
                    <span class="hidden-xs">{!! res('type') !!}</span> 
                    
                </a> 
            </li> 
            <li class=""> 
                <a href="#user" data-toggle="tab" aria-expanded="false"> 
                    <span class="visible-xs"><i class="fa fa-user"></i></span> 
                    <span id="cus_trigger" class="hidden-xs">{!! res('customer_code') !!}</span> 
                </a> 
            </li> 
        </ul> 
        <div class="tab-content" > 
            <div class="tab-pane list-group active" id="dept_list" > 
                
            </div> 
            <div class="tab-pane" id="user"> 
                <input type="text" class="form-control" id="keyword" placeholder="{!! res('search') !!}" />
                <div id="user_list">

                </div>
            </div> 
        </div> 
    </div>
	<div class="col-xs-12 col-lg-8 col-sm-8">
    	<div class="card-box">
    		<h4 class="m-t-0 m-b-20 header-title" >{!! res('sending_with') !!} <span id="title"></span></h4>
            <h4 class="m-t-0 m-b-20 header-title" >{!! res('customer_code') !!} : <span id="title2"></span></h4>
    		<div class="chat-conversation" style="padding-top:10px;">
                <ul class="conversation-list nicescroll" id="message" tabindex="5002" style="overflow: hidden; outline: none;">
                    
                </ul>
                <div class="row">
                    <form action="{{ legacy_href() }}_admin/_message/send" method="post">
                        <input type="hidden" name="id" value="">
                        <input type="hidden" name="user" value="">
                        <div class="col-sm-7 chat-inputbar">
                            <input name="text" type="text" class="form-control chat-input" placeholder="">
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
    LoadUser();
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
            //$('#message').scrollTop($('#message')[0].scrollHeight);
            $("input[name='text']").val('');
            LoadDept($("input[name='id']").val());
        }
    });

    $("input[name='text']").bind("keypress", {}, keypressInBox);
    $("#keyword").bind("keypress", {} , keypressInBox2);

    function keypressInBox(e) {
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code == 13) {               
            e.preventDefault();
            $("form").eq(0).submit();
        }
    }

    function keypressInBox2(e) {
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code == 13) {               
            e.preventDefault();
            LoadUser();
        }
    }

    var fn = window.setInterval(
        function () { 
            if(($('#message').prop("scrollHeight") - $('#message').height()) == Math.round($('#message').scrollTop())) {
                LoadDept($("input[name='id']").val());
                LoadUser();
                Load(1, true); 
            }
            
        }, 5000);
    
    $(window).focus(function() {
        window.clearInterval(fn);
        fn = window.setInterval(
        function () { 
            if(($('#message').prop("scrollHeight") - $('#message').height()) == Math.round($('#message').scrollTop())) {
                LoadDept($("input[name='id']").val());
                LoadUser();
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
        url: rooturl + '_admin/_message/load_dept',
        success: function(data) {
            data = $.parseJSON(data);
            $("#dept_list").html(data.html);
            $("input[name='id']").val(id);
            if(data.total != 0) {
                $("#total_count").text(data.total);
            } else {
                $("#total_count").text('');
            }
        }
    });
}

function LoadUser() {
   $.ajax({
        type: 'POST',
        async: true,
        data: 'keyword=' + $("#keyword").val() + '&select=' + $("input[name='user']").val() + '&dept=' + $("input[name='id']").val(),
        url: rooturl + '_admin/_message/load_user',
        success: function(data) {
            data = $.parseJSON(data);
            $("#user_list").html(data.html);
        }
    }); 
}

function End() {
   $.ajax({
        type: 'POST',
        async: true,
        data: 'id=' + $("input[name='id']").val() + '&user=' + $("input[name='user']").val() ,
        url: rooturl + '_admin/_message/send_endconversation',
        success: function(data) {
            LoadDept($("input[name='id']").val());
            LoadUser();
        }
    }); 
}

function ChangeDept(id, ele) {
    $("input[name='id']").val(id);
    $("#dept_list .list-group-item").removeClass('active');
    ele.addClass('active');
    $("#title").html(ele.find('.ctext-wrap').text());
    $("#cus_trigger").click();
    LoadUser();
}

function ChangeUser(id, ele) {
    $("input[name='user']").val(id);
    $("#user_list .list-group-item").removeClass('active');
    ele.addClass('active');
}

function Load(page, isnewload = false) {
    $.ajax({
        type: 'POST',
        async: true,
        data: 'id=' + $("input[name='id']").val() + '&user=' + $("input[name='user']").val() + '&page=' + page,
        url: rooturl + '_admin/_message/load',
        success: function(data) {
            data = $.parseJSON(data);

            $("#title2").html(data.title);
            if(isnewload) {
                $("#message").html($.parseHTML(data.html));
                $("#message").scrollTop($('#message').prop("scrollHeight"));
            } else {
                $(".load_more_btn").remove();
                $("#message").prepend(data.html);
                $("#message").scrollTop($("#message>div").eq(1).prop("scrollHeight"));
            }
            //$("#message").scrollTop($('#message').prop("scrollHeight"));
        }
    });
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
            url: rooturl + '_admin/_message/save_file',
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

function deletechat(id) {
    ConfirmBox(function () {
        $.ajax({
            type: 'POST',
            async: true,
            data: 'id=' + id ,
            url: rooturl + '_admin/_message/deletechat',
            success: function(data) {
                LoadDept($("input[name='id']").val());
                LoadUser();
                Load(1, true); 
                CloseLoading();
            }
        }); 
    });
   
}
</script>
@endsection
