# กลุ่ม points — รายงานการพอร์ต

## PACK (2026-10-22)

พอร์ตใน worktree เดียวกับ messaging/balance: branch `mod/messaging` (`/home/admin/laravel/pack-wt/messaging`, port 8811) — เทียบกับ PACK เดิม `:8114` (`/home/admin/bench/pack-old`, DB `admin_pack_bench`)
คงทุกการแก้ของ jl/SR (withdrawpoint อนุมัติ/ยกเลิกเฉพาะรายการรออนุมัติใน transaction + lock, PIN ผ่าน `PinBox::guard()`, `legacy.once`, ยอด shop_cashback > 0, escape ค่าจาก DB/ผู้ใช้)

### ไฟล์เดิม → สถานะ (docs/pack-diff.md)

| ไฟล์ | สถานะ |
|---|---|
| `_point/save_withdraw.php` (diff 4, jl 0) | **ทำแล้ว** — PACK = jl: ตรวจ PIN แล้ว**แลกแต้มจริง** (SR ตอบ "ยังไม่เปิดบริการให้แลก" แล้ว exit ทุกครั้ง) → นำโค้ด jl กลับมา (revert commit SR `b3d1ffd`): transaction + `SELECT … FOR UPDATE` แถว user, ตรวจยอดด้วย `Rules::withdrawInvalid` (นิพจน์ 7.4 เดิม, fixture php7.4), `,` กำกวม = ErrorBox, INSERT `point` (status 1) + `balance_point` (-ยอด, type 1, prefix withdrawpoint) |
| `_point/index.php` (diff 11, jl 0) | **ทำแล้ว** — JS ของ jl/PACK: ช่องว่าง = `ErrorBox('กรุณากรอกจำนวน')`, ConfirmBox → `withdrawpointing()` ส่ง `wantcash` + `paymentpin` (กล่อง PIN เรียก callback นี้ได้แล้ว — ใน SR เป็น JS error เงียบ) |
| `_admin/_shop_cashback/email_approved.php`, `email_cancel.php` (diff 26) | **ทำแล้ว** — ปุ่ม "ดูรายละเอียด" สี `#eb4823` (SR `#a3b2f1`), ไม่มี `sendLine()`/`push_notification()` (ไม่เลือก `line_uid`); แม่แบบ/ผู้ส่ง = `config('legacy.mail')` (PACK-Cargo <no-reply@pack-cargo.net> จากฐานร่วม); เปิดตรง = 403 (hotfix 2026-10-18 มีใน PACK ด้วย) |
| `getupload_inline()` (system_config.php — ใช้ใน `_shop_cashback/form`, `load_new_line`) | **ทำแล้ว** — PACK ใช้รุ่นเก่ากว่า SR/jl: ไม่มีตัวล้าง input (`change.reselect`), หลังอัปโหลด `.zip/.rar/.7z` แสดงชื่อไฟล์ นอกนั้นแสดงรูปย่อ; ไฟล์เดิมเป็นรูปเมื่อนามสกุลตรงตัวใน `jpg png jpeg JPG JPEG PNG` ไม่ใช่รูป = `<a class='fancybox'>` ไม่มี `download` (`App\Domain\Points\UploadInline`) |
| ที่เหลือ 23 ไฟล์ (`same`) | ไม่เปลี่ยน — เทียบกับ :8114 แล้วตรง (ด้านล่าง) |

`calPoint()` ของ PACK = SR ทุกบรรทัด (รวม `calpoint-dedupe-guard-v1`) → `App\Support\Legacy\PointCalc` ของฐานร่วมใช้ได้เลย (ไม่มีโค้ดในกลุ่ม points เรียก) — ด่านกันแจกซ้ำนับเฉพาะ prefix export ซึ่งสำคัญขึ้นใน PACK เพราะแถวแลกแต้ม (type 1, prefix withdrawpoint, ยอดติดลบ) เกิดขึ้นจริง

### สิทธิ์ของ PACK (admin_pack_bench)

`point` = role 1, 2 (ลูกค้า), 4, 7, 8, 9 · `shop_cashback` = 1, 2, 4, 5, 7, 8, 9 · `admin_shop_cashback` = 1, 4, 5 · `admin_point` = 1 · `admin_withdrawpoint` = 1, 4 — ไม่มีสิทธิ์กำพร้า (ไม่ต้อง fallback)

### ผลเทียบกับ :8114 (session เดียวกัน, ตัดช่องว่าง + แทน href)

ตรงกันทั้งหมด (ไม่นับ `target="_blank"`, escape และ `.text()` ของ upload):
`/point` (ลูกค้า 348: 1,297 แถว), `_point/grid` หน้า 1/2/130 + ค้นหา `PAY-1`, `|1`, `WPOINT|2`, ภาษา cn, `load_withdraw`;
`/shop_cashback`, `/shop_cashback/101`, `/shop_cashback/create`, `_shop_cashback/grid` (ผู้ใช้ 50) หน้า 1/2 + ค้นหา, `load_new_line`, `load_calculator` (byte);
`/admin/point`, `/admin/withdrawpoint` (+`/status/1`), `/admin/withdrawpoint/{0,253,254,255}`, `/admin/shop_cashback` (+`/101`, `/create`), grid แอดมินทั้ง 3 หน้า 1/2 + ค้นหารหัสลูกค้า + สถานะ `|1`, `|-1`, `|2`;
แถว zz ของผู้ใช้ทดสอบ (แลกแต้ม/คืนเงินร้านค้า) ใน grid ลูกค้า/แอดมินตรงกัน
ปฏิเสธ: role 6 (ผู้ใช้ 5) `_point/grid` / `save_withdraw` = ว่าง, ลูกค้า 348 grid แอดมิน = ว่าง, `save_approved` = ErrorBox สิทธิ์ (ตรง byte); หน้า `/point` (role 6), `/admin/point`, `/admin/withdrawpoint` (ลูกค้า) = layout + ErrorBox ข้อความเดียวกัน (เดิมแสดงหัวหน้าก่อน ErrorBox — ข้อ jl)

### E2E (เขียนเฉพาะฝั่ง Laravel — ผู้ใช้ทดสอบ zz_msg_cust (12286) ที่สร้างเอง + แอดมิน 11938, ลบตาม id แล้ว)

- `_point/save_withdraw`: ยังไม่มี PIN → กล่องตั้ง PIN · ว่าง/`undefined` → กล่อง PIN callback `withdrawpointing()` ใน `.cashback_form` · ผิด → ErrorBox · แต้ม 0 / `0` / `0.00` / `abc` / เกินยอด / `1,000` (เกิน) → "แต้มไม่เพียงพอในการแลก" · `1,00` → ErrorBox ยอดกำกวม · `-10.5` ซ้อน 2 → สำเร็จ 1 + "กำลังดำเนินการ", ส่งซ้ำ → "ส่งซ้ำ" · `5` → สำเร็จ
  แถวตรงกับ SQL เดิม: `point` (status 1, amount 10.50, date NOW, attachment/remark/approved_remark `''`, create_by = ผู้ใช้), `balance_point` (-10.50, `แลกแต้ม รายการแลกแต้ม WPOINT-254`, type 1, prefix 31, prefix_ref_id 254)
- `_admin/_withdrawpoint/save_approved` ซ้อน 2 → `balance` แถวเดียว (+10.50 type 1 prefix 31 `แลกแต้ม รายการแลกแต้ม WPOINT-254`), `cancel` ซ้อน 2 → `balance_point` คืนแต้มแถวเดียว (`ยกเลิกรายการแลกแต้ม …`, cancel_remark `zz_no ,ยกเลิกโดย ZZFDN2`), ยกเลิกรายการที่อนุมัติแล้ว = ไม่ทำอะไร, อนุมัติรายการที่ยกเลิกแล้ว = ErrorBox, ลูกค้าเรียก = ErrorBox สิทธิ์
- `_shop_cashback/save`: กล่อง PIN / PIN ผิด / ยอด -5 ปฏิเสธ / ส่งซ้อน 2 = 1 รายการ (`total` 1012.50, `1,000` → 1000.00, วันที่ `5 ตุลาคม 2026|14:05` → `2026-10-05 14:05:00`) · `save_remark` ของตัวเอง = แก้, ของคนอื่น = ไม่แก้
- แอดมิน `save_approved` ซ้อน 2 → `balance_cn` แถวเดียว (type 9 prefix 28), `save_cancel` ซ้อน 2, `save_remark`, `save` (remark_admin) — อีเมล 2 ฉบับลง log mailer: From `PACK-Cargo <no-reply@pack-cargo.net>`, ปุ่ม `#eb4823`
- ลบแล้ว: point 254–255, balance_point 12445–12447, 12450, balance 64832, shop_cashback 119, shop_cashback_detail 129–130, balance_cn 2208, ips 479–481, ผู้ใช้ 12286 (+ แถวของกลุ่ม balance — ดู balance.md)

### bench (`bin/bench`, -n 20 -c 1, artisan optimize; เครื่อง load ~8–10)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/point` (ลูกค้า 348, หน้าเต็ม) | 449 | 62 |
| `/_point/grid` (ลูกค้า 348, 1,297 แถว) | 266 | 163 |
| `/_admin/_withdrawpoint/grid` | 61 | 37 |

### ให้เจ้าของตัดสินใจ

- การแลกแต้มของ PACK **เปิดอยู่** (ต่างจาก SR) — Laravel ทำตาม PACK (แลกได้จริง, แอดมินอนุมัติ = เติมเงินบาท)
- `getupload_inline()` ของ PACK เป็นรุ่นเก่า (ไฟล์ PDF หลังอัปโหลดแสดงเป็นรูปเสีย, เลือกไฟล์เดิมซ้ำไม่ได้หลังกดลบ) — ทำตาม PACK; ถ้าต้องการพฤติกรรมที่แก้แล้วของ SR/jl ให้สั่ง

---

## SR (2026-10-20)

พอร์ตกลุ่ม points ให้ตรงกับเว็บเดิมของ SR บน branch `mod/points` (worktree `/home/admin/laravel/sr-wt/points`, port 8307) — โค้ดตั้งต้นคือที่ jl พอร์ตไว้ (รายงานเดิมด้านล่าง) คงทุกการแก้ช่องโหว่/การตัดสินใจของ jl:
withdrawpoint อนุมัติ/ยกเลิกเฉพาะรายการรออนุมัติ (transaction + lock), เทียบ PIN ผ่าน `PinBox::guard()` (hash_equals + PinThrottle), `load_new_line` item เป็นจำนวนเต็ม, ยอด shop_cashback ต้อง > 0, `legacy.once` กันกดซ้ำ, escape ค่าจาก DB/ผู้ใช้

### ไฟล์เดิม → สถานะ

| ไฟล์ (docs/sr-diff.md) | สถานะ |
|---|---|
| `_point/index.php` (diff 11) | **ทำแล้ว** — JS ของ SR: `withdrawpoint()` = ConfirmBox แล้วส่งแค่ `wantcash` (ไม่ตรวจช่องว่าง, ไม่ส่ง `paymentpin`), ไม่มีฟังก์ชัน `withdrawpointing()` |
| `_point/save_withdraw.php` (diff 4) | **ทำแล้ว** — SR ตรวจ PIN (ยังไม่มี PIN → กล่องตั้ง PIN, ยังไม่กรอก/`undefined` → กล่อง PIN callback `withdrawpointing()`, ผิด → ErrorBox + แถว ips) แล้วตอบ `ErrorBox('ยังไม่เปิดบริการให้แลก', 'CloseLoading()')` + `exit()` **ทุกครั้ง** → โค้ดแลกแต้ม (INSERT `point`/`balance_point`) ไม่เคยทำงาน → ตัดทิ้ง ไม่เขียน DB เลย (ลบ `Rules::withdrawInvalid` + fixture ที่ใช้เฉพาะทางนี้) |
| `_shop_cashback/save.php` (diff 32) | **ทำแล้ว** — SR เปิดบล็อก PIN จ่ายเงิน (jl comment ไว้): `getNewPinBox()` / `getPinBox(0)` ต่อท้าย `form` แล้ว `_profile/pin` ส่งฟอร์มซ้ำพร้อม `paymentpin` — ใช้ `PinBox::guard($request, user)` ก่อนตรวจอย่างอื่นทั้งหมด |
| `_admin/_shop_cashback/email_approved.php`, `email_cancel.php` (diff 2) | **ทำแล้ว** — ปุ่ม "ดูรายละเอียด" ของ SR (`width:100%` สี `#a3b2f1` padding `10px 0px`, ไม่มี target=_blank), แม่แบบ header/center/footer + ผู้ส่ง `config('legacy.mail')` (SRShippingSystem); เปิดตรงจาก URL (GET/POST) = 403 ว่าง ตาม hotfix 2026-10-18 ของ SR (เดิมของ jl-port = ส่งอีเมลได้เมื่อมีสิทธิ์) — อีเมลส่งจาก save_approved/save_cancel เท่านั้น |
| ที่เหลือ 22 ไฟล์ (`same`) | ไม่เปลี่ยน — เทียบผลกับ :8111 แล้ว (ตารางด้านล่าง) |

ไม่มีไฟล์ SR-only / JL-only ในกลุ่มนี้

### สิทธิ์ของ SR (admin_sr_bench, role_on_detail)

- `point` = role 1, 9, 10, 11, 12, 13 (ลูกค้า role 8 **ไม่มี** → `/point` ปฏิเสธ เหมือนเดิม) · `shop_cashback` = ทุก role (1, 7–13) · `admin_shop_cashback` = 1, 10, 11, 12, 13
- `admin_point`, `admin_withdrawpoint` = **role 1 เท่านั้น** — ใช้ตามที่ DB ถือ (SR ไม่มี fallback: `permission_fallbacks` ว่าง) — ข้อความ "fallback ← admin_shop_cashback / admin_exchange" ในรายงาน jl ด้านล่าง **ไม่ใช้กับ SR**
- ทดสอบปฏิเสธ: ลูกค้า 3 (`/point`, `/admin/*`), role 12 (user 6: `/admin/point`, `/admin/withdrawpoint`, `_admin/_withdrawpoint/save_approved` → ErrorBox สิทธิ์, grid → ว่าง)

### ผลเทียบกับ :8111 (session เดียวกัน, ตัดช่องว่าง + แทน href, ไม่นับ favicon/morris ของ layout)

ตรงกันทุกตัวอักษร: `/point` (user 2: 101 แถว, 11 หน้า), `_point/grid` หน้า 1/2/11, ค้นหา `PAY-20`/`POINT-1`, ตัวกรองประเภท `|1|2|3|9`, ภาษา th/en/cn, `load_withdraw`;
`/admin/point` (+`?status=1`), `_admin/_point/grid` หน้า 1/2/3, ค้นหา `SRC`/`SRC-A`, ตัวกรอง; `/admin/withdrawpoint` (+`/status/1`), `/admin/withdrawpoint/{0,1,2,3,99999}`, `_admin/_withdrawpoint/grid` สถานะ `-2/1/2/-1` + ค้นหารหัสลูกค้า/`POINT-2`;
`/shop_cashback`, `/shop_cashback/create`, `/shop_cashback/{id}`, `_shop_cashback/grid` (+ค้นหา `CB-SHOP-…`), `load_new_line`, `load_calculator` (JSON byte ต่อ byte);
`/admin/shop_cashback` (+`/status/2`), `/admin/shop_cashback/{create,id}`, `_admin/_shop_cashback/grid` (+สถานะ) — ข้อมูล point / shop_cashback ใน bench ว่าง จึงใช้แถว zz ของ user 11937 (ด้านล่าง)
ต่างเฉพาะที่ตั้งใจ: ลิงก์ `target="_blank"` ใน grid แต้ม (ตัดออก), escape ค่า (`remark` ที่มี `<b>`, คำค้น), หน้าไม่มีสิทธิ์ (layout + ErrorBox), `/shop_cashback/{id}` ของคนอื่น = หน้าเต็ม (เดิม exit กลาง layout)

### E2E (เขียนเฉพาะฝั่ง Laravel, user 11937/11938 + แถว zz_, ลบตาม id แล้ว)

- `_shop_cashback/save`: ไม่มี PIN → กล่องตั้ง PIN · ไม่กรอก → กล่อง PIN · ผิด → ErrorBox + ips 1 แถว · ยอด -5 → ErrorBox ยอดไม่ถูกต้อง · ถูก + ส่งพร้อมกัน 2 ครั้ง → สร้าง 1 รายการ (อีกคำขอ "กำลังดำเนินการ") + ส่งซ้ำ → "ส่งซ้ำ" · แถวตรงกับ SQL เดิม (`total` 1012.50, price `1,000` → 1000.00, `submit_date` จาก `5 ตุลาคม 2026|14:05` = `2026-10-05 14:05:00`)
- `_shop_cashback/save_remark` ของตัวเอง = แก้, ของคนอื่น = ไม่แก้ (ตอบสำเร็จเหมือนเดิม)
- แอดมิน `save`, `save_remark`, `save_approved` ซ้อน 2 = `balance_cn` แถวเดียว (type 9, prefix 28), `save_cancel` + ยกเลิกซ้ำ = ไม่เขียนทับ, อีเมล (log mailer) จาก SRShippingSystem ปุ่ม SR; `email_*` ตรง = 403
- withdrawpoint `save_approved` ซ้อน 2 = `balance` แถวเดียว, `cancel` ซ้อน 2 = `balance_point` คืนแต้มแถวเดียว, ยกเลิกรายการอนุมัติแล้ว = ไม่ทำอะไร, อนุมัติรายการยกเลิกแล้ว = ErrorBox
- `_point/save_withdraw`: กล่องตั้ง PIN / กล่อง PIN (`.cashback_form`, `withdrawpointing()`) / PIN ผิด / PIN ถูก = `ยังไม่เปิดบริการให้แลก` (กด 2 ครั้ง) — `point`/`balance_point` ไม่เปลี่ยน
- ลบแล้ว: shop_cashback 79–80, shop_cashback_detail 157–159, point 1–4, balance_cn 540–541, balance 3461, balance_point 250, ips 147–148; PIN ของ 11937/11938 คืนค่าเดิม (`''` / NULL)

### bench (`bin/bench`, -n 30 -c 1, 2 รอบ, artisan optimize)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/point` (user 2, หน้าเต็ม) | 357–361 | 39 |
| `/_point/grid` (user 2) | 67–71 | 49–53 |
| `/_admin/_point/grid` | 60–78 | 70 |
| `/_admin/_shop_cashback/grid` (ว่าง) | 23–58 | 29–30 |
| `/_admin/_withdrawpoint/grid` (ว่าง) | 21–22 | 29–30 |
| `/shop_cashback` (หน้าเต็ม) | 365–448 | 34–35 |

ช้ากว่า/ใกล้เคียง: grid ที่ไม่มีข้อมูล = overhead คงที่ของ Laravel (~25 ms); `_admin/_point/grid` ยอดคงเหลือเป็น subquery SUM ต่อแถวบน `balance_point` ที่ไม่มี index (เดิม N+1 แต่ตารางเล็ก 198 แถว) — ข้อเสนอ index `balance_point(user, is_delete)` ด้านล่างเดิมยังใช้ได้

### ช่องโหว่/ข้อสังเกตของ SR เดิม

1. `_point/index.php` ของ SR ไม่มี `withdrawpointing()` แต่ `save_withdraw` ส่ง callback นั้นให้กล่อง PIN → กรอก PIN แล้ว JS error เงียบ ผู้ใช้ไม่เห็นข้อความ "ยังไม่เปิดบริการให้แลก" เลย (คงไว้เหมือนเดิม 1:1)
2. `save_withdraw.php` / `_shop_cashback/save.php` ของ SR: `goLog()` เขียน PIN ที่พิมพ์ผิดเป็น plaintext ลง log และเทียบ `!=` หลวม — ใหม่ไม่ log PIN, hash_equals + จำกัดการเดา
3. `_shop_cashback/save.php` ของ SR: `paymentpin=undefined` ถูกเทียบเป็น PIN (ผิด) ส่วนใหม่ถือเป็น "ยังไม่กรอก" (กล่อง PIN) เหมือน `save_withdraw` — JS เดิมไม่ส่งค่านี้
4. ช่องโหว่อื่นของไฟล์ `same` เหมือนรายงาน jl ด้านล่าง (SQL injection, XSS, CSRF, race) — แก้แล้วฝั่งใหม่

### ให้เจ้าของตัดสินใจ

- การแลกแต้มของ SR ปิดอยู่ ("ยังไม่เปิดบริการให้แลก") — ถ้าจะเปิด ต้องตัดสินใจ: เปิดแบบโค้ดเดิม (ที่ตามมาหลัง exit) + เพิ่ม `withdrawpointing()` ในหน้า — ฝั่ง Laravel ตอนนี้ไม่มีโค้ดแลกแต้ม (ดูโค้ด jl ใน git history ก่อน commit นี้)
- `admin_point` / `admin_withdrawpoint` มีแค่ role 1 (ไม่มี fallback) — ถ้าต้องการให้ role อื่นดูแต้ม ให้ติ๊กสิทธิ์ใน role ของเว็บเดิม
- ข้อเสนอ index เดิม (`shop_cashback_detail(shop_cashback_id)`, `balance_point(user, is_delete)`) ยังใช้กับ SR

---

## jl (รายงานเดิม)

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
