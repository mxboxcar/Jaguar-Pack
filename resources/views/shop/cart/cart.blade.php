{{-- _cart/cart.php เดิม (ชิ้นส่วน HTML ตะกร้าแบบเต็มหน้า) --}}
<style>
  .img_provider {
    width: 18px;
    left: 50%;
    top: 50%;
    margin-right: 8px;
    margin-top: -2px;
  }
  .text-overflow {
    text-overflow: ellipsis;
    max-width: 400px;
    white-space: nowrap;
    overflow: hidden;
  }
  .cart-page-footer {
    z-index: 2;
    position: -webkit-sticky;
    position: sticky;
    bottom: 0;
    display: -ms-grid;
    display: grid;
    -ms-grid-columns: 1fr 20.3125rem 11.875rem;
    grid-template-columns: 1fr 20.3125rem 11.875rem;
    align-items: center;
    box-sizing: border-box;
    background: #fff;
    width: 100%;
    flex: 1 1;
    font-size: 1.5rem;
    margin-top: 1.75rem;
  }
  .cart-page-footer__row {
    grid-column-start: 1;
    grid-column-end: 4;
    -ms-grid-column-span: 3;
    display: flex;
    align-items: center;
    flex: 1 1;
    width: 100%;
    background: #fff;
    border-radius: 4px;
    border: 2px solid #ebeff2;
  }
  ._1a6khS {
    padding: 1rem 0;
  }
  .cart-item__cell-checkbox {
    padding: 0 12px 0 8px;
    display: flex;
    flex-direction: row-reverse;
    min-width: 58px;
    box-sizing: border-box;
  }
  .cart-page-footer__gap {
    flex: 1 1;
  }
  .cart-page-footer__first-summary, .cart-page-footer__summary {
    display: flex;
  }
  .cart-page-footer__summary {
    flex-direction: column;
  }
  .cart-page-footer__checkout {
    width: 200px;
    margin: 0 22px 0 15px;
  }
  .cart-page-footer-summary__subtotal {
    margin-left: 1.25rem;
    display: flex;
    align-items: center;
  }
  .cart-page-footer-summary__subtotal-text {
    color: #333;
    margin-right: 8px;
  }
  .cart-page-footer-summary__subtotal-amount {
    font-size: 24px;
    font-weight: bold;
    line-height: 2rem;
    margin-left: .3125rem;
    color: #ef5c19;
  }
  .cart-page-footer__second-summary {
    color: #f6a700;
    font-size: 1.5rem;
    margin-top: 4px;
    text-align: right;
  }
  .btn-checkout {
    color: #fff;
    background-color: #ef5c19 !important;
    border-color: #ef5c19 !important;
    width: 100%;
    height: 44px;
  }
  .btn-checkout:hover, .btn-checkout:active, .btn-checkout:focus {
    color: #fff;
    background-color: #ef5c19 !important;
  }
  .cart-page-footer--overlap:before {
    content: "";
    position: absolute;
    top: -1.25rem;
    left: 0;
    height: 1.25rem;
    width: 100%;
    background: linear-gradient(transparent,rgba(0,0,0,.06));
  }
</style>
<div class="col-lg-10 col-xs-12">
  <div id="cart_item">

  </div>
</div>

<script>
$('.content-page').css('overflow', 'visible')
$('#wrapper').css('overflow', 'visible')
$.ajax({
      type: 'POST',
      async: true,
      url: rooturl + '_cart/load_cart',
      success: function(data) {
        $("#cart_item").html(data);
        notification();
        Switcher();
      }
});

$(document).on("change", "input[name='selected_cart[]']", function(e) {
  calselectcart();
})

function SaveCartCraft(ele, id) {
    if(ele.val() == 1) {
        $(".remark").modal('show');
    }
    
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id=' + id + '&value=' + ele.val(),
        url: rooturl + '_cart/save_craft',
        success: function(data) {
            LoadFullCart();
        }
    });
}
function SaveCartQc(ele, id) {
    if(ele.val() == 1) {
        $(".remark").modal('show');
    }
    $.ajax({
        type: 'POST',
        async: true,
        data : 'id=' + id + '&value=' + ele.val(),
        url: rooturl + '_cart/save_qc',
        success: function(data) {
            LoadFullCart();
        }
    });
}

function selectallscart(shop, el) {
  if (shop == 'all') {
    $("input[name='selected_cart[]']").prop('checked', $(el).prop('checked'));
    $('input[name=head-shop]').prop('checked', $(el).prop('checked'));
  } else {
    $("."+ shop +" input[name='selected_cart[]']").prop('checked', $(el).prop('checked'));
  }
  calselectcart();
    
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

  $('#cartcount').text($("input[name='selected_cart[]']:checked").length)
  $("#cartsumcn").text('¥ ' + total_cn.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
  $("#cartsumth").text(total_th.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ฿');

  makeCurrency();
}
function cartgostep2() {
    var str = '';
    $("input[name='selected_cart[]']").each(function() {
        if($(this).prop('checked')) {
            str = str + ',' + $(this).val();
        }
    });

    if(str == '') {
        ErrorBox("กรุณาเลือกสินค้าที่ต้องการสั่งซื้อ");
    } else {
        window.location.href = '{{ legacy_href() }}cart/checkout/' + btoa(str.substr(1));
    }
}
</script>