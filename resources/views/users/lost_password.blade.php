{{-- lost_password.php ของ SR (แปลงจาก sr-prehotfix/lost_password.php) --}}
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="author" content="Coderthemes">

        <link rel="icon" href="{{ legacy_href() }}icons/favicon.ico" sizes="any"><link rel="icon" type="image/png" sizes="32x32" href="{{ legacy_href() }}icons/favicon-32.png"><link rel="apple-touch-icon" href="{{ legacy_href() }}icons/apple-touch-icon.png"><link rel="manifest" href="{{ legacy_href() }}icons/site.webmanifest"><meta name="theme-color" content="#f37620">

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
        

        <script>window.FastClick=window.FastClick||{attach:function(){}};/* no-op shim (2026-08-30): fastclick.js ถูกถอด กัน jquery.app.js เรียก FastClick แล้ว throw */</script>
    </head>
    <body>

        <div class="account-pages"></div>
        <div class="clearfix"></div>
        <div class="wrapper-page">
            <div class=" card-box">
            <div class="panel-heading text-center"> 
                
                <img style="width: 170px;" src="{{ legacy_href() }}assets/images/logo_mini.png" />
                <h1 class="text-center" style="font-size: 45px!important;"> S h i p <strong class="text-custom"> 2 </strong> Y o u </h1>
            </div> 


            <div class="panel-body">
            <div class="form-horizontal m-t-20">
                
                <div class="form-group ">
                    <div class="col-xs-12">
                        <input class="form-control" id="username" type="text" required="" placeholder="{!! res('username') !!}">
                    </div>
                </div>

                <div class="form-group text-center m-t-40">
                    <div class="col-xs-12">
                        <button class="ladda-button btn btn-primary"  onclick="doLostPassword();"><span class="ladda-label">
                        {!! res('reset_password') !!}
                        </span></button>
                    </div>
                </div>

                <div class="form-group m-t-30 m-b-0">
                    <div class="col-sm-6">
                        <a href="{{ legacy_href() }}login" class="text-dark"><i class="fa fa-arrow-circle-left m-r-5"></i> {!! res('back_to_login') !!}</a>
                    </div>
                </div>
            </div> 

            </div> 

            </div>                              
                <div class="row">
                <div class="col-sm-12 text-center">
                    © 2016-{{ date('Y') }} {{ config('legacy.project_name') }} All rights reserved. 
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