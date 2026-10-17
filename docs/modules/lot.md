# กลุ่ม lot — รายงานการพอร์ต

branch `mod/lot` · worktree `/home/admin/laravel/jl-wt/lot` · port 8208 (`bin/wt-serve lot --bg`)
ไฟล์เดิม: `_lot/*`, `_lot/_message/*`, `_admin/_lot/*` (43 ไฟล์ + RewriteRule 5 ตัว ใน `docs/url-map.md`)

## ไฟล์ของกลุ่ม

| ส่วน | ไฟล์ |
|---|---|
| routes | `routes/modules/lot.php` |
| ลูกค้า | `app/Http/Controllers/Lot/LotController.php`, `resources/views/lot/customer/*` |
| แอดมิน (อ่าน) | `app/Http/Controllers/Lot/Admin/AdminLotController.php`, `resources/views/lot/admin/*` |
| แอดมิน (เขียน) | `app/Http/Controllers/Lot/Admin/AdminLotSaveController.php` |
| พิมพ์ / Excel / อีเมล | `app/Http/Controllers/Lot/Admin/AdminLotPrintController.php`, `app/Domain/Lot/LotPrint.php`, `app/Domain/Lot/LotMailer.php` |
| worker อีเมล (แทน cron_lot_mail.php) | `app/Console/Commands/LotMailWorker.php` → `php8.4 artisan lot:mail-worker [--test]` |
| ตัวช่วย | `app/Domain/Lot/LotLookups.php` (prefix/paging/ดึงข้อมูลประกอบแบบ IN แทน N+1), `app/Domain/Lot/Php74.php` (`==`/ตัวเลขแบบ 7.4, `idList()`) |
| test | `tests/Feature/Lot/LotRoutesTest.php`, `tests/Unit/Lot/Php74Test.php` |

## routes (เดิม → ใหม่)

สิทธิ์: `VIEW` = `legacy.perm:admin_lot,admin_lot_view` (= `getPermission('admin_lot_view') || getPermission('admin_lot')` เดิม), `LOT` = `legacy.perm:admin_lot`
POST ทุกตัว (ยกเว้น `_lot/_message/*`) มี `legacy.ajax` และผ่าน `LegacyCsrf` (Origin/Referer เดียวกัน หรือ XHR — JS เดิมใช้ `$.ajax` จึงผ่าน)

| เดิม | ใหม่ | สิทธิ์ |
|---|---|---|
| `^lot$` → `_lot/index.php` | `GET /lot` | `legacy.perm:lot` |
| `^lot/([0-9]+)$` → `_lot/form.php` | `GET /lot/{id}` | `legacy.perm:lot` |
| `_lot/grid.php` | `POST /_lot/grid` | `legacy.auth` (+ ตรวจ `perm('lot')` ใน controller: ไม่มีสิทธิ์ = ตอบว่างแบบเดิม) |
| `_lot/load_lot_detail.php` | `POST /_lot/load_lot_detail` | `legacy.perm:lot` |
| `_lot/_message/{index,load,load_dept,send}.php` | `GET|POST /_lot/_message/*` → 404 | `legacy.auth` (ดู "ต่างจากเดิม") |
| `^admin/lot$` → `_admin/_lot/index.php` | `GET /admin/lot` | VIEW |
| `^admin/lot/create$`, `^admin/lot/([0-9]+)$` → `_admin/_lot/form.php` | `GET /admin/lot/create`, `GET /admin/lot/{id}` | VIEW |
| `_admin/_lot/grid.php` | `POST /_admin/_lot/grid` | VIEW |
| `_admin/_lot/load_autocomplete_tracking.php` | `POST /_admin/_lot/load_autocomplete_tracking` | VIEW |
| `_admin/_lot/load_autocomplete_user.php` | `POST /_admin/_lot/load_autocomplete_user` (+ `.php` ที่ JS ของ load_lot_memo เรียก) | VIEW |
| `_admin/_lot/load_check_tracking.php` | `POST /_admin/_lot/load_check_tracking` | VIEW |
| `_admin/_lot/load_lastest_lot.php` | `POST /_admin/_lot/load_lastest_lot` | VIEW |
| `_admin/_lot/load_lot_detail.php` | `POST /_admin/_lot/load_lot_detail` | VIEW |
| `_admin/_lot/load_lot_memo.php` | `POST /_admin/_lot/load_lot_memo` | VIEW |
| `_admin/_lot/load_ref_print.php` | `POST /_admin/_lot/load_ref_print` | VIEW |
| `_admin/_lot/load_tracking.php` | `POST /_admin/_lot/load_tracking` | VIEW |
| `_admin/_lot/load_user_rate.php` | `POST /_admin/_lot/load_user_rate` | VIEW |
| `_admin/_lot/save.php` | `POST /_admin/_lot/save` | VIEW |
| `_admin/_lot/save_delete_lot_memo.php` | `POST /_admin/_lot/save_delete_lot_memo` | VIEW |
| `_admin/_lot/save_file_lot.php` | `POST /_admin/_lot/save_file_lot` | VIEW |
| `_admin/_lot/save_lot_memo.php` | `POST /_admin/_lot/save_lot_memo` | VIEW |
| `_admin/_lot/save_lot_name.php` | `POST /_admin/_lot/save_lot_name` | VIEW |
| `_admin/_lot/save_lot_name_admin.php` | `POST /_admin/_lot/save_lot_name_admin` | VIEW |
| `_admin/_lot/save_lot_ship_amount.php` | `POST /_admin/_lot/save_lot_ship_amount` | VIEW |
| `_admin/_lot/save_new_lot.php` | `POST /_admin/_lot/save_new_lot` | VIEW |
| `_admin/_lot/save_print.php` | `POST /_admin/_lot/save_print` | VIEW |
| `_admin/_lot/save_remove_tracking.php` | `POST /_admin/_lot/save_remove_tracking` | VIEW |
| `_admin/_lot/save_status_lot.php` | `POST /_admin/_lot/save_status_lot` | VIEW |
| `_admin/_lot/save_status_lot_date.php` | `POST /_admin/_lot/save_status_lot_date` | VIEW |
| `_admin/_lot/save_status_reset_tracking.php` | `POST /_admin/_lot/save_status_reset_tracking` | VIEW |
| `_admin/_lot/save_status_tracking.php` | `POST /_admin/_lot/save_status_tracking` | VIEW |
| `_admin/_lot/print_all.php` | `GET /_admin/_lot/print_all?lot=` (.xls) | VIEW |
| `_admin/_lot/print.php` | `GET /_admin/_lot/print?lot=` | LOT |
| `_admin/_lot/print_receive.php` | `GET /_admin/_lot/print_receive?id=` | LOT |
| `_admin/_lot/email_lot.php` | `GET /_admin/_lot/email_lot?id=` (เข้าคิว `lot_mail_queue`) | LOT |
| `_admin/_lot/delete.php` | `POST /_admin/_lot/delete` | LOT |
| `_admin/_lot/save_verified.php` | `POST /_admin/_lot/save_verified` | LOT + user id 1/2 เท่านั้น (แบบเดิม) |
| `_admin/_lot/cancel.php` (ยกเลิก refill) | `POST /_admin/_lot/cancel` | `legacy.perm:admin_refill` |
| `_admin/_lot/cron_lot_mail.php` | `GET|POST /_admin/_lot/cron_lot_mail` → 403 "cron only"; งานจริง = `php8.4 artisan lot:mail-worker` | LOT |
| `_admin/_lot/email.php` | `GET|POST /_admin/_lot/email` → 403; เนื้อหาเมลอยู่ใน `LotMailer::sendMail()` | LOT |

## การตรวจ (2026-10-18, :8101 เดิม vs :8208 ใหม่, DB `admin_jl_bench`)

- **อ่าน** (ลูกค้า user 137 / zz_fdn_cust 11937, แอดมิน zz_fdn_admin 11938): `/lot`, `/lot/{id}`, `_lot/grid`, `_lot/load_lot_detail`, `/admin/lot`, `/admin/lot/{id}`, `_admin/_lot/grid`, `load_*`, `print*` — HTML ตรงกับเดิม ต่างเฉพาะช่องว่าง/ขึ้นบรรทัด (diff หลัง normalize host + whitespace)
- **เขียน**: ทดสอบกับ Laravel เท่านั้น ด้วยแถว zz ที่สร้างเอง (lot `zz_lot_test`, tracking `zztrk1/zztrk2`, refill `zz_lot_cancel_test`) แล้วนับแถวที่เปลี่ยนด้วย SELECT ที่กรองช่วงเวลา/ id — มีแต่แถวที่ตั้งใจเท่านั้น
  - `save` (insert/update/craft/ผู้ใช้ไม่พบ/craft ไม่พบ), `save_new_lot`, `delete` (ล็อตมี tracking → error, ว่าง → is_delete=1), `save_verified` (user 11938 → ไม่มีสิทธิ์, user 1 → คำนวณใหม่ + is_verified), `save_file_lot` (copy ไฟล์ temp → upload, ชื่อไฟล์ `../` ไม่ copy), `save_print`, `cancel`, `save_remove_tracking`, `email_lot` (เข้าคิว 1 แถว), worker (`--test` และส่งจริงผ่าน log mailer — รันใน transaction แล้ว rollback)
  - ค่าที่ลง DB เทียบกับ SQL ที่ไฟล์เดิมสร้าง (อ่านจากโค้ด): `'`→`''`, ตัด `\`, numberformat แบบตัดทศนิยม, `date_warehouse = NOW()` เมื่อเดิมเป็นศูนย์, ship = type ของล็อต ฯลฯ
- **เว็บเดิมบน :8101 เขียน DB ไม่ได้อยู่แล้ว**: `mysqlquerys()` ทุกคำสั่งที่ไม่ใช่ SELECT เรียก `goLog()` → `fopen('/home/admin/web/portal.jlcargoth.com/public_html/logs/<วันที่>.log')` ซึ่งเปิดไม่ได้บน bench → `exit("Can't open ...")` ก่อนรัน SQL
  (ทดลอง `save_new_lot.php` 1 ครั้ง: ตอบ `Can't open ...` และไม่มีแถวใหม่) — **ถ้าไฟล์ log ของวันนั้นเปิดได้ เว็บ bench จะเขียน log ลง webroot production** ⇒ ไม่ได้ยิง write ไปเว็บเดิมอีก และไม่ยิง payload ใดๆ ไปเว็บเดิม (กฎหลังเหตุการณ์ tracking)
- SQL injection: ทดสอบเฉพาะ Laravel — `_lot/grid` (txtsearch `x' OR 1=1 -- `) ตอบปกติ, `print?lot=5441) OR (1=1` ไม่แก้แถวใด, `delete arr_id=,5442) OR (1=1` ไม่แก้แถวใด; `Php74::idList()` ปฏิเสธ payload ทุกแบบ (unit test)
- ผู้ไม่ล็อกอิน → 302 `/login` (หน้า, ajax, print, email_lot); POST ข้าม origin → 403
- `php8.4 vendor/bin/phpunit` ผ่าน (รวม RouteSecurityTest, RouteModulesTest, LotRoutesTest, Php74Test)

## bench (`bin/bench`, -n 30 -c 1, opcache + `artisan optimize`)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/admin/lot` (แอดมิน) | 306 | 41 |
| `_admin/_lot/grid` page 1 | 3359 | 46 |
| `/lot` (ลูกค้า 137) | 300 | 35 |
| `_lot/grid` page 1 (ลูกค้า 137) | 2841 | 239 |
| `_admin/_lot/load_lot_detail` id=5400 (-n 20) | 175 | 46 |

`_lot/grid` ใหม่ยังใช้เวลา ~240 ms เพราะต้องนับ tracking ของผู้ใช้ต่อล็อต (ไม่มี index `tracking(user, lot_id)`) — ข้อเสนอ: เพิ่ม index `(user, lot_id, is_delete)` (ไม่ได้สร้างเอง)

## ต่างจากเดิมโดยตั้งใจ

- ทุก query bind ค่า; รายการ id ที่เดิมฝังลง `IN (...)` ตรงๆ (`delete`, `save_remove_tracking`, `save_status_*`, `print`, `print_all`) รับเฉพาะตัวเลขล้วน — ไม่ใช่ตัวเลข = ไม่แก้อะไร (เดิมเป็น SQL error หรือ injection) แต่ตอบข้อความสำเร็จเหมือนเดิม
- ค่าจาก DB/ผู้ใช้ใน HTML/JS/อีเมล ถูก escape (เดิม echo ดิบ)
- `/lot/{id}` ลูกค้า: แสดงหัวล็อตเฉพาะล็อตที่มี tracking ของตัวเอง (เดิมเปิดหัวล็อตของ id ใดก็ได้); รายการ tracking กรอง `user = Legacy::userId()` เหมือนเดิม
- `_lot/_message/*`: เดิม `require '../system_config.php'` ผิด path → HTTP 500 ทุกครั้ง (ใช้งานไม่ได้) → 404; แชทจริงอยู่กลุ่ม messaging
- `delete` ล็อตที่ยังมี tracking: เดิม `getError()` ไม่มีอาร์กิวเมนต์ = fatal (HTTP 500 เปล่า) → ตอบ `ErrorBox('');`
- `save_file_lot`: เดิม `copy('../../upload/temp/'.$value, ...)` โดยไม่ตรวจชื่อ (path traversal) → รับเฉพาะชื่อไฟล์ล้วน; dev เขียน `storage/app/legacy-upload` (driver local, ไม่ตอบ JS อัป Azure); production (driver azure) ตอบ JS `_api/blockblob_ajax` แบบเดิม
- `cron_lot_mail.php` / `email.php`: เดิมเปิดผ่านเว็บได้โดยไม่ล็อกอิน (ใครก็สั่ง worker/ส่งเมลได้) → route ตอบ 403, worker เป็นคำสั่ง artisan; ต้องตั้ง cron `* * * * * php8.4 artisan lot:mail-worker` ตอนสลับ (ยังไม่ได้ตั้ง — ห้ามแตะ cron)
- อีเมล: ส่งผ่าน Mail ของ Laravel (dev `MAIL_MAILER=log`, และถ้า `legacy.external.enabled` = false บังคับ log mailer) — ไม่มีการส่งจริง; ข้อความ/HTML (header/center/footer + CRLF ของ email.php) เท่ากับเดิมตามโค้ด
- LINE: `sendLine()` เดิมเป็นฟังก์ชันว่าง → ไม่ส่ง (log ไว้เท่านั้น)
- หน้า GET ของแอดมิน (`print`, `print_receive`, `email_lot`) เมื่อไม่มีสิทธิ์: เดิมพิมพ์ข้อความ `ErrorBox(...)` เปล่าๆ ในแท็บใหม่ → หน้า no-permission มาตรฐานของ `legacy.perm`; `print_all` เดิมตอบว่าง → เช่นเดียวกัน; ไม่ล็อกอิน → `/login`
- `print`, `print_all`, `email_lot` (GET ที่เขียนข้อมูล: `is_print`, เข้าคิวส่งเมลถึงลูกค้าทั้งล็อต): คำขอที่มาจากเว็บอื่น (`Sec-Fetch-Site: cross-site/same-site` หรือ Referer ต่าง host) → 403 — `LegacyCsrf` ตรวจแค่ POST; เดิมแค่ `<img src=".../email_lot?id=N">` ในหน้าที่แอดมินเปิดก็สั่งส่งเมลได้ (review 2026-10-18)
- `delete` `id=5,6`: เดิมนับ tracking เฉพาะ `lot_id = '5,6'` (= 5) แต่ลบทุก id ใน `IN (5,6)` → ลบล็อตที่ยังมี tracking ได้ → ต้องไม่มี tracking ทุกล็อตที่จะลบ (JS เดิมส่ง id เดียว ผลเหมือนเดิม)
- `print_all`: ชื่อไฟล์ใน `Content-Disposition` เหลือแค่ตัวเลขและ `,` (เดิม header injection ได้)
- `load_check_tracking` ช่องว่าง: เดิม `LIKE '%%'` ดึง tracking ทั้งระบบ (~470k แถว) จนหน้าค้าง → ถือว่าไม่พบข้อมูล
- `save` / `save_verified` ห่อ `DB::transaction()` (เดิมไม่มี); `email_lot` เป็น UPDATE + INSERT IGNORE (idempotent) ไม่ได้ห่อ
- N+1 ที่แก้: grid แอดมิน (COUNT ทั้งตาราง + 3 query ต่อแถว), grid ลูกค้า (subquery COUNT ต่อแถว), แถวลูก craft (`header = id`) ใน detail/print, autocomplete (2 query ต่อแถว), worker (ไม่เปลี่ยน — ทีละคิวแบบเดิม)
- `save_verified` ยังเรียก `Pricing::calPriceAutoDiff()` ทีละ tracking (ตรรกะคำนวณต่อแถวของฐานร่วม)

## ช่องโหว่เดิมที่เจอ (แก้ในพอร์ตแล้ว)

1. SQL injection ทุกไฟล์ที่รับ POST/GET (ต่อ string ตรงๆ) — ร้ายแรงสุด: `save_remove_tracking.php`, `delete.php`, `save_status_tracking.php`, `save_status_reset_tracking.php` ฝัง `id` ลง `WHERE id IN (...)` ของ UPDATE โดยไม่ quote → `id=1) OR (1=1` แก้ทั้งตาราง (เหตุการณ์ที่ล้าง `tracking.lot_id` ทั้ง 546k แถวใน bench) และ `print.php` / `print_all.php` (`UPDATE lot SET is_print` + SELECT) ผ่าน GET `?lot=`
2. `cron_lot_mail.php` และ `email.php` ไม่ตรวจล็อกอิน/สิทธิ์
3. `save_file_lot.php` path traversal ใน `copy()` (อ่านไฟล์ใดๆ ที่ PHP อ่านได้ไปไว้ใน `upload/` ที่เปิดสาธารณะ)
4. Stored XSS: ชื่อล็อต, tracking, remark, memo ฯลฯ ถูก echo ดิบในหน้าแอดมิน/ลูกค้า/อีเมล/JS (`save_print` ฝังค่าลง `window.open('...')`)
5. `_lot/form.php` (IDOR) แสดงหัวล็อตของ id ใดก็ได้
6. `getUserID()` (`explode('&')[1]`) ใน `_lot/grid.php`, `_lot/load_lot_detail.php` — username ที่มี `&` ได้ id ของคนอื่น (CONVENTIONS ข้อ 3.5) → ใช้ `Legacy::userId()`
7. `print_all.php` header injection ผ่าน `filename=$id`
8. ไม่มี CSRF ทุก POST (ใหม่: `LegacyCsrf`) และ GET ที่เขียนข้อมูล (`email_lot`, `print`, `print_all`) ยิงข้ามเว็บได้ (ใหม่: ตรวจ `Sec-Fetch-Site`/Referer)
10. `delete.php` ตรวจ tracking เฉพาะ id แรกของรายการ แต่ลบทุก id
9. `goLog()` เขียน log ไปไฟล์ใน webroot production (`/home/admin/web/portal.jlcargoth.com/public_html/logs/`) — ไฟล์ log ที่อยู่ใต้ webroot อาจเปิดอ่านได้จากเว็บ (ไม่ได้ตรวจ production ตามกติกา) และ bench copy ชี้ path เดียวกัน

## ไม่ได้พอร์ต / ค้าง

- ไม่ได้ตั้ง cron ของ `lot:mail-worker` (ต้องทำตอนสลับ — นอกขอบเขต)
- `_lot/_message/*` ตอบ 404 (เดิมใช้งานไม่ได้)
- เทียบผลการเขียนกับเว็บเดิมแบบยิงจริงไม่ได้ (เว็บเดิมบน bench exit ก่อนรัน SQL) — เทียบด้วยการอ่านโค้ด + SQL ที่ไฟล์เดิมสร้าง
- `save_verified` อนุญาตเฉพาะ user id 1, 2 แบบเดิม (ฮาร์ดโค้ด) — ให้เจ้าของตัดสินใจว่าจะเปลี่ยนเป็นสิทธิ์หรือไม่
