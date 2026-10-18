# กลุ่ม shop — รายงานการพอร์ต

โมดูลเดิม: `_cart`, `_taobao`, `_api`, `_mall`, `_admin/_mall`, `_admin/_mall_category`, `_admin/_mallstock` (64 ไฟล์ + 18 RewriteRule)
branch `mod/shop` · worktree `/home/admin/laravel/jl-wt/shop` · dev `http://127.0.0.1:8202` (`bin/wt-serve shop --bg`)

| ส่วน | ไฟล์ |
|---|---|
| routes | `routes/modules/shop.php` |
| controller | `app/Http/Controllers/Shop/{Cart,Taobao,Mall,Api}Controller.php`, `Shop/Admin/{MallAdmin,MallCategory,MallStock}Controller.php` (ฐาน `ShopController`) |
| domain | `app/Domain/Shop/` — `CartRepo`, `CartHtml` (HTML ตะกร้า), `Php74` (เลขคณิต/เทียบแบบ 7.4), `Upload` (getupload + JS blob), `BlobStore`, `CrawlGuard`, `ProductCrawler`, `UserAgent`, `ImageShrink`, `Unjail` |
| view | `resources/views/shop/{cart,taobao,mall,admin/*}` |
| test | `tests/Feature/Shop/ShopRoutesTest.php`, `ShopHelpersTest.php` |

## 1. URL ที่พอร์ต (เดิม → ใหม่)

หน้า (RewriteRule → `legacy_page`):

| URL เดิม | ไฟล์เดิม | route ใหม่ | สิทธิ์ |
|---|---|---|---|
| `taobao` | `_taobao/index.php` | `GET /taobao` | login + ตรวจ `taobao` ใน view (แบบเดิม) |
| `taobao/create`, `taobao/{id}` | `_taobao/form.php` | `GET /taobao/create`, `/taobao/{id}` | `taobao`, เฉพาะของตัวเอง |
| `cart/checkout`, `cart/checkout/{ids}` | `_cart/checkout.php` | `GET /cart/checkout[/{ids}]` | `order` |
| `cart/product/{url}` (+ `?page=checkout_product&l=`) | `_cart/product.php` | `GET /cart/product/{url?}` (ชื่อ `page.checkout_product`) | `order` |
| `mall`, `mall/{search}` | `_mall/index.php` | `GET /mall`, `/mall/{search}` | login + ตรวจ `mall` ใน view |
| `mall/cart` | `_mall/formcart.php` | `GET /mall/cart` | login + `mall` |
| `mall/create`, `mall/{id}` | `_mall/form.php` (ไม่มีไฟล์) | `GET /mall/create`, `/mall/{id}` → layout เปล่า | login |
| `admin/mall`, `admin/mall/create`, `admin/mall/{id}` | `_admin/_mall/index.php`, `form.php` | เหมือนเดิม | `admin_mall` |
| `admin/mall_stock` | `_admin/_mallstock/index.php` | `GET /admin/mall_stock` | `admin_mall_stock` |
| `admin/mall_category` | `_admin/_mall_category/index.php` | `GET /admin/mall_category` | `admin_mall_category` |
| `admin/mall_category/create`, `/{id}` | `_admin/_mall_category/form.php` | เหมือนเดิม | `admin_user_type` (ตามไฟล์เดิม) |

ajax (`/<ไฟล์>` ไม่มี `.php`, POST + `legacy.ajax` ถ้าเดิม require `system_check_ajax.php`):

| ไฟล์เดิม | route | สิทธิ์ |
|---|---|---|
| `_cart/load_cart`, `load_full_cart`, `save_product`, `delete_cart`, `delete_carts`, `save_amount`, `save_craft`, `save_qc`, `save_checkout` | POST เดียวกัน | `order` |
| `_cart/load_choose_product` | POST | login (ไม่มีสิทธิ์ = `<script>` แบบ getPermissionAlert_Page เดิม) |
| `_cart/cart` | GET/POST (ชิ้นส่วน HTML) | login |
| `_taobao/grid` | POST | login (ไม่มีสิทธิ์ `taobao` = ตอบว่างแบบเดิม) |
| `_taobao/load_new_line`, `load_calculator`, `save`, `save_remark` | POST | `taobao` |
| `_taobao/delete` | POST → 500 ว่าง (ดูข้อ 2) | login |
| `_mall/grid` | POST | login (ไม่มีสิทธิ์ `refill` = ว่าง แบบเดิม) |
| `_mall/load_detail`, `addtocart`, `delete_cart`, `save` | POST | `mall` |
| `_admin/_mall/grid`, `load_detail`, `load_sizecolor`, `load_stock`, `save`, `save_sizecolor`, `save_delsizecolor`, `save_isshow` | POST | `admin_mall` |
| `_admin/_mall/delete` | POST | `admin_product_type` (ตามไฟล์เดิม) |
| `_admin/_mall_category/grid`, `save` | POST | `admin_mall_category` |
| `_admin/_mall_category/delete` | POST | `admin_user_type` (ตามไฟล์เดิม) |
| `_admin/_mallstock/grid` | POST | `admin_mall_stock` |
| `_admin/_mallstock/load_amount`, `save_amount` | POST | `admin_mall_stock` (เดิม `admin_balance` — แก้ 2026-10-18) |
| `_api/load_product` | POST | `order` |
| `_api/load_product_v2` | POST | `order` (เดิมไม่ต้องล็อกอิน) |
| `_api/blockblob_ajax` | POST | login (เดิมไม่ต้องล็อกอิน) |
| `_api/blockblob`, `crawl_guard`, `image_shrink`, `useragent`, `load_product_v5`, `load_product_v7`, `send_sms` | GET/POST | login |
| `_api/get_order_number_v2`, `get_order_number_v3`, `submit_order_number_v2` | GET | token `LEGACY_SHOP_ORDER_API_TOKEN` |
| `_api/updateImagePrefix` | GET/POST | token `LEGACY_SHOP_IMAGE_PREFIX_TOKEN` |
| `_api/unban` | GET/POST | token `LEGACY_SHOP_UNBAN_TOKEN` |

ไม่ได้ทำ route (ตั้งใจ):
- ไฟล์ชิ้นส่วนหน้าที่เปิดตรงแล้ว 500 ในเว็บเดิม (`_cart/checkout`, `_cart/product`, `_taobao/index`, `_taobao/form`, `_mall/index`, `_mall/formcart`, `_admin/_mall/index|form`, `_admin/_mallstock/index`, `_admin/_mall_category/index|form`) — ใช้ผ่าน URL หน้าแทน
- `_cart/product_2` — สำเนาเก่าของ product.php ไม่มี page ไหนชี้มา เปิดตรง = 500

## 2. ต่างจากเดิมโดยตั้งใจ / ข้อสังเกต

- **กันกดซ้ำ/ส่งซ้อน (double submit)** (เจ้าของอนุมัติ 2026-10-18, E2E: ดับเบิลคลิก `_taobao/save` = 2 รายการ ตัดเงิน 2 ครั้ง — เว็บเดิมก็เป็น): middleware `legacy.once` (`App\Support\SubmitGuard`) — lock ต่อ ผู้ใช้+route ระหว่างประมวลผล (คำขอที่ซ้อนได้ `ErrorBox('รายการนี้กำลังดำเนินการ กรุณารอสักครู่')`) + payload เดิมที่สำเร็จแล้วภายใน 8 วินาทีได้ `ErrorBox('รายการนี้ถูกส่งไปแล้ว (ส่งซ้ำ) ...')` (คำตอบ error/กล่อง PIN ไม่ถูกจำ ส่งใหม่ได้ทันที): `_taobao/save`, `_cart/save_checkout`, `_mall/save` · test: `tests/Feature/SubmitGuardTest.php`, `tests/Feature/Money/DoubleSubmitDbTest.php` (JL_BENCH_DB_TESTS=1)
  - ชั้นนี้กันรายการซ้ำ; การกันยอดติดลบเมื่อคำขอต่างกันมาพร้อมกันยังเป็น transaction + `SELECT … FOR UPDATE` แถว user แล้วตรวจยอดภายใน (ทุก endpoint ตัดเงินตอนสร้าง)
- `_taobao/save` ล็อกแถว `user` (`Money::lockUser`) แทนการล็อกแถว `balance`/`balance_cn` ของผู้ใช้ (ผู้ใช้ที่ยังไม่มีแถว = gap lock ที่สอง transaction ถือพร้อมกันได้) แล้วตรวจยอดใน transaction เดิม
- **ราคาในตะกร้า (`_cart/save_product`) — ลูกค้ากำหนดเองไม่ได้** (เจ้าของอนุมัติ 2026-10-18 "ให้แอดมินแก้ไขก็พอ", รายงานข้อ 4.16): เดิมเชื่อ `search_price` จาก browser (ช่องราคาในฟอร์มแก้ได้ และยิง POST ตรงได้ เช่น 0.01)
  - `_api/load_product` จำชุดราคาที่ crawler ตอบ (`price`, ราคาทุก SKU `skus[*].price.priceText`, `priceRange[*][1]`) ต่อ ผู้ใช้ + URL (ทั้ง URL ที่ส่งมาและ `url` ที่ crawler ตอบ) ใน cache 2 ชม. (`App\Domain\Shop\CrawlPrices`)
  - `save_product`: ราคาที่ส่งมาตรงกับชุดนั้น (เทียบทศนิยม 2 ตำแหน่ง) = ลง DB ตามค่าที่ส่ง (เหมือนเดิม — JS เลือกราคาตาม SKU/จำนวนจากชุดเดียวกัน) · ไม่ตรง = ใช้ราคาหลักของ crawler · ไม่มีข้อมูล crawler ของผู้ใช้/URL นี้ (ไม่ได้โหลดผ่าน load_product หรือเกิน 2 ชม.) = `ErrorBox('ไม่พบข้อมูลราคาสินค้า กรุณาค้นหาสินค้าใหม่อีกครั้ง')` ไม่เขียนอะไร
  - ผู้ถือ `admin_mall` (role 1, 6) ใส่ราคาเองได้แบบเดิม · ราคาในบิลหลัง checkout แก้ได้ที่ฝั่งแอดมินออเดอร์ตามเดิม
  - ชื่อสินค้า/ร้าน/รูป ยังมาจาก browser แบบเดิม (ไม่กระทบยอดเงิน — พนักงานตรวจบิลเหมือนเดิม)
  - ตรวจสด :8298 (zz_fdn_cust): ส่ง 0.01 ก่อนโหลดสินค้า = ErrorBox; โหลดสินค้า (DEV STUB ราคา 10.00) แล้วส่ง 0.01 → `cart_detail.price = 10.00` (ลบแถวทดสอบ cart/cart_detail/crawl_guard_log ตาม id แล้ว)
- **`_admin/_mallstock/load_amount`, `save_amount`** (รายงานข้อ 4.16): เดิมเป็นสำเนาของ `_admin/_balance/*` → ผู้ถือ `admin_balance` INSERT/UPDATE แถว `balance` (ยอดเงินลูกค้า) ใดก็ได้ผ่าน URL นี้ → ใหม่: สิทธิ์ `admin_mall_stock` และแตะเฉพาะตาราง `mall_stock`
  - `load_amount`: แถว `mall_stock` (is_delete = 0) + `user.code` ในรูป JSON เดิม (key ตัวเลข+ชื่อ), ไม่พบ = `null`
  - `save_amount`: `id` = แถว mall_stock ที่มีอยู่ → แก้ `amount` (จำนวนเต็มเท่านั้น), `detail`, `user` (ถ้าส่ง `code` — ไม่พบ = ErrorBox no_user เดิม); `id` ว่าง = เพิ่มแถวใหม่ต้องส่ง `mall_detail_id` ที่มีอยู่จริง (mall_id เอาจาก mall_detail); `type` ไม่ใช้; id ไม่ใช่ตัวเลข/ไม่มีจริง = ErrorBox no_data
  - หน้า `admin/mall_stock` ยังเรียก `_admin/_balance/*` ตัวจริงแบบเดิม (กลุ่ม balance — ไม่ได้แก้)
- สิทธิ์ `admin_mall_category` (เดิมไม่มี role ใดถือ): ใช้งานได้แล้วผ่าน fallback ← `admin_mall` (role 1, 6) — ดู CONVENTIONS ข้อ 3.1
- **ภายนอก (dev ไม่ยิงจริง)**: `_api/load_product` เรียก crawler (`setting.apiurls`) เฉพาะเมื่อ `LEGACY_EXTERNAL_ENABLED=true`; dev ตอบสินค้าปลอม `[DEV STUB]` (ยังบันทึก crawl_guard_log และ log แบบเดิม). `load_product_v2` (vcanbuy) ก็ปิดใน dev
- **อัปโหลด**: `_api/blockblob_ajax` driver `local` คัดลอกจาก `legacy.upload.temp_dir` ไป `local_dir` (ผล `temp empty`/`exists`/`` เหมือนเดิม); **driver `azure` ยังไม่พอร์ต** (ไม่มี SDK ใน vendor และห้ามยิง Azure จาก dev) → ตอบ error + log — ต้องทำร่วมกับกลุ่ม settings ก่อนสลับ production
- **`_api/submit_order_number_v2`** ต้องใช้ `CombineTrack()` ของกลุ่ม tracking (ยังไม่พอร์ต): เรียก `App\Domain\Tracking\CombineTrack::run($tracking, $order)` ถ้ามี ไม่มี = 501 ไม่เขียนอะไร
- **token API**: token เดิมฝังในโค้ด → อ่านจาก `.env` (`LEGACY_SHOP_*`), ไม่ตั้ง = ปิด (ตอบว่าง) และเทียบด้วย `hash_equals`
- `_api/send_sms`: เดิมใครเปิดก็ส่ง SMS ทดสอบด้วยบัญชี thaibulksms ที่ฝังในโค้ด → ไม่ส่งอะไร (log อย่างเดียว)
- `_api/load_product_v7`: เดิมล็อกอิน cnsthai.com ด้วยบัญชีที่ฝังในโค้ดแล้ว fatal (`removehtmltag()` ไม่มีจริง) ทุกครั้ง → ตอบ 410 Gone ทันที (เดิม 500) ไม่ยิงภายนอก
- `_api/load_product_v2` ลิงก์ `.1688`: เดิม include v5 แล้ว `getimage()` ไม่มีจริง → fatal → 500 เหมือนเดิม (ไม่ยิงภายนอก)
- `_api/unban` → `Unjail::run()` (= `check_unjail()` รวมลบ `sessions` ที่ data ว่างแบบเดิม — ตารางเก่า ไม่ใช่ `sessions_v2`)
- **mall routes**: `.htaccess` เดิมเรียง `^mall/(.+)$` (ค้นหา) ไว้ก่อน `mall/create|cart|{id}` และไม่มี `[L]` — ถ้าใช้ Apache แบบเดิมจริง `mall/cart` จะกลายเป็นค้นหาคำว่า "cart" ที่นี่ให้ path เฉพาะชนะ (ตาม url-map) — `mall/create`, `mall/{id}` ชี้ไฟล์ที่ไม่มีในเว็บเดิม → แสดง layout เปล่า (เดิมหน้าหยุดกลางคัน)
- **ไม่มีสิทธิ์**: route ที่ใช้ `legacy.perm` ตอบแบบฐานร่วม (หน้า: layout + ErrorBox เด้ง dashboard; ajax: `ErrorBox(...)`) — เดิมบางหน้าแสดงหัวหน้าก่อน alert และ grid แอดมินบางตัวตอบว่าง; หน้าลูกค้า (`taobao`, `mall`) และ grid ลูกค้าคงแบบเดิมทุกตัวอักษร
- หน้าที่เดิม `exit()` กลาง content (ไม่พบรายการ, เลือกเกิน 50 รายการ, formcart ไม่มีสิทธิ์) — เดิม layout ถูกตัดครึ่ง; ใหม่แสดง layout ครบ + ข้อความ/สคริปต์เดิม
- `_cart/cart` (ชิ้นส่วน): เดิมเปิดตรงไม่มี `href` ได้ลิงก์ `hrefcart/checkout/...` — ใหม่เป็น URL ถูกต้อง
- **ค่าจาก DB/ผู้ใช้ทุกตัว escape** (ชื่อสินค้า, หมายเหตุ, URL, ชื่อร้าน, ชื่อ mall, desc ฯลฯ) — HTML ต่างจากเดิมเฉพาะเมื่อข้อมูลมีอักขระ HTML; ที่ตั้งใจเป็น HTML ดิบ: `mall.detail` (CKEditor ของแอดมิน — `html_entity_decode` ฝั่งลูกค้า และ textarea ฝั่งแอดมินแบบเดิม)
- การรับค่า: ค่าที่ลง DB = ค่าแบบเดิม (`LegacyInput`) รวมจุดที่เดิมได้ `''` ซ้อน (`_cart/save_product` ใช้ `str_replace("'", "''")` ซ้ำ → `it''s` ลง DB เหมือนเดิม)
- คงพฤติกรรมเดิมที่ "ผิด" ไว้ (ให้เจ้าของตัดสินใจ):
  - `_cart/save_checkout`: `bill_product_on_tracking.bill_product_id` = **id ของ cart** ไม่ใช่ bill (ตัวแปร `$data` เดิม) — โค้ดอื่นอ่านผ่าน `bill_product_detail_id` จึงไม่เห็นผล
  - `_admin/_mall/load_stock`: เดิมเรียก `getPayType()` ที่ไม่มีจริง → HTML ครึ่งแถวแรก + 500 (ตารางประวัติสต๊อกในหน้าแก้สินค้าว่างเสมอเมื่อมีประวัติ) — ใหม่ตอบแบบเดียวกัน
  - `_admin/_mall_category`: index/grid/save ใช้สิทธิ์ `admin_mall_category` ที่ไม่มีใน role_detail (ไม่มีใครเข้าได้), form/delete เป็นสำเนาของ `_admin/_user_type` (สิทธิ์ `admin_user_type`, อ่าน/ลบ **ตาราง user_type**), save แก้ไขเป็น SQL ผิด (ไม่เคย UPDATE ได้ แต่ตอบสำเร็จ), delete ที่ id ตรงกับ role → `getError()` ไม่มีอาร์กิวเมนต์ = 500
  - `_taobao/delete`: require path ผิด → 500 เสมอ (โค้ดข้างในจะลบ product_type) — ใหม่ 500 โดยไม่แตะข้อมูล
  - `_mall/grid` ตรวจสิทธิ์ `refill`, `_admin/_mall/delete` ตรวจ `admin_product_type`, `_admin/_mallstock/load_amount|save_amount` = สำเนา `_admin/_balance` (สิทธิ์ `admin_balance`, แก้ตาราง balance)
- PHP 7.4 → 8.4: เลขคณิตบน varchar (`cart_detail.price/amount`) ผ่าน `Php74::num()`, float → string ด้วย precision 14 (`Php74::str()`), `count($string)` = 1 (ชื่อสินค้าใน load_full_cart ถูกตัด 65 ตัว + `...` ทุกแถว), `$id != ""` กับ id int 0 (หน้าสร้างใหม่)

## 3. ช่องโหว่เดิมที่แก้

| ช่องโหว่ | ไฟล์เดิม | แก้ |
|---|---|---|
| SQL injection: `id IN (".base64_decode($ids).")` (อ่านตะกร้าคนอื่น/ทั้งตาราง, ลบรายการคนอื่น) | `_cart/checkout`, `load_full_cart`, `save_checkout`, `delete_carts` | รับเฉพาะรายการเลขจำนวนเต็ม + bind |
| SQL injection: ทุก `$_POST`/`$_GET` ต่อ string (`'` ถูกแปลงเป็น `''` แต่ `\` / ตัวเลข / IN / LIKE ยังเจาะได้) | ทุกไฟล์ (`_admin/_mall/delete`, `_admin/_mall_category/delete` IN (...) ฯลฯ) | query builder + bind ทั้งหมด, LIKE escape |
| IDOR: ใช้ cart ของใครก็ได้ด้วย `random` | `_cart/save_checkout` (และการหา cart ทุกไฟล์) | cart ต้องเป็นของผู้ใช้ (`cart.user`) |
| IDOR: `UPDATE taobao SET remark ... WHERE id = ?` ไม่กรองเจ้าของ | `_taobao/save` (แก้ไข) | กรอง `user` |
| ไม่ตรวจสิทธิ์/ล็อกอิน: ซื้อสินค้าตัดเงินได้ทุกคน | `_mall/addtocart`, `_mall/load_detail` | `legacy.perm:mall` |
| ไม่ตรวจล็อกอิน: สั่งอัปไฟล์ temp ขึ้น Azure, ดึงเว็บภายนอก, ส่ง SMS | `_api/blockblob_ajax`, `load_product_v2`, `send_sms` | ต้องล็อกอิน/สิทธิ์ order, SMS ไม่ส่ง |
| จำนวนติดลบ / race condition ตัดสต๊อก-ตัดเงิน | `_mall/addtocart`, `_taobao/save` | จำนวนต้องเป็นจำนวนเต็มบวก, `DB::transaction` + `lockForUpdate` |
| ไม่มี transaction (สร้างบิลครึ่งๆ กลางๆ) | `_cart/save_checkout`, `_taobao/save`, `_mall/save`, `delete_cart`, `_admin/_mall/save_sizecolor` | `DB::transaction()` |
| stored XSS (ชื่อ/หมายเหตุ/URL สินค้า, ชื่อ mall, desc, ชื่อหมวด ฯลฯ) | ทุก grid/หน้า | escape (`{{ }}` / htmlspecialchars), ค่าใน JS ผ่าน `Alerts::js()` |
| reflected XSS: `?l=`, `cart/product/{url}`, `mall/{search}`, `?status=`, `$_POST['page']` ใน JS | `_cart/product`, `_mall/index`, grid ทุกตัว | escape |
| secret ในโค้ด: token API, รหัส SMS (thaibulksms), บัญชี cnsthai.com, path key Azure | `_api/*.php` | token → `.env`; SMS/cnsthai ไม่ใช้แล้ว — **ควรเปลี่ยนรหัสทั้งหมดที่เคยอยู่ใน repo เดิม** |
| อ่านไฟล์นอก upload/temp (path จาก POST) | `_admin/_mall/save`, `_cart/save_product` (`file_exists('../upload/temp/'.$x)`) | `basename()` เสมอ |
| CSRF | ทุก POST | `LegacyCsrf` (ฐานร่วม) |
| ราคาติดลบใน `_taobao/save` (เช่น `[1000, -1000]` = ยอด 0 → จ่ายแค่ค่าบริการ แต่ taobao_detail มีออเดอร์ ¥1000 ให้แอดมินจ่ายแทน) | `_taobao/save` | ราคาทุกช่องต้องไม่ติดลบ (review) |
| จำนวน/ราคาติดลบหรือไม่ใช่ตัวเลขในตะกร้า → ยอดบิลติดลบตอน checkout | `_cart/save_product`, `_cart/save_amount` | จำนวนต้องเป็นจำนวนเต็ม ≥ 1, ราคาไม่ติดลบ; save_amount ค่าผิด = ไม่แก้ (ตอบว่างเหมือนเดิม) (review) |
| JS injection: `$_POST['id']` ต่อดิบใน `SuccessBox('...TAO-'.$id)` ที่หน้าเว็บ eval | `_taobao/save` (แก้ไข), `_admin/_mall/save`, `_admin/_mall_category/save` | `Alerts::js($id)` (review) |
| รายการเงินของคนอื่น: หน้า `taobao/create` (id 0) แสดง balance/balance_cn ที่ `prefix_ref_id = 0` ของทุกผู้ใช้ | `_taobao/form` | กรอง `user` = ผู้ใช้ (review) |
| stored XSS: `mall.detail` (CKEditor) แสดงดิบให้ลูกค้า — staff ที่มี `admin_mall` ฝังสคริปต์ได้; textarea ฝั่งแอดมินถูกปิดด้วย `</textarea>` | `_mall/load_detail`, `_admin/_mall/form` | `SafeHtml::clean()` (HTML ปกติออกเหมือนเดิมทุกตัวอักษร; มีแท็ก/แอตทริบิวต์อันตรายจึงตัด), textarea แปลง `</textarea` เป็น `&lt;/textarea` (review) |
| DOM XSS: ข้อมูลจาก crawler (สี/ขนาด/รูปที่ร้านค้าตั้งเอง) ต่อเข้า HTML ใน JS | `_cart/product` | `_h()` escape ใน JS (review) |

## 4. N+1 ที่แก้ (จำนวน query คงที่ต่อหน้า)

- `_taobao/grid`: `taobao_detail` ทีละแถว → `whereIn` ครั้งเดียว
- `_admin/_mall/grid`: `getStock2()` ทีละแถว → GROUP BY ครั้งเดียว
- `_admin/_mall/load_detail`, `_mall/load_detail`: `getStock()` ทีละแถว → GROUP BY ครั้งเดียว
- `_admin/_mallstock/grid`: SUM สต๊อกก่อนหน้าทีละแถว → self-join + GROUP BY ครั้งเดียว
- `_admin/_mall_category/grid`, `_cart/checkout|load_full_cart`: subquery ต่อแถว → join กับ GROUP BY
- `_cart/load_cart`: query เดียวกันสองรอบ → รอบเดียว; `getPrefix()` ในลูป → ครั้งเดียว

## 5. ตรวจเทียบกับเว็บเดิม (:8101) ด้วย cookie เดียวกัน

เทียบ HTML หลังแทน host และตัดช่องว่าง (`sed` แทน href + ยุบ whitespace) — ผล "เหมือนกัน" ทุกจุดด้านล่าง ยกเว้นที่ระบุ
- ลูกค้า 137: `mall`, `mall/{search}`, `_mall/grid` (มี/ไม่มีคำค้น), `_mall/load_detail` (4 สินค้า), `mall/cart`, `cart/checkout[/ids]`, `cart/product?l=`, `cart/product/{url}`, `_cart/load_cart`, `_cart/load_full_cart` (ต่างเฉพาะ escape `<b>`/`'`), `_cart/load_choose_product` (th/en/cn)
- ลูกค้า 418 (มี taobao 968 รายการ): `taobao`, `_taobao/grid` (หน้า 0/1/3, ค้น `TAO-75`), `taobao/7549|417|416` (มีรายการยกเลิก + balance_cn), `taobao/create`, `_taobao/load_calculator`, `_taobao/load_new_line`
- แอดมิน zz_fdn_admin: `admin/mall`, `_admin/_mall/grid` (หน้า 1/2), `admin/mall/{1,3,6}`, `admin/mall/create`, `_admin/_mall/load_detail|load_stock|load_sizecolor`, `admin/mall_stock`, `_admin/_mallstock/grid` (หน้า 1/3, ค้น), `admin/mall_category/{create,1}`, `_admin/_mallstock/load_amount`
- การเขียน (ข้อมูลทดสอบ `zz_shop_test*` ลบแล้วทั้งหมด): save_product (ค่า `''` ลง DB เหมือนเดิม), save_amount/craft/qc, delete_cart(s) (+ ลอง injection), save_checkout (bill/detail/tracking/bill_product_on_tracking ตรงตาม SQL เดิม), load_product (STUB + crawl_guard_log), blockblob_ajax (local), taobao save (ยอดไม่พอ / สร้าง / แก้หมายเหตุของคนอื่นไม่ได้), mall addtocart (ขาดสต๊อก/เงินไม่พอ/สำเร็จ: tracking + balance + mall_stock), admin mall save/save_sizecolor/save_delsizecolor/save_isshow/delete (+ injection)
- เว็บเดิมบน :8101 เขียน DB ไม่ได้ (goLog เขียน log ไป webroot production ไม่ได้แล้ว exit ก่อน query) — ฝั่งเขียนจึงเทียบกับ SQL ในไฟล์เดิมทีละคอลัมน์

## 6. bench (`bin/bench`, -n 30 -c 1, หลัง `artisan optimize`) — mean / p50 / p95 ms

| หน้า | เดิม 7.4 | ใหม่ Laravel |
|---|---|---|
| `taobao/417` (หน้าเต็ม) | 328 / 320 / 397 | 45 / 43 / 55 |
| `_taobao/grid` (user 418) | 57 / 57 / 63 | 28 / 28 / 30 |
| `mall` (หน้าเต็ม) | 347 / 313 / 555 | 37 / 35 / 48 |
| `_mall/grid` | 23 / 22 / 25 | 30 / 29 / 33 |
| `_admin/_mallstock/grid` | 51 / 51 / 62 | 31 / 30 / 39 |
| `_cart/load_cart` | 24 / 23 / 27 | 26 / 25 / 30 |

หน้าเต็มเร็วขึ้น ~7 เท่า (layout เดิมยิงสิทธิ์ ~120 query). ajax เล็ก (`_mall/grid`, `load_cart`) ช้ากว่าเดิมเล็กน้อยจาก overhead คงที่ของ Laravel (~20 ms: boot + session/jail/สิทธิ์) — ตาม CONVENTIONS ข้อ 10

## 7. ข้อเสนอ / ค้าง

- Azure driver ของ `BlobStore` (ร่วมกับ settings) และ `CombineTrack` (tracking) ต้องมีก่อนสลับ `_cart/save_product`(แนบรูป), `_admin/_mall/save`(รูป), `_api/submit_order_number_v2` ไป production
- ตั้ง `LEGACY_SHOP_ORDER_API_TOKEN`, `LEGACY_SHOP_IMAGE_PREFIX_TOKEN`, `LEGACY_SHOP_UNBAN_TOKEN` ใน `.env` production (ค่าใหม่ — ค่าเดิมหลุดอยู่ในโค้ด) และแจ้ง crawler/cron
- เจ้าของตัดสินใจ: สิทธิ์ `admin_mall_category`, ไฟล์ mall_category ที่เป็นสำเนา user_type, `getPayType()` ใน load_stock, `bill_product_on_tracking.bill_product_id`
- index ที่ควรมี (ตอนนี้มีแค่ PRIMARY): `cart(user)`, `cart_detail(cart_id)`, `mall_stock(mall_id, mall_detail_id)` — `crawl_guard_log` มีครบแล้ว (ยังไม่ได้สร้าง — ห้ามแก้ schema)

## 8. E2E write-flow (2026-10-18, fix/e2e)

- `_cart/save_checkout` กดซ้ำ (รายการถูกตัดออกจากตะกร้าแล้ว) เดิมสร้างใบสั่งซื้อเปล่ายอด 0 ทุกครั้ง → ไม่มีรายการ = `ErrorBox(order_error)` ไม่สร้างใบ/tracking;
  ล็อกแถว cart ก่อนอ่านรายการ (คำขอพร้อมกันคำขอที่สองเห็นตะกร้าที่ถูกตัดแล้ว) · test: `tests/Feature/Money/CartCheckoutDbTest.php`
