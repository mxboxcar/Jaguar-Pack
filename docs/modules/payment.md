# กลุ่ม payment — รายงานการพอร์ต

## PACK (2026-10-22)

branch `mod/payment` · worktree `/home/admin/laravel/pack-wt/payment` · dev `bin/wt-serve payment` → http://127.0.0.1:8804 · เทียบกับเว็บเดิม PACK (admin_pack_bench)
PACK = โค้ด SR + VAT/คูปองส่วนลดแบบ jl → ฐานคือ port ของ SR แล้วนำส่วน VAT ของ jl port กลับมาปรับเป็นสูตร PACK

### เปลี่ยนตาม PACK
- **VAT (สูตร PACK — ไม่มี "หัก ณ ที่จ่าย 3%" แบบ jl)**: `PaymentTotals::vat()` — ยอดก่อน VAT (หักคูปองแล้ว) → `vat_before = nfs(nfs(ยอด × v) / 100)` (nfs ตัดทศนิยม ไม่ปัด), `shipvat = total = ยอด + vat_before`; คอลัมน์ `vat`, `shipvat_before`, `shipv` ได้ '' = 0 (บรรทัดคำนวณเดิมถูก comment)
  - ลูกค้า: ช่อง "ต้องการ VAT?" ใน `payment/create/{random}`, `_payment/load_shipping_price` คืน `total` + `html` (แถวส่วนลดท้ายบิล / ภาษีมูลค่าเพิ่ม / รวม), `save_submit_payment` เขียน is_vat/v/vat_before/shipvat
  - หน้าบิล (`payment/{id}`, `admin/payment/{id}`): แถว VAT v% + ยอดสุทธิ; grid ลูกค้า/แอดมินมีป้าย VAT
  - แอดมิน: ปุ่ม "คิด VAT" → `POST /_admin/_payment/save_vat` (เปิด: ยอด = ค่าฝาก + ค่าพัสดุ + ค่าส่งเพิ่ม (ไม่รวมค่าส่งในไทย) + VAT, shipv = 3; ปิด: ยอดรวมค่าส่งในไทย, VAT = 0) แก้แถว balance ตัดเงิน (prefix 18) ตามยอดใหม่
- **คูปองส่วนลด** (ตาราง `coupon`: name, price, payment_id = บิลที่ใช้ — ใช้ได้ครั้งเดียว)
  - ลูกค้า: ช่อง "คูปองส่วนลด(ถ้ามี)" → `POST /_payment/load_discoutcode` (ราคาส่วนลด / `ErrorBox('ไม่มีรหัสส่วนลดนี้')`), `load_shipping_price` รับ `discount`, `save_submit_payment` รับ `discount_code` (ไม่พบ = `ErrorBox('ไม่พบคูปองส่วนลดนี้')` ก่อนเช็ค 20 วินาที) หักจากยอดก่อน VAT แล้ว `UPDATE coupon SET payment_id` (ไม่ได้เขียน `bill_payment.discount` แบบเดิม → 0.00)
  - หน้าบิล: "รหัสคูปองส่วนลด", แถว "Discount" (`bill_payment.discount`, ลูกค้า) และ "ส่วนลดท้ายบิล" (ราคาคูปอง); grid แสดง `total - discount`
  - แอดมินสร้างบิล: แถว Discount ว่าง + บรรทัด JS ที่ถูก comment (`calDiscountPaymentDisplay()` คืน 0 เสมอ → พิมพ์ `'0'` ใน comment แบบเดิม); `save_submit_payment` เขียน `discount` = '' → 0.00
- **สิทธิ์ `admin_viewprice_on_payment`** (role 1, 3): ไม่มีสิทธิ์ = grid แอดมินไม่มีคอลัมน์ยอดเงิน, หน้า `admin/payment/{id}` ไม่มีช่องราคา kg/คิว + ส่วนสรุปยอด และรายการ balance แสดงเฉพาะ prefix export
- ค่าเปลี่ยนผู้ส่ง = setting `change_sender` เสมอ (แบบ jl — PACK ไม่มี `charge_sender_edit` ต่อรหัสลูกค้าแบบ SR; `PaymentTotals::senderCharge()` ลบแล้ว)
- ไม่มีรหัสย่อย (tracking.subuser) / is_takeapic / date_section: ตัดช่องค้นหา + คอลัมน์ทุกหน้า, `bill_payment_detail` คัดลอกคอลัมน์ของ PACK (create_date … header)
- `_payment/load_select_lot` = jl (ไม่มีช่องค้นหา), `payment/create` = หน้า jl; `_payment/load_create`: ลิงก์ tracking → `tracking/search/{tracking}` + รูป tracking_image (เหมือน SR)
- `_admin/_payment/save_approved`: calPoint ทำงานจริงแบบ SR แต่ไม่มีขั้นลบพัสดุออกจากชั้นวาง (ไม่มีตาราง warehouse_section_detail)
- `_admin/_payment/save_delete` = jl: เฉพาะสถานะ 1 (ไม่มีกิ่งสถานะ 0 แบบ SR)
- `_payment/save` (สำเนา _refill/save): ตรวจสิทธิ์ `payment` ก่อนแล้ว `refill` (route มีทั้งสอง)
- alipay: `_alipay/cancel.php` ถูก comment ทั้งไฟล์ → ตอบว่าง ไม่ยกเลิก/ไม่คืนเงิน และหน้า `alipay/{id}` ไม่มีปุ่มยกเลิก · `_admin/_alipay/cancel` คืนบาท/หยวนเฉพาะยอดที่ไม่เป็น 0 · `_admin/_alipay/save` ไม่มีเงื่อนไขสถานะ (= jl) → ใช้การแก้ของ jl port (ไม่ยอมบิลที่ยกเลิกแล้ว) · หน้า `admin/alipay/{id}` แถว balance_cn ใช้ numberformat แบบบาท + ลำดับ JS แบบ PACK
- กล่องแนบไฟล์ (`App\Domain\Payment\UploadBox`) = getupload รุ่นเก่าของ PACK (นามสกุลรูป jpg/png/jpeg ตัวพิมพ์ตรง, ไฟล์อื่นเป็นลิงก์ fancybox, zip/rar/7z แสดงข้อความ, ไม่มีตัวล้าง input)
- อีเมล: ปุ่มสี #eb4823, header/center/footer + ผู้ส่งจาก `config('legacy.mail')` (PACK-Cargo), alipay ไม่ select line_uid
- สี icon หน้า create (#eb4823)

### pack-only ที่พอร์ต
- `admin/coupon` (+ `/status/{n}`), `admin/coupon/create`, `admin/coupon/{id}` (สิทธิ์ `admin_coupon` — role 1), `POST /_admin/_coupon/grid` (ไม่มีสิทธิ์ = ว่าง), `POST /_admin/_coupon/save` (`legacy.once`), `POST /_admin/_coupon/delete` — `App\Http\Controllers\Payment\Admin\AdminCouponController`, view `payment/admin_coupon/*`
  - ฟอร์มเพิ่ม: ชื่อเริ่มต้น randd(6); แก้ได้เฉพาะคูปองที่ยังไม่ถูกใช้ (ใช้แล้วแสดงอย่างเดียว + ลิงก์บิล); ชื่อซ้ำตอนแก้ = `ErrorBox('คูปองส่วนลดซ้ำ')` (เทียบทุกแถวรวมที่ลบ/ใช้แล้ว แบบเดิม); `'` ในชื่อเก็บเป็น `''` แบบ nStr เดิม
  - เมนู `admin/coupon` อยู่ใน layout ของฐานร่วมแล้ว
- `POST /_admin/_payment/save_vat`, `POST /_payment/load_discoutcode`
- `_payment/create_form_bk.php` = ไฟล์สำรองที่ไม่มีใครลิงก์ → dead (ไม่มี route)

### ลบ (SR-only)
`admin/receipt`, `admin/receipt/status/{n}`, `POST /_admin/_receipt/grid` (AdminReceiptController + view + test) · PaymentSrDbTest → PaymentPackDbTest

### ต่างจากเดิมโดยตั้งใจ (เพิ่มจากของ SR/jl port)
- คูปองที่ใช้ตอนจ่าย: อ่านแบบล็อกแถว (`SELECT … FOR UPDATE`) ภายใน transaction เดียวกับการสร้างบิล — เดิมอ่านนอก lock แล้ว UPDATE ท้ายไฟล์ (สองบิลพร้อมกันใช้คูปองใบเดียวได้)
- `_admin/_coupon/save`: ส่วนลดต้องเป็นตัวเลขไม่ติดลบ (`Money::adminAmount`: "1,000" → 1000, "1,00"/"abc"/ติดลบ = ErrorBox) — เดิมลงดิบ (MySQL ตัดที่ , → "1,000" = 1.00)
- `_admin/_coupon/delete`: ไฟล์เดิมเป็นสำเนา `_admin/_product_type/delete.php` (ตรวจ `admin_product_type` แล้ว `UPDATE product_type SET is_delete = 1 WHERE id = <id คูปอง>`) = ลบประเภทสินค้าผิดตัว ส่วนคูปองไม่ถูกลบ → ใหม่ตอบ `SuccessBox` แบบเดิม (grid โหลดใหม่ คูปองยังอยู่เหมือนเดิม) โดยไม่แตะตารางใด (สิทธิ์ยังเป็น admin_product_type ตามไฟล์เดิม)
- `_admin/_payment/save_vat`: บิลสถานะ -1 / ไม่มีอยู่ ไม่ทำอะไร (เหมือน jl port) + ล็อกแถวบิล
- `_alipay/cancel`: ต้องล็อกอิน (เดิมไฟล์ว่างทั้งไฟล์ ไม่ตรวจอะไร — ผลเหมือนกันคือไม่ทำอะไร)
- grid คูปอง: ค่าค้นหา escape, ค่า page ใน JS กรองเหลือ `[0-9A-Za-z.-]`
- ที่เหลือคงตาม SR/jl port (escape, ไม่มี target=_blank, favicon, ไม่มี key ตัวเลขซ้ำใน JSON ของ load_shipping_price, หน้าไม่มีสิทธิ์ไม่มีหัวหน้า)

### ช่องโหว่/บั๊กเดิมที่เจอใน PACK
1. `_admin/_coupon/delete.php` ลบ **product_type** ที่ id ตรงกับคูปอง (ข้อมูลประเภทสินค้าหาย) — ไม่พอร์ตส่วนที่ลบ
2. คูปองใช้ซ้ำได้เมื่อจ่ายพร้อมกัน (อ่าน/ผูกคูปองนอก lock) — แก้แล้ว
3. `save_vat` คิดยอดใหม่โดยไม่หักคูปอง และกิ่งเปิด VAT ไม่รวมค่าส่งในไทย → กด VAT กับบิลที่ใช้คูปองแล้ว แถวตัดเงินเปลี่ยนเป็นยอดไม่มีส่วนลด (E2E: บิลจ่าย 279.27 (คูปอง 40) → ปิด VAT 301.00 → เปิด VAT 322.07) — คงไว้ (ดูคำถาม)
4. `_admin/_coupon/form.php` ลิงก์ "เลขบิล" ชี้ `admin/payment/<id คูปอง>` (ข้อความเป็นเลขบิลจริง) — คงไว้แบบเดิม
5. `_admin/_coupon/grid.php` ค่าค้นหา/page ไม่ escape (XSS) — แก้แล้ว; `save.php` SQL ต่อ string — bind ทั้งหมด
6. `_payment/create_form.php` JS เรียก `CalculateTotal()` ตอนโหลดโดยไม่ส่งค่า → `eval(undefined).toFixed` error ทำให้คำสั่งล้างช่องคูปองที่ตามมาไม่ทำงาน — คง JS เดิมทุกตัวอักษร
7. hotfix "ATOMIC กัน double-submit" ใน `_payment/save_submit_payment.php` อ้าง `tracking.random` ที่ไม่มี → ไม่เคยทำงาน (เหมือน jl/SR — Laravel ใช้ lock + legacy.once)

### การตรวจ (session เดียวกัน, normalize host/ช่องว่าง/target=_blank; ไฟล์เดิมรันด้วย php7.4 CLI แบบอ่านอย่างเดียว เพราะ :8114 ตอบ 2–27 วินาที/หน้าช่วงที่ทุกกลุ่มใช้พร้อมกัน)
- **เท่ากันทุก byte หลัง normalize**: ลูกค้า 348 — `/payment` (+status), `_payment/grid` (หน้า 1–2, สถานะ -1, ค้นหา), `payment/create`, `load_select_lot`, `load_create` (ทั้งหมด/ถึงไทย/ค้นหา), `load_create_sum`, `payment/create/{random}`, `payment/{id}` (VAT/ไม่ VAT/ยกเลิก), `load_discoutcode` (ใช้ได้/ใช้แล้ว/ว่าง/ตัวพิมพ์เล็ก), `load_shipping_price` **36 แบบ** (VAT 0/1 × คูปอง ว่าง/ใช้ได้/ไม่มี × ประเภทส่ง 3 แบบ × ผู้ส่ง 0/1 — JSON เท่ากันเมื่อตัด key ตัวเลขซ้ำ); ลูกค้า 37/13 (บิลที่มีคูปอง/discount); ลูกค้า 57 `alipay`, `alipay/create`, `alipay/{id}` (สถานะ 2/-1), `_alipay/grid` (หน้า 1–2)
  แอดมิน 11938 — `admin/payment` (+status), `_admin/_payment/grid` (หน้า 1–2, สถานะ, รหัสลูกค้า, PAY-id), `admin/payment/{id}` (VAT 12635, ยกเลิก 12618, คูปอง 8378/8369, 63 พัสดุ 478, ไม่ VAT), `admin/payment/create[/348]`, `admin/payment/create/{u}/{random}` (2 ชุด), `_admin/_payment/load_create`/`load_select_lot`/`load_autocomplete_user`/`load_user_by_id`/`load_shipping_price` (4 แบบ), `admin/alipay`, `_admin/_alipay/grid`, `admin/alipay/create`, `admin/alipay/{id}`, `admin/coupon`, `_admin/_coupon/grid` (หน้า 1–2, ค้นหาชื่อ/เลขบิล), `admin/coupon/{id}` (ใช้แล้ว/ยังไม่ใช้/ไม่มีอยู่), `admin/coupon/create` (ต่างแค่ randd)
  role 7 (923, ไม่มี admin_viewprice_on_payment): `admin/payment/{id}` ×2, `_admin/_payment/grid` เท่ากัน; `admin/coupon` ไม่มีสิทธิ์ = ErrorBox (ใหม่ไม่มีหัวหน้า — ตามแบบ legacy.perm)
  `_payment/view_example` (sender 0/1): ต่างเฉพาะ favicon
- E2E (Laravel :8804 เท่านั้น, ผู้ใช้ zz_pay_e2e 12945 ที่สร้างเอง + แอดมิน 11938): คูปองเพิ่ม/ส่งซ้ำ (legacy.once)/ชื่อซ้ำ/"1,00"/แก้ "1,000"/ลบ (ไม่แตะอะไร) · ลูกค้าเลือกพัสดุ → หน้า create → load_discoutcode/load_shipping_price (279.27) → ไม่มี PIN/PIN ผิด/ส่งพร้อมกัน 2 ครั้ง (บิลเดียว, ครั้งที่สามได้ "ส่งซ้ำ") → บิล 279.27 (ค่าฝาก 70 + ผู้ส่ง 30 + พัสดุ 201 - คูปอง 40 + VAT 18.27, vat/shipvat_before/shipv/discount = 0) คูปองผูกบิล · คูปองใช้ซ้ำ = "ไม่พบคูปองส่วนลดนี้" · 20 วินาที · บิลที่สอง ไม่ VAT 100.50 · แอดมิน approve พร้อมกัน ×2 (แต้ม 1.34 / 0.67 ครั้งเดียว, อีเมล PACK-Cargo 1 ฉบับ) · save_vat ปิด/เปิด (301.00 / 322.07 + แถว balance ตาม) · ยกเลิกบิลสถานะ 2 = "รายการถูกยืนยันไปแล้ว" · แอดมินสร้างบิล pay_now (180.00, discount 0) + ส่งซ้ำ · ลูกค้ายกเลิกพร้อมกัน ×2 (คืน 180 ครั้งเดียว, แต้ม -0.53) · alipay 25¥ (หยวน 20 + บาท 25.75) + ส่งซ้ำ · ลูกค้ายกเลิก = ว่าง ไม่เปลี่ยน · แอดมิน save ×2 / cancel สถานะ 2 (ปฏิเสธ) / rollback / cancel ×2 (คืน 25.75 + 20¥ ครั้งเดียว) / save บิลที่ยกเลิก (ปฏิเสธ) — ตรงกับค่าที่อ่านจากโค้ดเดิมทุกแถว
  ลบแถวที่สร้างทั้งหมดตาม id แล้ว: user 12945, tracking 99749–99751/99811, bill_payment 12846/12875/12876 + detail 69837/69838/69845/69846, temp 130623–130625, temp_admin 29437, balance 65356/65357/65465/65467–65470, balance_cn 2326/2354/2355, balance_point 12469/12473/12474, bill_alipay 456, coupon 36, ips 498, sessions_v2 ที่สร้างด้วย make_bench_session
- test: `tests/Feature/Payment/PaymentPackDbTest.php` (11 test), `PaymentRoutesTest` (route คูปอง/save_vat/load_discoutcode, URL ที่ PACK ไม่มี = 404, `_payment/save` สองสิทธิ์), ปรับ `LegacyRisksDbTest` (ประเภทส่ง zz ของ test เอง — PACK id 14 = 170 ไม่ใช่ 150), `BillGridDbTest` (ข้ามแถว detail ที่ไม่มีบิลแม่)

### bench (`bin/bench` n=10 c=1 1 รอบ, `artisan optimize`, mean ms — :8114 ใช้ร่วมกับทุกกลุ่ม (4 worker) และเครื่อง load ~7 ระหว่างวัด → ตัวเลขเดิมสูงกว่าปกติมาก)
| หน้า | เดิม | ใหม่ |
|---|---|---|
| `GET /payment` (ลูกค้า 348) | 2400 | 104 |
| `POST /_payment/grid` | 8087 | 124 |
| `POST /_payment/load_shipping_price` (VAT + คูปอง) | 3284 | 479 |
| `GET /admin/payment/12635` | 2746 | 92 |
| `POST /_admin/_payment/grid` | 1844 | 118 |
| `GET /admin/coupon` | 2028 | 85 |
| `POST /_admin/_coupon/grid` | 381 | 73 |

`load_shipping_price` ใหม่ ~350 ms (วัดเดี่ยว): `bill_payment_detail_temp` (130K แถว) ไม่มี index `random` → ตรวจเจ้าของ random + getShippingTypePrice + รวมค่าพัสดุ = สแกนทั้งตาราง 3 รอบ (เดิม 2 รอบ) — ข้อเสนอ: index `bill_payment_detail_temp (random)` (ยังไม่สร้าง — ห้ามแก้ schema)

### คำถามถึงเจ้าของ
1. ปุ่มลบคูปอง: เดิมลบประเภทสินค้าผิดตัวและคูปองไม่ถูกลบ — ใหม่ไม่ลบอะไร ควรให้ลบคูปอง (is_delete = 1 เฉพาะที่ยังไม่ถูกใช้, สิทธิ์ admin_coupon) หรือไม่
2. `save_vat` กับบิลที่ใช้คูปอง: ยอดใหม่ไม่หักคูปอง และตอนเปิด VAT ไม่รวมค่าส่งในไทย (เหมือนไฟล์เดิม) — ควรคิดแบบเดียวกับตอนลูกค้าจ่ายหรือไม่
3. ชื่อคูปองซ้ำได้ตอนเพิ่มใหม่ (เดิมตรวจเฉพาะตอนแก้) — ควรตรวจตอนเพิ่มด้วยหรือไม่
4. ลูกค้ายกเลิกบิลสถานะ 1 ยังหักแต้ม (calPoint '-') แม้ยังไม่เคยได้แต้ม — เหมือนคำถาม SR ข้อ 1
5. ลูกค้ายกเลิกรายการอลีเพลเองไม่ได้ (ไฟล์ถูก comment) — ยืนยันว่าตั้งใจ
6. `admin_change_sender` ใน PACK มี role 1 ถือ (ไม่ใช่สิทธิ์กำพร้า) — fallback ว่างตาม SR จึงเปลี่ยนผู้ส่งได้เฉพาะ role 1

## SR (2026-10-20)

branch `mod/payment` · worktree `/home/admin/laravel/sr-wt/payment` · dev `bin/wt-serve payment` → http://127.0.0.1:8304 · เทียบกับเว็บเดิม SR :8111 (admin_sr_bench)

### เปลี่ยนตาม SR
- **ไม่มี VAT** (SR ไม่มี bill_payment.is_vat/vat/v/vat_before/shipv/shipvat/shipvat_before): ตัดช่อง "ต้องการ VAT?", แถวสรุป VAT ในหน้าบิล, ป้าย VAT ใน grid, ปุ่ม "คิด VAT" ของแอดมิน, `PaymentTotals::vat()/bill()`; `load_shipping_price` คืน JSON ของ getShippingTypePrice ตรงๆ (ไม่มี data.html)
- **รหัสย่อย (tracking.subuser)**: ช่องค้นหา + คอลัมน์ใน `payment/create` / `_payment/load_create`, คอลัมน์ในหน้า `payment/create/{random}`, `payment/{id}`, `admin/payment/{id}`, `admin/payment/create/{user}/{random}`, ใต้รหัสลูกค้าใน `_admin/_payment/load_create`; รูปพัสดุ (tracking_image) ใน load_create; ไม่มีป้าย MALL
- `_payment/load_select_lot`: เงื่อนไขแบบ SR (ช่องค้นหา tracking/quote/รหัสย่อย/ถึงไทย, lot_id ≠ 0, ไม่กรอง tracking/no/header) — SQL เดิมผิดไวยากรณ์เมื่อกรอก tracking และรหัสย่อยพร้อมกัน (วงเล็บไม่มี AND) → ผลว่าง: ทำซ้ำแบบเดิม (`applyTextSearch()` คืน false) ใช้กับ load_create ด้วย
- `_admin/_payment/load_select_lot`: + header = 0 · `_admin/_payment/load_create`: ไม่มีตารางเรทด้านบน
- `_payment/save_submit_payment` (ลูกค้า): **ถาม PIN จ่ายเงิน** (`PinBox::guard`, jl comment ไว้) · **สร้างบิลได้ 1 ใบต่อ 20 วินาที** (ErrorBox "กรุณารอ N วินาที" — ตรวจภายใต้ lock แถว user, นับเวลาด้วย TIMESTAMPDIFF ของ MySQL) · ค่าเปลี่ยนผู้ส่งต่อรหัสลูกค้า (setting `charge_sender_edit`, `PaymentTotals::senderCharge()`; หน้า create_form แสดงค่าเดียวกัน) · bill_payment_detail คัดลอก `subuser`, `is_takeapic` (ไม่มี is_checked/checked_by)
- ประเภทการส่งในฟอร์มสร้างบิล (ลูกค้า/แอดมิน) `ORDER BY no = 0, no ASC`
- `_admin/_payment/save_submit_payment`: เปิด hack ปุ่ม 9/10 (บริษัทจัดส่ง/รับเอง) แบบ SR; detail มี subuser, is_takeapic = 0 (เดิมไม่ได้ SELECT มา = '' → 0)
- `_admin/_payment/save_approved`: **calPoint** (แต้ม = price_shipping / divide_point, balance_point type 1 prefix export) + `warehouse_section_detail.is_delete = 1` ของพัสดุในบิล — ใน transaction เดียวกับการเปลี่ยนสถานะ (lock แถวบิล; กดซ้ำ = แต้มครั้งเดียว)
- `_payment/save_delete` (ลูกค้า): calPoint(id, '-') ก่อนยกเลิก (หักแต้ม) ใน transaction เดียวกัน
- `_admin/_payment/save_delete`: สถานะ **0** (แอดมินสร้างแบบยังไม่จ่าย) ยกเลิก + คืนพัสดุได้ (ไม่มีเงินคืน/อีเมล); ไม่พบบิล = ข้อความสำเร็จแบบเดิม (null == 0) โดยไม่เขียนอะไร
- `admin/payment/{id}`: พิมพ์ `console.log` ราคาเทียบเรท user_type_detail ต่อแถวแบบ SR (ค่าใน JS escape; เรทดึงครั้งเดียว ไม่ N+1)
- อีเมล (approved/cancel/alipay success): แม่แบบ SR (header/center/footer ของ SR ก่อน hotfix — ไฟล์หลัง hotfix มี `<?php ... ?>` หัวไฟล์ที่ file_get_contents จะติดไปในอีเมล), ปุ่มสี #a3b2f1, ผู้ส่ง `SRShippingSystem <no-reply@srshippingsystem.com>` (ค่าคงที่ใน `PaymentMailer` — ตอน merge กับ mod/users ให้ใช้ `config('legacy.mail')` ตัวเดียวกัน), payment ไม่เรียก sendLine/push แล้ว (เป็น no-op อยู่ดี)
- `_payment/view_example`: print_header.php ของ SR (ไม่มี html/head/body + jQuery/addCommas) — คง favicon set
- **alipay**: `_alipay/save` ถาม PIN, ใช้เงินหยวน (balance_cn) ก่อน ส่วนที่ขาด × เรท ตัดจากบาท, เก็บ `bill_alipay.usecash_cn`, เขียน balance/balance_cn เฉพาะยอดที่ไม่เป็น 0 · `_alipay/cancel` คืนทั้งสองส่วน (เฉพาะยอด ≠ 0) · `_admin/_alipay/cancel` คืนทั้ง balance และ balance_cn เสมอ (ยอด 0 ก็เขียนแบบเดิม) · `_admin/_alipay/save` เฉพาะสถานะ 1 อื่นๆ `ErrorBox('รายการไม่พร้อมที่จะดำเนินการนี้', 'window.location.reload();')` (SR แก้ช่องโหว่ยืนยันบิลที่ยกเลิกแล้วเอง) · หน้า alipay แสดงเงินหยวน/ยอดประมาณ (บาท/เรท + หยวน)/หยวนที่ใช้ และแถว balance_cn (¥)
- `calPoint()` = `App\Support\Legacy\PointCalc::calPoint()` (commit `foundation:` เพราะ reports ใช้ด้วย: `_export/save_delete`, `_admin/_export/save_delete`) · `getPayType()` ใช้แค่ mall (SR ไม่มี) → ไม่พอร์ต

### SR-only ที่พอร์ต
- `admin/receipt`, `admin/receipt/status/{n}` (`_admin/_receipt/index.php`, สิทธิ์ `admin_receipt` — role 1 เท่านั้นบน bench) + `POST /_admin/_receipt/grid` (ไม่มีสิทธิ์ = ว่าง): bill_payment ที่ receipt_id ≠ '' ค้นหา id/receipt_id/รหัสลูกค้า เรียง receipt_id DESC; ลิงก์ไป `admin/export/receipt/{id}` และ `_admin/_export/print_receipt/?id=` (กลุ่ม reports)
- `admin/receipt/{id}`, `admin/receipt/create` → `_admin/_receipt/form.php` ไม่มีในเว็บเดิม = dead → ไม่มี route (404 → หน้าแรก)

### ลบ (jl-only)
`_payment/load_vat`, `_payment/load_receipt_address`, `_payment/save_create_receiptaddress`, `_payment/save_deleteaddress`, `_payment/save_deletereceiptaddress`, `_admin/_payment/save_vat` (route + method + view receipt_address + test)

### ต่างจาก SR เดิมโดยตั้งใจ (นอกจากที่ jl port ทำไว้แล้ว)
- กันแจกแต้มซ้ำ (calpoint-dedupe-guard-v1) นับเฉพาะ prefix export (เดิมรวมทุก prefix type 1 ที่ prefix_ref_id เดียวกัน — แถวแลกแต้ม withdrawpoint ติดลบทำให้แจกซ้ำได้)
- alipay: ยอดที่ไม่ใช่ตัวเลข → ErrorBox (เดิมเทียบ string แล้วคำนวณผิด); ยอดหยวนติดลบถือเป็น 0 (เดิม use_balance ติดลบ → แถว balance_cn `'--x'` ที่ MySQL เก็บเป็น 0 แต่ส่วนบาทถูกบวกเพิ่ม = ลูกค้าเสียเงิน)
- อีเมลล้มเหลว: log อย่างเดียว (เดิม `echo $e` ติดไปในคำตอบ ajax)
- PIN ผิด: PinThrottle เหมือนกลุ่มอื่น; `goLog` ของเดิมบันทึก PIN ที่พิมพ์ผิดเป็น plaintext — ไม่บันทึก
- bill_payment_detail ลูกค้า: INSERT … SELECT คอลัมน์ระบุชัด (เดิม VALUES ต่อ string: subuser ไม่ผ่าน nStr → มี `'` = SQL error แถว detail หาย)
- หน้าไม่มีสิทธิ์ `admin/receipt`: ไม่มีหัวหน้า (breadcrumb) ก่อน ErrorBox — แบบเดียวกับทุกหน้าที่ใช้ `legacy.perm`
- ที่เหลือคงตาม jl port: escape `<"'&`, ไม่มี target=_blank, random/บิลของคนอื่น = ว่าง, ไม่มี key ตัวเลขซ้ำใน JSON ของ load_shipping_price

### ช่องโหว่เดิมที่เจอใน SR
- `_payment/save_delete` เรียก calPoint ด้วย id ที่ส่งมา **ก่อน** ตรวจเจ้าของ/สถานะ → ลูกค้าส่ง id บิลของคนอื่นแล้วหักแต้มของเจ้าของบิลนั้นได้ (ใหม่: เฉพาะบิลตัวเองสถานะ 1)
- `_payment/save_submit_payment`: เช็ค 20 วินาทีนอก lock (ส่งพร้อมกันผ่านได้) — ใหม่อยู่ใน lock + `legacy.once`
- `goLog` เก็บ PIN จ่ายเงินที่ผิดเป็น plaintext ใน log
- hotfix 2026-10-18 ใส่บรรทัด `<?php ... ?>` หัว email_header/center/footer.php → อีเมลจริงตอนนี้มีโค้ด PHP ติดไปด้วย (ฝั่งใหม่ใช้เนื้อหาก่อน hotfix)

### การตรวจ (เทียบ :8111 ด้วย session เดียวกัน, normalize host/ช่องว่าง/target=_blank)
- เท่ากันหลัง normalize (ต่างเฉพาะ escape / favicon / dashboard.js ของ layout): `/payment` (+status), `_payment/grid` หน้า 1–2 + สถานะ + ค้นหา, `payment/create`, `_payment/load_select_lot` + `load_create` (11 แบบรวม tracking+รหัสย่อย = ว่าง), `load_create_sum`, `payment/create/{random}`, `load_shipping_price` (4 แบบ), `view_example` (sender 0/1), `payment/{id}` (4 บิลจริง + บิล zz), `admin/payment` (+status), `_admin/_payment/grid` (หน้า 1–3, ค้นหารหัส/PAY-id), `admin/payment/{id}` (4 บิลจริง รวม 44 พัสดุ + 3 บิล zz รวม console.log), `admin/payment/create[/{u}]`, `admin/payment/create/{u}/{random}`, `_admin/_payment/load_create`/`load_select_lot` (2 ลูกค้า × 4 แบบ), `load_autocomplete_user`, `load_user_by_id`, `_admin/_payment/load_shipping_price`, `/alipay`(+status), `alipay/create`, `alipay/{id}`, `_alipay/grid`, `admin/alipay`(+status), `admin/alipay/create`, `admin/alipay/{id}`, `_admin/_alipay/grid`, `admin/receipt`(+status), `_admin/_receipt/grid` (6 แบบ ด้วยใบเสร็จ zz), หน้าไม่มีสิทธิ์ role 12 (`admin/receipt`)
- E2E (Laravel :8304 เท่านั้น, ผู้ใช้ 11937/11938 + tracking zz_pay_*): ไม่มี PIN/PIN ว่าง/ผิด/ถูก + กดซ้ำ (บิลเดียว, ตัดเงิน 507.69 = storage 50 + ค่าผู้ส่ง 30 + 427.69), ลูกค้ายกเลิก (คนอื่นยกเลิก = ไม่มีผล, ยกเลิกซ้อน = คืนครั้งเดียว, แต้ม -2.85), แอดมินสร้างบิล pay_now + ปุ่ม 9 (444.36), approve ×2 พร้อมกัน (แต้ม 2.63 ครั้งเดียว, ชั้นวางถูกลบ, อีเมล 1 ฉบับจาก SRShippingSystem), ยกเลิกบิลสถานะ 2 (ปฏิเสธ), บิลสถานะ 0 → ยกเลิก (คืนพัสดุ ไม่มีเงิน), alipay 50¥ (หยวน 20 + บาท 156), กดซ้ำ, `1,00`/`abc` ปฏิเสธ, ยกเลิก (คืนทั้งสอง), alipay 10¥ (หยวนล้วน) → approve → approve ซ้ำ (ไม่พร้อม) → cancel สถานะ 2 (ปฏิเสธ) → rollback → cancel (คืน 0 บาท + 10¥) — ตรงกับค่าที่อ่านจากโค้ดเดิมทุกแถว · ลบแถวที่สร้างทั้งหมดตาม id แล้ว (tracking 1410–1413, tracking_image 6046–6047, balance 1293–1296/1383–1385, balance_cn 106–110, balance_point 199–200, bill_payment 322–324 + detail, temp 499–501, temp_admin 1184–1186, bill_alipay 1–2, warehouse_section_detail 29, ips 91) และคืน pin ของ 11937 เป็นค่าว่าง
- test: `tests/Feature/Payment/PaymentSrDbTest.php` (9 test), `PaymentRoutesTest` (route SR + jl-only/dead ไม่มี route), ปรับ `BillGridDbTest` (แยก grid ของ reports), `LegacyRisksDbTest` (ประเภทส่งของ SR: 43/14), `GridPerfDbTest` (หน้าลึก/ประเภทตามข้อมูล SR)

### bench (`bin/bench` n=20 c=1, `artisan optimize`, mean ms)
| หน้า | เดิม | ใหม่ |
|---|---|---|
| `GET /payment` (ผู้ใช้ 2) | 368 | 43 |
| `POST /_payment/grid` | 52 | 37 |
| `POST /_payment/load_create` (17 พัสดุ) | 80 | 54 |
| `GET /admin/payment` | 441 | 41 |
| `POST /_admin/_payment/grid` | 39 | 35 |
| `GET /admin/payment/32` (44 พัสดุ) | 733 | 69 |
| `POST /_admin/_receipt/grid` (2 แถว) | 27 | 31 |
| `GET /alipay/create` | 387 | 45 |

`_admin/_receipt/grid` ช้ากว่า ~4 ms = overhead คงที่ของ Laravel (ตารางเล็ก query เดียว)

### คำถามถึงเจ้าของ
1. ลูกค้ายกเลิกบิลสถานะ 1 (ยังไม่อนุมัติ = ยังไม่เคยได้แต้ม) แต่ SR เดิมหักแต้ม (calPoint '-') — บน bench มี 5 บิลแบบนี้ (แต้มติดลบ) · ใหม่ทำตามเดิม: ควรหักเฉพาะเมื่อเคยได้แต้มหรือไม่
2. `_export/save_delete` / `_admin/_export/save_delete` (reports) ต้องเรียก `PointCalc::calPoint($id, '-', ...)` เช่นกัน — ตอนนี้ใน Laravel ยังเป็น comment "ฟังก์ชันว่าง" (งานของ mod/reports)
3. print_header ของ order/reports (`order/print_header.blade.php`, `reports/partials/print_header.blade.php`) ยังเป็นของ jl — SR ต่าง (ดู view_example)
4. ผู้ส่งอีเมลเป็นค่าคงที่ใน PaymentMailer จนกว่า config('legacy.mail') ของ mod/users จะ merge


branch `mod/payment` · worktree `/home/admin/laravel/jl-wt/payment` · dev server `bin/wt-serve payment` → http://127.0.0.1:8204

โมดูลเดิม: `_payment`, `_admin/_payment`, `_alipay`, `_admin/_alipay` และกล่อง PIN `getPinBox()` / `getNewPinBox()` (system_status_function.php)

| ส่วน | ไฟล์ |
|---|---|
| routes | `routes/modules/payment.php` |
| controller | `app/Http/Controllers/Payment/{PaymentController,AlipayController}.php`, `Payment/Admin/{AdminPaymentController,AdminPaymentCreateController,AdminAlipayController}.php`, `Payment/Concerns/ReadsLegacyInput.php` |
| logic ร่วมในกลุ่ม | `app/Domain/Payment/` — `PaymentLookups` (ข้อมูลประกอบต่อแถวแบบ batch, prefix cache, MakePaging, ค่าแบบ mysqli), `PaymentTotals` (ยอดรวม/VAT ตามลำดับการบวกเดิม), `PinBox`, `PaymentMailer`, `TempUpload`, `UploadBox` (สำเนาจาก tracking), `Php74` (สำเนาจาก tracking) |
| view | `resources/views/payment/{customer,admin,alipay,admin_alipay,partials}/` |
| test | `tests/Feature/Payment/PaymentRoutesTest.php` |

## 1. Routes ที่พอร์ต

หน้า (RewriteRule) — ทุกหน้ามี `->defaults('legacy_page', ...)`

| URL เดิม | page เดิม | route | สิทธิ์ |
|---|---|---|---|
| `payment` | payment | `GET /payment` | `legacy.perm:payment` |
| `payment/status/([0-9]+)` | payment | `GET /payment/status/{status}` | `legacy.perm:payment` |
| `payment/create` | payment_create | `GET /payment/create` | `legacy.perm:payment` |
| `payment/create/(.+)` | payment_detail_form | `GET /payment/create/{random}` | `legacy.perm:payment` |
| `payment/([0-9]+)` | payment_detail | `GET /payment/{id}` | `legacy.perm:payment` (บิลของตัวเองเท่านั้น) |
| `alipay` | alipay | `GET /alipay` | `legacy.perm:alipay` |
| `alipay/status/([0-9]+)` | alipay | `GET /alipay/status/{status}` | `legacy.perm:alipay` |
| `alipay/create` | alipay_detail (id=0) | `GET /alipay/create` | `legacy.perm:alipay` |
| `alipay/([0-9]+)` | alipay_detail | `GET /alipay/{id}` | `legacy.perm:alipay` (ของตัวเอง) |
| `admin/payment` | admin_payment | `GET /admin/payment` | `legacy.perm:admin_payment` |
| `admin/payment/status/([0-9]+)` | admin_payment | `GET /admin/payment/status/{status}` | `legacy.perm:admin_payment` |
| `admin/payment/([0-9]+)` | admin_payment_detail | `GET /admin/payment/{id}` | `legacy.perm:admin_payment` |
| `admin/payment/create` | admin_payment_create | `GET /admin/payment/create` | `legacy.perm:admin_payment_create` |
| `admin/payment/create/([0-9]+)` | admin_payment_create | `GET /admin/payment/create/{user_id}` | `legacy.perm:admin_payment_create` |
| `admin/payment/create/([0-9]+)/(.+)` | admin_payment_create_form | `GET /admin/payment/create/{user_id}/{random}` | `legacy.perm:admin_payment_create` |
| `admin/alipay` | admin_alipay | `GET /admin/alipay` | `legacy.perm:admin_alipay` |
| `admin/alipay/create` | admin_alipay_detail (id=0) | `GET /admin/alipay/create` | `legacy.perm:admin_alipay` |
| `admin/alipay/status/([0-9]+)` | admin_alipay | `GET /admin/alipay/status/{status}` | `legacy.perm:admin_alipay` |
| `admin/alipay/([0-9]+)` | admin_alipay_detail | `GET /admin/alipay/{id}` | `legacy.perm:admin_alipay` |

ajax / form post (ทุกตัว `legacy.ajax` = system_check_ajax เดิม ยกเว้น view_example/email_*, CSRF = `LegacyCsrf`)

| ไฟล์เดิม | route | สิทธิ์ (เดิม → ใหม่) |
|---|---|---|
| `_payment/grid.php` | `POST /_payment/grid` | payment (ไม่มีสิทธิ์ = ตอบว่าง ตรวจใน controller) |
| `_payment/load_create.php` | `POST /_payment/load_create` | payment |
| `_payment/load_create_sum.php` | `POST /_payment/load_create_sum` | payment |
| `_payment/load_select_lot.php` | `POST /_payment/load_select_lot` | payment |
| `_payment/load_shipping_price.php` | `POST /_payment/load_shipping_price` | **ไม่มี** → payment + random ของตัวเอง |
| `_payment/load_vat.php` | `POST /_payment/load_vat` | **ไม่มี** → payment (ไฟล์เดิมว่าง ตอบว่าง) |
| `_payment/save_create.php` | `POST /_payment/save_create` | payment |
| `_payment/save_submit_payment.php` | `POST /_payment/save_submit_payment` | payment |
| `_payment/save_delete.php` | `POST /_payment/save_delete` | payment |
| `_payment/save.php` (สำเนา _refill/save) | `POST /_payment/save` | refill |
| `_payment/load_receipt_address.php` | `POST /_payment/load_receipt_address` | payment (ตารางไม่มี → ผลว่างแบบเดิมโดยไม่ query) |
| `_payment/save_create_receiptaddress.php` | `POST /_payment/save_create_receiptaddress` | payment (ตรวจค่าแบบเดิม, ไม่เขียน DB เพราะตารางไม่มี) |
| `_payment/save_deleteaddress.php` | `POST /_payment/save_deleteaddress` | payment (ตาราง user_address ไม่มี) |
| `_payment/save_deletereceiptaddress.php` | `POST /_payment/save_deletereceiptaddress` | payment |
| `_payment/view_example.php` | `GET\|POST /_payment/view_example` | payment (ไม่มีสิทธิ์ = "ERROR : access denied") |
| `_payment/create.php`, `create_form.php`, `form.php`, `index.php` | (หน้า) | — |
| `_admin/_payment/grid.php` | `POST /_admin/_payment/grid` | admin_payment (ตอบว่าง) |
| `_admin/_payment/save_approved.php` | `POST /_admin/_payment/save_approved` | admin_payment |
| `_admin/_payment/save_delete.php` | `POST /_admin/_payment/save_delete` | admin_payment |
| `_admin/_payment/save_deletenopush.php` | `POST /_admin/_payment/save_deletenopush` | admin_payment |
| `_admin/_payment/save_vat.php` | `POST /_admin/_payment/save_vat` | admin_payment |
| `_admin/_payment/load_shipping_price.php` | `POST /_admin/_payment/load_shipping_price` | admin_payment |
| `_admin/_payment/save_new_sender.php` | `POST /_admin/_payment/save_new_sender` | admin_change_sender (ไม่มีใน role_detail = ไม่มีใครใช้ได้ เหมือนเดิม) |
| `_admin/_payment/email_approved.php`, `email_cancel.php` | `GET\|POST` (ตอบว่าง) | **ไม่มี** → admin_payment |
| `_admin/_payment/load_autocomplete_user.php` | `POST …/load_autocomplete_user` | admin_payment_create |
| `_admin/_payment/load_user_by_id.php` | `POST …/load_user_by_id` | admin_payment_create |
| `_admin/_payment/load_create.php` | `POST …/load_create` | admin_payment_create |
| `_admin/_payment/load_select_lot.php` | `POST …/load_select_lot` | admin_payment_create (ข้อความแจ้งใช้ชื่อ admin_payment_createpayment แบบเดิม) |
| `_admin/_payment/save_create.php` | `POST …/save_create` | admin_payment_create |
| `_admin/_payment/save_submit_payment.php` | `POST …/save_submit_payment` | **ไม่มีเลย** → admin_payment_create |
| `_alipay/grid.php` | `POST /_alipay/grid` | alipay (ตอบว่าง) |
| `_alipay/save.php` | `POST /_alipay/save` | alipay |
| `_alipay/cancel.php` | `POST /_alipay/cancel` | alipay |
| `_admin/_alipay/grid.php` | `POST /_admin/_alipay/grid` | admin_alipay (ตอบว่าง) |
| `_admin/_alipay/save.php`, `cancel.php`, `rollback.php` | `POST /_admin/_alipay/…` | admin_alipay |
| `_admin/_alipay/email_cancel.php`, `email_success.php` | `GET\|POST` (ตอบว่าง) | **ไม่มี** → admin_alipay |

หมายเหตุ url-map: `_admin/_payment/load_select_lot.php` จริงๆ ตรวจ `getPermission('admin_payment_create')` (เฉพาะข้อความแจ้งเตือนที่ใช้ชื่อ `admin_payment_createpayment`) → ผู้มีสิทธิ์ admin_payment_create ใช้ได้

## 2. ต่างจากเดิมโดยตั้งใจ

- **กันกดซ้ำ/ส่งซ้อน (double submit)** (เจ้าของอนุมัติ 2026-10-18, E2E: ดับเบิลคลิก `_taobao/save` = 2 รายการ ตัดเงิน 2 ครั้ง — เว็บเดิมก็เป็น): middleware `legacy.once` (`App\Support\SubmitGuard`) — lock ต่อ ผู้ใช้+route ระหว่างประมวลผล (คำขอที่ซ้อนได้ `ErrorBox('รายการนี้กำลังดำเนินการ กรุณารอสักครู่')`) + payload เดิมที่สำเร็จแล้วภายใน 8 วินาทีได้ `ErrorBox('รายการนี้ถูกส่งไปแล้ว (ส่งซ้ำ) ...')` (คำตอบ error/กล่อง PIN ไม่ถูกจำ ส่งใหม่ได้ทันที): `_payment/save` (แจ้งเติมเงิน), `_payment/save_create`, `_payment/save_submit_payment`, `_payment/save_create_receiptaddress`, `_admin/_payment/save_create`, `_admin/_payment/save_submit_payment`, `_alipay/save`, `_export/save_approved` (กลุ่ม reports — ยืนยันบิล+ตัดเงิน) · test: `tests/Feature/SubmitGuardTest.php`, `tests/Feature/Money/DoubleSubmitDbTest.php` (JL_BENCH_DB_TESTS=1)
  - ชั้นนี้กันรายการซ้ำ; การกันยอดติดลบเมื่อคำขอต่างกันมาพร้อมกันยังเป็น transaction + `SELECT … FOR UPDATE` แถว user แล้วตรวจยอดภายใน (ทุก endpoint ตัดเงินตอนสร้าง)
- `_alipay/save`, `_payment/save`: ยอดที่มี `,` แบบกำกวม (`1,00`) → `ErrorBox` (เดิมกลายเป็น 100)
- ลูกค้าเห็น/ใช้ได้เฉพาะของตัวเอง: `save_create` เก็บเฉพาะ tracking ของตัวเอง; `payment/create/{random}`, `load_shipping_price`, `save_submit_payment`, `view_example` ใช้ random ที่ตัวเองสร้าง + `tracking.user = ผู้ใช้` (เดิมรู้ random = ดู/จ่าย tracking ของคนอื่นได้)
- `_payment/save_delete`: ยกเลิก+คืนเงินได้เฉพาะบิลของตัวเองสถานะ 1 (= ปุ่มในหน้าเดิม) — เดิมทุกสถานะ ≠ -1 (สถานะ 0 ยังไม่ตัดเงินก็คืนได้, 2/3 ส่งของแล้วก็คืนได้) และ id ของคนอื่นได้แถว balance 0 บาท
- `_alipay/cancel`: เฉพาะบิลของตัวเองสถานะ 1 (ดูช่องโหว่ข้อ 1)
- กันกดซ้ำ: `save_submit_payment` (ลูกค้า/แอดมิน) ห่อ transaction + `SELECT … FOR UPDATE` แถว user แล้วอ่าน tracking ที่ยังไม่ผูกบิลภายในล็อก; ถ้าไม่เหลือ tracking → ตอบ "รายการนี้ถูกดำเนินการไปแล้ว" โดยไม่สร้างบิล (เดิมตั้งใจทำแบบนี้แต่ UPDATE อ้าง `tracking.random` ที่ไม่มี → `affected_rows = -1` ≠ 0 → ไม่เคยทำงาน: กดซ้ำ = บิลเปล่า + ตัดค่าส่งซ้ำ)
- งานย้ายเงิน/สถานะทุกตัว (approve, cancel, deletenopush, save_vat, save_new_sender, alipay save/cancel/rollback) อยู่ใน transaction + lock แถวบิล → กดพร้อมกันไม่คืนเงินซ้ำ
- `bill_payment_detail` ลูกค้า: `INSERT … SELECT <คอลัมน์ระบุชัด> FROM tracking` แทน `SELECT *` (ผลเท่ากัน: id = tracking.id, tracking_id = AUTO_INCREMENT)
- header_count: เดิม subquery `t2.header = tracking.id` (header เป็น TEXT เทียบ int → ใช้ index ไม่ได้ = สแกนทั้งตารางต่อแถว: `_payment/load_create` ของ user 137 ใช้ **46 วินาที**) → นับครั้งเดียวด้วย `header IN ('id',…)` = **0.13 วินาที** (ค่า header ใน DB เป็นตัวเลขล้วน ผลเท่ากัน)
- N+1 ที่แก้: เลขออเดอร์ต่อแถว (bill_product_on_tracking) + tracking ลูกต่อแถว + header_count → query คงที่ต่อหน้า (load_create, create_form, form ลูกค้า/แอดมิน, load_create แอดมิน), เรท user_type_detail ของแอดมิน, getPrefix ต่อแถว
- ค่าจาก DB/ผู้ใช้ escape ทั้งหมด (เดิม stored/reflected XSS) — ต่างจากเดิมเฉพาะ `&`/`'`/`<` ในชื่อขนส่ง, quote, ชื่อ ฯลฯ; ค่า `page` ที่ฝังใน JS ของ grid กรองเหลือ `[0-9A-Za-z.-]`
- `load_shipping_price` JSON: ไม่มี key ตัวเลขซ้ำ ("0":…,"1":…) ของ mysqli_fetch_array (JS ใช้แค่ชื่อคอลัมน์) — ค่าที่เหลือเท่ากันทุกตัว (ตรวจ 14 กรณี)
- อีเมล: `PaymentMailer` ใช้ Mail ของ Laravel (dev = log ไม่ส่งจริง), LINE/push = no-op เหมือนเดิม (ฟังก์ชันเดิมถูก comment); ค่าจาก DB ในอีเมล escape; `email_*.php` ที่เปิดตรงได้ → ตอบว่าง (เดิมใช้ `$email_id` ที่ไม่ได้กำหนด)
- `_admin/_alipay/cancel` ยังตอบ JS เรียก `email_cancel` แบบเดิม (ซึ่งเดิมไม่เคยส่งได้จริง → ใหม่ตอบว่าง)
- ไฟล์แนบ: ชื่อไฟล์ผ่าน whitelist ก่อนตรวจ `upload/temp` (เดิม `file_exists('../upload/temp/'.$x)`), ตอบ JS `_api/blockblob_ajax` แบบเดิม — ไม่เรียก Azure/API ภายนอกจากฝั่งนี้
- PIN: `App\Domain\Payment\PinBox::getPinBox()/getNewPinBox()` คืน JS **เท่าเดิมทุก byte** (เทียบกับฟังก์ชันเดิมด้วย php7.4) + `PinBox::guard()` สำหรับกลุ่มอื่น (balance/point/taobao/order/transfer/users) เทียบ PIN ด้วย `hash_equals`, PIN ผิดนับใน ips ผ่าน `Alerts::error` เหมือนเดิม; ใน payment/alipay เดิม comment การถาม PIN ไว้ → ไม่ถาม PIN เหมือนเดิม
- SameSite / gateway callback: กลุ่มนี้ไม่มี callback จาก payment gateway (Alipay = คำขอให้พนักงานเติมเงินให้ ไม่มี server-to-server/redirect กลับ) ทุก POST เป็น XHR same-origin → cookie `PHPSESSID` SameSite=Lax ของฐานร่วมใช้ได้ ไม่ต้องแก้
- หน้าไม่มีสิทธิ์/ไม่พบบิล: layout เต็ม + ErrorBox (เดิม exit กลางหน้า ไม่มีท้ายหน้า)

แก้เพิ่มหลัง review (adversarial review):
- `_admin/_alipay/save`: เดิม UPDATE ทุกสถานะ (ปุ่มบันทึกแสดงทุกสถานะ) → ยืนยันบิลที่ยกเลิก/คืนเงินแล้วเป็นสถานะ 2 → `rollback` (2 → 1 ไม่ตัดเงิน) → ลูกค้ากดยกเลิก = **คืนเงินซ้ำ** (ทดสอบบน :8204 ด้วยบิล zz: ได้ +52 จากบิลเดียว) — ตอนนี้ไม่ยอมบิลสถานะ -1/ไม่มีอยู่ (`ErrorBox('รายการนี้ถูกยกเลิกไปแล้ว')`) และอยู่ใน transaction + lock แถวบิล
- `_admin/_payment/save_vat`: เดิมเปลี่ยน VAT บิลที่ยกเลิกแล้วได้ → แถวตัดเงินเปลี่ยนตามยอดใหม่ แต่แถวคืนเงินยังเป็นยอดเดิม = ยอดลูกค้าเพิ่ม/ลดโดยไม่มีที่มา (ทดสอบ: คืน 260.50, ตัดเหลือ -166.58) → บิลสถานะ -1 ไม่ทำอะไร
- `admin/payment/create/{user_id}` ช่อง "ยอดเงินลูกค้า": เดิม `$user_id` มาจาก `extract($_GET)` = ยอดเงินของลูกค้าใน URL (รายงานรอบแรกเข้าใจว่าเป็นยอดของแอดมิน) → แก้ให้ตรงเดิม
- ลูกค้า `save_submit_payment`: ค่าส่งตามน้ำหนัก (`getShippingTypePrice`) คิดจาก random ของรายการที่จ่ายจริง (`id`) แทน `ref_id` ที่ส่งแยกมา (หน้าเดิมส่งค่าเดียวกัน; ส่ง ref_id ของ random อื่นที่เบากว่า = ค่าส่งถูกลง)
- id จาก POST ที่ฝังในข้อความ `SuccessBox(...)` ผ่าน `Alerts::js(e())` (เดิมไม่ escape); แถว balance/อีเมลใช้ id ของบิลที่อ่านได้จริง (เดิมใช้ค่าที่ส่งมา เช่น `5abc`)

แก้ตามที่เจ้าของอนุมัติ (2026-10-18, รายงานข้อ 13 — เฉพาะฝั่ง Laravel):
- ราคาติดลบตอนแอดมินสร้างบิล: เดิม `prices` ใน `admin/payment/create` ติดลบได้ → บิลยอดติดลบ + pay_now = เพิ่มเงินให้ลูกค้า
  ใหม่: `_admin/_payment/save_create` **บล็อกการบันทึก** ถ้าราคาช่องใดติดลบ (อ่านแบบตัวเลข 7.4 หลังตัด `,`) ตอบ `ErrorBox('ราคาไม่ถูกต้อง (ห้ามติดลบ) …', 'CloseLoading()')`;
  `save_submit_payment` ตรวจซ้ำ (temp ที่ค้าง/ยิงตรง): ราคารายการหรือยอดรวมติดลบ = ไม่สร้างบิล ไม่ตัด/เพิ่มเงิน
- `getShippingTypePrice_EditSender` (`Pricing::getShippingTypePriceEditSender`, ฐานร่วม — commit `foundation:`): เดิม join `t.id = bill_payment_detail.tracking_id`
  แต่ `tracking_id` คือ PK (AUTO_INCREMENT) ของ bill_payment_detail ส่วน tracking.id อยู่ในคอลัมน์ `id` (ตรวจกับ DB จริงแบบอ่านอย่างเดียว: 500/500 แถวตรงกันที่ `id`, 0/297 ที่ `tracking_id`)
  → ค่าส่งแบบ rate / VAT ใน `save_vat`, `load_new_sender` คิดจากน้ำหนักของ tracking ผิดแถว — ใหม่ join `t.id = t2.id`
- `_payment/save` (สำเนา `_refill/save`): ยอดต้องเป็นตัวเลขบวก (ดู money-move)
- test: `tests/Feature/Money/PaymentDbTest.php` (admin_jl_bench + rollback, opt-in `JL_BENCH_DB_TESTS=1`)

บิลเดียวกันในกลุ่ม reports (`_export/*`, `_admin/_export/*` — ยังไม่มี `docs/modules/reports.md` จึงบันทึกไว้ที่นี่; รายงานข้อ 15, เจ้าของอนุมัติ 2026-10-18):
- `_export/save_delete` (ลูกค้า): เดิมยกเลิก+คืนเงินบิลทุกสถานะ ≠ -1 (ก่อนรอบนี้ Laravel ยอม 0/1) → เฉพาะบิลของตัวเองสถานะ **0** (= ปุ่มยกเลิกในหน้า `export/{id}`) ล็อก user → บิล,
  คืนเงินเท่ายอดสุทธิที่บิลถูกตัดอยู่จริง (สถานะ 0 ปกติ = ไม่มีเงินคืน), สถานะอื่น = `ErrorBox`; บิลสถานะ 1 ยกเลิก+คืนเงินที่ `_payment/save_delete` ตามเดิม
- `_admin/_export/save_approved`: เดิมรับทุกสถานะ (บิลที่ยกเลิก/คืนเงินแล้ว หรือยังไม่จ่าย ถูกพลิกเป็น "ส่งแล้ว" + ตัด/คืนค่าส่งในประเทศ) → เฉพาะ **2** (รอส่ง)
  และ 3 (ส่งแล้ว = แก้ข้อมูลการส่ง; ปุ่มบันทึกแสดงทุกสถานะ และแถวค่าส่ง type 4 เดิมถูก `is_delete` แล้วเขียนใหม่ จึงไม่ตัดซ้ำ) ล็อกบิล + user; สถานะอื่น = `ErrorBox`
- test: `tests/Feature/Money/ReportsDbTest.php`

คงพฤติกรรมเดิม (ควรให้เจ้าของตัดสินใจ):
- `_admin/_alipay/grid` ลำดับ `AND … OR user.code LIKE …` ทำให้กิ่งค้นหารหัสลูกค้าไม่กรอง is_delete
- รหัสผ่าน taobao/alipay ของลูกค้าเก็บ plaintext ใน `bill_alipay` และแสดงในหน้าแอดมิน (ระบบเดิมออกแบบให้พนักงานล็อกอินแทน)
- `admin_change_sender` ไม่มีใน role_detail → ฟอร์มแก้ผู้ส่ง/`save_new_sender` ไม่มีใครใช้ได้ (พอร์ตครบแต่ทดสอบผ่าน HTTP ไม่ได้)
- `save_new_sender` เดิมเรียก `getShippingTypePrice` ด้วยตัวแปรว่าง → ใช้ค่าจาก shipping_type ที่เลือกเท่านั้น (คงไว้)

## 3. ช่องโหว่เดิมที่เจอ (แก้แล้วในฝั่งใหม่)

1. **ขโมยเงินผ่าน `_alipay/cancel.php`** (ร้ายแรง): อ่านบิลด้วย id โดยไม่กรองเจ้าของ แล้ว INSERT balance คืน `usecash` ของบิลนั้นให้ "ผู้ที่กด" — ลูกค้าคนไหนก็ส่ง id บิลของคนอื่นแล้วได้เงินเข้ากระเป๋า กดซ้ำได้ไม่จำกัด (บิลคนอื่นไม่ถูกเปลี่ยนสถานะ) และยกเลิกบิลตัวเองที่เติมให้แล้ว (สถานะ 2) เพื่อรับเงินคืนได้
2. **`_admin/_payment/save_submit_payment.php` ไม่ตรวจสิทธิ์/ล็อกอิน**: ใครก็ได้ที่ส่ง header X-Requested-With สร้างบิลให้ลูกค้าคนไหนก็ได้ และ `pay_now=1` ตัดเงินลูกค้าตาม `user_id` ที่ส่งมา
3. `_payment/save_delete.php`: ยกเลิก+คืนเงินบิลสถานะ 0 (ไม่เคยตัดเงิน) / 2 / 3 ได้ = ได้เงินฟรี
4. กดซ้ำ/ส่งพร้อมกัน: ตัดเงินซ้ำ (save_submit_payment), คืนเงินซ้ำ (cancel ทุกตัวไม่มี lock)
5. IDOR ผ่าน random ของ `bill_payment_detail_temp` (create_form, view_example, load_shipping_price, save_submit_payment) และ `save_create` รับ tracking id ของคนอื่น
6. SQL injection ทุกไฟล์ (POST/GET ต่อ string ตรง; GET ผ่าน addslashes แต่ POST ไม่)
7. XSS: ค่า search ใน grid, ทุกพารามิเตอร์ของ view_example (reflected), quote/remark/ที่อยู่/ชื่อขนส่ง/cancel_remark (stored), `$Page` ใน JS
8. ไม่มี CSRF (เดิม) — ตอนนี้ผ่าน `LegacyCsrf`
9. `load_shipping_price`, `load_vat`, `email_*.php` ไม่ตรวจล็อกอิน; email_* เปิดตรงได้
10. `file_exists('../upload/temp/'.$_POST[...])` = ใช้ตรวจว่ามีไฟล์ใดบนเครื่อง (path traversal)
11. `_admin/_alipay/grid.php` ตรวจ `getPermission('alipay')` (สิทธิ์ลูกค้า) แทน `admin_alipay` → ลูกค้าทุกคนเรียกดูรายการเติมอลีเพลของลูกค้าทุกคน (รหัสลูกค้า ยอดเงิน สถานะ) ได้ (ยืนยันแล้วด้วย GET/POST ปกติบน :8101 ด้วย session ลูกค้า 137) — ใหม่ใช้ `admin_alipay`
12. `_admin/_alipay/save.php` ยืนยันบิลที่ยกเลิกแล้ว + `rollback` = คืนเงินซ้ำ (ดูข้อ "แก้เพิ่มหลัง review")
13. `_admin/_payment/save_vat.php` แก้ VAT บิลที่ยกเลิกแล้ว = ยอดเงินลูกค้าผิด
14. `extract($_GET)` ใน system_config.php: ตัวแปรที่ไม่ได้กำหนดในไฟล์ ajax ถูกกำหนดจาก query string ได้ เช่น `save_vat.php?price_thai_storage=…`, `save_new_sender.php?shipping_type=…`, และ `email_approved.php?email_id=…` / `email_cancel.php?email_id=…` (ไม่ต้องล็อกอิน) สั่งส่งอีเมลของบิลใดก็ได้ — ใหม่ไม่อ่านตัวแปรจาก GET

## 4. การตรวจสอบ

- เทียบ HTML/JS กับเว็บเดิม (:8101) ด้วย cookie เดียวกัน (normalize ช่องว่าง + href): ลูกค้า 137 / 6799 / zz_fdn_cust, แอดมิน zz_fdn_admin — 40+ หน้า/ajax **เท่ากัน** ต่างเฉพาะการ escape (`&amp;`, `&#039;`, `&lt;`) และหน้า "ไม่มีสิทธิ์" ที่ใหม่มี layout เต็ม
- ยอดเงินที่เขียนลง DB เทียบกับสูตรเดิมที่รันด้วย php7.4 + `system_config.php` เดิมบนข้อมูลเดียวกัน: บิลลูกค้า (VAT/ผู้ส่งเอง: total 193.15, vat 198.72, vat_before 13.00, shipvat_before 5.57), บิลแอดมิน (289.75), save_vat เปิด/ปิด (192.76 / 285.72) — ตรงทุกค่า
- flow ที่ทดสอบผ่าน HTTP จริงบน :8204 (ข้อมูล zz_ แล้วลบทิ้ง): สร้างบิล → กดซ้ำ (ไม่ตัดซ้ำ) → ลูกค้าคนอื่นยกเลิก (ไม่มีผล) → ยกเลิก (คืนครั้งเดียว); แอดมินสร้างบิล pay_now → approve ×2 (อีเมล 1 ครั้ง) → save_delete (สถานะ 2 = ปฏิเสธ) → deletenopush → save_delete สถานะ 1 (คืนพัสดุ+เงิน); alipay save (เงินไม่พอ/พอ) → คนอื่นยกเลิก → ยกเลิก ×2 → rollback(-1) → approve → rollback(2) → cancel
- endpoint เขียนข้อมูลของเว็บเดิมบน :8101 ใช้ไม่ได้ (goLog เปิด log ใน webroot production ไม่ได้แล้ว exit ก่อนเขียน) → เทียบผลเขียน DB ด้วยการอ่านโค้ด + รันสูตรเดิมด้วย php7.4
- `php8.4 vendor/bin/phpunit` ผ่าน (รวม RouteSecurityTest, RouteModulesTest, PaymentRoutesTest)

## 5. bench (`bin/bench`, n=20 c=1, `artisan optimize`)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `GET /payment` (ลูกค้า 137) | 268 | 34 |
| `POST /_payment/grid` | 21 | 28 |
| `POST /_payment/load_create` (ลูกค้า 137, ~70 แถว) | ~46,000 (curl 1 ครั้ง) | ~130 (curl 1 ครั้ง) |
| `POST /_admin/_alipay/grid` | 62 | 38 |
| `GET /alipay/682` (ลูกค้า 6799) | 272 | 45 |
| `GET /admin/payment/create/137` (n=10) | 347 | 43 |

`_payment/grid` ช้ากว่าเดิม ~7 ms = overhead คงที่ของ Laravel (boot + session/jail/สิทธิ์) ตาม CONVENTIONS ข้อ 10

## 6. ข้อเสนอ / สิ่งที่ฝากผู้ merge

- `Php74`, `UploadBox` เป็นสำเนาจากกลุ่ม tracking (namespace ของกลุ่มตัวเอง) — ควรย้ายเป็นของกลางทีเดียวตอน merge
- commit `foundation: keep raw POST values` = cherry-pick ตัวเดียวกับของ mod/tracking (ไม่มีการแก้ฐานร่วมอื่น)
- `PinBox::guard()` = ด่าน PIN ตัวเดียวของทุกกลุ่ม (ตอนนี้ `_point/save_withdraw`, `_admin/_user/btn_*`; สำเนาใน Points/Users ลบแล้ว) + PinThrottle ตัวนับเดียวกับ `_profile/pin` (ผิด 5/15 นาที ต่อผู้ใช้+IP, 10 ต่อผู้ใช้; ถูก = ล้าง)

## 7. E2E admin write-flow (2026-10-18, fix/admin-writes)

- `_admin/_payment/save_new_sender` (สิทธิ์ `admin_change_sender` ได้จาก fallback ← `admin_payment`): เดิมแก้ได้ทุกสถานะ → บิลที่ยกเลิกแล้ว (คืนเงินตามยอดเดิมไปแล้ว)
  ถูกแก้แถวตัดเงินตามยอดใหม่ = ยอดเงินลูกค้าเปลี่ยนโดยไม่มีที่มา (E2E: ยกเลิกคืน 500 แล้วเปลี่ยนเป็น EMS → แถวตัดเงิน -610, ลูกค้าเสีย 110)
  → บิลที่ยกเลิกแล้ว `ErrorBox('บิลนี้ถูกยกเลิกแล้ว ...')`, ไม่พบบิล `ErrorBox(no_data)` ไม่แก้อะไร (เหมือน `save_vat`) · test: tests/Feature/Money/AdminWritesDbTest.php (JL_BENCH_DB_TESTS=1)
- **(เจ้าของยืนยัน 2026-10-18)** `save_new_sender` เปลี่ยนเป็นประเภทการส่ง/ผู้ส่งที่แพงขึ้น: **ยอดลูกค้าติดลบได้เหมือนเว็บเดิม** (ไม่ตรวจยอดคงเหลือ; ล็อกแถว user ใน transaction เท่านั้น) · `save_vat` และค่าส่งใน `_admin/_export/save_approved` ก็คงไว้แบบเดิม · test: tests/Feature/Money/LegacyRisksDbTest.php
- ยังเหมือนเดิม (แจ้งไว้): `save_create` ซ้ำกับ tracking ที่อยู่ในบิลแล้ว = สร้าง temp แต่ `save_submit_payment` ตอบ "ถูกดำเนินการไปแล้ว" ไม่สร้างบิล

## 8. ทดสอบกับข้อมูลบิลจริงบน bench (2026-10-19, fix/bill-data)

ข้อมูล: bill_payment 31,638 / bill_payment_detail 140,350 / bill_payment_detail_temp 2,167 / cart_detail 17,479 (ประวัติเต็มของ 122 ลูกค้าตัวอย่าง)

- เทียบ 1:1 กับ :8101 (normalize host/ช่องว่าง, multiset + ลำดับบรรทัด): ~330 หน้า/ajax — ลูกค้า 137/849/3269/1418/10957/974/7411/8588/5121/229/6269, แอดมิน zz_fdn_admin
  - **เท่ากันทุก byte หลัง normalize**: `/payment` (+status), `_payment/grid` (หน้า 1–3, ทุกสถานะ, ค้นหา), `payment/{id}` 93 บิล, `/export`, `_export/grid`, `export/{id}` 32 บิล, `admin/export/{id}` 35 บิล (ต่างเฉพาะเมนู fallback),
    `_admin/_payment/grid`, `_admin/_export/grid` (สถานะ/หน้า/ค้นหา id, PAY-id, รหัสลูกค้า, ชื่อผู้รับ), `payment/create` + `load_select_lot`/`load_create`/`load_create_sum`/`create/{random}`/`view_example`,
    `_admin/_payment/load_*`, `_order/print`, `_cart/load_cart`/`load_full_cart`/`cart/checkout/{ids}`, `_admin/_export/print` (เทียบกับ print.php เดิมที่รันด้วย php7.4 โดยตัด UPDATE ออก — :8101 หยุดที่ log ก่อนเขียน)
  - ต่างโดยตั้งใจ: เมนู fallback (`admin/balance_company`, `admin/point`, `admin/withdrawpoint`), ฟอร์มแก้ผู้ส่งใน `admin/payment/{id}` (`admin_change_sender` fallback ← `admin_payment`, ข้อ 7),
    `load_shipping_price` ไม่มี key ตัวเลขซ้ำ (ค่าที่เหลือเท่ากันทุกตัว), random/บิลของคนอื่น = ไม่มีสิทธิ์/ว่าง (ข้อ 2), `_cart/cart` เปิดตรง: ลิงก์ `hrefcart/...` เดิม (constant ไม่ได้กำหนด) → `/cart/...`
  - เทียบไม่ได้: บิลที่มีพัสดุ ≥ ~100 รายการ หน้าเดิม timeout 60 วินาที (header_count subquery สแกน tracking ทั้งตารางต่อแถว) — ใหม่ 150–250 ms
- **แก้ (bug)**: `payment/{id}` และ `admin/payment/{id}` เรียงแถวพัสดุตาม `bill_payment_detail.id` (= tracking.id) แต่เดิมไม่มี ORDER BY
  = MySQL อ่านผ่าน index `payment_id` → เรียงตาม PK `tracking_id` (ลำดับที่ใส่ลงบิล) — บน bench 9,414 บิลเรียงต่างกัน (เช่นบิล 123541) → ใหม่ `ORDER BY tracking_id`
  (ExportBill ของกลุ่ม reports ใช้ tracking_id อยู่แล้ว) · test: `tests/Feature/Money/BillGridDbTest.php`
- **แก้ (ความเร็ว)**: `bill_payment` ไม่มี index `user` → grid ทั้ง 4 ตัว (`_payment/grid`, `_export/grid`, `_admin/_payment/grid`, `_admin/_export/grid`)
  เดิม COUNT(*) (join user ทุกแถว) + ORDER BY id DESC LIMIT แยกกัน = สแกนทั้งตาราง 2 รอบเมื่อผลน้อย/ไม่มีผล (แอดมินค้นหา 520–580 ms ช้ากว่าหรือเท่าเดิม)
  → `GridQuery::idPage()` อ่าน id ที่ตรงรอบเดียว (นับ + ตัดหน้าใน PHP) แล้ว `rowsInOrder()` โหลดแถวเต็มเฉพาะ 10 id; `user.code LIKE` เป็น subquery (ผลเท่ากัน: join 1:1 ด้วย PK)
  ผล (bin/bench n=30 c=1, mean ms): `_admin/_export/grid` ค้นหาไม่พบ เดิม 545 / main 581 / ใหม่ 170 · `_admin/_payment/grid` PAY-id 585 / 527 / 238 ·
  `_admin/_export/grid` หน้า 1 231 / 306 / 177 · `_payment/grid` ค้นหาไม่พบ 304 / 228 / 111
  ข้อเสนอ index `bill_payment (user, …)` ดู docs/db-index-proposal.md ข้อ 2 (ยังไม่ใช้)
