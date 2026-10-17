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
| `_admin/_order/email_{approved,cancel,cashback}` | admin_order (GET/POST, ตอบว่าง) | เดิมเป็นไฟล์ที่ `include` จาก save_*; เรียกตรงไม่มี `$email_id` = ไม่ได้ทำอะไรที่ใช้ได้ → ตัวส่งจริงอยู่ใน `OrderMailer` |
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

### พฤติกรรมเดิมที่คงไว้ (แจ้งเจ้าของตัดสินใจ)

- `load_paging` ของลูกค้า สถานะ 5/6 เดิมเขียน `HAVING no_track = 0` กับ derived table (ค่าเป็น NULL) → **ไม่มีหน้า paging** ทั้งที่ grid มีข้อมูล (grid/ปุ่มจำนวนใช้ `is null` ถูกต้อง) — คงไว้
- ตัวกรอง "ยกเลิก" ของ grid เถาเปา (`-1`) เดิม `HAVING wait = 0 and complete = 0` กับ derived table → ไม่มีแถวผ่านเลย — คงไว้
- `load_paging` ของลูกค้าค้น `user.code OR id` (grid ค้นแค่ id); ของแอดมินค้น `admin_quote` เพิ่ม — จำนวนหน้าอาจไม่ตรง grid — คงไว้
- rollback เถาเปา: ตอนยกเลิกคืนเป็น **หยวน** (balance_cn) + ค่าบริการ แต่ rollback หักคืนจาก **บาท** (balance) = rate × price และหักค่าบริการแม้ไม่ได้คืน — คงไว้
- ยกเลิกรายการเถาเปาคืนค่าบริการ (charge ของทั้งใบ) ทุกครั้งที่ยกเลิกแต่ละรายการ — คงไว้
- admin ยกเลิกใบที่จ่ายแล้ว: คืนเงินเท่ากับ balance **แถวแรก** ของใบ (ปกติคือแถวชำระเงิน) และไม่หักรายการคืนเงินย่อยที่เคยคืนไปแล้ว — คงไว้
- `save_approved` (order/taobao) ไม่ตรวจสถานะก่อน (อนุมัติใบที่จ่าย/ยกเลิกแล้วซ้ำได้ — UI แสดงปุ่มเฉพาะสถานะที่ถูก) — คงไว้ (งานของแอดมิน)
- `save_finish` ตรวจ balance ด้วย `prefix = 11` ตายตัว
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
| `_admin/_order/email_*.php`, `_admin/_taobao/email_*.php` | เรียกตรงได้โดยไม่ตรวจอะไร | admin_order / admin_taobao, ไม่ทำอะไร |
| `_admin/_order/delete.php`, `_admin/_taobao/save_cancel.php`, `rollback.php` | race: กดซ้ำ/ยิงพร้อมกัน = คืนเงิน/หักเงินซ้ำ | transaction + ล็อกแถว + พลิกสถานะแบบ atomic |
| `_order/form.php`, `_admin/_order/form.php` | XSS ผ่าน URL: `var freagment = '<?php echo $div ?>'` (strip_tags ไม่กัน `'`) | escape แบบ JS |
| grid/form/print ทุกตัว | stored XSS: ชื่อสินค้า/ร้าน/หมายเหตุ/tracking/quote/cancel_remark ฯลฯ echo ดิบ (ลูกค้าเขียนเองได้ → แอดมินเปิดดู) | escape |
| grid ทุกตัว | reflected XSS: ค่า `txtsearch`/`page` ถูก echo กลับใน value/JS | escape |
| `_admin/_order/save_remove_track.php` | echo SQL กลับ | ตอบว่าง |
| ทุก POST | ไม่มี CSRF | `LegacyCsrf` (Origin/Referer หรือ XHR) — JS เดิมใช้ได้ (ทดสอบ origin อื่น = 403) |
| `system_status_function.php` (readRedis) | รหัส Redis ภายนอกฝังในโค้ดเดิม | ไม่ใช้/ไม่คัดลอก — ใช้ Cache ของ Laravel |

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
