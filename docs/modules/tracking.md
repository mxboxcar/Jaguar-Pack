# กลุ่ม tracking — รายงานการพอร์ต

## PACK (2026-10-22)

branch `mod/tracking` (ทำกลุ่ม warehouse ใน worktree เดียวกัน — ดู docs/modules/warehouse.md) · worktree `/home/admin/laravel/pack-wt/tracking` · dev http://127.0.0.1:8801 · เทียบกับ PACK เดิม :8114 (`admin_pack_bench`)
merge main (ฐานร่วม PACK: 0a4673f, d1d40c3) แล้ว — หัวข้อ SR ด้านล่างยังใช้ได้กับ PACK ยกเว้นที่ระบุในหัวข้อนี้

### สิ่งที่เปลี่ยนสำหรับ PACK (ตาม `docs/pack-diff.md` กลุ่ม tracking: 14 diff + 4 SR-only)

| ไฟล์เดิม | สถานะ | ที่ทำ |
|---|---|---|
| `_tracking/index.php` | diff 2 | ปุ่ม "เพิ่มรายการใหม่" แสดงเฉพาะผู้ใช้ประเภท 2 (นำเข้าสินค้า) หรือประเภทที่ `user_type.child = 2` (`TrackingController::canAdd()`) |
| `_tracking/save.php` | diff 59 | ไม่ตรวจสิทธิ์ `tracking` (comment ไว้ในไฟล์เดิม) แต่ตรวจประเภทผู้ใช้แบบเดียวกับปุ่ม → ไม่ผ่าน = `getPermissionAlert('tracking')` เดิม (route = `legacy.auth` + ตรวจใน controller); แก้ไข = **เปลี่ยนเลขพัสดุตามที่ส่งมาได้** (แบบ jl) และข้อความท้ายแสดงเลขว่าง (เดิม `$data` ถูกแทนด้วยแถว bill_product_on_tracking); ไม่มี `is_takeapic` / `tracking_check` |
| `_tracking/form.php` | diff 54 | ไม่มีช่อง "ถ่ายรูป", select ไม้/QC ไม่มี `onchange="openmodals()"`, ไม่มี modal "มีค่าใช้จ่าย…" — การ์ดรูป (`tracking_image`) ยังอยู่ |
| `_tracking/grid.php` | diff 16 | ไม่มีคอลัมน์ "รหัสลูกค้า" (subuser) และแถว "ถ่ายรูป" — คอลัมน์ไฟล์แนบ (`tracking_image`) ยังอยู่ |
| `_admin/_tracking/grid.php` | diff 72 | จำนวนหน้าอยู่ท้ายตารางในคำตอบเลย (`MakePaging($rows)` + `getpaging()`; ไม่มี `load_paging` ของ SR), ค้นเลขแทรค/ออเดอร์แบบขึ้นต้น (`LIKE 'x%'`), ไม่มีคอลัมน์ "จัดการชั้นวาง" (warehouse_section), ลิงก์ล็อตใช้ `tracking.lot_id` + ชื่อจาก LEFT JOIN, รหัสลูกค้าไม่มี subuser, ไม่มีแถว "ถ่ายรูป" |
| `_admin/_tracking/form.php` | diff 17 | ไม่มีช่อง "รหัสย่อย" (subuser) / "ถ่ายรูป"; เพิ่ม `setTimeout(... removeClass('validate[requiredattachment]'), 1000)` ของ PACK |
| `_admin/_tracking/save.php` | diff 7 | ไม่บันทึก `subuser` / `is_takeapic` (PACK ไม่มีคอลัมน์) — รูป `tracking_image` เหมือน SR; `nobody` = `config('legacy.nobody')` = `PACKnocode` (ฐานร่วม) |
| `_admin/_combine_track/save_combine.php` + `CombineTrack()` | diff 15 | 40 คอลัมน์ของตาราง PACK (ไม่มี date_section/subuser/is_takeapic); INSERT VALUES เดิมของ PACK ครบคอลัมน์ (ไม่มีปัญหาสลับคอลัมน์/ข้อมูลหายแบบ SR/jl) — ผลเท่ากับ INSERT ตามชื่อคอลัมน์; ย้ายรูปที่ยังไม่ลบเหมือนเดิม |
| `_admin/_lost_package/form.php` | diff 14 | JOIN `bill_product.exchange_rate` + ช่อง "฿" `cashback_th` (ค่าเริ่มต้น = cashback × อัตรา คูณแบบ PHP 7.4) และ JS ส่ง `cashback_th` |
| `_admin/_lost_package/save_approved.php` | diff 15 | **คืนเงินตามยอดบาท** `cashback_th` ที่กรอก: `balance.amount = cashback_th`, `lost_package.cashback = round(cashback_th / อัตรา, 2)` (round แบบ PHP 7.4 — `Php74::round()`), detail `¥<float แบบ 7.4> * <อัตรา>฿` (เช่น `¥77` ไม่ใช่ `¥77.00`); ค่า `cashback` (¥) ที่ส่งมาไม่ถูกใช้ |
| `_admin/_lost_package/grid.php` | diff 10 | ค้นด้วยเลขรายการ หรือ รหัสลูกค้า (ไม่มีเลขออเดอร์แบบ SR), ไม่มี `white-space`/`word-break` |
| `_admin/_lost_package/email_approved.php`, `email_cancel.php` | diff 21/24 | ปุ่มสี `#eb4823`, แม่แบบ header/center/footer + ผู้ส่งจาก `config('legacy.mail')` (ฐานร่วม: PACK-Cargo `<no-reply@pack-cargo.net>`), ไม่มีส่วน LINE/push |
| `_lost_package/upload.php` | diff 20 | ต่างแค่เครื่องหมายคำพูดของ hotfix — ไม่ต้องแก้ |
| `getupload()` / `getuploadmultiple()` (ของกลาง) | — | PACK เป็นรุ่นเก่า: ไฟล์ไม่ใช่รูป = ลิงก์ fancybox, ไม่มี `change.reselect`, อัป zip/rar/7z = ใส่ข้อความตอบกลับ, ปุ่มลบรูปใหม่ `$(this).parent().remove()` → `App\Domain\Tracking\UploadBox` และสำเนา `App\Domain\Warehouse\UploadBox` (ต้องเหมือนกัน — test) |
| ไฟล์ `same` อีก 23 ไฟล์ | same | ไม่ต้องแก้ — เทียบผลกับ :8114 แล้วตรงกัน |

### SR-only ที่ลบ (dead ใน PACK)

- `_admin/_check/index.php`, `save_clear.php`, `save_clearall.php` (หน้า `admin/check`, ตาราง `tracking_check`) — PACK ไม่มีไฟล์ ไม่มี RewriteRule ไม่มี page ใน content.php ไม่มีตาราง → ลบ route/`CheckController`/`TrackingChecks`/view/test
- `_admin/_tracking/load_paging.php` — PACK นับหน้าใน grid เอง → ลบ route/`loadPaging()`
- คอลัมน์ `tracking.is_takeapic` / `subuser` / `date_section` และชื่อชั้นวาง (`TrackingLookups::sectionNames`, ตาราง warehouse_section*) — ลบออกจาก controller/view/CombineTrack
- URL เหล่านี้บน Laravel = 404 → หน้าแรก (เหมือนเว็บเดิมที่ไม่มีหน้า) · `docs/url-map.md` ทำเครื่องหมาย dead แล้ว

### pack-only

- `track_external.php` (หน้าค้นเลขพัสดุสาธารณะ ไม่ต้องล็อกอิน, ฟอร์ม POST ไป `track_external`): **ไม่พอร์ต** ตามที่ผู้ประสานงาน/ฐานร่วมตัดสิน — ไม่มีหน้าใดลิงก์ถึง, SQL injection (`$_POST['search']` ต่อ SQL), reflected XSS (`value="<?php echo $_POST['search'] ?>"`), ไม่กรอง `is_delete`, เปิดเผยรหัสลูกค้า/ล็อต/เลขบิล/รูปหลักฐานส่งของ (`export_tracking`) ให้ทุกคนที่รู้เลขพัสดุ → รอเจ้าของ (คำถามข้อ 1) · Laravel `/track_external` = 404 → หน้าแรก
- `tracking_image`: ใช้ใน `_tracking/form|grid`, `_admin/_tracking/form|grid|save`, `save_combine`/`CombineTrack()` (ของกลุ่มนี้ — พอร์ตแล้วจาก SR) และ `_admin/_lot/*`, `_payment/load_create.php` (กลุ่ม lot/payment)

### ต่างจาก PACK เดิมโดยตั้งใจ (เพิ่มจาก SR/jl)

- **RollBack ของ lost_package หาแถวคืนเงินไม่เจอ (เงิน)**: `rollback.php` หาแถวด้วย `detail = '... ¥'.lost_package.cashback` (ค่าจาก DB เช่น `77.00`) แต่ `save_approved.php` ของ PACK เขียน `¥77` (float) → ยอดที่ไม่มีเศษสตางค์ 2 หลักหาไม่เจอ = RollBack **ไม่หักเงินคืน** แต่ตั้งสถานะกลับเป็นรอ → อนุมัติใหม่ = **คืนเงินซ้ำ** (เจอในข้อมูลจริง: LOS-1519 ลูกค้า 60 คืน ¥48 → rollback ไม่หัก → คืน ¥88 = ได้เกิน 244.80 บาท; LOS-1484 เกิน 1.00) → ใหม่ยอมรับ detail ทั้งสองรูปแบบ
- `save_approved`: ออเดอร์ไม่มีอัตราแลกเปลี่ยน (0/ไม่พบ) = ErrorBox ไม่ทำอะไร (เดิมหารศูนย์ → cashback = 0 แต่ยังคืนเงินเต็มยอดบาท) · ยอดบาทติดลบ = ปฏิเสธ (การแก้เงินเดิม ย้ายมาตรวจ `cashback_th`)
- `_admin/_tracking/grid`: นับด้วย `COUNT` (เดิมดึงทุกแถว + JOIN มานับด้วย `mysqli_num_rows` — ค้นว่าง = 73K แถวทุกครั้ง ~2 วินาที)
- หน้า dead (`admin/check`, หน้า warehouse ทั้งหมด): 404 → หน้าแรก (เว็บเดิม: admin/check = 404, admin/warehouse* = layout เปล่า)

### พฤติกรรมเดิมที่คงไว้

- `_tracking/save` ใช้แค่ประเภทผู้ใช้: ลูกค้า type 3 / ลูก type 3 (769 จาก 1,012 คนใน bench รวม 348 และ zz_fdn_cust) เปิดหน้า `tracking/create` ได้ (สิทธิ์ `tracking`) แต่บันทึกไม่ได้ (ErrorBox สิทธิ์); บัญชีพนักงานที่ไม่มีสิทธิ์ `tracking` แต่เป็น type 2 บันทึกได้
- กล่องอัปโหลดรุ่นเก่า: ไฟล์ pdf เดิมแสดงเป็นลิงก์ fancybox (เปิดในกล่องรูปไม่ได้ — jl/SR แก้แล้วแต่ PACK ยังไม่แก้)

### ตรวจเทียบกับ :8114 (cookie เดียวกัน, เทียบเฉพาะส่วนเนื้อหา, แทน href + ตัด target=_blank + class noti ของ layout)

ตรงกันทุกตัวอักษร: `/tracking` (ลูกค้า 9 = type 2 มีปุ่ม, ลูกค้า 348 = ไม่มีปุ่ม), `/tracking/create`, `/tracking/{id}` (ไม่มีล็อต / มีล็อต+บิล), `_tracking/grid` (หน้า 1/2, สถานะ 0/2/3, ค้นหา, ภาษา cn), `load_pricing`, `load_product_type` (ลูกค้า+แอดมิน),
`/admin/tracking`, `/admin/tracking/{create,99022 (มีรูป),91150,33}`, `_admin/_tracking/grid` (หน้า 1/2/5000, ค้นเลขแทรคขึ้นต้น, ship 0/1, รหัสลูกค้า, ออเดอร์, ภาษา en; แอดมิน + role 7), `/admin/combine_track`, `load_tracking` (มีผล/ไม่มีผล),
`/admin/lost_package`, `/admin/lost_package/{create,1758 (รอ — ช่อง ฿ 762.84),1747 (อนุมัติ),1757 (ยกเลิก)}` (+cn), `_admin/_lost_package/grid` (หน้า 1/2, ค้นรหัสลูกค้า/เลขรายการ/เลขออเดอร์ = ไม่พบ, สถานะ), `/lost_package` (+create/id/status), `_lost_package/grid`, `load_upload`, `load_order_number`
ต่างตามที่ตั้งใจ: JS หน้า combine (eval ErrorBox), หน้าไม่มีสิทธิ์ (ลูกค้าเปิดหน้าแอดมิน: เดิมพิมพ์หัวหน้าก่อน alert), dead URL ด้านบน · ajax แอดมินที่ไม่มีสิทธิ์ (ลูกค้า, role 6) ตอบว่าง/ErrorBox เหมือนเดิม

### E2E งานเขียน (ฝั่ง Laravel :8801 เท่านั้น, แถว `ZZE2E*`/`zz_e2e*`, ผู้ใช้ 11937/11938 — ลบตาม id แล้ว ตรวจไม่เหลือแถว)

- ลูกค้า (11938 type 2) สร้าง tracking ยิงซ้อน 2 คำขอ → 1 แถว (คำขอที่สอง "ส่งซ้ำ"), คอลัมน์ตรงกับ INSERT เดิม; แก้ไข → เลขพัสดุเปลี่ยน + ข้อความเลขว่าง; 11937 (type 3) สร้าง/แก้ = ErrorBox สิทธิ์ ไม่มีอะไรเปลี่ยน; ลูกค้าลบ
- แอดมินบันทึก (รูป `zz_e2e_a.jpg` + `../../x.php` → เก็บรูปเดียว, ไม่มี subuser/is_takeapic), สร้างใหม่ยิงซ้อน → 1 แถว, รวม 2 แถว (ยิงซ้อน) → แถวใหม่ 1 แถว + รูป 2 รูปย้าย, save_lock (-3), ลบ
- lost_package: ลูกค้าแจ้ง (ยิงซ้อน → 1 แถว) → อนุมัติ ฿65.00 (ยิงซ้อน → balance 1 แถว `¥12.5 * 5.200฿`, cashback 12.50) → RollBack (-65.00) → อนุมัติ ฿52 (`¥10 * 5.200฿`, cashback 10.00) → RollBack หักได้ (เดิมหาไม่เจอ) → ยอดติดลบถูกปฏิเสธ → save_remark → ลูกค้ายกเลิก → แอดมินยกเลิกซ้ำถูกปฏิเสธ; อีเมลลง log (From `PACK-Cargo <no-reply@pack-cargo.net>`, ปุ่ม #eb4823)
- goodsconfirm (กลุ่ม warehouse): ดูใน docs/modules/warehouse.md
- ลบแล้ว: tracking 99374/99380/99394–99403, tracking_image 47229/47230, bill_product 20605/20606, lost_package 1786/1787, balance 64881–64888, goodsconfirm 60–63, goodsconfirm_detail 51/52

### Bench (`bin/bench -n 15 -c 1`, `artisan optimize`; เครื่องโหลดสูง load 7–14 จาก agent อื่น — ตัวเลขเดิมผันผวนมาก)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/admin/tracking` (แอดมิน) | 1140–2795 | 117–170 |
| `_admin/_tracking/grid` หน้า 1 (ค้นว่าง, 73K แถว) | 2257–2650 | 128–130 |
| `/tracking` (ลูกค้า 9) | 583 | 75 |
| `_tracking/grid` หน้า 1 (ลูกค้า 9) | 164 | 78 |
| `_admin/_lost_package/grid` | 207 | 72 |

`_admin/_tracking/grid` เดิมช้าเพราะดึงทุกแถวมานับ (ดูหัวข้อต่างโดยตั้งใจ); ab นับขนาดคำตอบเดิมที่ต่างกันเป็น failed 5 ครั้ง (ไม่ใช่ error)

### Test

- `tests/Feature/Tracking/TrackingRoutesTest.php`: สิทธิ์ route ของ PACK (`_tracking/save` = `legacy.auth`), URL SR-only + `track_external` ไม่มี route, markup กล่องอัปโหลด PACK (และสำเนา warehouse เท่ากัน), `Php74::round()` แบบ 7.4, CombineTrack 40 คอลัมน์
- `tests/Feature/Tracking/TrackingPackDbTest.php` (แทน TrackingSrDbTest; JL_BENCH_DB_TESTS=1, rollback): กติกาประเภทผู้ใช้ (type 2 / child 2 / type 3), แก้เลขพัสดุ, IDOR, แอดมินบันทึก+รูป, grid แอดมินนับหน้าในคำตอบ + ค้นขึ้นต้น, combine, save_remark
- `tests/Feature/Money/LostPackageDbTest.php`: ปรับเป็น `cashback_th` + test ใหม่ (round 7.4, detail float, RollBack หาแถว `¥77` เจอ, อัตรา 0 = ปฏิเสธ)

### คำถามถึงเจ้าของ

1. `track_external.php` (หน้าค้นเลขพัสดุสาธารณะ) — ไม่พอร์ตตามที่ตกลง ถ้าต้องการ ต้องตัดสินใจ: เปิดสาธารณะต่อไหม, แสดงอะไรบ้าง (รหัสลูกค้า/เลขบิล/รูปหลักฐาน), กรองรายการที่ลบไหม, จำกัดความถี่
2. lost_package RollBack ที่หาแถวคืนเงินไม่เจอในเว็บเดิม (คืนเงินซ้ำ) — Laravel แก้แล้ว; ข้อมูลเดิมที่ได้เงินเกินใน bench (= clone ของจริง): LOS-1519 (ลูกค้า 60) เกิน 244.80, LOS-1484 (ลูกค้า 55) เกิน 1.00 — จะให้ปรับยอดไหม (ไม่ได้แตะข้อมูล)
3. ยืนยันการปฏิเสธอนุมัติเมื่อออเดอร์ไม่มีอัตราแลกเปลี่ยน (ใน bench ไม่มีกรณีนี้)
4. `_tracking/save` ของ PACK ไม่ตรวจสิทธิ์ `tracking` แต่ใช้ประเภทผู้ใช้ — คงไว้ตามเดิม (ลูกค้าประเภท 3 เปิดหน้าเพิ่มได้แต่บันทึกไม่ได้)

### ฝากกลุ่มอื่น

- reports/dashboard: `_dashboard/index.php` เดิมคิดพื้นที่ชั้นวางจาก `warehouse_section(_detail)` ซึ่ง PACK ไม่มี (เดิม SQL error → ว่าง)
- messaging: แจ้งเตือน "รายการรอเช็ค" (`tracking_check`) — PACK ไม่มีตาราง
- lot / payment / order / shop / reports: ยังมีโค้ดอ้าง `is_takeapic` / `subuser` / `date_section` / `warehouse_section*` (`app/Domain/Lot/LotPrint.php`, `AdminLotSaveController`, `Payment*Controller` + view, `OrderDetailRenderer`, `CartHtml`, `ExportBill`, `NotificationController`) — ณ เวลาที่ตรวจ

## SR (2026-10-20)

branch `mod/tracking` ของ repo sr · worktree `/home/admin/laravel/sr-wt/tracking` · dev http://127.0.0.1:8301 · เทียบกับ SR เดิม :8111 (`admin_sr_bench`)
ข้อความด้านล่าง (หัวข้อ 1–10) เป็นรายงานเดิมของ jl — ยังใช้ได้กับ SR ยกเว้นที่ระบุในหัวข้อนี้

### สิ่งที่เปลี่ยนสำหรับ SR (ตาม `docs/sr-diff.md` กลุ่ม tracking)

| ไฟล์เดิม | สถานะ | ที่ทำ |
|---|---|---|
| `_tracking/index.php` | diff 10 | ไม่ต้องแก้ (SR ซ่อนปุ่ม "เพิ่มรายการ" เมื่อไม่มีสิทธิ์ tracking — route มี `legacy.perm:tracking` อยู่แล้ว ผลเท่ากัน) |
| `_tracking/grid.php` | diff 30 | คอลัมน์ "รหัสลูกค้า" (= `tracking.subuser`), แถว "ถ่ายรูป" (`is_takeapic`), คอลัมน์ "ไฟล์แนบ" = รูป `tracking_image` (batch 1 query แทนทีละแถว) |
| `_tracking/form.php` | diff 80 | ช่อง "ถ่ายรูป" (select เมื่อยังไม่เข้าล็อต / ข้อความเมื่อเข้าแล้ว), `onchange="openmodals()"` ที่ไม้/QC/ถ่ายรูป + modal "มีค่าใช้จ่าย…", การ์ด "รูปภาพ" (`tracking_image`) |
| `_tracking/save.php` | diff 49 | บันทึก `is_takeapic`; สร้างใหม่ที่ขอ QC/ไม้ → INSERT `tracking_check`; แก้ไขที่ขอ QC/ไม้/ถ่ายรูป → แถว `tracking_check` ที่เช็คแล้วแถวแรกตั้งกลับเป็น 0 ไม่มีก็ INSERT ใหม่; **แก้ไข: เลขพัสดุใช้ค่าเดิมใน DB** (SR ไม่ให้ลูกค้าเปลี่ยนเลข) และข้อความแสดงเลขนั้น |
| `_admin/_tracking/grid.php` | diff 44 | ค้นเลขแทรค `LIKE '%x%'` (jl = `x%`), ชื่อล็อต/ลิงก์จาก `lot.id` ที่พบ, แถว "ถ่ายรูป", รหัสลูกค้า + `subuser` (สีแดง), ชั้นวางไม่นับแถว `warehouse_section_detail.is_delete = 1`, คอลัมน์ "ไฟล์แนบ" (รูป) |
| `_admin/_tracking/load_paging.php` | diff 60 | นับสดทุกครั้ง (SR ไม่มี cache Redis 10 นาที / ไม่ตัด zero-width space แบบ jl), `LIKE '%x%'` เหมือน grid |
| `_admin/_tracking/form.php` | diff 47 | ช่อง "รหัสย่อย" (`subuser`), "ถ่ายรูป" (`is_takeapic`) แทน "รับเข้าไทยแล้ว?" (`is_checked` — SR ไม่มีคอลัมน์), การ์ด "รูปภาพ" (กล่องอัปโหลดหลายไฟล์ + รูปเดิมพร้อมปุ่มลบ) |
| `_admin/_tracking/save.php` | diff 69 | `subuser` (ตัดช่องว่าง + `nStr` = `'` ถูกเก็บเป็น `''` แบบเดิม), `is_takeapic`, ไม่มี `is_checked`; รูป: ลบ (`is_delete=1`) ทั้งหมดแล้ว INSERT ตาม `upload_upload_track_mul[]`, ไฟล์ที่อยู่ใน temp = ตอบ JS เรียก `_api/blockblob_ajax` แบบเดิม |
| `_admin/_combine_track/save_combine.php` + `CombineTrack()` | diff 20 | คอลัมน์ SR (`date_section`, `subuser`, `is_takeapic` เทียบกับ `''`), ย้าย `tracking_image` (ที่ยังไม่ลบ) ไปแถวใหม่; คงการแก้ของ jl (lock + ตรวจสถานะ/ลูกค้าเดียวกัน + transaction) |
| `_admin/_lost_package/grid.php` | diff 28 | ค้นด้วยเลขรายการ **หรือ** เลขออเดอร์ **หรือ** รหัสลูกค้า, คอลัมน์ "หมายเหตุแอดมิน" (input แก้ในตาราง → `save_remark`), `white-space`/`word-break` แบบ SR |
| `_admin/_lost_package/email_approved.php`, `email_cancel.php` | diff 2 | ปุ่มสี `#a3b2f1` + แม่แบบ email header/center/footer ของ SR, ผู้ส่ง `SRShippingSystem <no-reply@srshippingsystem.com>` (`LostPackageMailer`) |
| `getuploadmultiple()` (SR อยู่ใน system_status_function.php) | — | `UploadBox::multiple()` เปลี่ยนเป็นแบบ SR (รูปเดิมดูจากนามสกุล, ไฟล์ zip/rar/7z, ไม่มีตัวล้างช่อง input ของ jl) — ใช้โดย lost_package, admin tracking และ **warehouse (AdminGoodsconfirmController)** |
| ไฟล์ `same` อีก 25 ไฟล์ | same | ไม่ต้องแก้ — เทียบผลกับ :8111 แล้วตรงกัน |

### SR-only ที่พอร์ตใหม่

| ไฟล์เดิม | route | สิทธิ์ |
|---|---|---|
| `_admin/_check/index.php` (page `admin_check`) | `GET /admin/check` | `legacy.perm:admin_tracking` |
| `_admin/_check/save_clear.php` | `POST /_admin/_check/save_clear` | `legacy.perm:admin_tracking` |
| `_admin/_check/save_clearall.php` | `POST /_admin/_check/save_clearall` | `legacy.perm:admin_tracking` |
| `_admin/_lost_package/save_remark.php` | `POST /_admin/_lost_package/save_remark` | `legacy.perm:admin_lost_package` |

ไฟล์: `app/Http/Controllers/Tracking/Admin/CheckController.php`, `resources/views/tracking/check/index.blade.php`, `app/Domain/Tracking/TrackingChecks.php` (ตาราง `tracking_check`), `app/Domain/Tracking/TempUpload.php` (ย้าย safeName/blobJs ออกจาก LostPackageController ให้ใช้ร่วมกับ admin tracking), partial `tracking/partials/images.blade.php`

JL-only: ไม่มี (กลุ่มนี้ไม่มีไฟล์ที่ jl มีแต่ SR ไม่มี) · ไม่มีหน้า "dead"

### ต่างจาก SR เดิมโดยตั้งใจ (เพิ่มจากของ jl ในหัวข้อ 2)

- ไม่มี `target="_blank"` (เจ้าของ) — รวมรูปในตาราง/ฟอร์ม, กล่องอัปโหลด, หน้า admin/check
- **save_combine / CombineTrack**: SR เดิม `INSERT INTO tracking VALUES(...)` ตามลำดับ `..., date_section, subuser, is_takeapic` แต่ตาราง SR เรียง `date_section, is_takeapic, subuser` → ค่าสลับคอลัมน์ (รหัสย่อย "1269263" กลายเป็น `is_takeapic = 1269263` = "ถ่ายรูป: เปิด" และ `subuser = "0"`) → ใหม่ INSERT ตามชื่อคอลัมน์ (ดู test `TrackingSrDbTest::test_combine_keeps_subuser_and_takeapic_columns_and_moves_images`)
- `_admin/_tracking/save.php` รูป: รับเฉพาะชื่อไฟล์ธรรมดา `[A-Za-z0-9._-]` (เดิมบันทึก/ส่งต่อทุกค่า: `file_exists('../../upload/temp/'.$x)` = path traversal และชื่อไฟล์ถูกฝังใน JS ที่ตอบ) — ชื่อที่ไม่ผ่านถูกข้าม
- `save_clearall.php`: id ต้องเป็นตัวเลขทั้งหมด (เดิม `IN (".substr($id,1).")` = SQL injection) — ไม่ใช่ตัวเลข = ไม่เปลี่ยนอะไร (เดิม SQL error เหมือนกัน) แต่ยังตอบสำเร็จแบบเดิม
- `admin/check` ไม่มีสิทธิ์: แสดงหน้า no-permission (เดิม `getPermissionAlert_Page()` ไม่ `exit` → **ตาราง tracking ที่รอเช็คทั้งหมดถูกส่งไปให้ผู้ไม่มีสิทธิ์** ก่อน JS redirect — เทียบแล้วกับ role 7)
- ลำดับแถวที่เดิมไม่มี ORDER BY (`tracking_image`, `tracking_check`) ใช้ `ORDER BY id`
- `_tracking/save.php` แก้ไข: งานเขียน `tracking_check` + `tracking` ห่อ transaction; ค่าเลขพัสดุเดิมที่มี `'` (SQL เดิมพัง → ไม่บันทึก) ไม่ได้เลียนแบบ
- อีเมล lost_package: แม่แบบของ SR ก่อน hotfix (หลัง hotfix 2026-10-18 ไฟล์ `email_*.php` บนเว็บเดิมขึ้นต้นด้วยบรรทัด `<?php … ?>` ซึ่ง `file_get_contents` ใส่ลงในเนื้ออีเมลด้วย — ไม่เลียนแบบ)
- hotfix ของ SR เดิม `_tracking/delete.php` แปลง id ที่ไม่ใช่ตัวเลขด้วย `intval` (`1) OR (1=1` → ลบ id 1) ส่วนพอร์ต (ของ jl) ไม่ลบอะไรเลย — ปลอดภัยทั้งคู่

### พฤติกรรมเดิมที่คงไว้ (ไม่ใช่ช่องโหว่)

- แก้ไข tracking ที่ขอ QC/ไม้/ถ่ายรูป เมื่อไม่มีแถว `tracking_check` ที่เช็คแล้ว → INSERT แถวใหม่ทุกครั้ง แม้มีแถวรอเช็คอยู่ (หน้า admin/check มีแถวซ้ำได้)
- ตอนสร้างใหม่ "ถ่ายรูป" อย่างเดียวไม่เข้า tracking_check (ต้อง QC หรือไม้) แต่ตอนแก้ไขเข้า
- `on_saveremark()` ส่ง `remark=` โดยไม่ encode (มี `&`/`+` แล้วข้อความขาด) — JS เดิม
- combine ไม่ย้าย `tracking_check` ไปแถวใหม่ (เดิมก็ไม่ย้าย — แถวรอเช็คของ tracking ที่ถูกรวมหายจากหน้า admin/check เพราะ tracking เดิมถูก DELETE)

### ตรวจเทียบกับ :8111 (cookie เดียวกัน, ตัดช่องว่าง + แทน href + ตัด target=_blank)

ตรงกันทุกตัวอักษร: `/tracking`, `/tracking/create`, `/tracking/{id}` (มี/ไม่มีล็อต, ชำระแล้ว, ของคนอื่น, ไม่มีจริง, มีรูป 7 รูป), `_tracking/grid` (หน้า 1/2/3/5, สถานะ 0/2/3/-1, ค้นหา), `load_pricing`, `load_product_type` (ลูกค้า 3, 7, 8),
`/admin/tracking`, `/admin/tracking/status/2`, `/admin/tracking/{create,0,994,1038,568}`, `_admin/_tracking/grid` + `load_paging` (หน้า 1/2/3, ค้นเลขแทรค/ship/ออเดอร์/รหัสลูกค้า, ภาษา th/en/cn), `/admin/combine_track`, `load_tracking` (ผลเยอะ ~42K บรรทัด/ไม่มีผล),
`/admin/check` (ว่าง / มีรายการ zz), `/lost_package` (+status), `/lost_package/{create,id,status/1}`, `_lost_package/grid`, `load_upload`, `load_order_number`, `/admin/lost_package` (+status), `/admin/lost_package/{create,id}`, `_admin/_lost_package/grid` (ค้นเลขรายการ/ออเดอร์/รหัสลูกค้า, สถานะ)
ต่างตามที่ตั้งใจ: escape `< " '` (`zz_remark <b>x</b> "q"`), หน้าไม่มีสิทธิ์ (role 7: admin/check, admin/tracking, admin/lost_package, admin tracking form), JS หน้า combine (eval ErrorBox ของ jl), meta theme-color/favicon/`jquery.dashboard.js` ของ layout
สิทธิ์: role 7 (user 11) → ErrorBox เดียวกัน (`load_paging`), grid ตอบว่างเหมือนเดิม; ลูกค้า 3 → `load_tracking` ErrorBox เดียวกัน

### E2E งานเขียน (ฝั่ง Laravel เท่านั้น, แถว zz_ / ผู้ใช้ 11937/11938, ลบตาม id แล้ว)

- ลูกค้าสร้าง tracking (QC+ถ่ายรูป) + กดซ้ำพร้อมกัน/ซ้ำภายหลัง → 1 แถว + `tracking_check` 1 แถว (คำขอซ้อน = "กำลังดำเนินการ", ซ้ำ = "ส่งซ้ำ")
- แอดมิน Clear (`save_clear`) → ลูกค้าแก้ไข (ถ่ายรูป) → แถวเดิมกลับเป็นรอเช็ค; แก้ไข (ไม้) → INSERT แถวใหม่; เลขพัสดุไม่เปลี่ยนแม้ส่งค่าใหม่; `save_clearall` 2 แถว
- อัปโหลดรูป (`_lost_package/upload`) → แอดมินบันทึก tracking พร้อมรูป temp + ชื่อที่ไม่มีไฟล์ + `../../x.php` → JS blockblob 1 ตัว, INSERT 2 แถว (ชื่ออันตรายถูกข้าม), `subuser` = `zzsub9`; บันทึกซ้ำโดยลบ 1 รูป (กดซ้ำพร้อมกัน → ส่งซ้ำ) → รูปเดิมถูก is_delete แล้ว INSERT ใหม่ตามรายการ
- รวม 2 tracking (ยิงพร้อมกัน 2 ครั้ง) → แถวใหม่ 1 แถว (`subuser`/`is_takeapic` ถูกคอลัมน์), รูปย้าย; คำขอที่สองตอบสำเร็จแต่ไม่ทำอะไร (เหมือนเดิม: ไม่ครบ 2 แถว)
- save_lock (-3, ล้าง date_thai), ลูกค้าลบ, แอดมินสร้าง (คิดราคาอัตโนมัติ: 60.00) + กดซ้ำ, แอดมินลบ, quick quote (+ IDOR tracking คนอื่น = ไม่เปลี่ยน)
- lost_package: สร้าง (+ ซ้อน) → `save_remark` → อนุมัติ (balance +64.88 = ¥12.50 × 5.190) + อนุมัติซ้ำ (ไม่ทำ) → rollback (-64.88) → ลูกค้ายกเลิก → แอดมินยกเลิก (ถูกปฏิเสธ: ยกเลิกแล้ว) → rollback → แอดมินยกเลิก; อีเมลลง log (`From: SRShippingSystem`, ปุ่ม #a3b2f1) ไม่ส่งจริง
- ลบแล้ว: tracking 1185/1186 (1183/1184 ถูกลบโดย combine), tracking_check 13/14 (+ แถวทดสอบอ่าน 1–4), tracking_image 6014–6017, lost_package 9/10/11/17, lost_package_detail 1–3, balance 947/948, bill_product 49, ไฟล์ temp 2 ไฟล์ — ตรวจแล้วไม่เหลือแถว/ไม่มีแถวเดิมถูกแก้

### Bench (`bin/bench`, -n 30 -c 1, `artisan optimize`; ms เฉลี่ย รอบ 1 / รอบ 2)

| หน้า | เดิม ms | ใหม่ ms |
|---|---|---|
| `/tracking` (ลูกค้า 8) | 367 / 347 | 43 / 37 |
| `_tracking/grid` หน้า 1 (ลูกค้า 8, มีรูปทุกแถว) | 77 / 84 | 45 / 40 |
| `_admin/_tracking/grid` หน้า 1 | 167 / 128 | 54 / 56 |
| `_admin/_tracking/load_paging` | 23–25 | 27–28 |
| `/admin/check` (3 แถว) | 336 | 37 |
| `_admin/_lost_package/grid` | 56 / 41 | 38 / 33 |

`load_paging` ช้ากว่าเดิม ~4 ms: ไฟล์เดิมเล็กมาก (count 1 ครั้ง) ส่วน Laravel มี overhead คงที่ต่อ request (boot + session/สิทธิ์) — ตาม CONVENTIONS ข้อ 10

### Test

- `tests/Feature/Tracking/TrackingRoutesTest.php`: route + สิทธิ์ของ 4 route SR-only, `legacy_page=admin_check`, ไม่มี `_blank` ใน view ของกลุ่ม, merge ของ CombineTrack แบบ SR (subuser/is_takeapic, ไม่มี is_checked)
- `tests/Feature/Tracking/TrackingSrDbTest.php` (JL_BENCH_DB_TESTS=1, rollback): สร้าง/แก้ไขลูกค้า + tracking_check, IDOR, แอดมินบันทึก subuser/ถ่ายรูป/รูป, combine (คอลัมน์ + ย้ายรูป), check clear/clearall (+ SQL injection), save_remark
- `tests/Feature/Money/LegacyRisksDbTest` (combine / save_lock) ผ่านแล้ว — เดิมล้มเพราะ `CombineTrack` INSERT คอลัมน์ `is_checked`/`checked_by` ที่ SR ไม่มี

### คำถามถึงเจ้าของ

1. `save_combine`: SR เดิมสลับค่า `subuser` ↔ `is_takeapic` ในแถวที่รวม — พอร์ตแก้เป็นตามชื่อคอลัมน์ (ถ้าต้องการให้เหมือนเดิมเป๊ะต้องตัดสินใจ) และข้อมูลจริงที่เคยรวมไปแล้วอาจมี `is_takeapic` เป็นเลขรหัสย่อย (ตรวจได้: `SELECT id, subuser, is_takeapic FROM tracking WHERE is_takeapic NOT IN (0,1)`)
2. `tracking_check` ซ้ำ (แก้ไขหลายครั้ง = หลายแถวรอเช็ค) — คงไว้แบบเดิม จะให้ INSERT เฉพาะเมื่อยังไม่มีแถวรอเช็คไหม
3. เมนูแจ้งเตือน "รายการรอเช็ค" (`notification.php`, กลุ่ม messaging) ตรวจสิทธิ์ `admin_export` แต่หน้า `admin/check` ใช้ `admin_tracking` — ผู้ที่มีแค่ `admin_export` เห็นตัวเลขแต่เปิดหน้าไม่ได้ (เหมือนเดิม)
4. hotfix เว็บเดิม (2026-10-18) ใส่บรรทัด PHP กันเปิดตรงไว้ที่ `email_header/center/footer.php` ซึ่งถูกอ่านด้วย `file_get_contents` → อีเมลจริงจากเว็บเดิมตอนนี้มีโค้ด PHP ติดอยู่ในเนื้อ HTML (ฝั่ง Laravel ไม่เป็น)
5. ข้อเสนอเดิมของ jl ยังใช้: บังคับความยาวคำค้นของ `load_tracking`; index `tracking_image(tracking_id, is_delete)` มีแล้ว (MUL) ไม่ต้องเพิ่ม

### ฝากกลุ่มอื่น

- warehouse: `AdminGoodsconfirmController` ใช้ `App\Domain\Tracking\UploadBox::multiple()` ซึ่งตอนนี้ให้ HTML แบบ SR (ถูกต้องสำหรับ SR เพราะ `getuploadmultiple()` เป็นของกลาง) — ตรวจหน้าของตัวเองอีกครั้งหลัง merge
- lot / payment: ยังอ้าง `tracking.is_checked`/`checked_by` (`app/Domain/Lot/LotPrint.php`, `AdminLotController`, `PaymentController`, `resources/views/lot/admin/detail.blade.php`, `routes/modules/lot.php`) — SR ไม่มีคอลัมน์นี้
- `tests/Feature/Lot/LotRoutesTest::test_route_count_matches_url_map` ล้มอยู่แล้วบน main (46 vs 43) — ไม่ใช่ของกลุ่มนี้
- `php8.4 vendor/bin/phpunit` กับ JL_BENCH_DB_TESTS=1: test ที่ล้มของกลุ่มอื่น (payment/money): `BillGridDbTest` (`is_vat`), `GridPerfDbTest`, `LegacyRisksDbTest::test_sender_change_*` 3 ตัว


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

- **กันกดซ้ำ/ส่งซ้อน (double submit)** (เจ้าของอนุมัติ 2026-10-18, E2E: ดับเบิลคลิก `_taobao/save` = 2 รายการ ตัดเงิน 2 ครั้ง — เว็บเดิมก็เป็น): middleware `legacy.once` (`App\Support\SubmitGuard`) — lock ต่อ ผู้ใช้+route ระหว่างประมวลผล (คำขอที่ซ้อนได้ `ErrorBox('รายการนี้กำลังดำเนินการ กรุณารอสักครู่')`) + payload เดิมที่สำเร็จแล้วภายใน 8 วินาทีได้ `ErrorBox('รายการนี้ถูกส่งไปแล้ว (ส่งซ้ำ) ...')` (คำตอบ error/กล่อง PIN ไม่ถูกจำ ส่งใหม่ได้ทันที): `_tracking/save`, `_lost_package/save`, `_admin/_tracking/save` · test: `tests/Feature/SubmitGuardTest.php`, `tests/Feature/Money/DoubleSubmitDbTest.php` (JL_BENCH_DB_TESTS=1)
  - ชั้นนี้กันรายการซ้ำ; การกันยอดติดลบเมื่อคำขอต่างกันมาพร้อมกันยังเป็น transaction + `SELECT … FOR UPDATE` แถว user แล้วตรวจยอดภายใน (ทุก endpoint ตัดเงินตอนสร้าง)

ไม่ได้ทำ route:
- `/_tracking/index`, `/_tracking/form`, `/_admin/_tracking/index`, `/_admin/_tracking/form`, `/_admin/_combine_track/index`, `/_lost_package/index`, `/_lost_package/form`, `/_admin/_lost_package/index`, `/_admin/_lost_package/form` — เป็นไฟล์เนื้อหาของหน้าที่ `content.php` include; เปิดตรงในเว็บเดิมได้แค่ fatal error (ไม่มี `$resource`/ฟังก์ชัน) → หน้าเหล่านี้เข้าทาง URL ของ RewriteRule ด้านบน
- `_lost_package/grid` และ `_admin/_lost_package/grid` เรียก `../delete` (ปุ่มลบ) และฟอร์มแอดมินโพสต์ไป `_admin/_lost_package/save` แต่ไฟล์ทั้งสองไม่มีในเว็บเดิม (404 เดิม) — ไม่ได้สร้าง

ผลต่างที่ตั้งใจ:
- **save_combine.php**: เดิม `INSERT INTO tracking VALUES(...)` 42 ค่า แต่ตารางมี 43 คอลัมน์ (มี `date_section`) → INSERT ล้มทุกครั้ง แต่ยัง `DELETE` tracking ทั้ง 2 แถว และย้าย `bill_product_on_tracking` ไป `tracking_id = 0` (ข้อมูลหาย) → ใหม่: INSERT ครบคอลัมน์ (รวม date_section ตามกติกาเดียวกับ `CombineTrack()`), ลบ/ย้าย/คิดราคาใน `DB::transaction()`
  - **(2026-10-18, fix/legacy-risks, เจ้าของอนุมัติ — แก้เฉพาะ Laravel)** เดิมรวม tracking ของลูกค้าต่างคน / ที่ลบแล้ว / อยู่ในบิลแล้ว (`payment_id > 0`) / ล็อก (`-2`) / claim (`-3`) ได้
    → ทั้ง 2 แถว (lock ใน transaction) ต้อง `is_delete = 0`, `payment_id = 0` (ยังไม่ชำระ ไม่ล็อก) และ `user` เดียวกัน ไม่งั้น `ErrorBox` (`CombineTrack::ERR_STATE` / `ERR_USER`) ไม่เปลี่ยนอะไร;
    JS หน้า `admin/combine_track` eval คำตอบที่เป็น `ErrorBox` (เดิมไม่ eval คำตอบเลย) · `CombineTrack::run()` (จาก `_api/submit_order_number_v2`) ยังเหมือนเดิม
  - **(2026-10-26, เจ้าของอนุมัติ — เหมือน YD 2026-10-25)** ยกเว้น: แถวหนึ่งอยู่ในบิลแล้ว (`payment_id > 0`) + อีกแถวเป็นแถวว่าง (`payment_id = 0`, `lot_id = 0`) ของลูกค้าคนเดียวกัน และเลขพัสดุตรงกัน (`strtolower(trim())`)
    → `CombineTrack::attach()`: ไม่สร้างแถวใหม่/ไม่คิดราคาใหม่/ไม่แตะ `bill_payment_detail` — เติม `order` ให้แถวในบิล (ถ้ายังว่าง), ย้าย `bill_product_on_tracking` / รูป / ชั้นวางมาที่แถวในบิล แล้วลบแถวว่าง
- `CombineTrack()`: loop ตรวจ `WHERE lot = ...` (คอลัมน์ไม่มีจริง → SQL error → ไม่ทำอะไร) ตัดทิ้ง; `exit(0)` เมื่อ order ว่าง → คืน flag ให้ผู้เรียกหยุดเอง; ไม่ `echo` แต่คืนข้อความ
- `save_lock.php`: เดิมไม่สนค่า `value` (ปุ่มล็อก -2 / ปลดล็อก 0) ตั้ง `payment_id = -3` เสมอ — **คงพฤติกรรมเดิม** (เจ้าของยืนยันว่าตั้งใจ)
  - **(2026-10-18, fix/legacy-risks, เจ้าของอนุมัติ)** แต่เดิมเขียนทับ tracking ที่อยู่ในบิลแล้ว (`payment_id > 0` → หลุดจากบิล) หรือที่ลบแล้วได้ → ใหม่: lock แถวใน transaction, แก้เฉพาะแถวที่ `is_delete = 0` และ `payment_id <= 0`;
    ไม่เข้าเงื่อนไข/ไม่พบ = `ErrorBox('tracking นี้อยู่ในบิลแล้ว หรือถูกลบแล้ว ไม่สามารถล็อกได้')` ไม่เปลี่ยนอะไร (สำเร็จตอบว่างเหมือนเดิม) · test: tests/Feature/Money/LegacyRisksDbTest.php
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

## 9. Review (adversarial, 2026-10-18)

ทวนซ้ำด้วย HTTP จริง :8101 vs :8201 (ลูกค้า 137 / 545 / 74 (ไม่มีสิทธิ์ lost_package), zz_fdn_cust, zz_fdn_admin, ไม่ล็อกอิน) — ไม่พบจุดที่ต้องแก้โค้ด
- เทียบ ~110 request: ทุกหน้า/grid/ajax อ่านอย่างเดียว ภาษา th/en/cn, paging ลึก (หน้า 2/50/5000/สุดท้าย/เกิน), ค้นหาทุกช่อง, สถานะทุกค่า, tracking ที่มี lot/ผูกออเดอร์/ชำระแล้ว/ลบแล้ว/goodsconfirm/ชั้นวาง → ตรงกันทั้งหมด ยกเว้นข้อที่ระบุในข้อ 2 และด้านล่าง
- สิทธิ์: ทุก POST ไม่ล็อกอิน → redirect login; ลูกค้า → ErrorBox สิทธิ์ (admin grid ตอบว่างเหมือนเดิม); Origin ข้ามเว็บ → 403 ทุกตัว
- IDOR (ลูกค้า 137 กับ tracking/lost_package ของคนอื่นที่เป็นแถว zz_): save / save_quick_quote / delete / arr_id / load_product_type / form / grid / lost_package cancel / form → ไม่มีแถวใดเปลี่ยน ไม่มีข้อมูลรั่ว
- SQL injection (เฉพาะฝั่งใหม่, endpoint อ่าน): `' OR '1'='1`, `1) OR (1=1`, UNION, `\`, `%`, array → 200 ทั้งหมด ไม่มี error/ผลเกิน; XSS ใน page/txtsearch/status/image/ship → escape ทั้งหมด
- ต่างจากเดิมเล็กน้อย (มาจากฐานร่วม ไม่แก้ในกลุ่มนี้): `page` ที่ไม่ใช่จำนวนเต็ม (`abc`, `1.5`) — `Paging::getpaging` แปลงเป็น int (เดิมสะท้อนค่าดิบลง onclick); ajax ที่ไม่ล็อกอิน เดิมตอบว่าง 200 ใหม่ 302 → login; หน้า index ที่ไม่มีสิทธิ์ ไม่มีหัวข้อ/breadcrumb เดิม
- `load_tracking` (รวมรหัส): ลำดับแถวที่ `lot_id, no` เท่ากันเดิมไม่แน่นอน (ใหม่เรียงต่อด้วย id) และคำค้นสั้น/ว่างยังคืนแถวจำนวนมาก (เช่น `7903` ≈ 6 MB) — เสนอให้บังคับความยาวคำค้น (รอเจ้าของตัดสินใจ)

## 10. E2E write-flow (2026-10-18, fix/e2e)

- **แจ้งไม่ได้รับสินค้า: คืนเงินซ้ำได้** — `_lost_package/cancel` (ลูกค้า) และ `_admin/_lost_package/cancel` ยกเลิกได้ทุกสถานะ (ปุ่มแสดงเฉพาะสถานะ 1),
  `save_approved` อนุมัติได้ทุกสถานะที่ไม่ใช่ 2 → ยกเลิกรายการที่อนุมัติ+คืนเงินแล้ว (ไม่หักคืน) → RollBack (-1 → 1 ไม่หัก) → อนุมัติอีกครั้ง = **คืนเงินซ้ำ**
  (ยืนยันบน :8100 ด้วย zz_fdn_cust: LOS-299 ได้ 2 แถว +5.25)
  ใหม่: ยกเลิก (ลูกค้า/แอดมิน) และอนุมัติได้เฉพาะสถานะ 1 ใน transaction + lock แถว; สถานะอื่น = `ErrorBox(…, 'CloseLoading()')`; ยอดคืนติดลบ = ปฏิเสธ
  อนุมัติซ้ำ (สถานะ 2) ยังตอบสำเร็จโดยไม่ทำอะไรแบบเดิม · test: `tests/Feature/Money/LostPackageDbTest.php`
