{{-- lost_password.php ของ PACK (หน้าแบบ login.css เดิม — ต่างจาก SR) · favicon = ชุดไอคอนของเจ้าของ (แทน favicon.png?v=2) --}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="icon" href="{{ legacy_href() }}icons/favicon.ico" sizes="any"><link rel="icon" type="image/png" sizes="32x32" href="{{ legacy_href() }}icons/favicon-32.png"><link rel="apple-touch-icon" href="{{ legacy_href() }}icons/apple-touch-icon.png"><link rel="manifest" href="{{ legacy_href() }}icons/site.webmanifest"><meta name="theme-color" content="#f37620">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
{{-- เดิม $site['data']['stitle'] ไม่มีค่า (ตัวแปรไม่ถูกตั้ง) → ว่าง --}}
<title> | Backend Management System</title>
        <meta name="description" content="{{ trim(config('legacy.project_name')) }} ขนส่งสินค้าจากจีนมาไทย">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ trim(config('legacy.project_name')) }}">
        <meta property="og:description" content="{{ trim(config('legacy.project_name')) }} ขนส่งสินค้าจากจีนมาไทย">
        <meta property="og:image" content="{{ legacy_href() }}assets/images/fav.png">

<link href="{{ legacy_href() }}assets/fonts/default.css" rel="stylesheet" type="text/css" />
<link href="{{ legacy_href() }}assets/css/login.css" rel="stylesheet" type="text/css" />
<link href="{{ legacy_href() }}assets/plugins/sweetalert/dist/sweetalert.css" rel="stylesheet" type="text/css">

</head>

<body>
<div id="login-area">
    <h1 class="clearhide"></h1>
    <h2 class="clearhide">Backend Management System</h2>
    <div id="login">
        <p class="clearhide">Username :</p>
        <p><input name="user" id="username" type="text" class="box" value="" title="Username :" placeholder="ผู้ใช้งาน" /></p>
        </td>
        <p class="but-area"><input type="button" name="button" value="GET NEW PASSWORD" onclick="doLostPassword();" class="but but-login" /><br/><input style="margin-left:0px;margin-top: 20px;" type="button" name="Reset" onclick="window.location.href = '{{ legacy_href() }}login'" value="Back to login" class="but but-reset"  /></p>      
    </div>
</div>

<p id="copyright">
    &copy; 2019 . All Rights Reserved.
</p>



<script src="{{ legacy_href() }}assets/js/jquery.min.js"></script>
<script src="{{ legacy_href() }}assets/js/bootstrap.min.js"></script>
<script src="{{ legacy_href() }}assets/validate/jquery.validationEngine.js"></script>
<script src="{{ legacy_href() }}assets/js/jquery.form.min.js"></script>
@include('legacy.partials.validationengine')

        <script src="{{ legacy_href() }}assets/plugins/ladda-buttons/js/spin.min.js"></script>
        <script src="{{ legacy_href() }}assets/plugins/ladda-buttons/js/ladda.min.js"></script>
        <script src="{{ legacy_href() }}assets/plugins/ladda-buttons/js/ladda.jquery.min.js"></script>

        <script src="{{ legacy_href() }}assets/plugins/sweetalert/dist/sweetalert.min.js"></script>
        <script src="{{ legacy_href() }}assets/pages/jquery.sweet-alert.init.js"></script>

        <script>
            var resizefunc = [];
            var rooturl = '{{ legacy_href() }}';
            var rooturl_blob = '{{ legacy_blob() }}';
            var fn_public = '';

            $.fn.enterKey = function (fnc) {
                return this.each(function () {
                    $(this).keypress(function (ev) {
                        var keycode = (ev.keyCode ? ev.keyCode : ev.which);
                        if (keycode == '13') {
                            fnc.call(this, ev);
                        }
                    })
                })
            }

            $("#username, #password").enterKey(function () {
                doCheckLogin();
            });

            function doLostPassword() {
                OpenLoading();
                $.ajax({
                    type: 'POST',
                    async: true,
                    data : 'username=' + $("#username").val() + '&password=' + $("#password").val(),
                    url: rooturl + '_register/lost_password',
                    success: function(data) {
                        eval(data);
                    }
                });
            }


            $(document).ready(function () {
                $('.ladda-button').ladda('bind', {timeout: 999999});
            });

            function OpenLoading() {
                var html = "<div class='loadingelement' style='width:" + $('body').width() + "px;height:" + $('body').height() + "px;position: absolute;z-index:77777777;' >";
                html += "<div style='margin:automargin: auto;text-align: center;font-size: 30px;font-weight: bold;color: #a94442;'><div>";
                html += "</div>";
                $('.content-page .content').fadeTo("fast", 0.5, function () { });
                $('.content-page .content').prepend(html);
            }
            function CloseLoading() {
                $(".loadingelement").remove();
                $('.content-page .content').fadeTo("fast", 1, function () { });
                
                Ladda.stopAll();
                swal.close();
            }
            function ErrorBox(text, fn) {
              swal({   
                title: "{!! res('alert') !!}",   
                text: text,   
                html:true,
                type: "error",   showCancelButton: false,   
                confirmButtonColor: "#81C868",   
                confirmButtonText: "{!! res('close') !!}",   
                cancelButtonText: "{!! res('cancel') !!}",   
                closeOnConfirm: false,   closeOnCancel: true }, 
                function(isConfirm){   
                  if (isConfirm) {
                    if(fn_public != undefined && fn_public != "" && fn_public != null) {
                      eval(fn_public);
                      fn_public = '';
                    }
                    if(fn != undefined && fn != "" && fn != null)  {
                      eval(fn);
                    } else {
                      CloseLoading();
                    }
                  } else {
                    CloseLoading();
                  }
                });
            }
            function SuccessBox(customtext ,text, fn) {
                $(".sweet-alert p").eq(0).text('');
                if(customtext != "" || customtext != undefined) {
                    text = customtext;
                }
              swal({   
                title: "{!! res('done') !!}",   
                text: text, 
                html:true,  
                type: "success",   showCancelButton: false,   
                confirmButtonColor: "#81C868",   
                confirmButtonText: "{!! res('close') !!}",   
                cancelButtonText: "{!! res('cancel') !!}",   
                closeOnConfirm: false,   closeOnCancel: true }, 
                function(isConfirm){   
                  if (isConfirm) {
                    if(fn_public != undefined && fn_public != "" && fn_public != null) {
                      eval(fn_public);
                      fn_public = '';
                    }
                    if(fn != undefined && fn != "" && fn != null)  {
                      eval(fn);
                    } else {
                      CloseLoading();
                    }
                  } else {
                    CloseLoading();
                  }
                });
            }
            function ConfirmBox(fn) {
              swal({   
                title: "{!! res('approve_question') !!}?",   
                text: "{!! res('approve_question_desc') !!}",   
                type: "warning",   showCancelButton: true,   
                html:true,
                confirmButtonColor: "#81C868",   
                confirmButtonText: "{!! res('ok') !!}",   
                cancelButtonText: "{!! res('cancel') !!}",   
                closeOnConfirm: false,   closeOnCancel: true }, 
                function(isConfirm){   
                  if (isConfirm) {    
                    fn();
                  } else {
                    CloseLoading();
                  }
                });
            }
        </script>
    
    </body>
</html>