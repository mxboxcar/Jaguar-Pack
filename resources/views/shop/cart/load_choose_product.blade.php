{{-- _cart/load_choose_product.php เดิม --}}
<input type="hidden" name="search_product_name" value="" />
<input type="hidden" name="search_shop_name" value="" />
<input type="hidden" name="search_main_image" value="" />
<input type="hidden" name="search_color" value="" />
<input type="hidden" name="search_size" value="" />
<input type="hidden" name="search_url" value="" />

<input type="hidden" name="search_color_a" value="" />
<input type="hidden" name="search_size_a" value="" />


    <div class="form-horizontal col-xs-4" role="form">    
        <div class="form-group col-xs-12">
            <a class="fancybox" id="search_main_image_href" href="">
                <img src="" id="search_main_image" class="img-responsive" />
            </a>
        </div>
        <div id="search_external_image_div" class="col-xs-12" style="margin:5px 0px"></div>

        <div class="col-xs-12" style="margin:5px 0px">
            {!! res('shop_name') !!}: <span id="search_shop_name"></span>
        </div>

        <div>
        <a id="search_url" href="" type="button" class="btn btn-primary waves-effect waves-light">{!! res('view_this_item') !!}</a>
        </div>
    </div>
    <div class="form-horizontal col-xs-8" role="form">    
        <div class="form-group">
            <div class="col-xs-12">
                <div style="font-size: 20px;" id="search_product_name"></div>
            </div>
        </div>
        <div class="form-group">
            <div  class="control-label col-xs-3">
                {!! res('price') !!}:
            </div>
            
            <div class="form-control-static col-xs-4" >
                <input class="form-control" type="text" name="search_price" id="search_price" onkeyup="cal_thaiprice_cart()" />
            </div>

            <div class="form-control-static col-xs-3" rate="{{ $rate }}" id="search_price_thai" >
            </div>
        </div>
        <div class="form-group">
            <div  class="control-label col-xs-3">
                {!! res('amount') !!}:
            </div>
            <div class="form-control-static col-xs-9" >
                <div style="float:left;width:120px;">
                    <div class="input-group ">
                        <span class="input-group-addon" onclick="switchpricedown(); changesku();" style="cursor:pointer;padding: 6px">
                            <i class="fa  fa-minus"></i>
                        </span>
                        <input style="font-weight: normal;text-align:center" type="text" onkeyup="changesku()" id="search_amount" name="search_amount" class="form-control" value="1">
                        <span class="input-group-addon" onclick="switchpriceup(); changesku();" style="cursor:pointer;padding: 6px">
                            <i class="fa  fa-plus"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div  class="control-label col-xs-3">
                {!! res('color') !!}:
            </div>
            <div class="form-control-static col-xs-9" id="search_color">
            </div>
        </div>
        <div class="form-group">
            <div  class="control-label col-xs-3">
                {!! res('size') !!}:
            </div>
            <div class="form-control-static col-xs-9" id="search_size">
            </div>
        </div>

        <div class="form-group">
            <div  class="control-label col-xs-3">
                สินค้าคงเหลือ:
            </div>
            <div class="form-control-static col-xs-9" id="search_qty">
                9,999
            </div>
        </div>

        <div class="form-group">
            <div  class="control-label col-xs-3">
                {!! res('remark') !!}:
            </div>
            <div class="col-xs-8" >
                <textarea class="form-control" id="search_remark" name="search_remark"></textarea>
            </div>
        </div>

        <div class="form-group upload_cart">
            <div  class="control-label col-xs-3">
                {!! res('attachment') !!}:
            </div>
            <div class="col-xs-10 form-control-static" >
                {!! $upload !!}
            </div>
        </div>
    </div>

<div class="text-center">
<button style="padding: 10px 50px;" type="button" class="btn btn-success waves-effect waves-light" id="submitbtn" onclick="submittingtocart()">{!! res('add_to_list') !!}</button>
</div>
    