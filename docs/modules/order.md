# กลุ่ม order — รายงานการพอร์ต

ไฟล์เดิม: `_order/*` (11), `_admin/_order/*` (40), `_admin/_taobao/*` (12) · RewriteRule 13 ข้อ
โค้ดใหม่: `routes/modules/order.php`, `app/Http/Controllers/Order/{OrderController, Admin/AdminOrderController, Admin/AdminOrderDetailController, Admin/AdminTaobaoController}.php`,
`app/Domain/Order/*` (query/คำนวณยอด/ตารางรายการสินค้า/อีเมล/PHP 7.4), `resources/views/order/**`, `tests/Feature/Order/OrderRoutesTest.php`
worktree: `/home/admin/laravel/jl-wt/order` (branch `mod/order`, port 8203) · ไม่มีการแก้ฐานร่วม (foundation)

## 1. URL ที่พอร์ต

### หน้า (RewriteRule)

| URL เดิม | ไฟล์เดิม | route ใหม่ | สิทธิ์ |
|---|---|---|---|
| `admin/dashboard` | _admin/_order/index.php | `GET /admin/dashboard` → AdminOrderController@index | admin_order |
| `order` | _order/index.php | `GET /order` → OrderController@index | order |
| `order/{id}` | _order/form.php | `GET /order/{id}` → OrderController@form | order + เป็นเจ้าของใบ |
| `order/{id}/{div}` | _order/form.php | `GET /order/{id}/{div}` | order + เจ้าของ |
| `order/status/{n}` | _order/index.php | `GET /order/status/{status}` | order |
| `admin/order` | _admin/_order/index.php | `GET /admin/order` | admin_order |
| `admin/order/{id}` | _admin/_order/form.php | `GET /admin/order/{id}` | admin_order |
| `admin/order/{id}/{div}` | _admin/_order/form.php | `GET /admin/order/{id}/{div}` | admin_order |
| `admin/order/status/{n}` | _admin/_order/index.php | `GET /admin/order/status/{status}` | admin_order |
| `admin/taobao` | _admin/_taobao/index.php | `GET /admin/taobao` | admin_taobao |
| `admin/taobao/create` | _admin/_taobao/form.php (id=0) | `GET /admin/taobao/create` | admin_taobao |
| `admin/taobao/{id}` | _admin/_taobao/form.php | `GET /admin/taobao/{id}` | admin_taobao |
| `admin/taobao/status/{n}` | _admin/_taobao/index.php | `GET /admin/taobao/status/{status}` | admin_taobao |

### ajax / form post (path ไฟล์ตัด .php — POST + `legacy.ajax` ทุกตัว ยกเว้นที่ระบุ)

| ไฟล์เดิม | สิทธิ์ใหม่ | หมายเหตุ |
|---|---|---|
| `_order/grid`, `load_paging`, `load_total_order`, `save_payment`, `save_quote`, `save_quote_tracking`, `save_remark` | legacy.auth + ตรวจ `order` ใน controller | เดิม `exit()` เงียบเมื่อไม่มีสิทธิ์ → ตอบว่างเหมือนเดิม; ทุก query กรอง `user` = ผู้ล็อกอิน |
| `_order/delete` | order | |
| `_order/print` | legacy.auth (GET, ไม่ใช่ ajax) | หน้าพิมพ์ + print_header เดิม |
| `_admin/_order/grid` | legacy.auth + ตรวจ `admin_order` ใน controller | ไม่มีสิทธิ์ = ตอบว่าง |
| `_admin/_order/{load_paging, delete, save_approved, save_finish, save_lock, save_remark, save_quote_admin, save_vat, load_detail, load_calculate_cashback, load_color_form, load_remark_product, load_remark_shop, save_price, save_shipping, save_cashback, save_cashback_cancel, save_color, save_craft, save_qc, save_remark_product, save_remark_shop, save_product_type, save_order_number, save_tracking, save_quote, save_valid_track, save_add_new_track, save_remove_track}` | admin_order | ตรงกับ getPermission เดิม |
| `_admin/_order/load_total_order` | admin_order | **เดิมไม่ตรวจสิทธิ์** |
| `_admin/_order/save_amount` | admin_order | **เดิมไม่ตรวจสิทธิ์และไม่ตรวจล็อกอิน** |
| `_admin/_order/email_{approved,cancel,cashback}` | admin_order (GET/POST, ตอบว่าง) | เดิมเป็นไฟล์ที่ `include` จาก save_*; เรียกตรงได้โดยไม่ล็อกอิน และ `extract($_GET)` ทำให้ `?email_id=` ส่งอีเมลจริงได้ (ดูข้อ 3) → ใหม่ไม่ทำอะไร ตัวส่งจริงอยู่ใน `OrderMailer` |
| `_admin/_taobao/grid` | legacy.auth + ตรวจ `admin_taobao` | ไม่มีสิทธิ์ = ตอบว่าง |
| `_admin/_taobao/{save, save_remark, save_cancel}` | admin_taobao | |
| `_admin/_taobao/save_approved` | legacy.auth + ตรวจ `admin_taobao` ใน controller | เดิมแจ้งเตือนด้วยชื่อโมดูล `admin_shop_cashback` — คงข้อความเดิม |
| `_admin/_taobao/rollback` | admin_alipay | ตามไฟล์เดิม |
| `_admin/_taobao/{load_calculator, load_new_line}` | taobao | ตามไฟล์เดิม (สิทธิ์ลูกค้า) |
| `_admin/_taobao/email_{approved,cancel}` | admin_taobao (ตอบว่าง) | เหมือน email ของ order |

### ไม่พอร์ต (ตั้งใจ)

| ไฟล์ | เหตุผล |
|---|---|
| `_admin/_order/get_notrack.php`, `get_nowarehouse.php`, `get_shop.php` | ไม่ใช่โค้ด PHP — เป็นข้อความ SQL ที่เก็บไว้ (เรียกตรงได้ = แสดง SQL ออกมา) ไม่มี JS/ไฟล์ใดเรียก; ตรรกะเดียวกันอยู่ใน `OrderQueries` |

## 2. ต่างจากเดิมโดยตั้งใจ

- **escape ค่าจาก DB/ผู้ใช้** ทุกที่ (เดิม echo ดิบ) ด้วย `Html::h()` = htmlspecialchars แบบไม่ encode entity ซ้ำ (ชื่อสินค้าที่มี `&amp;` อยู่แล้วยังแสดงเหมือนเดิม); ค่าใน string JS (`div` ของ URL, page, onclick ของ cashback_form, เลขออเดอร์ใน save_add_new_track) escape แบบ JS ก่อน
  - ผลต่าง HTML ที่เหลือจากการเทียบ = เฉพาะตัวอักษร `<>"'&` ในข้อมูล
- **LIKE ค้นหาตามตัวอักษร**: `%` `_` ที่ผู้ใช้พิมพ์ไม่เป็น wildcard แล้ว (เดิมต่อ string)
- **N+1 → query คงที่**: grid (จำนวนร้าน/ผู้ล็อก/getPrefix ต่อแถว, ราคาต่อแถวของ taobao), ตารางสินค้าในใบ (tracking ต่อร้าน, balance คืนเงินต่อสินค้า, product_type ต่อร้าน), save_craft/save_qc (UPDATE tracking ทีละแถว)
  - เช่น load_detail ใบ 33 รายการ 10 ร้าน: เดิม ~100+ query → ใหม่ 7–10 query
- **derived table ทั้งตาราง** (นับ tracking ที่ไม่มีเลข/ไม่เข้าโกดัง ทุกใบในระบบ ~3–4 วินาที/ครั้ง) → นับเฉพาะ 10 แถวของหน้า; ตอนกรองสถานะ 3/5/6 ใช้ correlated sub-query ใน HAVING (ผลเท่ากัน: derived = NULL ⇔ correlated = 0)
- **cache จำนวนแถว** (`load_paging`, `load_total_order`): เดิม Redis ภายนอก 10 นาที → `Cache` ของ Laravel 10 นาที (key = SQL + ค่า bind); ไม่เขียนค่า `-` ชั่วคราวแบบเดิม (เดิม request ที่มาพร้อมกันจะเห็น "(-)")
- **transaction + ล็อกแถว** ในงานที่เขียนหลายตาราง: จ่ายเงิน, ยกเลิกใบ (คืนเงิน), คืนเงินรายการ, ยกเลิก/rollback เถาเปา, คำนวณยอด/VAT
- **การกดซ้ำ/ยิงพร้อมกัน**: ยกเลิกใบ (admin), ยกเลิกรายการเถาเปา, rollback คืนเงินได้ครั้งเดียว (เดิมอ่านสถานะก่อนแล้วค่อย UPDATE = คืนเงินซ้ำได้)
- **ข้อมูลขยะ**: save_cashback / save_add_new_track เมื่อไม่พบรายการ เดิม INSERT แถว balance/tracking ที่ไม่มีเจ้าของ → ไม่เขียนอะไร; save_cashback_cancel เมื่อไม่พบรายการ เดิม `UPDATE balance ... WHERE ref_id_2 = ''` (= 0) → ข้าม
- `_admin/_order/save_remove_track` เดิม echo คำสั่ง SQL กลับไป (JS ไม่ใช้) → ตอบว่าง
- **อีเมล**: `OrderMailer` (Mail ของ Laravel) — `legacy.external.enabled=false` บังคับ mailer `log` เสมอ (dev ไม่ส่งจริง); LINE/push เดิมเป็นฟังก์ชันว่าง → ไม่ส่ง; template header/center/footer คัดข้อความคงที่ของไฟล์เดิม (รวม `<?php ... ?>` ที่ติดไปในอีเมลเดิม)
- หน้าไม่มีสิทธิ์/ไม่พบใบ: layout ครบ + ErrorBox (เดิม `exit()` กลางหน้า HTML ขาด) — เหมือนกลุ่มอื่น
- `_order/print`: ไม่มีคอลัมน์ `bill_product.run_id` (เดิมแสดงว่าง) — แสดงว่างแบบเดิม

### บั๊กเงิน/ตัวกรองที่แก้ตามที่เจ้าของอนุมัติ (2026-10-18, รายงานข้อ 14 "ให้ถูกต้องตามสามัญสำนึก" — เฉพาะฝั่ง Laravel)

- `_order/load_paging` (ลูกค้า) สถานะ 5/6: เดิม `HAVING no_track = 0` กับ derived table (ค่าเป็น NULL) → ไม่มีหน้า paging ทั้งที่ grid มีข้อมูล → ใช้ HAVING เดียวกับ grid (`OrderQueries::applyShipStatus` ไม่มีโหมด "never" แล้ว)
- grid เถาเปาแอดมิน ตัวกรอง "ยกเลิก" (`-1`): เดิม `HAVING wait = 0 and complete = 0` กับ derived table → ไม่มีแถวผ่าน → ใช้ correlated COUNT (0 แทน NULL) = ใบที่ไม่มีรายการรอ/สำเร็จเหลือ
- `_admin/_taobao/save_cancel`: เฉพาะรายการสถานะ 1 (= ปุ่มในหน้า; เดิมยกเลิก+คืนเงินรายการที่จ่ายร้านแล้วได้) ล็อกแถวรายการ + ใบ + user
  - คืนใน **สกุลที่ลูกค้าจ่ายจริง** (`App\Domain\Order\TaobaoRefund`): ตอนสร้างใบ ราคาหยวนตัดจากกระเป๋าหยวนก่อน (`usecash_cn`) ที่เหลือ × rate + ค่าบริการ ตัดจากบาท
    → คืนหยวนเท่าที่ใบนี้ยังถูกตัดหยวนค้างอยู่ ที่เหลือคืนเป็นบาท × rate ของใบ (แถวใหม่ detail `…, รายการสั่งซื้อ : <order> (คืนเป็นเงินบาท)`); เดิมคืนเป็นหยวนเสมอ
  - ค่าบริการ: `taobao.charge` = ค่าบริการ **ต่อ 1 ออเดอร์** (`_taobao/save` เก็บ `charge_taobao` ไม่ใช่ยอดรวม) และแต่ละรายการคือ 1 ออเดอร์ → คืน 1 × charge ต่อรายการถูกต้องอยู่แล้ว (รายงานเดิมเข้าใจว่าเป็นค่าของทั้งใบ)
    ที่แก้คือคืนได้เฉพาะรายการที่ยังรอ (ไม่คืนซ้ำ/ไม่คืนหลังจ่ายร้านแล้ว) และยอดคืนรวมทุกสกุลไม่เกินที่ใบนี้ยังถูกตัดอยู่
  - แถวคืนเงิน (balance/balance_cn) มี `ref_id_2` = id ของ taobao_detail (เดิม NULL) เพื่อให้ rollback หักคืนตรงรายการ — โค้ดอื่นที่อ่าน ref_id_2 กรอง prefix ของ order เท่านั้น ไม่กระทบ
  - อีเมลแจ้งยกเลิกแสดงยอดที่คืนจริง (บาท = ค่าบริการ + ส่วนที่คืนเป็นบาท, หยวน)
- `_admin/_taobao/rollback` (-1 → 1): เดิมหัก **บาท** rate × price + ค่าบริการ ทั้งที่ตอนยกเลิกคืนเป็นหยวน (และหักค่าบริการแม้ไม่ได้คืน), ไม่ตรวจยอด
  → หักคืนตามแถวคืนเงินของรายการนั้น (ref_id_2) ในสกุลเดียวกับที่คืน; รายการที่ยกเลิกจากเว็บเดิม (ไม่มี ref_id_2) = เว็บเดิมคืนราคาเป็นหยวน + ค่าบริการเป็นบาท → หักแบบนั้น;
  ยอดคงเหลือ (บาทหรือหยวน) ไม่พอ = ปฏิเสธ; 2 → 1 ไม่แตะเงินเหมือนเดิม
- `_admin/_taobao/save_approved`: เฉพาะสถานะ 1 (เดิมอนุมัติรายการที่ยกเลิกแล้วได้ → rollback 2 → 1 ไม่หักเงิน → ยกเลิกซ้ำ = คืนเงินซ้ำ)
- `_admin/_order/save_approved`: เฉพาะสถานะ 0 (เดิมพลิกใบที่จ่ายแล้ว 2/3 กลับเป็น 1 = ลูกค้าจ่ายซ้ำได้ และเปิดใบที่ยกเลิกแล้ว) ล็อกแถว
- `_admin/_order/delete` ใบที่จ่ายแล้ว: เดิมคืนเท่ากับ balance แถวแรก (แถวชำระเงิน) ไม่หักรายการที่คืนไปแล้ว (`save_cashback`) = คืนเกินที่จ่าย → คืนเท่ายอดสุทธิที่ใบนี้ยังถูกตัดอยู่ (ล็อก user)
- `_admin/_order/save_finish` `prefix = 11` ตายตัว: เจตนาของโค้ดเดิม = id ของ prefix "order" (แถวชำระเงินที่ `_order/save_payment` เขียนใช้ `getPrefixID('order')`, และ 11 = `order` ในตาราง prefix ทั้ง bench/production)
  → อ่านจาก `OrderLookups::prefixId('order')` (ผลเท่าเดิมตอนนี้ แต่ไม่พังถ้า id เปลี่ยน)
- test: `tests/Feature/Money/OrderDbTest.php` (admin_jl_bench + rollback, opt-in `JL_BENCH_DB_TESTS=1`), `MoneyRulesTest::test_taobao_refund_uses_the_currency_actually_paid`, `OrderRoutesTest` (paging 5/6)

### พฤติกรรมเดิมที่คงไว้ (แจ้งเจ้าของตัดสินใจ)

- `load_paging` ของลูกค้าค้น `user.code OR id` (grid ค้นแค่ id); ของแอดมินค้น `admin_quote` เพิ่ม — จำนวนหน้าอาจไม่ตรง grid — คงไว้
- admin ยกเลิกใบ (`delete`) ยังทำได้ทุกสถานะที่ไม่ใช่ -1 (UI แสดงปุ่มเฉพาะ < 2) — แต่คืนเงินไม่เกินที่ถูกตัดแล้ว
- `bill_product_on_tracking.user` ของ tracking ที่แอดมินเพิ่ม = id แอดมิน (ไม่ใช่ลูกค้า)
- `load_detail`: คอลัมน์น้ำหนัก/ขนาด/ราคา/ประเภทสินค้าของ tracking ไม่ได้ select ในไฟล์เดิม → แสดง `.0 / 0.0000 / 0.00 / ว่าง` เสมอ
- หน้า form ลูกค้าอ้าง `$data_head` ที่ไม่มี → product_type ของ type 0 เสมอ และแสดงจำนวน/ราคาแบบ "ยังไม่จ่าย" เสมอ; print ใช้ `$item['price_shipping']` ที่ไม่มีคอลัมน์ (setting `newordershippingprice` ไม่มี) → ค่าส่งจีน 0 ในยอดรวมหน้าพิมพ์
- แอดมินเห็น `taobao_password` ของลูกค้าเป็นข้อความธรรมดา (ข้อมูลรหัสผ่านเก็บแบบ plaintext ใน DB)
- **ขึ้นกับ foundation**: Laravel `TrimStrings` ยัง trim ค่า POST (เช่น หมายเหตุ "abc␠" → "abc") — commit `d1d1ac8` ของ `mod/tracking` (foundation: ถอด TrimStrings/ConvertEmptyStringsToNull) แก้ให้ตรงเดิม เมื่อ merge แล้วกลุ่มนี้ได้ผลเหมือนเดิมทันที (ไม่ได้แก้ซ้ำใน branch นี้)

## 3. ช่องโหว่เดิมที่เจอ (แก้แล้ว)

| ไฟล์เดิม | ช่องโหว่ | แก้ |
|---|---|---|
| ทุกไฟล์ (63) | SQL injection: ต่อ `$_POST`/`$_GET` เข้า SQL ตรง (เช่น `txtsearch` ของ grid, `id`, `value`, `ref`) | query builder + bind ทั้งหมด |
| `_order/save_payment.php` | **IDOR**: ไม่ตรวจเจ้าของใบ — ลูกค้าคนใดก็ "จ่าย" ใบของคนอื่นได้ (หักเงินเจ้าของใบ, พลิกสถานะเป็นจ่ายแล้ว) | กรอง `user` = ผู้ล็อกอิน |
| `_order/save_payment.php` | จ่ายได้ทุกสถานะที่ไม่ใช่ 2 (ใบที่ยกเลิก/ยังไม่อนุมัติ/ปิดแล้ว ถูกพลิกเป็น "ชำระแล้ว" + หักเงิน) | จ่ายได้เฉพาะ status 1 (ตามปุ่มในหน้า) |
| `_order/delete.php` | ลูกค้ายกเลิกใบได้ทุกสถานะด้วย POST ตรง (ใบที่จ่ายแล้ว/แอดมินล็อก — ไม่มีการคืนเงิน) | เฉพาะ status 0 และไม่ถูกล็อก (ตามปุ่มในหน้า) |
| `_admin/_order/save_amount.php` | ไม่ตรวจล็อกอิน/สิทธิ์ — ใครก็แก้จำนวนสินค้าและยอดรวมใบสั่งซื้อได้ | admin_order |
| `_admin/_order/load_total_order.php` | ไม่ตรวจสิทธิ์ (รั่วจำนวนใบทั้งระบบ) | admin_order |
| `_admin/_order/email_*.php`, `_admin/_taobao/email_*.php` | เรียกตรงได้โดยไม่ตรวจอะไร — `GET ?email_id=<id>&ids=<id>` (ผ่าน `extract($_GET)`) ส่งอีเมล/LINE แจ้งลูกค้าจริงโดยไม่ต้องล็อกอิน | admin_order / admin_taobao, ไม่ทำอะไร |
| `_admin/_order/delete.php`, `_admin/_taobao/save_cancel.php`, `rollback.php` | race: กดซ้ำ/ยิงพร้อมกัน = คืนเงิน/หักเงินซ้ำ | transaction + ล็อกแถว + พลิกสถานะแบบ atomic |
| `_order/form.php`, `_admin/_order/form.php` | XSS ผ่าน URL: `var freagment = '<?php echo $div ?>'` (strip_tags ไม่กัน `'`) | escape แบบ JS |
| grid/form/print ทุกตัว | stored XSS: ชื่อสินค้า/ร้าน/หมายเหตุ/tracking/quote/cancel_remark ฯลฯ echo ดิบ (ลูกค้าเขียนเองได้ → แอดมินเปิดดู) | escape |
| grid ทุกตัว | reflected XSS: ค่า `txtsearch`/`page` ถูก echo กลับใน value/JS | escape |
| `_admin/_order/save_remove_track.php` | echo SQL กลับ | ตอบว่าง |
| ทุก POST | ไม่มี CSRF | `LegacyCsrf` (Origin/Referer หรือ XHR) — JS เดิมใช้ได้ (ทดสอบ origin อื่น = 403) |
| `system_status_function.php` (readRedis) | รหัส Redis ภายนอกฝังในโค้ดเดิม | ไม่ใช้/ไม่คัดลอก — ใช้ Cache ของ Laravel |

### ช่องโหว่ที่พบจากการ review (แก้แล้วใน branch นี้)

| ที่ | ปัญหา | แก้ |
|---|---|---|
| `load_detail` (ปุ่ม `cashback_form`) และ `save_add_new_track` (ค่า `order`) | ค่าที่ฝังใน string JS ใน `onclick=`/`onblur=` ใช้ `Html::h()` ซึ่ง **ไม่ encode entity ซ้ำ** → ค่าใน DB/POST อย่าง `1&#39;);alert(1);//` (amount, amount_before, price, cashback_remark) ถูก browser ถอดเป็น `'` ก่อนรัน JS = stored XSS ในหน้าแอดมิน | escape JS แล้ว `htmlspecialchars` แบบ encode ซ้ำเสมอ (`Html::jsAttr()` / ใน renderer) |
| grid ทั้ง 3 ตัว (`page`) | `fn_public = "loadAjaxGrid('..', '<page>')"` (string `"..."` ที่ถูก eval ภายหลัง) และ `onchange="...'<page>'..."` ของ grid แอดมิน ใช้ `Alerts::js()` ที่ไม่ escape `"` และไม่รองรับการ eval ชั้นที่สอง → `page=1"+alert(1)+"` หรือ `1');alert(1);//` รัน JS ได้ | `Html::jsNested()` (escape 2 ชั้น) / `Html::jsAttr()` |
| ลิงก์สินค้า/รูป (`url`, `main_image`, `size`, `color`) | `href` จากค่าที่ลูกค้ากรอก ไม่กัน `javascript:` (และ `javascript&#58;` ผ่าน `h()` ได้) | `Html::url()` → `#` เมื่อ scheme เป็น javascript/vbscript/data (ข้อมูลใน bench ไม่มีแถวแบบนี้ = ผลเท่าเดิม) |
| ข้อความ SuccessBox (`id` ใน save_payment / delete / save_approved / save_finish) | swal `html:true` → `id` ที่ส่งมาเป็น HTML | `e()` ก่อน `Alerts::js()` |
| `_admin/_order/save_cashback` | กดซ้ำพร้อมกัน: ทั้งสอง request "ลบรายการคืนเงินเดิม" แล้ว INSERT คนละแถว → คืนเงินซ้ำ (เดิมก็เป็น) | ล็อกแถว `bill_product_detail` (FOR UPDATE) ใน transaction — ทดสอบยิงพร้อมกัน 2 ครั้ง เหลือแถวคืนเงินที่ใช้งาน 1 แถว |

ช่องโหว่เดิมเพิ่มเติม (อ่านจากโค้ด ไม่ได้ยิงเว็บเดิม): `_admin/_order/email_*.php`, `_admin/_taobao/email_*.php` ไม่ตรวจล็อกอิน และ `system_config.php` ทำ `extract($_GET)` → `GET /_admin/_order/email_cancel?email_id=<ใบใดก็ได้>` (และ `email_approved`, `email_cashback`, `_taobao/email_*?email_id=&ids=`) **ส่งอีเมล/LINE แจ้งลูกค้าจริงได้โดยไม่ต้องล็อกอิน** (แจ้งยกเลิก/อนุมัติปลอม) — ไม่ใช่ "เรียกตรงแล้วไม่มี id" อย่างที่เขียนไว้เดิม; ใหม่ = ต้องมีสิทธิ์แอดมินและไม่ทำอะไร

## 4. การทดสอบ

- เทียบ HTML จริงกับเว็บเดิม (:8101) ด้วย session เดียวกัน (ลูกค้า 137, เจ้าของใบอื่นๆ, แอดมิน zz_fdn_admin) หลัง normalize href/ช่องว่าง:
  - grid ลูกค้า 7 แบบ (ทุกสถานะ/ship/ค้นหา/page ว่าง), grid แอดมิน 6 แบบ, grid เถาเปา 6 แบบ — **ตรงกันทุกตัว** (ต่างเฉพาะ escape เมื่อค้นด้วย `'"<`)
  - หน้า order ลูกค้า 10 ใบ, print 5 ใบ, load_detail แอดมิน 8 ใบ (หลายร้าน/คืนเงิน/VAT/ร้านชื่อว่าง/ไม่ซื้อร้านนี้ -2), form แอดมิน 6 ใบ (รวมใบที่ไม่มี), form เถาเปา 5 ใบ + create, load_new_line, load_calculator, load_color_form/load_remark_*/json (byte ตรงกัน) — **ตรงกัน** ยกเว้น escape
  - ข้อมูลทดสอบ `zz_` (ใบสั่งซื้อ/รายการ/tracking/balance/เถาเปาของ ZZ_FDN_CUST) ครอบคลุม ร้าน NULL, จำนวน "abc", URL รูปใน size/color, ไฟล์แนบ, คืนเงิน, VAT, tracking หลายแถว — เทียบทุกสถานะ 0/1/2/-1 ตรงกัน แล้วลบทิ้งหมดแล้ว (ลบตาม id)
- จำนวน (load_total_order ทุก ref, load_paging) เทียบกับ SQL เดิมรันตรงใน MySQL (อ่านอย่างเดียว) — ตรงกัน
  (ไม่ได้เรียก load_paging/load_total_order ของเว็บเดิม เพราะต่อ Redis ภายนอกด้วยรหัสจริง)
- endpoint ที่เขียนข้อมูล: ทดสอบเฉพาะฝั่งใหม่ แล้วตรวจแถวใน DB กับสูตรเดิม — **ไม่ได้ยิง endpoint เขียนของ :8101** เพราะ `mysqlquerys()` เดิม log ทุก non-SELECT ไปที่ `/home/admin/web/portal.jlcargoth.com/public_html/logs` (production) และ save_approved/delete/save_cashback/save_cancel เดิมส่งอีเมลจริงผ่าน Mailgun
  - ยอดรวม/VAT หลัง save_amount/save_price/save_shipping/save_vat = สูตร SQL เดิม; จ่ายเงินซ้ำไม่หักซ้ำ; ยกเลิกซ้ำคืนเงินครั้งเดียว; IDOR (ลูกค้าแก้/จ่าย/ยกเลิกใบคนอื่น) ไม่เปลี่ยนข้อมูล; ไม่ล็อกอิน = 302 /login; origin อื่น = 403
  - อีเมลออกที่ log mailer (ตรวจใน `storage/logs/laravel.log`)
- `php8.4 vendor/bin/phpunit` ผ่าน (รวม RouteSecurityTest, RouteModulesTest, tests/Feature/Order)

## 5. bench (`bin/bench`, c=1, หลัง `artisan optimize`; grid เดิมช้ามาก จึงวัด 6 ครั้ง)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/order` (หน้า list ลูกค้า) n=20 | 336 | 35 |
| `_order/grid` ลูกค้า 137 (17,744 ใบ) n=6 | 2,712 | 38 |
| `_admin/_order/grid` n=6 | 2,759 | 44 |
| `_admin/_order/load_detail` ใบ 33 รายการ/10 ร้าน n=20 | 317 | 78 |
| `/admin/order/{id}` n=20 | 312 | 39 |
| `/order/{id}` (ใบเดียวกัน, เจ้าของ) n=20 | 553 | 55 |
| `_admin/_taobao/grid` n=20 | 259 | 42 |

- นับจำนวนครั้งแรก (ยังไม่ cache) ของสถานะ 3/5/6 ทั้งระบบ ยังหนัก (~3–5 วินาที เท่าเดิม) แล้ว cache 10 นาทีเหมือนเดิม — ถ้าต้องเร็วกว่านี้ควรมีคอลัมน์สรุปหรือ index `tracking(is_delete, tracking)` (ข้อเสนอ ไม่ได้สร้าง)
