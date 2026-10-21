{{-- สร้างจาก login.php ของเว็บเดิมด้วย tools/port-layout.php — แก้ได้ แต่ให้เทียบหน้าตากับเดิมทุกครั้ง --}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="icon" href="{{ legacy_href() }}icons/favicon.ico" sizes="any"><link rel="icon" type="image/png" sizes="32x32" href="{{ legacy_href() }}icons/favicon-32.png"><link rel="apple-touch-icon" href="{{ legacy_href() }}icons/apple-touch-icon.png"><link rel="manifest" href="{{ legacy_href() }}icons/site.webmanifest"><meta name="theme-color" content="#f37620">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title> | Backend Management System</title>
        <meta name="description" content="{{ trim(config('legacy.project_name')) }} ขนส่งสินค้าจากจีนมาไทย">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ trim(config('legacy.project_name')) }}">
        <meta property="og:description" content="{{ trim(config('legacy.project_name')) }} ขนส่งสินค้าจากจีนมาไทย">
        <meta property="og:image" content="{{ legacy_href() }}assets/images/fav.png">

<link href="{{ legacy_href() }}assets/fonts/default.css" rel="stylesheet" type="text/css" />
<link href="{{ legacy_href() }}assets/css/login.css" rel="stylesheet" type="text/css" />
<link href="{{ legacy_href() }}assets/plugins/sweetalert/dist/sweetalert.css" rel="stylesheet" type="text/css">

<script>
            /* login ready-guard (mobile fix 2026-08-30):
               receive taps that arrive before the bottom scripts finish loading,
               then fire login automatically once ready (fixes dead-tap on mobile) */
            window._loginReady = false;
            window._loginPending = false;
            function doCheckLogin() {
                if (window._loginReady) { doCheckLoginReal(); return; }
                window._loginPending = true;
                var b = document.querySelector('.ladda-button');
                if (b) { b.disabled = true; b.style.opacity = '0.6'; }
            }
        </script>


        <style>
            /* login-mobile-nozoom (2026-08-30): เฉพาะหน้า login ใช้บนมือถือได้โดยไม่ต้องซูม */
            #username, #password { font-size: 16px; height: 42px; }   /* >=16px กัน iOS auto-zoom ตอนแตะช่อง */
            .wrapper-page .ladda-button { font-size: 16px; }
            @media (max-width: 767px) {
                .wrapper-page { width: 90%; max-width: 420px; margin: 8% auto; }
                .panel-heading img { max-width: 75%; height: auto; }
            }
        </style>

    </head>

<body>
<div id="login-area">
    <h1 class="clearhide"></h1>
    <h2 class="clearhide">Backend Management System</h2>
    <div id="login">
        
        <p class="clearhide">Username :</p>
        <p><input name="user" id="username" type="text" class="box" value="" title="Username :" placeholder="ผู้ใช้งาน" /></p>
        <p class="clearhide">Password :</p>
        <p><input name="pws" id="password" type="password" class="box" value="" title="Password :"   placeholder="รหัสผ่าน" /></td>
        <p class="but-area">
            <input type="button" name="button" value="Login" onclick="doCheckLogin();" class="but but-login" />
            <br />
            <input type="button" name="Reset" style="margin-left:0px;margin-top: 20px;" onclick="window.location.href = '{{ legacy_href() }}register'" value="Register" class="but but-reset"  />
            <input type="button" name="Reset" onclick="window.location.href = '{{ legacy_href() }}lostpassword'" value="Lost Password" class="but but-reset"  />
        </p>    
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

            {{-- เดิม: $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'http' → JS ส่งไป https (host จาก href ไม่ใช่ Host header) --}}
            @if (request()->header('X-Forwarded-Proto') === 'http')
            window.location.href = {!! json_encode('https://'.parse_url(legacy_href(), PHP_URL_HOST).request()->getRequestUri(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!};
            @endif

            $("#username, #password").enterKey(function () {
                doCheckLogin();
            });

            function doCheckLoginReal() {
                OpenLoading();
                $.ajax({
                    type: 'POST',
                    async: true,
                    data : 'username=' + $("#username").val() + '&password=' + $("#password").val(),
                    url: rooturl + 'system_check_login',
                    success: function(data) {
                        eval(data);
                    }
                });
            }

            $("form").validationEngine();
            $("form").ajaxForm({ 
                success: function(data) {
                    eval(data);
                    if(data.trim().search("ErrorBox") == -1) {
                        $("#myModal").modal('hide');
                    }
                    setTimeout(function(){ CloseLoading(); }, 3000);
                }
            });


            $(document).ready(function () {
                $('.ladda-button').ladda('bind', {timeout: 999999});
                window._loginReady = true;
                var _b = document.querySelector('.ladda-button');
                if (_b) { _b.disabled = false; _b.style.opacity = ''; }
                if (window._loginPending) { window._loginPending = false; doCheckLoginReal(); }
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
