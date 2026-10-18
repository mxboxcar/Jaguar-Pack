# กลุ่ม users — รายงานการพอร์ต

branch `mod/users` · worktree `/home/admin/laravel/jl-wt/users` · port 8210

ไฟล์เดิม: `_profile/*`, `_register/*`, `_admin/_user/*`, `_admin/_user_type/*`, `_admin/_role/*`, `register.php`, `lost_password.php`, `password.php`, `system_check_username.php` (42 ไฟล์ใน `docs/url-map.md`)

## 1. URL ที่พอร์ต

| เดิม | ใหม่ (route) | สิทธิ์ |
|---|---|---|
| `register` → register.php | `GET /register` | สาธารณะ (ล็อกอินอยู่ → dashboard) |
| `lostpassword` → lost_password.php | `GET /lostpassword`, `GET /lost_password` | สาธารณะ |
| password.php | `GET /password` (ตอบว่าง) | สาธารณะ |
| system_check_username.php | `GET /system_check_username` | สาธารณะ + ajax |
| _register/save.php | `POST /_register/save` | สาธารณะ + ajax |
| _register/lost_password.php | `POST /_register/lost_password` | สาธารณะ + ajax |
| `profile`, `profile/` → _profile/index.php | `GET /profile` | legacy.auth + `profile` (ใน controller) |
| _profile/load_user_type_detail, load_role_detail, pin | `POST /_profile/...` | legacy.auth + `profile` (ไม่มีสิทธิ์ = ว่าง) |
| _profile/save.php | `POST /_profile/save` | legacy.auth (เดิมไม่ตรวจสิทธิ์) — แก้ได้เฉพาะแถวตัวเอง |
| `admin/user`, `admin/user/status/N` → _admin/_user/index.php | `GET /admin/user`, `/admin/user/status/{status}` | `admin_user_view` \|\| `admin_user` (ใน view แบบเดิม) |
| `admin/user/create`, `admin/user/N` → form.php | `GET /admin/user/create`, `/admin/user/{id}` | legacy.perm:admin_user,admin_user_view (view = อ่านอย่างเดียว) |
| _admin/_user/grid, load_user_type_detail | `POST` | admin_user_view \|\| admin_user (ไม่มีสิทธิ์ = ว่าง) |
| _admin/_user/load_role_detail, rate_history | `POST` | admin_user |
| _admin/_user/save | `POST` | admin_user (ตรวจหลังเช็ครหัสมี & แบบเดิม) |
| _admin/_user/save_approved, cancel, delete | `POST` | legacy.perm:admin_user |
| _admin/_user/btn_viewpassword, btn_resetpassword, btn_pinreset, btn_unbanned | `POST` | admin_user + อยู่ใน setting `read_password_pin` + PIN แอดมิน |
| `admin/user_type`(+create, N) + grid/save/delete | `GET/POST` | admin_user_type |
| `admin/role`(+create, N) + grid/save/delete | `GET/POST` | admin_role |

ไม่ทำ route (ตั้งใจ):
- ไฟล์ที่ถูก `require` เท่านั้น: `_register/email.php`, `_register/email_lost_password.php`, `_admin/_user/email.php`, `email_approved.php`, `email_cancel.php`, `btn_email_lost_password.php` → `App\Domain\Users\UsersMailer`; `_admin/_user/rate_history_lib.php` → `App\Domain\Users\RateHistory`
  (เดิมเปิดตรงได้โดยไม่ล็อกอิน — ดูช่องโหว่ข้อ 7)
- path ของไฟล์เนื้อหาหน้า (`/_profile/index`, `/_admin/_user/form` ฯลฯ) ใช้ได้เฉพาะผ่าน index.php เดิม → ใช้ URL ของ RewriteRule แทน

## 2. ผลเทียบกับเว็บเดิม (:8101, session เดียวกัน)

เทียบหลังแทน href และยุบช่องว่าง (ไฟล์เดิมเป็น CRLF): **เหมือนกันทุกหน้า**
- หน้า: profile (ลูกค้า/แอดมิน), admin/user (+status/1, ลูกค้าเห็นหัว+ErrorBox), admin/user/create, /137, /11915 (ราคาตั้งเอง), /1900 (มีผู้แนะนำ), /1385 (มีไฟล์บัตร), /999999, admin/role (+create, /3), admin/user_type (+create, /3, /1698), register, lostpassword
- ajax: user grid 5 แบบ (ค้นหา/role/type/สถานะ/หน้า 2), role grid 2, user_type grid 2, load_role_detail, load_user_type_detail, profile load_* , rate_history (byte เดียวกัน), system_check_username (byte เดียวกัน)
- ต่าง: หน้าฟอร์มที่ไม่มีสิทธิ์ แสดง layout ครบ (ของฐานร่วม `legacy.no-permission`; เดิม exit กลางหน้า), ค่า DB ที่มี `'` ถูก escape เป็น `&#039;`

การเขียน DB — ทดสอบกับพอร์ตใหม่เท่านั้น (เว็บเดิมบน :8101 เขียน log ลง webroot production ไม่ได้แล้ว exit ก่อน query และห้ามยิง write/payload ไปเว็บเดิม) ใช้แถว `zz_` แล้วตรวจจำนวนแถวก่อน/หลังด้วย SELECT ที่จำกัดขอบเขต:
สมัคร (1 user + user_count +1 + crawl_guard_signup 1), error ทุกแบบ, ลืมรหัส (เปลี่ยนเฉพาะแถวนั้น), โปรไฟล์ save/&/username=รหัส, PIN ใหม่/ซ้ำ/ผิด (บันทึก ips)/ถูก/callback,
แอดมินสร้างผู้ใช้ราคาตั้งเอง (user + user_type ลูก + user_type_detail 6 + rate history), แก้ไข (ประวัติราคาครบ), approve/cancel/delete (แถวเดียว), btn_* ทั้ง 4 (เพิ่มรหัส ZZ_FDN_ADMIN ใน setting ชั่วคราวแล้วคืนค่าเดิม ตรวจ md5),
user_type สร้าง/แก้/ลบ/ลบกลุ่มที่ชน role (500), role สร้าง/แก้/ลบ (500 แบบเดิม) — ลบข้อมูลทดสอบแล้ว จำนวนแถวทุกตารางกลับเท่าเดิม
- payload SQL injection ยิงเฉพาะ endpoint อ่านของพอร์ตใหม่ (grid, load_role_detail, system_check_username) — bind ทั้งหมด ไม่มี error; ตัวแยก id ของ delete ทดสอบใน phpunit

## 3. bench (`bin/bench`, -n 30 -c 1, `artisan optimize`)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| /profile (ลูกค้า 137) | 345 / 340 | 42 / 37 |
| /admin/user/137 | 413 / 516 | 57 / 57 |
| _admin/_user/grid page 1 | 197 / 179 | 91 / 82 |
| _admin/_user_type/grid page 1 | 234 / 230 | 117 / 117 |

user_type grid ยังใช้ subquery นับผู้ใช้ต่อแถวแบบเดิม (ไม่มี index `user.type`) — ข้อเสนอ: `ALTER TABLE user ADD INDEX (type)`, `ADD INDEX (role)` (ไม่ได้สร้างเอง)

## 4. ต่างจากเดิมโดยตั้งใจ

- **`_profile/pin` จำกัดการเดา PIN** (เจ้าของอนุมัติ 2026-10-18, รายงานข้อ 4.21): `method=verified` ใส่ผิดครบ 5 ครั้งภายใน 15 นาที ต่อ (ผู้ใช้ + IP) หรือ 10 ครั้งต่อผู้ใช้ (ทุก IP รวม — กันเปลี่ยน IP) → ล็อกจนครบเวลา แม้ใส่ PIN ถูก: `ErrorBox('ใส่ PIN ผิดเกินจำนวนครั้งที่กำหนด<br />กรุณาลองใหม่อีกครั้งใน N นาที', 'CloseLoading()')`
  - ระหว่างยังไม่ล็อก ข้อความผิดยังเป็น `ErrorBox('PIN จ่ายเงินไม่ถูกต้อง', 'CloseLoading()')` และยังบันทึกแถว `ips` ให้ jail เหมือนเดิม; ใส่ถูก = ล้างตัวนับ
  - `App\Domain\Users\PinThrottle` (Laravel `RateLimiter`, cache store ปกติ — production `file`), ค่าตั้ง `config/legacy.php` `pin_throttle` / `.env` `LEGACY_PIN_MAX_ATTEMPTS`, `LEGACY_PIN_MAX_ATTEMPTS_PER_USER`, `LEGACY_PIN_DECAY_MINUTES`
  - endpoint อื่นที่ตรวจ `paymentpin` เอง ผ่าน `App\Domain\Payment\PinBox::guard()` ตัวเดียว (`_point/save_withdraw`, `_admin/_user/btn_*`) ใช้ตัวนับเดียวกัน (fix/followups) — สลับ endpoint ไม่ได้เดาเพิ่ม; payment/alipay/money-move/balance ไม่มีการตรวจ PIN ฝั่ง server (เดิม comment ไว้) · test: `tests/Feature/Users/PinBoxThrottleTest.php`
  - test: `tests/Feature/Users/ProfilePinThrottleTest.php` · ตรวจสด :8298 (zz_fdn_cust, X-Real-IP 10.250.77.21–31 สลับ IP): 10 ครั้งแรก "PIN จ่ายเงินไม่ถูกต้อง", ครั้งที่ 11 ล็อก (ลบแถว ips ทดสอบ 10 แถวตาม id แล้ว, ไม่เกิด ips_ban)
- SQL ทั้งหมด bind ค่า, LIKE escape `% _ \` (เดิมผู้ใช้พิมพ์ `_` = wildcard)
- N+1: ฟอร์มผู้ใช้/กลุ่มราคา (product_type × user_type_detail ต่อแถว → 2 query), grid ผู้ใช้ (ชื่อกลุ่มแม่ต่อแถว → join), role detail (วนเทียบ → map), INSERT user_type_detail / role_on_detail ทีละแถว → insert ครั้งเดียว
- งานหลายตารางอยู่ใน transaction (สมัคร, save ผู้ใช้, user_type, role, unbanned); สมัครล็อกแถว `user_count` (เดิมรหัสลูกค้าซ้ำได้เมื่อสมัครพร้อมกัน)
- `_admin/_user_type/save`: `UPDATE user_type SET is_default = 0` ทั้งตาราง → เฉพาะแถวที่ไม่ใช่ 0/NULL (ผลเท่าเดิม)
- btn_* ต้องอยู่ใน `read_password_pin` ด้วย (เดิมซ่อนแค่ปุ่ม) และ PIN เทียบแบบ strict (`PinBox::guard`)
- btn_unbanned/resetpassword: IP ว่างใน `ips.text` ไม่ถูกใช้ทำ `LIKE '%%'` (เดิมลบทั้งตาราง ips/ips_ban)
- ข้อความ "ไม่อนุญาตให้ใช้คำ" ตอนสมัคร: เดิมเป็น UTF-8 ซ้อน (อ่านไม่ออก) → ภาษาไทยปกติ
- `_profile/pin`: callback/eq escape ก่อนฝังใน JS, ตั้ง PIN ใหม่มีเงื่อนไข `pin` ว่างใน WHERE, log PIN ที่ผิดเป็น `***`
- `_register/save` upload_file: รับเฉพาะชื่อไฟล์ใน temp_dir; driver azure ยังไม่พอร์ต (log warning — กลุ่ม settings)
- อีเมล: Laravel Mail (dev = log), escape ค่าจาก DB, dev ปิดรหัสผ่านใน log เป็น `***`
- rate history: ไม่ CREATE TABLE เอง (ไม่มีตาราง = ข้าม)
- user_type/role delete ที่เดิม PHP fatal (`getError()` ไม่มีอาร์กิวเมนต์) → HTTP 500 ว่างเหมือนเดิม (role ที่มีอยู่ลบไม่ได้เลย, user_type ที่ id ตรงกับ role ลบไม่ได้) — ให้เจ้าของตัดสินใจว่าจะแก้หรือไม่
- ค่าที่คงพฤติกรรมเดิม (บันทึกไว้): profile/save และ admin save (แก้ไข) ไม่บันทึก email/phone; admin สร้างผู้ใช้ใช้ username เป็น code; ลืมรหัสผ่านลบ ips ของ IP ผู้ขอ

## 5. ช่องโหว่เดิมที่พบ (วิเคราะห์จากโค้ด ไม่ได้ยิงเว็บเดิม)

1. **SQL injection** `_admin/_user/delete.php`, `_admin/_user_type/delete.php`, `_admin/_role/delete.php`: `id IN (".$id.")` / `arr_id` ไม่อยู่ในเครื่องหมายคำพูด → `id=1) OR (1=1` = soft-delete ผู้ใช้ทั้งระบบ (แก้: รับเฉพาะรายการตัวเลข)
2. **ยึดบัญชีผ่าน `getUserID()`**: `_profile/save.php` / `_profile/pin.php` ใช้ `WHERE id = getUserID()` (explode('&')[1]) และสมัคร username ที่มี `&` ได้ → แก้ชื่อ/ที่อยู่/**รหัสผ่าน**/PIN ของผู้ใช้อื่น (แก้: `Legacy::userId()`)
3. **ดูรหัสผ่านลูกค้าได้ทุกคนที่มี admin_user**: btn_viewpassword ฯลฯ ไม่ตรวจ `read_password_pin` (แก้แล้ว) และรหัสผ่านเก็บแบบถอดกลับได้ + ส่งทางอีเมลเป็น plaintext (คงไว้ — ต้องเปลี่ยนระบบรหัสผ่าน)
4. **Path traversal → ไฟล์บนเซิร์ฟเวอร์ขึ้น Azure สาธารณะ**: `_register/save.php` `UploadAzure('../upload/temp/'.$_POST['upload_file'])` เช่น `../../system_config.php` (แก้แล้ว)
5. **Stored/Reflected XSS**: ชื่อ/อีเมล/ที่อยู่/ชื่อกลุ่ม/role echo ดิบใน grid/ฟอร์ม/หน้าสมัคร, `txtsearch` และ `page` สะท้อนกลับใน value/JS, `cancel_remark` ในอีเมล, `callback` ของ PIN ถูก eval (แก้: escape ทั้งหมด)
6. **ไม่มี CSRF** ทุก endpoint ที่เขียนข้อมูล (แก้โดย LegacyCsrf ของฐานร่วม)
7. **ไฟล์อีเมลเปิดตรงได้โดยไม่ล็อกอิน**: `/_admin/_user/email_approved.php?id=N` (และ email.php, email_cancel.php, btn_email_lost_password.php, `_register/email*.php` ผ่าน `extract($_GET)`) → ส่งอีเมลพร้อมรหัสผ่านให้ลูกค้าคนใดก็ได้ซ้ำๆ (mail bombing) — ไม่พอร์ตเป็น route
8. **ลืมรหัสผ่าน**: ใครก็รีเซ็ตรหัสของ username ใดก็ได้ (ไม่มี token ยืนยัน) และลบประวัติล็อกอินผิดของ IP ตัวเอง → ปลด jail ตัวเองเพื่อ brute force ต่อได้ (คงไว้ — ต้องให้เจ้าของตัดสินใจ)
9. unbanned/reset: `ips.username LIKE '%code%'` / `text LIKE '%ip%'` จับคู่หลวม (JL01 ตรงกับ JL0123, 1.2.3.4 ตรงกับ 11.2.3.45) → ปลด/ลบประวัติของคนอื่น; IP ว่าง = ลบทั้งตาราง (แก้เฉพาะกรณี IP ว่าง)
10. รหัสลูกค้าซ้ำได้เมื่อสมัครพร้อมกัน (แก้: lock), `system_check_username` เปิดให้เดา username ได้ (คงไว้)
11. log: PIN ที่พิมพ์ผิดถูกเขียนลง log เป็น plaintext (แก้: `***`)
12. **สมัครเข้ากลุ่มราคาของคนอื่น**: `_register/save.php` ตรวจ `user_type` แค่ `is_show` → แก้ POST `user_type` เป็นกลุ่มราคาตั้งเองของลูกค้าอื่น (`child <> 0`, is_show = 1 ทั้ง ~1,690 กลุ่ม) หรือกลุ่มที่ลบแล้ว ได้เรตพิเศษทันที (แก้: รับเฉพาะ child = 0, is_delete = 0 แบบที่หน้าสมัครแสดง)
13. unbanned/reset ของลูกค้าที่ `code` ว่าง (แอดมินแก้ code เป็นว่างได้): `username LIKE '%%'` = ทุกแถว → ลบ ips/ips_ban ของทุก IP (แก้: code ว่าง = ไม่ลบ ips/ips_ban, unbanned ยังปลด ips_ban_user/is_enable ให้)
14. `_profile/pin` (method=verified) เดา PIN 6 หลักได้ไม่จำกัด (มีแค่แถว ips ที่ jail นับ) — **แก้แล้ว 2026-10-18** (ดูข้อ 4: PinThrottle)

## 5.1 รีวิวรอบ 2 (adversarial) — เทียบ HTTP เดิม/ใหม่อีกรอบ

- หน้า/grid/ajax ~120 คำขอ (ลูกค้า 137, zz_fdn_cust, แอดมิน zz_fdn_admin/1, role 5 = admin_user_view, ไม่ล็อกอิน, th/en/cn) เหมือนเดิมหลังยุบช่องว่าง
- แก้: `pageJs()` คืน `1.5` / `2abc` แบบเดิม (เดิมตัดเหลือตัวเลข → JS ลบแล้วโหลดหน้าผิด), `system_check_username` ไม่ส่ง fieldId → `null` แบบเดิม, ข้อ 12–13 ข้างบน
- ยังต่าง (ฐานร่วม): ajax ที่ยังไม่ล็อกอินได้ 302 → /login (เดิม 200 ว่าง); pager ของ `page=1.5` (Paging::getpaging ปัดเป็นจำนวนเต็ม) — ไม่มี UI ส่งค่านี้
- route ajax/หน้า index ของแอดมินที่เดิม `exit()` เงียบ/พิมพ์หัวก่อน ตรวจสิทธิ์ใน controller/view (ไม่ใช่ `legacy.perm`) เพื่อให้ผลเหมือนเดิม — ทดสอบด้วย session ลูกค้าแล้วได้ว่างทุกตัว

## 6. ไฟล์ร่วม / สำเนา

- foundation (commit แยก): `bootstrap/app.php` ไม่ trim/แปลง '' (patch เดียวกับกลุ่มอื่น), `tests/Feature/RouteSecurityTest.php` เพิ่ม route สาธารณะ 7 ตัวใน `PUBLIC`
- สำเนาในกลุ่ม (รวมเป็นของกลางตอน merge): `App\Domain\Users\Php74` (= Tracking/Payment), (PinBox รวมแล้ว → `App\Domain\Payment\PinBox`), `UploadBox` (เจ้าของ settings)
- test: `tests/Feature/Users/UsersPortTest.php` (route + middleware ครบ, ตัวแยก id, paging, diff ประวัติราคา, Php74)

## 7. E2E admin write-flow (2026-10-18, fix/admin-writes)

- กันดับเบิลคลิก (`legacy.once`): `_admin/_user/save` (เดิมได้ผู้ใช้ username ซ้ำ 2 แถว), `_admin/_role/save`, `_admin/_user_type/save` (role/กลุ่มราคาซ้ำ)
  — แก้ไขด้วย payload เดิมซ้ำภายใน 8 วินาทีได้ ErrorBox "ส่งซ้ำ" (ไม่มีผลต่อข้อมูล) · test: `tests/Feature/SubmitGuardTest.php`
- ยังเหมือนเดิม (ตั้งใจคง): `_admin/_role/delete` ตอบ 500 ว่างเสมอเมื่อ role มีอยู่จริง (ลบ role ไม่ได้), บันทึกผู้ใช้ด้วย option_price = 1 สร้างกลุ่มราคาใหม่ทุกครั้ง
