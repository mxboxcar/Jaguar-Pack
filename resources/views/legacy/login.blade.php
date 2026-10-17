{{-- สร้างจาก login.php ของเว็บเดิมด้วย tools/port-layout.php — แก้ได้ แต่ให้เทียบหน้าตากับเดิมทุกครั้ง --}}
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="author" content="Coderthemes">

        <link rel="shortcut icon" href="{{ legacy_href() }}assets/images/favicon.png?v=2">

        <title>{{ config('legacy.project_name') }}</title>
        <meta name="description" content="{{ trim(config('legacy.project_name')) }} ขนส่งสินค้าจากจีนมาไทย">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ trim(config('legacy.project_name')) }}">
        <meta property="og:description" content="{{ trim(config('legacy.project_name')) }} ขนส่งสินค้าจากจีนมาไทย">
        <meta property="og:image" content="{{ legacy_href() }}assets/images/fav.png">

        <link href="{{ legacy_href() }}assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/core.css" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/components.css" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/pages.css" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/responsive.css" rel="stylesheet" type="text/css" />

        <link href="{{ legacy_href() }}assets/plugins/ladda-buttons/css/ladda-themeless.min.css" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/plugins/sweetalert/dist/sweetalert.css" rel="stylesheet" type="text/css">
        <!-- HTML5 Shiv and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
        <![endif]-->

        <script src="assets/js/modernizr.min.js"></script>
        

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


        <script>window.FastClick=window.FastClick||{attach:function(){}};/* no-op shim (2026-08-30): fastclick.js ถูกถอด กัน jquery.app.js เรียก FastClick แล้ว throw */</script>
    </head>
    <body>

        <div class="account-pages"></div>
        <div class="clearfix"></div>
        <div class="wrapper-page">
            <div class=" card-box">
            <div class="panel-heading text-center"> 
               <!--  <h3 class="text-center"> {!! res('sign_in') !!} <strong class="text-custom">UBold</strong> </h3> -->
                <img style="width: 270px;" src="{{ legacy_href() }}assets/images/logo.png" />
            </div> 


            <div class="panel-body">
            <div class="form-horizontal m-t-20">
            <!-- <form class="form-horizontal m-t-20" action="index.html"> -->
                
                <div class="form-group ">
                    <div class="col-xs-12">
                        <input class="form-control" id="username" type="text" required="" placeholder="{!! res('username') !!}">
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-xs-12">
                        <input class="form-control" id="password" type="password" required="" placeholder="{!! res('password') !!}">
                    </div>
                </div>

                <div class="form-group text-center m-t-40">
                    <div class="col-xs-12">
                        <button class="ladda-button btn btn-primary"  onclick="doCheckLogin();"><span class="ladda-label">
                        {!! res('login') !!}
                        </span></button>
                    </div>
                </div>

                <div class="form-group m-t-30 m-b-0">
                    <div class="col-sm-6">
                        <a href="{{ legacy_href() }}lostpassword" class="text-dark"><i class="fa fa-lock m-r-5"></i> {!! res('lost_password') !!}</a>
                    </div>
                    <div class="col-sm-6 text-right">
                        <a href="{{ legacy_href() }}register" class="text-dark"><i class="fa fa-user-plus m-r-5"></i> {!! res('register') !!}</a>
                    </div>
                </div>
            <!-- </form>  -->
            </div> 

            </div> 

            </div>                              
                <div class="row">
                <div class="col-sm-12 text-center">
                    
                    © 2017-{{ date('Y') }} {{ config('legacy.project_name') }} All rights reserved.  
                </div>
            </div>
            
        </div>
        
        

        <!-- jQuery  -->
        <script src="{{ legacy_href() }}assets/js/jquery.min.js"></script>
        <script src="{{ legacy_href() }}assets/js/bootstrap.min.js"></script>
        <script src="{{ legacy_href() }}assets/js/detect.js"></script>
        <script src="{{ legacy_href() }}assets/js/jquery.slimscroll.js"></script>
        <script src="{{ legacy_href() }}assets/js/jquery.blockUI.js"></script>
        <script src="{{ legacy_href() }}assets/js/waves.js"></script>
        <script src="{{ legacy_href() }}assets/js/wow.min.js"></script>
        <script src="{{ legacy_href() }}assets/js/jquery.nicescroll.js"></script>
        <script src="{{ legacy_href() }}assets/js/jquery.scrollTo.min.js"></script>


        <script src="{{ legacy_href() }}assets/js/jquery.core.js"></script>
        <script src="{{ legacy_href() }}assets/js/jquery.app.js"></script>


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
    
    </body>
</html>