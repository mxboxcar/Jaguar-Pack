# กลุ่ม messaging

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
| `_admin/_notice/grid.php`, `save.php`, `save_isshow.php` | `POST` | admin_notice + ajax |
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
