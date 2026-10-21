# Role → สิทธิ์ ของ PACK (acceptance check C)

คำนวณเมื่อ 2026-10-22 จาก `admin_pack_bench` (สำเนาของ `admin_pack`, อ่านอย่างเดียว) ด้วย `tools/role-matrix.php` (ใช้ DB ตาม `.env` ของ repo)
- **เดิม**: ใช้ SQL เดียวกับ `getPermission()` ใน `system_config.php` ของ PACK ทีละสิทธิ์ (โค้ด getPermission/checkUser ของ PACK = SR ทุกบรรทัด)
- **ใหม่**: ใช้ `Permissions::build()` จริงของ Laravel (รวม fallback ตาม `config/legacy.php` — ตอนนี้ว่าง เหมือน SR)
- ชื่อสิทธิ์ที่ตรวจ: ทุกชื่อใน `role_detail` (63 แถว) และทุกชื่อที่โค้ดเดิมของ PACK เรียก `getPermission*('...')` (62 ชื่อ)

ผล: **ตรงกันทุกช่อง** (ไม่มีช่อง **F** หรือ `✘!`)

ความหมายของสัญลักษณ์: `✔` มีทั้งเดิมและใหม่ · `·` ไม่มีทั้งคู่ · **F** ใหม่ได้จาก fallback · `✘!` เดิมมีแต่ใหม่ไม่มี

ข้อสังเกตของ PACK:
- **role 2 "สิทธิ์ลูกค้า" = ลูกค้า** (1,011 คน) — role 8 "ลูกค้า" มีสิทธิ์ชุดเดียวกันแต่ไม่มีผู้ใช้ · ผู้สมัครใหม่ได้ `role = 0` (ไม่มี role นี้ = ไม่มีสิทธิ์ใดเลย จนแอดมินตั้ง role ในฟอร์มผู้ใช้; bench มี 15 คน)
- role 3 "บัญชี" ไม่มีสิทธิ์ฝั่งลูกค้าเลย (ไม่มี `profile`, `tracking` ฯลฯ) — role 5, 6 ก็เช่นกัน
- `admin_nobilling` มีแค่ role 4 (role 1 ผู้ดูแล **ไม่มี**) — ไม่ใช่สิทธิ์กำพร้า จึงไม่ใช้ fallback ของ jl
- PACK มีสิทธิ์ที่ SR ไม่มี: `admin_coupon` (role 1), `admin_viewprice_on_payment` (role 1, 3)
- **สิทธิ์กำพร้า** (มีใน `role_detail` / โค้ดตรวจ แต่ไม่มี role ใดถือ → เว็บเดิมไม่มีใครเข้าได้แม้แอดมิน; Laravel เหมือนเดิมเพราะ fallback ว่าง):
  - `admin_warehouse` — `/admin/warehouse` และ `_admin/_warehouse/*` ทั้งหมด, กล่องบน dashboard (`_dashboard/index.php`) → **ทั้งโมดูลโกดังเข้าไม่ได้ใน PACK** (ให้กลุ่ม warehouse/เจ้าของตัดสินใจ — ห้ามเพิ่ม fallback เอง)
  - `admin_user_view` — เมนู/หน้า `admin/user` ใช้ `admin_user || admin_user_view` → มีผลแค่ role 1 (admin_user)
  - `admin_mall` — `system_ckupload` ใช้ `admin_notice || admin_mall` → มีผลแค่ผู้ถือ admin_notice (role 1, 4, 7)
  - ไม่มีโค้ดตรวจ: `admin_mall_stock`, `admin_warehouse_move`, `mall`, `admin_balance_company_add`
- ไม่มีแถวใน `role_detail` (ไม่มีใครได้ทั้งเดิมและใหม่): `admin_iden` (เมนูเดิมเท่านั้น), `admin_payment_createpayment` (ชื่อในข้อความ), `view_bol` (`/get_price`)

ถ้าจะเปิดใช้สิทธิ์เหล่านี้ เจ้าของต้องติ๊กให้ role ในหน้าจัดการ role ของเว็บ (หรือเพิ่ม fallback ใน `config/legacy.php` เมื่อเจ้าของอนุมัติ) **ห้ามเพิ่ม fallback เอง**

## 1–2. role และตารางสิทธิ์

| id | ชื่อ | ผู้ใช้ (is_delete=0, ไม่นับ zz) |
|---|---|---|
| 1 | ผู้ดูแล | 3 |
| 2 | สิทธิ์ลูกค้า | 1011 |
| 3 | บัญชี  | 1 |
| 4 | ดูเเลออเดอร์สั่งซื้อ | 3 |
| 5 | จ่ายเงินหยวน | 0 |
| 6 | สำหรับโกดัง GOGO เท่านั้น(ห้ามลบby MAX) | 1 |
| 7 | Tracking | 1 |
| 8 | ลูกค้า | 0 |
| 9 | ลง Tracking | 0 |

| สิทธิ์ | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 | หมายเหตุ |
|---|---|---|---|---|---|---|---|---|---|---|
| `admin_alipay` | ✔ | · | · | ✔ | ✔ | · | ✔ | · | · |  |
| `admin_balance` | ✔ | · | · | · | · | · | · | · | · |  |
| `admin_balance_cn` | ✔ | · | · | ✔ | ✔ | · | · | · | · |  |
| `admin_balance_cn_add` | ✔ | · | · | · | · | · | · | · | · |  |
| `admin_balance_company` | ✔ | · | · | · | ✔ | · | · | · | · |  |
| `admin_balance_company_add` | ✔ | · | · | · | · | · | · | · | · | โค้ดเดิมไม่ได้ตรวจ |
| `admin_balance_company_delete` | ✔ | · | · | · | · | · | · | · | · |  |
| `admin_change_sender` | ✔ | · | · | · | · | · | · | · | · |  |
| `admin_combine_track` | ✔ | · | · | ✔ | · | · | ✔ | · | ✔ |  |
| `admin_coupon` | ✔ | · | · | · | · | · | · | · | · |  |
| `admin_exchange` | ✔ | · | · | ✔ | · | · | · | · | · |  |
| `admin_export` | ✔ | · | ✔ | · | · | · | ✔ | · | ✔ |  |
| `admin_goodsconfirm` | ✔ | · | · | ✔ | · | · | ✔ | · | · |  |
| `admin_iden` | · | · | · | · | · | · | · | · | · | ไม่มีใน role_detail (ไม่มีใครได้) |
| `admin_log` | ✔ | · | · | · | · | · | · | · | · |  |
| `admin_lost_package` | ✔ | · | · | ✔ | · | · | ✔ | · | · |  |
| `admin_lot` | ✔ | · | · | · | · | ✔ | ✔ | · | ✔ |  |
| `admin_lot_view` | · | · | · | · | · | ✔ | · | · | ✔ |  |
| `admin_mall` | · | · | · | · | · | · | · | · | · |  |
| `admin_mall_stock` | · | · | · | · | · | · | · | · | · | โค้ดเดิมไม่ได้ตรวจ |
| `admin_message` | ✔ | · | · | ✔ | · | · | ✔ | · | ✔ |  |
| `admin_nobilling` | · | · | · | ✔ | · | · | · | · | · |  |
| `admin_notice` | ✔ | · | · | ✔ | · | · | ✔ | · | · |  |
| `admin_order` | ✔ | · | ✔ | ✔ | · | · | ✔ | · | · |  |
| `admin_payment` | ✔ | · | ✔ | ✔ | · | · | ✔ | · | ✔ |  |
| `admin_payment_create` | ✔ | · | · | · | · | · | ✔ | · | · |  |
| `admin_payment_createpayment` | · | · | · | · | · | · | · | · | · | ไม่มีใน role_detail (ไม่มีใครได้) |
| `admin_point` | ✔ | · | · | · | · | · | · | · | · |  |
| `admin_product_type` | ✔ | · | · | ✔ | · | · | ✔ | · | · |  |
| `admin_refill` | ✔ | · | ✔ | ✔ | · | · | ✔ | · | · |  |
| `admin_report` | ✔ | · | ✔ | · | · | · | · | · | · |  |
| `admin_role` | ✔ | · | · | · | · | · | · | · | · |  |
| `admin_setting` | ✔ | · | · | · | · | · | ✔ | · | · |  |
| `admin_shipping_type` | ✔ | · | · | · | · | · | ✔ | · | · |  |
| `admin_shop_cashback` | ✔ | · | · | ✔ | ✔ | · | · | · | · |  |
| `admin_taobao` | ✔ | · | · | ✔ | ✔ | · | · | · | · |  |
| `admin_tracking` | ✔ | · | · | ✔ | · | · | ✔ | · | ✔ |  |
| `admin_transfer` | ✔ | · | · | ✔ | ✔ | · | · | · | · |  |
| `admin_user` | ✔ | · | · | · | · | · | · | · | · |  |
| `admin_user_type` | ✔ | · | · | · | · | · | · | · | · |  |
| `admin_user_view` | · | · | · | · | · | · | · | · | · |  |
| `admin_viewprice_on_payment` | ✔ | · | ✔ | · | · | · | · | · | · |  |
| `admin_warehouse` | · | · | · | · | · | · | · | · | · |  |
| `admin_warehouse_move` | · | · | · | · | · | · | · | · | · | โค้ดเดิมไม่ได้ตรวจ |
| `admin_warehouse_section` | ✔ | · | · | · | · | · | · | · | · |  |
| `admin_withdrawcash` | ✔ | · | ✔ | · | · | · | · | · | · |  |
| `admin_withdrawpoint` | ✔ | · | · | ✔ | · | · | · | · | · |  |
| `alipay` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `balance` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `balance_cn` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `export` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `goodsconfirm` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `lost_package` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `lot` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `mall` | · | · | · | · | · | · | · | · | · | โค้ดเดิมไม่ได้ตรวจ |
| `message` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `order` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `payment` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `point` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `profile` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `refill` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `shop_cashback` | ✔ | ✔ | · | ✔ | ✔ | · | ✔ | ✔ | ✔ |  |
| `taobao` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `tracking` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `transfer` | ✔ | ✔ | · | ✔ | · | · | ✔ | ✔ | ✔ |  |
| `view_bol` | · | · | · | · | · | · | · | · | · | ไม่มีใน role_detail (ไม่มีใครได้) |

ไม่ตรงกัน: 0 ช่อง

## 3. ตรวจซ้ำบน production ก่อนสลับ (อ่านอย่างเดียว)

```bash
grep -rhoE "getPermission(Alert(_Page)?)?\(\s*['\"][a-z_]+['\"]" /home/admin/bench/pack-old --include=*.php --exclude-dir=vendor \
  | grep -oE "['\"][a-z_]+['\"]" | tr -d "'\"" | sort -u > /tmp/pack_perm_names.txt
php8.4 tools/role-matrix.php /tmp/pack_perm_names.txt     # .env ชี้ DB ไหน = ตรวจ DB นั้น (ตอนนี้ admin_pack_bench)
```
```sql
-- ใครถือสิทธิ์อะไรใน production (admin_pack) — legacy ไม่กรอง role_detail.is_delete
SELECT d.role_detail_name, GROUP_CONCAT(DISTINCT o.role_id ORDER BY o.role_id)
FROM role_detail d LEFT JOIN role_on_detail o ON o.role_detail_id = d.id AND o.permission = 1 AND o.is_delete = 0
GROUP BY d.role_detail_name ORDER BY 1;
```
