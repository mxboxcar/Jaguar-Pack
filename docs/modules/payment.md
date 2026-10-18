# กลุ่ม payment — รายงานการพอร์ต

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
