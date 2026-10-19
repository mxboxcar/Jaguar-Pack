{{-- สร้างจาก index.php ของเว็บเดิมด้วย tools/port-layout.php — แก้ได้ แต่ให้เทียบหน้าตากับเดิมทุกครั้ง --}}
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <?php 
        // $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        // if(stripos($ua,'android') !== false) {
        //     echo '<meta name="viewport" content="width=device-width, initial-scale=1.0;">';
        // }
        ?>

        <link rel="icon" href="{{ legacy_href() }}icons/favicon.ico" sizes="any"><link rel="icon" type="image/png" sizes="32x32" href="{{ legacy_href() }}icons/favicon-32.png"><link rel="apple-touch-icon" href="{{ legacy_href() }}icons/apple-touch-icon.png"><link rel="manifest" href="{{ legacy_href() }}icons/site.webmanifest"><meta name="theme-color" content="#9b1c20">

        <title>{{ config('legacy.project_name') }}</title>
        <meta name="description" content="{{ trim(config('legacy.project_name')) }} ขนส่งสินค้าจากจีนมาไทย">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ trim(config('legacy.project_name')) }}">
        <meta property="og:description" content="{{ trim(config('legacy.project_name')) }} ขนส่งสินค้าจากจีนมาไทย">
        <meta property="og:image" content="{{ legacy_href() }}assets/images/fav.png">

        <!--Morris Chart CSS -->
		    {{-- morris.css ตัดออก 2026-10-19: ไม่มีหน้าไหนใช้กราฟ morris และ path แบบ relative ทำให้ 404 ทุกหน้า --}}

        <link href="{{ legacy_href() }}assets/css/bootstrap.min.css?v={{ config('legacy.version') }}" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/core.css?v={{ config('legacy.version') }}" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/components.css?v={{ config('legacy.version') }}" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/icons.css?v={{ config('legacy.version') }}" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/pages.css?v={{ config('legacy.version') }}" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/css/responsive.css?v={{ config('legacy.version') }}" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/plugins/ladda-buttons/css/ladda-themeless.min.css?v={{ config('legacy.version') }}" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/plugins/select2/select2.css?v={{ config('legacy.version') }}" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/plugins/sweetalert/dist/sweetalert.css?v={{ config('legacy.version') }}" rel="stylesheet" type="text/css">
        <link href="{{ legacy_href() }}assets/css/pages.css?v={{ config('legacy.version') }}" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/validate/validationEngine.jquery.css?v={{ config('legacy.version') }}" rel="stylesheet" type="text/css">
        <link href="{{ legacy_href() }}assets/plugins/multiselect/css/multi-select.css?v={{ config('legacy.version') }}"  rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/lightbox/jquery.fancybox.css?version=?v={{ config('legacy.version') }}" type="text/css" media="screen" rel="stylesheet" />
        <link href="{{ legacy_href() }}assets/dropper/jquery.fs.dropper.min.css?v={{ config('legacy.version') }}" rel="stylesheet">
        <link href="{{ legacy_href() }}assets/datepicker/css/bootstrap-datetimepicker.min.css?v={{ config('legacy.version') }}" rel="stylesheet" >
        <link rel="stylesheet" href="{{ legacy_href() }}assets/cart/css/style.css?v={{ config('legacy.version') }}"> <!-- Gem style -->
        <link href="{{ legacy_href() }}assets/plugins/switchery/dist/switchery.min.css?v={{ config('legacy.version') }}" rel="stylesheet" />
        <style>
        body {
            font-family: 微软雅黑!important;
            color:#000!important;
            font-size:13px!important;
        }
        table {
           font-size:13px!important;
            color:#000!important;
        }
        td, th {
            font-size:13px!important;
            color:#000!important;
        }
        a {
            font-size:13px!important;
        }
        #sidebar-menu ul ul a , #sidebar-menu > ul > li > a {
            color:#000!important;
        }
        .editable {
            background: transparent;
            border: none;
            width:100%;
        }
        <?php if($isMobile) { echo ".card-box { overflow-x: scroll!important; }"; } ?>
        </style>

        <script src="{{ legacy_href() }}assets/js/modernizr.min.js?v={{ config('legacy.version') }}"></script>


        <script>
            var resizefunc = [];
        </script>

        <!-- jQuery  -->
        <script src="{{ legacy_href() }}assets/js/jquery.min.js?v={{ config('legacy.version') }}"></script>
        <script src="{{ legacy_href() }}assets/js/socket.io-4.7.5.min.js?v={{ config('legacy.version') }}"></script>
        

        <!-- start customize -->
        <script src="{{ legacy_href() }}assets/validate/jquery.validationEngine.js?v={{ config('legacy.version') }}"></script>
        <script src="{{ legacy_href() }}assets/js/jquery.form.min.js?v={{ config('legacy.version') }}"></script>
        @include('legacy.partials.validationengine')

        <script src="{{ legacy_href() }}assets/plugins/ladda-buttons/js/spin.min.js?v={{ config('legacy.version') }}"></script>
        <script src="{{ legacy_href() }}assets/plugins/ladda-buttons/js/ladda.min.js?v={{ config('legacy.version') }}"></script>
        <script src="{{ legacy_href() }}assets/plugins/ladda-buttons/js/ladda.jquery.min.js?v={{ config('legacy.version') }}"></script>

        <script src="{{ legacy_href() }}assets/plugins/sweetalert/dist/sweetalert.min.js?v={{ config('legacy.version') }}"></script>
        <script src="{{ legacy_href() }}assets/pages/jquery.sweet-alert.init.js?v={{ config('legacy.version') }}"></script>
        <script src="{{ legacy_href() }}assets/plugins/select2/select2.min.js?v={{ config('legacy.version') }}" type="text/javascript"></script>
        <script type="text/javascript" src="{{ legacy_href() }}assets/plugins/jquery-quicksearch/jquery.quicksearch.js?v={{ config('legacy.version') }}"></script>
        <script type="text/javascript" src="{{ legacy_href() }}assets/plugins/multiselect/js/jquery.multi-select.js?v={{ config('legacy.version') }}"></script>
        <script src="{{ legacy_href() }}assets/cookie/jquery.cookie.js?v={{ config('legacy.version') }}"></script>
        <script src="{{ legacy_href() }}assets/js/bootstrap3-typeahead.js?v={{ config('legacy.version') }}"></script>
        <script src="{{ legacy_href() }}assets/lightbox/jquery.fancybox.js?v={{ config('legacy.version') }}"></script>
        <script src="{{ legacy_href() }}assets/dropper/jquery.fs.dropper.min.js?v={{ config('legacy.version') }}"></script>
        <script src="{{ legacy_href() }}assets/js/moment.min.js?v={{ config('legacy.version') }}"></script>
        <script src="{{ legacy_href() }}assets/datepicker/js/bootstrap-datetimepicker.js?v={{ config('legacy.version') }}"></script>
        <script src="{{ legacy_href() }}assets/plugins/switchery/dist/switchery.min.js?v={{ config('legacy.version') }}"></script>
        
        

        <script type="text/javascript">

            var rooturl = '{{ legacy_href() }}';
            var rooturl_blob = '{{ legacy_blob() }}';
            var fn_public = '';


            {{-- เดิม: $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'http' → JS ส่งไป https (host จาก href ไม่ใช่ Host header) --}}
            @if (request()->header('X-Forwarded-Proto') === 'http')
            window.location.href = {!! json_encode('https://'.parse_url(legacy_href(), PHP_URL_HOST).request()->getRequestUri(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!};
            @endif

            jQuery.fn.preventDoubleSubmission = function() {
              $(this).on('submit',function(e){
                var $form = $(this);
                  e.preventDefault();
              });
              return this;
            };
            $('form').preventDoubleSubmission();
            
            function keypressInBox(e) {
                var code = (e.keyCode ? e.keyCode : e.which);
                if (code == 13) { //Enter keycode                        
                    e.preventDefault();
                    LoadQuickSearch();
                }
            }

            function keypressQuote(e) {
                var code = (e.keyCode ? e.keyCode : e.which);
                var ref = $(this).attr('ref');
                var value = $(this).val();

                if (code == 13) { //Enter keycode                        
                    e.preventDefault();
                    $.ajax({
                        type: 'POST',
                        async: true,
                        data : 'ref=' + ref + '&value=' + value,
                        url: rooturl + '_tracking/save_quick_quote',
                        success: function(data) {
                            
                        }
                    });
                }
            }

            function SaveQuickQuote(ref, value) {
                $.ajax({
                    type: 'POST',
                    async: true,
                    data : 'ref=' + ref + '&value=' + value,
                    url: rooturl + '_tracking/save_quick_quote',
                    success: function(data) {
                        
                    }
                });
            }

            function LoadQuickSearch() {
                $.ajax({
                    type: 'POST',
                    async: true,
                    data : 'current_href=' + encodeURIComponent(window.location.href) + '&code=' + $("#quick_search").val().replace(' ', ''),
                    url: rooturl + 'load_quick_search',
                    success: function(data) {
                        CloseLoadingElement();
                        data = $.parseJSON(data);

                        if(data.href == "") {
                            ErrorBox('{!! res('no_data') !!}');
                        } else {
                            ConfirmBox(function() {
                                window.location.href = data.href;
                            }, data.prefix);
                        }
                    }
                });
            }
            

            function loadAjaxGrid(url, page, txtsearch, fn) {
                OpenLoading();
                $.ajax({
                    type: 'POST',
                    async: true,
                    data : 'page=' + page + '&txtsearch=' + txtsearch,
                    url: rooturl + url,
                    success: function(data) {
                        if(fn_public == undefined || fn_public == "" || fn_public == null) {
                          CloseLoading();
                        }
                        try {
                            $("#gridAjax").html(data);
                        } catch(err) {}
                        
                        if(typeof(fn) == "function") {
                            fn();
                        }
                        makeCurrency();
                    }
                });
            }

            $(".fancybox").fancybox({
                afterLoad: function() {
                    console.log(this);
                    var time = new Date().getTime();
                    this.title = '<a download="'+ time +'" href="' + this.href + '">ดาวน์โหลด</a> ' + this.title;
                }
            });

            var noti_length = 0;

            $(document).ready(function () {
                OpenloadingNew();
                $('.ladda-button').ladda('bind', {timeout: 999999});
                $(".select2").select2();
                
                $("button[class='confirm']").click(function() {
                    OpenLoadingElement();
                });
                makeCurrency();
                notification();
                








                // แจ้งเตือน: รอสัญญาณ push จาก notify-server ไม่ต้องถามเอง
                // เดิมพอร์ทัลนี้คอมเมนต์ตัวถามทิ้งไว้ = แถบแจ้งเตือนไม่เคยรีเฟรชเอง
                // ถ้าต่อ socket ไม่ได้ ให้ถามเองทุก 10 วิ (พฤติกรรมเดียวกับพอร์ทัลอื่น)
                var NOTI_POLL_FAST = 10000;
                var NOTI_POLL_SLOW = 300000;
                var notiTimer = null;
                var notiLastV = null;
                function setNotiPoll(ms) {
                    if (notiTimer) { clearInterval(notiTimer); }
                    notiTimer = setInterval(function () { notifications(); }, ms);
                }
                setNotiPoll(NOTI_POLL_FAST);

                (function () {
                    var token = "{{ \App\Support\Legacy\Notify::token() }}";
                    if (typeof io !== "function" || token === "") { return; }
                    try {
                        var sock = io({
                            path: "/socket.io/",
                            auth: { token: token },
                            transports: ["websocket", "polling"],
                            reconnectionDelayMax: 30000
                        });
                        sock.on("connect", function () { setNotiPoll(NOTI_POLL_SLOW); });
                        sock.on("hello", function (d) {
                            if (notiLastV !== null && d.v !== notiLastV) { notifications(); }
                            notiLastV = d.v;
                        });
                        sock.on("changed", function (d) { notiLastV = d.v; notifications(); });
                        sock.on("disconnect", function () { setNotiPoll(NOTI_POLL_FAST); });
                        sock.on("connect_error", function () { setNotiPoll(NOTI_POLL_FAST); });
                    } catch (e) { setNotiPoll(NOTI_POLL_FAST); }
                })();






                $('#searchlink').on('keypress', function (e) {
                     if(e.which === 13){
                        searchlink();
                     }
               });

                document.title = document.title + " | " + $(".page-title").text();
               

                setTimeout(function() {
                    if(document.URL.indexOf('admin') != -1) {
                        $(".content-page .content").css("background-color", "#FDE498");
                    }
                }, 500);

                $("body").bind("ajaxComplete", function(e, xhr, settings){
                   makeCurrency();
                });
                
                initialAddcomma();

                $("#quick_search").bind("keypress", {}, keypressInBox);
                $(".quote_tracking").bind("keypress", {}, keypressQuote);

                $(".dropper-input").css({"margin-top": "-34px",
                    "height": "34px",
                    "width": "125px"});
            });

            //call every time ajax stop
            $(document).ajaxStop(function () {
                
              $('.ladda-button').ladda('bind', {timeout: 999999});
              makeCurrency();
              
              $(".select2").select2();
              
            });

            function initialAddcomma() {
                $(".addcomma").each(function() {
                        try {
                            $(this).text(addCommas( (Fixed($(this).text(), 2)))); 
                         } catch(err) {}
                        
                });
                $(".addcommazero").each(function() {
                        try {
                            $(this).val(addCommas($(this).val())); 
                        } catch(err) {}
                });
                $(".addcommacubic").each(function() {
                        try {
                            $(this).text(addCommas( (Fixed($(this).text(), 4)))); 
                         } catch(err) {}
                        
                });

            }

            function initialAddcommaText() {
                $(".addcomma").each(function() {
                        try {
                            $(this).text(addCommas( (Fixed($(this).text(), 2)))); 
                         } catch(err) {}
                        
                });
                $(".addcommazero").each(function() {
                        try {
                            $(this).text(addCommas($(this).text())); 
                        } catch(err) {}
                });
                $(".addcommacubic").each(function() {
                        try {
                            $(this).text(addCommas( (Fixed($(this).text(), 4)))); 
                         } catch(err) {}
                        
                });
            }

            function initialDatepicker() {
                $('.datepicker').datetimepicker({
                    format: 'D MMMM YYYY | HH:mm',
                    pickDate: true,
                    pickTime: true,
                    useMinutes: true,
                    useSeconds: false,
                    pick12HourFormat: false,
                    language: '{{ $lang }}',
                    showToday: true,  
                    defaultDate: new Date(),
                }); 
            }
            function initialDatepicker2() {
                $('.datepicker').datetimepicker({
                    format: 'D MMMM YYYY',
                    pickDate: true,
                    pickTime: false,
                    useMinutes: true,
                    useSeconds: false,
                    pick12HourFormat: false,
                    language: '{{ $lang }}',
                    showToday: true,  
                    //defaultDate: new Date(),
                }); 
            }

            function makeCurrency() {
                $(".currency").each(function() {
                    var v = $(this).text();
                    $(this).text(addCommas(v));
                });
            }

            function Language(lang) {
                $.ajax({
                    type: 'POST',
                    async: true,
                    data: 'lang=' + lang,
                    url: rooturl + "system_change_language",
                    success: function(data) {
                        location.reload(true);
                    }
                });
            }

            function b64EncodeUnicode(str) {
                // first we use encodeURIComponent to get percent-encoded UTF-8,
                // then we convert the percent encodings into raw bytes which
                // can be fed into btoa.
                return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
                    function toSolidBytes(match, p1) {
                        return String.fromCharCode('0x' + p1);
                }));
            }

            function makecubicformat(value) {
                value = String(value).replace(" ", "");
                value = value.replace(",", "");

                var exp = value.split(".");

                var front = exp[0];
                var back = exp[1];

                var v = '';
                for(i = front.length; i >= 0; i--) {
                    if(front[i] != undefined && front[i] != "undefined") {
                        v = front[i] + v;
                    }
                }
                v = v + ".";

                if(back != undefined && back != "undefined") {
                    for(i = 0; i < 4; i++) {
                        if(back[i] == "") {
                            v = v + "0";
                        } else {
                            if(back[i] != undefined && back[i] != "undefined") {
                                v = v + back[i];
                            }
                        }
                    }
                } else {
                    v = v + "0";
                }
                
                return eval(v);
            }

            function notifications() {
                $.ajax({
                    type: 'POST',
                    async: true,
                    url: rooturl + 'notifications',
                    success: function(data) {
                        var lengths = eval(data);
                        if(noti_length == 0) {
                            noti_length = lengths;
                        } else {
                            if(noti_length < lengths) {
                                notifyMe();
                                notification(); 
                            }
                            noti_length = lengths;
                        }
                    }
                });
            }

            function notification() {
                $.ajax({
                    type: 'POST',
                    async: true,
                    url: rooturl + 'notification',
                    success: function(data) {
                        $("#notification").html(data);
                    }
                });
            }

            function OpenloadingNew() {
                var html = "<div class='loadingelement' style='width:" + $('body').width() + "px;height:" + $('body').height() + "px;position: absolute;z-index:-999;    background-color: #fff;opacity: 0.5;' >";
                html += "<div style='margin:automargin: auto;text-align: center;font-size: 30px;font-weight: bold;color: #a94442;'><div>";
                html += "</div>";

                $('.content-page .content').prepend(html);
            }

            function OpenLoading() {
                // var html = "<div class='loadingelement' style='width:" + $('body').width() + "px;height:" + $('body').height() + "px;position: absolute;z-index:999999;' >";
                // html += "<div style='margin:automargin: auto;text-align: center;font-size: 30px;font-weight: bold;color: #a94442;'><div>";
                // html += "</div>";
                
                // if(!msieversion()) {
                //     $('.content-page .content').css("opacity", 0.5);
                //     $('.content-page .content').prepend(html);
                $(".loadingelement").css("width", $('body').width() + "px");
                $(".loadingelement").css("height", $('body').height() + "px");
                $(".loadingelement").css("z-index", "99999999");
                // }

            }
            function OpenLoadingElement() {
                $(".loadingelement").css("width", $('body').width() + "px");
                $(".loadingelement").css("height", $('body').height() + "px");
                $(".loadingelement").css("z-index", "99999999");
                //var text = $(".confirm").text();

                //if(text == "ยืนยัน") {
                    // var html = "<div class='loadingelement' style='width:" + $('body').width() + "px;height:" + $('body').height() + "px;position: absolute;z-index:999999;' >";
                    // html += "<div style='margin:automargin: auto;text-align: center;font-size: 30px;font-weight: bold;color: #a94442;'><div>";
                    // html += "</div>";
                    // if($(".loadingelement").length == 0) {
                    //     if(!msieversion()) {
                    //         $('body').css("opacity", 0.5);
                    //         $('body').prepend(html);
                    //     }
                        
                    // }
                    
                //}
            }

            function CloseLoading() {
                // $(".loadingelement").remove();

                // $('.content-page .content').css("opacity", 1);
                // $('body').css("opacity", 1);
                $(".loadingelement").css("width", $('body').width() + "px");
                $(".loadingelement").css("height", $('body').height() + "px");
                $(".loadingelement").css("z-index", "-9999999999");

                Ladda.stopAll();
                swal.close();
            }

            function CloseLoadingElement() {
                $(".loadingelement").remove();
                $('body').css("opacity", 1);
            }
            function ErrorBox(text, fn) {
                CloseLoadingElement();
                $("button[class='confirm']").click(function() {
                    OpenLoadingElement();
                });
              swal({   
                title: "{!! res('alert') !!}",   
                text: text,   
                html:true,
                type: "error",   showCancelButton: false,   
                confirmButtonColor: "#81C868",   
                confirmButtonText: "{!! res('close') !!}",   
                cancelButtonText: "{!! res('cancel') !!}",   
                closeOnConfirm: false,   closeOnCancel: true,
                animation:false,
                allowOutsideClick: false }, 
                function(isConfirm){ 
                    OpenLoadingElement();  
                  if (isConfirm) {
                    if(fn_public != undefined && fn_public != "" && fn_public != null) {
                      eval(fn_public);
                      fn_public = '';
                      CloseLoading();
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
                CloseLoadingElement();
                $("button[class='confirm']").click(function() {
                    OpenLoadingElement();
                });
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
                closeOnConfirm: false,   closeOnCancel: true ,
                animation:false,
                allowOutsideClick: false}, 
                function(isConfirm){  
                    OpenLoadingElement(); 
                  if (isConfirm) {
                    if(fn_public != undefined && fn_public != "" && fn_public != null) {
                      eval(fn_public);
                      fn_public = '';
                      CloseLoading();
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
            function ConfirmBox(fn,text) {
                if(text == undefined || text == null || text == "") {
                    text = "{!! res('approve_question_desc') !!}";
                }
                $("button[class='confirm']").click(function(event) {
                    OpenLoadingElement();
                    event.preventDefault();
                    event.stopPropagation();
                    
                });
              swal({   
                title: "{!! res('approve_question') !!}?",   
                text: text,   
                type: "warning",   showCancelButton: true,   
                html:true,
                animation:false,
                confirmButtonColor: "#81C868",   
                confirmButtonText: "{!! res('ok') !!}",   
                cancelButtonText: "{!! res('cancel') !!}",   
                closeOnConfirm: false,   closeOnCancel: true ,
                allowOutsideClick: false}, 
                function(isConfirm){   
                    OpenLoadingElement();
                  if (isConfirm) {
                    fn();
                  } else {
                    CloseLoading();
                  }
                });
            }
            function DeleteAjax(val) {
                var check = $("#Checking").val();
                var arr = check.split(',');
                if(val.prop('checked') == true) {
                    if($.inArray(val.val(), arr) == -1) {
                        check += "," + val.val();
                    }
                } else {
                    check = check.replace("," + val.val(),'')
                }
                $("#Checking").val(check);
            }

            function TriggerMenu() {
                var val = 0;
                if($.cookie("{{ config('legacy.cookies.admin_menu') }}") == "0") {
                    val = 1;
                } else {
                    $.cookie("{{ config('legacy.cookies.admin_menu') }}", 1,{ path: '{{ config('legacy.cookie_path') }}' });
                }
                $.cookie("{{ config('legacy.cookies.admin_menu') }}", val ,{ path: '{{ config('legacy.cookie_path') }}' });
            }


            function currency_number(n) {
                var parts=n.toString().split(".");
                return parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",") + (parts[1] ? "." + parts[1] : "");
            }

            function clearform() {
              $("form")[0].reset();
            }

            function getPrice(fn, user,type,weight,width,height,long,amount) {
              $.ajax({
                  type: 'POST',
                  async: true,
                  data : 'user=' + user + '&type=' + type + '&weight=' + weight
                          + '&width=' + width + '&height=' + height + '&long=' + long + '&amount=' + amount,
                  url: rooturl + 'get_price',
                  success: function(data) {
                    fn(data);
                  }
              });
            }

            function nstr(value) {
              value = value.replace( /,/g, "" );
              return value;
            }

            function searchlink() {
                window.location.href = rooturl + '?l=' + encodeURIComponent($("#searchlink").val()) + '&page=checkout_product';
            }

            function switchimage(element) {
                var href = element.attr('src');
                $("#search_main_image").attr('src', href);
                $("#search_main_image_href").attr('href', href);
                $("input[name='search_main_image']").val(href);

            }
            function switchcolor(element) {
                $(".search_color_item").removeClass("select");
                element.addClass("select");
                $("input[name='search_color']").val(element.text());
                $("input[name='search_color_a']").val(element.attr('a'));
                changesku();
            }

            function switchsize(element) {
                $(".search_size_item").removeClass("select");
                element.addClass("select");
                $("input[name='search_size']").val(element.text());
                $("input[name='search_size_a']").val(element.attr('a'));
                changesku();
            }

            function switchcolorimage(element) {
                $(".search_color_item").removeClass("select");
                element.addClass("select");
                var href = element.find('img').attr('src');
                $("#search_main_image").attr('src', href);
                $("#search_main_image_href").attr('href', href);
                // val = ชื่อตัวเลือกจริง ถ้าไม่มีค่อยถอยไปใช้ URL รูป
                $("input[name='search_color']").val(element.attr('val') || element.find('img').attr('src'));
                $("input[name='search_main_image']").val(element.find('img').attr('src'));
                $("input[name='search_color_a']").val(element.attr('a'));
                changesku();
            }

            function switchsizeimage(element) {
                $(".search_size_item").removeClass("select");
                element.addClass("select");
                var href = element.find('img').attr('src');
                $("#search_main_image").attr('src', href);
                $("#search_main_image_href").attr('href', href);
                // val = ชื่อตัวเลือกจริง ถ้าไม่มีค่อยถอยไปใช้ URL รูป
                $("input[name='search_size']").val(element.attr('val') || element.find('img').attr('src'));
                $("input[name='search_main_image']").val(element.find('img').attr('src'));
                $("input[name='search_size_a']").val(element.attr('a'));
                changesku();
            }

            function switchpricedown() {
                var oldval = $("#search_amount").val();
                if(oldval <= 1) {
                    oldval = 1;
                } else {
                    oldval = eval(oldval) - 1;
                }

                $("#search_amount").val(oldval);
                if (priceRange && priceRange.length > 0) {
                    var amount = eval($("#search_amount").val());
                    for(var i in priceRange) {
                        if (amount >= priceRange[i][0]) {
                            $("#search_price").val(priceRange[i][1]);
                        }
                    }
                }
                cal_thaiprice_cart();
            }

            function switchpricedown_in(ele, id) {
                var oldval = ele.parent().find("input[name='search_amount']").val();
                if(oldval <= 1) {
                    oldval = 1;
                } else {
                    oldval = eval(oldval) - 1;
                }

                $.ajax({
                      type: 'POST',
                      async: true,
                      data : 'id=' + id + '&value=' + oldval,
                      url: rooturl + '_cart/save_amount',
                      success: function(data) {
                        LoadCart();
                      }
                });

                ele.parent().find("input[name='search_amount']").val(oldval);
                
            }
            function switchpriceup() {
                var oldval = $("#search_amount").val();
                oldval = eval(oldval) + 1;
                $("#search_amount").val(oldval);
                if (priceRange && priceRange.length > 0) {
                    var amount = eval($("#search_amount").val());
                    for(var i in priceRange) {
                        if (amount >= priceRange[i][0]) {
                            $("#search_price").val(priceRange[i][1]);
                        }
                    }
                }
                cal_thaiprice_cart();
            }

            function switchpriceup_in(ele , id) {
                var oldval = ele.parent().find("input[name='search_amount']").val();
                oldval = eval(oldval) + 1;

                $.ajax({
                      type: 'POST',
                      async: true,
                      data : 'id=' + id + '&value=' + oldval,
                      url: rooturl + '_cart/save_amount',
                      success: function(data) {
                        LoadCart();
                      }
                });

                ele.parent().find("input[name='search_amount']").val(oldval);
            }


            function submittingtocart() {
                if($("#search_amount").val() != "0") {
                    if($("input[name='search_color']").val() == "" && $(".search_color_item").length != 0) {
                        ErrorBox('{!! res('please_select_color') !!}');
                        return;
                    }
                    if($("input[name='search_size']").val() == "" && $(".search_size_item").length != 0) {
                        ErrorBox('{!! res('please_select_size') !!}');
                        return;
                    }
                    $("#searchdetail").submit();
                    $("#searchlink").val('');
                } else {
                    ErrorBox('กรุณาใส่จำนวนสินค้า');
                }
            }

            setTimeout(function() {
                $("#searchdetail").validationEngine();
                $("#searchdetail").ajaxForm({ 
                    success: function(data) {
                        //data = $.parseJSON(data);
                        eval(data);
                    }
                });


            }, 1000);

            function LoadCart() {
                $("#cart_item").html('{!! res('loading_cart') !!}');
                $.ajax({
                  type: 'POST',
                  async: true,
                  url: rooturl + '_cart/load_cart',
                  success: function(data) {
                    $("#cart_item").html(data);
                  }
              });
            }

            function RemoveCart(index) {
                ConfirmBox(function() {
                    $.ajax({
                          type: 'POST',
                          async: true,
                          data : 'index=' + index,
                          url: rooturl + '_cart/delete_cart',
                          success: function(data) {
                            eval(data);
                            LoadCart();
                          }
                      });
                });
                
            }

            function Switcher(is_disable) {
                if(!is_disable) {
                    var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
                    elems.forEach(function(html) {
                      var switchery = new Switchery(html);
                    });
                } else {
                    var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));

                    elems.forEach(function(html) {
                      var switchery = new Switchery(html, { disabled: true });
                    });
                }
                
            }

            function Fixed(nStr, point) {
                nStr = nStr.replace(',', '');
                var spt = nStr.split('.');
                var vv = '';

                for(var i = spt[0].toString().length; i >= 0; i--) {
                    if(spt[0][i] != undefined) {
                        vv = spt[0][i] + vv;
                    }
                }

                if(spt[0].toString().length == 0) {
                    vv = vv + "0";
                }


                vv = vv + ".";

                for(var i = 0; i < point; i++) {
                    try {
                        if(spt[1][i] == "") {
                            vv = vv + "0";
                        } else {
                            if(spt[1][i] == undefined) {
                               vv = vv + "0";
                            } else {
                                vv = vv + spt[1][i];
                            }
                        }
                    } catch(err) { vv = vv + "0"; }
                }

                return vv;
            }
            function addCommas(nStr)
            {
                

                nStr += '';
                x = nStr.split('.');
                x1 = x[0];
                x2 = x.length > 1 ? '.' + x[1] : '';
                var rgx = /(\d+)(\d{3})/;
                while (rgx.test(x1)) {
                    x1 = x1.replace(rgx, '$1' + ',' + '$2');
                }
                return x1 + x2;
            }

            function Logout() {
                OpenLoading();
                $.ajax({
                    type: 'POST',
                    async: true,
                    data : 'action=logout' ,
                    url: rooturl + 'system_checkuser',
                    success: function(data) {
                        window.location.href = rooturl;
                    }
                });
            }

            // ให้เสียงแจ้งเตือนดังแค่แท็บเดียว — เปิดหลายแท็บจะได้ไม่ดังพร้อมกันรัว ๆ
            // ใช้ localStorage เป็นตัวจอง เพราะทุกแท็บของโดเมนเดียวกันอ่าน/เขียนค่าเดียวกันได้
            // แท็บไหนจองทัน = เล่นเสียง · แท็บที่เหลือเห็นว่าเพิ่งมีคนจองใน 3 วิ ก็เงียบไป
            // (โหมดส่วนตัวบางเบราว์เซอร์ใช้ localStorage ไม่ได้ -> ปล่อยให้ดังตามเดิม ดีกว่าเงียบสนิท)
            // เจ้าของสั่ง: พอร์ทัลนี้เปิดแจ้งเตือนแต่ "ยังไม่เอาเสียง"
            // อยากเปิดเสียงเมื่อไหร่ เปลี่ยนบรรทัดล่างเป็น true บรรทัดเดียวจบ
            var NOTI_SOUND_ENABLED = false;
            function notiSoundAllowed() {
                if (!NOTI_SOUND_ENABLED) { return false; }
                try {
                    var now = Date.now();
                    var last = parseInt(localStorage.getItem("noti_sound_at") || "0", 10);
                    if (now - last < 3000) { return false; }
                    localStorage.setItem("noti_sound_at", String(now));
                    return true;
                } catch (e) {
                    return true;
                }
            }

            function notifyMe() {
                if (notiSoundAllowed()) {
                    var audio = new Audio('{{ legacy_href() }}assets/images/ding.mp3');
                    audio.play();
                }
              // if (!("Notification" in window)) {
              //   alert("This browser does not support desktop notification");
              // } else if (Notification.permission === "granted") {
              //   var options = {
              //       body: "กรุณาตรวจสอบการแจ้งเตือน",
              //       icon: "icon.jpg",
              //       dir : "ltr"
              //   };
              //   var notification = new Notification("การแจ้งเตือน",options);
              // } else if (Notification.permission !== 'denied') {
              //     Notification.requestPermission(function (permission) {
              //     if (!('permission' in Notification)) {
              //       Notification.permission = permission;
              //     }

              //     if (permission === "granted") {
              //       var options = {
              //               body: "กรุณาตรวจสอบการแจ้งเตือน",
              //               icon: "icon.jpg",
              //               dir : "ltr"
              //         };
              //       var notification = new Notification("Hi there",options);
              //     }
              //   });
              // }
            }
        </script>


        <script>window.FastClick=window.FastClick||{attach:function(){}};/* no-op shim (2026-08-30): fastclick.js ถูกถอด กัน jquery.app.js เรียก FastClick แล้ว throw */</script>
        @stack('head')
    </head>


    <?php 
    if($page == 'admin_lot_detail') {
        echo '<body class="widescreen fixed-left" style="min-width: 1224px!important;">';
    } else {
        echo '<body class="widescreen fixed-left" style="min-width: 1024px!important;">';
    }
    ?>
        <div id="wrapper" class="<?php if($isMobile) { echo "enlarged"; } ?> forced">
            <!-- Top Bar Start -->
            <div class="topbar">

                <!-- LOGO -->
                <div class="topbar-left">
                    <div class="text-center">
                        <a href="{{ legacy_href() }}" class="logo">

                        <span><img  src="{{ legacy_href() }}assets/images/logo_mini.png?v={{ config('legacy.version') }}" style="width: 135px;" /></span>

                        <img style="margin-top: 5px;margin-left: 12px;width: 135px;" style="margin-top: 10px;margin-left: 12px;" class="icon-c-logo " src="{{ legacy_href() }}assets/images/logo_mini.png?v={{ config('legacy.version') }}" />
                        </a>
                    </div>
                </div>

                <!-- Button mobile view to collapse sidebar menu -->
                <div class="navbar navbar-default" role="navigation">
                    <div class="container">
                        <div class="">
                            

                           <!-- <div role="search" class="navbar-left app-search pull-left hidden-xs">
                                 <input id="quick_search" type="text" placeholder="{!! res('quick_search') !!}" class="form-control">
                                 <a href="#" onclick="LoadQuickSearch();return false;"><i class="fa fa-search"></i></a>
                            </div> -->

                            @if ($isMobile || in_array($page, ['admin_lot_detail', 'payment_create', 'admin_payment_create'], true))
                                @include('legacy.partials.menu_mobile')
                            @endif 

                            <ul
                                <?php
                                    // if($isMobile) {
                                    //     echo 'style="width: 70%;height: 58px;overflow-x: scroll;"';
                                    // }
                                ?>
                             class="nav navbar-nav navbar-right pull-right" id="notification" >
                                
                            </ul>
                        </div>
                        <!--/.nav-collapse -->
                    </div>
                </div>
            </div>
            <!-- Top Bar End -->








            <!-- ========== Left Sidebar Start ========== -->
            <?php if(!$isMobile 
                && $page != 'admin_lot_detail' 
                && $page != 'payment_create'
                && $page != 'admin_payment_create') { ?>
            <div class="left side-menu">
                <div class="sidebar-inner slimscrollleft">
                    <!--- Divider -->
                    @include('legacy.partials.menu')
                    <div class="clearfix"></div>
                </div>
            </div>
            <?php } ?>
            <!-- Left Sidebar End -->



            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->
            <div class="content-page" <?php if($isMobile) { echo "style='margin-left: 0px!important;'"; } ?>>

                <!-- Start content -->
                <div class="content">
                   

                    <div class="container">


                    <?php
                        if(\App\Support\Legacy\Lookup::getSetting('today_lot') != '') { echo '<div class="col-xs-12" style="margin-bottom: 15px;font-size: 15px;border: 1px solid #a0a0a0;padding: 10px;border-radius: 5px;background-color: #ff9f9f;"><b>หมายเลขล๊อตสินค้าที่เข้าโกดังไทยคืนนี้ : </b>'.nl2br(\App\Support\Legacy\Lookup::getSetting('today_lot'))."</div>"; }
                    ?>

                    <?php
                        if(\App\Support\Legacy\Lookup::getSetting('today_lot_yellow') != '') { echo '<div class="col-xs-12" style="margin-bottom: 15px;font-size: 15px;border: 1px solid #a0a0a0;padding: 10px;border-radius: 5px;background-color: #ffbd4a;">'.nl2br(\App\Support\Legacy\Lookup::getSetting('today_lot_yellow'))."</div>"; }
                    ?>

                    <?php
                        if(\App\Support\Legacy\Lookup::getSetting('today_lot_green') != '') { echo '<div class="col-xs-12" style="margin-bottom: 15px;font-size: 15px;border: 1px solid #a0a0a0;padding: 10px;border-radius: 5px;background-color: #81c868;">'.nl2br(\App\Support\Legacy\Lookup::getSetting('today_lot_green'))."</div>"; }
                    ?>

                    
                    <?php if(perm('order')) { ?>
                    <div class="col-xs-10" style="margin-bottom: 15px;">
                            <input style="font-weight: normal;" type="text" id="searchlink" class="form-control" placeholder="วางลิ้งค์สินค้าเพื่อค้นหา" value="">
                    </div>

                    <div class="col-xs-2" style="margin-bottom: 15px;margin-top: 2px;">
                        <button onclick="searchlink();" type="button"  class="pull-right btn btn-success waves-effect waves-light">
                        <span class="btn-label"><i class="fa fa-search"></i></span>
                        {!! res('searching_now') !!}
                        </button>
                    </div>
                    <?php } ?>
                    
                      @yield('content')

                    </div> <!-- container -->
                    <div id="cd-shadow-layer"></div>
                    <div id="cd-cart">
                        <h2>{!! res('cart') !!}
                            <div class="form-group">
                                <div class="col-md-12">
                                    <div class="input-group ">
                                        <input style="font-weight: normal;" type="text" id="searchlink" class="form-control"  placeholder="{!! res('put_link') !!}" value="" >
                                        <span class="input-group-addon" onclick="searchlink();" style="cursor:pointer">
                                            <i class="icon-magnifier"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </h2>
                        <div id="cart_item">

                        </div>
                        
                        <p class="text-danger">* ราคาข้างต้นเป็นราคาโดยประมาณ</p>
                        <a href="{{ legacy_href() }}cart/checkout" class="checkout-btn">{!! res('confirm_order') !!}</a>
                    </div> <!-- cd-cart -->
                </div> <!-- content -->

                <footer class="footer text-right">
                    © 2016-{{ date('Y') }} {{ config('legacy.project_name') }} All rights reserved. 
                </footer>

            </div>


            <!-- ============================================================== -->
            <!-- End Right content here -->
            <!-- ============================================================== -->


            

        </div>
        <!-- END wrapper -->
        

        <form id="searchdetail" action="{{ legacy_href() }}_cart/save_product" method="post" >
            <div id="panel-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content p-0 b-0">
                        <div class="panel panel-color panel-primary">
                            <div class="panel-heading"> 
                                <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> 
                                <h3 class="panel-title">{!! res('product_detail') !!}</h3> 
                            </div> 
                            <div class="panel-body"> 
                                
                            </div> 
                            <div class="panel-footer text-right">
                                <button type="button" class="btn btn-default waves-effect" type="button" onclick="$('.modal').modal('hide');">{!! res('close') !!}</button>
                                <button type="button" class="btn btn-primary waves-effect waves-light" onclick="submittingtocart()" type="button">{!! res('add_to_list') !!}</button>
                            </div>
                            
                        </div>

                    </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
            </div><!-- /.modal -->
        </form>

        <script src="{{ legacy_href() }}assets/js/bootstrap.min.js"></script>
        <script src="{{ legacy_href() }}assets/js/detect.js"></script>
        <?php if(!$isMobile) { ?>
        <?php } ?>
        <script src="{{ legacy_href() }}assets/js/jquery.slimscroll.js"></script>
        <script src="{{ legacy_href() }}assets/js/jquery.blockUI.js"></script>
        <script src="{{ legacy_href() }}assets/js/waves.js"></script>
        <script src="{{ legacy_href() }}assets/js/wow.min.js"></script>
        <script src="{{ legacy_href() }}assets/js/jquery.nicescroll.js"></script>
        <script src="{{ legacy_href() }}assets/js/jquery.scrollTo.min.js"></script>

        <script src="{{ legacy_href() }}assets/plugins/peity/jquery.peity.min.js"></script>

        <!-- jQuery  -->
        <script src="{{ legacy_href() }}assets/plugins/waypoints/lib/jquery.waypoints.js"></script>
        <script src="{{ legacy_href() }}assets/plugins/counterup/jquery.counterup.min.js"></script>



        <script src="{{ legacy_href() }}assets/plugins/morris/morris.min.js"></script>
        <script src="{{ legacy_href() }}assets/plugins/raphael/raphael-min.js"></script>

        <script src="{{ legacy_href() }}assets/plugins/jquery-knob/jquery.knob.js"></script>

        {{-- jquery.dashboard.js ตัดออก 2026-10-19: เป็นกราฟตัวอย่างของ template (ข้อมูลปลอม 2005–2015) ไม่มีหน้าไหนมีกล่องกราฟ → error "Graph container element not found" ทุกหน้า --}}

        <script src="{{ legacy_href() }}assets/js/jquery.core.js"></script>
        <script src="{{ legacy_href() }}assets/js/jquery.app.js"></script>


        @stack('scripts')
    </body>
</html>