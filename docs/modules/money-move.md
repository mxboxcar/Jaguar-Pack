# money-move — เติมเงิน / โอนเงินไปจีน / ถอนเงิน

## SR (2026-10-20)

branch `mod/money-move` · worktree `/home/admin/laravel/sr-wt/money-move` · dev `bin/wt-serve money-move` → http://127.0.0.1:8306 · เทียบกับเว็บเดิม SR :8111 (admin_sr_bench)

### เปลี่ยนตาม SR
- **`_transfer/save` (โอนเงินไปจีน, ลูกค้า)** — แบบเดียวกับ `_alipay/save` ของ SR (กลุ่ม payment): **ถาม PIN จ่ายเงิน** (`PinBox::guard`, jl comment ไว้; PIN ถูกตรวจก่อนดู id) · **ใช้เงินหยวนในระบบ (balance_cn) ก่อน** ส่วนที่ขาด × `rate_transfer` ตัดจากบาท → เก็บ `bill_transfer.usecash` (บาท) + `usecash_cn` (หยวน) · เขียน balance / balance_cn (type 5, prefix transfer) เฉพาะยอดที่ไม่เป็น 0 · ยอดไม่พอ: `use_cash > getCurrentCash()` (เท่ากันพอดีผ่าน) → `ErrorBox(no_remain_cash, 'CloseLoading()')`
  - ยังอยู่ใน transaction + lock แถว user (อ่านยอดทั้งสองกระเป๋าภายใน lock) + `legacy.once` เหมือน jl port
- **`_transfer/cancel`** = hotfix 2026-10-18 ของ SR (เฉพาะรายการของตัวเอง status 1) + คืนทั้งบาทและหยวน — ใช้กติกา jl "คืนเท่าที่รายการถูกตัดอยู่จริง ไม่เกินยอดของรายการ" แยกต่อกระเป๋า (`App\Domain\MoneyMove\TransferRefund`, ใช้ร่วมกับ `_admin/_transfer/cancel`)
- **`_admin/_transfer/cancel`**: คืน usecash → balance และ usecash_cn → balance_cn ให้เจ้าของรายการ (net-based เหมือนข้างบน)
- **`_admin/_transfer/rollback`** (ไฟล์เดิม same แต่ความหมายเปลี่ยนเพราะ SR คืนหยวนตอนยกเลิก): รายการ -1 → ตัดคืนทั้ง balance และ **balance_cn** เท่าที่ยอดสุทธิของรายการขาดจาก -usecash / -usecash_cn (กดซ้ำไม่หักซ้ำ), กระเป๋าใดยอดคงเหลือไม่พอ = ปฏิเสธทั้งรายการ (`ErrorBox('ยอดเงินหยวนคงเหลือของลูกค้าไม่พอสำหรับ RollBack ...')`) — jl port ไม่แตะ balance_cn เพราะ jl คืนแค่บาท
- **`_admin/_transfer/save`**: SR แก้ในไฟล์เดิมแล้ว = เฉพาะ status 1 อื่นๆ (รวมไม่พบ / บันทึกซ้ำบนรายการ 2) `ErrorBox('รายการไม่พร้อมที่จะดำเนินการนี้', 'window.location.reload();')` — แทนกติกา jl "2 = บันทึกหลักฐานซ้ำได้" (ยังล็อกแถวใน transaction)
- **`/admin/transfer/{id}`, `/admin/transfer/create`**: SR form.php ตรวจ `admin_transfer` (jl ตรวจ `transfer` = IDOR ที่ jl port แก้ด้วยการบังคับทั้งสอง) → route ใช้ `legacy.perm:admin_transfer` อย่างเดียว
- หน้าโอน (ลูกค้า/แอดมิน): หัวเรื่องใช้ prefix `TRF-` (jl ใช้ของ alipay), แถว "ใช้ยอดเงินหยวน" (usecash_cn), ตาราง "รายการเงินที่เกี่ยวข้อง" = แถว balance ตามด้วยแถว balance_cn (หยวนแสดง `¥`) (`Lookups::balancesWithCn`) · ฟอร์มสร้างของลูกค้า: "ยอดเงินหยวนในระบบ" + ยอดโอนได้โดยประมาณ = บาท/เรท + หยวน · JS ของหน้าแอดมินเรียงฟังก์ชัน rollback ก่อน canceling2 แบบ SR
- **`_admin/_refill/save_approved`**: บันทึก `refill.approved_user` = ผู้อนุมัติ · status ≠ 1 (ไม่พบ/ยกเลิก/อนุมัติแล้ว) = `ErrorBox('รายการไม่พร้อมที่จะดำเนินการนี้', 'window.location.reload();')` (SR แก้เอง — แทนข้อความ "ถูกยกเลิกไปแล้ว" และ "สำเร็จ" ของการอนุมัติซ้ำใน jl port)
- **`_admin/_refill/cancel`**: รายการอนุมัติแล้ว (2) = `ErrorBox('รายการถูกดำเนินการสำเร็จไปแล้ว', 'window.location.reload();')` แบบ SR (`Money::REFILL_ALREADY_APPROVED` แยกจากข้อความของ `_admin/_lot/cancel` ที่ใช้ helper เดียวกัน)
- `admin/refill/{id}` + `_admin/_refill/grid`: ช่อง/คอลัมน์ "ผู้อนุมัติรายการ" (`approved_user` → user.code, ลิงก์ admin/user/{id} ไม่มี target=_blank) — join เฉพาะ 10 แถวของหน้า ไม่อยู่ใน COUNT
- **ถอนเงินออกจากระบบ (สิทธิ์ตามไฟล์ SR)**: `admin/withdrawcash` (+status) ตรวจ `admin_withdrawpoint` หลังพิมพ์หัวหน้า แล้วปฏิเสธด้วยชื่อ `admin_withdrawcash` (HTML ตรงเดิม) · `admin/withdrawcash/{id}` + grid = `admin_withdrawcash` · `save_approved` / `cancel` = `admin_withdrawpoint` — bench: admin_withdrawpoint = role 1, admin_withdrawcash = role 1/12/13
- อีเมล (refill approved/cancel, transfer success/cancel): `config('legacy.mail')` (header/center/footer + ผู้ส่ง `SRShippingSystem <no-reply@srshippingsystem.com>`), ปุ่ม `#a3b2f1` padding `10px 25px 10px 0px` — ไม่มีแบรนด์ jl แล้ว (test `test_mailer_has_no_jl_branding`)

### SR-only / ลบ (jl-only)
- ไม่มีไฟล์ SR-only หรือ JL-only ในกลุ่มนี้ (30 ไฟล์: same 12, diff 18) — route ครบชุดเดิม

### ตามไฟล์ (docs/sr-diff.md)
| ไฟล์ | ผล |
|---|---|
| `_refill/{form,grid,index,save}`, `_admin/_refill/save`, `_admin/_transfer/{grid,index}`, `_admin/_withdrawcash/{form,grid}`, `_transfer/{grid,index}` | same — ไม่เปลี่ยน (เทียบผลแล้วตรง) |
| `_admin/_transfer/rollback` | same แต่ปรับ: หัก balance_cn ด้วย (ข้างบน) |
| `_admin/_refill/{cancel,form,grid,save_approved}` | done |
| `_admin/_refill/index` | ไม่เปลี่ยน — SR ตัดการตรวจสิทธิ์ออก แต่ Laravel คง `admin_refill` (ช่องโหว่ ดูข้างล่าง) |
| `_admin/_refill/email_*`, `_admin/_transfer/email_*` | done (Mailer: สี/แม่แบบ/ผู้ส่ง SR) — เปิดตรงยังตอบว่างต้องมีสิทธิ์ |
| `_admin/_transfer/{cancel,form,save}`, `_transfer/{cancel,form,save}` | done |
| `_admin/_withdrawcash/{cancel,index,save_approved}` | done (สิทธิ์ admin_withdrawpoint) |

### ต่างจาก SR เดิมโดยตั้งใจ (นอกจากที่ jl port ทำไว้แล้ว)
- `_admin/_refill/grid` และ `admin/refill` ยังต้องมี `admin_refill` (SR ตัดออก — ดูช่องโหว่ข้อ 1)
- `_transfer/save`: ยอดที่ไม่ใช่ตัวเลข → ErrorBox (เดิม `"abc" >= ยอดหยวน` เทียบ string → ตัดหยวนทั้งหมดแต่รายการยอด 0); ยอดหยวนติดลบถือเป็น 0 (เหมือน alipay ของ payment); PIN ผิดนับ PinThrottle และไม่ log PIN เป็น plaintext
- ยกเลิก (ลูกค้า/แอดมิน): ยอด 0 ไม่เขียนแถว (`_admin/_transfer/cancel` เดิมเขียน balance + balance_cn เสมอ เช่น แถว `0.00` ของ TRF-9/TRF-40 บน bench) และคืนไม่เกินที่ถูกตัดอยู่จริง
- `_admin/_refill/cancel` ของรายการที่ยกเลิกแล้ว / ไม่พบ: ปฏิเสธ (SR ยังยกเลิกซ้ำ + ส่งอีเมลซ้ำ)
- `_admin/_withdrawcash/save_approved` / `cancel`: กติกา jl (status 1 → 2 / ยกเลิกเฉพาะ 1, คืนครั้งเดียว) — SR ยังเป็นแบบเดิมที่มีช่องโหว่ (ข้อ 3–4)
- หน้าไม่มีสิทธิ์ที่ใช้ `legacy.perm` (เช่น `admin/transfer` ของ role 7): ไม่มีหัวหน้าก่อน ErrorBox; หน้าที่เดิม exit กลาง layout (`/refill/{id}`, `/transfer/{id}` ของคนอื่น, `/admin/transfer/{id}` ของลูกค้า): layout เต็ม — เหมือน jl port
- `/transfer/{id}` ของคนอื่น: ไม่แสดงแถวเงิน (jl port) — SR แสดงทั้ง balance และ balance_cn ของรายการคนอื่น

### ช่องโหว่เดิมที่เจอใน SR
1. **`_admin/_refill/grid.php` และ `index.php` ไม่ตรวจสิทธิ์** (SR ลบ `getPermission('admin_refill')` ออก) → ลูกค้าที่ล็อกอินแล้ว POST `_admin/_refill/grid` เห็นรายการเติมเงินของทุกคน (รหัสลูกค้า, ยอด, สลิป, ผู้อนุมัติ) — ตรวจบน :8111 ด้วยลูกค้า 3 ได้ตาราง 10 แถว/หน้า; Laravel ตอบว่าง
2. `_transfer/save`: ไม่มี transaction/lock (กดซ้ำเร็วๆ ตัดเงินเกินยอด), `goLog` เก็บ PIN ที่พิมพ์ผิดเป็น plaintext, SQL ต่อ string ทุกช่อง
3. `_admin/_withdrawcash/cancel.php`: ยังเช็ค `$data['status'] != -1` ก่อนอ่าน `$data` → กดยกเลิกซ้ำคืนเงินซ้ำ (เหมือน jl เดิม)
4. `_admin/_withdrawcash/save_approved.php`: อนุมัติรายการที่ยกเลิก+คืนเงินแล้วได้
5. `_admin/_transfer/rollback.php`: หักคืนโดยไม่ตรวจยอดคงเหลือ (ติดลบได้)
6. hotfix 2026-10-18 ของ `_transfer/cancel.php` ปิดช่อง "id คนอื่น/คืนซ้ำ" แล้ว (ใหม่ทำแบบเดียวกัน + net-based)

### การตรวจ (เทียบ :8111 ด้วย session เดียวกัน, normalize host/ช่องว่าง/target=_blank/favicon)
- เท่ากันหลัง normalize (ต่างเฉพาะ `</script>` ของ dashboard.js ใน layout): `/transfer` (+status), `transfer/create` (ผู้ใช้ 2 และ 11937 มีหยวน ¥20), `transfer/{41,40,148}`, `_transfer/grid` หน้า 1–2 + ค้นหา/สถานะ, `admin/transfer` (+status), `admin/transfer/{create,41,40,148,999}`, `_admin/_transfer/grid` (หน้า 1–2, ค้นรหัส, `4|-1`), `admin/refill` (+status), `admin/refill/{create,1,202,348,349,9999}`, `_admin/_refill/grid` (หน้า 1/2/12, ค้นรหัส+สถานะ, `|-1`, `19|`, ZZFDN), `/refill`, `refill/{create,200,202,348,349}`, `_refill/grid` หน้า 1–3 + สถานะ, `admin/withdrawcash` (+status, role 1/12/ลูกค้า — การปฏิเสธของ role 12 ตรงเดิม), `admin/withdrawcash/{1,52,53}` (role 1, 12), `_admin/_withdrawcash/grid` (role 1/12/ลูกค้า, ค้นหา, สถานะ) · ภาษา en/cn: transfer/148, transfer/create, `_admin/_refill/grid`
- ต่างโดยตั้งใจ: `_admin/_refill/grid` + `admin/refill` ของลูกค้า (ข้อ 1), `/transfer/40` ของลูกค้าอื่น (ไม่แสดงแถวเงิน), หน้าปฏิเสธสิทธิ์ตามข้างบน
- E2E (Laravel :8306 เท่านั้น, ผู้ใช้ 11937/11938, แถว zz_): `_transfer/save` ไม่มี PIN/PIN ผิด/`1,00`/`abc` (ไม่สร้างรายการ) → ¥50 มีหยวน ¥20 (usecash 156.00 = 30×5.2, usecash_cn 20.00, balance -156.00, balance_cn -20.00) + ส่งซ้อน 2 คำขอ (คำขอที่ 2 = "กำลังดำเนินการ") + ส่งซ้ำ (= "ส่งซ้ำ") · ลูกค้าอื่นยกเลิก (ไม่มีผล) · ยกเลิกซ้อน 2 คำขอ (คืน 156 + ¥20 ครั้งเดียว) · แอดมิน save บน -1 (ไม่พร้อม) · rollback ซ้อน 2 (หัก 156 + ¥20 ครั้งเดียว) · cancel ×2 (คืนครั้งเดียว, ครั้งที่ 2 "รายการถูกยืนยันไปแล้ว") · rollback → save ×2 (ครั้งที่ 2 ไม่พร้อม) → cancel บน 2 (ไม่มีผล) → rollback บน 2 (ไม่แตะเงิน) · rollback เมื่อหยวนเหลือ ¥5 (ปฏิเสธ ไม่เขียนแถว) · refill ลูกค้า 2 รายการ → อนุมัติซ้อน 2 (เติม 100 ครั้งเดียว, approved_user = 11938) → ยกเลิกรายการที่อนุมัติแล้ว (SR ErrorBox) → ยกเลิก ×2 / อนุมัติรายการที่ยกเลิก / id ไม่มี (ปฏิเสธ) · แอดมินเติมเงินตรง + ส่งซ้อน (รายการเดียว) · ถอนเงิน: role 12 อนุมัติ/ยกเลิก = ปฏิเสธ admin_withdrawpoint · อนุมัติ ×2, ยกเลิกรายการที่จ่ายแล้ว (ปฏิเสธ), ยกเลิกซ้อน 2 (คืน 40 ครั้งเดียว), อนุมัติรายการที่ยกเลิก (ปฏิเสธ) · อีเมล 4 แบบใน log จาก SRShippingSystem สี #a3b2f1
  - แถวที่เขียนตรงกับ SQL ของไฟล์ SR ทุกคอลัมน์ (ยกเว้นที่ระบุว่าต่างโดยตั้งใจ) · ลบแถวที่สร้างทั้งหมดตาม id แล้ว: balance 2386–2398, balance_cn 303–310, bill_transfer 148, refill 348–351, balance_withdraw 52–53, ips 113 และคืน pin ของ 11937 เป็นค่าว่าง
- test: `tests/Feature/MoneyMove/MoneyMoveRoutesTest` (สิทธิ์ SR, ไม่มีแบรนด์ jl ใน Mailer), `tests/Feature/Access/RoleDenialTest` (admin transfer form = admin_transfer), `tests/Feature/Money/MoneyMoveDbTest` (+ กระเป๋าหยวน, PIN, กติกา SR ของ save/save_approved/cancel), `DoubleSubmitDbTest` (transfer ใส่ PIN)

### bench (`bin/bench` n=20 c=1, `artisan optimize`, mean ms รอบที่ 2)
| หน้า | เดิม | ใหม่ |
|---|---|---|
| `GET /transfer` (ผู้ใช้ 2) | 327 | 34 |
| `GET /transfer/create` (11937) | 317 | 39 |
| `POST /_transfer/grid` (ผู้ใช้ 2, 10 แถว) | 38 | 28 |
| `POST /_admin/_transfer/grid` | 49 | 30 |
| `GET /admin/transfer/148` | 490 | 39 |
| `GET /admin/refill` | 537 | 37 |
| `POST /_admin/_refill/grid` | 34 | 28 |
| `POST /_refill/grid` (ผู้ใช้ 2) | 39 (รอบ 1: 48) | 43 (รอบ 1: 32) |
| `POST /_admin/_withdrawcash/grid` | 53 | 32 |

`_refill/grid` รอบ 2 ช้ากว่า ~4 ms = เครื่องแกว่ง (รอบ 1 เร็วกว่าเดิม 16 ms) + overhead คงที่ของ Laravel กับ grid เล็ก (ตาราง refill บน bench 202 แถว)

### คำถามถึงเจ้าของ
1. **สิทธิ์ถอนเงิน**: SR ใช้ `admin_withdrawpoint` ที่หน้า list/อนุมัติ/ยกเลิก แต่ `admin_withdrawcash` ที่หน้ารายการ/grid → role 12 (Pur-staff) / 13 (PUR-SUPPORT) ที่มี admin_withdrawcash เปิดรายการได้แต่เข้าหน้า list และอนุมัติ/ยกเลิกไม่ได้ (มีแค่ role 1) — พอร์ตตามเดิม; ควรเปลี่ยนเป็น admin_withdrawcash ทั้งชุดหรือไม่
2. **`_admin/_refill/grid` ไม่ตรวจสิทธิ์ในเว็บเดิม SR** (ลูกค้าเห็นรายการเติมเงินทุกคน) — Laravel ปิดแล้ว; ควร hotfix เว็บเดิมด้วยหรือไม่ (ใส่ `getPermission('admin_refill')` กลับ)
3. `_admin/_transfer/save`: ทำตาม SR (บันทึกได้เฉพาะ status 1) — ถ้าต้องแก้หลักฐาน/หมายเหตุของรายการที่โอนแล้ว ต้อง RollBack แล้วบันทึกใหม่ (jl port เคยยอมให้บันทึกซ้ำบน 2) ใช้แบบ SR ได้หรือไม่
4. แถว `0.00` ที่ `_admin/_transfer/cancel` เดิมเขียนเสมอ (balance_cn ของรายการที่ไม่ได้ใช้หยวน) — ใหม่ไม่เขียน; ต้องการให้เขียนเหมือนเดิมเพื่อรายงานหรือไม่

โมดูลเดิม: `_refill`, `_admin/_refill`, `_transfer`, `_admin/_transfer`, `_admin/_withdrawcash` (30 ไฟล์, 19 RewriteRule)
worktree `/home/admin/laravel/jl-wt/money-move` (branch `mod/money-move`, port 8206)

## ไฟล์

| ส่วน | ไฟล์ |
|---|---|
| routes | `routes/modules/money-move.php` |
| controller | `app/Http/Controllers/MoneyMove/{RefillController,TransferController}.php`, `Admin/{AdminRefillController,AdminTransferController,AdminWithdrawCashController}.php` |
| view | `resources/views/money-move/{refill,transfer,admin/refill,admin/transfer,admin/withdrawcash,partials}/*` |
| domain | `app/Domain/MoneyMove/Lookups.php` (prefix cache, paging, ตาราง balance), `Mailer.php` (อีเมลแจ้งผล), `UploadBox.php` (getupload), `TempUpload.php` (JS blockblob), `Php74.php` |
| test | `tests/Feature/MoneyMove/MoneyMoveRoutesTest.php` |

`UploadBox` / `TempUpload` / `Php74` เป็นสำเนาจาก branch tracking / payment (ยังไม่มีในฐานร่วม) — ตอน merge รวมเป็นของกลางได้ (HTML/ผลเท่ากัน)

## Route ที่พอร์ต

| เดิม | ใหม่ | สิทธิ์ |
|---|---|---|
| `refill`, `refill/status/N` (`_refill/index.php`) | `GET /refill`, `/refill/status/{status}` | refill |
| `refill/create`, `refill/N` (`_refill/form.php`) | `GET /refill/create`, `/refill/{id}` | refill |
| `_refill/grid.php` | `POST /_refill/grid` | ล็อกอิน + ตรวจ refill ใน controller (เดิมตอบว่าง) |
| `_refill/save.php` | `POST /_refill/save` | refill |
| `transfer`, `transfer/status/N`, `transfer/create`, `transfer/N` | `GET /transfer...` | transfer |
| `_transfer/grid.php` / `save.php` / `cancel.php` | `POST /_transfer/grid` / `save` / `cancel` | ล็อกอิน (grid) / transfer |
| `admin/refill`, `admin/refill/status/N`, `admin/refill/create`, `admin/refill/N` | `GET /admin/refill...` | admin_refill |
| `_admin/_refill/grid` / `save` / `save_approved` / `cancel` | `POST` เดิม | ล็อกอิน (grid) / admin_refill |
| `_admin/_refill/email_approved` / `email_cancel` (include) | `GET\|POST` → ตอบว่าง ไม่ส่งอะไร | admin_refill |
| `admin/transfer`, `admin/transfer/status/N` | `GET /admin/transfer...` | admin_transfer |
| `admin/transfer/create`, `admin/transfer/N` (`form.php` เดิมตรวจ `transfer`) | `GET` | **transfer + admin_transfer** (ตรวจ `transfer` ก่อน → ข้อความปฏิเสธของผู้ไม่มี transfer ตรงเดิม) |
| `_admin/_transfer/grid` / `save` / `cancel` / `rollback` | `POST` เดิม | ล็อกอิน (grid) / admin_transfer |
| `_admin/_transfer/email_success` / `email_cancel` (include) | `GET\|POST` → ตอบว่าง | admin_transfer |
| `admin/withdrawcash`, `admin/withdrawcash/status/N`, `admin/withdrawcash/N` | `GET` | admin_withdrawcash |
| `_admin/_withdrawcash/grid` / `save_approved` / `cancel` | `POST` เดิม | ล็อกอิน (grid) / admin_withdrawcash |

ไม่ทำ route: `*/index.php`, `*/form.php` (เดิมเป็นไฟล์ที่ index.php include — เปิดตรงได้แค่ชิ้น HTML ที่พัง), `_admin/_withdrawcash/email_*.php` (ไม่มีไฟล์จริง)
JS ในหน้าแอดมินเติมเงินเรียก `_admin/_lot/load_autocomplete_user` = ของกลุ่ม lot

## ต่างจากเดิมโดยตั้งใจ

- **กันกดซ้ำ/ส่งซ้อน (double submit)** (เจ้าของอนุมัติ 2026-10-18, E2E: ดับเบิลคลิก `_taobao/save` = 2 รายการ ตัดเงิน 2 ครั้ง — เว็บเดิมก็เป็น): middleware `legacy.once` (`App\Support\SubmitGuard`) — lock ต่อ ผู้ใช้+route ระหว่างประมวลผล (คำขอที่ซ้อนได้ `ErrorBox('รายการนี้กำลังดำเนินการ กรุณารอสักครู่')`) + payload เดิมที่สำเร็จแล้วภายใน 8 วินาทีได้ `ErrorBox('รายการนี้ถูกส่งไปแล้ว (ส่งซ้ำ) ...')` (คำตอบ error/กล่อง PIN ไม่ถูกจำ ส่งใหม่ได้ทันที): `_refill/save`, `_transfer/save`, `_admin/_refill/save` (เติมเงินทันที) · test: `tests/Feature/SubmitGuardTest.php`, `tests/Feature/Money/DoubleSubmitDbTest.php` (JL_BENCH_DB_TESTS=1)
  - ชั้นนี้กันรายการซ้ำ; การกันยอดติดลบเมื่อคำขอต่างกันมาพร้อมกันยังเป็น transaction + `SELECT … FOR UPDATE` แถว user แล้วตรวจยอดภายใน (ทุก endpoint ตัดเงินตอนสร้าง)
- ยอดเงินที่มี `,` แบบที่ไม่ใช่ตัวคั่นหลักพัน (`1,00`, `1,0000`) → `ErrorBox` (`Money::ambiguousComma`) — เดิม `str_replace(',', '')` = `1,00` กลายเป็น 100; `1,000.50` ยังรับ (= 1000.50)
- `_admin/_refill/save_approved` ของรายการที่ถูกยกเลิกแล้ว (status -1) → `ErrorBox('รายการนี้ถูกยกเลิกไปแล้ว ไม่สามารถอนุมัติได้')` — เดิมตอบ "สำเร็จ" ทั้งที่ไม่ได้เติมเงิน
- ค่าจาก DB/ผู้ใช้ใน HTML ถูก escape (`acc_name`, `remark`, `cancel_remark`, `branch_*`, ชื่อไฟล์แนบ, ค่าค้นหา) — ค่าคำค้นที่มี `'` แสดงเป็น `&#039;` แทน `'`
  ค่า setting `bank_acc/bank_no/bank_name/bank_branch` ยังแสดงดิบ (แอดมินตั้ง)
- รายการที่ไม่พบ/ของคนอื่นใน `/refill/{id}`: เดิม layout ถูกตัดกลางหน้า (exit) — ใหม่แสดง layout เต็ม + ErrorBox เดิม
- grid: LIKE ค้นหา escape `%` `_` (เดิมเป็น wildcard)
- `save_approved` ของเติมเงิน: เดิม `sleep(3)` แล้วลบ balance ซ้ำ — ใหม่ lock แถว refill ใน transaction (ไม่ sleep) แล้วยังลบซ้ำแบบเดิม
- งานเงินทุกตัว (`_transfer/save`, `_transfer/cancel`, `_admin/_refill/save`, `save_approved`, `_admin/_transfer/cancel`, `rollback`, `_admin/_withdrawcash/*`) อยู่ใน `DB::transaction` + `lockForUpdate`
  `_transfer/save` lock แถว user ก่อนเช็คยอด (เดิมกดซ้ำเร็วๆ ใช้เงินเกินยอดได้)
- ชื่อไฟล์แนบที่รับจาก POST (`upload_file`, `upload_approved_file`, `approved_upload`) ต้องเป็นชื่อแบบที่ system_upload ออกให้ (`[A-Za-z0-9._-]`) ไม่งั้นบันทึกเป็น `''`
- อีเมล: Mail ของ Laravel (dev = log), ข้อความที่แอดมินพิมพ์ในอีเมลถูก escape; LINE/push = no-op เหมือนเดิม (ฟังก์ชันเดิมว่าง)
- อีเมลแจ้งผลใช้ id ของแถวที่พบ (เดิมใส่ `$_POST['id']` ดิบลงหัวเรื่อง/ลิงก์ HTML: `123<img ...>` → MySQL แปลงเป็น 123 แล้วส่งถึงลูกค้าเจ้าของรายการ 123) ไม่พบแถว = ไม่ส่ง
- ไฟล์ email_*.php เปิดตรง: เดิมไม่ต้องล็อกอินและยิง Mailgun ด้วย `$email_id` ว่าง — ใหม่ต้องมีสิทธิ์ และไม่ส่งอะไร

## ช่องโหว่/บั๊กเดิมที่แก้

1. **`_transfer/cancel.php` — สร้างเงินได้**: UPDATE กรอง `user` แต่ INSERT คืนเงินไม่กรอง และเช็คแค่ `status != -1` → ลูกค้าส่ง id ของรายการคนอื่น (หรือรายการที่โอนสำเร็จแล้ว) ได้ `usecash` เข้าบัญชีตัวเองซ้ำได้ไม่จำกัด
   ใหม่: คืนเงินเฉพาะรายการของตัวเองที่ status = 1 (ปุ่มยกเลิกเดิมแสดงเฉพาะ status 1)
2. **`_admin/_withdrawcash/cancel.php`**: เช็ค `$data['status'] != -1` ก่อนอ่าน `$data` (ว่าง → จริงเสมอ) → กดยกเลิกซ้ำคืนเงินซ้ำทุกครั้ง, id ที่ไม่มีสร้างแถว balance ขยะ — ใหม่ต้องมีรายการและ status != -1
3. **`_admin/_transfer/form.php` ตรวจ `getPermission('transfer')`** (ลูกค้ามีทุก role) → ลูกค้าเปิด `/admin/transfer/<id>` ดูรายการ/บัญชีปลายทาง/ยอดเงินของทุกคนได้ — ใหม่ต้องมี admin_transfer ด้วย
4. **`_transfer/form.php`**: ตาราง balance query ด้วย `prefix_ref_id` อย่างเดียว → `/transfer/<id คนอื่น>` เห็นรายการเงินของคนอื่น — ใหม่แสดงเฉพาะรายการของตัวเอง
5. SQL injection ทุกไฟล์ (`$_POST['id']`, `txtsearch`, `code`, `cancel_remark` ฯลฯ ต่อ string) — ใหม่ bind ทั้งหมด
6. Stored XSS: `acc_name`/`remark` (ลูกค้า) แสดงดิบในหน้าแอดมิน, `attachment` (ลูกค้าส่งชื่ออะไรก็ได้) ถูกฝังใน `<script>` ของ grid แอดมิน, `$_GET['status']` ฝังใน JS ของ index
7. Path traversal: `file_exists('../upload/temp/'.$_POST['upload_file'])`
8. email_*.php เปิดตรงได้โดยไม่ล็อกอิน (ยิง Mailgun)

## บั๊กเงินที่แก้ตามที่เจ้าของอนุมัติ (2026-10-18, รายงานข้อ 9–12 — แก้เฉพาะฝั่ง Laravel เว็บเดิมยังเป็นแบบเก่า)

สถานะของ refill / bill_transfer / balance_withdraw: 1 = รอ, 2 = สำเร็จ, -1 = ยกเลิก (คืนเงินแล้ว) — helper ร่วม `app/Domain/Money/Money.php`
ทุกตัวอยู่ใน `DB::transaction` + `lockForUpdate()` แถวรายการ แล้วล็อกแถว `user` ของลูกค้าก่อนเขียน balance; ข้อความปฏิเสธ = `ErrorBox('…', 'CloseLoading()')` แบบเดิม

| endpoint | เดิม | ใหม่ |
|---|---|---|
| `_admin/_transfer/save` | ไม่ตรวจ status: บันทึกรายการที่ยกเลิก+คืนเงินแล้วเป็น 2 ได้ (ลูกค้าได้ทั้งเงินคืนและการโอน) | 1 → 2, 2 = บันทึกหลักฐานซ้ำได้ (ไม่แตะเงิน, ปุ่มแสดงทุกสถานะ), -1/ไม่พบ = ปฏิเสธ (ต้อง RollBack ก่อน) |
| `_admin/_transfer/rollback` | -1 → 1 หักเงินจากแถวตัดเงินแรกทั้ง balance **และ balance_cn** โดยไม่เช็คยอด (การยกเลิกคืนเข้า balance เท่านั้น → แถว balance_cn = หักซ้ำ) | หักเท่าที่ยอดสุทธิของรายการขาดจาก `-usecash` (สุทธิครบแล้ว = ไม่หักอีก → กดซ้ำไม่หักซ้ำ), ยอดคงเหลือไม่พอ = ปฏิเสธ, ไม่แตะ balance_cn; 2 → 1 ไม่แตะเงินเหมือนเดิม |
| `_admin/_transfer/cancel`, `_transfer/cancel` | คืน `usecash` เต็มเสมอ | (เฉพาะ status 1 เหมือนเดิม) คืนเท่าที่รายการถูกตัดอยู่จริง ไม่เกิน `usecash` |
| `_admin/_withdrawcash/save_approved` | ไม่ตรวจ -1 → อนุมัติ (จ่ายออก) รายการที่ยกเลิก+คืนเงินแล้วได้ | 1 → 2, 2 = บันทึกหลักฐานซ้ำได้, -1/ไม่พบ = ปฏิเสธ ไม่บันทึกอะไร |
| `_admin/_withdrawcash/cancel` | (Laravel เดิม) status ≠ -1 → คืนเงินรายการที่จ่ายไปแล้ว (2) ได้ | เฉพาะ status 1 (= ปุ่มในหน้า), 2 = "จ่ายแล้ว ยกเลิกไม่ได้", คืนเท่าที่ถูกตัดไว้จริง (กดซ้ำไม่คืนซ้ำ) |
| `_admin/_refill/cancel` | ไม่ตรวจ status → ยกเลิกรายการที่อนุมัติแล้วได้ (เงินที่เติมยังอยู่) | เฉพาะ status 1 (ปุ่มยกเลิกในหน้าเดิมแสดงเฉพาะสถานะนี้) — **เลือกปฏิเสธ** ไม่ใช่หักเงินคืน เพราะ UI เดิมไม่มีทางยกเลิกรายการที่อนุมัติแล้ว ถ้าต้องดึงเงินคืนให้ทำรายการปรับยอดแยก |
| `_admin/_refill/save_approved` | ลบแถวซ้ำด้วย `prefix = '10'` ตายตัว; อนุมัติรายการยอดติดลบได้ (= หักเงินลูกค้า) | prefix จากตาราง `prefix` (= 10 ตอนนี้), ล็อก user, ยอด ≤ 0 = ปฏิเสธ |
| `_refill/save` (และสำเนา `_payment/save`) | รับยอดอะไรก็ได้ (ติดลบ / 0 / `abc`) | ต้องเป็นตัวเลขบวก ทศนิยมไม่เกิน 2 ตำแหน่ง (ตัด `,` แบบเดิม) ไม่งั้น ErrorBox |

test: `tests/Feature/Money/MoneyMoveDbTest.php` (รันกับ admin_jl_bench ใน transaction ที่ rollback ทุกครั้ง, opt-in `JL_BENCH_DB_TESTS=1`) + `tests/Feature/Money/MoneyRulesTest.php` (ไม่แตะ DB)

## พฤติกรรมเดิมที่คงไว้

- หัวเรื่อง `/transfer/{id}` ใช้ prefix ของ alipay (`ALI-`) ตามเดิม; ข้อความสำเร็จของ withdrawcash ใช้ prefix `WPOINT-` ตามเดิม
- แอดมิน `_admin/_refill/save` (เติมเงินให้ลูกค้าตรง): **แก้แล้ว (fix/followups, เจ้าของอนุมัติ)** เดิมรับยอดติดลบ/0/`abc` → `Money::validRefillAmount()` เดียวกับ `_refill/save` (ตัวเลขบวก ทศนิยมไม่เกิน 2 ตำแหน่ง) ไม่งั้น `ErrorBox('กรุณาระบุจำนวนเงินให้ถูกต้อง (ต้องมากกว่า 0)', 'CloseLoading()')` — ถ้าต้องปรับยอดลง ใช้ทางปรับยอดของ balance แทน
- `_admin/_refill/cancel` และ `_admin/_lot/cancel` (สำเนาเดิมในกลุ่ม lot) ใช้ `Money::cancelPendingRefill()` ตัวเดียวกัน (transaction + lockForUpdate, เฉพาะ status 1, ซ้ำ = ErrorBox ไม่เขียนแถว) · test: `tests/Feature/Money/MoneyMoveDbTest.php`

## ความเร็ว grid เติมเงิน (2026-10-18, fix/grid-perf — bench refill 53,803 แถว, ลูกค้า 137 = 17.7k แถว)

- `_refill/grid`: `COUNT(*) … is_delete = 0 AND id LIKE '%%' AND user = ?` ต้องเปิดทุกแถวของลูกค้า (62 ms) → ช่องค้นว่างไม่ใส่ `id LIKE '%%'` (id int NOT NULL = จริงทุกแถว)
  และนับด้วย `GridQuery::liveCount()` (นับจาก `idx_refill_user (user, id)` ลบแถวที่ถูกลบ) ≈ 8 ms
- `_admin/_refill/grid`: `COUNT(*)` ผ่าน `LEFT JOIN user` + `id LIKE '%%' OR code LIKE '%%'` (330 ms) → ช่องค้นว่างไม่มีเงื่อนไข (นับจาก `idx_notif`) ≈ 21 ms;
  มีคำค้น: `user.code LIKE` ผ่าน join ≡ `refill.user IN (SELECT id FROM user WHERE code LIKE …)` (user.id = PK) → นับจาก index ไม่ join ≈ 70 ms
- ผล HTML เท่าเดิม (diff กับ :8101 ลูกค้า 137/545/380/418/10579 × สถานะ/คำค้น/หน้า และแอดมิน)
- grid โอน/ถอน (`bill_transfer` 2.5k, `balance_withdraw` 3.6k แถว) ไม่คำนวณยอดและเร็วอยู่แล้ว (< 20 ms ใน DB) — ไม่แก้

## การตรวจ

- HTML เทียบกับ :8101 (ตัดช่องว่าง, แทน href) ด้วย session ลูกค้า (137, 10579, zz_fdn_cust) และแอดมิน (zz_fdn_admin): ทุกหน้า/grid ตรงกัน ยกเว้นค่าที่ escape ตามข้างบน
  - index ×5, status ×3, grid ลูกค้า/แอดมิน (หน้า 1/2/3/20/0, ค้นหา, สถานะ, `'` `\`), form ลูกค้า/แอดมินทุกสถานะ (-1/1/2, create, id ไม่มี, id ของคนอื่น)
- การเขียน DB ทดสอบกับ endpoint ใหม่เท่านั้น — **endpoint เขียนของ :8101 ห้ามยิง**: `mysqlquerys()` → `goLog()` เขียนไฟล์ log ลง `/home/admin/web/portal.jlcargoth.com/public_html/logs/` (production) และ `save_approved`/`cancel`/`save` ของแอดมินส่งอีเมล Mailgun จริง (`debug_mail = false`)
  แถวที่เขียนตรวจกับ SQL เดิมทีละคอลัมน์ (refill, bill_transfer, balance, balance_withdraw): ค่า, ประเภท (type 1/5), prefix (10/20/39), detail ตรงกัน
- ข้อมูลทดสอบ `zz_` ของ user zz_fdn_cust ถูกลบหลังทดสอบ

### bench (`bin/bench`, -n 30 -c 1, หลัง `artisan optimize`)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/refill` (user 137) | 294 / 342 | 34 / 41 |
| `POST /_transfer/grid` (user 10579, 10 แถว) | 54 / 48 | 34 / 33 |
| `POST /_admin/_transfer/grid` (แอดมิน) | 61 / 65 | 41 / 42 |
| `/admin/withdrawcash/3701` (แอดมิน) | 343 / 413 | 45 / 40 |

N+1: grid เดิมเรียก `getPrefix()` (1 query) ทุกแถว → ใหม่ cache ต่อ request; จำนวน query ต่อหน้าคงที่

## review (adversarial, 2026-10-18)

- เทียบ HTML :8101 กับ :8206 ซ้ำทุกหน้า/grid (th/en/cn, UA มือถือ, ลูกค้า 137/10579/zz_fdn_cust, แอดมิน zz_fdn_admin, หน้า 1/2/3/20/0/abc/1e1/3.5, ค้นหา, สถานะ, id ของคนอื่น, id ไม่มี) ด้วยข้อมูล zz_ (refill/bill_transfer/balance_withdraw/balance) — ตรงกันทั้งหมด ยกเว้นที่ escape และ paging ด้านล่าง
- เขียน DB (เฉพาะ :8206): refill save, transfer save/cancel (ของตัวเอง/คนอื่น/สถานะ 2/ซ้ำ), admin refill save/save_approved (ซ้ำ)/cancel, admin transfer save/cancel/rollback (-1 มีแถวตัดเงิน, 2), withdrawcash cancel (ซ้ำ, id ไม่มี)/save_approved — แถวที่เปลี่ยนตรงกับ SQL เดิม และกระทบเฉพาะแถว zz_
- SQL injection (เฉพาะ :8206, grid อ่านอย่างเดียว + id ของแถว zz_): `txtsearch`, สถานะ, `page`, `id` — bind ทั้งหมด ไม่มี 500
- CSRF: Origin/Referer ต่างโดเมน = 403, ไม่มี XHR = redirect 403 แบบเดิม, ไม่ล็อกอิน = /login
- แก้: อีเมลแจ้งผลใช้ id จาก DB (ดูข้างบน)
- ยังต่างเล็กน้อย (ฐานร่วม `Paging::getpaging`): `page` ที่ไม่ใช่จำนวนเต็ม (`abc`, `3.5`) ปุ่มหน้าก่อน/ถัดไปและหน้าที่ active ต่างจากเดิม (เดิมได้ `2.5`/`4.5`) — มาจาก input ที่แต่งเองเท่านั้น
