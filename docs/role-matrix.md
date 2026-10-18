# Role → สิทธิ์ (acceptance check C)

ตรวจ 2026-10-18 บน `admin_jl_bench` (สำเนา) — เว็บเดิม `:8101` เทียบ Laravel `:8100` (main `7658309`) และ branch `fix/admin-roles`
**ก่อนสลับ production ให้เจ้าของรัน SQL ในข้อ 4 บน production แล้วเทียบกับตารางข้อ 2** (map fallback ใช้ "ชื่อสิทธิ์" ไม่ใช่ id ของ role)

## 1. role ใน admin_jl_bench

| id | ชื่อ | ผู้ใช้ (is_delete=0) | ประเภท |
|---|---|---|---|
| 0 | (ไม่มีแถวใน `role`) | 163 | ไม่มีสิทธิ์ใดเลย (LEFT JOIN ได้ NULL ทั้งเดิมและใหม่) |
| 1 | Default | 6 | แอดมินเต็ม |
| 2 | นำเข้าอย่างเดียว | 451 | ลูกค้า |
| 3 | สั่งซื้อ+นำเข้า | 9795 | ลูกค้า |
| 4 | 中国仓库 | 2 | แอดมินโกดังจีน |
| 5 | บัญชี | 1 | แอดมินบัญชี |
| 6 | คนกดออเดอร์ | 8 | แอดมินกดออเดอร์ (+ สิทธิ์ลูกค้า) |
| 7 | พนักงานแพ็คสินค้า | 1 | แอดมินแพ็ค |

`role_on_detail` ทั้ง 161 แถวเป็น `permission = 1`, `is_delete = 0`, ไม่มีชื่อสิทธิ์ซ้ำใน role เดียวกัน → ลำดับแถว (เดิมไม่มี ORDER BY) ไม่มีผล
(role 12–14 `zz_role_*` ที่เห็นใน bench เป็นแถวทดสอบชั่วคราวของงานอื่น ไม่นับ)

## 2. ตารางสิทธิ์: `getPermission()` เดิม เทียบ `Permissions` ใหม่

`✔` = ทั้งเดิมและใหม่มีสิทธิ์ · `·` = ไม่มีทั้งคู่ · **F** = ใหม่ได้จาก fallback (`config/legacy.php` `permission_fallbacks`, CONVENTIONS 3.1 — เจ้าของอนุมัติ) เดิมไม่มี ·
`✘!` = เดิมมีแต่ใหม่ไม่มี (ไม่พบ). คำนวณจาก DB ด้วยตรรกะของ `getPermission()` เดิม (system_config.php) เทียบ `Permissions::build()` จริง
รายการสิทธิ์ = ทุกชื่อใน `role_detail` + ทุกชื่อที่โค้ดเดิมเรียก `getPermission('...')`

| สิทธิ์ (role_detail_name) | 0 | 1 | 2 | 3 | 4 | 5 | 6 | 7 | หมายเหตุ |
|---|---|---|---|---|---|---|---|---|---|
| `admin_alipay` | · | ✔ | · | · | · | ✔ | · | · |  |
| `admin_balance` | · | ✔ | · | · | · | ✔ | · | · |  |
| `admin_balance_cn` | · | ✔ | · | · | · | ✔ | · | · |  |
| `admin_balance_cn_add` | · | ✔ | · | · | · | · | · | · |  |
| `admin_balance_company` | · | **F** | · | · | · | **F** | · | · | fallback ← `admin_balance` |
| `admin_balance_company_add` | · | **F** | · | · | · | · | · | · | ไม่มีโค้ดตรวจ · fallback ← `admin_balance_cn_add` |
| `admin_balance_company_delete` | · | **F** | · | · | · | · | · | · | fallback ← `admin_balance_cn_add` |
| `admin_change_sender` | · | **F** | · | · | · | **F** | · | **F** | fallback ← `admin_payment` |
| `admin_combine_track` | · | ✔ | · | · | · | · | ✔ | · |  |
| `admin_coupon` | · | · | · | · | · | · | · | · | ไม่มีใน role_detail · เมนูเดิมเท่านั้น (ไม่มีหน้า) |
| `admin_exchange` | · | ✔ | · | · | · | ✔ | · | · |  |
| `admin_export` | · | ✔ | · | · | · | ✔ | · | ✔ |  |
| `admin_goodsconfirm` | · | ✔ | · | · | ✔ | ✔ | · | ✔ |  |
| `admin_iden` | · | · | · | · | · | · | · | · | ไม่มีใน role_detail · เมนูเดิมเท่านั้น |
| `admin_log` | · | ✔ | · | · | · | · | · | · |  |
| `admin_lost_package` | · | ✔ | · | · | · | ✔ | ✔ | · |  |
| `admin_lot` | · | ✔ | · | · | · | ✔ | ✔ | ✔ |  |
| `admin_lot_view` | · | · | · | · | ✔ | · | · | · |  |
| `admin_mall` | · | ✔ | · | · | · | · | ✔ | · |  |
| `admin_mall_category` | · | **F** | · | · | · | · | **F** | · | ไม่มีใน role_detail · fallback ← `admin_mall` |
| `admin_mall_stock` | · | ✔ | · | · | · | · | ✔ | · |  |
| `admin_message` | · | ✔ | · | · | · | ✔ | ✔ | · |  |
| `admin_nobilling` | · | **F** | · | · | · | **F** | · | **F** | fallback ← `admin_report` |
| `admin_notice` | · | ✔ | · | · | · | · | · | · |  |
| `admin_order` | · | ✔ | · | · | ✔ | ✔ | ✔ | ✔ |  |
| `admin_payment` | · | ✔ | · | · | · | ✔ | · | ✔ |  |
| `admin_payment_create` | · | ✔ | · | · | · | ✔ | ✔ | ✔ |  |
| `admin_payment_createpayment` | · | · | · | · | · | · | · | · | ไม่มีใน role_detail · แค่ชื่อในข้อความแจ้งเตือน |
| `admin_point` | · | **F** | · | · | · | **F** | · | · | fallback ← `admin_shop_cashback` |
| `admin_product_type` | · | ✔ | · | · | · | ✔ | · | · |  |
| `admin_refill` | · | ✔ | · | · | · | ✔ | · | · |  |
| `admin_report` | · | ✔ | · | · | · | ✔ | · | ✔ |  |
| `admin_role` | · | ✔ | · | · | · | · | · | · |  |
| `admin_setting` | · | ✔ | · | · | · | · | · | · |  |
| `admin_shipping_type` | · | ✔ | · | · | · | · | · | · |  |
| `admin_shop_cashback` | · | ✔ | · | · | · | ✔ | · | · |  |
| `admin_taobao` | · | ✔ | · | · | · | ✔ | ✔ | · |  |
| `admin_tracking` | · | ✔ | · | · | ✔ | ✔ | ✔ | ✔ |  |
| `admin_transfer` | · | ✔ | · | · | · | ✔ | · | · |  |
| `admin_user` | · | ✔ | · | · | · | · | · | · |  |
| `admin_user_type` | · | ✔ | · | · | · | · | · | · |  |
| `admin_user_view` | · | · | · | · | · | ✔ | · | · |  |
| `admin_warehouse` | · | ✔ | · | · | · | · | · | · |  |
| `admin_warehouse_move` | · | ✔ | · | · | · | · | · | · | มีใน role_detail แต่ไม่มีโค้ดตรวจ |
| `admin_warehouse_section` | · | ✔ | · | · | · | · | · | · |  |
| `admin_withdrawcash` | · | ✔ | · | · | · | · | · | · |  |
| `admin_withdrawpoint` | · | **F** | · | · | · | **F** | · | · | fallback ← `admin_exchange` |
| `alipay` | · | ✔ | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `balance` | · | ✔ | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `balance_cn` | · | · | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `export` | · | ✔ | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `goodsconfirm` | · | ✔ | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `lost_package` | · | ✔ | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `lot` | · | ✔ | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `mall` | · | ✔ | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `message` | · | ✔ | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `order` | · | ✔ | · | ✔ | · | ✔ | ✔ | · |  |
| `payment` | · | ✔ | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `point` | · | · | · | · | · | · | ✔ | · |  |
| `profile` | · | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | · |  |
| `refill` | · | ✔ | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `shop_cashback` | · | ✔ | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `taobao` | · | ✔ | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `tracking` | · | ✔ | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `transfer` | · | ✔ | ✔ | ✔ | · | ✔ | ✔ | · |  |
| `view_bol` | · | · | · | · | · | · | · | · | ไม่มีใน role_detail · เจ้าของไม่เปิด (`POST /get_price` ปฏิเสธทุกคน) |

ต่างจากเดิมเฉพาะช่อง **F** ซึ่งตรงกับตาราง CONVENTIONS 3.1 ทุกช่อง (`admin_point` 1,5 · `admin_withdrawpoint` 1,5 · `admin_nobilling` 1,5,7 · `admin_balance_company` 1,5 ·
`admin_balance_company_add/_delete` 1 · `admin_change_sender` 1,5,7 · `admin_mall_category` 1,6) — `view_bol` ไม่มีใครได้ (เหมือนเดิม)

## 3. หน้าเว็บต่อ role (GET 158 URL ทุก RewriteRule ของหน้า, เมนู)

ผู้ใช้ทดสอบ (อ่านอย่างเดียว): role 0 = 75, 1 = 1, 2 = 9, 3 = 5, 4 = 74, 5 = 6782, 6 = 83, 7 = 2172 · detail ใช้ id ที่ไม่มีจริง (ด่านสิทธิ์มาก่อน)
"ผ่าน" = ไม่มี ErrorBox สิทธิ์ (เดิม `exit()` เงียบของ `admin/lot/{id}` นับเป็นผ่าน)

| role | เดิม ผ่าน/ปฏิเสธ | ใหม่ ผ่าน/ปฏิเสธ | ต่าง | ทุกจุดที่ต่าง |
|---|---|---|---|---|
| 0 | 11 / 147 | 6 / 152 | 7 | A, B, C, D, E |
| 1 | 137 / 21 | 144 / 14 | 7 | F (balance_company, point, withdrawpoint×3, mall_category, nobilling) |
| 2 | 48 / 110 | 41 / 117 | 7 | A, B, C, D, E |
| 3 | 53 / 105 | 46 / 112 | 7 | A, B, C, D, E |
| 4 | 25 / 133 | 22 / 136 | 5 | A, C, D, E (label) |
| 5 | 108 / 50 | 113 / 45 | 7 | F (balance_company, point, withdrawpoint×3, nobilling), D |
| 6 | 78 / 80 | 76 / 82 | 4 | C, E, F (mall_category) |
| 7 | 32 / 126 | 31 / 127 | 5 | A, D, E (label), F (nobilling) |

- **A** `admin/message`: เดิมไม่ตรวจสิทธิ์ (ทุกคนที่ล็อกอินเปิดได้) → ใหม่ `admin_message` (docs/modules/messaging.md ช่องโหว่เดิม)
- **B** `admin/lot/create`, `admin/lot/{id}` ผู้ไม่มี `admin_lot`/`admin_lot_view`: เดิม `exit()` หน้าว่างครึ่ง layout → ใหม่ ErrorBox มาตรฐาน (routes/modules/lot.php หัวไฟล์) — ปฏิเสธทั้งคู่ ต่างแค่รูปแบบ
- **C** `admin/report`: index.php เดิมไม่ตรวจ (grid ตรวจ) → ใหม่ `admin_report` (routes/modules/reports.php)
- **D** `admin/combine_track`: index.php เดิมไม่ตรวจ (ajax ตรวจ) → ใหม่ `admin_combine_track` (routes/modules/tracking.php)
- **E** `admin/transfer/create`, `admin/transfer/{id}`: เดิมตรวจแค่ `transfer` (ลูกค้า/role 6 เปิดได้ = IDOR) → ใหม่ต้องมี `transfer` + `admin_transfer` (docs/modules/money-move.md ข้อ 3)
  - fix/admin-roles: ตรวจ `transfer` ก่อน → role 0/4/7 ได้ข้อความโมดูล "โอนเงินไปจีน" เหมือนเดิม (main เดิมตอบ "ผู้ดูแล - โอนเงินไปจีน")
- **F** fallback (ข้อ 2)

เมนูซ้าย (desktop `menu.php` และ mobile `menu_mobile.php`) ทุก role: HTML ตรงกับเดิมทุกบรรทัด (หลัง normalize host/ช่องว่าง)
ยกเว้นลิงก์ของสิทธิ์ fallback: `admin/balance_company` (role 1, 5) และในเมนู mobile `admin/point`, `admin/withdrawpoint` (role 1, 5)
ในหน้าที่มีสิทธิ์ (เช่น `admin/user/5` role 1/5, `admin/lot/5534` role 1/4/6, `admin/transfer/2491`, `admin/balance_cn`) เนื้อหาตรงกับเดิม ต่างแค่เมนู fallback

## 4. SQL ให้เจ้าของรันบน production (อ่านอย่างเดียว)

```sql
-- role และสิทธิ์ที่ถือ (เทียบกับตารางข้อ 2 — ต้องได้ชุดเดียวกันต่อ role)
SELECT r.id, r.role_name, GROUP_CONCAT(d.role_detail_name ORDER BY d.role_detail_name) AS perms
FROM role r
LEFT JOIN role_on_detail o ON o.role_id = r.id AND o.is_delete = 0 AND o.permission = 1
LEFT JOIN role_detail d ON d.id = o.role_detail_id
GROUP BY r.id, r.role_name ORDER BY r.id;

-- ชื่อสิทธิ์ซ้ำใน role เดียวกัน (ต้องว่าง; ถ้ามี แถวแรกชนะทั้งเดิมและใหม่ แต่ควรดูว่าค่า permission ตรงกัน)
SELECT o.role_id, d.role_detail_name, COUNT(*) c, GROUP_CONCAT(o.permission ORDER BY o.id) p
FROM role_on_detail o JOIN role_detail d ON d.id = o.role_detail_id
WHERE o.is_delete = 0 GROUP BY o.role_id, d.role_detail_name HAVING c > 1;

-- role ที่ได้สิทธิ์กำพร้าผ่าน fallback (ต้นทาง → role) — ให้ตรงกับ CONVENTIONS 3.1
SELECT d.role_detail_name AS source, GROUP_CONCAT(o.role_id ORDER BY o.role_id) AS roles
FROM role_on_detail o JOIN role_detail d ON d.id = o.role_detail_id
WHERE o.is_delete = 0 AND o.permission = 1
  AND d.role_detail_name IN ('admin_shop_cashback','admin_exchange','admin_report','admin_balance','admin_balance_cn_add','admin_payment','admin_mall')
GROUP BY d.role_detail_name;

-- สิทธิ์กำพร้าที่ production ติ๊กให้แล้ว (ถ้ามี → fallback ไม่จำเป็นสำหรับ role นั้น)
SELECT o.role_id, d.role_detail_name, o.permission FROM role_on_detail o JOIN role_detail d ON d.id = o.role_detail_id
WHERE o.is_delete = 0 AND d.role_detail_name IN ('admin_point','admin_withdrawpoint','admin_nobilling','admin_balance_company',
  'admin_balance_company_add','admin_balance_company_delete','admin_change_sender','admin_mall_category','view_bol');

-- ผู้ใช้ที่ role ไม่มีในตาราง role (ได้สิทธิ์ว่างทั้งเดิมและใหม่)
SELECT role, COUNT(*) FROM `user` WHERE is_delete = 0 AND role NOT IN (SELECT id FROM role) GROUP BY role;
```

## 5. route ↔ getPermission() ของไฟล์เดิม

ทุก route ใต้ `_…` (ajax/ไฟล์ตรง) เทียบชุดสิทธิ์ `legacy.perm` + `perm()` ใน controller กับ `getPermission()` ในไฟล์เดิม:
ตรงกัน ยกเว้นรายการที่บันทึกไว้แล้วใน docs/modules/*.md / routes (ทั้งหมดเป็นการ "แคบกว่าเดิม" หรือ owner-approved):
ไฟล์เดิมที่ไม่ตรวจสิทธิ์ (email_*, cron_*, `_admin/_message/*`, `_admin/_setting/save`, `_admin/_order/save_amount|load_total_order`, `_admin/_tracking/load_pricing|load_product_type|save_lock`,
`_admin/_payment/save_submit_payment`, `_payment/load_vat|load_shipping_price`, `_mall/addtocart|load_detail`, `_lost_package/save_image`, `_api/load_product_v2`) → ใส่สิทธิ์ของหน้าแม่;
`_admin/_alipay/grid` (เดิม `alipay` ของลูกค้า) → `admin_alipay`; `_admin/_mallstock/load_amount|save_amount` (เดิม `admin_balance`) → `admin_mall_stock` (เจ้าของอนุมัติ);
`_admin/_notice/delete` → `admin_product_type` + `admin_notice`; `_admin/_lot/save*` → `admin_lot` เท่านั้น (admin_lot_view ดูอย่างเดียว, เจ้าของอนุมัติ)

รูปแบบการปฏิเสธ ajax (role 0 POST ทุก route ที่มี `legacy.perm` = 223 route: ทุกตัว `X-Legacy-Denied: 1` + `ErrorBox(...)` ของ `getPermissionAlert`):
grid แอดมินที่เดิม `exit()` ตอบว่าง — `_admin/_mall/grid`, `_admin/_mall_category/grid`, `_admin/_mallstock/grid` และ `_admin/_lot/*` ใหม่ตอบ ErrorBox (บันทึกใน shop.md / lot.php แล้ว)
`_admin/_notice/grid` (ไม่ได้บันทึก) → fix/admin-roles ตอบว่างเหมือนเดิม

## 6. ไม่ได้ตรวจ

- endpoint เขียนของผู้ที่ **มี** สิทธิ์ (จะเขียน DB) — ตรวจเฉพาะด่านปฏิเสธ; เว็บเดิมบน :8101 ไม่ได้ยิง POST เลย (เทียบจากซอร์ส)
- ปุ่ม/ช่องใน detail ที่ขึ้นกับสิทธิ์ซึ่งต้องมีข้อมูลจริง: `admin_change_sender` ในบิล (bench ไม่มีแถว `bill_payment`)
- แจ้งเตือนหัวเว็บ (`notification.php`) ต่อ role — ใช้ `admin_withdrawpoint`/`admin_nobilling` ซึ่งเป็น fallback (CONVENTIONS 3.1)
