# SR เทียบ jl: ไฟล์เดิมต่อกลุ่ม

สร้างด้วย `python3 -I tools/sr-diff.py > docs/sr-diff.md` — `same` = ไฟล์เดิมของ SR เหมือน jl (ไม่นับช่องว่าง) → route/controller ของ jl ใช้ได้เลย (ยังต้องเทียบผลจริงเพราะ schema/helper ต่าง),
`diff N` = ต่าง N บรรทัด → ต้องปรับตาม SR, `SR-only` = ต้องพอร์ตใหม่, `JL-only` = มีใน app ที่ fork มาแต่ SR ไม่มี → ลบ route หรือคงไว้ตามที่ SR เดิมทำ

| กลุ่ม | same | diff | SR-only | JL-only |
|---|---:|---:|---:|---:|
| tracking | 25 | 12 | 4 | 0 |
| shop | 21 | 16 | 6 | 4 |
| order | 44 | 15 | 12 | 4 |
| payment | 18 | 27 | 2 | 6 |
| balance | 18 | 2 | 0 | 0 |
| money-move | 12 | 18 | 0 | 0 |
| points | 22 | 5 | 0 | 0 |
| lot | 31 | 10 | 5 | 2 |
| warehouse | 34 | 4 | 0 | 0 |
| users | 27 | 15 | 0 | 0 |
| messaging | 24 | 11 | 0 | 0 |
| reports | 18 | 17 | 9 | 1 |
| settings | 26 | 3 | 2 | 0 |
| foundation | 9 | 21 | 0 | 3 |

## tracking

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_check/index.php` | SR-only |
| `_admin/_check/save_clear.php` | SR-only |
| `_admin/_check/save_clearall.php` | SR-only |
| `_admin/_combine_track/index.php` | same |
| `_admin/_combine_track/load_tracking.php` | same |
| `_admin/_combine_track/save_combine.php` | diff 20 |
| `_admin/_lost_package/cancel.php` | same |
| `_admin/_lost_package/email_approved.php` | diff 2 |
| `_admin/_lost_package/email_cancel.php` | diff 2 |
| `_admin/_lost_package/form.php` | same |
| `_admin/_lost_package/grid.php` | diff 28 |
| `_admin/_lost_package/index.php` | same |
| `_admin/_lost_package/rollback.php` | same |
| `_admin/_lost_package/save_approved.php` | same |
| `_admin/_lost_package/save_remark.php` | SR-only |
| `_admin/_tracking/delete.php` | same |
| `_admin/_tracking/form.php` | diff 47 |
| `_admin/_tracking/grid.php` | diff 44 |
| `_admin/_tracking/index.php` | same |
| `_admin/_tracking/load_paging.php` | diff 60 |
| `_admin/_tracking/load_pricing.php` | same |
| `_admin/_tracking/load_product_type.php` | same |
| `_admin/_tracking/save.php` | diff 69 |
| `_admin/_tracking/save_lock.php` | same |
| `_lost_package/cancel.php` | same |
| `_lost_package/form.php` | same |
| `_lost_package/grid.php` | same |
| `_lost_package/index.php` | same |
| `_lost_package/load_order_number.php` | same |
| `_lost_package/load_upload.php` | same |
| `_lost_package/save.php` | same |
| `_lost_package/save_image.php` | same |
| `_lost_package/upload.php` | same |
| `_tracking/delete.php` | same |
| `_tracking/form.php` | diff 80 |
| `_tracking/grid.php` | diff 30 |
| `_tracking/index.php` | diff 10 |
| `_tracking/load_pricing.php` | same |
| `_tracking/load_product_type.php` | same |
| `_tracking/save.php` | diff 49 |
| `_tracking/save_quick_quote.php` | same |

## shop

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_mallstock/grid.php` | diff 69 |
| `_admin/_mallstock/index.php` | diff 10 |
| `_admin/_mallstock/load_amount.php` | same |
| `_admin/_mallstock/save_amount.php` | same |
| `_api/blockblob.php` | diff 4 |
| `_api/blockblob_ajax.php` | diff 21 |
| `_api/crawl_guard.php` | same |
| `_api/get_order_number_v2.php` | same |
| `_api/get_order_number_v3.php` | diff 3 |
| `_api/image_shrink.php` | same |
| `_api/load_product.php` | diff 13 |
| `_api/load_product_v2.php` | same |
| `_api/load_product_v5.php` | same |
| `_api/load_product_v7.php` | same |
| `_api/send_sms.php` | diff 2 |
| `_api/submit_order_number_v2.php` | same |
| `_api/unban.php` | diff 10 |
| `_api/useragent.php` | same |
| `_cart/checkout.php` | diff 25 |
| `_cart/delete_cart.php` | same |
| `_cart/delete_carts.php` | same |
| `_cart/load_cart.php` | diff 17 |
| `_cart/load_choose_product.php` | diff 4 |
| `_cart/load_full_cart.php` | diff 23 |
| `_cart/product.php` | diff 64 |
| `_cart/save_amount.php` | same |
| `_cart/save_checkout.php` | diff 20 |
| `_cart/save_craft.php` | same |
| `_cart/save_product.php` | diff 2 |
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
| `_taobao/save.php` | diff 37 |
| `_taobao/save_remark.php` | same |
| `_api/updateImagePrefix.php` | JL-only |
| `_cart/cart.php` | JL-only |
| `_cart/product_2.php` | JL-only |
| `_taobao/delete.php` | JL-only |

## order

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_order/delete.php` | same |
| `_admin/_order/delete_withcashback.php` | SR-only |
| `_admin/_order/email_approved.php` | diff 8 |
| `_admin/_order/email_cancel.php` | diff 2 |
| `_admin/_order/email_cashback.php` | diff 2 |
| `_admin/_order/form.php` | diff 200 |
| `_admin/_order/get_notrack.php` | same |
| `_admin/_order/get_nowarehouse.php` | same |
| `_admin/_order/get_shop.php` | same |
| `_admin/_order/grid.php` | diff 52 |
| `_admin/_order/index.php` | same |
| `_admin/_order/load_calculate_cashback.php` | same |
| `_admin/_order/load_changeuser.php` | SR-only |
| `_admin/_order/load_color_form.php` | same |
| `_admin/_order/load_detail.php` | diff 111 |
| `_admin/_order/load_remark_product.php` | same |
| `_admin/_order/load_remark_shop.php` | same |
| `_admin/_order/load_total_order.php` | diff 14 |
| `_admin/_order/save_add_new_track.php` | same |
| `_admin/_order/save_amount.php` | same |
| `_admin/_order/save_approved.php` | same |
| `_admin/_order/save_cashback.php` | same |
| `_admin/_order/save_cashback_cancel.php` | same |
| `_admin/_order/save_change_send_type.php` | SR-only |
| `_admin/_order/save_changeuser.php` | SR-only |
| `_admin/_order/save_color.php` | same |
| `_admin/_order/save_craft.php` | same |
| `_admin/_order/save_finish.php` | diff 23 |
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
| `_admin/_order/save_shipping.php` | diff 76 |
| `_admin/_order/save_takeapic.php` | SR-only |
| `_admin/_order/save_tracking.php` | same |
| `_admin/_order/save_valid_track.php` | same |
| `_admin/_printorder/form.php` | SR-only |
| `_admin/_printorder/grid.php` | SR-only |
| `_admin/_printorder/index.php` | SR-only |
| `_admin/_printorder/load_detail.php` | SR-only |
| `_admin/_printorder/print.php` | SR-only |
| `_admin/_printorder/save_print.php` | SR-only |
| `_admin/_taobao/email_approved.php` | diff 2 |
| `_admin/_taobao/email_cancel.php` | diff 2 |
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
| `_order/form.php` | diff 75 |
| `_order/grid.php` | diff 20 |
| `_order/index.php` | same |
| `_order/load_total_order.php` | diff 15 |
| `_order/save_payment.php` | diff 32 |
| `_order/save_quote.php` | same |
| `_order/save_quote_tracking.php` | same |
| `_order/save_remark.php` | same |
| `_admin/_order/load_paging.php` | JL-only |
| `_admin/_order/save_vat.php` | JL-only |
| `_order/load_paging.php` | JL-only |
| `_order/print.php` | JL-only |

## payment

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_alipay/cancel.php` | diff 6 |
| `_admin/_alipay/email_cancel.php` | diff 2 |
| `_admin/_alipay/email_success.php` | diff 2 |
| `_admin/_alipay/form.php` | diff 29 |
| `_admin/_alipay/grid.php` | same |
| `_admin/_alipay/index.php` | same |
| `_admin/_alipay/rollback.php` | same |
| `_admin/_alipay/save.php` | diff 11 |
| `_admin/_payment/create.php` | diff 8 |
| `_admin/_payment/create_form.php` | diff 22 |
| `_admin/_payment/email_approved.php` | diff 32 |
| `_admin/_payment/email_cancel.php` | diff 25 |
| `_admin/_payment/form.php` | diff 106 |
| `_admin/_payment/grid.php` | diff 9 |
| `_admin/_payment/index.php` | same |
| `_admin/_payment/load_autocomplete_user.php` | same |
| `_admin/_payment/load_create.php` | diff 71 |
| `_admin/_payment/load_select_lot.php` | diff 1 |
| `_admin/_payment/load_shipping_price.php` | same |
| `_admin/_payment/load_user_by_id.php` | same |
| `_admin/_payment/save_approved.php` | diff 8 |
| `_admin/_payment/save_create.php` | same |
| `_admin/_payment/save_delete.php` | diff 15 |
| `_admin/_payment/save_deletenopush.php` | same |
| `_admin/_payment/save_new_sender.php` | same |
| `_admin/_payment/save_submit_payment.php` | diff 12 |
| `_admin/_receipt/grid.php` | SR-only |
| `_admin/_receipt/index.php` | SR-only |
| `_alipay/cancel.php` | diff 8 |
| `_alipay/form.php` | diff 39 |
| `_alipay/grid.php` | same |
| `_alipay/index.php` | same |
| `_alipay/save.php` | diff 62 |
| `_payment/create.php` | diff 15 |
| `_payment/create_form.php` | diff 60 |
| `_payment/form.php` | diff 45 |
| `_payment/grid.php` | diff 9 |
| `_payment/index.php` | same |
| `_payment/load_create.php` | diff 30 |
| `_payment/load_create_sum.php` | same |
| `_payment/load_select_lot.php` | diff 37 |
| `_payment/load_shipping_price.php` | diff 80 |
| `_payment/save.php` | same |
| `_payment/save_create.php` | same |
| `_payment/save_delete.php` | same |
| `_payment/save_submit_payment.php` | diff 134 |
| `_payment/view_example.php` | same |
| `_admin/_payment/save_vat.php` | JL-only |
| `_payment/load_receipt_address.php` | JL-only |
| `_payment/load_vat.php` | JL-only |
| `_payment/save_create_receiptaddress.php` | JL-only |
| `_payment/save_deleteaddress.php` | JL-only |
| `_payment/save_deletereceiptaddress.php` | JL-only |

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
| `_admin/_balance_company/grid.php` | same |
| `_admin/_balance_company/index.php` | same |
| `_admin/_balance_company/load_amount.php` | same |
| `_admin/_balance_company/save_amount.php` | same |
| `_balance/grid.php` | diff 5 |
| `_balance/index.php` | same |
| `_balance/load_withdraw.php` | same |
| `_balance/save_withdraw.php` | diff 66 |
| `_balance_cn/grid.php` | same |
| `_balance_cn/index.php` | same |
| `_balance_cn/load_withdraw.php` | same |
| `_balance_cn/save_withdraw.php` | same |

## money-move

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_refill/cancel.php` | diff 9 |
| `_admin/_refill/email_approved.php` | diff 2 |
| `_admin/_refill/email_cancel.php` | diff 2 |
| `_admin/_refill/form.php` | diff 9 |
| `_admin/_refill/grid.php` | diff 9 |
| `_admin/_refill/index.php` | diff 8 |
| `_admin/_refill/save.php` | same |
| `_admin/_refill/save_approved.php` | diff 5 |
| `_admin/_transfer/cancel.php` | diff 3 |
| `_admin/_transfer/email_cancel.php` | diff 2 |
| `_admin/_transfer/email_success.php` | diff 2 |
| `_admin/_transfer/form.php` | diff 45 |
| `_admin/_transfer/grid.php` | same |
| `_admin/_transfer/index.php` | same |
| `_admin/_transfer/rollback.php` | same |
| `_admin/_transfer/save.php` | diff 10 |
| `_admin/_withdrawcash/cancel.php` | diff 4 |
| `_admin/_withdrawcash/form.php` | same |
| `_admin/_withdrawcash/grid.php` | same |
| `_admin/_withdrawcash/index.php` | diff 2 |
| `_admin/_withdrawcash/save_approved.php` | diff 4 |
| `_refill/form.php` | same |
| `_refill/grid.php` | same |
| `_refill/index.php` | same |
| `_refill/save.php` | same |
| `_transfer/cancel.php` | diff 6 |
| `_transfer/form.php` | diff 41 |
| `_transfer/grid.php` | same |
| `_transfer/index.php` | same |
| `_transfer/save.php` | diff 61 |

## points

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_point/grid.php` | same |
| `_admin/_point/index.php` | same |
| `_admin/_shop_cashback/email_approved.php` | diff 2 |
| `_admin/_shop_cashback/email_cancel.php` | diff 2 |
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
| `_point/index.php` | diff 11 |
| `_point/load_withdraw.php` | same |
| `_point/save_withdraw.php` | diff 4 |
| `_shop_cashback/form.php` | same |
| `_shop_cashback/grid.php` | same |
| `_shop_cashback/index.php` | same |
| `_shop_cashback/load_calculator.php` | same |
| `_shop_cashback/load_new_line.php` | same |
| `_shop_cashback/save.php` | diff 32 |
| `_shop_cashback/save_remark.php` | same |

## lot

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_lot/cancel.php` | same |
| `_admin/_lot/cron_lot_mail.php` | same |
| `_admin/_lot/delete.php` | same |
| `_admin/_lot/email.php` | diff 6 |
| `_admin/_lot/email_lot.php` | same |
| `_admin/_lot/form.php` | diff 399 |
| `_admin/_lot/grid.php` | diff 26 |
| `_admin/_lot/index.php` | diff 79 |
| `_admin/_lot/load_autocomplete_tracking.php` | same |
| `_admin/_lot/load_autocomplete_user.php` | same |
| `_admin/_lot/load_image.php` | SR-only |
| `_admin/_lot/load_lastest_lot.php` | same |
| `_admin/_lot/load_lot_detail.php` | diff 143 |
| `_admin/_lot/load_lot_memo.php` | same |
| `_admin/_lot/load_quickfile.php` | SR-only |
| `_admin/_lot/load_ref_print.php` | diff 2 |
| `_admin/_lot/load_toptable.php` | SR-only |
| `_admin/_lot/load_tracking.php` | diff 35 |
| `_admin/_lot/load_unpaid.php` | SR-only |
| `_admin/_lot/load_user_rate.php` | same |
| `_admin/_lot/print.php` | diff 10 |
| `_admin/_lot/print_receive.php` | same |
| `_admin/_lot/save.php` | diff 94 |
| `_admin/_lot/save_delete_lot_memo.php` | same |
| `_admin/_lot/save_file_lot.php` | same |
| `_admin/_lot/save_lot_memo.php` | same |
| `_admin/_lot/save_lot_name.php` | same |
| `_admin/_lot/save_lot_name_admin.php` | same |
| `_admin/_lot/save_lot_ship_amount.php` | same |
| `_admin/_lot/save_new_lot.php` | same |
| `_admin/_lot/save_print.php` | same |
| `_admin/_lot/save_quickfile.php` | SR-only |
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
| `_lot/form.php` | diff 1 |
| `_lot/grid.php` | same |
| `_lot/index.php` | same |
| `_lot/load_lot_detail.php` | same |
| `_admin/_lot/load_check_tracking.php` | JL-only |
| `_admin/_lot/print_all.php` | JL-only |

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
| `_admin/_goodsconfirm/save.php` | diff 3 |
| `_admin/_goodsconfirm/save_approved.php` | same |
| `_admin/_goodsconfirm/save_image.php` | same |
| `_admin/_goodsconfirm/save_unapproved.php` | same |
| `_admin/_warehouse/form.php` | same |
| `_admin/_warehouse/grid.php` | same |
| `_admin/_warehouse/index.php` | same |
| `_admin/_warehouse/load_editshelf.php` | same |
| `_admin/_warehouse/load_lot_detail.php` | same |
| `_admin/_warehouse/load_lot_detail_thai.php` | same |
| `_admin/_warehouse/load_tracking.php` | same |
| `_admin/_warehouse/load_tracking_edit.php` | same |
| `_admin/_warehouse/save.php` | same |
| `_admin/_warehouse/save_cancel_warehouse_calculate.php` | same |
| `_admin/_warehouse/save_editshelf.php` | same |
| `_admin/_warehouse/save_lot_detail.php` | diff 357 |
| `_admin/_warehouse/save_new_warehouse.php` | same |
| `_admin/_warehouse/save_push_track.php` | same |
| `_admin/_warehouse/save_track_weight.php` | same |
| `_admin/_warehouse_section/delete.php` | same |
| `_admin/_warehouse_section/form.php` | same |
| `_admin/_warehouse_section/grid.php` | same |
| `_admin/_warehouse_section/index.php` | same |
| `_admin/_warehouse_section/save.php` | same |
| `_admin/_warehouse_section_move/grid.php` | same |
| `_admin/_warehouse_section_move/index.php` | same |
| `_goodsconfirm/form.php` | diff 5 |
| `_goodsconfirm/grid.php` | diff 12 |
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
| `_admin/_user/email.php` | diff 2 |
| `_admin/_user/email_approved.php` | diff 4 |
| `_admin/_user/email_cancel.php` | same |
| `_admin/_user/form.php` | diff 408 |
| `_admin/_user/grid.php` | diff 32 |
| `_admin/_user/index.php` | same |
| `_admin/_user/load_role_detail.php` | same |
| `_admin/_user/load_user_type_detail.php` | diff 83 |
| `_admin/_user/rate_history.php` | same |
| `_admin/_user/rate_history_lib.php` | same |
| `_admin/_user/save.php` | diff 36 |
| `_admin/_user/save_approved.php` | diff 13 |
| `_admin/_user_type/delete.php` | same |
| `_admin/_user_type/form.php` | same |
| `_admin/_user_type/grid.php` | same |
| `_admin/_user_type/index.php` | same |
| `_admin/_user_type/save.php` | same |
| `_profile/index.php` | diff 16 |
| `_profile/load_role_detail.php` | same |
| `_profile/load_user_type_detail.php` | same |
| `_profile/pin.php` | same |
| `_profile/save.php` | diff 42 |
| `_register/email.php` | diff 19 |
| `_register/email_lost_password.php` | diff 2 |
| `_register/lost_password.php` | same |
| `_register/save.php` | diff 40 |
| `lost_password.php` | diff 7 |
| `password.php` | diff 9 |
| `register.php` | diff 119 |
| `system_check_username.php` | same |

## messaging

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_message/deletechat.php` | same |
| `_admin/_message/index.php` | diff 12 |
| `_admin/_message/load.php` | diff 4 |
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
| `_admin/_send/index.php` | diff 4 |
| `_admin/_send/index_self.php` | diff 4 |
| `_message/index.php` | same |
| `_message/load.php` | diff 2 |
| `_message/load_dept.php` | same |
| `_message/save_file.php` | same |
| `_message/send.php` | same |
| `_message/send_endconversation.php` | same |
| `_notice/form.php` | same |
| `_notice/grid.php` | same |
| `_notice/index.php` | same |
| `email_center.php` | diff 2 |
| `email_footer.php` | diff 10 |
| `email_header.php` | diff 2 |
| `notification.php` | diff 82 |
| `notifications.php` | same |
| `notify_counts.php` | diff 2 |
| `notify_token.php` | diff 2 |

## reports

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_dashboard/index.php` | diff 6 |
| `_admin/_export/calvat.php` | SR-only |
| `_admin/_export/email_cancel.php` | diff 2 |
| `_admin/_export/email_success.php` | diff 2 |
| `_admin/_export/form.php` | diff 54 |
| `_admin/_export/grid.php` | diff 28 |
| `_admin/_export/index.php` | same |
| `_admin/_export/print.php` | same |
| `_admin/_export/print_2.php` | diff 47 |
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
| `_admin/_nobilling/index.php` | diff 13 |
| `_admin/_report/cashmove.php` | same |
| `_admin/_report/current_cash.php` | same |
| `_admin/_report/current_cash_cn.php` | same |
| `_admin/_report/else.php` | diff 61 |
| `_admin/_report/else_preorder_working.php` | diff 2 |
| `_admin/_report/file.php` | same |
| `_admin/_report/form.php` | diff 3 |
| `_admin/_report/grid.php` | diff 71 |
| `_admin/_report/index.php` | diff 82 |
| `_admin/_report/printtax.php` | SR-only |
| `_admin/_report/refill_5.php` | same |
| `_admin/_report/refill_6.php` | same |
| `_admin/_report/refill_7.php` | same |
| `_admin/_report/shipping.php` | same |
| `_dashboard/index.php` | diff 46 |
| `_export/form.php` | diff 87 |
| `_export/grid.php` | diff 9 |
| `_export/index.php` | same |
| `_export/save_approved.php` | same |
| `_export/save_delete.php` | same |
| `download_file.php` | diff 5 |
| `print_footer.php` | same |
| `print_header.php` | diff 73 |
| `_dashboard/index_bk.php` | JL-only |

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
| `_admin/_setting/index.php` | diff 2 |
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
| `get_resource.php` | diff 2 |
| `index_close.php` | diff 2 |
| `load_quick_search.php` | same |
| `resource.php` | SR-only |
| `system_ckupload.php` | SR-only |
| `system_upload.php` | same |
| `system_upload_2.php` | same |

## foundation

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_mall/delete.php` | diff 5 |
| `_admin/_mall/form.php` | diff 38 |
| `_admin/_mall/grid.php` | diff 35 |
| `_admin/_mall/index.php` | diff 5 |
| `_admin/_mall/load_detail.php` | diff 2 |
| `_admin/_mall/load_sizecolor.php` | diff 10 |
| `_admin/_mall/load_stock.php` | diff 2 |
| `_admin/_mall/save.php` | diff 17 |
| `_admin/_mall/save_sizecolor.php` | diff 5 |
| `assets/validate/validationengine.php` | same |
| `content.php` | diff 41 |
| `index.php` | diff 198 |
| `login.php` | diff 34 |
| `menu.php` | diff 53 |
| `menu_mobile.php` | diff 17 |
| `resource/cn.php` | diff 22 |
| `resource/en.php` | diff 9 |
| `resource/th.php` | diff 62 |
| `sessionmaster/database.class.php` | diff 7 |
| `sessionmaster/mysql.sessions.php` | same |
| `system_blocklist.php` | same |
| `system_change_language.php` | same |
| `system_check_ajax.php` | same |
| `system_check_login.php` | same |
| `system_checkuser.php` | same |
| `system_config.php` | diff 644 |
| `system_db.php` | diff 20 |
| `system_dedup_guard.php` | same |
| `system_paging.php` | same |
| `system_status_function.php` | diff 764 |
| `_admin/_mall/save_delsizecolor.php` | JL-only |
| `_admin/_mall/save_isshow.php` | JL-only |
| `assets/validate/validationengine.php` | JL-only |

## schema: admin_jl_bench → admin_sr_bench

- ตารางที่ jl มีแต่ SR ไม่มี: `_user`, `mall`, `mall_cart`, `mall_category`, `mall_detail`, `mall_stock`
- ตารางที่ SR มีแต่ jl ไม่มี: `find_product`, `find_product_image`, `running`, `tracking_check`, `tracking_image`, `user_setting`

| ตาราง | คอลัมน์ | jl | SR |
|---|---|---|---|
| `bill_alipay` | `usecash_cn` | **ไม่มี** | decimal(20,2) |
| `bill_payment` | `is_vat` | int | **ไม่มี** |
| `bill_payment` | `receipt_address` | **ไม่มี** | varchar(225) |
| `bill_payment` | `receipt_date` | **ไม่มี** | datetime |
| `bill_payment` | `receipt_id` | **ไม่มี** | varchar(50) |
| `bill_payment` | `receipt_is_vat` | **ไม่มี** | int |
| `bill_payment` | `receipt_name` | **ไม่มี** | varchar(225) |
| `bill_payment` | `receipt_tax` | **ไม่มี** | varchar(50) |
| `bill_payment` | `receipt_total` | **ไม่มี** | decimal(10,2) |
| `bill_payment` | `shipv` | decimal(12,2) | **ไม่มี** |
| `bill_payment` | `shipvat` | decimal(12,2) | **ไม่มี** |
| `bill_payment` | `shipvat_before` | decimal(12,2) | **ไม่มี** |
| `bill_payment` | `v` | decimal(12,2) | **ไม่มี** |
| `bill_payment` | `vat` | decimal(12,2) | **ไม่มี** |
| `bill_payment` | `vat_before` | decimal(12,2) | **ไม่มี** |
| `bill_payment_detail` | `amount` | tinyint | int |
| `bill_payment_detail` | `checked_by` | int | **ไม่มี** |
| `bill_payment_detail` | `is_checked` | int | **ไม่มี** |
| `bill_payment_detail` | `is_takeapic` | **ไม่มี** | int |
| `bill_payment_detail` | `subuser` | **ไม่มี** | varchar(225) |
| `bill_product` | `is_print` | **ไม่มี** | int |
| `bill_product` | `is_vat` | int | **ไม่มี** |
| `bill_product` | `run_id` | **ไม่มี** | varchar(50) |
| `bill_product` | `total_vat` | decimal(20,2) | **ไม่มี** |
| `bill_product` | `vat` | decimal(20,2) | **ไม่มี** |
| `bill_product_detail` | `is_takeapic` | **ไม่มี** | int |
| `bill_transfer` | `usecash_cn` | **ไม่มี** | decimal(20,2) |
| `cart_detail` | `is_takeapic` | **ไม่มี** | int |
| `lost_package` | `admin_quote` | **ไม่มี** | text |
| `product_type` | `thaiword` | **ไม่มี** | varchar(225) |
| `refill` | `approved_user` | **ไม่มี** | int |
| `tracking` | `amount` | tinyint | int |
| `tracking` | `checked_by` | int | **ไม่มี** |
| `tracking` | `header` | text | int |
| `tracking` | `is_checked` | int | **ไม่มี** |
| `tracking` | `is_takeapic` | **ไม่มี** | int |
| `tracking` | `subuser` | **ไม่มี** | varchar(225) |
| `user` | `address` | mediumtext | text |
| `user` | `cancel_remark` | mediumtext | text |
| `user` | `commission` | int | **ไม่มี** |
| `user` | `diff` | decimal(10,2) | decimal(20,2) |
| `user` | `line_uid` | varchar(225) | varchar(100) |
| `user` | `lineid` | varchar(45) | varchar(100) |
| `user` | `phone2` | varchar(45) | varchar(50) |
| `user` | `wechatid` | **ไม่มี** | varchar(100) |
| `warehouse_section_detail` | `user_agent` | **ไม่มี** | int |

