# กลุ่ม messaging

## SR (2026-10-20)

branch `mod/messaging` ใน `/home/admin/laravel/sr-wt/messaging` (port 8311) — เทียบกับ SR เดิม `:8111` (`/home/admin/bench/sr-old`, DB `admin_sr_bench`)
ข้อตัดสินใจของ jl ด้านล่างใช้กับ SR ทั้งหมด (ลบประกาศต้องมี 2 สิทธิ์, escape ข้อความแชท, cron ต้องมีสิทธิ์ ฯลฯ) — ส่วนนี้คือสิ่งที่ต่างสำหรับ SR

### ไฟล์ใน docs/sr-diff.md

| ไฟล์เดิม | สถานะ | ผล |
|---|---|---|
| `notification.php` | diff 82 | **ทำแล้ว** — ช่องแรกใช้สิทธิ์ `admin_goodsconfirm` (jl: admin_withdrawpoint) และไม่มีเงื่อนไขมือถือ; `admin/send` ไม่นับประเภทส่ง 43/53; ช่องใหม่ `admin/sendself` (43/53, ข้อความ `pick_alone` "รับเอง") และ `admin/check` (tracking_check ที่ `is_checked = 0` และมีพัสดุจริง); ช่อง `payment/create` ของลูกค้านับเฉพาะพัสดุที่เข้าล็อตแล้ว (`lot_id != 0`); กระดิ่งประกาศล่าสุด (`notice.is_show = 1` แถวเดียว, `substr(strip_tags(...), 0, 1000)."..."`) ก่อนเมนูภาษา; nobilling ยังเช็ค `$data` ของบล็อกก่อนหน้าแบบเดิม (ตอนนี้คือบล็อก tracking_check) |
| `_admin/_send/index.php` | diff 4 | **ทำแล้ว** — `shipping_type != 43 AND != 53`, คอลัมน์ประเภทส่ง `word-break: break-all` |
| `_admin/_send/index_self.php` | diff 4 | **ทำแล้ว** — หน้าใหม่ `GET /admin/sendself` (`?page=admin_sendself`, สิทธิ์ admin_export) = shipping_type 43 หรือ 53; `/_admin/_send/index_self` (เปิดไฟล์ตรง) ยังตอบชิ้น HTML ตามข้อ 10 ของ jl (SR เดิมเปิดตรง = 500) |
| `_admin/_message/index.php` | diff 12 | **ทำแล้ว** — JS `decodeHtml()` ก่อนใส่ผลของ load, ปุ่มแนบรูปเรียก `_admin/_message/save_file` (jl เรียก `_message/save_file`) |
| `_admin/_message/load.php` | diff 4 | **ทำแล้ว** — ตอบ `json_encode(['html' => htmlentities($html), ...])` ไม่มี `urldecode` (ข้อความแชท/รหัสลูกค้า/`sql` ไม่ถูก urldecode อีก) |
| `_message/load.php` | diff 2 | **ทำแล้ว** — ชื่อผู้ตอบ `srshippingsystem Support Team` |
| `email_header.php`, `email_center.php`, `email_footer.php` | diff 2/2/10 | **ทำแล้ว** — อีเมลประกาศใช้ `config('legacy.mail')` (header `max-width: 560px`, center `text-indent: 30px`, footer "ขอแสดงความนับถือ SRShippingSystem" ไม่มีบรรทัด require_once/ติดต่อของ jl, ไม่ใส่บรรทัด guard ของ hotfix 2026-10-18); เปิดไฟล์ตรงจาก URL = **403 ว่าง** เหมือน SR หลัง hotfix |
| `notify_counts.php`, `notify_token.php` | diff 2 | ไม่ต้องแก้ — ต่างแค่ชื่อไฟล์/ชื่อเว็บ ซึ่งอยู่ใน `config('legacy.notify')` (site = srshippingsystem, path จาก .env) แล้ว |
| ไฟล์ `same` อีก 24 ไฟล์ (_message/*, _notice/*, _admin/_notice/*, _admin/_message/ ที่เหลือ, notifications.php) | same | **unchanged** — เทียบกับ :8111 แล้วตรง (ดูด้านล่าง) |
| `_linenotify/*` | ไม่มีใน SR | **ลบแล้ว** — ไม่มี route/controller/view/config เหลือ (เหลือแค่ข้อความในส่วน jl ด้านล่าง) |

helper: `notify_message()` ของ SR (ยิงรูปเข้า LINE Notify — jl คอมเมนต์ทิ้งทั้งฟังก์ชัน) → `App\Domain\Messaging\NotifyMessage::send()` ทำงานเฉพาะเมื่อ `legacy.external.enabled = true` (dev ไม่ยิง)
เดิมไม่มีโค้ดใดเรียกฟังก์ชันนี้ (จุดเดียวใน `sendLineImage()` ถูกคอมเมนต์) และ LINE Notify ปิดบริการแล้ว — พอร์ตไว้ให้ครบเท่านั้น
`NoticeMail` / `php8.4 artisan messaging:notice-mail`: ผู้ส่ง `config('legacy.mail.from_*')` = SRShippingSystem <no-reply@srshippingsystem.com>, แม่แบบ SR, ไม่มีแบรนด์ jl

### ต่างจาก SR เดิมโดยตั้งใจ (นอกจากข้อของ jl ด้านล่าง)

1. `_admin/_notice/save` มี `legacy.once` (กดบันทึกซ้ำ = ไม่สร้างประกาศ 2 แถว) — แชท (`_message/send`, `_admin/_message/send`) ไม่ใส่ เพราะส่งข้อความเดิมซ้ำได้ตามปกติ (เหมือนเดิม)
2. `_admin/_message/load`: `title` (รหัสลูกค้าที่ JS ใส่ด้วย `.html()`) ถูก escape; ข้อความแชทยัง escape แบบ jl (ผ่าน htmlentities อีกชั้น → `decodeHtml()` ใน JS ได้ข้อความที่ escape แล้ว ไม่ใช่ HTML ดิบ)
3. ตัด `target="_blank"` ใน admin/send, admin/sendself, grid ประกาศแอดมิน, ปุ่มแนบรูปในแชท
4. ชิ้นส่วนอีเมลตอบ 403 เฉพาะผู้ล็อกอิน (ไม่ล็อกอิน = ไปหน้า login แบบ route อื่นของกลุ่ม) — SR เดิมตอบ 403 ทุกคน
5. ข้อความกระดิ่งประกาศแสดงแบบเดิม (HTML ของแอดมินที่ผ่าน strip_tags) — ไม่ escape เพิ่ม เหมือน grid ประกาศของ jl

### ผลเทียบกับ SR เดิม (:8111, session เดียวกัน, ตัดช่องว่าง)

- `notification` (ผู้ใช้ 1, 6, 11, 3, 11937, 11938) — ตรงกัน ต่างแค่ขึ้นบรรทัดก่อน `<li class="">`; ทดสอบซ้ำโดยใส่แถว zz_ (notice is_show=1, tracking_check, bill_payment ประเภท 53) ช่อง admin/check / admin/sendself / กระดิ่ง ตรงกัน
- `notifications` ทุก role ตรงกัน
- หน้า admin/send, admin/sendself (มี/ไม่มีแถว 53), notice, notice/1, admin/notice, admin/notice/1, admin/notice/create, message, admin/message: เนื้อหาตรงกัน ยกเว้น `target="_blank"`; ส่วน layout ต่างแค่ favicon/morris (ฐานร่วม)
- `_notice/grid`, `_admin/_notice/grid` หน้า 1/2 + ค้นหา: ตรงกัน (ยกเว้น target=_blank); ลูกค้าเรียก admin grid = ว่างเหมือนเดิม
- `_message/load_dept`, `_admin/_message/load_dept`, `load_user` (มี/ไม่มีคำค้น): ตรง byte; `_message/load`, `_admin/_message/load` หน้า 0/1/2: ตรง byte ยกเว้นข้อความแชทที่มี HTML/`&` (ตั้งใจ escape) และ target=_blank ของรูปแนบเก่า
- สิทธิ์: ลูกค้า (11937) เปิด admin/message, admin/notice, admin/notice/1, admin/send, admin/sendself = ข้อความไม่มีสิทธิ์ (SR เดิม admin/message ไม่ตรวจ / admin/send แสดงข้อมูลหลัง alert)
- `/_admin/_send/index_self` เดิม 500, email_* เดิม 403 (ดูข้างบน)

### E2E (เขียนฝั่ง Laravel เท่านั้น — แถว zz_ ใน admin_sr_bench, ตาราง chat/chat_dept/notice/tracking_check/notice_mail_queue ว่างก่อนเริ่ม)

- `_message/send` ×2 (ได้ 2 แถว เหมือนเดิม), `_admin/_message/send`, `send_endconversation` ทั้งสองฝั่ง (is_read / is_read_admin), `deletechat` (ลบได้เฉพาะแถวที่ตัวเองสร้าง — ผู้ใช้อื่นลบไม่ได้)
- `_admin/_notice/save` สร้าง ×2 (ครั้งที่ 2 = ErrorBox ส่งซ้ำ, 1 แถว), แก้ไข, `save_isshow`, `delete` (role 6 ไม่มีสิทธิ์ = ปฏิเสธ; admin = is_delete 1 ของประกาศ ไม่แตะ product_type)
- `_admin/_notice/email` → log mailer: From SRShippingSystem, แม่แบบ SR; `email_notice` เข้าคิว 26 ราย (กดซ้ำไม่เพิ่ม), cross-site = 403; `messaging:notice-mail --test` และแบบส่ง 2 ฉบับ (ไป log เท่านั้น)
- ลบทุกแถวที่สร้างตาม id แล้ว (chat 1–5, chat_dept 1, notice 1–2, tracking_check 143, bill_payment 626, คิว noti_id 1 = 26 แถว) — นับแล้วกลับเป็น 0 ทุกตาราง

### ผล bench (`bin/bench`, n=30 c=1, หลัง `artisan optimize`, ผู้ใช้ 1, รอบที่ 2)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/notice` (หน้าเต็ม) | 394 | 39 |
| `/admin/send` (หน้าเต็ม) | 418 | 52 |
| `notification` (แอดมิน) | 337 | 48 |
| `_notice/grid` | 14 | 24 (overhead Laravel คงที่ ~20 ms) |
| `_admin/_message/load` | 20 | 28 (overhead คงที่) |
| `_admin/_message/load_user` | 15 | 34 (overhead คงที่ + bench มีผู้ใช้แค่ 26 คน; jl ที่มีผู้ใช้ ~11,900 เท่ากัน) |

### ช่องโหว่เดิมใน SR (อ่านโค้ด — ไม่ได้ยิงใส่เว็บเดิม)

- `_admin/_message/load.php` ของ SR ยังไม่ตรวจล็อกอิน/สิทธิ์ และตอนนี้ JS `decodeHtml()` ถอด htmlentities กลับเป็น HTML ดิบ → stored XSS จากข้อความแชทลูกค้าในหน้าแอดมินยังอยู่ (เหมือน jl)
- `_admin/_send/index.php` / `index_self.php`: alert แล้วไม่ exit (ข้อมูลทั้งหมดอยู่ใน HTML ของผู้ไม่มีสิทธิ์)
- system_config.php ของ SR มี key ของ SendGrid ฝังในโค้ด (ในส่วนที่คอมเมนต์ไว้ และใน `send_a_lot_mail()`) — ไม่ได้คัดลอกมา ควรเพิกถอน key นั้น

### คำถามถึงเจ้าของ

1. ช่องแจ้งเตือน "พัสดุรอตรวจ" (`admin/check`) แสดงตามสิทธิ์ `admin_export` (แบบ SR เดิม) แต่หน้า admin/check ใช้ `admin_tracking` — role 11 มี admin_tracking แต่ไม่มี admin_export จึงไม่เห็นตัวเลข; ส่วนผู้มี admin_export แต่ไม่มี admin_tracking จะเห็นตัวเลขแต่เปิดหน้าไม่ได้ (ใน bench role 1/9/10/12 มีทั้งคู่) — คงแบบเดิม หรือเปลี่ยนเป็น admin_tracking?
2. ช่อง nobilling ยังแสดงตาม `$data` ของบล็อกก่อนหน้า (บั๊กเดิม — ใน SR คือจำนวน tracking_check) จึงมักไม่แสดงเมื่อไม่มีพัสดุรอตรวจ — คงแบบเดิมไว้ ควรแก้ให้เช็คจำนวนของตัวเองหรือไม่
3. ลบประกาศต้องมี `admin_product_type` + `admin_notice` (ข้อตัดสินใจ jl) — ใน SR role 10/11/12 มี admin_notice แต่ไม่มี admin_product_type จึงลบประกาศไม่ได้ (เหมือนเดิม) ยืนยันหรือไม่
4. `notify_message()` (LINE Notify) ไม่มีผู้เรียกและบริการปิดแล้ว — ลบทิ้งได้หรือไม่

---

## jl (ต้นฉบับที่ fork มา)

แชทลูกค้า/แอดมิน, ประกาศ (notice) + อีเมลประกาศ, LINE Notify, รายการรอส่ง, แถบแจ้งเตือน, ชิ้นส่วนอีเมล
worktree `/home/admin/laravel/jl-wt/messaging` (branch `mod/messaging`, port 8211)

## Route ที่พอร์ต (URL เดิมทุกตัว)

| ไฟล์เดิม | route ใหม่ | สิทธิ์ |
|---|---|---|
| `?page=message` → `_message/index.php` | `GET /message` | legacy.auth (เดิมไม่ตรวจ) |
| `_message/load.php` | `POST /_message/load` | legacy.auth (เดิมไม่ตรวจแม้การล็อกอิน) |
| `_message/load_dept.php` | `POST /_message/load_dept` | legacy.auth + ajax |
| `_message/send.php` | `POST /_message/send` | legacy.auth + ajax |
| `_message/send_endconversation.php` | `POST /_message/send_endconversation` | legacy.auth + ajax |
| `_message/save_file.php` | `POST /_message/save_file` | message + ajax |
| `?page=admin_message` → `_admin/_message/index.php` | `GET /admin/message` | admin_message (เดิมไม่ตรวจ) |
| `_admin/_message/load.php` | `POST /_admin/_message/load` | admin_message (เดิมไม่ตรวจอะไรเลย) |
| `_admin/_message/load_dept.php`, `load_user.php`, `send.php`, `send_endconversation.php` | `POST /_admin/_message/...` | admin_message + ajax (เดิมไม่ตรวจสิทธิ์) |
| `_admin/_message/deletechat.php`, `save_file.php` | `POST /_admin/_message/...` | admin_message + ajax |
| `?page=notice` / `notice/{id}` | `GET /notice`, `GET /notice/{id}` | legacy.auth |
| `_notice/grid.php` | `POST /_notice/grid` | legacy.auth + ajax |
| `admin/notice`, `admin/notice/create`, `admin/notice/{id}` | `GET` เดียวกัน | admin_notice |
| `_admin/_notice/grid.php` | `POST` | login + ajax (ไม่มีสิทธิ์ admin_notice = ตอบว่างแบบเดิม ตรวจใน controller) |
| `_admin/_notice/save.php`, `save_isshow.php` | `POST` | admin_notice + ajax |
| `_admin/_notice/delete.php` | `POST /_admin/_notice/delete` | admin_product_type **และ** admin_notice + ajax (ดูด้านล่าง) |
| `_admin/_notice/email.php`, `email_notice.php` | `GET` | admin_notice |
| `_admin/_notice/cron_notice_mail.php` | `GET /_admin/_notice/cron_notice_mail` + `php8.4 artisan messaging:notice-mail` | admin_notice (เดิมเปิดได้โดยไม่ล็อกอิน) |
| `admin/send` → `_admin/_send/index.php` | `GET /admin/send` | admin_export |
| `_admin/_send/index_self.php` | `GET /_admin/_send/index_self` (ชิ้น HTML) | admin_export |
| `linenotify`, `_linenotify/callback.php`, `revoke.php` | `GET` เดียวกัน | profile |
| `notifications.php`, `notification.php` | `POST /notifications`, `POST /notification` | legacy.auth + ajax |
| `notify_counts.php`, `notify_token.php` | `GET/POST` (หน้าว่างแบบเดิม) | legacy.auth (เดิมสาธารณะ) |
| `email_header.php`, `email_center.php`, `email_footer.php` | `GET` | legacy.auth (เดิมสาธารณะ) |

## ผลเทียบกับเว็บเดิม (:8101, admin_jl_bench)

- หน้าเต็ม (layout + content) message, admin/message, notice, notice/85, admin/notice, admin/notice/85, admin/notice/create, admin/send: **เหมือนเดิม** (ต่างแค่ช่องว่าง/CRLF)
- linenotify: เหมือนเดิม ยกเว้น `client_id` มาจาก .env (dev ว่าง) และ `&` ใน href ถูก escape เป็น `&amp;`
- `_notice/grid`, `_admin/_notice/grid` (page 1/2/3/9/0/abc): เหมือนเดิม
- `notifications`, `notification`: เหมือนเดิมทุก role (user 1, 6, 9, 74, 2172, 6782, 11938, 137) + mobile UA
- `_message/load`, `_message/load_dept`, `_admin/_message/load` (page 1/0/-9), `load_dept`, `load_user`: **byte ต่อ byte** ยกเว้นข้อความแชทที่มี HTML (ตั้งใจ escape)
- `email_header/center/footer`, `notify_counts`, `notify_token`: byte ต่อ byte
- การเขียน (send, send_endconversation, deletechat, notice save/isshow/delete, linenotify callback/revoke) ทดสอบเฉพาะฝั่งใหม่ กับแถว zz_ (เว็บเดิมบน :8101 เขียนไม่ได้: `goLog()` เปิดไฟล์ log ใน webroot production ไม่ได้แล้ว exit) ตรวจจำนวนแถวก่อน/หลังทุกครั้ง แล้วลบแถวทดสอบออก

## สิ่งที่ต่างจากเดิมโดยตั้งใจ

1. **`_admin/_notice/delete`**: เดิมเป็นสำเนาของ `_admin/_product_type/delete.php` — กดลบประกาศแล้ว **ไปลบ product_type ที่ id ตรงกัน** (และฉีด SQL ได้ผ่าน `IN (".$id.")`)
   ที่นี่ลบ (is_delete = 1) **ประกาศ** ตามที่ปุ่มตั้งใจ, id ต้องเป็นตัวเลขล้วน, ต้องมีทั้ง `admin_product_type` (เดิม) และ `admin_notice`
   (ไม่กว้างกว่าเดิม: ถ้าคงแค่ admin_product_type แอดมินประเภทสินค้าที่ไม่มีสิทธิ์ประกาศจะลบประกาศได้ — role 5 ใน bench) → เจ้าของควรยืนยัน
2. ข้อความแชท: escape HTML ยกเว้นรูปแบบ `<a class="fancybox" ...><img ...></a>` ที่ปุ่มแนบรูปสร้าง (URL http/https เท่านั้น) — เดิม stored XSS ลูกค้า → แอดมิน
3. ค่าจาก DB ใน HTML ของแชท (ชื่อแผนก, icon, รหัสลูกค้า) ถูก escape; `txtSearch` ใน grid ถูก escape (เดิม reflected XSS); เลขหน้าที่ฝังใน JS ของ admin grid กรองเหลือแต่ตัวเลข
4. JSON ของแอดมินเดิม `urldecode(json_encode(...))` → ที่นี่ urldecode ทีละค่าก่อน escape/encode (ผลเท่ากันปกติ แต่ไม่ได้ JSON พังเมื่อข้อความมี `%22`)
5. textarea ของ admin notice form: แทน `</textarea` เป็น `&lt;/textarea` (กันหลุด textarea) — นอกนั้นเหมือนเดิม
6. save_file: ชื่อไฟล์ต้องไม่มี `/` `\` (เดิม `file_exists('../upload/temp/'.$value)` รับ `../`), ค่าใน JS ถูก escape
7. LINE Notify callback: เขียน `line_uid` เฉพาะเมื่อ state = ผู้ที่ล็อกอิน (เดิมใครส่ง state ของคนอื่นมาก็ผูก LINE เข้าบัญชีนั้นได้ — state = encrypt(id) แบบ ECB ตายตัว)
   dev (`legacy.external.enabled=false`): ไม่เรียก LINE จริง, token ปลอม `zz_fake_line_token`, revoke ไม่ยิง API; client_id/secret อยู่ใน .env (`LEGACY_LINE_NOTIFY_*`) — เดิมฝังในโค้ด
   หมายเหตุ: LINE Notify ปิดบริการแล้ว (31 มี.ค. 2025) ฟีเจอร์นี้ใช้จริงไม่ได้อยู่แล้ว
8. อีเมล: `Mail` ของ Laravel; dev บังคับ mailer `log` เสมอ (ไม่ว่า MAIL_MAILER คืออะไร) — เดิม Mailgun key ฝังในโค้ด
9. `admin/send`: ผู้ไม่มีสิทธิ์ไม่ได้ตารางข้อมูล (เดิม alert แล้วไม่ exit = ข้อมูลอยู่ใน HTML ครบ)
10. `_admin/_send/index_self`: เดิมเปิดตรงๆ = fatal error (ไม่ได้ include system_config) — ที่นี่ตอบชิ้น HTML
11. `cron_notice_mail`: เดิมใครก็เปิดผ่านเว็บได้ (สั่งส่งอีเมลได้) → ต้องมีสิทธิ์ admin_notice; cron จริงใช้ artisan
12. GET ที่เปลี่ยนข้อมูล (`_admin/_notice/email`, `email_notice`, `cron_notice_mail`, `_linenotify/revoke`) ตอบ 403 เมื่อมาจากเว็บอื่น
    (`Sec-Fetch-Site` = cross-site/same-site หรือ Referer ต่าง host) — LegacyCsrf ปล่อย GET ทั้งหมด เดิม `<img src=…email_notice?id=1>` บนเว็บอื่น
    ทำให้แอดมินที่ล็อกอินอยู่เข้าคิวอีเมลถึงลูกค้าทุกคน (~11,900 ราย) ได้; ปุ่มในหน้า (window.open / ลิงก์) ยังใช้ได้ตามเดิม
    `_linenotify/callback` ยกเว้น (LINE redirect กลับมาแบบ cross-site) — ป้องกันด้วยการตรวจ state และตรวจ state ก่อนแลก token
12. ชิ้นส่วนอีเมล / notify_* ต้องล็อกอิน (เดิมสาธารณะ — ไม่มี JS ไหนเรียกผ่าน URL)
13. load_user: LIKE escape `%` `_` (เดิมเป็น wildcard)
14. ช่องว่าง/CRLF ใน HTML ของหน้า/grid ไม่ตรง byte (JSON ของแชทตรง byte เพราะ `Chat::crlf()`)
15. `_linenotify/callback` / `revoke`: เดิม redirect ไป `href."/linenotify"` = `//linenotify` (href ลงท้าย / อยู่แล้ว) — Laravel ไม่ match path นี้ (404) → ที่นี่ redirect ไป `/linenotify`

## N+1 / query ที่ลด

- `notification`: เดิม COUNT ทีละช่อง (สูงสุด ~17 query) + getPrefix ทีละช่อง → 1 query COUNT รวม + 1 query prefix; nobilling ใช้ COUNT แทนดึงทุกแถว (และนับเฉพาะเมื่อเงื่อนไขเดิมจะแสดง)
- `notifications` fallback (ไม่มีไฟล์ counts): 12 query → 1 query
- grid ประกาศ / admin/send: getPrefix ต่อแถว → ครั้งเดียวต่อ request
- แชท: ไม่นับ mysqli_num_rows ที่เดิมคำนวณแล้วไม่ได้ใช้
- worker อีเมล: เดิม SELECT notice + user ทีละแถว → join ในคำสั่งเดียว

## ช่องโหว่เดิมที่เจอ (อ่านโค้ดเท่านั้น — ไม่ได้ยิง payload ใส่เว็บเดิม)

- SQL injection: `_admin/_notice/delete.php` (`IN (".$id.")` แบบไม่มี quote — แบบเดียวกับ save_remove_tracking ที่ทำ tracking เสียหาย)
- broken access control: `_admin/_message/load.php` ไม่ตรวจล็อกอิน (อ่านแชทของลูกค้าใดก็ได้), `_message/load.php` ไม่ตรวจล็อกอิน (ไม่ล็อกอิน = เห็นข้อความที่ลูกค้าทุกคนส่งถึงแอดมิน), `_admin/_message/load_dept|load_user|send|send_endconversation` และหน้า admin/message ไม่ตรวจสิทธิ์ (ลูกค้าส่งข้อความในนามแอดมินถึงลูกค้าคนอื่นได้), `_admin/_send/index.php` ไม่ exit หลัง alert, `cron_notice_mail.php` เปิดได้โดยไม่ล็อกอิน
- stored XSS: ข้อความแชท (ลูกค้า → แอดมิน), reflected XSS: `txtSearch`, `$Page` ใน admin notice grid
- path traversal: save_file (`../`)
- LINE Notify: account binding ด้วย state ของคนอื่น, client secret ฝังในโค้ด
- `_admin/_notice/email.php`: ส่งประกาศไปอีเมลใดก็ได้ (`email_to` จาก `extract($_GET)`)
- secret ฝังในโค้ด: Mailgun key (system_config.php), LINE client secret (`_linenotify/*.php`) — ไม่ได้คัดลอกมา

## ผล bench (`bin/bench`, n=30 c=1, หลัง `artisan optimize`)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/notice` (หน้าเต็ม) | 321 / 328 | 37 / 37 |
| `_notice/grid` | 18 / 16 | 27 / 25 (overhead Laravel คงที่) |
| `notification` (แอดมิน) | 145 / 179 | 45 / 36 |
| `_admin/_message/load_user` | 131 / 134 | 134 / 138 (LIKE '%..%' สแกน user ทั้งตาราง เท่ากัน) |

ข้อเสนอ index (ไม่ได้สร้าง): `chat(dept, to, is_read)`, `chat(dept, from, is_read_admin)`

## ยังไม่ได้ทดสอบจริง

- `email_notice` (INSERT IGNORE คิว ~11,900 แถว) — ไม่ยิงเพื่อไม่เขียนข้อมูลจำนวนมากใน bench; ทดสอบ worker ด้วย `messaging:notice-mail --test` กับคิวว่างเท่านั้น
- การอัปโหลดผ่าน `system_upload_2` (กลุ่ม settings) / `_api/blockblob_ajax` (กลุ่ม shop)
