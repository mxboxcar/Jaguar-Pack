# URL map: เว็บเดิม → กลุ่มที่รับผิดชอบ

สร้างอัตโนมัติด้วย `php8.4 tools/url-map.php /home/admin/bench/jl-old > docs/url-map.md` — ห้ามแก้มือ (แก้ตารางใน tools/url-map.php แล้วสร้างใหม่)

- กลุ่ม: `tracking`, `shop`, `order`, `payment`, `balance`, `money-move`, `points`, `lot`, `warehouse`, `users`, `messaging`, `reports`, `settings` · `foundation` = ฐานร่วม (ทำแล้ว)
- bench เดิม (:8101) ไม่มี rewrite: เปิดหน้าด้วย `http://127.0.0.1:8101/index.php?page=<page>` และ ajax ด้วย `/<path>.php`
- กฎท้ายไฟล์ `^(.*?)/?$ → $1.php` ทำให้ทุกไฟล์ `.php` เรียกได้โดยไม่ต้องมี `.php` (ajax ทั้งหมดใช้แบบนี้) — ดูตารางที่ 2

## 1. RewriteRule ทั้งหมด (171 บรรทัด)

| # | บรรทัด | URL (regex) | ปลายทางเดิม | page | ไฟล์ | กลุ่ม |
|---|---|---|---|---|---|---|
| 1 | 4 | `^(_[^/]+/)+$` | `-` [R=404,L] |  |  | **foundation (nginx/infra)** |
| 2 | 7 | `^logs/` | `-` [F,L] |  |  | **foundation (nginx/infra)** |
| 3 | 8 | `^upload/.+\.(php\|phps\|phtml\|phar\|pht\|php[0-9]\|shtml\|cgi\|pl\|py\|sh\|htaccess)$` | `-` [F,L,NC] |  |  | **foundation (nginx/infra)** |
| 4 | 9 | `^upload/` | `-` [L] |  |  | **foundation (nginx/infra)** |
| 5 | 12 | `^login$` | `login.php`  |  | login.php | **foundation** |
| 6 | 13 | `^register$` | `register.php`  |  | register.php | **users** |
| 7 | 14 | `^dashboard$` | `?page=dashboard`  | dashboard | _dashboard/index.php | **reports** |
| 8 | 15 | `^message$` | `?page=message`  | message | _message/index.php | **messaging** |
| 9 | 16 | `^lostpassword$` | `lost_password.php`  |  | lost_password.php | **users** |
| 10 | 17 | `^admin/message$` | `?page=admin_message`  | admin_message | _admin/_message/index.php | **messaging** |
| 11 | 18 | `^admin/dashboard$` | `?page=order_admin`  | order_admin | _admin/_order/index.php | **order** |
| 12 | 20 | `^notice$` | `?page=notice`  | notice | _notice/index.php | **messaging** |
| 13 | 21 | `^notice/([0-9]+)$` | `?page=notice_detail&id=$1`  | notice_detail | _notice/form.php | **messaging** |
| 14 | 23 | `^order$` | `?page=order`  | order | _order/index.php | **order** |
| 15 | 24 | `^order/([0-9]+)$` | `?page=order_detail&id=$1`  | order_detail | _order/form.php | **order** |
| 16 | 25 | `^order/([0-9]+)/(.+)$` | `?page=order_detail&id=$1&div=$2`  | order_detail | _order/form.php | **order** |
| 17 | 26 | `^order/status/([0-9]+)$` | `?page=order&status=$1`  | order | _order/index.php | **order** |
| 18 | 28 | `^profile$` | `?page=profile`  | profile | _profile/index.php | **users** |
| 19 | 29 | `^linenotify$` | `?page=linenotify`  | linenotify | _linenotify/index.php | **messaging** |
| 20 | 30 | `^profile/$` | `?page=profile`  | profile | _profile/index.php | **users** |
| 21 | 31 | `^dashboard$` | `?page=dashboard`  | dashboard | _dashboard/index.php | **reports** |
| 22 | 33 | `^tracking$` | `?page=tracking`  | tracking | _tracking/index.php | **tracking** |
| 23 | 34 | `^tracking/create$` | `?page=tracking_detail&id=0`  | tracking_detail | _tracking/form.php | **tracking** |
| 24 | 35 | `^tracking/([0-9]+)$` | `?page=tracking_detail&id=$1`  | tracking_detail | _tracking/form.php | **tracking** |
| 25 | 38 | `^refill$` | `?page=refill`  | refill | _refill/index.php | **money-move** |
| 26 | 39 | `^refill/create$` | `?page=refill_detail&id=0`  | refill_detail | _refill/form.php | **money-move** |
| 27 | 40 | `^refill/([0-9]+)$` | `?page=refill_detail&id=$1`  | refill_detail | _refill/form.php | **money-move** |
| 28 | 41 | `^refill/status/([0-9]+)$` | `?page=refill&status=$1`  | refill | _refill/index.php | **money-move** |
| 29 | 43 | `^payment$` | `?page=payment`  | payment | _payment/index.php | **payment** |
| 30 | 44 | `^payment/create$` | `?page=payment_create`  | payment_create | _payment/create.php | **payment** |
| 31 | 45 | `^payment/create/(.+)$` | `?page=payment_detail_form&random=$1`  | payment_detail_form | _payment/create_form.php | **payment** |
| 32 | 46 | `^payment/([0-9]+)$` | `?page=payment_detail&id=$1`  | payment_detail | _payment/form.php | **payment** |
| 33 | 47 | `^payment/status/([0-9]+)$` | `?page=payment&status=$1`  | payment | _payment/index.php | **payment** |
| 34 | 50 | `^export$` | `?page=export`  | export | _export/index.php | **reports** |
| 35 | 51 | `^export/([0-9]+)$` | `?page=export_detail&id=$1`  | export_detail | _export/form.php | **reports** |
| 36 | 53 | `^transfer$` | `?page=transfer`  | transfer | _transfer/index.php | **money-move** |
| 37 | 54 | `^transfer/create$` | `?page=transfer_detail&id=0`  | transfer_detail | _transfer/form.php | **money-move** |
| 38 | 55 | `^transfer/([0-9]+)$` | `?page=transfer_detail&id=$1`  | transfer_detail | _transfer/form.php | **money-move** |
| 39 | 56 | `^transfer/status/([0-9]+)$` | `?page=transfer&status=$1`  | transfer | _transfer/index.php | **money-move** |
| 40 | 58 | `^alipay$` | `?page=alipay`  | alipay | _alipay/index.php | **payment** |
| 41 | 59 | `^alipay/create$` | `?page=alipay_detail&id=0`  | alipay_detail | _alipay/form.php | **payment** |
| 42 | 60 | `^alipay/([0-9]+)$` | `?page=alipay_detail&id=$1`  | alipay_detail | _alipay/form.php | **payment** |
| 43 | 61 | `^alipay/status/([0-9]+)$` | `?page=alipay&status=$1`  | alipay | _alipay/index.php | **payment** |
| 44 | 63 | `^lot$` | `?page=lot`  | lot | _lot/index.php | **lot** |
| 45 | 64 | `^lot/([0-9]+)$` | `?page=lot_detail&id=$1`  | lot_detail | _lot/form.php | **lot** |
| 46 | 66 | `^taobao$` | `?page=taobao`  | taobao | _taobao/index.php | **shop** |
| 47 | 67 | `^taobao/create$` | `?page=taobao_detail&id=0`  | taobao_detail | _taobao/form.php | **shop** |
| 48 | 68 | `^taobao/([0-9]+)$` | `?page=taobao_detail&id=$1`  | taobao_detail | _taobao/form.php | **shop** |
| 49 | 70 | `^lost_package$` | `?page=lost_package`  | lost_package | _lost_package/index.php | **tracking** |
| 50 | 71 | `^lost_package/create$` | `?page=lost_package_detail&id=0`  | lost_package_detail | _lost_package/form.php | **tracking** |
| 51 | 72 | `^lost_package/([0-9]+)$` | `?page=lost_package_detail&id=$1`  | lost_package_detail | _lost_package/form.php | **tracking** |
| 52 | 73 | `^lost_package/status/([0-9]+)$` | `?page=lost_package_detail&status=$1`  | lost_package_detail | _lost_package/form.php | **tracking** |
| 53 | 75 | `^shop_cashback$` | `?page=shop_cashback`  | shop_cashback | _shop_cashback/index.php | **points** |
| 54 | 76 | `^shop_cashback/create$` | `?page=shop_cashback_detail&id=0`  | shop_cashback_detail | _shop_cashback/form.php | **points** |
| 55 | 77 | `^shop_cashback/([0-9]+)$` | `?page=shop_cashback_detail&id=$1`  | shop_cashback_detail | _shop_cashback/form.php | **points** |
| 56 | 79 | `^balance$` | `?page=balance`  | balance | _balance/index.php | **balance** |
| 57 | 80 | `^balance_cn$` | `?page=balance_cn`  | balance_cn | _balance_cn/index.php | **balance** |
| 58 | 81 | `^point$` | `?page=point`  | point | _point/index.php | **points** |
| 59 | 83 | `^cart/checkout$` | `?page=checkout`  | checkout | _cart/checkout.php | **shop** |
| 60 | 84 | `^cart/checkout/(.+)$` | `?page=checkout&ids=$1`  | checkout | _cart/checkout.php | **shop** |
| 61 | 85 | `^cart/product/([^/]+)$` | `?page=checkout_product&url=$1` [QSA] | checkout_product | _cart/product.php | **shop** |
| 62 | 87 | `^goodsconfirm$` | `?page=goodsconfirm`  | goodsconfirm | _goodsconfirm/index.php | **warehouse** |
| 63 | 88 | `^goodsconfirm/([0-9]+)$` | `?page=goodsconfirm_detail&id=$1`  | goodsconfirm_detail | _goodsconfirm/form.php | **warehouse** |
| 64 | 90 | `^mall$` | `?page=mall`  | mall | _mall/index.php | **shop** |
| 65 | 91 | `^mall/(.+)$` | `?page=mall&search=$1`  | mall | _mall/index.php | **shop** |
| 66 | 92 | `^mall/create$` | `?page=mall_detail&id=0`  | mall_detail | _mall/form.php (**ไม่มีไฟล์นี้ในเว็บเดิม** — หน้าเดิม error/ว่าง) | **shop** |
| 67 | 93 | `^mall/cart$` | `?page=mall_cart&id=0`  | mall_cart | _mall/formcart.php | **shop** |
| 68 | 94 | `^mall/([0-9]+)$` | `?page=mall_detail&id=$1`  | mall_detail | _mall/form.php (**ไม่มีไฟล์นี้ในเว็บเดิม** — หน้าเดิม error/ว่าง) | **shop** |
| 69 | 106 | `^admin/balance$` | `?page=admin_balance`  | admin_balance | _admin/_balance/index.php | **balance** |
| 70 | 107 | `^admin/balance_cn$` | `?page=admin_balance_cn`  | admin_balance_cn | _admin/_balance_cn/index.php | **balance** |
| 71 | 108 | `^admin/balance_company$` | `?page=admin_balance_company`  | admin_balance_company | _admin/_balance_company/index.php | **balance** |
| 72 | 110 | `^admin/order$` | `?page=order_admin`  | order_admin | _admin/_order/index.php | **order** |
| 73 | 111 | `^admin/order/([0-9]+)$` | `?page=order_admin_detail&id=$1`  | order_admin_detail | _admin/_order/form.php | **order** |
| 74 | 112 | `^admin/order/([0-9]+)/(.+)$` | `?page=order_admin_detail&id=$1&div=$2`  | order_admin_detail | _admin/_order/form.php | **order** |
| 75 | 113 | `^admin/order/status/([0-9]+)$` | `?page=order_admin&status=$1`  | order_admin | _admin/_order/index.php | **order** |
| 76 | 115 | `^admin/user$` | `?page=admin_user`  | admin_user | _admin/_user/index.php | **users** |
| 77 | 116 | `^admin/user/create$` | `?page=admin_user_detail&id=0`  | admin_user_detail | _admin/_user/form.php | **users** |
| 78 | 117 | `^admin/user/([0-9]+)$` | `?page=admin_user_detail&id=$1`  | admin_user_detail | _admin/_user/form.php | **users** |
| 79 | 118 | `^admin/user/status/([0-9]+)$` | `?page=admin_user&status=$1`  | admin_user | _admin/_user/index.php | **users** |
| 80 | 120 | `^admin/role$` | `?page=admin_role`  | admin_role | _admin/_role/index.php | **users** |
| 81 | 121 | `^admin/role/create$` | `?page=admin_role_detail&id=0`  | admin_role_detail | _admin/_role/form.php | **users** |
| 82 | 122 | `^admin/role/([0-9]+)$` | `?page=admin_role_detail&id=$1`  | admin_role_detail | _admin/_role/form.php | **users** |
| 83 | 124 | `^admin/refill$` | `?page=admin_refill`  | admin_refill | _admin/_refill/index.php | **money-move** |
| 84 | 125 | `^admin/refill/create$` | `?page=admin_refill_detail&id=0`  | admin_refill_detail | _admin/_refill/form.php | **money-move** |
| 85 | 126 | `^admin/refill/([0-9]+)$` | `?page=admin_refill_detail&id=$1`  | admin_refill_detail | _admin/_refill/form.php | **money-move** |
| 86 | 127 | `^admin/refill/status/([0-9]+)$` | `?page=admin_refill&status=$1`  | admin_refill | _admin/_refill/index.php | **money-move** |
| 87 | 129 | `^admin/product_type$` | `?page=admin_product_type`  | admin_product_type | _admin/_product_type/index.php | **settings** |
| 88 | 130 | `^admin/product_type/create$` | `?page=admin_product_type_detail&id=0`  | admin_product_type_detail | _admin/_product_type/form.php | **settings** |
| 89 | 131 | `^admin/product_type/([0-9]+)$` | `?page=admin_product_type_detail&id=$1`  | admin_product_type_detail | _admin/_product_type/form.php | **settings** |
| 90 | 133 | `^admin/shipping_type$` | `?page=admin_shipping_type`  | admin_shipping_type | _admin/_shipping_type/index.php | **settings** |
| 91 | 134 | `^admin/shipping_type/create$` | `?page=admin_shipping_type_detail&id=0`  | admin_shipping_type_detail | _admin/_shipping_type/form.php | **settings** |
| 92 | 135 | `^admin/shipping_type/([0-9]+)$` | `?page=admin_shipping_type_detail&id=$1`  | admin_shipping_type_detail | _admin/_shipping_type/form.php | **settings** |
| 93 | 137 | `^admin/lot$` | `?page=admin_lot`  | admin_lot | _admin/_lot/index.php | **lot** |
| 94 | 138 | `^admin/lot/create$` | `?page=admin_lot_detail&id=0`  | admin_lot_detail | _admin/_lot/form.php | **lot** |
| 95 | 139 | `^admin/lot/([0-9]+)$` | `?page=admin_lot_detail&id=$1`  | admin_lot_detail | _admin/_lot/form.php | **lot** |
| 96 | 141 | `^admin/user_type$` | `?page=admin_user_type`  | admin_user_type | _admin/_user_type/index.php | **users** |
| 97 | 142 | `^admin/user_type/create$` | `?page=admin_user_type_detail&id=0`  | admin_user_type_detail | _admin/_user_type/form.php | **users** |
| 98 | 143 | `^admin/user_type/([0-9]+)$` | `?page=admin_user_type_detail&id=$1`  | admin_user_type_detail | _admin/_user_type/form.php | **users** |
| 99 | 145 | `^admin/payment$` | `?page=admin_payment`  | admin_payment | _admin/_payment/index.php | **payment** |
| 100 | 146 | `^admin/payment/status/([0-9]+)$` | `?page=admin_payment&status=$1`  | admin_payment | _admin/_payment/index.php | **payment** |
| 101 | 147 | `^admin/payment/([0-9]+)$` | `?page=admin_payment_detail&id=$1`  | admin_payment_detail | _admin/_payment/form.php | **payment** |
| 102 | 148 | `^admin/payment/create$` | `?page=admin_payment_create`  | admin_payment_create | _admin/_payment/create.php | **payment** |
| 103 | 149 | `^admin/payment/create/([0-9]+)$` | `?page=admin_payment_create&user_id=$1`  | admin_payment_create | _admin/_payment/create.php | **payment** |
| 104 | 150 | `^admin/payment/create/([0-9]+)/(.+)$` | `?page=admin_payment_create_form&user_id=$1&random=$2`  | admin_payment_create_form | _admin/_payment/create_form.php | **payment** |
| 105 | 153 | `^admin/export$` | `?page=admin_export`  | admin_export | _admin/_export/index.php | **reports** |
| 106 | 154 | `^admin/export/status/([0-9]+)$` | `?page=admin_export&status=$1`  | admin_export | _admin/_export/index.php | **reports** |
| 107 | 155 | `^admin/export/([0-9]+)$` | `?page=admin_export_detail&id=$1`  | admin_export_detail | _admin/_export/form.php | **reports** |
| 108 | 157 | `^admin/transfer$` | `?page=admin_transfer`  | admin_transfer | _admin/_transfer/index.php | **money-move** |
| 109 | 158 | `^admin/transfer/create$` | `?page=admin_transfer_detail&id=0`  | admin_transfer_detail | _admin/_transfer/form.php | **money-move** |
| 110 | 159 | `^admin/transfer/status/([0-9]+)$` | `?page=admin_transfer&status=$1`  | admin_transfer | _admin/_transfer/index.php | **money-move** |
| 111 | 160 | `^admin/transfer/([0-9]+)$` | `?page=admin_transfer_detail&id=$1`  | admin_transfer_detail | _admin/_transfer/form.php | **money-move** |
| 112 | 162 | `^admin/alipay$` | `?page=admin_alipay`  | admin_alipay | _admin/_alipay/index.php | **payment** |
| 113 | 163 | `^admin/alipay/create$` | `?page=admin_alipay_detail&id=0`  | admin_alipay_detail | _admin/_alipay/form.php | **payment** |
| 114 | 164 | `^admin/alipay/status/([0-9]+)$` | `?page=admin_alipay&status=$1`  | admin_alipay | _admin/_alipay/index.php | **payment** |
| 115 | 165 | `^admin/alipay/([0-9]+)$` | `?page=admin_alipay_detail&id=$1`  | admin_alipay_detail | _admin/_alipay/form.php | **payment** |
| 116 | 167 | `^admin/tracking$` | `?page=admin_tracking`  | admin_tracking | _admin/_tracking/index.php | **tracking** |
| 117 | 168 | `^admin/tracking/create$` | `?page=admin_tracking_detail&id=0`  | admin_tracking_detail | _admin/_tracking/form.php | **tracking** |
| 118 | 169 | `^admin/tracking/status/([0-9]+)$` | `?page=admin_tracking&status=$1`  | admin_tracking | _admin/_tracking/index.php | **tracking** |
| 119 | 170 | `^admin/tracking/([0-9]+)$` | `?page=admin_tracking_detail&id=$1`  | admin_tracking_detail | _admin/_tracking/form.php | **tracking** |
| 120 | 172 | `^admin/report$` | `?page=admin_report`  | admin_report | _admin/_report/index.php | **reports** |
| 121 | 173 | `^admin/report/([0-9]+)$` | `?page=admin_report_detail&id=$1`  | admin_report_detail | _admin/_report/form.php | **reports** |
| 122 | 175 | `^admin/send$` | `?page=admin_send`  | admin_send | _admin/_send/index.php | **messaging** |
| 123 | 176 | `^admin/setting$` | `?page=admin_setting`  | admin_setting | _admin/_setting/index.php | **settings** |
| 124 | 178 | `^admin/notice$` | `?page=admin_notice`  | admin_notice | _admin/_notice/index.php | **messaging** |
| 125 | 179 | `^admin/notice/([0-9]+)$` | `?page=admin_notice_detail&id=$1`  | admin_notice_detail | _admin/_notice/form.php | **messaging** |
| 126 | 180 | `^admin/notice/create$` | `?page=admin_notice_detail&id=0`  | admin_notice_detail | _admin/_notice/form.php | **messaging** |
| 127 | 182 | `^admin/combine_track$` | `?page=admin_combine_track`  | admin_combine_track | _admin/_combine_track/index.php | **tracking** |
| 128 | 184 | `^admin/taobao$` | `?page=admin_taobao`  | admin_taobao | _admin/_taobao/index.php | **order** |
| 129 | 185 | `^admin/taobao/create$` | `?page=admin_taobao_detail&id=0`  | admin_taobao_detail | _admin/_taobao/form.php | **order** |
| 130 | 186 | `^admin/taobao/([0-9]+)$` | `?page=admin_taobao_detail&id=$1`  | admin_taobao_detail | _admin/_taobao/form.php | **order** |
| 131 | 187 | `^admin/taobao/status/([0-9]+)$` | `?page=admin_taobao&status=$1`  | admin_taobao | _admin/_taobao/index.php | **order** |
| 132 | 189 | `^admin/lost_package$` | `?page=admin_lost_package`  | admin_lost_package | _admin/_lost_package/index.php | **tracking** |
| 133 | 190 | `^admin/lost_package/create$` | `?page=admin_lost_package_detail&id=0`  | admin_lost_package_detail | _admin/_lost_package/form.php | **tracking** |
| 134 | 191 | `^admin/lost_package/status/([0-9]+)$` | `?page=admin_lost_package&status=$1`  | admin_lost_package | _admin/_lost_package/index.php | **tracking** |
| 135 | 192 | `^admin/lost_package/([0-9]+)$` | `?page=admin_lost_package_detail&id=$1`  | admin_lost_package_detail | _admin/_lost_package/form.php | **tracking** |
| 136 | 194 | `^admin/shop_cashback$` | `?page=admin_shop_cashback`  | admin_shop_cashback | _admin/_shop_cashback/index.php | **points** |
| 137 | 195 | `^admin/shop_cashback/create$` | `?page=admin_shop_cashback_detail&id=0`  | admin_shop_cashback_detail | _admin/_shop_cashback/form.php | **points** |
| 138 | 196 | `^admin/shop_cashback/([0-9]+)$` | `?page=admin_shop_cashback_detail&id=$1`  | admin_shop_cashback_detail | _admin/_shop_cashback/form.php | **points** |
| 139 | 197 | `^admin/shop_cashback/status/([0-9]+)$` | `?page=admin_shop_cashback&status=$1`  | admin_shop_cashback | _admin/_shop_cashback/index.php | **points** |
| 140 | 199 | `^admin/point$` | `?page=admin_point`  | admin_point | _admin/_point/index.php | **points** |
| 141 | 200 | `^admin/withdrawpoint$` | `?page=admin_withdrawpoint`  | admin_withdrawpoint | _admin/_withdrawpoint/index.php | **points** |
| 142 | 201 | `^admin/withdrawpoint/([0-9]+)$` | `?page=admin_withdrawpoint_detail&id=$1`  | admin_withdrawpoint_detail | _admin/_withdrawpoint/form.php | **points** |
| 143 | 202 | `^admin/withdrawpoint/status/([0-9]+)$` | `?page=admin_withdrawpoint&status=$1`  | admin_withdrawpoint | _admin/_withdrawpoint/index.php | **points** |
| 144 | 204 | `^admin/withdrawcash$` | `?page=admin_withdrawcash`  | admin_withdrawcash | _admin/_withdrawcash/index.php | **money-move** |
| 145 | 205 | `^admin/withdrawcash/([0-9]+)$` | `?page=admin_withdrawcash_detail&id=$1`  | admin_withdrawcash_detail | _admin/_withdrawcash/form.php | **money-move** |
| 146 | 206 | `^admin/withdrawcash/status/([0-9]+)$` | `?page=admin_withdrawcash&status=$1`  | admin_withdrawcash | _admin/_withdrawcash/index.php | **money-move** |
| 147 | 208 | `^admin/exchange$` | `?page=admin_exchange`  | admin_exchange | _admin/_exchange/index.php | **settings** |
| 148 | 209 | `^admin/exchange/([0-9]+)$` | `?page=admin_exchange_detail&id=$1`  | admin_exchange_detail | _admin/_exchange/form.php | **settings** |
| 149 | 210 | `^admin/exchange/status/([0-9]+)$` | `?page=admin_exchange&status=$1`  | admin_exchange | _admin/_exchange/index.php | **settings** |
| 150 | 212 | `^admin/goodsconfirm$` | `?page=admin_goodsconfirm`  | admin_goodsconfirm | _admin/_goodsconfirm/index.php | **warehouse** |
| 151 | 213 | `^admin/goodsconfirm/([0-9]+)$` | `?page=admin_goodsconfirm_detail&id=$1`  | admin_goodsconfirm_detail | _admin/_goodsconfirm/form.php | **warehouse** |
| 152 | 214 | `^admin/goodsconfirm/status/([0-9]+)$` | `?page=admin_goodsconfirm&status=$1`  | admin_goodsconfirm | _admin/_goodsconfirm/index.php | **warehouse** |
| 153 | 215 | `^admin/goodsconfirm/create$` | `?page=admin_goodsconfirm_detail&id=0`  | admin_goodsconfirm_detail | _admin/_goodsconfirm/form.php | **warehouse** |
| 154 | 217 | `^admin/mall$` | `?page=admin_mall`  | admin_mall | _admin/_mall/index.php | **shop** |
| 155 | 218 | `^admin/mall/([0-9]+)$` | `?page=admin_mall_detail&id=$1`  | admin_mall_detail | _admin/_mall/form.php | **shop** |
| 156 | 219 | `^admin/mall_stock$` | `?page=admin_mall_stock`  | admin_mall_stock | _admin/_mallstock/index.php | **shop** |
| 157 | 220 | `^admin/mall/create$` | `?page=admin_mall_detail&id=0`  | admin_mall_detail | _admin/_mall/form.php | **shop** |
| 158 | 223 | `^admin/mall_category$` | `?page=admin_mall_category`  | admin_mall_category | _admin/_mall_category/index.php | **shop** |
| 159 | 224 | `^admin/mall_category/([0-9]+)$` | `?page=admin_mall_category_detail&id=$1`  | admin_mall_category_detail | _admin/_mall_category/form.php | **shop** |
| 160 | 225 | `^admin/mall_category/create$` | `?page=admin_mall_category_detail&id=0`  | admin_mall_category_detail | _admin/_mall_category/form.php | **shop** |
| 161 | 227 | `^admin/warehouse_section$` | `?page=admin_warehouse_section`  | admin_warehouse_section | _admin/_warehouse_section/index.php | **warehouse** |
| 162 | 228 | `^admin/warehouse_section/([0-9]+)$` | `?page=admin_warehouse_section_detail&id=$1`  | admin_warehouse_section_detail | _admin/_warehouse_section/form.php | **warehouse** |
| 163 | 229 | `^admin/warehouse_section/create$` | `?page=admin_warehouse_section_detail&id=0`  | admin_warehouse_section_detail | _admin/_warehouse_section/form.php | **warehouse** |
| 164 | 231 | `^admin/warehouse_section_move$` | `?page=admin_warehouse_section_move`  | admin_warehouse_section_move | _admin/_warehouse_section_move/index.php | **warehouse** |
| 165 | 232 | `^admin/warehouse$` | `?page=admin_warehouse`  | admin_warehouse | _admin/_warehouse/index.php | **warehouse** |
| 166 | 233 | `^admin/warehouse/([0-9]+)$` | `?page=admin_warehouse_detail&id=$1`  | admin_warehouse_detail | _admin/_warehouse/form.php | **warehouse** |
| 167 | 234 | `^admin/warehouse/create$` | `?page=admin_warehouse_detail&id=0`  | admin_warehouse_detail | _admin/_warehouse/form.php | **warehouse** |
| 168 | 236 | `^admin/log$` | `?page=admin_log`  | admin_log | _admin/_log/index.php | **reports** |
| 169 | 244 | `^forums/` | `-` [L,NC] |  |  | **foundation (nginx/infra)** |
| 170 | 246 | `^` | `%1` [R,L] |  |  | **foundation (nginx/infra)** |
| 171 | 249 | `^(.*?)/?$` | `$1.php` [L] |  |  | **foundation (nginx/infra)** |

## 2. ไฟล์ PHP ทั้งหมดของเว็บเดิม (734 ไฟล์, ไม่รวม vendor/)

URL ajax = ชื่อไฟล์ตัด `.php` (เช่น `_tracking/grid.php` → `POST /_tracking/grid`) · ไฟล์ที่เป็นหน้า (page) แสดง page ไว้ด้วย

### tracking (37 ไฟล์)

| ไฟล์ | URL เดิม | page | หมายเหตุ |
|---|---|---|---|
| `_admin/_combine_track/index.php` | `/_admin/_combine_track/index` | admin_combine_track |  |
| `_admin/_combine_track/load_tracking.php` | `/_admin/_combine_track/load_tracking` |  |  |
| `_admin/_combine_track/save_combine.php` | `/_admin/_combine_track/save_combine` |  |  |
| `_admin/_lost_package/cancel.php` | `/_admin/_lost_package/cancel` |  |  |
| `_admin/_lost_package/email_approved.php` | `/_admin/_lost_package/email_approved` |  |  |
| `_admin/_lost_package/email_cancel.php` | `/_admin/_lost_package/email_cancel` |  |  |
| `_admin/_lost_package/form.php` | `/_admin/_lost_package/form` | admin_lost_package_detail |  |
| `_admin/_lost_package/grid.php` | `/_admin/_lost_package/grid` |  |  |
| `_admin/_lost_package/index.php` | `/_admin/_lost_package/index` | admin_lost_package |  |
| `_admin/_lost_package/rollback.php` | `/_admin/_lost_package/rollback` |  |  |
| `_admin/_lost_package/save_approved.php` | `/_admin/_lost_package/save_approved` |  |  |
| `_admin/_tracking/delete.php` | `/_admin/_tracking/delete` |  |  |
| `_admin/_tracking/form.php` | `/_admin/_tracking/form` | admin_tracking_detail |  |
| `_admin/_tracking/grid.php` | `/_admin/_tracking/grid` |  |  |
| `_admin/_tracking/index.php` | `/_admin/_tracking/index` | admin_tracking |  |
| `_admin/_tracking/load_paging.php` | `/_admin/_tracking/load_paging` |  |  |
| `_admin/_tracking/load_pricing.php` | `/_admin/_tracking/load_pricing` |  |  |
| `_admin/_tracking/load_product_type.php` | `/_admin/_tracking/load_product_type` |  |  |
| `_admin/_tracking/save.php` | `/_admin/_tracking/save` |  |  |
| `_admin/_tracking/save_lock.php` | `/_admin/_tracking/save_lock` |  |  |
| `_lost_package/cancel.php` | `/_lost_package/cancel` |  |  |
| `_lost_package/form.php` | `/_lost_package/form` | lost_package_detail |  |
| `_lost_package/grid.php` | `/_lost_package/grid` |  |  |
| `_lost_package/index.php` | `/_lost_package/index` | lost_package |  |
| `_lost_package/load_order_number.php` | `/_lost_package/load_order_number` |  |  |
| `_lost_package/load_upload.php` | `/_lost_package/load_upload` |  |  |
| `_lost_package/save.php` | `/_lost_package/save` |  |  |
| `_lost_package/save_image.php` | `/_lost_package/save_image` |  |  |
| `_lost_package/upload.php` | `/_lost_package/upload` |  |  |
| `_tracking/delete.php` | `/_tracking/delete` |  |  |
| `_tracking/form.php` | `/_tracking/form` | tracking_detail |  |
| `_tracking/grid.php` | `/_tracking/grid` |  |  |
| `_tracking/index.php` | `/_tracking/index` | tracking |  |
| `_tracking/load_pricing.php` | `/_tracking/load_pricing` |  |  |
| `_tracking/load_product_type.php` | `/_tracking/load_product_type` |  |  |
| `_tracking/save.php` | `/_tracking/save` |  |  |
| `_tracking/save_quick_quote.php` | `/_tracking/save_quick_quote` |  |  |

### shop (64 ไฟล์)

| ไฟล์ | URL เดิม | page | หมายเหตุ |
|---|---|---|---|
| `_admin/_mall/delete.php` | `/_admin/_mall/delete` |  |  |
| `_admin/_mall/form.php` | `/_admin/_mall/form` | admin_mall_detail |  |
| `_admin/_mall/grid.php` | `/_admin/_mall/grid` |  |  |
| `_admin/_mall/index.php` | `/_admin/_mall/index` | admin_mall |  |
| `_admin/_mall/load_detail.php` | `/_admin/_mall/load_detail` |  |  |
| `_admin/_mall/load_sizecolor.php` | `/_admin/_mall/load_sizecolor` |  |  |
| `_admin/_mall/load_stock.php` | `/_admin/_mall/load_stock` |  |  |
| `_admin/_mall/save.php` | `/_admin/_mall/save` |  |  |
| `_admin/_mall/save_delsizecolor.php` | `/_admin/_mall/save_delsizecolor` |  |  |
| `_admin/_mall/save_isshow.php` | `/_admin/_mall/save_isshow` |  |  |
| `_admin/_mall/save_sizecolor.php` | `/_admin/_mall/save_sizecolor` |  |  |
| `_admin/_mall_category/delete.php` | `/_admin/_mall_category/delete` |  |  |
| `_admin/_mall_category/form.php` | `/_admin/_mall_category/form` | admin_mall_category_detail |  |
| `_admin/_mall_category/grid.php` | `/_admin/_mall_category/grid` |  | ตรวจ `getPermission('admin_mall_category')` แต่ไม่มีโมดูลนี้ใน role_detail → ไม่มีใครเข้าได้ (แม้แอดมิน) |
| `_admin/_mall_category/index.php` | `/_admin/_mall_category/index` | admin_mall_category | ตรวจ `getPermission('admin_mall_category')` แต่ไม่มีโมดูลนี้ใน role_detail → ไม่มีใครเข้าได้ (แม้แอดมิน) |
| `_admin/_mall_category/save.php` | `/_admin/_mall_category/save` |  | ตรวจ `getPermission('admin_mall_category')` แต่ไม่มีโมดูลนี้ใน role_detail → ไม่มีใครเข้าได้ (แม้แอดมิน) |
| `_admin/_mallstock/grid.php` | `/_admin/_mallstock/grid` |  |  |
| `_admin/_mallstock/index.php` | `/_admin/_mallstock/index` | admin_mall_stock |  |
| `_admin/_mallstock/load_amount.php` | `/_admin/_mallstock/load_amount` |  |  |
| `_admin/_mallstock/save_amount.php` | `/_admin/_mallstock/save_amount` |  |  |
| `_api/blockblob.php` | `/_api/blockblob` |  |  |
| `_api/blockblob_ajax.php` | `/_api/blockblob_ajax` |  |  |
| `_api/crawl_guard.php` | `/_api/crawl_guard` |  |  |
| `_api/get_order_number_v2.php` | `/_api/get_order_number_v2` |  |  |
| `_api/get_order_number_v3.php` | `/_api/get_order_number_v3` |  |  |
| `_api/image_shrink.php` | `/_api/image_shrink` |  |  |
| `_api/load_product.php` | `/_api/load_product` |  |  |
| `_api/load_product_v2.php` | `/_api/load_product_v2` |  |  |
| `_api/load_product_v5.php` | `/_api/load_product_v5` |  |  |
| `_api/load_product_v7.php` | `/_api/load_product_v7` |  |  |
| `_api/send_sms.php` | `/_api/send_sms` |  |  |
| `_api/submit_order_number_v2.php` | `/_api/submit_order_number_v2` |  |  |
| `_api/unban.php` | `/_api/unban` |  |  |
| `_api/updateImagePrefix.php` | `/_api/updateImagePrefix` |  |  |
| `_api/useragent.php` | `/_api/useragent` |  |  |
| `_cart/cart.php` | `/_cart/cart` |  |  |
| `_cart/checkout.php` | `/_cart/checkout` | checkout |  |
| `_cart/delete_cart.php` | `/_cart/delete_cart` |  |  |
| `_cart/delete_carts.php` | `/_cart/delete_carts` |  |  |
| `_cart/load_cart.php` | `/_cart/load_cart` |  |  |
| `_cart/load_choose_product.php` | `/_cart/load_choose_product` |  |  |
| `_cart/load_full_cart.php` | `/_cart/load_full_cart` |  |  |
| `_cart/product.php` | `/_cart/product` | checkout_product |  |
| `_cart/product_2.php` | `/_cart/product_2` |  |  |
| `_cart/save_amount.php` | `/_cart/save_amount` |  |  |
| `_cart/save_checkout.php` | `/_cart/save_checkout` |  |  |
| `_cart/save_craft.php` | `/_cart/save_craft` |  |  |
| `_cart/save_product.php` | `/_cart/save_product` |  |  |
| `_cart/save_qc.php` | `/_cart/save_qc` |  |  |
| `_mall/addtocart.php` | `/_mall/addtocart` |  |  |
| `_mall/delete_cart.php` | `/_mall/delete_cart` |  |  |
| `_mall/formcart.php` | `/_mall/formcart` | mall_cart |  |
| `_mall/grid.php` | `/_mall/grid` |  |  |
| `_mall/index.php` | `/_mall/index` | mall |  |
| `_mall/load_detail.php` | `/_mall/load_detail` |  |  |
| `_mall/save.php` | `/_mall/save` |  |  |
| `_taobao/delete.php` | `/_taobao/delete` |  |  |
| `_taobao/form.php` | `/_taobao/form` | taobao_detail |  |
| `_taobao/grid.php` | `/_taobao/grid` |  |  |
| `_taobao/index.php` | `/_taobao/index` | taobao |  |
| `_taobao/load_calculator.php` | `/_taobao/load_calculator` |  |  |
| `_taobao/load_new_line.php` | `/_taobao/load_new_line` |  |  |
| `_taobao/save.php` | `/_taobao/save` |  |  |
| `_taobao/save_remark.php` | `/_taobao/save_remark` |  |  |

### order (63 ไฟล์)

| ไฟล์ | URL เดิม | page | หมายเหตุ |
|---|---|---|---|
| `_admin/_order/delete.php` | `/_admin/_order/delete` |  |  |
| `_admin/_order/email_approved.php` | `/_admin/_order/email_approved` |  |  |
| `_admin/_order/email_cancel.php` | `/_admin/_order/email_cancel` |  |  |
| `_admin/_order/email_cashback.php` | `/_admin/_order/email_cashback` |  |  |
| `_admin/_order/form.php` | `/_admin/_order/form` | order_admin_detail |  |
| `_admin/_order/get_notrack.php` | `/_admin/_order/get_notrack` |  |  |
| `_admin/_order/get_nowarehouse.php` | `/_admin/_order/get_nowarehouse` |  |  |
| `_admin/_order/get_shop.php` | `/_admin/_order/get_shop` |  |  |
| `_admin/_order/grid.php` | `/_admin/_order/grid` |  |  |
| `_admin/_order/index.php` | `/_admin/_order/index` | order_admin |  |
| `_admin/_order/load_calculate_cashback.php` | `/_admin/_order/load_calculate_cashback` |  |  |
| `_admin/_order/load_color_form.php` | `/_admin/_order/load_color_form` |  |  |
| `_admin/_order/load_detail.php` | `/_admin/_order/load_detail` |  |  |
| `_admin/_order/load_paging.php` | `/_admin/_order/load_paging` |  | ตาราง `caching` ไม่มีใน DB (query ล้มเงียบ) |
| `_admin/_order/load_remark_product.php` | `/_admin/_order/load_remark_product` |  |  |
| `_admin/_order/load_remark_shop.php` | `/_admin/_order/load_remark_shop` |  |  |
| `_admin/_order/load_total_order.php` | `/_admin/_order/load_total_order` |  |  |
| `_admin/_order/save_add_new_track.php` | `/_admin/_order/save_add_new_track` |  |  |
| `_admin/_order/save_amount.php` | `/_admin/_order/save_amount` |  |  |
| `_admin/_order/save_approved.php` | `/_admin/_order/save_approved` |  |  |
| `_admin/_order/save_cashback.php` | `/_admin/_order/save_cashback` |  |  |
| `_admin/_order/save_cashback_cancel.php` | `/_admin/_order/save_cashback_cancel` |  |  |
| `_admin/_order/save_color.php` | `/_admin/_order/save_color` |  |  |
| `_admin/_order/save_craft.php` | `/_admin/_order/save_craft` |  |  |
| `_admin/_order/save_finish.php` | `/_admin/_order/save_finish` |  |  |
| `_admin/_order/save_lock.php` | `/_admin/_order/save_lock` |  |  |
| `_admin/_order/save_order_number.php` | `/_admin/_order/save_order_number` |  |  |
| `_admin/_order/save_price.php` | `/_admin/_order/save_price` |  |  |
| `_admin/_order/save_product_type.php` | `/_admin/_order/save_product_type` |  |  |
| `_admin/_order/save_qc.php` | `/_admin/_order/save_qc` |  |  |
| `_admin/_order/save_quote.php` | `/_admin/_order/save_quote` |  |  |
| `_admin/_order/save_quote_admin.php` | `/_admin/_order/save_quote_admin` |  |  |
| `_admin/_order/save_remark.php` | `/_admin/_order/save_remark` |  |  |
| `_admin/_order/save_remark_product.php` | `/_admin/_order/save_remark_product` |  |  |
| `_admin/_order/save_remark_shop.php` | `/_admin/_order/save_remark_shop` |  |  |
| `_admin/_order/save_remove_track.php` | `/_admin/_order/save_remove_track` |  |  |
| `_admin/_order/save_shipping.php` | `/_admin/_order/save_shipping` |  |  |
| `_admin/_order/save_tracking.php` | `/_admin/_order/save_tracking` |  |  |
| `_admin/_order/save_valid_track.php` | `/_admin/_order/save_valid_track` |  |  |
| `_admin/_order/save_vat.php` | `/_admin/_order/save_vat` |  |  |
| `_admin/_taobao/email_approved.php` | `/_admin/_taobao/email_approved` |  |  |
| `_admin/_taobao/email_cancel.php` | `/_admin/_taobao/email_cancel` |  |  |
| `_admin/_taobao/form.php` | `/_admin/_taobao/form` | admin_taobao_detail |  |
| `_admin/_taobao/grid.php` | `/_admin/_taobao/grid` |  |  |
| `_admin/_taobao/index.php` | `/_admin/_taobao/index` | admin_taobao |  |
| `_admin/_taobao/load_calculator.php` | `/_admin/_taobao/load_calculator` |  |  |
| `_admin/_taobao/load_new_line.php` | `/_admin/_taobao/load_new_line` |  |  |
| `_admin/_taobao/rollback.php` | `/_admin/_taobao/rollback` |  |  |
| `_admin/_taobao/save.php` | `/_admin/_taobao/save` |  |  |
| `_admin/_taobao/save_approved.php` | `/_admin/_taobao/save_approved` |  |  |
| `_admin/_taobao/save_cancel.php` | `/_admin/_taobao/save_cancel` |  |  |
| `_admin/_taobao/save_remark.php` | `/_admin/_taobao/save_remark` |  |  |
| `_order/delete.php` | `/_order/delete` |  |  |
| `_order/form.php` | `/_order/form` | order_detail |  |
| `_order/grid.php` | `/_order/grid` |  |  |
| `_order/index.php` | `/_order/index` | order |  |
| `_order/load_paging.php` | `/_order/load_paging` |  |  |
| `_order/load_total_order.php` | `/_order/load_total_order` |  |  |
| `_order/print.php` | `/_order/print` |  |  |
| `_order/save_payment.php` | `/_order/save_payment` |  |  |
| `_order/save_quote.php` | `/_order/save_quote` |  |  |
| `_order/save_quote_tracking.php` | `/_order/save_quote_tracking` |  |  |
| `_order/save_remark.php` | `/_order/save_remark` |  |  |

### payment (51 ไฟล์)

| ไฟล์ | URL เดิม | page | หมายเหตุ |
|---|---|---|---|
| `_admin/_alipay/cancel.php` | `/_admin/_alipay/cancel` |  |  |
| `_admin/_alipay/email_cancel.php` | `/_admin/_alipay/email_cancel` |  |  |
| `_admin/_alipay/email_success.php` | `/_admin/_alipay/email_success` |  |  |
| `_admin/_alipay/form.php` | `/_admin/_alipay/form` | admin_alipay_detail |  |
| `_admin/_alipay/grid.php` | `/_admin/_alipay/grid` |  |  |
| `_admin/_alipay/index.php` | `/_admin/_alipay/index` | admin_alipay |  |
| `_admin/_alipay/rollback.php` | `/_admin/_alipay/rollback` |  |  |
| `_admin/_alipay/save.php` | `/_admin/_alipay/save` |  |  |
| `_admin/_payment/create.php` | `/_admin/_payment/create` | admin_payment_create |  |
| `_admin/_payment/create_form.php` | `/_admin/_payment/create_form` | admin_payment_create_form |  |
| `_admin/_payment/email_approved.php` | `/_admin/_payment/email_approved` |  |  |
| `_admin/_payment/email_cancel.php` | `/_admin/_payment/email_cancel` |  |  |
| `_admin/_payment/form.php` | `/_admin/_payment/form` | admin_payment_detail |  |
| `_admin/_payment/grid.php` | `/_admin/_payment/grid` |  |  |
| `_admin/_payment/index.php` | `/_admin/_payment/index` | admin_payment |  |
| `_admin/_payment/load_autocomplete_user.php` | `/_admin/_payment/load_autocomplete_user` |  |  |
| `_admin/_payment/load_create.php` | `/_admin/_payment/load_create` |  |  |
| `_admin/_payment/load_select_lot.php` | `/_admin/_payment/load_select_lot` |  | ตรวจ `getPermission('admin_payment_createpayment')` แต่ไม่มีโมดูลนี้ใน role_detail → ไม่มีใครเข้าได้ (แม้แอดมิน) |
| `_admin/_payment/load_shipping_price.php` | `/_admin/_payment/load_shipping_price` |  |  |
| `_admin/_payment/load_user_by_id.php` | `/_admin/_payment/load_user_by_id` |  |  |
| `_admin/_payment/save_approved.php` | `/_admin/_payment/save_approved` |  |  |
| `_admin/_payment/save_create.php` | `/_admin/_payment/save_create` |  |  |
| `_admin/_payment/save_delete.php` | `/_admin/_payment/save_delete` |  |  |
| `_admin/_payment/save_deletenopush.php` | `/_admin/_payment/save_deletenopush` |  |  |
| `_admin/_payment/save_new_sender.php` | `/_admin/_payment/save_new_sender` |  |  |
| `_admin/_payment/save_submit_payment.php` | `/_admin/_payment/save_submit_payment` |  |  |
| `_admin/_payment/save_vat.php` | `/_admin/_payment/save_vat` |  |  |
| `_alipay/cancel.php` | `/_alipay/cancel` |  |  |
| `_alipay/form.php` | `/_alipay/form` | alipay_detail |  |
| `_alipay/grid.php` | `/_alipay/grid` |  |  |
| `_alipay/index.php` | `/_alipay/index` | alipay |  |
| `_alipay/save.php` | `/_alipay/save` |  |  |
| `_payment/create.php` | `/_payment/create` | payment_create |  |
| `_payment/create_form.php` | `/_payment/create_form` | payment_detail_form |  |
| `_payment/form.php` | `/_payment/form` | payment_detail |  |
| `_payment/grid.php` | `/_payment/grid` |  |  |
| `_payment/index.php` | `/_payment/index` | payment |  |
| `_payment/load_create.php` | `/_payment/load_create` |  |  |
| `_payment/load_create_sum.php` | `/_payment/load_create_sum` |  |  |
| `_payment/load_receipt_address.php` | `/_payment/load_receipt_address` |  | ตาราง `user_receipt_address` ไม่มีใน DB (query ล้มเงียบ) |
| `_payment/load_select_lot.php` | `/_payment/load_select_lot` |  |  |
| `_payment/load_shipping_price.php` | `/_payment/load_shipping_price` |  |  |
| `_payment/load_vat.php` | `/_payment/load_vat` |  |  |
| `_payment/save.php` | `/_payment/save` |  |  |
| `_payment/save_create.php` | `/_payment/save_create` |  |  |
| `_payment/save_create_receiptaddress.php` | `/_payment/save_create_receiptaddress` |  | ตาราง `user_receipt_address` ไม่มีใน DB (query ล้มเงียบ) |
| `_payment/save_delete.php` | `/_payment/save_delete` |  |  |
| `_payment/save_deleteaddress.php` | `/_payment/save_deleteaddress` |  | ตาราง `user_address` ไม่มีใน DB (query ล้มเงียบ) |
| `_payment/save_deletereceiptaddress.php` | `/_payment/save_deletereceiptaddress` |  | ตาราง `user_receipt_address` ไม่มีใน DB (query ล้มเงียบ) |
| `_payment/save_submit_payment.php` | `/_payment/save_submit_payment` |  |  |
| `_payment/view_example.php` | `/_payment/view_example` |  |  |

### balance (20 ไฟล์)

| ไฟล์ | URL เดิม | page | หมายเหตุ |
|---|---|---|---|
| `_admin/_balance/grid.php` | `/_admin/_balance/grid` |  |  |
| `_admin/_balance/index.php` | `/_admin/_balance/index` | admin_balance |  |
| `_admin/_balance/load_amount.php` | `/_admin/_balance/load_amount` |  |  |
| `_admin/_balance/save_amount.php` | `/_admin/_balance/save_amount` |  |  |
| `_admin/_balance_cn/grid.php` | `/_admin/_balance_cn/grid` |  |  |
| `_admin/_balance_cn/index.php` | `/_admin/_balance_cn/index` | admin_balance_cn |  |
| `_admin/_balance_cn/load_amount.php` | `/_admin/_balance_cn/load_amount` |  |  |
| `_admin/_balance_cn/save_amount.php` | `/_admin/_balance_cn/save_amount` |  |  |
| `_admin/_balance_company/grid.php` | `/_admin/_balance_company/grid` |  |  |
| `_admin/_balance_company/index.php` | `/_admin/_balance_company/index` | admin_balance_company |  |
| `_admin/_balance_company/load_amount.php` | `/_admin/_balance_company/load_amount` |  |  |
| `_admin/_balance_company/save_amount.php` | `/_admin/_balance_company/save_amount` |  |  |
| `_balance/grid.php` | `/_balance/grid` |  |  |
| `_balance/index.php` | `/_balance/index` | balance |  |
| `_balance/load_withdraw.php` | `/_balance/load_withdraw` |  |  |
| `_balance/save_withdraw.php` | `/_balance/save_withdraw` |  |  |
| `_balance_cn/grid.php` | `/_balance_cn/grid` |  |  |
| `_balance_cn/index.php` | `/_balance_cn/index` | balance_cn |  |
| `_balance_cn/load_withdraw.php` | `/_balance_cn/load_withdraw` |  |  |
| `_balance_cn/save_withdraw.php` | `/_balance_cn/save_withdraw` |  |  |

### money-move (30 ไฟล์)

| ไฟล์ | URL เดิม | page | หมายเหตุ |
|---|---|---|---|
| `_admin/_refill/cancel.php` | `/_admin/_refill/cancel` |  |  |
| `_admin/_refill/email_approved.php` | `/_admin/_refill/email_approved` |  |  |
| `_admin/_refill/email_cancel.php` | `/_admin/_refill/email_cancel` |  |  |
| `_admin/_refill/form.php` | `/_admin/_refill/form` | admin_refill_detail |  |
| `_admin/_refill/grid.php` | `/_admin/_refill/grid` |  |  |
| `_admin/_refill/index.php` | `/_admin/_refill/index` | admin_refill |  |
| `_admin/_refill/save.php` | `/_admin/_refill/save` |  |  |
| `_admin/_refill/save_approved.php` | `/_admin/_refill/save_approved` |  |  |
| `_admin/_transfer/cancel.php` | `/_admin/_transfer/cancel` |  |  |
| `_admin/_transfer/email_cancel.php` | `/_admin/_transfer/email_cancel` |  |  |
| `_admin/_transfer/email_success.php` | `/_admin/_transfer/email_success` |  |  |
| `_admin/_transfer/form.php` | `/_admin/_transfer/form` | admin_transfer_detail |  |
| `_admin/_transfer/grid.php` | `/_admin/_transfer/grid` |  |  |
| `_admin/_transfer/index.php` | `/_admin/_transfer/index` | admin_transfer |  |
| `_admin/_transfer/rollback.php` | `/_admin/_transfer/rollback` |  |  |
| `_admin/_transfer/save.php` | `/_admin/_transfer/save` |  |  |
| `_admin/_withdrawcash/cancel.php` | `/_admin/_withdrawcash/cancel` |  |  |
| `_admin/_withdrawcash/form.php` | `/_admin/_withdrawcash/form` | admin_withdrawcash_detail |  |
| `_admin/_withdrawcash/grid.php` | `/_admin/_withdrawcash/grid` |  |  |
| `_admin/_withdrawcash/index.php` | `/_admin/_withdrawcash/index` | admin_withdrawcash |  |
| `_admin/_withdrawcash/save_approved.php` | `/_admin/_withdrawcash/save_approved` |  |  |
| `_refill/form.php` | `/_refill/form` | refill_detail |  |
| `_refill/grid.php` | `/_refill/grid` |  |  |
| `_refill/index.php` | `/_refill/index` | refill |  |
| `_refill/save.php` | `/_refill/save` |  |  |
| `_transfer/cancel.php` | `/_transfer/cancel` |  |  |
| `_transfer/form.php` | `/_transfer/form` | transfer_detail |  |
| `_transfer/grid.php` | `/_transfer/grid` |  |  |
| `_transfer/index.php` | `/_transfer/index` | transfer |  |
| `_transfer/save.php` | `/_transfer/save` |  |  |

### points (27 ไฟล์)

| ไฟล์ | URL เดิม | page | หมายเหตุ |
|---|---|---|---|
| `_admin/_point/grid.php` | `/_admin/_point/grid` |  |  |
| `_admin/_point/index.php` | `/_admin/_point/index` | admin_point |  |
| `_admin/_shop_cashback/email_approved.php` | `/_admin/_shop_cashback/email_approved` |  |  |
| `_admin/_shop_cashback/email_cancel.php` | `/_admin/_shop_cashback/email_cancel` |  |  |
| `_admin/_shop_cashback/form.php` | `/_admin/_shop_cashback/form` | admin_shop_cashback_detail |  |
| `_admin/_shop_cashback/grid.php` | `/_admin/_shop_cashback/grid` |  |  |
| `_admin/_shop_cashback/index.php` | `/_admin/_shop_cashback/index` | admin_shop_cashback |  |
| `_admin/_shop_cashback/save.php` | `/_admin/_shop_cashback/save` |  |  |
| `_admin/_shop_cashback/save_approved.php` | `/_admin/_shop_cashback/save_approved` |  |  |
| `_admin/_shop_cashback/save_cancel.php` | `/_admin/_shop_cashback/save_cancel` |  |  |
| `_admin/_shop_cashback/save_remark.php` | `/_admin/_shop_cashback/save_remark` |  |  |
| `_admin/_withdrawpoint/cancel.php` | `/_admin/_withdrawpoint/cancel` |  |  |
| `_admin/_withdrawpoint/form.php` | `/_admin/_withdrawpoint/form` | admin_withdrawpoint_detail |  |
| `_admin/_withdrawpoint/grid.php` | `/_admin/_withdrawpoint/grid` |  |  |
| `_admin/_withdrawpoint/index.php` | `/_admin/_withdrawpoint/index` | admin_withdrawpoint |  |
| `_admin/_withdrawpoint/save_approved.php` | `/_admin/_withdrawpoint/save_approved` |  |  |
| `_point/grid.php` | `/_point/grid` |  |  |
| `_point/index.php` | `/_point/index` | point |  |
| `_point/load_withdraw.php` | `/_point/load_withdraw` |  |  |
| `_point/save_withdraw.php` | `/_point/save_withdraw` |  |  |
| `_shop_cashback/form.php` | `/_shop_cashback/form` | shop_cashback_detail |  |
| `_shop_cashback/grid.php` | `/_shop_cashback/grid` |  |  |
| `_shop_cashback/index.php` | `/_shop_cashback/index` | shop_cashback |  |
| `_shop_cashback/load_calculator.php` | `/_shop_cashback/load_calculator` |  |  |
| `_shop_cashback/load_new_line.php` | `/_shop_cashback/load_new_line` |  |  |
| `_shop_cashback/save.php` | `/_shop_cashback/save` |  |  |
| `_shop_cashback/save_remark.php` | `/_shop_cashback/save_remark` |  |  |

### lot (43 ไฟล์)

| ไฟล์ | URL เดิม | page | หมายเหตุ |
|---|---|---|---|
| `_admin/_lot/cancel.php` | `/_admin/_lot/cancel` |  |  |
| `_admin/_lot/cron_lot_mail.php` | `/_admin/_lot/cron_lot_mail` |  |  |
| `_admin/_lot/delete.php` | `/_admin/_lot/delete` |  |  |
| `_admin/_lot/email.php` | `/_admin/_lot/email` |  |  |
| `_admin/_lot/email_lot.php` | `/_admin/_lot/email_lot` |  |  |
| `_admin/_lot/form.php` | `/_admin/_lot/form` | admin_lot_detail |  |
| `_admin/_lot/grid.php` | `/_admin/_lot/grid` |  |  |
| `_admin/_lot/index.php` | `/_admin/_lot/index` | admin_lot |  |
| `_admin/_lot/load_autocomplete_tracking.php` | `/_admin/_lot/load_autocomplete_tracking` |  |  |
| `_admin/_lot/load_autocomplete_user.php` | `/_admin/_lot/load_autocomplete_user` |  |  |
| `_admin/_lot/load_check_tracking.php` | `/_admin/_lot/load_check_tracking` |  |  |
| `_admin/_lot/load_lastest_lot.php` | `/_admin/_lot/load_lastest_lot` |  |  |
| `_admin/_lot/load_lot_detail.php` | `/_admin/_lot/load_lot_detail` |  |  |
| `_admin/_lot/load_lot_memo.php` | `/_admin/_lot/load_lot_memo` |  |  |
| `_admin/_lot/load_ref_print.php` | `/_admin/_lot/load_ref_print` |  |  |
| `_admin/_lot/load_tracking.php` | `/_admin/_lot/load_tracking` |  |  |
| `_admin/_lot/load_user_rate.php` | `/_admin/_lot/load_user_rate` |  |  |
| `_admin/_lot/print.php` | `/_admin/_lot/print` |  |  |
| `_admin/_lot/print_all.php` | `/_admin/_lot/print_all` |  |  |
| `_admin/_lot/print_receive.php` | `/_admin/_lot/print_receive` |  |  |
| `_admin/_lot/save.php` | `/_admin/_lot/save` |  |  |
| `_admin/_lot/save_delete_lot_memo.php` | `/_admin/_lot/save_delete_lot_memo` |  |  |
| `_admin/_lot/save_file_lot.php` | `/_admin/_lot/save_file_lot` |  |  |
| `_admin/_lot/save_lot_memo.php` | `/_admin/_lot/save_lot_memo` |  |  |
| `_admin/_lot/save_lot_name.php` | `/_admin/_lot/save_lot_name` |  |  |
| `_admin/_lot/save_lot_name_admin.php` | `/_admin/_lot/save_lot_name_admin` |  |  |
| `_admin/_lot/save_lot_ship_amount.php` | `/_admin/_lot/save_lot_ship_amount` |  |  |
| `_admin/_lot/save_new_lot.php` | `/_admin/_lot/save_new_lot` |  |  |
| `_admin/_lot/save_print.php` | `/_admin/_lot/save_print` |  |  |
| `_admin/_lot/save_remove_tracking.php` | `/_admin/_lot/save_remove_tracking` |  |  |
| `_admin/_lot/save_status_lot.php` | `/_admin/_lot/save_status_lot` |  |  |
| `_admin/_lot/save_status_lot_date.php` | `/_admin/_lot/save_status_lot_date` |  |  |
| `_admin/_lot/save_status_reset_tracking.php` | `/_admin/_lot/save_status_reset_tracking` |  |  |
| `_admin/_lot/save_status_tracking.php` | `/_admin/_lot/save_status_tracking` |  |  |
| `_admin/_lot/save_verified.php` | `/_admin/_lot/save_verified` |  |  |
| `_lot/_message/index.php` | `/_lot/_message/index` |  |  |
| `_lot/_message/load.php` | `/_lot/_message/load` |  |  |
| `_lot/_message/load_dept.php` | `/_lot/_message/load_dept` |  |  |
| `_lot/_message/send.php` | `/_lot/_message/send` |  |  |
| `_lot/form.php` | `/_lot/form` | lot_detail |  |
| `_lot/grid.php` | `/_lot/grid` |  |  |
| `_lot/index.php` | `/_lot/index` | lot |  |
| `_lot/load_lot_detail.php` | `/_lot/load_lot_detail` |  |  |

### warehouse (38 ไฟล์)

| ไฟล์ | URL เดิม | page | หมายเหตุ |
|---|---|---|---|
| `_admin/_goodsconfirm/cancel.php` | `/_admin/_goodsconfirm/cancel` |  |  |
| `_admin/_goodsconfirm/form.php` | `/_admin/_goodsconfirm/form` | admin_goodsconfirm_detail |  |
| `_admin/_goodsconfirm/grid.php` | `/_admin/_goodsconfirm/grid` |  |  |
| `_admin/_goodsconfirm/index.php` | `/_admin/_goodsconfirm/index` | admin_goodsconfirm |  |
| `_admin/_goodsconfirm/load_tracking.php` | `/_admin/_goodsconfirm/load_tracking` |  |  |
| `_admin/_goodsconfirm/load_tracking_by_id.php` | `/_admin/_goodsconfirm/load_tracking_by_id` |  |  |
| `_admin/_goodsconfirm/load_upload.php` | `/_admin/_goodsconfirm/load_upload` |  |  |
| `_admin/_goodsconfirm/save.php` | `/_admin/_goodsconfirm/save` |  |  |
| `_admin/_goodsconfirm/save_approved.php` | `/_admin/_goodsconfirm/save_approved` |  |  |
| `_admin/_goodsconfirm/save_image.php` | `/_admin/_goodsconfirm/save_image` |  |  |
| `_admin/_goodsconfirm/save_unapproved.php` | `/_admin/_goodsconfirm/save_unapproved` |  |  |
| `_admin/_warehouse/form.php` | `/_admin/_warehouse/form` | admin_warehouse_detail |  |
| `_admin/_warehouse/grid.php` | `/_admin/_warehouse/grid` |  |  |
| `_admin/_warehouse/index.php` | `/_admin/_warehouse/index` | admin_warehouse |  |
| `_admin/_warehouse/load_editshelf.php` | `/_admin/_warehouse/load_editshelf` |  |  |
| `_admin/_warehouse/load_lot_detail.php` | `/_admin/_warehouse/load_lot_detail` |  |  |
| `_admin/_warehouse/load_lot_detail_thai.php` | `/_admin/_warehouse/load_lot_detail_thai` |  |  |
| `_admin/_warehouse/load_tracking.php` | `/_admin/_warehouse/load_tracking` |  |  |
| `_admin/_warehouse/load_tracking_edit.php` | `/_admin/_warehouse/load_tracking_edit` |  |  |
| `_admin/_warehouse/save.php` | `/_admin/_warehouse/save` |  |  |
| `_admin/_warehouse/save_cancel_warehouse_calculate.php` | `/_admin/_warehouse/save_cancel_warehouse_calculate` |  |  |
| `_admin/_warehouse/save_editshelf.php` | `/_admin/_warehouse/save_editshelf` |  |  |
| `_admin/_warehouse/save_lot_detail.php` | `/_admin/_warehouse/save_lot_detail` |  |  |
| `_admin/_warehouse/save_new_warehouse.php` | `/_admin/_warehouse/save_new_warehouse` |  |  |
| `_admin/_warehouse/save_push_track.php` | `/_admin/_warehouse/save_push_track` |  |  |
| `_admin/_warehouse/save_track_weight.php` | `/_admin/_warehouse/save_track_weight` |  |  |
| `_admin/_warehouse_section/delete.php` | `/_admin/_warehouse_section/delete` |  |  |
| `_admin/_warehouse_section/form.php` | `/_admin/_warehouse_section/form` | admin_warehouse_section_detail |  |
| `_admin/_warehouse_section/grid.php` | `/_admin/_warehouse_section/grid` |  |  |
| `_admin/_warehouse_section/index.php` | `/_admin/_warehouse_section/index` | admin_warehouse_section |  |
| `_admin/_warehouse_section/save.php` | `/_admin/_warehouse_section/save` |  |  |
| `_admin/_warehouse_section_move/grid.php` | `/_admin/_warehouse_section_move/grid` |  |  |
| `_admin/_warehouse_section_move/index.php` | `/_admin/_warehouse_section_move/index` | admin_warehouse_section_move |  |
| `_goodsconfirm/form.php` | `/_goodsconfirm/form` | goodsconfirm_detail |  |
| `_goodsconfirm/grid.php` | `/_goodsconfirm/grid` |  |  |
| `_goodsconfirm/index.php` | `/_goodsconfirm/index` | goodsconfirm |  |
| `_goodsconfirm/load_tracking_by_id.php` | `/_goodsconfirm/load_tracking_by_id` |  |  |
| `_goodsconfirm/save_approved.php` | `/_goodsconfirm/save_approved` |  |  |

### users (42 ไฟล์)

| ไฟล์ | URL เดิม | page | หมายเหตุ |
|---|---|---|---|
| `_admin/_role/delete.php` | `/_admin/_role/delete` |  |  |
| `_admin/_role/form.php` | `/_admin/_role/form` | admin_role_detail |  |
| `_admin/_role/grid.php` | `/_admin/_role/grid` |  |  |
| `_admin/_role/index.php` | `/_admin/_role/index` | admin_role |  |
| `_admin/_role/save.php` | `/_admin/_role/save` |  |  |
| `_admin/_user/btn_email_lost_password.php` | `/_admin/_user/btn_email_lost_password` |  |  |
| `_admin/_user/btn_pinreset.php` | `/_admin/_user/btn_pinreset` |  |  |
| `_admin/_user/btn_resetpassword.php` | `/_admin/_user/btn_resetpassword` |  |  |
| `_admin/_user/btn_unbanned.php` | `/_admin/_user/btn_unbanned` |  |  |
| `_admin/_user/btn_viewpassword.php` | `/_admin/_user/btn_viewpassword` |  |  |
| `_admin/_user/cancel.php` | `/_admin/_user/cancel` |  |  |
| `_admin/_user/delete.php` | `/_admin/_user/delete` |  |  |
| `_admin/_user/email.php` | `/_admin/_user/email` |  |  |
| `_admin/_user/email_approved.php` | `/_admin/_user/email_approved` |  |  |
| `_admin/_user/email_cancel.php` | `/_admin/_user/email_cancel` |  |  |
| `_admin/_user/form.php` | `/_admin/_user/form` | admin_user_detail |  |
| `_admin/_user/grid.php` | `/_admin/_user/grid` |  |  |
| `_admin/_user/index.php` | `/_admin/_user/index` | admin_user |  |
| `_admin/_user/load_role_detail.php` | `/_admin/_user/load_role_detail` |  |  |
| `_admin/_user/load_user_type_detail.php` | `/_admin/_user/load_user_type_detail` |  |  |
| `_admin/_user/rate_history.php` | `/_admin/_user/rate_history` |  |  |
| `_admin/_user/rate_history_lib.php` | `/_admin/_user/rate_history_lib` |  |  |
| `_admin/_user/save.php` | `/_admin/_user/save` |  |  |
| `_admin/_user/save_approved.php` | `/_admin/_user/save_approved` |  |  |
| `_admin/_user_type/delete.php` | `/_admin/_user_type/delete` |  |  |
| `_admin/_user_type/form.php` | `/_admin/_user_type/form` | admin_user_type_detail |  |
| `_admin/_user_type/grid.php` | `/_admin/_user_type/grid` |  |  |
| `_admin/_user_type/index.php` | `/_admin/_user_type/index` | admin_user_type |  |
| `_admin/_user_type/save.php` | `/_admin/_user_type/save` |  |  |
| `_profile/index.php` | `/_profile/index` | profile |  |
| `_profile/load_role_detail.php` | `/_profile/load_role_detail` |  |  |
| `_profile/load_user_type_detail.php` | `/_profile/load_user_type_detail` |  |  |
| `_profile/pin.php` | `/_profile/pin` |  |  |
| `_profile/save.php` | `/_profile/save` |  |  |
| `_register/email.php` | `/_register/email` |  |  |
| `_register/email_lost_password.php` | `/_register/email_lost_password` |  |  |
| `_register/lost_password.php` | `/_register/lost_password` |  |  |
| `_register/save.php` | `/_register/save` |  |  |
| `lost_password.php` | `/lost_password` |  |  |
| `password.php` | `/password` |  | ใช้ตาราง `user_copy` ซึ่งไม่มีใน DB (query ล้มเงียบ) |
| `register.php` | `/register` |  |  |
| `system_check_username.php` | `/system_check_username` |  |  |

### messaging (38 ไฟล์)

| ไฟล์ | URL เดิม | page | หมายเหตุ |
|---|---|---|---|
| `_admin/_message/deletechat.php` | `/_admin/_message/deletechat` |  |  |
| `_admin/_message/index.php` | `/_admin/_message/index` | admin_message |  |
| `_admin/_message/load.php` | `/_admin/_message/load` |  |  |
| `_admin/_message/load_dept.php` | `/_admin/_message/load_dept` |  |  |
| `_admin/_message/load_user.php` | `/_admin/_message/load_user` |  |  |
| `_admin/_message/save_file.php` | `/_admin/_message/save_file` |  |  |
| `_admin/_message/send.php` | `/_admin/_message/send` |  |  |
| `_admin/_message/send_endconversation.php` | `/_admin/_message/send_endconversation` |  |  |
| `_admin/_notice/cron_notice_mail.php` | `/_admin/_notice/cron_notice_mail` |  |  |
| `_admin/_notice/delete.php` | `/_admin/_notice/delete` |  |  |
| `_admin/_notice/email.php` | `/_admin/_notice/email` |  |  |
| `_admin/_notice/email_notice.php` | `/_admin/_notice/email_notice` |  |  |
| `_admin/_notice/form.php` | `/_admin/_notice/form` | admin_notice_detail |  |
| `_admin/_notice/grid.php` | `/_admin/_notice/grid` |  |  |
| `_admin/_notice/index.php` | `/_admin/_notice/index` | admin_notice |  |
| `_admin/_notice/save.php` | `/_admin/_notice/save` |  |  |
| `_admin/_notice/save_isshow.php` | `/_admin/_notice/save_isshow` |  |  |
| `_admin/_send/index.php` | `/_admin/_send/index` | admin_send |  |
| `_admin/_send/index_self.php` | `/_admin/_send/index_self` |  |  |
| `_linenotify/callback.php` | `/_linenotify/callback` |  |  |
| `_linenotify/index.php` | `/_linenotify/index` | linenotify |  |
| `_linenotify/revoke.php` | `/_linenotify/revoke` |  |  |
| `_message/index.php` | `/_message/index` | message |  |
| `_message/load.php` | `/_message/load` |  |  |
| `_message/load_dept.php` | `/_message/load_dept` |  |  |
| `_message/save_file.php` | `/_message/save_file` |  |  |
| `_message/send.php` | `/_message/send` |  |  |
| `_message/send_endconversation.php` | `/_message/send_endconversation` |  |  |
| `_notice/form.php` | `/_notice/form` | notice_detail |  |
| `_notice/grid.php` | `/_notice/grid` |  |  |
| `_notice/index.php` | `/_notice/index` | notice |  |
| `email_center.php` | `/email_center` |  |  |
| `email_footer.php` | `/email_footer` |  |  |
| `email_header.php` | `/email_header` |  |  |
| `notification.php` | `/notification` |  |  |
| `notifications.php` | `/notifications` |  |  |
| `notify_counts.php` | `/notify_counts` |  |  |
| `notify_token.php` | `/notify_token` |  |  |

### reports (36 ไฟล์)

| ไฟล์ | URL เดิม | page | หมายเหตุ |
|---|---|---|---|
| `_admin/_dashboard/index.php` | `/_admin/_dashboard/index` |  |  |
| `_admin/_export/email_cancel.php` | `/_admin/_export/email_cancel` |  |  |
| `_admin/_export/email_success.php` | `/_admin/_export/email_success` |  |  |
| `_admin/_export/form.php` | `/_admin/_export/form` | admin_export_detail |  |
| `_admin/_export/grid.php` | `/_admin/_export/grid` |  |  |
| `_admin/_export/index.php` | `/_admin/_export/index` | admin_export |  |
| `_admin/_export/print.php` | `/_admin/_export/print` |  |  |
| `_admin/_export/print_2.php` | `/_admin/_export/print_2` |  |  |
| `_admin/_export/save_approved.php` | `/_admin/_export/save_approved` |  |  |
| `_admin/_export/save_delete.php` | `/_admin/_export/save_delete` |  |  |
| `_admin/_log/grid.php` | `/_admin/_log/grid` |  |  |
| `_admin/_log/index.php` | `/_admin/_log/index` | admin_log |  |
| `_admin/_nobilling/index.php` | `/_admin/_nobilling/index` | admin_nobilling |  |
| `_admin/_report/cashmove.php` | `/_admin/_report/cashmove` |  |  |
| `_admin/_report/current_cash.php` | `/_admin/_report/current_cash` |  |  |
| `_admin/_report/current_cash_cn.php` | `/_admin/_report/current_cash_cn` |  |  |
| `_admin/_report/else.php` | `/_admin/_report/else` |  |  |
| `_admin/_report/else_preorder_working.php` | `/_admin/_report/else_preorder_working` |  |  |
| `_admin/_report/file.php` | `/_admin/_report/file` |  |  |
| `_admin/_report/form.php` | `/_admin/_report/form` | admin_report_detail |  |
| `_admin/_report/grid.php` | `/_admin/_report/grid` |  |  |
| `_admin/_report/index.php` | `/_admin/_report/index` | admin_report |  |
| `_admin/_report/refill_5.php` | `/_admin/_report/refill_5` |  |  |
| `_admin/_report/refill_6.php` | `/_admin/_report/refill_6` |  |  |
| `_admin/_report/refill_7.php` | `/_admin/_report/refill_7` |  |  |
| `_admin/_report/shipping.php` | `/_admin/_report/shipping` |  |  |
| `_dashboard/index.php` | `/_dashboard/index` | dashboard |  |
| `_dashboard/index_bk.php` | `/_dashboard/index_bk` |  |  |
| `_export/form.php` | `/_export/form` | export_detail |  |
| `_export/grid.php` | `/_export/grid` |  |  |
| `_export/index.php` | `/_export/index` | export |  |
| `_export/save_approved.php` | `/_export/save_approved` |  |  |
| `_export/save_delete.php` | `/_export/save_delete` |  |  |
| `download_file.php` | `/download_file` |  |  |
| `print_footer.php` | `/print_footer` |  |  |
| `print_header.php` | `/print_header` |  |  |

### settings (30 ไฟล์)

| ไฟล์ | URL เดิม | page | หมายเหตุ |
|---|---|---|---|
| `_admin/_exchange/cancel.php` | `/_admin/_exchange/cancel` |  |  |
| `_admin/_exchange/form.php` | `/_admin/_exchange/form` | admin_exchange_detail |  |
| `_admin/_exchange/grid.php` | `/_admin/_exchange/grid` |  |  |
| `_admin/_exchange/index.php` | `/_admin/_exchange/index` | admin_exchange |  |
| `_admin/_exchange/save_approved.php` | `/_admin/_exchange/save_approved` |  |  |
| `_admin/_product_type/delete.php` | `/_admin/_product_type/delete` |  |  |
| `_admin/_product_type/form.php` | `/_admin/_product_type/form` | admin_product_type_detail |  |
| `_admin/_product_type/grid.php` | `/_admin/_product_type/grid` |  |  |
| `_admin/_product_type/index.php` | `/_admin/_product_type/index` | admin_product_type |  |
| `_admin/_product_type/save.php` | `/_admin/_product_type/save` |  |  |
| `_admin/_setting/index.php` | `/_admin/_setting/index` | admin_setting |  |
| `_admin/_setting/save.php` | `/_admin/_setting/save` |  |  |
| `_admin/_shipping_type/delete.php` | `/_admin/_shipping_type/delete` |  |  |
| `_admin/_shipping_type/delete_cal.php` | `/_admin/_shipping_type/delete_cal` |  |  |
| `_admin/_shipping_type/form.php` | `/_admin/_shipping_type/form` | admin_shipping_type_detail |  |
| `_admin/_shipping_type/grid.php` | `/_admin/_shipping_type/grid` |  |  |
| `_admin/_shipping_type/index.php` | `/_admin/_shipping_type/index` | admin_shipping_type |  |
| `_admin/_shipping_type/load_cal_1.php` | `/_admin/_shipping_type/load_cal_1` |  |  |
| `_admin/_shipping_type/load_cal_2.php` | `/_admin/_shipping_type/load_cal_2` |  |  |
| `_admin/_shipping_type/save.php` | `/_admin/_shipping_type/save` |  |  |
| `_admin/_shipping_type/save_listno.php` | `/_admin/_shipping_type/save_listno` |  |  |
| `_admin/_shipping_type/save_new_price_by_cubic.php` | `/_admin/_shipping_type/save_new_price_by_cubic` |  |  |
| `_admin/_shipping_type/save_new_price_by_kg.php` | `/_admin/_shipping_type/save_new_price_by_kg` |  |  |
| `gateway.php` | `/gateway` |  |  |
| `get_price.php` | `/get_price` |  | ตรวจ `getPermission('view_bol')` แต่ไม่มีโมดูล view_bol ใน role_detail → ทุกคนได้ ErrorBox สิทธิ์ (endpoint ตาย) |
| `get_resource.php` | `/get_resource` |  |  |
| `index_close.php` | `/index_close` |  |  |
| `load_quick_search.php` | `/load_quick_search` |  |  |
| `system_upload.php` | `/system_upload` |  |  |
| `system_upload_2.php` | `/system_upload_2` |  |  |

### foundation (21 ไฟล์)

| ไฟล์ | URL เดิม | page | หมายเหตุ |
|---|---|---|---|
| `assets/validate/validationengine.php` | `/assets/validate/validationengine` |  | resources/views/legacy/partials/validationengine.blade.php |
| `content.php` | `/content` |  | routes/modules/*.php (แทน switch page) |
| `index.php` | `/index` |  | resources/views/legacy/layout.blade.php + routes/legacy.php |
| `login.php` | `/login` | login | GET /login → LegacyLoginController@show |
| `menu.php` | `/menu` |  | resources/views/legacy/partials/menu.blade.php |
| `menu_mobile.php` | `/menu_mobile` |  | resources/views/legacy/partials/menu_mobile.blade.php |
| `resource/cn.php` | `/resource/cn` |  | lang/cn/legacy.php (res()) |
| `resource/en.php` | `/resource/en` |  | lang/en/legacy.php (res()) |
| `resource/th.php` | `/resource/th` |  | lang/th/legacy.php (res()) |
| `sessionmaster/database.class.php` | `/sessionmaster/database.class` |  | (ไม่ใช้แล้ว — session อยู่ใน sessions_v2) |
| `sessionmaster/mysql.sessions.php` | `/sessionmaster/mysql.sessions` |  | (ไม่ใช้แล้ว) |
| `system_blocklist.php` | `/system_blocklist` |  | ข้อมูล: bin/link-assets คัดลอกไป storage/app/legacy |
| `system_change_language.php` | `/system_change_language` |  | POST /system_change_language |
| `system_check_ajax.php` | `/system_check_ajax` |  | middleware legacy.ajax |
| `system_check_login.php` | `/system_check_login` |  | POST /system_check_login |
| `system_checkuser.php` | `/system_checkuser` |  | POST /system_checkuser (logout/RandomStr) |
| `system_config.php` | `/system_config` |  | config/legacy.php + app/Support/Legacy/* |
| `system_db.php` | `/system_db` |  | .env (DB_*) |
| `system_dedup_guard.php` | `/system_dedup_guard` |  | (ปิดใช้งานในเว็บเดิม) |
| `system_paging.php` | `/system_paging` |  | Format::pagingOffset / paginator ของ Laravel |
| `system_status_function.php` | `/system_status_function` |  | app/Support/Legacy/{Status,Lookup,Pricing,Jail,Blocklist,LegacyLog} |

### 3rd-party (assets — ไม่เสิร์ฟผ่าน Laravel) (5 ไฟล์)

- `assets/ckeditor/**` (2 ไฟล์)
- `assets/plugins/**` (3 ไฟล์)

### 3rd-party (PHPExcel → ใช้ไลบรารีใหม่ในกลุ่ม reports) (189 ไฟล์)

- `assets/phpexcel/**` (189 ไฟล์)

## 3. สรุปจำนวน

| กลุ่ม | RewriteRule | ไฟล์ PHP |
|---|---|---|
| tracking | 16 | 37 |
| shop | 18 | 64 |
| order | 13 | 63 |
| payment | 19 | 51 |
| balance | 5 | 20 |
| money-move | 19 | 30 |
| points | 12 | 27 |
| lot | 5 | 43 |
| warehouse | 13 | 38 |
| users | 14 | 42 |
| messaging | 9 | 38 |
| reports | 10 | 36 |
| settings | 10 | 30 |
| foundation | 1 | 21 |
| 3rd-party (assets — ไม่เสิร์ฟผ่าน Laravel) | 0 | 5 |
| 3rd-party (PHPExcel → ใช้ไลบรารีใหม่ในกลุ่ม reports) | 0 | 189 |
| foundation (nginx/infra) | 7 | 0 |

ไม่มีรายการที่ยังไม่มีเจ้าของ (UNASSIGNED = 0)
