# กลุ่ม reports

## PACK (2026-10-22)

พอร์ตกลุ่ม reports ให้ตรงกับเว็บเดิมของ PACK (portal.pack-cargo.net) บน branch `mod/reports` — ตั้งต้นจากโค้ด SR ที่ fork มา
PACK เป็นลูกผสม: บิลส่งสินค้าเป็นแบบ SR (ผู้ส่ง, ตัวกรองรับเอง/ส่งขนส่ง) แต่มี VAT/ส่วนลด/คูปองแบบ jl และ `_admin/_report/*` เหมือน jl ทุกตัวอักษร (diff 0) → ใช้โค้ด Laravel ของ jl
คงทุกการแก้ช่องโหว่/การตัดสินใจของเจ้าของจาก jl/SR (escape, lock, double-submit guard, สถานะที่ยกเลิก/บันทึกได้, ไม่มี target="_blank", 404 → หน้าแรก)

### ไฟล์เดิม → สถานะ (docs/pack-diff.md)

| ไฟล์ | สถานะ |
|---|---|
| `_admin/_report/index.php`, `form.php`, `grid.php`, `else.php`, `else_preorder_working.php` (diff กับ SR, jl 0) | **ทำแล้ว** — คืนโค้ด jl: ไม่มีรายงานภาษีขาย type 97, ลิงก์ยอดเคลื่อนไหว 12 เดือนของปีนี้, ไฟล์ `export.xls` |
| `_admin/_report/cashmove`, `current_cash`, `current_cash_cn`, `file`, `refill_5/6/7`, `shipping` | ไม่เปลี่ยน (same) — เทียบแล้ว (ต่างแค่เวลา generated; refill/shipping เดิม 500 PHPExcel ใหม่ = xlsx ตามการตัดสินใจของ jl) |
| `_admin/_export/form.php` (diff 88) | **ทำแล้ว** — ปุ่มพิมพ์ปุ่มเดียว, ไม่มีรหัสย่อย, แถวคูปองส่วนลดท้ายบิล + VAT, ราคากิโล/คิว + แถวยอดรวมทั้งหมด + กล่อง "รายละเอียดการชำระ" (มีรหัสคูปอง) เฉพาะสิทธิ์ `admin_viewprice_on_payment`; ไม่มีสิทธิ์นี้ = รายการเงินเฉพาะ prefix export |
| `_admin/_export/grid.php` (diff 23) | **ทำแล้ว** — ยอด `total - discount` เฉพาะสิทธิ์ `admin_viewprice_on_payment`, ป้าย VAT, ไม่มีลิงก์ใบเสร็จ, คอลัมน์จัดการ 60px (ตัวกรองรับเอง/ส่งขนส่งแบบ SR คงไว้) |
| `_admin/_export/print.php` (same) + `print_2.php` (diff 41) | **ทำแล้ว** — โลโก้ `logo1.png` + เบอร์ PACK (ฝังในไฟล์เดิม), ไม่มีรหัสย่อย/ชั้นวาง, ท้ายตาราง 2 กิ่งเหมือนกัน, ช่องเซ็น "ผู้รับสินค้า/ผู้ออกสินค้า" |
| `_admin/_export/email_success.php`, `email_cancel.php` (diff 33/24) | **ทำแล้ว** — ปุ่มสี `#eb4823`, ไม่มี LINE/push (ไฟล์ PACK ไม่มี); เปิดตรง = 403 (PACK มี hotfix บรรทัดแรกเหมือน SR) |
| `_admin/_export/index.php`, `save_approved.php`, `save_delete.php` | ไม่เปลี่ยน (same) — E2E แล้ว |
| `_export/form.php` (diff 64) | **ทำแล้ว** — ไม่มีรหัสย่อย, แถว "Discount" (`bill_payment.discount`) ทุกบิล, คูปองท้ายบิล, VAT, รหัสคูปองในกล่องการชำระ (ส่วนผู้ส่งแบบ SR คงไว้) |
| `_export/grid.php` (diff 11) | **ทำแล้ว** — ยอด `total - discount` (ลบแบบ PHP 7.4), ป้าย VAT |
| `_export/index.php`, `save_approved.php`, `save_delete.php` | ไม่เปลี่ยน (same) — E2E แล้ว |
| `_dashboard/index.php` (diff 77) | **ทำแล้ว** — สี `#eb4823`, ตารางชั้นวาง (สิทธิ์ `admin_warehouse`) แสดงแค่หัวตาราง (PACK ไม่มีตาราง `warehouse_section*` → เดิม query ล้มเงียบ ไม่มีแถว; ไม่มี role ไหนถือสิทธิ์นี้), ข้อความตีลังไม้/ที่อยู่โกดังจีน/เบอร์จีน/ติดต่อโกดังไทยฝังตามไฟล์เดิม (SR อ่านจาก setting), ตารางราคาแสดงทุกคน (ไม่มีเงื่อนไข id 891 ของ SR) |
| `download_file.php` (diff 11) | **ทำแล้ว** — ตาม hotfix ของ PACK: พนักงานที่ล็อกอิน (role ไม่ใช่ 0/2/8) แทนสิทธิ์ admin_lot/admin_lot_view ของ SR; ลูกค้า = 403 "forbidden"; ที่เหลือเหมือน SR (400 bad request / 404 not found / dev redirect) |
| `_admin/_nobilling/index.php` (diff 13) | **dead / ลบแล้ว** — `content.php` ของ PACK ไม่มี case `admin_nobilling` (RewriteRule `admin/nobilling` ได้หน้า layout ว่าง) และไม่มีไฟล์ไหนลิงก์ → ลบ route/controller/view/test |
| `_admin/_dashboard/index.php` (diff 6) | dead (ไม่มีใคร include — เหมือน jl/SR) |
| `_admin/_log/*`, `print_header.php`, `print_footer.php` | ไม่เปลี่ยน (same) — เทียบแล้ว |
| SR-only 9 ไฟล์: `_admin/_export/{calvat,print_3,print_receipt,printprice,receipt,receipt_1,receipt_2,save_receipt}.php`, `_admin/_report/printtax.php` | **ลบแล้ว** — PACK ไม่มีไฟล์และ `bill_payment` ไม่มีคอลัมน์ `receipt_*`: ลบ route `admin/export/receipt/{id}` + ajax ทั้งหมด, `AdminReceiptController`, `App\Domain\Reports\{Receipt,Running}`, view `reports/receipt/*`, `ReceiptCalcTest`, test ใบเสร็จ/เลขรันใน `ReportsSrDbTest`; ทำเครื่องหมาย dead ใน docs/url-map.md |

ไม่มีไฟล์ pack-only ในกลุ่มนี้ (ฟีเจอร์ VAT/คูปองของ PACK อยู่ในไฟล์ข้างบน)

### helper ในกลุ่ม
- `ExportBill::coupon($id)` = `SELECT * FROM coupon WHERE payment_id = ? AND is_delete = '0'` แถวแรก (ไม่มีบิล = null)
- `ExportBill::trackingRows($details, $admin, $prices)` / `balanceBox($id, $admin, $exportOnly)` รองรับสิทธิ์ `admin_viewprice_on_payment` (ตรวจใน controller ด้วย `perm()` — ยังไม่มีในฐานร่วม)
- `UploadBox::single()` = `getupload()` ของ PACK (รุ่นแบบ jl: รูป = jpg/png/jpeg ตัวเล็ก/ใหญ่ทั้งคำ, ไฟล์อื่นเป็นลิงก์ class fancybox, อัปเสร็จ zip/rar/7z แสดงข้อความ ไม่มี hotfix เลือกไฟล์ซ้ำของ SR) — ตัด `multiple()` ที่ไม่มีใครใช้

### ต่างจากเดิมโดยตั้งใจ (PACK)
- ค่าจาก DB escape (เช่น `submit_remark` ที่มี HTML แสดงเป็นข้อความ) · ไม่มี `target="_blank"` (ในกล่องอัปโหลดด้วย)
- `/export/0`: ไม่มีคูปอง/รายการเงิน (เดิม query คูปองด้วย `payment_id = ''` = ได้รหัสคูปองที่ยังไม่ถูกใช้ของใครก็ได้ — ดูช่องโหว่)
- หน้าไม่มีสิทธิ์ (admin/export ของ role 4, export/{id} ของคนอื่น): หน้าแจ้งไม่มีสิทธิ์ของ Laravel (เดิมแสดงหัวหน้าแล้ว exit กลาง layout)
- download_file ไม่ได้ล็อกอิน = ไปหน้า login (hotfix เดิม 403 "forbidden"); dev redirect ไป blob แทนการดึงไฟล์
- `admin/nobilling` = 404 → หน้าแรก (เดิมได้ layout ว่าง เพราะ content.php ไม่มี page นี้)

### ช่องโหว่/บั๊กเดิมของ PACK ที่เจอ
- `_export/form.php` ที่ id ไม่มีอยู่จริง (`/export/0`): `SELECT * FROM coupon WHERE payment_id = ''` → MySQL แปลง '' เป็น 0 → ลูกค้าคนไหนก็เห็นชื่อ/มูลค่าคูปองที่ยังไม่ถูกใช้ (bench: เห็นรหัสจริง 50 บาท) — ใหม่ไม่ query
- `_admin/_nobilling/index.php` ไม่มีสิทธิ์ = ErrorBox แต่ไม่ exit (แสดงข้อมูลต่อทั้งหน้า) — ไม่มีผลเพราะหน้าเปิดไม่ได้ใน PACK
- `print.php` เขียน DB (`is_print`, `print_date`) ตอน GET — คงด่านกัน GET ข้ามเว็บของ jl/SR

### เทียบ 1:1 กับ :8114 (session เดียวกัน, `diff -w` หลังแทน HREF)
- ลูกค้า: dashboard (348, 11938), export, export/{id} (VAT 12635, คูปอง 8378 (ผู้ใช้ 37), ส่วนลด 465 (ผู้ใช้ 91), ผู้ส่งแบบกำหนดเอง 12391 (ผู้ใช้ 470), บิลคนอื่น, /export/0), `_export/grid` (ทุกสถานะ, ค้นหา, หน้า 2)
- แอดมิน: admin/export, status/3, admin/export/{id} (role 1 และ role 7 ที่ไม่มีสิทธิ์ดูราคา, role 4), `_admin/_export/grid` (role 1/3/7 × ทุกตัวกรองรวม 4/5/-2, ค้นหา pack / pay-1263, หน้า 2–3; role 4 = ว่าง)
- print: เรนเดอร์ไฟล์เดิมด้วย php7.4 แบบตัด UPDATE ออก (ไม่ยิง :8114 เพราะไฟล์เดิมเขียน DB) เทียบกับ Laravel 4 บิล = ตรงกัน (Laravel เขียน is_print ของบิลจริง 3 ใบ แล้วคืนค่า NULL เดิม)
- รายงาน: admin/report (role 1, 3), admin/report/{1–13,20,98,99} + `_admin/_report/grid` ทุก type × มี/ไม่มีวันที่ × หน้า 1–2, file/else/else_preorder_working, cashmove, current_cash(_cn), refill_5/6/7, shipping; role 7 = grid ว่าง
- admin/log + grid (หน้า 1–2, ค้นหา), download_file (ลูกค้า/role 4/แอดมิน × path ผิด/ถูก)
- หลัง merge ฐานร่วม (0a4673f, d1d40c3): หน้าเต็ม (layout + เนื้อหา) ต่างกันแค่ `</script>` 1 บรรทัดของ layout
- ผลตรงกันหมด ยกเว้นข้อที่ตั้งใจต่างข้างบน

### E2E (ฝั่ง Laravel เท่านั้น, ผู้ใช้ 11937/11938, แถว zz_ บน admin_pack_bench)
สร้างบิล zz_e2e 6 ใบ (12717–12720, 12723–12724) + คูปอง zz_COUPON + tracking 4 + เติมเงิน 100 แล้วส่ง POST ซ้อน 2 ครั้งพร้อมกัน:
- `_export/save_approved` (สถานะ 0): สถานะ 1 + approved_date, balance -10.00 "ชำระเงิน PAY-…" type 3 prefix payment 1 แถว; อีก request ถูก `legacy.once` ปฏิเสธ
- `_export/save_delete` (สถานะ 0): สถานะ -1, cancel_remark "zz_cancel ,ยกเลิกโดย ZZFDN1", balance_point -2.00 (300/150) 1 แถว, ไม่มีเงินคืน; request ที่ 2 = ErrorBox สถานะ
- `_admin/_export/save_delete` (สถานะ 2): ปลด tracking, balance_point -2.00, คืนเงิน 450 1 แถว, อีเมลยกเลิก (log) ผู้ส่ง PACK-Cargo + ปุ่ม #eb4823; request ที่ 2 = "รายการถูกยืนยันไปแล้ว"
- `_admin/_export/save_approved` (สถานะ 2 → 3): export_* ถูกต้อง, export_date = NOW(), tracking.date_finish, แถวค่าส่งในไทย type 4 ที่ใช้งาน 1 แถว (บันทึกซ้ำแทนแถวเดิมแบบไฟล์เดิม), อีเมลสำเร็จ
- `_admin/_export/print`: is_print/print_date ของบิล zz, role 4 = "ERROR : access denied"
- (รอบแรก 500 หลัง commit เพราะ laravel.log เป็นของ root จาก phpunit — ปัญหาสิทธิ์ไฟล์ของ dev ไม่ใช่โค้ด; แก้ด้วย bin/fix-perms แล้วทดสอบใหม่ด้วยบิลใหม่)
- ลบแล้วตาม id: bill_payment 12717–12720, 12723–12724, coupon 10, tracking 99176/99177/99241/99242, balance 64802/64831/64833/64835/64836/64838–64840, balance_point 12448/12449/12451 — เหลือ 0 แถว

### bench (`bin/bench`, n=20 c=1 รอบเดียว, artisan optimize; mean ms) — เครื่องโหลดสูง (load avg 8–11 จาก agent อื่น) ตัวเลขสูงกว่าปกติทั้งสองฝั่ง

| หน้า | เดิม (7.4) | ใหม่ (Laravel) |
|---|---|---|
| dashboard (ลูกค้า 348) | 2590 | 258 |
| `_export/grid` (ลูกค้า 348) | 1387 | 191 |
| admin/export | 4172 | 169 |
| `_admin/_export/grid` | 1596 | 268 |
| admin/export/12635 | 3554 | 192 |
| admin/report | 2652 | 101 |
| `_admin/_report/grid` type 3 | 504 | 169 |

### test
- `php8.4 vendor/bin/phpunit` ผ่าน (320 tests, skipped 111 = DB tests)
- `JL_BENCH_DB_TESTS=1 php8.4 vendor/bin/phpunit tests/Feature/Reports` ผ่าน (15 tests) · ทั้งชุด: ล้ม 2 + error 33 ในกลุ่มอื่นทั้งหมด (คอลัมน์/ตาราง SR-only: subuser, is_takeapic, tracking_check, warehouse*, refill.approved_user, bill_product.is_print/run_id, receipt_id; `BillGridDbTest::test_bill_detail_rows_follow_legacy_order_not_tracking_id` = PaymentController กับบิลที่ไม่มีแถว; `LegacyRisksDbTest` sender change -670 vs -650)
- test ใหม่: `tests/Feature/Reports/ReportsPackDbTest.php` (คูปอง/VAT/Discount ในตาราง, สิทธิ์ดูราคา, กล่องรายการเงิน, grid total - discount + ป้าย VAT); `ReportsRoutesTest` ตรวจว่า route ใบเสร็จ SR และ admin/nobilling ไม่มีแล้ว, download_file = legacy.auth
- ลบ: `ReceiptCalcTest`, `NobillingPagingTest`, test ใบเสร็จ/เลขรันใน `ReportsSrDbTest` (คง test หักแต้มตอนยกเลิก)

### คำถามถึงเจ้าของ (PACK)
1. download_file: ทำตาม hotfix ของ PACK (พนักงานทุก role ยกเว้น 0/2/8) ซึ่งกว้างกว่า SR (เฉพาะ admin_lot/admin_lot_view) — ใช้แบบ PACK ต่อหรือไม่
2. หน้าแรกของลูกค้า: ข้อความตีลังไม้/ที่อยู่โกดังจีน/เบอร์ ฝังในไฟล์เดิมและต่างจาก setting ของ PACK (setting: "คิว*1,300", เบอร์ไทย 064-…; ไฟล์: "คิว*1,500", 063-…/062-…) — ตอนนี้ตามไฟล์เดิม ควรย้ายไปอ่าน setting แบบ SR หรือไม่
3. `_export/save_approved` (ลูกค้ายืนยันชำระจากหน้า export) ตัดเงินเต็ม `total` แต่ grid แสดง `total - discount` (บิลเก่า 465: ชำระ -454 แต่คืน 4999) — คงตามไฟล์เดิม; ควรตัด/คืน `total - discount` หรือไม่ (ยกเลิกใน Laravel คืนเท่าที่ถูกตัดจริงอยู่แล้ว)
4. admin/nobilling: PACK ไม่มีหน้า (content.php) แต่ NotificationController (กลุ่ม messaging) ยังมีลิงก์ "พัสดุค้างไม่ออกบิล" ของ SR — ให้กลุ่ม messaging ตรวจกับ notification.php ของ PACK
5. สิทธิ์ `admin_warehouse` ไม่มี role ไหนถือใน PACK และไม่มีตาราง warehouse_section → ตารางชั้นวางในหน้าแรกไม่เคยแสดง (คงไว้แค่หัวตารางตามไฟล์เดิม)

## SR (2026-10-20)

พอร์ตกลุ่ม reports ให้ตรงกับเว็บเดิมของ SR (portal.srshippingsystem.com) บน branch `mod/reports` — โค้ดตั้งต้นคือที่ jl พอร์ตไว้ (jl ไม่มีรายงานกลุ่มนี้ใน docs/modules; การตัดสินใจเดิมอยู่ใน commit ของ jl `bc30d08`, `05cf276`, `0da8e44`, `dc50fe2` และ docs/modules/payment.md ข้อ 15) — คงทุกการแก้ช่องโหว่/การตัดสินใจของเจ้าของจาก jl

### ไฟล์เดิม → สถานะ

| ไฟล์ (docs/sr-diff.md) | สถานะ |
|---|---|
| `_dashboard/index.php` (diff 46) | **ทำแล้ว** — สี/ไอคอน `#ae75ff`, กล่อง 5 ช่อง `col-sm-2` (คืนกล่องแต้ม), สูตรตีลังไม้/ที่อยู่โกดังจีน/เบอร์จีน/เบอร์ไทย อ่านจาก setting (`calculate_woodprotect`, `address_cn`, `phone_cn`, `phone_th`) แทนข้อความ jl, คอลัมน์ 165px/70px, ซ่อนตารางราคาสำหรับผู้ใช้ id 891 |
| `_export/form.php` (diff 87) | **ทำแล้ว** — ส่วน "ผู้ส่ง" (getsender + ชื่อ/ที่อยู่/เบอร์ผู้ส่งเมื่อ sender_type = 1), คอลัมน์ "รหัสย่อย" (`bill_payment_detail.subuser`), ไม่มี MALL, ไม่มีแถว VAT/หัก ณ ที่จ่าย, colspan 12 |
| `_export/grid.php` (diff 9) | **ทำแล้ว** — ไม่มีป้าย VAT |
| `_export/index.php`, `save_approved.php` | ไม่เปลี่ยน (same) — เทียบผลแล้ว |
| `_export/save_delete.php` | **ทำแล้ว** — `PointCalc::calPoint($id, '-')` ใน transaction + lock เดียวกับการยกเลิก |
| `_admin/_export/form.php` (diff 54) | **ทำแล้ว** — ปุ่ม "พิมพ์ใบปะหน้า" + "พิมพ์บิลชำระเงิน" (printprice), คอลัมน์รหัสย่อย, ไม่มี VAT |
| `_admin/_export/grid.php` (diff 28) | **ทำแล้ว** — ตัวกรอง "รับเอง" (shipping_type 43) / "ส่งขนส่ง" (≠ 43), ลิงก์ใบเสร็จต่อแถว (แดง = ออกเลขแล้ว), คอลัมน์จัดการ 80px, ไม่มี VAT |
| `_admin/_export/print.php` + `print_2.php` (diff 47) | **ทำแล้ว** — โลโก้ + เบอร์ SR, ไม่มี badge / (PAY-id), คอลัมน์รหัสย่อย, ไม่มีคอลัมน์ชั้นวาง (SR comment query ไว้ → ตัด query warehouse_section_detail ทิ้ง), print_header ของ SR |
| `_admin/_export/printprice.php` + `print_3.php` | **SR-only พอร์ตแล้ว** `GET /_admin/_export/printprice?id=` — ใบปะหน้า + ค่าบริการ/ราคากิโล/คิว + ค่าโกดังไทย/ค่าส่งในไทย/สุทธิ; ตั้ง is_print เหมือน print.php (มีด่านกัน GET ข้ามเว็บแบบ print) |
| `_admin/_export/receipt.php` | **SR-only พอร์ตแล้ว** หน้า `admin/export/receipt/{id}` (page `admin_export_receipt`, สิทธิ์ `admin_payment`) |
| `_admin/_export/receipt_1.php`, `receipt_2.php` | **SR-only พอร์ตแล้ว** `POST` ajax (สิทธิ์ `admin_payment`) — ตารางบวก VAT 7% / รวม VAT แล้ว |
| `_admin/_export/calvat.php` | **SR-only พอร์ตแล้ว** `POST` ajax (สิทธิ์ `admin_export`) — JSON byte ต่อ byte กับเดิม (`urldecode(json_encode())`) |
| `_admin/_export/save_receipt.php` | **SR-only พอร์ตแล้ว** `POST` ajax (สิทธิ์ `admin_export`, `legacy.once:lock`) — เลขใบเสร็จ = `getRunning(ปี พ.ศ. 2 หลัก, false, 5)` เช่น `6900001` |
| `_admin/_export/print_receipt.php` | **SR-only พอร์ตแล้ว** `GET /_admin/_export/print_receipt?id=` (ไม่มีสิทธิ์ admin_export / id ว่าง/0 = "ERROR : access denied") |
| `_admin/_report/printtax.php` | **SR-only พอร์ตแล้ว** `GET /_admin/_report/printtax?m=&y=` (สิทธิ์ admin_export) — รายงานภาษีขายรายเดือน |
| `_admin/_export/email_success.php`, `email_cancel.php` (diff 2) | **ทำแล้ว** — ปุ่มสี `#a3b2f1`, แม่แบบ/ผู้ส่ง `config('legacy.mail')`; เปิดตรงจาก URL = 403 ว่าง (ตาม hotfix 2026-10-18 ของ SR) |
| `_admin/_export/index.php`, `save_approved.php` | ไม่เปลี่ยน (same) |
| `_admin/_export/save_delete.php` | **ทำแล้ว** — calPoint('-') หลังปลด tracking ก่อนเปลี่ยนสถานะ (ลำดับเดิม) ใน transaction เดียวกัน |
| `_admin/_report/index.php` (diff 82) | **ทำแล้ว** — รายงานยอดเคลื่อนไหว + ภาษีขาย ของเดือนนี้และย้อนหลัง 5 เดือน (`strtotime("-N month")` แบบเดิม), ลิงก์ "รายงานภาษีขายรวม" (`admin/report/97`) |
| `_admin/_report/form.php` (diff 3) | **ทำแล้ว** — id 97 = "รายงานภาษีขาย" |
| `_admin/_report/grid.php` (diff 71) | **ทำแล้ว** — type 97: ใบเสร็จจาก bill_payment (receipt_id ≠ '', กรอง receipt_date), คอลัมน์ "จำนวนเงินภาษี" |
| `_admin/_report/else.php` (diff 61) | **ทำแล้ว** — type 97 ใช้ SQL ใบเสร็จ; หัว/ช่องภาษีในไฟล์เดิมตรวจ `$txtSearch` ที่ไม่มีในไฟล์นี้ → แสดงคอลัมน์ทั่วไปเหมือนเดิม |
| `_admin/_report/else_preorder_working.php` (diff 2) | **ทำแล้ว** — ชื่อไฟล์ `export.xlsx` (เนื้อหายังเป็นตาราง HTML แบบเดิม) |
| `_admin/_report/cashmove`, `current_cash`, `current_cash_cn`, `file`, `refill_5/6/7`, `shipping` | ไม่เปลี่ยน (same) — เทียบแล้ว; refill_5/6/7/shipping เดิม 500 (PHPExcel) ใหม่ = xlsx (การตัดสินใจของ jl) |
| `_admin/_log/index.php`, `grid.php` | ไม่เปลี่ยน (same) — เทียบแล้ว |
| `_admin/_nobilling/index.php` (diff 13) | ไม่เปลี่ยน — SR ย้ายแค่จุดตรวจสิทธิ์ไปบนสุด (Laravel ตรวจที่ route อยู่แล้ว); คงการแบ่งหน้า (เจ้าของสั่ง) |
| `_admin/_dashboard/index.php` (diff 6) | **dead** — ไม่มีไฟล์ใดใน SR include และไม่มี RewriteRule ชี้มา (เหมือน jl) |
| `download_file.php` (diff 5) | **ทำแล้ว** — ตาม hotfix 2026-10-18 ของ SR: สิทธิ์ admin_lot/admin_lot_view, path ต้องขึ้นต้น href_blob และไม่มี `..`, ไม่เขียนดิสก์, ส่งให้เบราว์เซอร์ตรง (pdf/รูป = inline ตามชนิด, อื่นๆ = attachment), path ผิด = 400 "bad request" / ดึงไม่ได้ = 404 "not found" |
| `print_header.php` (diff 73) | **ทำแล้ว** — ใช้ไฟล์เดียว `resources/views/order/print_header.blade.php` (ของ SR, กลุ่ม order) กับทุกหน้าพิมพ์ของ reports; ลบ `reports/partials/print_header` (ของ jl) |
| `print_footer.php` | ไม่เปลี่ยน — ไม่มีไฟล์ไหน include |
| `_dashboard/index_bk.php` (JL-only) | dead — ไม่มี route อยู่แล้ว |

### helper ที่พอร์ตใหม่ (ในกลุ่ม)
- `App\Domain\Reports\Running::next()` = `getRunning()` + `updateRunning()` — SR ใช้ที่เดียว (save_receipt) จึงอยู่ในกลุ่ม; อ่านแถวด้วย `lockForUpdate` แล้ว +1 ใน transaction เดียว
- `App\Domain\Reports\Receipt` = `ThaiBath()`/`ReadNumber()` (ไฟล์ใบเสร็จเดิมคัดลอกซ้ำไว้ทุกไฟล์), สูตร receipt_1/2, calvat, print_receipt และแถวรายงานภาษี; `round()`/`number_format()` แบบ PHP 7.4 (มี pre-rounding) — ทดสอบกับ php7.4 จริง 16,000 ค่า ตรงทุกค่า
- `PointCalc::calPoint()` (ฐานร่วม กลุ่ม payment ทำไว้) เรียกจาก `_export/save_delete` และ `_admin/_export/save_delete`

### ต่างจากเดิมโดยตั้งใจ
- ค่าจาก DB/ผู้ใช้ escape ทั้งหมด (เดิม echo ดิบ: ชื่อ/ที่อยู่ผู้รับ, รหัสย่อย, ข้อมูลใบเสร็จ ฯลฯ) · ไม่มี `target="_blank"`
- save_receipt: transaction + lock แถวบิลและแถว running (เดิมบันทึกพร้อมกันแล้วได้เลขใบเสร็จซ้ำได้), id ต้องเป็นตัวเลขล้วน, กันส่งซ้อนด้วย `legacy.once:lock`
- หน้าใบเสร็จ/print_receipt ที่ id ไม่ใช่ตัวเลขล้วน = ไม่เจอบิล (เดิม MySQL แปลง '5abc' เป็น 5)
- email_success/email_cancel เปิดตรง = 403 (เหมือน production SR หลัง hotfix; jl ตอบว่าง 200)
- download_file ไม่มีสิทธิ์ = หน้าแจ้งไม่มีสิทธิ์ของ `legacy.perm` (hotfix เดิมตอบ 403 "forbidden"); ใน dev (`LEGACY_EXTERNAL_ENABLED=false`) redirect ไป blob แทนการดึงไฟล์เอง
- ยกเลิกบิลซ้ำ: ลูกค้าได้ ErrorBox "บิลนี้ไม่อยู่ในสถานะที่ยกเลิกได้" (การตัดสินใจของ jl — ยกเลิกได้เฉพาะสถานะ 0) และไม่หักแต้มซ้ำ
- ส่วนต่างอื่นที่เหลือจากการเทียบ HTML มีแค่ของ layout ฐานร่วม (favicon, morris.css, jquery.dashboard.js) และหน้าไม่มีสิทธิ์ (เดิม exit กลาง layout)

### ช่องโหว่/บั๊กเดิมของ SR ที่เจอ
- save_receipt.php: ไม่มี lock ระหว่าง getRunning กับ updateRunning → เลขใบเสร็จซ้ำได้; ใส่ค่า POST ลง SQL ตรงๆ (ตัดแค่ `'` ของชื่อ/ที่อยู่/เลขภาษี ส่วน `receipt_total`, `date`, `id` ไม่ตัด = SQL injection)
- printtax.php: `$_GET['y']`/`m` ต่อ SQL ตรง (SQL injection) — ใหม่ bind ค่า; ช่วงวันที่ 'Y-m-01' ถึง 'Y-m-31' (เดือนที่มี 30 วันได้วันที่ผิดรูป และใบเสร็จวันที่ 31 ที่เวลาหลังเที่ยงคืนหลุด) — คงแบบเดิม
- รายงานภาษี (printtax / grid 97, ใบเสร็จแบบรวม VAT): `number_format(x, 2)` ใส่ comma → ภาษี ≥ 1,000 บาท (ยอด ≥ 15,286 บาท) ถูกนำไปบวกรวมเป็น 1 บาท → ยอดรวมภาษีท้ายรายงานผิด (คงแบบเดิม — ดูคำถามข้อ 2)
- receipt_1/receipt_2: เมื่อ `receipt_total = 0.00` (ค่าเริ่มต้น) ช่องยอดแสดงว่าง/0 แทนยอดบิล (`$a` ยังไม่ถูกกำหนด) — แอดมินต้องพิมพ์ยอดเอง (คงแบบเดิม)
- ReadNumber(): 1,000,000 พอดีอ่านเป็น "แสนบาทถ้วน" (คงแบบเดิม)
- calPoint('-') ตอนลูกค้ายกเลิกบิลสถานะ 0 หักแต้มที่ไม่เคยได้ (แต้มติดลบ) — คงแบบเดิม (ดูคำถามข้อ 1)

### E2E (ฝั่ง Laravel เท่านั้น, แถว zz_ บน admin_sr_bench, ลบตาม id แล้ว)
สร้างบิล zz_e2e 5 ใบของผู้ใช้ 11937 (+ tracking 3, bill_payment_detail 1, balance เติมเงิน 1) แล้วทดสอบทุก flow ด้วยการส่งซ้อน 2 request พร้อมกัน:
- save_receipt: ครั้งแรกได้เลข `6900001` (running `69` no → 2), อีก request ถูก `legacy.once` ปฏิเสธ; บันทึกซ้ำ = แก้ข้อมูล เลขเดิม; `'` ถูกตัด และ `\` หายแบบเดิม — จากนั้นเทียบหน้าใบเสร็จ, receipt_1/2, print_receipt, printtax, grid/file type 97 กับ :8111 (อ่านอย่างเดียว) 7 ชุดยอด (VAT/ไม่ VAT, ยอด ≥ 15,286, 0, กรณี .01/.99) = ตรงทุกตัวอักษร
- `_export/save_delete` (สถานะ 0): สถานะ -1, cancel_remark "… ,ยกเลิกโดย ZZFDN1", tracking.payment_id = 0, balance_point -2.00 (300/150) 1 แถว, ไม่มีเงินคืน; request ที่ 2 = ErrorBox
- `_admin/_export/save_delete` (สถานะ 2): ปลด tracking, balance_point -2.00 1 แถว, คืนเงิน 450 1 แถว, อีเมล (log) ผู้ส่ง SRShippingSystem + ปุ่ม #a3b2f1; request ที่ 2 = "รายการถูกยืนยันไปแล้ว"
- `_admin/_export/save_approved` (สถานะ 2 → 3): ค่า export_* ถูกต้อง, ตั้ง date_finish, แถวค่าส่งในไทย type 4 ที่ใช้งานอยู่ 1 แถว (บันทึกซ้ำแทนแถวเดิม)
- `_export/save_approved` (สถานะ 0 → 1): ตัดเงิน -10 1 แถว; request ที่ 2 ถูก `legacy.once` ปฏิเสธ
- printprice/print ของบิล zz: ตั้ง is_print/print_date (ไม่ได้เทียบกับ :8111 เพราะไฟล์เดิมเขียน DB ตอน GET — ตรวจด้วยการอ่านโค้ด)
- ลบแล้ว: bill_payment 621–625, tracking 2643–2645, bill_payment_detail (tracking_id 2645), balance 2894–2898, balance_point 227–228, running id 1 — เหลือ 0 แถว

### เทียบ 1:1 กับ :8111 (session เดียวกัน)
- หน้าลูกค้า: dashboard (ลูกค้า 5 / แอดมิน 1 / 11937), export, export/{id} (สถานะ 0 และ 3), `_export/grid` (ตามสถานะ และหน้า 2)
- หน้าแอดมิน: admin/export, admin/export/status/3, admin/export/{id}, `_admin/_export/grid` (ทุกตัวกรองรวม 4/5, ค้นหา, หน้า 1–2)
- ใบเสร็จ: admin/export/receipt/{id}, receipt_1/2 (id จริง / ไม่มี / ไม่ใช่ตัวเลข), calvat (24 ค่า type 1/2 รวม '', 'abc', '1,234.5', 1,000,000), print_receipt, printtax (5 เดือน)
- รายงาน: admin/report, admin/report/{3,97}, `_admin/_report/grid` (type 3/5/6/7/99/98/97/1 × มี/ไม่มีวันที่ × หน้า 1–2), file/else/else_preorder_working, cashmove (3 เดือน), current_cash(_cn)
- อื่นๆ: admin/log + grid, admin/nobilling
- สิทธิ์: role 7 (ผู้ใช้ 11) และลูกค้า (ผู้ใช้ 5) กับหน้า/ajax ใบเสร็จ, print, printprice, printtax

ผลตรงกันหมด ยกเว้นส่วนที่ตั้งใจต่างไว้ข้างบน

### bench (`bin/bench`, n=30 c=1, artisan optimize; mean ms รอบ 1 / รอบ 2)

| หน้า | เดิม (7.4) | ใหม่ (Laravel) |
|---|---|---|
| dashboard (ลูกค้า 8) | 382 / 432 | 44 / 48 |
| `_export/grid` (ลูกค้า 2) | 46 / 65 | 33 / 35 |
| admin/export | 414 / 377 | 34 / 35 |
| `_admin/_export/grid` | 41 / 51 | 30 / 33 |
| admin/export/184 | 564 / 526 | 49 / 58 |
| admin/export/receipt/184 (SR) | 402 / 419 | 36 / 47 |
| admin/report | 365 / 461 | 45 / 37 |
| `_admin/_report/grid` type 3 | 184 / 54 | 37 / 33 |
| `_admin/_export/receipt_1` (SR) | 23 / 45 | 43 / 39 |

receipt_1 รอบแรกช้ากว่าเดิม: เป็น ajax เล็ก (1 query, ~2 KB) และ Laravel มี overhead คงที่ ~25–30 ms ต่อ request (boot + session/jail/สิทธิ์) ตาม CONVENTIONS ข้อ 10

### test
- `php8.4 vendor/bin/phpunit` ผ่าน (297 tests, skipped 98 = DB tests) · `JL_BENCH_DB_TESTS=1 php8.4 vendor/bin/phpunit` ผ่าน (297 tests, skipped 2)
- test ใหม่: `tests/Feature/Reports/ReceiptCalcTest.php` (ค่าคาดหวังจาก :8111), `tests/Feature/Reports/ReportsSrDbTest.php` (เลขใบเสร็จ/running, หักแต้มตอนยกเลิก), route table ใน ReportsRoutesTest เพิ่ม endpoint ของ SR
- `Tests\Feature\Money\BillGridDbTest::test_export_grids_show_the_same_rows_as_legacy_queries` ผ่านแล้ว (ตัด is_vat ออกจาก view)

### คำถามถึงเจ้าของ
1. ลูกค้ายกเลิกบิลสถานะ 0 (ยังไม่จ่าย ยังไม่เคยได้แต้ม) แต่ SR เดิมหักแต้ม (calPoint '-') → แต้มติดลบ — ตอนนี้ทำตามเดิม (เหมือนคำถามข้อ 1 ของกลุ่ม payment) ควรหักเฉพาะเมื่อเคยได้แต้มหรือไม่
2. รายงานภาษีขาย (ใบเสร็จแบบรวม VAT): ภาษีที่ ≥ 1,000 บาทถูกนับเป็น 1 บาทในยอดรวม (comma ใน number_format) — แก้ให้รวมถูก หรือคงเหมือนเดิม
3. printtax ใช้ช่วงวันที่ 'Y-m-01'..'Y-m-31' — ปัจจุบัน save_receipt บันทึกเวลา 00:00 เสมอจึงยังไม่หลุด; ควรแก้เป็นช่วงเดือนจริงหรือไม่
4. หน้าใบเสร็จ/print_receipt/printtax มีชื่อ "บริษัท โกโก-คาร์โก้ จำกัด", ที่อยู่ และเลขผู้เสียภาษีฝังอยู่ในไฟล์เดิมของ SR — คัดลอกตามเดิม; ข้อมูลยังถูกต้องหรือไม่ และควรย้ายไปเป็น setting หรือไม่
5. ฟอร์มใบเสร็จแสดงยอดเริ่มต้นว่าง/0 เมื่อยังไม่เคยบันทึก (บั๊กเดิม `$a`) — ควรเติมยอดบิลให้อัตโนมัติหรือไม่
6. หน้าพิมพ์ของ lot (`resources/views/lot/print/header.blade.php`) ยังเป็น header แบบ jl ขณะที่ `_admin/_lot/print.php` ของ SR include print_header.php ของ SR — ตรวจในรอบ cleanup
