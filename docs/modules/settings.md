# กลุ่ม settings — รายงานการพอร์ต

Worktree `/home/admin/laravel/jl-wt/settings` (branch `mod/settings`), dev server port 8213.

## Route ที่พอร์ต (URL เดิม → route ใหม่)

| เดิม | route ใหม่ | สิทธิ์ / middleware |
|---|---|---|
| `admin/setting` (`?page=admin_setting`, `_admin/_setting/index.php`) | `GET /admin/setting` | `legacy.perm:admin_setting` |
| `_admin/_setting/save.php` | `POST /_admin/_setting/save` | `legacy.ajax`, `legacy.perm:admin_setting` (เดิมไม่ตรวจสิทธิ์) |
| `admin/exchange`, `admin/exchange/status/N` | `GET /admin/exchange`, `GET /admin/exchange/status/{status}` | `legacy.perm:admin_exchange` |
| `admin/exchange/N` (`_admin/_exchange/form.php`) | `GET /admin/exchange/{id}` | `legacy.perm:admin_exchange` |
| `_admin/_exchange/grid.php` | `POST /_admin/_exchange/grid` | `legacy.ajax`, `legacy.auth` + `perm('admin_withdrawcash')` ใน controller (เดิม exit เงียบ) |
| `_admin/_exchange/save_approved.php` | `POST /_admin/_exchange/save_approved` | `legacy.ajax`, `legacy.perm:admin_exchange` |
| `_admin/_exchange/cancel.php` | `POST /_admin/_exchange/cancel` | `legacy.ajax`, `legacy.perm:admin_withdrawpoint` (ชื่อโมดูลตามไฟล์เดิม) |
| `admin/product_type`, `/create`, `/N` | `GET /admin/product_type`, `/admin/product_type/create`, `/admin/product_type/{id}` | `legacy.perm:admin_product_type` |
| `_admin/_product_type/grid.php` | `POST /_admin/_product_type/grid` | `legacy.ajax`, `legacy.auth` + perm ใน controller |
| `_admin/_product_type/save.php`, `delete.php` | `POST /_admin/_product_type/save`, `/delete` | `legacy.ajax`, `legacy.perm:admin_product_type` |
| `admin/shipping_type`, `/create`, `/N` | `GET /admin/shipping_type`, `/create`, `/{id}` | `legacy.perm:admin_shipping_type` |
| `_admin/_shipping_type/grid.php` | `POST /_admin/_shipping_type/grid` | `legacy.ajax`, `legacy.auth` + perm ใน controller |
| `_admin/_shipping_type/{save,save_listno,delete,delete_cal,load_cal_1,load_cal_2,save_new_price_by_kg,save_new_price_by_cubic}.php` | `POST /_admin/_shipping_type/<ชื่อเดียวกัน>` | `legacy.ajax`, `legacy.perm:admin_shipping_type` |
| `load_quick_search.php` | `POST /load_quick_search` | `legacy.ajax`, `legacy.auth` (เดิมไม่ตรวจล็อกอิน) |
| `get_price.php` | `POST /get_price` | `legacy.ajax`, `legacy.perm:view_bol` (ไม่มี role ใดมี view_bol = ปฏิเสธทุกคนเหมือนเดิม) |
| `system_upload.php`, `system_upload_2.php` | `POST /system_upload`, `POST /system_upload_2` | `legacy.ajax`, `legacy.auth` (เดิมไม่ตรวจล็อกอิน) |
| `gateway.php`, `index_close.php`, `get_resource.php` | `GET /gateway`, `GET /index_close`, `GET /get_resource` | สาธารณะ (เพิ่มใน `PUBLIC` ของ RouteSecurityTest — commit foundation แยก) |

helper ที่ใช้ร่วม (CONVENTIONS ข้อ 8 "ยังไม่พอร์ต" ที่ settings เป็นเจ้าของ):

- `getupload()`, `getupload_inline()`, `getuploadmultiple()` → `App\Domain\Settings\UploadBox::getupload() / getuploadInline() / getuploadmultiple()` (HTML/JS ตรงเดิม, ชื่อไฟล์ escape)
- `getproducttype()` → `App\Domain\Settings\ProductTypeSelect::getproducttype()` (เดิมใช้คอลัมน์ `product_type.ship_type` ที่ไม่มีจริง → `<select>` ว่างเสมอ; ไม่มีไฟล์ไหนเรียกแล้ว)
- `_api/image_shrink.php` → `App\Domain\Settings\ImageShrink` (สำเนาจากของกลุ่ม shop — ผู้ merge เลือกตัวเดียวได้)

## ไม่ได้พอร์ต / ตั้งใจให้ต่าง

- เรียกไฟล์หน้าโมดูลตรงๆ (`/_admin/_setting/index`, `/_admin/_exchange/form` ฯลฯ ในตาราง url-map): เดิมไฟล์พวกนี้ไม่ include `system_config.php` เอง → fatal error เมื่อเรียกตรง ใช้ได้เฉพาะผ่าน `index.php?page=` ซึ่งพอร์ตเป็น route หน้าแล้ว
- `/upload/temp/<file>` (ภาพตัวอย่างหลังอัปโหลด): เป็นของ nginx/infra (RewriteRule `^upload/`) — production ต้องให้ nginx ชี้ `upload/temp` ไปที่ `LEGACY_UPLOAD_TEMP_DIR`
- ค่าจาก DB/ผู้ใช้ escape ด้วย `{{ }}` (เดิม echo ดิบ): เห็นต่างเฉพาะข้อความที่มี `& " < >` เช่น `J&T` → `J&amp;T`, ข้อความ setting ใน textarea (`"` → `&quot;`) — แสดงผลเหมือนเดิม และกดบันทึกซ้ำแล้วค่าไม่เพี้ยน (เดิม `&amp;` ใน DB จะกลายเป็น `&`)
- LIKE ในช่องค้นหา grid escape `% _` (CONVENTIONS ข้อ 4)
- `load_quick_search`: โหมดแอดมิน (ไม่กรองเจ้าของ) ต้องมีสิทธิ์ `admin_<prefix.name>`; ไม่มี → ค้นเฉพาะรายการของตัวเอง; ข้อความยืนยัน escape เลขที่/รหัสลูกค้า; ตารางใน `prefix.table_name` ต้องมีคอลัมน์ `id,user,is_delete` (เดิม SQL error = ไม่พบ ผลเท่าเดิม)
- `_admin/_exchange/cancel`: เดิมกดซ้ำได้เงินหยวนคืนซ้ำทุกครั้ง (เทียบ `$data['status']` ก่อนอ่านแถว) และยกเลิกรายการที่อนุมัติแล้วได้ → ใหม่ทำเฉพาะ status ไม่ใช่ -1/2, id ที่ไม่มีจริงไม่ INSERT แถวขยะ
- `_admin/_exchange/save_approved`: เดิมอนุมัติรายการที่ยกเลิกแล้ว (คืนหยวนแล้ว) ได้ → ได้ทั้งหยวนคืนและเงินบาท; ใหม่ตอบ ErrorBox; id ที่ไม่มีจริงไม่ INSERT balance ขยะ; ทั้งสองทำใน transaction + `lockForUpdate`
- อีเมลแจ้งผลแลกเงิน: เดิม `include_once('email_success.php' / 'email_cancel.php')` แต่ไม่มีไฟล์ในโฟลเดอร์ = ไม่เคยส่ง → ไม่ส่งเหมือนเดิม
- `delete.php` (product_type, shipping_type, delete_cal): รับเฉพาะรายการเลขจำนวนเต็ม; รูปแบบอื่นไม่ทำอะไร (เดิม SQL syntax error) แต่ตอบ SuccessBox เหมือนเดิม
- `system_upload*`: ต้องล็อกอิน, ตอบ `text/plain` + `nosniff`, เก็บที่ `legacy.upload.temp_dir` (dev = `storage/app/legacy-upload/temp`, ไม่ขึ้น Azure)
- `_admin/_setting/save` (review 2026-10-18): เดิมคีย์ที่ไม่ส่งมาถูกเขียน `''` ทับทั้ง 16 คีย์ (POST ว่างครั้งเดียว = ล้างเลขบัญชี/อัตราแลกเปลี่ยน — เคยล้าง bench จริง) → ใหม่อัปเดตเฉพาะคีย์ที่อยู่ใน POST body เป็นค่า scalar; ไม่มีเลย = `ErrorBox('ไม่มีข้อมูลที่จะบันทึก')` ไม่แก้อะไร (ฟอร์มจริงส่งครบทุกช่อง → ผลเหมือนเดิม; ช่องที่ส่งมาว่างยังบันทึก `''`)
- `_admin/_product_type/save`, `_admin/_shipping_type/save`: POST ที่ไม่มีช่อง `name` เลย เดิม INSERT แถวว่าง (id ว่าง) หรือล้างทุกคอลัมน์ของ id นั้น → ใหม่ ErrorBox ไม่แก้อะไร
- `system_upload_2`: เดิมไม่ตรวจเนื้อไฟล์ → ใหม่นามสกุลรูป/PDF ต้องเป็นรูป/PDF จริง (finfo) ไม่ใช่ = error แบบเดิม; ชนิดจริงไม่ตรงนามสกุลใช้นามสกุลตามชนิดจริง (เหมือน `system_upload`); xls/xlsx ยังไม่ตรวจเนื้อไฟล์
- `fn_public = "loadAjaxGrid(..., '<page>')"` ใน grid ทั้ง 3: ค่า `page` escape 2 ชั้น (`Input::jsEval`) เพราะ string ถูก `eval()` อีกรอบ (เดิมรอดเพราะ `'`→`''` เท่านั้น)
- grid: ค่า `page` ที่ฝังใน JS ผ่าน `Input::js()` (เดิม echo ดิบ)
- หน้าแอดมินที่ไม่มีสิทธิ์: ได้หน้า `legacy.no-permission` ของ foundation (เดิมแสดงหัวหน้า + ErrorBox แล้วหน้าถูกตัดกลาง)

## ช่องโหว่เดิมที่เจอ (อ่านโค้ดเท่านั้น — ไม่ได้ยิง payload ใส่เว็บเดิม)

1. SQL injection ทุกไฟล์ที่รับค่า POST/GET (setting/save, exchange grid/form/cancel/save_approved, product_type/shipping_type grid/save/delete, load_cal, save_listno, save_new_price_*, load_quick_search) — `delete.php` 3 ไฟล์ `id IN ($id)` ลบ (soft delete) ได้ทั้งตาราง เหมือนเหตุการณ์ save_remove_tracking
2. `_admin/_setting/save.php` ไม่ตรวจสิทธิ์: ลูกค้าที่ล็อกอิน (หรือใครก็ได้ที่ส่ง X-Requested-With) แก้เลขบัญชีรับโอน/อัตราแลกเปลี่ยนได้
3. `load_quick_search.php`: IDOR — ส่ง `current_href=...admin...` หรือ `sa-` เพื่อดูว่ารายการของคนอื่นมีจริงและเป็นของรหัสลูกค้าใด; ไม่ล็อกอินก็ค้นแถว `user = 0` ได้; ชื่อตารางจาก DB ต่อเข้า SQL
4. `system_upload.php` / `system_upload_2.php` ไม่ตรวจล็อกอิน — ใครก็อัปโหลดไฟล์ลง webroot `upload/temp` ได้; `system_upload_2` ไม่ตรวจเนื้อไฟล์
5. `_admin/_exchange/cancel.php` คืนเงินหยวนซ้ำได้ไม่จำกัด / `save_approved.php` อนุมัติรายการที่ยกเลิกแล้วได้ (เงินงอก)
6. Stored XSS: ค่า setting, ชื่อประเภทสินค้า/การจัดส่ง, cancel_remark, detail, ชื่อไฟล์ใน getupload*, ข้อความ quick search (`ConfirmBox` html:true)
7. Reflected JS injection: `$_POST['page']`, `$_POST['txtsearch']` ใน grid; `$_GET['status']` ใน exchange index
8. `getPermission('view_bol')` ใน get_price: ไม่มีโมดูลนี้ = endpoint ตาย (คงไว้ ให้เจ้าของตัดสินใจ); สิทธิ์ของ exchange ใช้ 3 ชื่อปนกัน (`admin_exchange` / `admin_withdrawcash` / `admin_withdrawpoint`) — คงไว้

## การทดสอบ

เทียบ HTML เดิม (:8101) กับใหม่ (:8213) ด้วย session เดียวกัน (แอดมิน zz_fdn_admin, ลูกค้า 137), normalize href + ช่องว่าง:
หน้า setting / exchange (+status, +id 49, +id 0) / product_type (list, create, id 1, id ไม่มีจริง) / shipping_type (list, create, id 3, 13),
grid ทั้ง 3 ตัว (หลายหน้า, page ว่าง/0, คำค้น, สถานะ), load_cal_1/2 (มี/ไม่มีแถว, id ว่าง), gateway, index_close, get_resource, get_price
→ เหมือนเดิมทั้งหมด ยกเว้นข้อ escape ข้างบน; quick search 19 กรณี เหมือนเดิมยกเว้นกรณี IDOR ที่ตั้งใจปิด

SQL injection ทดสอบเฉพาะฝั่ง Laravel บน endpoint อ่าน (quick search, exchange grid) → ไม่มีแถวรั่ว; `tests/Feature/Settings/SettingsRoutesTest.php` ครอบ route/สิทธิ์ และ idList ที่กัน payload `id=910719) OR (1=1`

**ยังไม่ได้ทดสอบเขียน DB** (save/delete/approve/cancel/save_new_price/save_listno/upload ที่บันทึกไฟล์): หยุดก่อนเพราะพบตาราง `bill_payment`, `bill_payment_detail`, `balance`, `refill` ใน admin_jl_bench ว่าง (ดูรายงานส่งกลับ) — ต้องทดสอบด้วยแถว zz_ ก่อน merge

## bench (`bin/bench`, -n 20 -c 1)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/admin/setting` | 343 | 47 |
| `/_admin/_shipping_type/grid` | 36 | 37 |

หน้า setting เร็วขึ้นเพราะ getKey() เดิม 1 query ต่อช่อง (16 ครั้ง) + layout เดิม; grid เล็กเท่าเดิม (overhead คงที่ของ Laravel)

## Review รอบ 2 (2026-10-18)

- เทียบเดิม/ใหม่ (normalize href+ช่องว่าง) ด้วย zz_fdn_admin: หน้า setting/exchange(+status, id)/product_type/shipping_type (list, create, id) ทั้ง th/en/cn, grid 3 ตัว (หน้า 1/2/16/99/ว่าง, คำค้นไม่พบ, สถานะ -1/2), load_cal_1/2 (มี/ไม่มีแถว, id ว่าง) → 0 บรรทัดต่าง
- สิทธิ์: ไม่ล็อกอิน → /login; ลูกค้า (zz_fdn_cust) → ErrorBox สิทธิ์ทุก route แอดมิน, grid ตอบว่าง; ชื่อสิทธิ์ตรง `getPermission()` เดิมทุกไฟล์
- CSRF: Origin/Referer ต่างโดเมน → 403 ทุก route เขียน 13 ตัว; ไม่มี `X-Requested-With` → /403
- SQLi (เฉพาะ Laravel): quote/UNION/`1) OR (1=1`/`\`/`%`/`_` ใน txtsearch, id ของ load_cal, code ของ quick search, ค่า setting, id/arr_id ของ delete → bind ทั้งหมด ไม่มีแถวรั่ว ไม่มี error ใน log
- เขียนจริง (แถว zz_ แล้วลบตาม id): product_type เพิ่ม/แก้/ลบ (arr_id), shipping_type เพิ่ม/แก้ (is_use_rate 0/1 + rate_id[]), save_listno, save_new_price_by_kg/cubic (+ error max<=min, ช่องว่าง), delete_cal (id, arr_id), delete; exchange อนุมัติ (balance +10×4.95 = 49.50 ครั้งเดียว, อนุมัติซ้ำไม่เพิ่ม, รายการยกเลิกแล้ว → ErrorBox); cancel = ไม่มี role ใดมี `admin_withdrawpoint` (ปฏิเสธแม้ role 1 เหมือนเดิม); setting บันทึกทั้งฟอร์มด้วยค่าเดิม → ทุกแถวเหมือนเดิม, บันทึกคีย์เดียว → แถวเดียวเปลี่ยน
- upload: png/jpg/jpeg/pdf ผ่าน; .php/.phtml/.php.jpg/รูปปลอม/`x.png%00.php` ถูกปฏิเสธ; ชื่อ `../../x.png` ไม่ใช้เป็น path (ชื่อสุ่ม 10 ตัวใน temp_dir เท่านั้น)
- test: `tests/Feature/Settings/SettingsSaveTest.php`, `SettingsUploadTest.php` (ไม่แตะ DB: DB facade เป็นตัวบันทึก)
- ยังเหลือ: `UploadBox` มีสำเนาใน 7 กลุ่ม และ `ImageShrink` 2 ชุด (Settings/Shop) — รวมเป็นตัวเดียวภายหลัง; ชื่อไฟล์เดิมที่ `system_upload_2` ตอบกลับ (`[NIDA]<ชื่อเดิม>`) ไม่ escape (text/plain; JS ฝั่งผู้ใช้ต้อง escape เอง)
- bench รอบ 2 (`bin/bench -n 30 -c 1`, zz_fdn_admin, artisan optimize, เฉลี่ย 2 รอบ เดิม → ใหม่): `/admin/setting` 381 → 51 ms, `/admin/exchange` 333 → 49 ms, `/_admin/_product_type/grid` 30.5 → 28.1 ms
