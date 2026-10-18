{{-- _cart/product.php เดิม (page=checkout_product): ค้นหาสินค้าจากลิงก์ ?l=... --}}
@use('App\Support\Legacy\Alerts')
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
    <h4 class="page-title">{!! res('search_product') !!} <small>{{ $url }}</small></h4>
    <ol class="breadcrumb">
        <li>
            <a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
        </li>
        <li class="active">
            {!! res('search_product') !!}
        </li>
    </ol>
</div>

<div class="col-xs-7">
    <div class="card-box " style="padding: 10px;">
        <div class="row">
            <form id="searchdetail" action="{{ legacy_href() }}_cart/save_product" method="post">
                <div class="text-center" style="padding:100px 0px">
                    <img src="{{ legacy_href() }}assets/images/loading.gif" />
                    <div style="margin-top:20px;font-weight:bold">กำลังโหลดข้อมูลสินค้า...</div>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="col-xs-5">
    <h5>{!! res('cart') !!}</h5>
    <div class="card-box" id="cart_item" style="background-color: #f0faea;">

    </div>
</div>
<script>
    // ค่าจาก crawler (ชื่อ/รูป สี-ขนาดที่ร้านค้าตั้งเอง) ต่อเข้า HTML — เดิมไม่ escape = DOM XSS
    function _h(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
    $.ajax({
        type: 'POST',
        async: true,
        url: rooturl + '_cart/load_cart',
        success: function(data) {
            $("#cart_item").html(data);
            notification();
        }
    });




    var link = '{!! Alerts::js($link) !!}';

    if (link != "") {
        $.ajax({
            timeout: 30000,
            error: function(xhr, textStatus, errorThrown) {
                ErrorBox("พบข้อผิดพลาด", true);
                setTimeout(function() {
                    CloseLoading();
                }, 3000);
            },
            type: 'POST',
            async: true,
            data: 'url=' + encodeURIComponent('{!! Alerts::js($link) !!}'),
            url: rooturl + '_api/load_product',
            success: function(data) {


                data = $.parseJSON(data); if (data && data.error === 'true' && data.message) { ErrorBox(data.message, 'CloseLoading()'); return; } // crawl_guard
                if (data.product_name != "") {
                    CloseLoading();
                    load_choose_product_plan(data);
                } else {
                    ErrorBox("พบข้อผิดพลาด", 'CloseLoading()');
                }


            }
        });
    } else {
        $("#searchdetail").html('');
    }

    var sku = '';
    var priceRange = '';

    function load_choose_product_plan(data) {
        $.ajax({
            timeout: 30000,
            error: function(xhr, textStatus, errorThrown) {
                ErrorBox("พบข้อผิดพลาด", true);
                setTimeout(function() {
                    CloseLoading();
                }, 3000);
            },
            type: 'POST',
            async: true,
            url: rooturl + '_cart/load_choose_product',
            success: function(page) {

                sku = data.skus;
                priceRange = data.priceRange;




                if (data.price == null || data.shop_name == "") {
                    var html = '';
                    html += '<div class="text-center" style="padding:80px 0px">';
                    html += '<img src="{{ legacy_href() }}assets/images/error.png" />';
                    html += '<div style="margin-top:20px;font-weight:bold">ไม่พบข้อมูลสินค้า !</div>';
                    html += '</div>';
                    $("#searchdetail").html(html);
                } else {
                    $("#searchdetail").html(page);
                    //hiddpath
                    $("input[name='search_main_image']").val(data.main_image);
                    $("input[name='search_price']").val(data.price);
                    $("input[name='search_product_name']").val(data.product_name);
                    $("input[name='search_shop_name']").val(data.shop_name);
                    $("input[name='search_url']").val(data.url);

                    $("#search_main_image").attr('src', data.main_image);
                    $("#search_product_name").text(data.product_name);
                    $("#search_shop_name").text(data.shop_name);
                    $("#search_main_image_href").attr('href', data.main_image);
                    $("#search_url").attr('href', data.url);

                    $("#search_external_image_div").html('');
                    $("#search_external_image").html('');

                    if (data.external_image != undefined) {
                        for (var i = 0; i < data.external_image.length; i++) {
                            if (data.external_image.indexOf('lazyload.png') == -1) {
                                var html = '<div style="margin-top:3px;padding:0px" class="col-xs-3"><img src="' + _h(data.external_image[i]) + '" onclick="switchimage($(this))" style="cursor:pointer;max-width: 70px;max-height: 35px;"  /></div>';
                                $("#search_external_image_div").append(html);
                                html = '<input type="hidden" value="' + _h(data.external_image[i]) + '" name="search_external_image[]" />'
                                $("#search_external_image").append(html);
                            }
                        }
                    }



                    $("#search_price").val(data.price);
                    $("#search_price_thai").html('<span class="currency">' + (data.price * eval($("#search_price_thai").attr('rate'))).toFixed(2) + '</span>&nbsp;฿');

                    $("#search_color").html('');

                    if (data.method == "2") {
                        if (data.color.length != 0) {
                            if (data.color_type == 'radio') {
                                for (var i = 0; i < data.color.length; i++) {
                                    if (data.color[i].search("#") >= 0 && data.color[i].length == 7) {
                                        var html = '';
                                        if (data.url.search('alibaba') >= 0) {
                                            html = '<div class="search_color_item " onclick="switchcolorborder($(this))" style="height:30px;width:30px;background-color:' + _h(data.color[i]) + '"></div>';
                                        } else {
                                            html = '<div class="search_color_item " onclick="switchcolor($(this))" >' + _h(data.color[i]) + '</div>';
                                        }
                                        $("#search_color").append(html);
                                    } else if (data.color[i].search("//") >= 0) {
                                        var html = '<div class="search_color_item col-xs-2" onclick="switchcolorimage($(this))" ><img src="' + _h(data.color[i]) + '" class="img-responsive" /></div>';
                                        $("#search_color").append(html);
                                    } else {
                                        var html = '<div class="search_color_item " onclick="switchcolor($(this))" >' + _h(data.color[i]) + '</div>';
                                        $("#search_color").append(html);
                                    }
                                }

                            } else {
                                //option
                                for (var i = 0; i < data.color.length; i++) {
                                    if (data.color[i].search("#") >= 0 && data.color[i].length == 7) {
                                        var html = '';
                                        if (data.url.search('alibaba') >= 0) {
                                            html = '<div class="search_color_item " onclick="switchcolorborder($(this))" style="height:30px;width:30px;background-color:' + _h(data.color[i]) + '"></div>';
                                        } else {
                                            html = '<div class="search_color_item " onclick="switchcolor($(this))" >' + _h(data.color[i]) + '</div>';
                                        }
                                        $("#search_color").append(html);
                                    } else if (data.color[i].search("//") >= 0) {
                                        var html = '<div class="search_color_item col-xs-2" onclick="switchcolorimage($(this))" ><img src="' + _h(data.color[i]) + '" class="img-responsive" /></div>';
                                        $("#search_color").append(html);
                                    } else {
                                        var html = '<div class="search_color_item " onclick="switchcolor($(this))" >' + _h(data.color[i]) + '</div>';
                                        $("#search_color").append(html);
                                    }
                                }
                            }
                        } else {
                            $("#search_color").parent('.form-group').css('display', 'none');
                        }

                        if (data.size.length != 0) {
                            for (var i = 0; i < data.size.length; i++) {
                                if (data.size[i].search("//") == -1) {
                                    var html = '<div class="search_size_item " onclick="switchsize($(this))" >' + _h(data.size[i]) + '</div>';
                                    $("#search_size").append(html);
                                } else {
                                    var html = '<div style="text-align: center;padding:0px;" class="search_size_item col-xs-2" onclick="switchsizeimage($(this))" ><img style="max-width: 70px;max-height: 35px;" src="' + _h(data.size[i]) + '" /></div>';
                                    $("#search_size").append(html);
                                }

                            }
                        } else {
                            $("#search_size").parent('.form-group').css('display', 'none');
                        }
                    } else {
                        if (data.newcolor != null) {
                            for (var i = 0; i < data.newcolor.length; i++) {
                                var spt = data.newcolor[i].split('|');

                                if (spt[1].search("#") >= 0 && spt[1].length == 7) {
                                    var html = '';
                                    if (data.url.search('alibaba') >= 0) {
                                        html = '<div class="search_color_item " a="' + _h(spt[0]) + '" onclick="switchcolorborder($(this))" style="height:50px;width:50px;background-color:' + _h(spt[1]) + '"></div>';
                                    } else {
                                        html = '<div class="search_color_item " a="' + _h(spt[0]) + '" onclick="switchcolor($(this))" >' + _h(spt[1]) + '</div>';
                                    }
                                    $("#search_color").append(html);
                                } else if (spt[1].search("//") >= 0) {
                                    var html = '<div class="search_color_item  col-xs" a="' + _h(spt[0]) + '" onclick="switchcolorimage($(this))" ><img style="max-width: 70px;max-height: 35px;" src="' + _h(spt[1]) + '?r=' + _h(spt[0]) + '" class="" /><span> ' + _h(spt[0]) + '</span></div>';
                                    $("#search_color").append(html);
                                } else {
                                    var html = '<div class="search_color_item " a="' + _h(spt[0]) + '" onclick="switchcolor($(this))" >' + _h(spt[1]) + '</div>';
                                    $("#search_color").append(html);
                                }
                            }
                        } else {
                            $("#search_color").parent('.form-group').css('display', 'none');
                        }



                        if (data.newsize2 != undefined && data.newsize2.length != 0) {

                            for (var i = 0; i < data.newsize2.length; i++) {
                                var it = data.newsize2[i];
                                var html = '';
                                if (it.image != undefined && it.image != '' && it.title != undefined && it.title != '') {
                                    html = '<div class="search_size_item" a="' + _h(it.id) + '" val="' + _h(it.title) + '" onclick="switchsizeimage($(this))" style="display:inline-flex;align-items:center;padding:2px 8px 2px 2px;"><img style="width:35px;height:35px;object-fit:cover;margin-right:6px;flex-shrink:0;" src="' + _h(it.image) + '" /><span>' + _h(it.title) + '</span></div>';
                                } else if (it.image != undefined && it.image != '') {
                                    html = '<div style="text-align: center;padding:0px;" a="' + _h(it.id) + '" class="search_size_item col-xs-2" onclick="switchsizeimage($(this))" ><img style="max-width: 70px;max-height: 35px;" src="' + _h(it.image) + '" /></div>';
                                } else {
                                    html = '<div class="search_size_item " a="' + _h(it.id) + '" onclick="switchsize($(this))" >' + _h(it.title) + '</div>';
                                }
                                $("#search_size").append(html);
                            }

                        } else if (data.newsize.length != 0) {

                            for (var i = 0; i < data.newsize.length; i++) {
                                var spt = data.newsize[i].split('|');
                                // spt[0] = รหัสตัวเลือก (ใช้จับคู่ sku) , spt[1] = ชื่อที่ต้องโชว์ให้ลูกค้าเห็น
                                var lbl = (spt[1] == undefined || spt[1] == '') ? spt[0] : spt[1];
                                if (lbl.search("//") == -1) {
                                    var html = '<div class="search_size_item " a="' + _h(spt[0]) + '" onclick="switchsize($(this))" >' + _h(lbl) + '</div>';
                                    $("#search_size").append(html);
                                } else {
                                    var html = '<div style="text-align: center;padding:0px;" a="' + _h(spt[0]) + '" class="search_size_item col-xs-2" onclick="switchsizeimage($(this))" ><img style="max-width: 70px;max-height: 35px;" src="' + _h(lbl) + '" /></div>';
                                    $("#search_size").append(html);
                                }

                            }

                        } else {

                            $("#search_size").parent('.form-group').css('display', 'none');
                        }
                    }
                    makeCurrency();
                    changesku();

                    //$("#searchdetail .modal").modal('show');
                }
            }
        });
    }

    function cal_thaiprice_cart() {
        var cn_price = $("#search_price").val();

        $("#search_price_thai").html('<span class="currency">' + (cn_price * eval($("#search_price_thai").attr('rate'))).toFixed(2) + '</span>&nbsp;฿');
        makeCurrency();
    }

    function calselectcart() {
        var total_th = 0;
        var total_cn = 0;

        $("input[name='selected_cart[]']:checked").each(function() {
            var t = eval($(this).attr('price'));
            var tcn = eval($(this).attr('pricecn'));

            total_th = total_th + t;
            total_cn = total_cn + tcn;

        });

        $("#cartsumcn").text('¥ ' + total_cn.toFixed(2));
        $("#cartsumth").text(total_th.toFixed(2) + ' ฿');

        makeCurrency();
    }

    function selectallscart(shop) {



        if (shop == 0) {
            $("input[name='selected_cart[]']").prop('checked', true);
        } else if (shop == 1) {
            $("input[name='selected_cart[]']").prop('checked', false);
        } else {
            $("." + shop + " input[name='selected_cart[]']").prop('checked', true);
        }

        calselectcart();

    }

    function deleteselectcart() {
        var str = '';
        $("input[name='selected_cart[]']").each(function() {
            if ($(this).prop('checked')) {
                str = str + ',' + $(this).val();
            }
        });

        ConfirmBox(function() {
            $.ajax({
                type: 'POST',
                async: true,
                data: 'index=' + str,
                url: rooturl + '_cart/delete_carts',
                success: function(data) {
                    eval(data);
                }
            });
        });

    }

    function cartgostep2() {
        var str = '';
        $("input[name='selected_cart[]']").each(function() {
            if ($(this).prop('checked')) {
                str = str + ',' + $(this).val();
            }
        });

        if (str == '') {
            ErrorBox("กรุณาเลือกสินค้าที่ต้องการสั่งซื้อ");
        } else {
            window.location.href = '{{ legacy_href() }}cart/checkout/' + btoa(str.substr(1));
        }
    }

    function changesku() {

        var colorcap = $("input[name='search_color_a']").val();
        var sizecap = $("input[name='search_size_a']").val();

        if (colorcap == "" && sizecap == "") {
            if (sku != '') {
                $("#search_qty").text(sku[0].quantity.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,"));
            }

            // if(sku[0].quantity == 0) {
            //     $("#submitbtn").prop('disabled', true);
            // } else {
            //     $("#submitbtn").prop('disabled', false);
            // }
        } else {
            var insku = sku[colorcap + ':' + sizecap];
            if (insku == undefined) {
                insku = sku[sizecap + ':' + colorcap];
            }
            if (insku == undefined) {
                insku = sku[sizecap];
            }
            if (insku == undefined) {
                insku = sku[colorcap];
            }
            if (insku != undefined) {
                $("#search_price").val(insku.price.priceText);
                cal_thaiprice_cart();
                $("#search_qty").text(insku.quantity.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,"));


                // if(insku.quantity == 0) {
                //     $("#submitbtn").prop('disabled', true);
                // } else {
                //     $("#submitbtn").prop('disabled', false);
                // }
            }
        }
        if (priceRange && priceRange.length > 0) {
            var amount = $("#search_amount").val();
            for (var i in priceRange) {
                if (amount >= priceRange[i][0]) {
                    $("#search_price").val(priceRange[i][1]);
                    cal_thaiprice_cart();
                }
            }
        }



    }
</script>
@endsection
