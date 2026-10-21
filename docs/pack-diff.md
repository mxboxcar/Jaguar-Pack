# pack เทียบ SR: ไฟล์เดิมต่อกลุ่ม

สร้างด้วย `python3 -I tools/pack-diff.py > docs/pack-diff.md` (เว็บจริงของ pack / SR / jl, `diff -wB`)
`same` = เหมือน SR → ใช้โค้ดที่พอร์ตแล้วได้เลย · `diff N (jl M)` = ต่าง SR N บรรทัด, ต่าง jl M บรรทัด (jl น้อยกว่า = ใช้ jl เป็นแม่แบบ) ·
`SR-only` = pack ไม่มีไฟล์นี้ → ลบ route · `pack-only` = ต้องพอร์ตใหม่

| กลุ่ม | same | diff | SR-only |
|---|---:|---:|---:|
| tracking | 23 | 14 | 4 |
| shop | 21 | 12 | 10 |
| order | 48 | 12 | 11 |
| payment | 18 | 27 | 2 |
| balance | 18 | 2 | 0 |
| money-move | 17 | 13 | 0 |
| points | 23 | 4 | 0 |
| lot | 33 | 12 | 1 |
| warehouse | 26 | 10 | 2 |
| users | 30 | 11 | 1 |
| messaging | 27 | 8 | 0 |
| reports | 19 | 16 | 9 |
| settings | 27 | 4 | 0 |
| foundation | 16 | 11 | 3 |

## tracking

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_check/index.php` | SR-only |
| `_admin/_check/save_clear.php` | SR-only |
| `_admin/_check/save_clearall.php` | SR-only |
| `_admin/_combine_track/index.php` | same |
| `_admin/_combine_track/load_tracking.php` | same |
| `_admin/_combine_track/save_combine.php` | diff 15 (jl 17) |
| `_admin/_lost_package/cancel.php` | same |
| `_admin/_lost_package/email_approved.php` | diff 21 (jl 22) |
| `_admin/_lost_package/email_cancel.php` | diff 24 (jl 24) |
| `_admin/_lost_package/form.php` | diff 14 (jl 14) |
| `_admin/_lost_package/grid.php` | diff 10 (jl 22) |
| `_admin/_lost_package/index.php` | same |
| `_admin/_lost_package/rollback.php` | same |
| `_admin/_lost_package/save_approved.php` | diff 15 (jl 15) |
| `_admin/_lost_package/save_remark.php` | same |
| `_admin/_tracking/delete.php` | same |
| `_admin/_tracking/form.php` | diff 17 (jl 46) |
| `_admin/_tracking/grid.php` | diff 72 (jl 47) |
| `_admin/_tracking/index.php` | same |
| `_admin/_tracking/load_paging.php` | SR-only |
| `_admin/_tracking/load_pricing.php` | same |
| `_admin/_tracking/load_product_type.php` | same |
| `_admin/_tracking/save.php` | diff 7 (jl 64) |
| `_admin/_tracking/save_lock.php` | same |
| `_lost_package/cancel.php` | same |
| `_lost_package/form.php` | same |
| `_lost_package/grid.php` | same |
| `_lost_package/index.php` | same |
| `_lost_package/load_order_number.php` | same |
| `_lost_package/load_upload.php` | same |
| `_lost_package/save.php` | same |
| `_lost_package/save_image.php` | same |
| `_lost_package/upload.php` | diff 20 (jl 20) |
| `_tracking/delete.php` | same |
| `_tracking/form.php` | diff 54 (jl 27) |
| `_tracking/grid.php` | diff 16 (jl 13) |
| `_tracking/index.php` | diff 2 (jl 10) |
| `_tracking/load_pricing.php` | same |
| `_tracking/load_product_type.php` | same |
| `_tracking/save.php` | diff 59 (jl 22) |
| `_tracking/save_quick_quote.php` | same |

## shop

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_mallstock/grid.php` | same |
| `_admin/_mallstock/index.php` | same |
| `_admin/_mallstock/load_amount.php` | same |
| `_admin/_mallstock/save_amount.php` | same |
| `_api/blockblob.php` | diff 4 (jl 4) |
| `_api/blockblob_ajax.php` | diff 24 (jl 9) |
| `_api/crawl_guard.php` | same |
| `_api/get_order_number_v2.php` | diff 6 (jl 6) |
| `_api/get_order_number_v3.php` | SR-only |
| `_api/image_shrink.php` | same |
| `_api/load_product.php` | diff 13 (jl 2) |
| `_api/load_product_v2.php` | SR-only |
| `_api/load_product_v5.php` | same |
| `_api/load_product_v7.php` | SR-only |
| `_api/send_sms.php` | SR-only |
| `_api/submit_order_number_v2.php` | same |
| `_api/unban.php` | diff 10 (jl 4) |
| `_api/useragent.php` | same |
| `_cart/checkout.php` | diff 36 (jl 12) |
| `_cart/delete_cart.php` | same |
| `_cart/delete_carts.php` | same |
| `_cart/load_cart.php` | diff 1 (jl 19) |
| `_cart/load_choose_product.php` | diff 16 (jl 20) |
| `_cart/load_full_cart.php` | diff 15 (jl 20) |
| `_cart/product.php` | diff 68 (jl 2) |
| `_cart/save_amount.php` | same |
| `_cart/save_checkout.php` | diff 19 (jl 2) |
| `_cart/save_craft.php` | same |
| `_cart/save_product.php` | same |
| `_cart/save_qc.php` | same |
| `_cart/save_takeapic.php` | SR-only |
| `_findproduct/delete.php` | SR-only |
| `_findproduct/form.php` | SR-only |
| `_findproduct/grid.php` | SR-only |
| `_findproduct/index.php` | SR-only |
| `_findproduct/save.php` | SR-only |
| `_taobao/form.php` | same |
| `_taobao/grid.php` | same |
| `_taobao/index.php` | same |
| `_taobao/load_calculator.php` | same |
| `_taobao/load_new_line.php` | same |
| `_taobao/save.php` | diff 4 (jl 32) |
| `_taobao/save_remark.php` | same |

## order

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_order/delete.php` | same |
| `_admin/_order/delete_withcashback.php` | SR-only |
| `_admin/_order/email_approved.php` | diff 35 (jl 33) |
| `_admin/_order/email_cancel.php` | diff 24 (jl 24) |
| `_admin/_order/email_cashback.php` | diff 26 (jl 26) |
| `_admin/_order/form.php` | diff 160 (jl 39) |
| `_admin/_order/get_notrack.php` | same |
| `_admin/_order/get_nowarehouse.php` | same |
| `_admin/_order/get_shop.php` | same |
| `_admin/_order/grid.php` | diff 50 (jl 2) |
| `_admin/_order/index.php` | same |
| `_admin/_order/load_calculate_cashback.php` | same |
| `_admin/_order/load_changeuser.php` | SR-only |
| `_admin/_order/load_color_form.php` | same |
| `_admin/_order/load_detail.php` | diff 36 (jl 83) |
| `_admin/_order/load_remark_product.php` | same |
| `_admin/_order/load_remark_shop.php` | same |
| `_admin/_order/load_total_order.php` | diff 17 (jl 0) |
| `_admin/_order/save_add_new_track.php` | diff 4 (jl 3) |
| `_admin/_order/save_amount.php` | same |
| `_admin/_order/save_approved.php` | same |
| `_admin/_order/save_cashback.php` | same |
| `_admin/_order/save_cashback_cancel.php` | same |
| `_admin/_order/save_change_send_type.php` | same |
| `_admin/_order/save_changeuser.php` | SR-only |
| `_admin/_order/save_color.php` | same |
| `_admin/_order/save_craft.php` | same |
| `_admin/_order/save_finish.php` | diff 23 (jl 0) |
| `_admin/_order/save_lock.php` | same |
| `_admin/_order/save_newrate.php` | SR-only |
| `_admin/_order/save_order_number.php` | same |
| `_admin/_order/save_price.php` | same |
| `_admin/_order/save_product_type.php` | same |
| `_admin/_order/save_qc.php` | same |
| `_admin/_order/save_quote.php` | same |
| `_admin/_order/save_quote_admin.php` | same |
| `_admin/_order/save_remark.php` | same |
| `_admin/_order/save_remark_product.php` | same |
| `_admin/_order/save_remark_shop.php` | same |
| `_admin/_order/save_remove_track.php` | same |
| `_admin/_order/save_shipping.php` | same |
| `_admin/_order/save_takeapic.php` | SR-only |
| `_admin/_order/save_tracking.php` | same |
| `_admin/_order/save_valid_track.php` | same |
| `_admin/_printorder/form.php` | SR-only |
| `_admin/_printorder/grid.php` | SR-only |
| `_admin/_printorder/index.php` | SR-only |
| `_admin/_printorder/load_detail.php` | SR-only |
| `_admin/_printorder/print.php` | SR-only |
| `_admin/_printorder/save_print.php` | SR-only |
| `_admin/_taobao/email_approved.php` | diff 25 (jl 25) |
| `_admin/_taobao/email_cancel.php` | diff 30 (jl 30) |
| `_admin/_taobao/form.php` | same |
| `_admin/_taobao/grid.php` | same |
| `_admin/_taobao/index.php` | same |
| `_admin/_taobao/load_calculator.php` | same |
| `_admin/_taobao/load_new_line.php` | same |
| `_admin/_taobao/rollback.php` | same |
| `_admin/_taobao/save.php` | same |
| `_admin/_taobao/save_approved.php` | same |
| `_admin/_taobao/save_cancel.php` | same |
| `_admin/_taobao/save_remark.php` | same |
| `_order/delete.php` | same |
| `_order/form.php` | diff 55 (jl 28) |
| `_order/grid.php` | same |
| `_order/index.php` | same |
| `_order/load_total_order.php` | same |
| `_order/save_payment.php` | same |
| `_order/save_quote.php` | same |
| `_order/save_quote_tracking.php` | same |
| `_order/save_remark.php` | same |

## payment

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_alipay/cancel.php` | diff 5 (jl 9) |
| `_admin/_alipay/email_cancel.php` | diff 27 (jl 26) |
| `_admin/_alipay/email_success.php` | diff 24 (jl 24) |
| `_admin/_alipay/form.php` | diff 15 (jl 29) |
| `_admin/_alipay/grid.php` | diff 2 (jl 2) |
| `_admin/_alipay/index.php` | same |
| `_admin/_alipay/rollback.php` | same |
| `_admin/_alipay/save.php` | diff 11 (jl 0) |
| `_admin/_payment/create.php` | diff 8 (jl 8) |
| `_admin/_payment/create_form.php` | diff 26 (jl 20) |
| `_admin/_payment/email_approved.php` | diff 2 (jl 32) |
| `_admin/_payment/email_cancel.php` | diff 2 (jl 25) |
| `_admin/_payment/form.php` | diff 126 (jl 54) |
| `_admin/_payment/grid.php` | diff 16 (jl 7) |
| `_admin/_payment/index.php` | same |
| `_admin/_payment/load_autocomplete_user.php` | same |
| `_admin/_payment/load_create.php` | diff 2 (jl 69) |
| `_admin/_payment/load_select_lot.php` | same |
| `_admin/_payment/load_shipping_price.php` | same |
| `_admin/_payment/load_user_by_id.php` | same |
| `_admin/_payment/save_approved.php` | diff 8 (jl 0) |
| `_admin/_payment/save_create.php` | same |
| `_admin/_payment/save_delete.php` | diff 15 (jl 0) |
| `_admin/_payment/save_deletenopush.php` | same |
| `_admin/_payment/save_new_sender.php` | same |
| `_admin/_payment/save_submit_payment.php` | diff 11 (jl 12) |
| `_admin/_receipt/grid.php` | SR-only |
| `_admin/_receipt/index.php` | SR-only |
| `_alipay/cancel.php` | diff 54 (jl 50) |
| `_alipay/form.php` | diff 18 (jl 57) |
| `_alipay/grid.php` | same |
| `_alipay/index.php` | same |
| `_alipay/save.php` | same |
| `_payment/create.php` | diff 15 (jl 4) |
| `_payment/create_form.php` | diff 110 (jl 62) |
| `_payment/form.php` | diff 64 (jl 40) |
| `_payment/grid.php` | diff 11 (jl 2) |
| `_payment/index.php` | same |
| `_payment/load_create.php` | diff 18 (jl 14) |
| `_payment/load_create_sum.php` | same |
| `_payment/load_select_lot.php` | diff 36 (jl 0) |
| `_payment/load_shipping_price.php` | diff 103 (jl 56) |
| `_payment/save.php` | diff 5 (jl 5) |
| `_payment/save_create.php` | same |
| `_payment/save_delete.php` | same |
| `_payment/save_submit_payment.php` | diff 107 (jl 109) |
| `_payment/view_example.php` | same |

## balance

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_balance/grid.php` | same |
| `_admin/_balance/index.php` | same |
| `_admin/_balance/load_amount.php` | same |
| `_admin/_balance/save_amount.php` | same |
| `_admin/_balance_cn/grid.php` | same |
| `_admin/_balance_cn/index.php` | same |
| `_admin/_balance_cn/load_amount.php` | same |
| `_admin/_balance_cn/save_amount.php` | same |
| `_admin/_balance_company/grid.php` | diff 2 (jl 2) |
| `_admin/_balance_company/index.php` | same |
| `_admin/_balance_company/load_amount.php` | same |
| `_admin/_balance_company/save_amount.php` | same |
| `_balance/grid.php` | same |
| `_balance/index.php` | same |
| `_balance/load_withdraw.php` | same |
| `_balance/save_withdraw.php` | diff 33 (jl 43) |
| `_balance_cn/grid.php` | same |
| `_balance_cn/index.php` | same |
| `_balance_cn/load_withdraw.php` | same |
| `_balance_cn/save_withdraw.php` | same |

## money-move

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_refill/cancel.php` | same |
| `_admin/_refill/email_approved.php` | diff 21 (jl 21) |
| `_admin/_refill/email_cancel.php` | diff 24 (jl 25) |
| `_admin/_refill/form.php` | diff 9 (jl 0) |
| `_admin/_refill/grid.php` | diff 5 (jl 0) |
| `_admin/_refill/index.php` | same |
| `_admin/_refill/save.php` | same |
| `_admin/_refill/save_approved.php` | diff 2 (jl 3) |
| `_admin/_transfer/cancel.php` | diff 6 (jl 7) |
| `_admin/_transfer/email_cancel.php` | diff 24 (jl 24) |
| `_admin/_transfer/email_success.php` | diff 26 (jl 26) |
| `_admin/_transfer/form.php` | diff 4 (jl 42) |
| `_admin/_transfer/grid.php` | same |
| `_admin/_transfer/index.php` | same |
| `_admin/_transfer/rollback.php` | same |
| `_admin/_transfer/save.php` | diff 10 (jl 0) |
| `_admin/_withdrawcash/cancel.php` | diff 5 (jl 9) |
| `_admin/_withdrawcash/form.php` | same |
| `_admin/_withdrawcash/grid.php` | same |
| `_admin/_withdrawcash/index.php` | same |
| `_admin/_withdrawcash/save_approved.php` | same |
| `_refill/form.php` | same |
| `_refill/grid.php` | same |
| `_refill/index.php` | same |
| `_refill/save.php` | same |
| `_transfer/cancel.php` | diff 72 (jl 67) |
| `_transfer/form.php` | diff 19 (jl 60) |
| `_transfer/grid.php` | same |
| `_transfer/index.php` | same |
| `_transfer/save.php` | same |

## points

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_point/grid.php` | same |
| `_admin/_point/index.php` | same |
| `_admin/_shop_cashback/email_approved.php` | diff 26 (jl 26) |
| `_admin/_shop_cashback/email_cancel.php` | diff 26 (jl 26) |
| `_admin/_shop_cashback/form.php` | same |
| `_admin/_shop_cashback/grid.php` | same |
| `_admin/_shop_cashback/index.php` | same |
| `_admin/_shop_cashback/save.php` | same |
| `_admin/_shop_cashback/save_approved.php` | same |
| `_admin/_shop_cashback/save_cancel.php` | same |
| `_admin/_shop_cashback/save_remark.php` | same |
| `_admin/_withdrawpoint/cancel.php` | same |
| `_admin/_withdrawpoint/form.php` | same |
| `_admin/_withdrawpoint/grid.php` | same |
| `_admin/_withdrawpoint/index.php` | same |
| `_admin/_withdrawpoint/save_approved.php` | same |
| `_point/grid.php` | same |
| `_point/index.php` | diff 11 (jl 0) |
| `_point/load_withdraw.php` | same |
| `_point/save_withdraw.php` | diff 4 (jl 0) |
| `_shop_cashback/form.php` | same |
| `_shop_cashback/grid.php` | same |
| `_shop_cashback/index.php` | same |
| `_shop_cashback/load_calculator.php` | same |
| `_shop_cashback/load_new_line.php` | same |
| `_shop_cashback/save.php` | same |
| `_shop_cashback/save_remark.php` | same |

## lot

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_lot/cancel.php` | same |
| `_admin/_lot/cron_lot_mail.php` | same |
| `_admin/_lot/delete.php` | same |
| `_admin/_lot/email.php` | diff 6 (jl 2) |
| `_admin/_lot/email_lot.php` | same |
| `_admin/_lot/form.php` | diff 375 (jl 208) |
| `_admin/_lot/grid.php` | same |
| `_admin/_lot/index.php` | diff 3 (jl 82) |
| `_admin/_lot/load_autocomplete_tracking.php` | same |
| `_admin/_lot/load_autocomplete_user.php` | same |
| `_admin/_lot/load_image.php` | diff 12 |
| `_admin/_lot/load_lastest_lot.php` | same |
| `_admin/_lot/load_lot_detail.php` | diff 169 (jl 36) |
| `_admin/_lot/load_lot_memo.php` | same |
| `_admin/_lot/load_quickfile.php` | diff 9 |
| `_admin/_lot/load_ref_print.php` | diff 2 (jl 0) |
| `_admin/_lot/load_toptable.php` | SR-only |
| `_admin/_lot/load_tracking.php` | diff 3 (jl 33) |
| `_admin/_lot/load_unpaid.php` | same |
| `_admin/_lot/load_user_rate.php` | same |
| `_admin/_lot/print.php` | diff 8 (jl 2) |
| `_admin/_lot/print_receive.php` | same |
| `_admin/_lot/save.php` | diff 45 (jl 86) |
| `_admin/_lot/save_delete_lot_memo.php` | same |
| `_admin/_lot/save_file_lot.php` | same |
| `_admin/_lot/save_lot_memo.php` | same |
| `_admin/_lot/save_lot_name.php` | same |
| `_admin/_lot/save_lot_name_admin.php` | same |
| `_admin/_lot/save_lot_ship_amount.php` | same |
| `_admin/_lot/save_new_lot.php` | same |
| `_admin/_lot/save_print.php` | same |
| `_admin/_lot/save_quickfile.php` | diff 9 |
| `_admin/_lot/save_remove_tracking.php` | same |
| `_admin/_lot/save_status_lot.php` | same |
| `_admin/_lot/save_status_lot_date.php` | same |
| `_admin/_lot/save_status_reset_tracking.php` | same |
| `_admin/_lot/save_status_tracking.php` | same |
| `_admin/_lot/save_verified.php` | same |
| `_lot/_message/index.php` | same |
| `_lot/_message/load.php` | same |
| `_lot/_message/load_dept.php` | same |
| `_lot/_message/send.php` | same |
| `_lot/form.php` | diff 1 (jl 0) |
| `_lot/grid.php` | same |
| `_lot/index.php` | same |
| `_lot/load_lot_detail.php` | same |

## warehouse

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_goodsconfirm/cancel.php` | same |
| `_admin/_goodsconfirm/form.php` | same |
| `_admin/_goodsconfirm/grid.php` | same |
| `_admin/_goodsconfirm/index.php` | same |
| `_admin/_goodsconfirm/load_tracking.php` | same |
| `_admin/_goodsconfirm/load_tracking_by_id.php` | same |
| `_admin/_goodsconfirm/load_upload.php` | same |
| `_admin/_goodsconfirm/save.php` | same |
| `_admin/_goodsconfirm/save_approved.php` | same |
| `_admin/_goodsconfirm/save_image.php` | same |
| `_admin/_goodsconfirm/save_unapproved.php` | same |
| `_admin/_warehouse/form.php` | diff 188 (jl 188) |
| `_admin/_warehouse/grid.php` | diff 27 (jl 27) |
| `_admin/_warehouse/index.php` | same |
| `_admin/_warehouse/load_editshelf.php` | SR-only |
| `_admin/_warehouse/load_lot_detail.php` | diff 31 (jl 33) |
| `_admin/_warehouse/load_lot_detail_thai.php` | diff 28 (jl 28) |
| `_admin/_warehouse/load_tracking.php` | diff 39 (jl 39) |
| `_admin/_warehouse/load_tracking_edit.php` | diff 276 (jl 276) |
| `_admin/_warehouse/save.php` | same |
| `_admin/_warehouse/save_cancel_warehouse_calculate.php` | same |
| `_admin/_warehouse/save_editshelf.php` | SR-only |
| `_admin/_warehouse/save_lot_detail.php` | diff 577 (jl 266) |
| `_admin/_warehouse/save_new_warehouse.php` | same |
| `_admin/_warehouse/save_push_track.php` | diff 2 (jl 2) |
| `_admin/_warehouse/save_track_weight.php` | diff 21 (jl 21) |
| `_admin/_warehouse_section/delete.php` | diff 2 (jl 2) |
| `_admin/_warehouse_section/form.php` | same |
| `_admin/_warehouse_section/grid.php` | same |
| `_admin/_warehouse_section/index.php` | same |
| `_admin/_warehouse_section/save.php` | same |
| `_admin/_warehouse_section_move/grid.php` | same |
| `_admin/_warehouse_section_move/index.php` | same |
| `_goodsconfirm/form.php` | same |
| `_goodsconfirm/grid.php` | same |
| `_goodsconfirm/index.php` | same |
| `_goodsconfirm/load_tracking_by_id.php` | same |
| `_goodsconfirm/save_approved.php` | same |

## users

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_role/delete.php` | same |
| `_admin/_role/form.php` | same |
| `_admin/_role/grid.php` | same |
| `_admin/_role/index.php` | same |
| `_admin/_role/save.php` | same |
| `_admin/_user/btn_email_lost_password.php` | same |
| `_admin/_user/btn_pinreset.php` | same |
| `_admin/_user/btn_resetpassword.php` | same |
| `_admin/_user/btn_unbanned.php` | same |
| `_admin/_user/btn_viewpassword.php` | same |
| `_admin/_user/cancel.php` | same |
| `_admin/_user/delete.php` | same |
| `_admin/_user/email.php` | diff 2 (jl 2) |
| `_admin/_user/email_approved.php` | diff 2 (jl 4) |
| `_admin/_user/email_cancel.php` | same |
| `_admin/_user/form.php` | diff 8 (jl 400) |
| `_admin/_user/grid.php` | same |
| `_admin/_user/index.php` | same |
| `_admin/_user/load_role_detail.php` | same |
| `_admin/_user/load_user_type_detail.php` | same |
| `_admin/_user/rate_history.php` | same |
| `_admin/_user/rate_history_lib.php` | same |
| `_admin/_user/save.php` | diff 2 (jl 34) |
| `_admin/_user/save_approved.php` | same |
| `_admin/_user_type/delete.php` | same |
| `_admin/_user_type/form.php` | same |
| `_admin/_user_type/grid.php` | same |
| `_admin/_user_type/index.php` | same |
| `_admin/_user_type/save.php` | same |
| `_profile/index.php` | diff 13 (jl 6) |
| `_profile/load_role_detail.php` | same |
| `_profile/load_user_type_detail.php` | same |
| `_profile/pin.php` | same |
| `_profile/save.php` | diff 29 (jl 39) |
| `_register/email.php` | diff 12 (jl 19) |
| `_register/email_lost_password.php` | diff 2 (jl 2) |
| `_register/lost_password.php` | same |
| `_register/save.php` | diff 22 (jl 25) |
| `lost_password.php` | diff 105 (jl 104) |
| `password.php` | SR-only |
| `register.php` | diff 17 (jl 112) |
| `system_check_username.php` | same |

## messaging

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_message/deletechat.php` | same |
| `_admin/_message/index.php` | diff 10 (jl 4) |
| `_admin/_message/load.php` | diff 9 (jl 7) |
| `_admin/_message/load_dept.php` | same |
| `_admin/_message/load_user.php` | same |
| `_admin/_message/save_file.php` | same |
| `_admin/_message/send.php` | same |
| `_admin/_message/send_endconversation.php` | same |
| `_admin/_notice/cron_notice_mail.php` | same |
| `_admin/_notice/delete.php` | same |
| `_admin/_notice/email.php` | same |
| `_admin/_notice/email_notice.php` | same |
| `_admin/_notice/form.php` | same |
| `_admin/_notice/grid.php` | same |
| `_admin/_notice/index.php` | same |
| `_admin/_notice/save.php` | same |
| `_admin/_notice/save_isshow.php` | same |
| `_admin/_send/index.php` | same |
| `_admin/_send/index_self.php` | same |
| `_message/index.php` | diff 10 (jl 10) |
| `_message/load.php` | diff 8 (jl 8) |
| `_message/load_dept.php` | same |
| `_message/save_file.php` | same |
| `_message/send.php` | same |
| `_message/send_endconversation.php` | same |
| `_notice/form.php` | same |
| `_notice/grid.php` | same |
| `_notice/index.php` | same |
| `email_center.php` | same |
| `email_footer.php` | diff 2 (jl 10) |
| `email_header.php` | same |
| `notification.php` | diff 34 (jl 76) |
| `notifications.php` | same |
| `notify_counts.php` | diff 2 (jl 2) |
| `notify_token.php` | diff 2 (jl 2) |

## reports

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_dashboard/index.php` | diff 6 (jl 6) |
| `_admin/_export/calvat.php` | SR-only |
| `_admin/_export/email_cancel.php` | diff 24 (jl 23) |
| `_admin/_export/email_success.php` | diff 33 (jl 33) |
| `_admin/_export/form.php` | diff 88 (jl 56) |
| `_admin/_export/grid.php` | diff 23 (jl 11) |
| `_admin/_export/index.php` | same |
| `_admin/_export/print.php` | same |
| `_admin/_export/print_2.php` | diff 41 (jl 47) |
| `_admin/_export/print_3.php` | SR-only |
| `_admin/_export/print_receipt.php` | SR-only |
| `_admin/_export/printprice.php` | SR-only |
| `_admin/_export/receipt.php` | SR-only |
| `_admin/_export/receipt_1.php` | SR-only |
| `_admin/_export/receipt_2.php` | SR-only |
| `_admin/_export/save_approved.php` | same |
| `_admin/_export/save_delete.php` | same |
| `_admin/_export/save_receipt.php` | SR-only |
| `_admin/_log/grid.php` | same |
| `_admin/_log/index.php` | same |
| `_admin/_nobilling/index.php` | diff 13 (jl 0) |
| `_admin/_report/cashmove.php` | same |
| `_admin/_report/current_cash.php` | same |
| `_admin/_report/current_cash_cn.php` | same |
| `_admin/_report/else.php` | diff 61 (jl 0) |
| `_admin/_report/else_preorder_working.php` | diff 2 (jl 0) |
| `_admin/_report/file.php` | same |
| `_admin/_report/form.php` | diff 3 (jl 0) |
| `_admin/_report/grid.php` | diff 71 (jl 0) |
| `_admin/_report/index.php` | diff 82 (jl 0) |
| `_admin/_report/printtax.php` | SR-only |
| `_admin/_report/refill_5.php` | same |
| `_admin/_report/refill_6.php` | same |
| `_admin/_report/refill_7.php` | same |
| `_admin/_report/shipping.php` | same |
| `_dashboard/index.php` | diff 77 (jl 90) |
| `_export/form.php` | diff 64 (jl 82) |
| `_export/grid.php` | diff 11 (jl 2) |
| `_export/index.php` | same |
| `_export/save_approved.php` | same |
| `_export/save_delete.php` | same |
| `download_file.php` | diff 11 (jl 11) |
| `print_footer.php` | same |
| `print_header.php` | same |

## settings

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_exchange/cancel.php` | same |
| `_admin/_exchange/form.php` | same |
| `_admin/_exchange/grid.php` | same |
| `_admin/_exchange/index.php` | same |
| `_admin/_exchange/save_approved.php` | same |
| `_admin/_product_type/delete.php` | same |
| `_admin/_product_type/form.php` | same |
| `_admin/_product_type/grid.php` | same |
| `_admin/_product_type/index.php` | same |
| `_admin/_product_type/save.php` | same |
| `_admin/_setting/index.php` | diff 2 (jl 0) |
| `_admin/_setting/save.php` | same |
| `_admin/_shipping_type/delete.php` | same |
| `_admin/_shipping_type/delete_cal.php` | same |
| `_admin/_shipping_type/form.php` | same |
| `_admin/_shipping_type/grid.php` | same |
| `_admin/_shipping_type/index.php` | same |
| `_admin/_shipping_type/load_cal_1.php` | same |
| `_admin/_shipping_type/load_cal_2.php` | same |
| `_admin/_shipping_type/save.php` | same |
| `_admin/_shipping_type/save_listno.php` | same |
| `_admin/_shipping_type/save_new_price_by_cubic.php` | same |
| `_admin/_shipping_type/save_new_price_by_kg.php` | same |
| `get_price.php` | same |
| `get_resource.php` | diff 2 (jl 0) |
| `index_close.php` | same |
| `load_quick_search.php` | same |
| `resource.php` | same |
| `system_ckupload.php` | diff 35 |
| `system_upload.php` | diff 44 (jl 44) |
| `system_upload_2.php` | same |

## foundation

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_mall/delete.php` | same |
| `_admin/_mall/form.php` | same |
| `_admin/_mall/grid.php` | same |
| `_admin/_mall/index.php` | same |
| `_admin/_mall/load_detail.php` | same |
| `_admin/_mall/load_sizecolor.php` | same |
| `_admin/_mall/load_stock.php` | same |
| `_admin/_mall/save.php` | same |
| `_admin/_mall/save_sizecolor.php` | same |
| `assets/validate/validationengine.php` | SR-only |
| `content.php` | diff 37 (jl 44) |
| `index.php` | diff 169 (jl 53) |
| `login.php` | diff 167 (jl 145) |
| `menu.php` | diff 53 (jl 66) |
| `menu_mobile.php` | diff 26 (jl 25) |
| `resource/cn.php` | diff 8 (jl 16) |
| `resource/en.php` | diff 4 (jl 9) |
| `resource/th.php` | diff 35 (jl 51) |
| `sessionmaster/database.class.php` | SR-only |
| `sessionmaster/mysql.sessions.php` | SR-only |
| `system_blocklist.php` | same |
| `system_change_language.php` | same |
| `system_check_ajax.php` | same |
| `system_check_login.php` | same |
| `system_checkuser.php` | same |
| `system_config.php` | diff 120 (jl 722) |
| `system_db.php` | diff 21 (jl 25) |
| `system_dedup_guard.php` | same |
| `system_paging.php` | same |
| `system_status_function.php` | diff 383 (jl 634) |

## pack-only (ไม่มีใน SR)

| ไฟล์ | jl มีไหม |
|---|---|
| `_admin/_coupon/delete.php` | - |
| `_admin/_coupon/form.php` | - |
| `_admin/_coupon/grid.php` | - |
| `_admin/_coupon/index.php` | - |
| `_admin/_coupon/save.php` | - |
| `_admin/_order/load_paging.php` | มี |
| `_admin/_payment/save_vat.php` | มี |
| `_cart/product_bk.php` | - |
| `_payment/create_form_bk.php` | - |
| `_payment/load_discoutcode.php` | - |
| `system_lang_guard.php` | - |
| `track_external.php` | - |

## schema: admin_sr_bench → admin_pack_bench

- ตารางที่ SR มีแต่ pack ไม่มี: `find_product`, `find_product_image`, `running`, `tracking_check`, `warehouse`, `warehouse_detail`, `warehouse_section`, `warehouse_section_detail`
- ตารางที่ pack มีแต่ SR ไม่มี: `coupon`

| ตาราง | คอลัมน์ | SR | pack |
|---|---|---|---|
| `bill_payment` | `discount` | **ไม่มี** | decimal(12,2) |
| `bill_payment` | `is_vat` | **ไม่มี** | decimal(12,2) |
| `bill_payment` | `receipt_address` | varchar(225) | **ไม่มี** |
| `bill_payment` | `receipt_date` | datetime | **ไม่มี** |
| `bill_payment` | `receipt_id` | varchar(50) | **ไม่มี** |
| `bill_payment` | `receipt_is_vat` | int | **ไม่มี** |
| `bill_payment` | `receipt_name` | varchar(225) | **ไม่มี** |
| `bill_payment` | `receipt_tax` | varchar(50) | **ไม่มี** |
| `bill_payment` | `receipt_total` | decimal(10,2) | **ไม่มี** |
| `bill_payment` | `shipv` | **ไม่มี** | decimal(12,2) |
| `bill_payment` | `shipvat` | **ไม่มี** | decimal(12,2) |
| `bill_payment` | `shipvat_before` | **ไม่มี** | decimal(12,2) |
| `bill_payment` | `v` | **ไม่มี** | decimal(12,2) |
| `bill_payment` | `vat` | **ไม่มี** | decimal(12,2) |
| `bill_payment` | `vat_before` | **ไม่มี** | decimal(12,2) |
| `bill_payment_detail` | `date_section` | datetime | **ไม่มี** |
| `bill_payment_detail` | `is_takeapic` | int | **ไม่มี** |
| `bill_payment_detail` | `subuser` | varchar(225) | **ไม่มี** |
| `bill_product` | `is_print` | int | **ไม่มี** |
| `bill_product` | `run_id` | varchar(50) | **ไม่มี** |
| `bill_product_detail` | `is_takeapic` | int | **ไม่มี** |
| `cart_detail` | `is_takeapic` | int | **ไม่มี** |
| `product_type` | `thaiword` | varchar(225) | **ไม่มี** |
| `refill` | `approved_user` | int | **ไม่มี** |
| `tracking` | `date_section` | datetime | **ไม่มี** |
| `tracking` | `is_takeapic` | int | **ไม่มี** |
| `tracking` | `subuser` | varchar(225) | **ไม่มี** |

