{{-- สร้างจาก menu_mobile.php ของเว็บเดิมด้วย tools/port-layout.php — แก้ได้ แต่ให้เทียบหน้าตากับเดิมทุกครั้ง --}}
<?php
$is_admin = false;

if(perm('admin_order') || 
    perm('admin_user') || 
    perm('admin_role') ||
    perm('admin_payment') ||
    perm('admin_export') ||
    perm('admin_product_type') || 
    perm('admin_shipping_type') ||
    perm('admin_user_type') ||
    perm('admin_refill') || 
    perm('admin_balance') || 
    perm('admin_lot') ||
    perm('admin_transfer') ||
    perm('admin_alipay') ||
    perm('admin_setting') ||
    perm('admin_tracking') ||
    perm('admin_lost_package') ||
    perm('admin_balance_company') ||
    perm('admin_point') ||
    perm('admin_withdrawpoint') ||
    perm('admin_warehouse') ||
    perm('admin_warehouse_section') ||
    perm('admin_goodsconfirm') ||
    perm('admin_report') ||
    perm('admin_lot_view')) { 
    $is_admin = true;
}
?>

<ul class="nav navbar-nav" style="float: left;">
    <?php if($is_admin) { ?>
    <li class="dropdown">
      <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{!! res('administrator') !!} <span class="caret"></span></a>
      <ul class="dropdown-menu">
        <?php if(perm('admin_order')) { ?>
                <li class="<?php if($page == 'admin_order' || $page == 'admin_order_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/order"><i class="fa  fa-list-alt"></i> <span>{!! res('order_list') !!}</span></a></li>
                <?php } if(perm('admin_user') || perm('admin_user_view')) { ?>
                <li class="<?php if($page == 'admin_user' || $page == 'admin_user_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/user"><i class="md  md-location-history"></i> <span>{!! res('user') !!}</span></a></li>
                <?php } if(perm('admin_payment_create')) { ?>
                <li class="<?php if($page == 'admin_payment_create') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/payment/create"><i class="md  md-location-history"></i> <span>{!! res('export_creating') !!}</span></a></li>
                <?php } if(perm('admin_user_type')) { ?>
                <li class="<?php if($page == 'admin_user_type' || $page == 'admin_user_type_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/user_type"><i class="md  md-local-library"></i> <span>{!! res('user_type') !!}</span></a></li>
                <?php } if(perm('admin_role')) { ?>
                <li class="<?php if($page == 'admin_role' || $page == 'admin_role_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/role"><i class="md  md-perm-contact-cal"></i> <span>{!! res('role') !!}</span></a></li>
                <?php } if(perm('admin_payment')) { ?>
                <li class="<?php if($page == 'admin_payment' || $page == 'admin_payment_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/payment"><i class="md md-attach-money"></i> <span>{!! res('payment') !!}</span></a></li>
                <?php } if(perm('admin_export')) { ?>
                <li class="<?php if($page == 'admin_export' || $page == 'admin_export_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/export"><i class="md md-directions-car"></i> <span>{!! res('export_list') !!}</span></a></li>
                <?php } if(perm('admin_refill')) { ?>
                <li class="<?php if($page == 'admin_refill' || $page == 'admin_refill_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/refill"><i class="fa fa-money"></i> <span>{!! res('refill') !!}</span></a></li>
                <?php } if(perm('admin_lost_package')) { ?>
                <li class="<?php if($page == 'admin_lost_package' || $page == 'admin_lost_package_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/lost_package"><i class="fa fa-cube"></i> <span>{!! res('lost_package') !!}</span></a></li>
                <?php } if(perm('admin_balance')) { ?>
                <li class="<?php if($page == 'admin_balance' || $page == 'admin_balance_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/balance"><i class="fa md-account-balance-wallet"></i> <span>{!! res('balance') !!}</span></a></li>
                <?php } if(perm('admin_balance_company')) { ?>
                <li class="<?php if($page == 'admin_balance_company' || $page == 'admin_balance_company_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/balance_company"><i class="fa md-account-balance-wallet"></i> <span>{!! res('balance_company') !!}</span></a></li>
                <?php } if(perm('admin_lot')  || perm('admin_lot_view')) { ?>
                <li class="<?php if($page == 'admin_lot' || $page == 'admin_lot_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/lot"><i class="md  md-local-shipping"></i> <span>{!! res('lot') !!}</span></a></li>
                <?php } if(perm('admin_warehouse')) { ?>
                <li class="<?php if($page == 'admin_warehouse' || $page == 'admin_lot_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/warehouse"><i class="fa fa-home"></i> <span>{!! res('warehouse') !!}</span></a></li>
                <?php } if(perm('admin_warehouse_section')) { ?>
                <li class="<?php if($page == 'admin_warehouse_section' || $page == 'admin_warehouse_section_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/warehouse_section"><i class="fa fa-home"></i> <span>{!! res('warehouse_section') !!}</span></a></li>
                <?php } if(perm('admin_tracking')) { ?>
                <li class="<?php if($page == 'admin_tracking' || $page == 'admin_tracking_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/tracking"><i class="fa fa-barcode"></i> <span>{!! res('tracking') !!}</span></a></li>
                <?php } if(perm('admin_combine_track')) { ?>
                <li class="<?php if($page == 'admin_combine_track') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/combine_track"><i class="fa fa-barcode"></i> <span>{!! res('combine_track') !!}</span></a></li>
                <?php } if(perm('admin_transfer')) { ?>
                <li class="<?php if($page == 'admin_transfer' || $page == 'admin_transfer_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/transfer"><i class="md md-import-export"></i> <span>{!! res('transfer') !!}</span></a></li>
                <?php } if(perm('admin_alipay')) { ?>
                <li class="<?php if($page == 'admin_alipay' || $page == 'admin_alipay_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/alipay"><i class="fa  fa-cny"></i> <span>{!! res('alipay') !!}</span></a></li>
                <?php } if(perm('admin_taobao') || perm('admin_shop_cashback') || perm('admin_balance_cn')) { ?>
                <li class="<?php if($page == 'admin_taobao' || $page == 'admin_taobao_detail'  || $page == 'shop_cashback' || $page == 'shop_cashback_detail'  || $page == 'balance') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/taobao"><i class="fa  fa-cny"></i> <span>{!! res('taobao') !!}</span></a></li>
                <?php } if(perm('admin_setting')) { ?>
                <li class="<?php if($page == 'admin_setting' || $page == 'admin_setting_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/setting"><i class="fa fa-cog"></i> <span>{!! res('setting') !!}</span></a></li>
                <?php } if(perm('admin_product_type')) { ?>
                <li class="<?php if($page == 'admin_product_type' || $page == 'admin_product_type_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/product_type"><i class="fa fa-random"></i> <span>{!! res('product_type') !!}</span></a></li>
                <?php } if(perm('admin_shipping_type')) { ?>
                <li class="<?php if($page == 'admin_shipping_type' || $page == 'admin_shipping_type_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/shipping_type"><i class="fa fa-car"></i> <span>{!! res('shipping_type') !!}</span></a></li>
                <?php } if(perm('admin_notice')) { ?>
                <li class="<?php if($page == 'admin_notice' || $page == 'admin_notice_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/notice"><i class="fa fa-map-signs"></i> <span>{!! res('notice') !!}</span></a></li>
                <?php } if(perm('admin_point')) { ?>
                <li class="<?php if($page == 'admin_point' || $page == 'admin_point_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/point"><i class="fa fa-star-half-o"></i> <span>{!! res('point_list') !!}</span></a></li>
                <?php } if(perm('admin_withdrawpoint')) { ?>
                <li class="<?php if($page == 'admin_withdrawpoint' || $page == 'admin_withdrawpoint_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/withdrawpoint"><i class="fa fa-star-half-o"></i> <span>{!! res('withdrawpoint') !!}</span></a></li>
                <?php } if(perm('admin_goodsconfirm')) { ?>
                <li class="<?php if($page == 'admin_goodsconfirm' || $page == 'admin_goodsconfirm_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/goodsconfirm"><i class="fa fa-close"></i> <span>{!! res('goodsconfirm') !!}</span></a></li>
                <?php } if(perm('admin_report')) { ?>
                <li class="<?php if($page == 'admin_report' || $page == 'admin_report_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/report"><i class="fa fa-bar-chart-o"></i> <span>{!! res('report') !!}</span></a></li>
                <?php } if(perm('admin_mall')) { ?>
                <li class="<?php if($page == 'admin_mall' || $page == 'admin_mall_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/mall"><i class="fa fa-cube"></i> <span>{!! res('mall') !!}</span></a></li>
                <?php } if(perm('admin_mall_stock')) { ?>
                <li class="<?php if($page == 'admin_mallstock' || $page == 'admin_mall_detail') { echo "active"; } ?>"><a  href="{{ legacy_href() }}admin/mall_stock"><i class="fa fa-cube"></i> <span>ความเคลื่อนไหวสินค้า</a></li>
                <?php }  ?>
      </ul>
    </li>
    <?php } ?>
</ul>


<ul class="nav navbar-nav" style="float: left;">
  <li class="dropdown">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{!! res('menu') !!} <span class="caret"></span></a>
    <ul class="dropdown-menu">
      <li class=""><a class="<?php if($page == 'dashboard' ) { echo "active"; } ?>" href="{{ legacy_href() }}dashboard"><i class="fa fa-bar-chart-o"></i> <span>{!! res('dashboard') !!}</a></li>
        <li class=""><a class="<?php if($page == 'linenotify' ) { echo "active"; } ?>" href="{{ legacy_href() }}linenotify"><img src="http://www.yd-cargo.com/images/icon/LINE-icon-30x30.png" width="20" height="20"> เชื่อมต่อ Line Notify</a></li>

      <?php 
            $count = \App\Support\Legacy\LayoutData::noticeCount();
        ?>
      <li class=""><a class="<?php if($page == 'notice' ) { echo "active"; } ?>" href="{{ legacy_href() }}notice"><div class="badge badge-xs badge-danger " style="position: relative;top:0px;" ><?php echo $count; ?></div><i class="fa fa-hand-paper-o "></i> <span>{!! res('notice') !!}</a></li>
         
        <?php if(perm('profile')) { ?>
        <li class=""><a class="<?php if($page == 'profile') { echo "active"; } ?>" href="{{ legacy_href() }}profile"><i class="fa fa-sliders"></i> <span>{!! res('profile') !!}</span></a></li>
        <?php }
            $data = ['c' => \App\Support\Legacy\LayoutData::chatUnreadAdmin()];

            $count = '';
            if($data['c'] != 0) {
                $count = $data['c'];
            }
        ?>
        <?php if(perm('admin_message')) { ?>
        <li class=""><a class="<?php if($page == 'admin_message') { echo "active"; } ?>" href="{{ legacy_href() }}admin/message"><div class="badge badge-xs badge-danger" style="position: absolute;margin: -5px 20px;" ><?php echo $count; ?></div><i class="md  md-message"></i> <span>{!! res('admin_message') !!}</span></a></li>
        <?php } if(perm('message')) {
            $data = ['c' => \App\Support\Legacy\LayoutData::chatUnreadUser()];

            $count = '';
            if($data['c'] != 0) {
                $count = $data['c'];
            }
        ?>
        <?php } if(perm('message')) { ?>
        <li class="<?php if($page == 'message') { echo "active"; } ?>"><a class="" href="{{ legacy_href() }}message"><div class="badge badge-xs badge-danger " style="position: absolute;margin: -5px 20px;" ><?php echo $count; ?></div><i class="md  md-message"></i> <span>{!! res('message') !!}</span></a></li>
        <?php } if(perm('mall')) { ?>
        <li class="<?php if($page == 'mall') { echo "active"; } ?>"><a class="" href="{{ legacy_href() }}mall"><div class="badge badge-xs badge-danger " style="position: absolute;margin: -5px 20px;" ><?php echo $count; ?></div><img src="{{ legacy_href() }}assets/images/icon/shopping-basket.png" style="width:20px;height:20px" /> <span>{!! res('mall') !!}</span></a></li>
        <?php } if(perm('order')) { ?>
        <li class="<?php if($page == 'order' || $page == 'order_detail') { echo "active"; } ?>"><a class="" href="{{ legacy_href() }}order"><img src="{{ legacy_href() }}assets/images/icon/order.png" style="width:20px;height:20px" /> <span>{!! res('order_list') !!}</span></a></li>
        <?php } if(perm('payment')) { ?>
        <li class="<?php if($page == 'payment_create') { echo "active"; } ?>"><a class="" href="{{ legacy_href() }}payment/create"><img src="{{ legacy_href() }}assets/images/icon/lot.png" style="width:20px;height:20px" /> <span>{!! res('payment_create') !!}</span></a></li>
        <?php } if(perm('export')) { ?>
        <li class="<?php if($page == 'export' || $page == 'export_detail') { echo "active"; } ?>"><a class="" href="{{ legacy_href() }}export"><img src="{{ legacy_href() }}assets/images/icon/export.png" style="width:20px;height:20px" /> <span>{!! res('export_list') !!}</span></a></li>
        <?php } if(perm('tracking')) { ?>
        <li class="<?php if($page == 'tracking' || $page == 'tracking_detail') { echo "active"; } ?>"><a class="" href="{{ legacy_href() }}tracking"><img src="{{ legacy_href() }}assets/images/icon/tracking.png" style="width:20px;height:20px" /> <span>{!! res('tracking') !!}</span></a></li>
        <?php } if(perm('refill')) { ?>
        <li class="<?php if($page == 'refill' || $page == 'refill_detail') { echo "active"; } ?>"><a class="" href="{{ legacy_href() }}refill"><img src="{{ legacy_href() }}assets/images/icon/refill.png" style="width:20px;height:20px" /> <span>{!! res('refill') !!}</span></a></li>
        <?php } if(perm('transfer')) { ?>
        <li class="<?php if($page == 'transfer' || $page == 'transfer_detail') { echo "active"; } ?>"><a class="" href="{{ legacy_href() }}transfer"><img src="{{ legacy_href() }}assets/images/icon/transfer.jpg" style="width:20px;height:20px" /> <span>{!! res('transfer') !!}</span></a></li>
        <?php } if(perm('alipay')) { ?>
        <li class="<?php if($page == 'alipay' || $page == 'alipay_detail') { echo "active"; } ?>"><a class="" href="{{ legacy_href() }}alipay"><img src="{{ legacy_href() }}assets/images/icon/alipay.png" style="width:20px;height:20px" /> <span>{!! res('alipay') !!}</span></a></li>
        <?php } if(perm('lost_package')) { ?>
        <li class="<?php if($page == 'lost_package' || $page == 'lost_package_detail') { echo "active"; } ?>"><a class="" href="{{ legacy_href() }}lost_package"><img src="{{ legacy_href() }}assets/images/icon/shopping-bag.png" style="width:20px;height:20px" /> <span>{!! res('lost_package') !!}</span></a></li>
        <?php } if(perm('taobao') || perm('shop_cashback') || perm('balance_cn')) { ?>
        <li class="<?php if($page == 'taobao' || $page == 'taobao_detail' || $page == 'shop_cashback' || $page == 'shop_cashback_detail' || $page == 'balance_cn') { echo "active"; } ?>"><a class="" href="{{ legacy_href() }}taobao"><img src="{{ legacy_href() }}assets/images/icon/shopping-basket.png" style="width:20px;height:20px" /> <span>{!! res('taobao') !!}</span></a></li>
        <?php } if(perm('balance')) { ?>
        <li class="<?php if($page == 'balance' || $page == 'balance_detail') { echo "active"; } ?>"><a class="" href="{{ legacy_href() }}balance"><img src="{{ legacy_href() }}assets/images/icon/balance.png" style="width:20px;height:20px" /> <span>{!! res('balance') !!}</span></a></li>
        <?php } if(perm('point')) { ?>
        <li class="<?php if($page == 'point' || $page == 'point_detail') { echo "active"; } ?>"><a class="" href="{{ legacy_href() }}point"><img src="{{ legacy_href() }}assets/images/icon/medal.png" style="width:20px;height:20px" /> <span>{!! res('point_list') !!}</span></a></li>
        <?php } if(perm('goodsconfirm')) { 
            $data = ['count' => \App\Support\Legacy\LayoutData::goodsconfirmPending()];
        ?>
        <li class="<?php if($page == 'goodconfirm' || $page == 'goodconfirm_detail') { echo "active"; } ?>"><a class="" href="{{ legacy_href() }}goodsconfirm"><img src="{{ legacy_href() }}assets/images/icon/package.png" style="width:20px;height:20px" /> <?php if($data['count'] != 0) { ?><div class="badge badge-xs badge-danger " style="position: absolute;margin: -5px -15px;" ><?php echo $data['count']; ?></div><?php } ?> <span>{!! res('goodsconfirm_list') !!} </span></a></li>
        <?php } ?>
    </ul>
  </li>
</ul>