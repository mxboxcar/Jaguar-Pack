# กลุ่ม tracking — รายงานการพอร์ต

branch `mod/tracking` · worktree `/home/admin/laravel/jl-wt/tracking` · dev server `bin/wt-serve tracking` → http://127.0.0.1:8201

โมดูลเดิม: `_tracking`, `_admin/_tracking`, `_admin/_combine_track`, `_lost_package`, `_admin/_lost_package` และ `CombineTrack()` (system_status_function.php)

| ส่วน | ไฟล์ |
|---|---|
| routes | `routes/modules/tracking.php` |
| controller | `app/Http/Controllers/Tracking/{TrackingController,LostPackageController}.php`, `Tracking/Admin/{AdminTrackingController,CombineTrackController,AdminLostPackageController}.php` |
| logic ร่วมในกลุ่ม | `app/Domain/Tracking/` — `Php74` (เทียบ/คำนวณแบบ PHP 7.4), `TrackingLookups` (ข้อมูลประกอบต่อแถวแบบ batch, prefix cache, MakePaging), `PricePreview`, `ProductTypeSelect`, `CombineTrack`, `UploadBox` (getupload/getuploadmultiple), `LostPackageMailer` |
| view | `resources/views/tracking/{customer,admin,combine,lost,partials}/` |
| test | `tests/Feature/Tracking/TrackingRoutesTest.php` |

## 1. Routes ที่พอร์ต (URL เดิม → route ใหม่)

หน้า (RewriteRule) — ทุกหน้ามี `->defaults('legacy_page', ...)` = `$_GET['page']` เดิม

| URL เดิม | page เดิม | route | สิทธิ์ |
|---|---|---|---|
| `tracking` | tracking | `GET /tracking` | `legacy.perm:tracking` |
| `tracking/create` | tracking_detail (id=0) | `GET /tracking/create` | `legacy.perm:tracking` |
| `tracking/([0-9]+)` | tracking_detail | `GET /tracking/{id}` | `legacy.perm:tracking` |
| `admin/tracking` | admin_tracking | `GET /admin/tracking` | `legacy.perm:admin_tracking` |
| `admin/tracking/create` | admin_tracking_detail | `GET /admin/tracking/create` | `legacy.perm:admin_tracking` |
| `admin/tracking/status/([0-9]+)` | admin_tracking | `GET /admin/tracking/status/{status}` | `legacy.perm:admin_tracking` |
| `admin/tracking/([0-9]+)` | admin_tracking_detail | `GET /admin/tracking/{id}` | `legacy.perm:admin_tracking` |
| `admin/combine_track` | admin_combine_track | `GET /admin/combine_track` | `legacy.perm:admin_combine_track` |
| `lost_package` | lost_package | `GET /lost_package` (+ `?status=`) | `legacy.perm:lost_package` |
| `lost_package/create` | lost_package_detail (id=0) | `GET /lost_package/create` | `legacy.perm:lost_package` |
| `lost_package/([0-9]+)` | lost_package_detail | `GET /lost_package/{id}` | `legacy.perm:lost_package` |
| `lost_package/status/([0-9]+)` | lost_package_detail (ไม่มี id) | `GET /lost_package/status/{status}` | `legacy.perm:lost_package` |
| `admin/lost_package` | admin_lost_package | `GET /admin/lost_package` | `legacy.perm:admin_lost_package` |
| `admin/lost_package/create` | admin_lost_package_detail | `GET /admin/lost_package/create` | `legacy.perm:admin_lost_package` |
| `admin/lost_package/status/([0-9]+)` | admin_lost_package | `GET /admin/lost_package/status/{status}` | `legacy.perm:admin_lost_package` |
| `admin/lost_package/([0-9]+)` | admin_lost_package_detail | `GET /admin/lost_package/{id}` | `legacy.perm:admin_lost_package` |

ajax / form post (ทุกตัว `legacy.ajax` = system_check_ajax เดิม, CSRF = `LegacyCsrf` ของ group legacy)

| ไฟล์เดิม | route | สิทธิ์ (เดิม → ใหม่) |
|---|---|---|
| `_tracking/grid.php` | `POST /_tracking/grid` | tracking (ไม่มีสิทธิ์ = ตอบว่างเหมือนเดิม, ตรวจใน controller) |
| `_tracking/delete.php` | `POST /_tracking/delete` | tracking |
| `_tracking/save.php` | `POST /_tracking/save` | tracking |
| `_tracking/load_pricing.php` | `POST /_tracking/load_pricing` | ไม่มี → ล็อกอิน + tracking ของตัวเองเท่านั้น |
| `_tracking/load_product_type.php` | `POST /_tracking/load_product_type` | ไม่มี → ล็อกอิน + tracking ของตัวเองเท่านั้น |
| `_tracking/save_quick_quote.php` | `POST /_tracking/save_quick_quote` | ไม่มี (กรอง user) → ล็อกอิน + กรอง user |
| `_admin/_tracking/grid.php` | `POST /_admin/_tracking/grid` | admin_tracking (ตอบว่างเมื่อไม่มีสิทธิ์) |
| `_admin/_tracking/load_paging.php` | `POST /_admin/_tracking/load_paging` | admin_tracking |
| `_admin/_tracking/delete.php` | `POST /_admin/_tracking/delete` | admin_tracking |
| `_admin/_tracking/save.php` | `POST /_admin/_tracking/save` | admin_tracking |
| `_admin/_tracking/save_lock.php` | `POST /_admin/_tracking/save_lock` | **ไม่มี** → admin_tracking |
| `_admin/_tracking/load_pricing.php` | `POST /_admin/_tracking/load_pricing` | **ไม่มี** → admin_tracking |
| `_admin/_tracking/load_product_type.php` | `POST /_admin/_tracking/load_product_type` | **ไม่มี** → admin_tracking |
| `_admin/_combine_track/load_tracking.php` | `POST /_admin/_combine_track/load_tracking` | admin_combine_track |
| `_admin/_combine_track/save_combine.php` | `POST /_admin/_combine_track/save_combine` | admin_combine_track |
| `_lost_package/grid.php` | `POST /_lost_package/grid` | lost_package (ตอบว่างเมื่อไม่มีสิทธิ์) |
| `_lost_package/cancel.php` | `POST /_lost_package/cancel` | lost_package |
| `_lost_package/load_order_number.php` | `POST /_lost_package/load_order_number` | lost_package |
| `_lost_package/load_upload.php` | `POST /_lost_package/load_upload` | lost_package |
| `_lost_package/save.php` | `POST /_lost_package/save` | lost_package |
| `_lost_package/save_image.php` | `POST /_lost_package/save_image` | **ไม่มี** → lost_package |
| `_lost_package/upload.php` | `POST /_lost_package/upload` | ล็อกอิน (เหมือนเดิม) |
| `_admin/_lost_package/grid.php` | `POST /_admin/_lost_package/grid` | admin_lost_package (ตอบว่างเมื่อไม่มีสิทธิ์) |
| `_admin/_lost_package/cancel.php` | `POST /_admin/_lost_package/cancel` | admin_lost_package |
| `_admin/_lost_package/rollback.php` | `POST /_admin/_lost_package/rollback` | admin_lost_package |
| `_admin/_lost_package/save_approved.php` | `POST /_admin/_lost_package/save_approved` | admin_lost_package |
| `_admin/_lost_package/email_approved.php` | `GET\|POST /_admin/_lost_package/email_approved` | **ไม่มีเลย (ไม่ต้องล็อกอิน)** → admin_lost_package, ตอบว่าง ไม่ส่งอะไร |
| `_admin/_lost_package/email_cancel.php` | `GET\|POST /_admin/_lost_package/email_cancel` | เหมือนข้างบน |
| `CombineTrack()` | `App\Domain\Tracking\CombineTrack::run()` (ให้กลุ่มที่พอร์ต `_api/submit_order_number_v2.php` เรียก) | — |

## 2. ไม่ได้พอร์ต / ต่างจากเดิมโดยตั้งใจ

ไม่ได้ทำ route:
- `/_tracking/index`, `/_tracking/form`, `/_admin/_tracking/index`, `/_admin/_tracking/form`, `/_admin/_combine_track/index`, `/_lost_package/index`, `/_lost_package/form`, `/_admin/_lost_package/index`, `/_admin/_lost_package/form` — เป็นไฟล์เนื้อหาของหน้าที่ `content.php` include; เปิดตรงในเว็บเดิมได้แค่ fatal error (ไม่มี `$resource`/ฟังก์ชัน) → หน้าเหล่านี้เข้าทาง URL ของ RewriteRule ด้านบน
- `_lost_package/grid` และ `_admin/_lost_package/grid` เรียก `../delete` (ปุ่มลบ) และฟอร์มแอดมินโพสต์ไป `_admin/_lost_package/save` แต่ไฟล์ทั้งสองไม่มีในเว็บเดิม (404 เดิม) — ไม่ได้สร้าง

ผลต่างที่ตั้งใจ:
- **save_combine.php**: เดิม `INSERT INTO tracking VALUES(...)` 42 ค่า แต่ตารางมี 43 คอลัมน์ (มี `date_section`) → INSERT ล้มทุกครั้ง แต่ยัง `DELETE` tracking ทั้ง 2 แถว และย้าย `bill_product_on_tracking` ไป `tracking_id = 0` (ข้อมูลหาย) → ใหม่: INSERT ครบคอลัมน์ (รวม date_section ตามกติกาเดียวกับ `CombineTrack()`), ลบ/ย้าย/คิดราคาใน `DB::transaction()`
- `CombineTrack()`: loop ตรวจ `WHERE lot = ...` (คอลัมน์ไม่มีจริง → SQL error → ไม่ทำอะไร) ตัดทิ้ง; `exit(0)` เมื่อ order ว่าง → คืน flag ให้ผู้เรียกหยุดเอง; ไม่ `echo` แต่คืนข้อความ
- `save_lock.php`: เดิมไม่สนค่า `value` (ปุ่มล็อก -2 / ปลดล็อก 0) ตั้ง `payment_id = -3` เสมอ — **คงพฤติกรรมเดิม** (บั๊กเดิม แจ้งเจ้าของ)
- `save_approved.php`: เดิม id ที่ไม่มีจริงก็ INSERT balance ขยะ (user = 0, amount 0) → ข้าม
- `_lost_package/save.php`: รับชื่อรูปเฉพาะชื่อไฟล์ธรรมดา (`[A-Za-z0-9._-]`); เดิมบันทึกทุกค่าและ `file_exists('../upload/temp/'.$img)` ตรงๆ (path traversal)
- `_lost_package/upload.php`: เก็บไฟล์ที่ `config('legacy.upload.temp_dir')` (dev = `storage/app/legacy-upload/temp`) — เดิมเขียน `upload/temp/` แบบ relative
- อีเมล lost_package: ใช้ Mail ของ Laravel (dev `MAIL_MAILER=log` → ไม่ส่งจริง); LINE / push เดิมเป็นฟังก์ชันว่าง → ไม่ส่ง (log ไว้เท่านั้น); `email_*.php` เปิดตรงไม่ส่งอะไร
- `/_admin/_tracking/load_paging`: เดิม cache ใน Redis 10 นาที → `Cache` ของ Laravel 10 นาที (key = page + เงื่อนไขค้นหา)
- ข้อความ/ค่าจาก DB ใน HTML ใช้ `{{ }}` (เดิม echo ดิบ) → ค่าที่มี `' " < > &` ต่างจากเดิม (escape); ค่าในตัวแปร PHP เดิมที่ `'` กลายเป็น `''` (เช่นช่องค้นหา) ยังแสดงเป็น `''` แต่ escape
- ค่าผู้ใช้ใน JS (`$Page`, `status`, tracking ในข้อความ SuccessBox) ผ่าน `Alerts::js()` (เดิม XSS / JS พังเมื่อมี `'`)
- LIKE escape `% _ \` (เดิมเป็น wildcard)
- หน้าที่ไม่มีสิทธิ์ / lost_package ของคนอื่น: แสดง layout + ErrorBox ครบหน้า (เดิม `exit()` กลางหน้า layout ขาดท้าย); ฝั่งหน้า index เดิมแสดงหัวข้อก่อน alert — ใหม่เป็นหน้า no-permission ของฐานร่วม
- ลำดับแถวที่เดิมไม่มี `ORDER BY` (ประเภทสินค้า, admin_quote/goodsconfirm แถวแรก, ออเดอร์ที่ผูก, balance) ใช้ `ORDER BY id` (= ลำดับที่ MySQL คืนจริงในข้อมูล bench)
- งานเขียนหลายตาราง (admin save + CalPriceAutoDiff + goodsconfirm, combine, approve, rollback, lost save) ห่อ `DB::transaction()`

## 3. ช่องโหว่เดิมที่พบ (แก้แล้วในพอร์ต)

1. **SQL injection** ทุกไฟล์ (ต่อ `$_POST`/`$_GET` ตรง) — เด่นสุด: `_tracking/delete.php` และ `_admin/_tracking/delete.php` ฝัง `id`/`arr_id` ใน `IN (...)` ไม่มีเครื่องหมายคำพูด (`id=1) OR (1=1` = ลบทุกแถว), `save_combine.php` `IN (".substr($tracking,1).")` → bind ทั้งหมด, id list ต้องเป็นตัวเลขเท่านั้น
2. **IDOR** `_tracking/save.php`: ถ้า tracking ไม่ใช่ของตัวเอง ตกไปที่ `UPDATE tracking SET quote = ... WHERE id = '$id'` ไม่กรอง user = ลูกค้าแก้หมายเหตุของ tracking ใครก็ได้ → กรอง user
3. ไม่ตรวจสิทธิ์: `_admin/_tracking/save_lock.php` (ใครที่ส่ง XHR ได้ ไม่ต้องล็อกอิน ตั้ง payment_id = -3 / ล้าง date_thai ของ tracking ไหนก็ได้), `_admin/_tracking/load_pricing.php`, `load_product_type.php`, `_tracking/load_pricing.php`, `load_product_type.php` (ไม่ต้องล็อกอิน อ่านอัตราราคาของผู้ใช้ประเภทใดก็ได้ผ่าน id tracking), `_lost_package/save_image.php`, `_admin/_combine_track/index.php`
4. `_admin/_lost_package/email_approved.php` / `email_cancel.php`: เปิดตรงได้โดยไม่ล็อกอิน และ `extract($_GET)` ทำให้ `?email_id=N` สั่งส่งอีเมลแจ้งลูกค้าของรายการใดก็ได้ (spam/phishing ด้วยโดเมนบริษัท)
5. Stored XSS: ค่า tracking/order/quote/remark/submit_remark/cancel_remark/ชื่อประเภทสินค้า/detail ของ balance echo ดิบ; reflected XSS: `$_POST['page']`, `$_POST['txtsearch']` ใน grid, `$_GET['status']` ใน JS ของหน้า lost_package
6. path traversal: `_lost_package/save.php` / `save_image.php` `file_exists('../upload/temp/'.$img)` + ส่ง path ไปให้ `_api/blockblob_ajax`
7. ไม่มี CSRF (ทุก POST) → `LegacyCsrf`
8. ข้อมูลหาย: `save_combine.php` (ข้อ 2 หัวข้อก่อน)
9. `_admin/_combine_track/load_tracking.php` ค้นว่าง = `LIKE '%%'` คืนทุกแถว (~546K แถว) — **คงไว้เหมือนเดิม** (แอดมินเท่านั้น) แต่เป็นความเสี่ยงโหลดเครื่อง เสนอให้บังคับต้องกรอกคำค้น

สิทธิ์ที่เดิมไม่มีการตรวจ ใส่สิทธิ์ของหน้าแม่ตาม CONVENTIONS ข้อ 3.1 (ดูตารางข้อ 1)

## 4. N+1 ที่แก้

| หน้า | เดิม (ต่อหน้า 10 แถว) | ใหม่ |
|---|---|---|
| `_tracking/grid` | 2 + 10 (ออเดอร์ที่ผูก) + getPrefix ทุกแถว | count + list + 1 (ออเดอร์) + prefix cache |
| `_admin/_tracking/grid` | 2 + 10×4 (ออเดอร์, admin_quote, goodsconfirm, ชั้นวาง) + prefix ×N | list + 4 batch query (IN) |
| `_admin/_combine_track/load_tracking` | 1 + N×2 | 1 + 2 |
| `_lost_package/grid`, `_admin/_lost_package/grid` | 2 + 10 (รูปแรก) | count + list + 1 |

## 5. ตรวจเทียบกับเว็บเดิม (:8101) — HTML เท่ากันหลัง normalize ช่องว่าง/href

`bin/wt-serve tracking`, cookie เดียวกัน (ลูกค้า 137, ลูกค้า 545 (มี lost_package), แอดมิน zz_fdn_admin 11938)

ตรงกันทุกตัวอักษร (ต่างเฉพาะช่องว่าง): `/tracking`, `/tracking/create`, `/tracking/{id}` (มี lot / ไม่มี lot / ของคนอื่น / ไม่มีจริง), `_tracking/grid` (หลายหน้า, สถานะ 0/2/3, สถานะที่ไม่ใช่ตัวเลข, page ว่าง), `load_pricing`, `load_product_type`, `/admin/tracking`, `/admin/tracking/status/2`, `/admin/tracking/{create,id}` ×5, `_admin/_tracking/grid` (ค้น tracking / ship / รหัสลูกค้า / ค่ามีช่องว่างท้าย), `load_paging` ×4, `/admin/combine_track`, `load_tracking` (มีผล / ไม่มีผล), `/lost_package`, `?status=2`, `/lost_package/{create,id,status/1}`, `_lost_package/grid`, `load_upload`, `load_order_number`, `/admin/lost_package` (+status), `/admin/lost_package/{create,id}`, `_admin/_lost_package/grid`
ต่างตามข้อ 2: ค่าที่มี `'` ถูก escape (`a''b` → `a&#039;&#039;b`), หน้า lost_package ของคนอื่น (ครบ layout)

งานเขียน DB: endpoint เขียนของเว็บเดิมบน :8101 ใช้ไม่ได้ (goLog พยายามเปิด log ใน webroot production แล้ว `exit` ก่อน query — ลองไป 1 ครั้งตอนเริ่ม ไม่มีการเขียนอะไร) จึงตรวจฝั่งใหม่กับ SQL ของไฟล์เดิมทีละคอลัมน์ ด้วยข้อมูลทดสอบ `zz_` (ลบแล้ว): สร้าง/แก้/ลบ tracking ลูกค้า (+ เลขซ้ำ, IDOR, `id=1) OR (1=1`), แอดมินแก้/สร้าง (+ วันที่ไทย, CalPriceAutoDiff, ไม่พบผู้ใช้), save_lock, รวม 2 แถว (ค่ารวม/amount สูงสุด/ลบแถวเดิม/คิดราคา), lost_package สร้าง → อนุมัติ (balance = ¥12.50 × 5.190 = 64.88) → อนุมัติซ้ำ (ไม่ทำ) → rollback (balance -64.88) → ลูกค้ายกเลิก → แอดมินยกเลิก (อีเมล 2 ฉบับลง log), upload (png ผ่าน / txt / รูปปลอม ถูกปฏิเสธ)

## 6. Bench (`bin/bench`, -n 30 -c 1, `artisan optimize`)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/tracking` (user 137) | 328 | 35 |
| `_tracking/grid` page 1 (user 137, 19,630 แถว) | 387 | 110 |
| `_admin/_tracking/grid` page 1 (แอดมิน) | 114 | 44 |
| `_lost_package/grid` (user 545) | 69 | 31 |

## 7. แก้ฐานร่วม (commit แยก `foundation:`)

- `bootstrap/app.php`: ถอด global middleware `TrimStrings` และ `ConvertEmptyStringsToNull` — Laravel trim ค่า POST ทุกตัวและแปลง `''` เป็น `null` ก่อนถึง `LegacyInput` ทำให้ค่าที่ลง DB/แสดงผลต่างจากเว็บเดิม (เจอใน `_admin/_tracking/grid` ค้นด้วยค่าที่มีช่องว่างท้าย) — มีผลกับทุกกลุ่ม

## 8. ฝากกลุ่มอื่น / เจ้าของ

- `UploadBox` (getupload/getuploadmultiple) พอร์ตไว้ในกลุ่มนี้ชั่วคราว — เจ้าของคือ settings (ใช้ร่วม) ตอน merge ให้ย้ายไปของกลาง
- `CombineTrack::run()` สำหรับกลุ่มที่พอร์ต `_api/submit_order_number_v2.php`
- JS ของ `_lost_package/save` / `save_image` ยังเรียก `_api/blockblob_ajax` (ย้ายไฟล์ขึ้น Azure) แบบเดิม — endpoint นั้นเป็นของกลุ่มอื่น ต้องใช้ driver local ใน dev
- เสนอ index: `tracking(tracking)`, `goodsconfirm(tracking_id)`, `warehouse_section_detail(tracking_id)`, `bill_product_on_tracking(tracking_id)` (ถ้ายังไม่มี)
