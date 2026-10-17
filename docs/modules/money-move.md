# money-move — เติมเงิน / โอนเงินไปจีน / ถอนเงิน

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
| `admin/transfer/create`, `admin/transfer/N` (`form.php` เดิมตรวจ `transfer`) | `GET` | **transfer + admin_transfer** |
| `_admin/_transfer/grid` / `save` / `cancel` / `rollback` | `POST` เดิม | ล็อกอิน (grid) / admin_transfer |
| `_admin/_transfer/email_success` / `email_cancel` (include) | `GET\|POST` → ตอบว่าง | admin_transfer |
| `admin/withdrawcash`, `admin/withdrawcash/status/N`, `admin/withdrawcash/N` | `GET` | admin_withdrawcash |
| `_admin/_withdrawcash/grid` / `save_approved` / `cancel` | `POST` เดิม | ล็อกอิน (grid) / admin_withdrawcash |

ไม่ทำ route: `*/index.php`, `*/form.php` (เดิมเป็นไฟล์ที่ index.php include — เปิดตรงได้แค่ชิ้น HTML ที่พัง), `_admin/_withdrawcash/email_*.php` (ไม่มีไฟล์จริง)
JS ในหน้าแอดมินเติมเงินเรียก `_admin/_lot/load_autocomplete_user` = ของกลุ่ม lot

## ต่างจากเดิมโดยตั้งใจ

- ค่าจาก DB/ผู้ใช้ใน HTML ถูก escape (`acc_name`, `remark`, `cancel_remark`, `branch_*`, ชื่อไฟล์แนบ, ค่าค้นหา) — ค่าคำค้นที่มี `'` แสดงเป็น `&#039;` แทน `'`
  ค่า setting `bank_acc/bank_no/bank_name/bank_branch` ยังแสดงดิบ (แอดมินตั้ง)
- รายการที่ไม่พบ/ของคนอื่นใน `/refill/{id}`: เดิม layout ถูกตัดกลางหน้า (exit) — ใหม่แสดง layout เต็ม + ErrorBox เดิม
- grid: LIKE ค้นหา escape `%` `_` (เดิมเป็น wildcard)
- `save_approved` ของเติมเงิน: เดิม `sleep(3)` แล้วลบ balance ซ้ำ — ใหม่ lock แถว refill ใน transaction (ไม่ sleep) แล้วยังลบซ้ำแบบเดิม
- งานเงินทุกตัว (`_transfer/save`, `_transfer/cancel`, `_admin/_refill/save`, `save_approved`, `_admin/_transfer/cancel`, `rollback`, `_admin/_withdrawcash/*`) อยู่ใน `DB::transaction` + `lockForUpdate`
  `_transfer/save` lock แถว user ก่อนเช็คยอด (เดิมกดซ้ำเร็วๆ ใช้เงินเกินยอดได้)
- ชื่อไฟล์แนบที่รับจาก POST (`upload_file`, `upload_approved_file`, `approved_upload`) ต้องเป็นชื่อแบบที่ system_upload ออกให้ (`[A-Za-z0-9._-]`) ไม่งั้นบันทึกเป็น `''`
- อีเมล: Mail ของ Laravel (dev = log), ข้อความที่แอดมินพิมพ์ในอีเมลถูก escape; LINE/push = no-op เหมือนเดิม (ฟังก์ชันเดิมว่าง)
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

## พฤติกรรมเดิมที่คงไว้ (เสนอให้เจ้าของตัดสินใจ)

- `_admin/_transfer/save.php` ไม่ตรวจ status: อนุมัติรายการที่ยกเลิก (คืนเงินแล้ว) เป็น status 2 ได้ → ลูกค้าได้ทั้งเงินคืนและการโอน (ปุ่มบันทึกแสดงทุกสถานะ)
- `_admin/_withdrawcash/save_approved.php` ไม่ตรวจ status -1 → อนุมัติรายการที่ยกเลิก/คืนเงินแล้วได้
- `_admin/_refill/cancel.php` ไม่ตรวจ status → ยกเลิกรายการที่อนุมัติแล้วได้ (เงินที่เติมยังอยู่)
- `_admin/_transfer/rollback.php` (status -1) หักเงินคืนโดยไม่เช็คยอดคงเหลือ
- `_refill/save.php` ไม่ตรวจว่ายอด > 0 (ลูกค้าแจ้งยอดติดลบได้ แอดมินต้องตรวจเอง)
- หัวเรื่อง `/transfer/{id}` ใช้ prefix ของ alipay (`ALI-`) ตามเดิม; ข้อความสำเร็จของ withdrawcash ใช้ prefix `WPOINT-` ตามเดิม
- `save_approved` ของเติมเงินลบ balance ซ้ำด้วย `prefix = '10'` ตายตัว, rollback ใช้ `prefix = 20` ตายตัว (= ค่า id ใน bench/production ตอนนี้)

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
