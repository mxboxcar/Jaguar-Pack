{{-- register.php ของ SR (แปลงจาก sr-prehotfix/register.php — รายการประเภทผู้ใช้จาก RegisterController, กล่องแนบบัตรประชาชน = getupload() เดิม) --}}
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="author" content="Coderthemes">

        <link rel="icon" href="{{ legacy_href() }}icons/favicon.ico" sizes="any"><link rel="icon" type="image/png" sizes="32x32" href="{{ legacy_href() }}icons/favicon-32.png"><link rel="apple-touch-icon" href="{{ legacy_href() }}icons/apple-touch-icon.png"><link rel="manifest" href="{{ legacy_href() }}icons/site.webmanifest"><meta name="theme-color" content="#9b1c20">

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
        <link href="{{ legacy_href() }}assets/dropper/jquery.fs.dropper.min.css?version=" rel="stylesheet">
        <link href="{{ legacy_href() }}assets/plugins/ladda-buttons/css/ladda-themeless.min.css" rel="stylesheet" type="text/css" />
        <link href="{{ legacy_href() }}assets/plugins/sweetalert/dist/sweetalert.css" rel="stylesheet" type="text/css">
        <link href="{{ legacy_href() }}assets/validate/validationEngine.jquery.css?version=" rel="stylesheet" type="text/css">
        <!-- HTML5 Shiv and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
        <![endif]-->
        <script src="{{ legacy_href() }}assets/js/jquery.min.js"></script>
        <script src="assets/js/modernizr.min.js"></script>
        <script src="{{ legacy_href() }}assets/dropper/jquery.fs.dropper.min.js?version="></script>
        
        
        
        
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
            var rooturl = '{{ legacy_href() }}';
            var rooturl_blob = '{{ legacy_blob() }}';
            var fn_public = '';
            var resizefunc = [];

            $(function() {
                $(".dropper-input").css({"margin-top": "-34px",
                    "height": "34px",
                    "width": "125px"});
            });
            
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


            $("form").validationEngine();
            $("form").ajaxForm({ 
                success: function(data) {
                    eval(data);
                    
                }
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

            function sumiting() {
                if($("form").validationEngine('validate') == false) 
                { 
                    CloseLoading(); 
                } else {
                    ConfirmBox(function () {
                        if($("input[name='accept']").prop('checked')) {
                            $("form").eq(0).submit();
                        } else {
                            ErrorBox('{!! res('please_accept_condition') !!}', 'CloseLoading()');
                        }
                    });
                }
            }


        </script>

        <script>window.FastClick=window.FastClick||{attach:function(){}};/* no-op shim (2026-08-30): fastclick.js ถูกถอด กัน jquery.app.js เรียก FastClick แล้ว throw */</script>
    </head>
    <body>

        <div class="account-pages"></div>
        <div class="clearfix"></div>
        <div class="container-alt">
        <div class="row">
        <div class="col-sm-10 col-sm-offset-1">
            <div class="wrapper-page signup-signin-page">
                <div class="card-box">
                <div class="form-group">
                    <div class="pull-left">
                        <a href="#" onclick="Language('th');return false;">ภาษาไทย</a>, <a href="#" onclick="Language('cn');return false;">中国</a>
                    </div>
                    <div class="pull-right">
                        <a href="{{ legacy_href() }}login" class="text-dark"><i class="fa fa-arrow-left r-5"></i> {!! res('back_to_login') !!}</a>
                    </div>
                </div>
                    <div class="panel-heading">
                        <h3 class="text-center"><img style="width: 330px;" src="{{ legacy_href() }}assets/images/logo_orange.png?s=s" /></h3>
                    </div>
    
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="p-20">
                                <form method="POST" class="form-horizontal m-t-20" action="{{ legacy_href() }}_register/save">
                                        <h4><b>{!! res('register') !!}</b></h4>
                                        <div class="form-group">
                                            <div class="col-xs-12">
                                                <input class="form-control validate[required,custom[onlyLetterSp]]"  type="text" name="username" required="" placeholder="{!! res('username_regis') !!}">
                                            </div>
                                        </div>
                
                                        <div class="col-xs-12  text-danger">*กรุณากรอกชื่อภาษาอังกฤษโดยพิมพ์ชื่อและนามสกุลติดกัน ไม่ต้องเว้นวรรค</div>

                                        <div class="form-group ">
                                            <div class="col-xs-12">
                                                <input class="form-control validate[required,custom[email]]" type="email" name="email" required="" placeholder="{!! res('email') !!}">
                                            </div>
                                        </div>

                                        <div class="form-group ">
                                            <div class="col-xs-3 control-label">
                                                {!! res('user_type') !!}
                                            </div>
                                            <div class="col-xs-7">
                                                <div class="radio radio-primary" style="padding:0px">
                                                {!! $userTypesHtml !!}
                                                    
                                                </div>
                                            </div>
                                        </div>

                                        <div style="height:20px"></div>
                                        <h4><b>{!! res('personal_info') !!}</b></h4>

                                        <div class="form-group ">
                                            <div class="col-xs-12">
                                                <input class="form-control validate[required,onlyLetter]" type="name_lastname" name="name" required="" placeholder="{!! res('namelast_regis') !!}">
                                            </div>
                                        </div>

                                        <div class="form-group ">
                                            <div class="col-xs-12">
                                                <textarea name="address" class="form-control validate[required, minSize[25]]"  placeholder="{!! res('address_regis') !!}"></textarea>
                                            </div>
                                        </div>

                                        <div class="form-group ">
                                            <div class="col-xs-5">
                                                <input class="form-control validate[required]" type="phone" name="phone" required="" placeholder="{!! res('phone_regis') !!}">
                                            </div>
                                        </div>
                                        <div class="form-group ">
                                            <div class="col-xs-5">
                                                <input class="form-control validate[required]" type="phone" name="phone2" required="" placeholder="{!! res('phone_regis_2') !!}">
                                            </div>
                                        </div>
                                        <div class="form-group ">
                                            <div class="col-xs-4">
                                                <input class="form-control validate[required]" type="text" name="lineid" required="" placeholder="Line ID">
                                            </div>
                                        </div>
                                        <div class="form-group ">
                                            <div class="col-xs-4">
                                                <input class="form-control" type="text" name="wechatid" required="" placeholder="WeChat ID">
                                            </div>
                                        </div>
                                        <div class="form-group ">
                                            <div class="col-xs-3 control-label">
                                                {!! res('identity') !!}
                                            </div>
                                            <div class="col-xs-6">
                                                {!! \App\Domain\Users\UploadBox::single('file', '', true, true, 'col-xs-12') !!}
                                            </div>
                                        </div>
                                        <div class="form-group ">
                                            <div class="col-xs-12 control-label text-danger">
                                                {!! res('regis_remark') !!}
                                            </div>
                                            
                                        </div>

                                        <div style="height:20px"></div>
                                        <h4><b>{!! res('condition') !!}</b></h4>


                                        <div class="form-group ">
                                            <div class="col-xs-12">
    <p style="padding-top:10px;">*** รบกวนลูกค้าทุกท่านอ่านให้ละเอียดก่อนสมัครสมาชิก หากได้ทำการสมัครสมาชิกแล้วทางบริษัทถือว่าท่านได้ตกลงในเงื่อนไขการให้บริการ ***</p>
    <p style="padding-top:10px;">ข้อตกลงการใช้บริการ</p>
    <p style="padding-top:10px;">เงื่อนไขและข้อตกลงนี้ถือว่าเป็นอันเข้าใจกันดีแล้วทั้งสองฝ่าย ซึ่งต่อไปนี้ขอแทน SRShippingSystem ว่า“ บริษัท” และแทนลูกค่าว่า “ท่าน”</p>
    <p style="padding-top:10px;">โดยท่านต้องยอมรับเงื่อนไข ขอบเขตความรับผิดชอบของทางบริษัท ก่อนใช้บริการ ดังต่อไปนี้</p>

    <p style="padding-top:10px;">1. ในกรณีที่ท่านใช้บริการฝากสั่งกับทางบริษัท (ให้บริษัทติดต่อซื้อสินค้าให้) ทางบริษัทเป็นเพียงตัวกลางในการติดต่อซื้อขาย จากร้านค้าที่ท่านเลือกมา ไม่มีส่วนในการรับผิดชอบการันตีความผิดพลาดของร้านค้าจีน อาทิเช่น สินค้าไม่เหมือนแบบ แตกหัก ส่งผิดไซส์ ส่งไม่ครบ ไม่คืนเงิน เป็นต้น ทางบริษัทยินดีเป็นสื่อกลางในการทวงถามติดตามเท่านั้น</p>
    <p style="padding-top:10px;">2. ในกรณีที่ท่านสั่งสินค้าเอง (ใช้แต่บริการขนส่ง)</p>
    <p style="padding-top:10px;">กรณีมีปัญหากับร้านค้าจีนนั้น ทางบริษัทมีค่าใช้จ่ายในการติดต่อทวงถาม แต่ไม่รับประกันผลการรับผิดชอบจากร้านค้าจีนได้ เนื่องจากท่านเป็นผู้เลือกร้านค้านั้นเอง ทางบริษัทเป็นเพียงสื่อกลางในการติดต่อให้เท่านั้น</p>
    <p style="padding-top:10px;">3. กรณีที่สินค้าสูญหายจากการขนส่งทางบริษัทรับผิดชอบตามมูลค่าสินค้าตามจริงไม่เกิน 3 เท่าของค่าขนส่งสินค้าที่สูญหาย ท่านสามารถทําประกันสินค้าโดยชําระ 5% ของราคาสินค้าเมื่อสูญหายทางบริษัทรับผิดชอบเต็ม 100% ของจำนวนมูลค่าสินค้านั้นๆ โดยการทำประกันสามารถติดต่อแจ้งกับทางบริษัทได้เลยคะ</p>
    <p style="padding-top:10px;">4. ทางบริษัทขอสงวนลิขสิทธิ์ไม่รับผิดชอบในกรณีสินค้าเสียหาย แตกหัก จากการบรรจุภัณฑ์ไม่เหมาะสม กรณีสินค้าที่แตกหักเสียหายง่าย ท่านควรแจ้งทางบริษัทหรือร้านค้าให้ตีลังสินค้า</p>

    <p style="padding-top:10px;">เนื่องจากการขนส่งระหว่างประเทศจะมีการย้ายตู้ สินค้าจากตู้เดิมจะถูกขนย้ายสู่ตู้ใหม่ พนักงานขนถ่ายสินค้าจัดสินค้าตามขนาดของหีบห่อที่บรรจุรวมจะไม่สามารถรับประกันถึงความชำรุดเสียหายนี้ได้ หากท่านได้สมัครสมาชิกและส่งสินค้าเข้าระบบ ทางเราถือว่าท่านได้รับทราบข้อตกลงนี้แล้วทุกกรณี</p>

    <p style="padding-top:10px;">5. ในกรณีสินค้าพิเศษและสินค้าต้องห้าม หากพบว่ามีการแอบส่งโดยไม่แจ้งทางบริษัท กรณีโดนยึด และเกิดปัญหาต่างๆ ลูกค้าต้องรับผิดชอบความเสียหายนั้น และบริษัทจะไม่รับผิดชอบใดๆทั้งสิ้น</p>
    <p style="padding-top:10px;">6. ในกรณีที่ท่านได้รับสินค้า แล้วพบว่าบรรจุภัณฑ์เสียหาย สินค้าหลุดตก สินค้ามีรอยกรีด เกิดความเสียหายถึงตัวสินค้า อันเนื่องมาจากการขนส่ง การสุ่มตรวจสินค้าที่ด่านไทย ทางบริษัทไม่สามารถรับผิดชอบต่อเหตุการณ์ต่างๆนี้ได้</p>
    <p style="padding-top:10px;">7. ในกรณีที่สินค้าถึงไทยแล้ว เพื่อการติดตามผลกับทางร้านค้า ว่าสินค้าที่สั่งมานั้นถูกต้องตามแบบ และครบตามจำนวนหรือไม่ เมื่อได้รับสินค้าท่านจะต้องรีบเช็คโดยเร็วที่สุด หากพบว่าสินค้าร้านค้าใดมีปัญหา ท่านต้องทำการเก็บห่อ/กล่องไว้ก่อน และรีบแจ้งทางบริษัทโดยทันที หากท่านมารับสินค้า หรือแจ้งทางบริษัทช้า ทางบริษัทขอแจ้งให้ท่านทราบว่า สินค้าอาจติดตามยากและท่านอาจจะไม่ได้รับการชดเชยใดๆทั้งสิ้นจากร้านค้าจีน ทางบริษัทไม่สามารถรับผิดชอบความผิดพลาดใดๆ ระหว่างท่านและร้านค้าจีนได้ ทำหน้าที่เป็นเพียงสื่อกลางในการติดตามทวงถามเท่านั้น</p>
    <p style="padding-top:10px;">8. ทางบริษัทขอสงวนสิทธิ์ในการแจ้งปรับเปลี่ยนราคาค่าขนส่งโดยไม่ต้องแจ้งให้ทราบล่วงหน้า ราคาที่เสนออาจมีการปรับเปลี่ยนได้ภายหลัง</p>
    <p style="padding-top:10px;">9. บริษัทขอสงวนสิทธิ์ไม่จัดส่งสินค้า หากยังไม่ได้รับการชำระเงิน</p>
    <p style="padding-top:10px;">10. บริษัทของสงวนสิทธิ์ในการตรวจเช็คสินค้าสินค้าในกรณีจําเป็นเพื่อระบุและบันทึกรายละเอียดสินค้า</p>
    <p style="padding-top:10px;">11. ระยะเวลาการขนส่งเป็นระยะเวลาโดยเฉลี่ย อาจเร็วหรือช้ากว่าที่แจ้ง ทางบริษัทไม่มีการรับประกันถึงความล่าช้าของสินค้าไม่สามารถรับผิดชอบในกรณีที่สินค้าติดตรวจ ติดภัยธรรมชาติ ทำให้สินค้ามาช้าไม่ทันขายตามระยะเวลาที่ท่านกำหนด</p>
    <p style="padding-top:10px;">12. ทางเราสามารถแก้ไขการเรียกเก็บเงินรายการต่างๆที่เกี่ยวข้องกับระบบภายหลังได้หากพบว่าระบบมีข้อผิดพลาดใดๆ</p>

    <p style="padding-top:10px;"></p>
    <p style="padding-top:10px;"></p>

    <p style="padding-top:10px;">各位尊敬的顾客，希望您能够在成为我们公司的会员之前仔细阅读我们公司的会员服务条款，若您成为了我们公司会员，则视为同意以下所有条款。</p>
    <p style="padding-top:10px;">服务协议</p>
    <p style="padding-top:10px;">为了使双方便于理解对以上所提及到的服务条款以及服务协议，以下，则将以“我方”代表SRShippingSystem公司，将以“您”代表每一位顾客。</p>
    <p style="padding-top:10px;">此后，当您接受我方的服务之前，必须要接受我方的服务条款以及责任范围。</p>

    <p style="padding-top:10px;">1. 通过我方购买商品（我方联系购买商品事宜）的情况；我方仅作为联系中介，帮助联系您所选择的卖家。我方对于中方卖家的过错概不负责。例如：货不对版、货物残缺、尺码不对、货物数量不足、退款问题，等等。但我方会帮助您联系中方卖家，反应相关事宜。</p>
    <p style="padding-top:10px;">2.您自行采购（使用我方物流运输服务）的情况；</p>
    <p style="padding-top:10px;">当您与中方卖家出现冲突或问题，我方的收取费用中已包含咨询、联系费用，但鉴于中方卖家为您所选择，因此我方并不对中方卖家的问题过错负责，我方仅作为联系中介，联系双方解决相关事宜。</p>
    <p style="padding-top:10px;">3. 货物在运输途中受损、丢失的情况；我方将根据货物损失的具体情况赔偿不超过该损失货物的运费的3倍金额。您可以为货物购买运费险，您仅需要支付货物价值总额的5%，若已投运费险的货物在运输途中受损、丢失，我方将全额赔偿货物金额。若您需要购买运费险，请联系我方公司或工作人员。</p>
    <p style="padding-top:10px;">4. 我方对于因包装不当而造成的货物丢失、损坏保留不负责任的权利。对于易碎、易损坏的货物您需要提前告知我方或让卖家为货物进行估价。</p>
    <p style="padding-top:10px;">5. 对于运输特殊商品、违禁品的情况；一旦发现没有告知我方，私自运送这些商品的情况，若日后发生任何问题，则由您承担一切责任，我方概不负责。</p>
    <p style="padding-top:10px;">6. 当您收到货物，发现货物包装不完整、货物脱落、货物有裂痕的情况是由于在运输过程中，泰方抽检而造成的货物损失，我方将不会对以上所述的情况承担责任。</p>
    <p style="padding-top:10px;">7. 当货物送至泰国，为了能与商家及时联系关于您所订购的货物有没有符合样版、货物数量有没有齐全这样的情况。希望您收货后能尽快检查货物，若发现有问题的货物，您需要将包裹的盒子或包装袋收起来然后马上联系我方工作人员。需要提醒您的是，若您没有及时检查包裹或联系我方，货物追踪难度加大而且中方卖家很大可能会对这些商品不负责或不赔偿。我方对于您和中方卖家之间任意一方出现的过错不承担责任，我方仅作为您和中方卖家之间的联系的渠道。</p>
    <p style="padding-top:10px;">8．我方保留所有关于运费提价的权利。我方无需提前告知。运费可能在日后有所提升。</p>
    <p style="padding-top:10px;">9．我方拥有对于不支付运费的货物不运送的权利。</p>
    <p style="padding-top:10px;">10.我方拥有检查制指定货物以及记录货物详情的权利。</p>
    <p style="padding-top:10px;">11. 我方所告知您的货物运输时间为大概的运输时间，真实的运输时间可能更快或更慢。我方不能保障货物不会因接受海关检查，遭遇自然灾害的影响而使得货物到达时间延迟而导致您不能在规定的时间内出售货物。</p>
    <p style="padding-top:10px;">12. 下单后，若发现关于系统的账单有任何错误，本公司能稍后更改所有账单信息。</p>
                                            </div>
                                        </div>
                
                                        <div class="form-group pull-right">
                                            <div class="col-xs-12">
                                                <div class="checkbox checkbox-primary">
                                                    <input id="checkbox-signup" type="checkbox" name="accept" >
                                                    <label for="checkbox-signup">{!! res('accept_condition') !!}</label>
                                                </div>
                                            </div>
                                        </div>
                
                                        <div class="form-group text-right m-t-20 m-b-0">
                                            <div class="col-xs-12">
                                                <button class="btn btn-pink text-uppercase waves-effect waves-light w-sm" type="button" onclick="sumiting()">
                                                    {!! res('register') !!}
                                                </button>
                                            </div>
                                        </div>
                                        
                                    </form>
                                </div>
                            </div>
                            
                        </div>
                        
                    </div>
                </div>
    
            </div>
        </div>
    </div>
    </div>
    </body>
</html>