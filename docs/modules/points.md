# กลุ่ม points — รายงานการพอร์ต

branch `mod/points` · worktree `/home/admin/laravel/jl-wt/points` · port 8207

## Route ที่พอร์ต (URL เดิมทุกตัว)

| เดิม | ใหม่ | middleware |
|---|---|---|
| `^point$` → `_point/index.php` | `GET /point` | `legacy.perm:point` |
| `_point/grid.php` | `POST /_point/grid` | `legacy.auth` + `legacy.ajax` (สิทธิ์ `point` ใน controller: เดิม exit เงียบ) |
| `_point/load_withdraw.php` | `POST /_point/load_withdraw` | เหมือนข้างบน |
| `_point/save_withdraw.php` | `POST /_point/save_withdraw` | เหมือนข้างบน (+ PIN แบบเดิม) |
| `^admin/point$` → `_admin/_point/index.php` | `GET /admin/point` (`?status=`) | `legacy.perm:admin_point` |
| `_admin/_point/grid.php` | `POST /_admin/_point/grid` | `legacy.auth` + `legacy.ajax` (สิทธิ์ใน controller) |
| `^admin/withdrawpoint$`, `^admin/withdrawpoint/status/([0-9]+)$` → `_admin/_withdrawpoint/index.php` | `GET /admin/withdrawpoint`, `GET /admin/withdrawpoint/status/{status}` | `legacy.perm:admin_withdrawpoint` |
| `^admin/withdrawpoint/([0-9]+)$` → `_admin/_withdrawpoint/form.php` | `GET /admin/withdrawpoint/{id}` | `legacy.perm:admin_withdrawpoint` |
| `_admin/_withdrawpoint/grid.php` | `POST /_admin/_withdrawpoint/grid` | `legacy.auth` + `legacy.ajax` (สิทธิ์ใน controller) |
| `_admin/_withdrawpoint/cancel.php`, `save_approved.php` | `POST /_admin/_withdrawpoint/{cancel,save_approved}` | `legacy.ajax` + `legacy.perm:admin_withdrawpoint` |
| `^shop_cashback$` → `_shop_cashback/index.php` | `GET /shop_cashback` | `legacy.perm:shop_cashback` |
| `^shop_cashback/create$`, `^shop_cashback/([0-9]+)$` → `_shop_cashback/form.php` | `GET /shop_cashback/create`, `GET /shop_cashback/{id}` | `legacy.perm:shop_cashback` (+ เจ้าของรายการ) |
| `_shop_cashback/grid.php` | `POST /_shop_cashback/grid` | `legacy.auth` + `legacy.ajax` (สิทธิ์ใน controller) |
| `_shop_cashback/load_calculator.php`, `load_new_line.php`, `save.php`, `save_remark.php` | `POST /_shop_cashback/{load_calculator,load_new_line,save,save_remark}` | `legacy.ajax` + `legacy.perm:shop_cashback` |
| `^admin/shop_cashback$`, `^admin/shop_cashback/status/([0-9]+)$` → `_admin/_shop_cashback/index.php` | `GET /admin/shop_cashback`, `GET /admin/shop_cashback/status/{status}` | `legacy.perm:admin_shop_cashback` |
| `^admin/shop_cashback/create$`, `^admin/shop_cashback/([0-9]+)$` → `_admin/_shop_cashback/form.php` | `GET /admin/shop_cashback/create`, `GET /admin/shop_cashback/{id}` | `legacy.perm:admin_shop_cashback` |
| `_admin/_shop_cashback/grid.php` | `POST /_admin/_shop_cashback/grid` | `legacy.auth` + `legacy.ajax` (สิทธิ์ใน controller) |
| `_admin/_shop_cashback/save.php`, `save_approved.php`, `save_cancel.php`, `save_remark.php` | `POST /_admin/_shop_cashback/{save,save_approved,save_cancel,save_remark}` | `legacy.ajax` + `legacy.perm:admin_shop_cashback` |
| `_admin/_shop_cashback/email_approved.php`, `email_cancel.php` | `POST /_admin/_shop_cashback/{email_approved,email_cancel}` (`id`, `ids`) | `legacy.perm:admin_shop_cashback` |

ไม่ได้ทำ route: `/_point/index`, `/_admin/_point/index`, `/_admin/_withdrawpoint/{index,form}`, `/_shop_cashback/{index,form}`, `/_admin/_shop_cashback/{index,form}` (url-map ตารางที่ 2)
— เป็นชิ้นส่วนที่ `index.php` include ถ้าเรียกตรงในเว็บเดิมจะ fatal (ไม่มี `system_config.php` / `$resource`) หน้าเต็มใช้ RewriteRule ด้านบนแทน

## ไฟล์

- `routes/modules/points.php`
- `app/Http/Controllers/Points/{PointController,ShopCashbackController}.php`, `Admin/{AdminPointController,AdminWithdrawPointController,AdminShopCashbackController}.php`
- `app/Domain/Points/`: `Grid` (input/ค้นหา/หน้า), `Cashback` (รายการย่อย/บัญชีอ้างอิง), `Rules` (เงื่อนไขแบบ 7.4), `CashbackMailer`, `Php74`, `UploadInline`, `TempUpload`
- `resources/views/points/{point,admin_point,admin_withdrawpoint,shop_cashback,admin_shop_cashback,partials}/*`
- `tests/Feature/Points/PointsLogicTest.php` + `tests/fixtures/points-php74-cases.json` (สร้างจากนิพจน์เดิมด้วย php7.4)

สำเนาจากกลุ่มอื่น (ยังไม่มีในฐานร่วม — ตอน merge ควรรวมเป็นของกลาง):
`Php74` = `App\Domain\Balance\Php74`, (PinBox รวมแล้ว: `save_withdraw` ใช้ `App\Domain\Payment\PinBox` + PinThrottle), `TempUpload` ≈ `App\Domain\MoneyMove\TempUpload` (indent ต่างตามไฟล์เดิม),
`UploadInline` = `getupload_inline()` (เจ้าของ settings), `CashbackMailer` ใช้แม่แบบเดียวกับ `App\Domain\Payment\PaymentMailer`

## ผลเทียบกับเว็บเดิม (:8101 vs :8207, cookie เดียวกัน, ตัดช่องว่าง + แทน href)

ข้อมูลทดสอบ: role `zz_points_tester` (ได้สิทธิ์ point/admin_point/admin_withdrawpoint/shop_cashback/admin_shop_cashback) + user `zz_pts_tester`
พร้อมแถว `balance_point`/`point`/`shop_cashback(_detail)`/`balance`/`balance_cn` ของ user นั้น — ลบทิ้งหลังทดสอบ; นอกจากนั้นใช้ข้อมูลจริงใน bench (shop_cashback 196 แถว, user 380 มี 63 รายการ)

ตรงกันทุกตัวอักษร (หลังตัดช่องว่าง):
- หน้า `/point`, `/admin/point` (+`?status=`), `/admin/withdrawpoint` (+`/status/1`), `/admin/withdrawpoint/{id}` (รออนุมัติ/อนุมัติแล้ว/ไม่พบ/id 0),
  `/shop_cashback`, `/shop_cashback/create`, `/shop_cashback/{id}` (ของตัวเอง), `/admin/shop_cashback` (+`/status/2`), `/admin/shop_cashback/{id}`, `/admin/shop_cashback/create`, id ที่ไม่มี
- grid ทั้ง 5: หน้า 1–5, page ว่าง, ค้นหารหัส (`POINT-…`, `CB-SHOP-…` ตัวเล็ก/ใหญ่, รหัสลูกค้า), กรองสถานะ `-2/-1/0/1/2`, ประเภท (admin point = ว่างเพราะ SQL เดิม error), ไม่มีสิทธิ์ (ว่าง), ภาษา th/en/cn
- `load_withdraw`, `load_calculator` (JSON byte ต่อ byte), `load_new_line`, `save_withdraw` ทางที่ไม่เขียน DB (ยังไม่มี PIN → กล่องตั้ง PIN, ยังไม่กรอก PIN → กล่อง PIN, ไม่มีสิทธิ์ → ว่าง)
- ErrorBox ไม่มีสิทธิ์ของ ajax แอดมินทุกตัว

ที่ต่างเฉพาะส่วนที่ตั้งใจ: ค่าจาก DB/ผู้ใช้ถูก escape (`detail`, `remark`, `cancel_remark`, คำค้น `'` → `&#039;`), หน้าไม่มีสิทธิ์ (ดูด้านล่าง)

ทางที่เขียน DB ทดสอบเฉพาะฝั่งใหม่ แล้วเทียบแถวกับ SQL ของไฟล์เดิม (คอลัมน์/ค่า/รูปแบบ detail/prefix ตรงกัน):
`save_withdraw` (0, `0.00`, `abc`, ว่าง, เกินยอด, PIN ผิด, `-10.5` → 10.5), withdrawpoint `save_approved`/`cancel`, shop_cashback `save` (2 แถว, ราคา `abc`, วันที่ว่าง, ไฟล์แนบใน temp → JS `_api/blockblob_ajax` byte ต่อ byte), `save_remark` (ของตัวเอง/ของคนอื่น), แอดมิน `save`, `save_remark`, `save_approved` (ซ้ำ, ราคา 0), `save_cancel`
— **ไม่ได้ยิง endpoint ที่เขียน DB ของเว็บเดิม** เพราะ `mysqlquerys()` → `goLog()` เขียน log ไปที่ webroot production
อีเมล: `MAIL_MAILER=log` (ลง `storage/logs/laravel.log` ไม่ส่งจริง), LINE/push เดิมเป็นฟังก์ชันว่าง = ไม่ยิงอะไร

## bench (`bin/bench`, -n 30 -c 1, 2 รอบ, artisan optimize)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/point` (หน้าเต็ม) | 414–521 | 33–40 |
| `/_point/grid` | 57–60 | 30–31 |
| `/_shop_cashback/grid` (user 380) | 48–55 | 28–31 |
| `/_admin/_shop_cashback/grid` | 62–66 | 37–38 |
| `/admin/shop_cashback/153` (หน้าเต็ม) | 366–436 | 45–56 |
| `/_admin/_withdrawpoint/grid` | 27–45 | 28–30 |

## ต่างจากเดิมโดยตั้งใจ

- **กันกดซ้ำ/ส่งซ้อน (double submit)** (เจ้าของอนุมัติ 2026-10-18, E2E: ดับเบิลคลิก `_taobao/save` = 2 รายการ ตัดเงิน 2 ครั้ง — เว็บเดิมก็เป็น): middleware `legacy.once` (`App\Support\SubmitGuard`) — lock ต่อ ผู้ใช้+route ระหว่างประมวลผล (คำขอที่ซ้อนได้ `ErrorBox('รายการนี้กำลังดำเนินการ กรุณารอสักครู่')`) + payload เดิมที่สำเร็จแล้วภายใน 8 วินาทีได้ `ErrorBox('รายการนี้ถูกส่งไปแล้ว (ส่งซ้ำ) ...')` (คำตอบ error/กล่อง PIN ไม่ถูกจำ ส่งใหม่ได้ทันที): `_point/save_withdraw`, `_shop_cashback/save` · test: `tests/Feature/SubmitGuardTest.php`, `tests/Feature/Money/DoubleSubmitDbTest.php` (JL_BENCH_DB_TESTS=1)
  - ชั้นนี้กันรายการซ้ำ; การกันยอดติดลบเมื่อคำขอต่างกันมาพร้อมกันยังเป็น transaction + `SELECT … FOR UPDATE` แถว user แล้วตรวจยอดภายใน (ทุก endpoint ตัดเงินตอนสร้าง)
- `_point/save_withdraw`: แต้มที่มี `,` แบบกำกวม → `ErrorBox`; `1,000` → 1000 (เดิม MySQL ตัดเหลือ 1)
- `_admin/_withdrawpoint/save_approved` ของรายการที่ยกเลิกแล้ว → `ErrorBox('รายการนี้ถูกยกเลิกไปแล้ว ไม่สามารถอนุมัติได้')` (เดิมตอบ "สำเร็จ" โดยไม่ทำอะไร)
- ค่าจาก DB/ผู้ใช้ escape ทั้งหมด (เดิม echo ดิบ): `balance_point.detail`, `shop_cashback.remark/remark_admin`, `cancel_remark`, `order`, `attachment` (ใน `GetFancyBox`), รหัสลูกค้า, คำค้นใน `value=`, `page`/`status` ที่ฝังใน JS
- `load_new_line`: `item` บังคับเป็นจำนวนเต็ม (เดิมฝังดิบเป็นชื่อ class/ฟังก์ชัน JS = XSS) — JS เดิมส่งตัวเลขเสมอ ผลเท่าเดิม
- N+1: ยอดคงเหลือ (SUM ต่อแถว) ใน grid แต้มลูกค้า/แอดมิน → subquery ในคำสั่งเดียว; รายการย่อย `shop_cashback_detail` ต่อแถวใน grid ลูกค้า/แอดมิน (ไม่มี index = full scan ทุกแถว) → `WHERE shop_cashback_id IN (...)` ครั้งเดียว
- `save_withdraw`: ห่อ `DB::transaction()` + `SELECT … FOR UPDATE` แถว user แล้วตรวจยอดใน transaction (เดิมกดซ้อนกันแลกเกินยอดได้); PIN เทียบแบบ `hash_equals` (เดิม `!=` หลวม)
- withdrawpoint `save_approved` / `cancel`: ทำเฉพาะรายการที่ "รออนุมัติ" (status 1) ใน transaction + lock — ดูช่องโหว่ข้อ 4–5; id ที่ไม่มีจริงไม่ INSERT แถวขยะ
- shop_cashback `save_approved`: transaction + lock (อนุมัติซ้อน = เติมหยวนซ้ำ); `save_cancel`: ไม่ยกเลิกรายการที่อนุมัติแล้ว (ยกเลิกแล้วอนุมัติใหม่ = เติมเงินซ้ำ) และไม่ส่งอีเมลเมื่อไม่ได้ทำอะไร
- **shop_cashback ยอด <= 0 (แก้ 2026-10-18, fix/legacy-risks, เจ้าของอนุมัติ — แก้เฉพาะ Laravel)**: เดิมลูกค้าส่งยอดติดลบ/0/"abc" ได้ แล้วถ้าแอดมินกดอนุมัติ = INSERT `balance_cn` ติดลบ (หักเงินหยวนลูกค้า)
  → `_shop_cashback/save`: ทุกแถวต้องผ่าน `Money::validRefillAmount()` (ตัวเลข > 0 ทศนิยมไม่เกิน 2 ตำแหน่ง, `,` คั่นหลักพันได้ — เก็บแบบไม่มี `,`; เดิม MySQL ตัด `1,000` เหลือ 1) ไม่งั้น `ErrorBox('กรุณาระบุจำนวนเงินที่ขอคืนให้ถูกต้อง ...')` ไม่สร้างอะไร; `total` = ผลรวมของแถวที่ผ่าน
  · `_admin/_shop_cashback/save_approved`: รายการย่อยที่ `price <= 0` → `ErrorBox('ยอดเงินของรายการนี้ไม่ถูกต้อง (ต้องมากกว่า 0) ไม่สามารถอนุมัติได้')` สถานะไม่เปลี่ยน ไม่เติม/หักเงิน (เดิม 0 = อนุมัติโดยไม่เติมเงิน) · test: tests/Feature/Money/LegacyRisksDbTest.php
- shop_cashback `save`: ห่อ transaction; JS `blockblob_ajax` ตอบเฉพาะไฟล์ที่ชื่อปลอดภัยและมีจริงใน `legacy.upload.temp_dir` (เดิม `file_exists('../upload/temp/'.$x)` = path traversal และชื่อว่างก็ผ่านเพราะเป็นโฟลเดอร์)
- `email_approved` / `email_cancel` เรียกตรง: ต้องมีสิทธิ์ `admin_shop_cashback`, รับ `id`/`ids` ทาง POST (เดิมไม่ตรวจอะไรเลย รับค่าผ่าน `extract($_GET)`), ตอบว่างเหมือนเดิม
  และส่งเฉพาะเมื่อรายการย่อย `ids` เป็นของหัวรายการ `id` จริง (เดิมจับคู่มั่วได้ → อีเมลถึงลูกค้า A พร้อมเลขคำสั่งซื้อ/ยอดของลูกค้า B)
- withdrawpoint `save_approved`/`cancel` เดิม `include_once('email_*.php')` ไฟล์ที่ไม่มีในโฟลเดอร์นั้น → ไม่มีอีเมล (เหมือนเดิม; ไม่พิมพ์ warning)
- หน้าไม่มีสิทธิ์: ใช้ `legacy.perm` (layout + ErrorBox → dashboard) — เดิมแสดงหัวข้อ/ปุ่มของหน้าก่อนเด้ง ErrorBox; `/shop_cashback/{id}` ของคนอื่นตอบหน้าเต็ม (เดิม exit กลาง layout)
- ajax ที่ยังไม่ล็อกอิน: redirect `/login` (เดิม grid ตอบว่าง ฯลฯ); POST ต้องผ่าน `LegacyCsrf` (เดิมไม่มีการกัน CSRF)
- `save.php` ลูกค้า: สร้างรายการใหม่เสมอ (เดิม `if($id == "")` ใช้ `$id` จาก `extract($_GET)` → `?id=x` ทำให้ไม่บันทึกอะไร)
- `/admin/point` ที่กรองประเภท (`?status=` ไม่ว่าง): ตอบ "ไม่พบข้อมูล" ตรงๆ — เดิม SQL `AND type = …` ชน `user.type` (ambiguous) → mysqli false = ไม่มีแถว (ผลเท่ากัน)

## ช่องโหว่เดิมที่เจอ (แก้แล้วในฝั่งใหม่)

1. SQL injection ทุกไฟล์: `txtsearch`, `id`, `remark`, `remark_admin`, `cancel_remark`, `wantcash`, `order[]`/`date[]`/`price[]`/`upload_file_*` ต่อ string ตรง — ใหม่ bind ทุกค่า
2. Stored XSS: `balance_point.detail`, `shop_cashback.remark` (ลูกค้าพิมพ์ → แสดงในหน้าแอดมิน), `cancel_remark`, `order`, `attachment`; reflected XSS: `txtsearch`, `page`, `item`
3. CSRF: save/approve/cancel ทุกตัวไม่มีการกัน
4. `_admin/_withdrawpoint/cancel.php` ไม่ตรวจสถานะ: ยกเลิกซ้ำ = คืนแต้มซ้ำไม่จำกัด; ยกเลิกรายการที่อนุมัติแล้ว = ลูกค้าได้ทั้งเงินบาทและแต้มคืน; id ไม่มีจริง = INSERT `balance_point` user ว่าง
5. `_admin/_withdrawpoint/save_approved.php` อนุมัติได้ทุกสถานะที่ไม่ใช่ 2 (รวมที่ยกเลิกแล้ว → ได้แต้มคืน + เงิน)
6. Race condition: แลกแต้มเกินยอด (`save_withdraw`), อนุมัติคืนเงินร้านค้าซ้อน (`save_approved`) — ไม่มี transaction/lock
7. `email_approved.php` / `email_cancel.php` เรียกได้โดยไม่ล็อกอิน → สั่งส่งอีเมลแจ้งลูกค้าใดก็ได้ (spam/phishing จากโดเมนบริษัท)
8. `load_new_line.php` / `getupload_inline()` ฝัง `$_POST['item']` เป็นชื่อฟังก์ชัน JS

## ให้เจ้าของตัดสินใจ

- `admin_point`, `admin_withdrawpoint` ไม่มี role ใดได้รับใน DB → เดิมเข้าไม่ได้แม้แอดมิน — **Laravel ใช้ได้แล้ว (2026-10-18)** ผ่าน fallback: `admin_point` ← `admin_shop_cashback`, `admin_withdrawpoint` ← `admin_exchange` (role 1, 5) — CONVENTIONS ข้อ 3.1;
  `point` มีเฉพาะ role 6 (คนกดออเดอร์) — ลูกค้า (role 3) เปิด `/point` ไม่ได้ (เหมือนเดิม)
- withdrawpoint form ที่ id 0 (`/admin/withdrawpoint/0`) แสดงฟอร์มที่ submit ไป `_admin/_refill/save` (กลุ่ม money-move) — คงไว้เหมือนเดิม
- ปุ่ม Delete/DeleteAll ใน JS ของ grid ทั้งหมดเรียก `<grid>/../delete` ซึ่งไม่มีไฟล์จริง (โค้ดตาย) — คงไว้; หน้า admin shop_cashback เรียก `CalculatePrice()` ที่ไม่มีเมื่อเปิด `/create` (JS error เดิม) — คงไว้
- ข้อเสนอ index (ไม่ได้สร้าง): `shop_cashback_detail(shop_cashback_id)`, `balance_point(user, is_delete)` — ตอนนี้ทุก query ของสองตารางนี้เป็น full scan

## ฐานร่วมที่แก้

- `foundation: keep raw POST values (drop TrimStrings / ConvertEmptyStringsToNull)` — cherry-pick commit เดียวกับ `mod/tracking` / `mod/balance` / `mod/payment` (diff เหมือนกันทุกบรรทัด, merge ไม่ชน): คำค้น/หมายเหตุเดิมไม่ถูก trim และ `id=''` ต้องเป็น `''`
- ไม่ได้แก้ไฟล์ฐานร่วมอื่น

## รีวิว (adversarial, HTTP จริง :8101 vs :8207)

- เทียบหน้า/grid/ajax ที่อ่านอย่างเดียวด้วย session ลูกค้า (137, 380), แอดมิน (11938), role 6 และ role ทดสอบ `zz_points_rev` ที่มีสิทธิ์ครบ 5 โมดูล
  พร้อมแถวทดสอบ zz ใน `balance_point`/`point`/`shop_cashback(_detail)`: ตรงกันหลังตัดช่องว่าง ยกเว้นการ escape ที่ตั้งใจ (ภาษา th/en/cn, หน้า 1–3, page ว่าง/abc/1.5/2e0, คำค้น, สถานะทุกค่า)
- ต่างเล็กน้อย (ฐานร่วม `Paging::getpaging`): `page` ที่ไม่ใช่ตัวเลข (`abc`, `1.5`) ปุ่มเลขหน้าไม่เหมือนเดิม (ข้อมูลในตารางเหมือนเดิม — JS เดิมส่งตัวเลขเสมอ)
- IDOR: ลูกค้าเปิด/แก้หมายเหตุ `shop_cashback` ของคนอื่นไม่ได้, grid แต้มเห็นเฉพาะของตัวเอง
- SQL injection (เฉพาะฝั่งใหม่): payload ใน `txtsearch`/`page`/filter ของทุก grid ไม่เพิ่มแถว; write ด้วย id แถว zz เท่านั้น — กระทบเฉพาะแถวที่ตั้งใจ
- CSRF: ทุก POST ที่เขียนข้อมูล 403 เมื่อ Origin/Referer ข้ามโดเมน หรือไม่มีทั้ง Origin/Referer/X-Requested-With
- race: `save_withdraw` ซ้อน 2 ครั้ง (เกินยอด) ผ่านครั้งเดียว; อนุมัติคืนเงินร้านค้าซ้อน 2 ครั้ง = balance_cn แถวเดียว; อนุมัติ+ยกเลิกแลกแต้มพร้อมกัน = มีผลอย่างเดียว

## E2E admin write-flow (2026-10-18, fix/admin-writes)

- `_admin/_shop_cashback/save_cancel` กดซ้ำ/ซ้อน: เดิมเขียนทับเหตุผลยกเลิกและส่งอีเมล/แจ้งเตือนยกเลิกซ้ำทุกครั้ง → รายการที่ยกเลิกแล้วไม่ทำอะไร · test: tests/Feature/Money/AdminWritesDbTest.php (JL_BENCH_DB_TESTS=1)
- ไม่มี endpoint "ปรับแต้มโดยแอดมิน" ในเว็บเดิม (`_admin/_point` มีแค่ index/grid) — ทดสอบ withdrawpoint อนุมัติ/ยกเลิก (ซ้อน 3 คำขอ = เติมเงิน/คืนแต้มครั้งเดียว) และ cashback แทน
- ยังเหมือนเดิม: ลูกค้าแจ้งยอดคืนเงินติดลบได้ (`_shop_cashback/save` price -5) ถ้าแอดมินอนุมัติ = หักเงินหยวนลูกค้า
