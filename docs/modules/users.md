# กลุ่ม users — รายงานการพอร์ต

## SR (2026-10-20)

branch `mod/users` · worktree `/home/admin/laravel/sr-wt/users` · port 8310 · เทียบกับ SR เดิม :8111 (session เดียวกัน, ผู้ใช้ 11938 แอดมิน / 6 role 12 / 11 role 7 ไม่มีสิทธิ์ / 24, 22, 11937 ลูกค้า)

### ไฟล์ตาม docs/sr-diff.md

| ไฟล์ | ผล |
|---|---|
| `_admin/_role/*` (5), `_admin/_user_type/*` (5), `_admin/_user/{index,cancel,delete,load_role_detail,rate_history,rate_history_lib,btn_*}` , `_profile/{load_role_detail,load_user_type_detail,pin}`, `_register/lost_password`, `system_check_username` | same → ไม่ต้องแก้ (เทียบผลแล้วตรง) |
| `_admin/_user/grid.php` (diff 32) | ทำแล้ว: ไม่แสดงผู้ใช้ที่ยกเลิก (`is_approved != -1` เสมอ), ค้น lineid/wechatid, ตัวกรอง `99` = "ตั้งเอง" (`type NOT IN` กลุ่มหลัก), คอลัมน์ Line ID + ยอดเงินในระบบ, เรียง `id DESC` |
| `_admin/_user/form.php` (diff 408) | ทำแล้ว: SR ไม่มีหน้าอ่านอย่างเดียว (admin_user_view เห็นฟอร์มเดียวกัน), ช่อง LINE ID/WeChat ID, ไม่มีรหัสผู้แนะนำ, บัตรประชาชนลิงก์ `upload/<ไฟล์>` (ไม่ใช่ Azure), ตารางเรตตั้งเองแบบแนวตั้ง, LoadUserType เฉพาะ option_price = 0 |
| `_admin/_user/save.php` (diff 36) | ทำแล้ว: code จากช่องฟอร์ม (jl = username), บันทึก lineid/wechatid, ไม่มี commission, เปลี่ยนรหัสผ่านได้เฉพาะผู้บันทึก role = 1 |
| `_admin/_user/save_approved.php` (diff 13) | ทำแล้ว: ตอบ `$('form').submit();`, อีเมลอนุมัติเฉพาะ type 3, type 4 = 500 (ดูข้างล่าง) |
| `_admin/_user/load_user_type_detail.php` (diff 83) | ทำแล้ว: สิทธิ์ admin_user เท่านั้น, แสดงทุก product_type (เรตของกลุ่ม หรือค่าตั้งต้นของ product_type), numberformat |
| `_admin/_user/email.php`, `email_approved.php`, `_register/email.php`, `_register/email_lost_password.php` (diff) | ทำแล้วใน `UsersMailer` (ข้อความ/สี/หัวเรื่องของ SR) |
| `_profile/index.php` (diff 16) | ทำแล้ว: ช่อง WeChat ID แทนบัตรประชาชน |
| `_profile/save.php` (diff 42) | ทำแล้ว: ถาม PIN ก่อน (`PinBox::guard`), บันทึก email/phone/phone2/lineid/wechatid |
| `_register/save.php` (diff 40) | ทำแล้ว: รหัส `SU####`, lineid/wechatid, อีเมลซ้ำไม่ได้, ข้อความ "ผู้ใช้งานนี้มีในระบบแล้ว", ไม่มีรหัสผู้แนะนำ, ไฟล์บัตรคัดลอกในเครื่อง |
| `register.php` (diff 119), `lost_password.php` (diff 7) | ทำแล้ว: สร้าง blade ใหม่จากไฟล์ SR (โลโก้ SR, ลิงก์เปลี่ยนภาษา, ช่อง LINE/WeChat, กล่องแนบบัตร, ข้อตกลงของ SR แทน JLCARGO) |
| `password.php` (diff 9) | คงไว้ = ตอบว่าง (ดูช่องโหว่ข้อ 1) |
| `_register/email_lost_password.php`, `btn_email_lost_password.php`, `email_cancel.php` | ใน UsersMailer (สี/ข้อความ SR) |

ไม่มีไฟล์ SR-only / JL-only ในกลุ่มนี้ — route เดิมครบ ไม่ต้องลบ

### ฐานร่วม (commit `foundation:` แยก)
- `config/legacy.php` `mail` = ผู้ส่ง `SRShippingSystem <no-reply@srshippingsystem.com>` (ตาม `sendmail()` ของ SR) + แม่แบบ email_header/center/footer ของ SR (byte ตรงกับไฟล์เดิม) — Mailer ของกลุ่มอื่นใช้ค่านี้ได้เลย (`config('legacy.mail.from_address')`, `UsersMailer::wrap()` เป็นตัวอย่าง)
- `config/services.php` mailgun domain ตั้งต้น `mg.srshippingsystem.com`, `config/mail.php` from ตั้งต้นเป็นของ SR, `tests/Feature/Integrations/MailgunMailerTest.php` ตามค่าใหม่
- ยังมีข้อความ JLcargo ใน Mailer ของกลุ่มอื่น (NoticeMail, CashbackMailer, MoneyMove/Lot/Order/Payment/Reports/LostPackage) — เป็นงานของกลุ่มนั้น

### ต่างจาก SR เดิมโดยตั้งใจ
- ทุกข้อของ jl ด้านล่างยังอยู่ (bind SQL, escape, CSRF, PinThrottle, legacy.once, transaction/lock ของ user_count ฯลฯ)
- ลิงก์ทุกตัวไม่มี `target="_blank"` (grid ผู้ใช้/กลุ่มราคา/role, ปุ่ม "แก้ไขโมดูลนี้", ลิงก์บัตรประชาชน, ปุ่มในอีเมล)
- admin_user_view: หน้าฟอร์มเหมือน SR (แก้ช่องได้) แต่บันทึก/อนุมัติ/ยกเลิก/ลบ/btn_* ต้องมี admin_user (owner decision `*_view` = อ่านอย่างเดียว) — bench ไม่มี role ที่ถือ view อย่างเดียว
- `_profile/save`: PIN ผิด → log เป็น `***` (เดิม plaintext) และนับ PinThrottle ร่วมกับ `_profile/pin`
- `save_approved` type 4: เดิม UPDATE อนุมัติแล้ว `require_once('email_approved2.php')` ที่ไม่มีไฟล์ → PHP fatal (หน้าไม่ทำอะไรต่อ); ใหม่ = อนุมัติแล้วตอบ HTTP 500 ว่าง (ผลที่ผู้ใช้เห็นเท่าเดิม) — bench ไม่มี user_type id 4
- `_register/save` บัตรประชาชน: รับเฉพาะชื่อไฟล์ล้วนใน `legacy.upload.temp_dir` แล้ว copy ไป `legacy.upload.local_dir` (production ต้องตั้ง `LEGACY_UPLOAD_LOCAL_DIR` = โฟลเดอร์ `upload/` ของเว็บ เพื่อให้ลิงก์ `upload/<ไฟล์>` ในฟอร์มแอดมินเปิดได้); ค่า `identity` ยังบันทึกตามที่ส่งมาแบบเดิม
- อีเมล: เดิม `sendmail()` จับ exception แล้ว `echo $e` (ข้อความ error ปนในคำตอบ ajax) → ใหม่ log เงียบ; ค่าจาก DB ถูก escape
- ข้อความ blocklist ตอนสมัครยังเป็นภาษาไทยที่อ่านได้ (SR เดิมเป็น UTF-8 ซ้อนเหมือน jl)

### ช่องโหว่เดิมที่พบใน SR (เพิ่มจากของ jl)
1. **ร้ายแรง: `password.php` ของ SR เปิดสาธารณะ (ไม่ต้องล็อกอิน) และพิมพ์รหัสผ่านที่ถอดรหัสแล้วของผู้ใช้ทุกคน** (`echo "UPDATE user SET password = '".decrypt($row['password'])."' ..."` ทุกแถวของ `user`) — ยังอยู่ทั้ง sr-prehotfix และ sr-old (hotfix 2026-10-18 ไม่ได้แก้) · Laravel ตอบว่าง · **ควรลบไฟล์นี้ออกจาก production ทันที** (ไม่ได้ยิงทดสอบ)
2. `_register/save.php` SR: `copy('../upload/temp/'.$_POST['upload_file'], '../upload/'.$upload_file)` ไม่กรอง path → คัดลอกไฟล์ใดๆ ที่ php อ่านได้ (เช่น `../../system_config.php`, `../../system_db.php`) ไปไว้ใต้ `upload/` ที่เปิดสาธารณะ (แก้แล้ว)
3. `_admin/_user/form.php` SR echo `identity` ดิบใน href/img (stored XSS ผ่านค่าที่สมัครส่งมา) และ lineid/wechatid ดิบ (แก้: escape)
4. `_admin/_user/load_user_type_detail.php` / grid / form echo ชื่อกลุ่ม/ประเภทสินค้า/ lineid ดิบ (แก้: escape)
5. ช่องโหว่ข้อ 1–14 ของ jl ด้านล่าง SR มีครบเหมือนกัน (ไฟล์ same)

### ผลเทียบ (หลังยุบช่องว่าง + แทน href; ต่างที่เหลือ = favicon/morris/dashboard.js ของ layout ฐานร่วม และ target="_blank")
- หน้า: admin/user, admin/user/status/1, admin/user/create, /24, /2, /22, /11937, /999999, ผู้ใช้ราคาตั้งเอง (สร้าง zz ชั่วคราว), ฟอร์มที่มีปุ่ม view/reset/pin/unbanned (เพิ่มรหัสแอดมินใน setting ชั่วคราว), role 12 (admin_user), role 7 (ไม่มีสิทธิ์ → layout + ErrorBox ของฐานร่วม), profile (24, 11937, 11938, 6), admin/user_type (+create, /3), admin/role (+create, /8), register, lostpassword (th/cn/en) — **ตรงกัน**
- ajax: _admin/_user/grid 7 แบบ (หน้า 1/2, ค้นหา+role+type+approved, 99, -1, `a_b`, ไม่ส่ง txtsearch) + ลูกค้า/role 7 (ว่าง), load_user_type_detail (2, 3, 126, 999, ว่าง, role 7 ว่าง), load_role_detail ×4, rate_history, _profile/load_* ×8, user_type grid ×3, role grid ×2 — **ตรงกัน**

### E2E (ฝั่ง Laravel เท่านั้น, แถว zz_ แล้วลบตาม id)
- สมัคร: สำเร็จ (user SU0001 + crawl_guard_signup + copy บัตร), ส่งซ้ำ = "ผู้ใช้งานนี้มีในระบบแล้ว", อีเมลซ้ำ, user_type ตั้งเองของคนอื่น (invalid), path `../../.env` (บันทึกค่า แต่ไม่คัดลอก) · อีเมลสมัคร/ลืมรหัส ตรวจใน log (From SRShippingSystem, ข้อความ SR)
- ลืมรหัสผ่าน (รหัสเปลี่ยน + อีเมล) — **หมายเหตุ**: ลบแถว `ips` ของ IP ผู้ขอ (127.0.0.1) ตามพฤติกรรมเดิม ทำให้แถว ips id 88 (ล็อกอินผิดจาก 127.0.0.1 ของการทดสอบฐานร่วมเมื่อ 02:04) หายไป 1 แถว กู้คืนไม่ได้ (ค่าใน log ถูกปิด) — เป็นแถวทดสอบ ไม่กระทบข้อมูลจริง; หลังจากนั้น snapshot ips/ips_ban/ips_ban_user ก่อนทุกขั้น
- โปรไฟล์ 11937: ไม่มี PIN → กล่องตั้ง PIN, ตั้ง PIN, ไม่ส่ง PIN → กล่อง PIN, PIN ผิด, ถูก (บันทึกทุกช่อง), ส่งซ้ำ, รหัส = username, รหัสมี & , เปลี่ยนรหัสผ่าน → คืนค่าเดิมทุกช่อง (pin คืนเป็น '')
- แอดมิน: สร้างผู้ใช้ราคาตั้งเอง (user + user_type ลูก + user_type_detail 8 + rate history), ส่งซ้ำ = "ส่งซ้ำ" (legacy.once), แก้โดย role 1 (รหัสเปลี่ยน) / role 12 (รหัสไม่เปลี่ยน) / role 7 (ปฏิเสธ), อนุมัติ type 2 (ไม่มีเมล) / 3 (มีเมล) / 4 (500), ยกเลิก (อีเมล), ลบ, btn_viewpassword/pinreset/resetpassword/unbanned + PIN ผิด (setting/PIN แอดมินคืนค่าแล้ว), user_type สร้าง/ลบ, role สร้าง/ลบ (500 แบบเดิม)
- ลบแล้ว: user 12005, 12006, 12069; user_type 173, 174; user_type_detail 4394–4409; role 14 + role_on_detail; user_rate_history 2–3; crawl_guard_signup 16–18; ips 89, 90; ไฟล์ทดสอบใน storage/app/legacy-upload

### bench (`bin/bench`, -n 30 -c 1, `artisan optimize`)

| หน้า | เดิม mean ms (2 รอบ) | ใหม่ mean ms |
|---|---|---|
| /profile (ลูกค้า 24) | 322 / 366 | 45 / 37 |
| /admin/user/24 (แอดมิน 11938) | 394 / 379 | 47 / 48 |
| _admin/_user/grid page 1 | 161 / 80 | 56 / 33 |
| _admin/_user_type/grid page 1 | 22 / 23 | 28 / 27 |
| /register (ไม่ล็อกอิน) | 20 / 16 | 25 / 17 |

user_type grid และ register ช้ากว่าเล็กน้อย: bench SR มีผู้ใช้แค่ ~24 แถว (subquery นับผู้ใช้ต่อกลุ่มของเดิมจึงเร็ว) และ register เป็นหน้าคงที่ — ต่างเท่ากับ overhead คงที่ของ Laravel (~5–20 ms) · ใน production ที่มีผู้ใช้มาก grid ของเดิมจะช้าลงตามจำนวนแถว

### test
- `php8.4 vendor/bin/phpunit`: users ผ่านทั้งหมด; ล้ม 1 ตัวของกลุ่มอื่น `Lot\LotRoutesTest::test_route_count_matches_url_map` (ล้มบน main ด้วย)
- `JL_BENCH_DB_TESTS=1`: `Users\UsersSrDbTest` (5) ผ่าน; ล้มของกลุ่มอื่น (คอลัมน์ jl ที่ SR ไม่มี: tracking.is_checked, bill_payment.is_vat ฯลฯ): Lot\LotPrintHeaderCountDbTest ×2, Money\BillGridDbTest, Money\LegacyRisksDbTest ×4, Money\GridPerfDbTest

### คำถามถึงเจ้าของ
1. ลบ `password.php` ออกจาก production SR ทันทีหรือไม่ (เปิดเผยรหัสผ่านทุกคน) — แนะนำอย่างยิ่ง
2. production: ตั้ง `LEGACY_UPLOAD_LOCAL_DIR` ให้ชี้ `upload/` ของเว็บ SR (บัตรประชาชนตอนสมัคร) หรือจะย้ายไปเก็บที่ Azure (ต้องแก้ account ของ SR ก่อน — โค้ดเดิมเขียนว่า Azure ของ SR ตั้ง account ผิด)
3. type 4 ตอนอนุมัติ: ให้ส่งอีเมลอนุมัติแบบ type 3 แทน 500 หรือไม่ (ไฟล์ email_approved2.php ไม่มีในเว็บเดิม)
4. admin_user_view เห็นฟอร์มแบบแก้ไขได้แบบ SR (แต่บันทึกไม่ได้) — ต้องการหน้าอ่านอย่างเดียวแบบ jl หรือไม่
5. ข้อความในอีเมลสมัครของ SR มี "กรุณาติดต่อหมายเลขโทรศัพท์ :" แต่ไม่มีเบอร์ (เดิมเว้นว่าง) — คงไว้ตามเดิม


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
- `_register/save` upload_file: รับเฉพาะชื่อไฟล์ใน temp_dir แล้วส่ง `App\Support\Legacy\BlobStore::uploadFromTemp($f, skipExisting: false)` (= UploadAzure เดิม: อัปทับ; driver azure/local)
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
