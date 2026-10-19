# Role → สิทธิ์ ของ SR (acceptance check C)

คำนวณเมื่อ 2026-10-20 จาก `admin_sr_bench` ซึ่งเป็นสำเนาเต็มของ `admin_sr` (อ่านอย่างเดียว) ด้วย `tools/role-matrix.php`
- **เดิม**: ใช้ SQL เดียวกับ `getPermission()` ใน `system_config.php` ของ SR ทีละสิทธิ์
- **ใหม่**: ใช้ `Permissions::build()` จริงของ Laravel
- ชื่อสิทธิ์ที่ตรวจ: ทุกชื่อใน `role_detail` และทุกชื่อที่โค้ดเดิมเรียก `getPermission*('...')`

ผล: **ตรงกันทุกช่อง**
- SR ไม่มีสิทธิ์กำพร้า (สิทธิ์ที่โค้ดตรวจและมีใน role_detail มี role ถืออย่างน้อย 1 role ทุกตัว) map fallback จึงว่าง (CONVENTIONS ข้อ 3.1)
- ถ้าใช้ map ของ jl จะให้สิทธิ์เกินที่ SR ตั้งไว้ เช่น role 10–13 จะได้ `admin_point` จาก `admin_shop_cashback`

ความหมายของสัญลักษณ์: `✔` มีทั้งเดิมและใหม่ · `·` ไม่มีทั้งคู่ · **F** ใหม่ได้จาก fallback · `✘!` เดิมมีแต่ใหม่ไม่มี
ตารางนี้ไม่มีช่องแบบ **F** หรือ `✘!` เลย

สิทธิ์ที่ไม่มีใครได้ทั้งเดิมและใหม่ เพราะไม่มีแถวใน `role_detail`:
- `findproduct` (หน้า `/findproduct` ของลูกค้า และตาราง `find_product` ว่าง)
- `view_bol` (`/get_price`)
- `admin_payment_createpayment` (เป็นแค่ชื่อในข้อความ)

ถ้าจะเปิดใช้สิทธิ์เหล่านี้ เจ้าของต้องติ๊กให้ role ในหน้าจัดการ role ของเว็บ (หรือเพิ่มแถวใน `role_detail`) **ห้ามเพิ่ม fallback เอง**

## 1–2. role และตารางสิทธิ์

| id | ชื่อ | ผู้ใช้ (is_delete=0, ไม่นับ zz) |
|---|---|---|
| 1 | ผู้ดูแล | 3 |
| 7 | นำเข้าอย่างเดียว | 1 |
| 8 | สั่งซื้อและนำเข้า | 19 |
| 9 | Admin | 0 |
| 10 | สั่งซื้อ | 0 |
| 11 | Admin support | 0 |
| 12 | Pur-staff | 1 |
| 13 | PUR-SUPPORT | 0 |

| สิทธิ์ | 1 | 7 | 8 | 9 | 10 | 11 | 12 | 13 | หมายเหตุ |
|---|---|---|---|---|---|---|---|---|---|
| `admin_alipay` | ✔ | · | · | · | ✔ | · | ✔ | · |  |
| `admin_balance` | ✔ | · | · | · | · | · | ✔ | · |  |
| `admin_balance_cn` | ✔ | · | · | · | · | · | · | · |  |
| `admin_balance_cn_add` | ✔ | · | · | · | · | · | · | · |  |
| `admin_balance_company` | ✔ | · | · | · | · | · | · | · |  |
| `admin_balance_company_add` | ✔ | · | · | · | · | · | · | · | โค้ดเดิมไม่ได้ตรวจ |
| `admin_balance_company_delete` | ✔ | · | · | · | · | · | · | · |  |
| `admin_change_sender` | ✔ | · | · | ✔ | ✔ | · | ✔ | · |  |
| `admin_combine_track` | ✔ | · | · | ✔ | ✔ | · | ✔ | ✔ |  |
| `admin_exchange` | ✔ | · | · | · | ✔ | · | ✔ | · |  |
| `admin_export` | ✔ | · | · | ✔ | ✔ | · | ✔ | · |  |
| `admin_goodsconfirm` | ✔ | · | · | ✔ | ✔ | ✔ | ✔ | · |  |
| `admin_log` | ✔ | · | · | · | · | · | · | · |  |
| `admin_lost_package` | ✔ | · | · | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `admin_lot` | ✔ | · | · | ✔ | · | · | · | · |  |
| `admin_lot_view` | ✔ | · | · | · | ✔ | ✔ | ✔ | · |  |
| `admin_mall` | · | · | · | · | · | · | · | · |  |
| `admin_mall_stock` | · | · | · | · | · | · | · | · | โค้ดเดิมไม่ได้ตรวจ |
| `admin_message` | ✔ | · | · | ✔ | ✔ | ✔ | ✔ | · |  |
| `admin_nobilling` | ✔ | · | · | · | ✔ | ✔ | ✔ | ✔ |  |
| `admin_notice` | ✔ | · | · | ✔ | ✔ | ✔ | ✔ | · |  |
| `admin_order` | ✔ | · | · | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `admin_payment` | ✔ | · | · | ✔ | ✔ | · | ✔ | · |  |
| `admin_payment_create` | ✔ | · | · | ✔ | ✔ | · | ✔ | · |  |
| `admin_payment_createpayment` | · | · | · | · | · | · | · | · | ไม่มีใน role_detail (ไม่มีใครได้) |
| `admin_point` | ✔ | · | · | · | · | · | · | · |  |
| `admin_printorder` | ✔ | · | · | ✔ | ✔ | · | ✔ | · |  |
| `admin_product_type` | ✔ | · | · | ✔ | · | · | · | · |  |
| `admin_receipt` | ✔ | · | · | · | · | · | · | · |  |
| `admin_refill` | ✔ | · | · | ✔ | ✔ | · | ✔ | · |  |
| `admin_report` | ✔ | · | · | · | · | · | · | · |  |
| `admin_role` | ✔ | · | · | · | · | · | · | · |  |
| `admin_setting` | ✔ | · | · | · | · | · | · | · |  |
| `admin_shipping_type` | ✔ | · | · | ✔ | ✔ | ✔ | ✔ | · |  |
| `admin_shop_cashback` | ✔ | · | · | · | ✔ | ✔ | ✔ | ✔ |  |
| `admin_taobao` | ✔ | · | · | · | ✔ | · | ✔ | · |  |
| `admin_tracking` | ✔ | · | · | ✔ | ✔ | ✔ | ✔ | · |  |
| `admin_transfer` | ✔ | · | · | · | ✔ | · | ✔ | · |  |
| `admin_user` | ✔ | · | · | ✔ | · | · | ✔ | · |  |
| `admin_user_type` | ✔ | · | · | · | · | · | · | · |  |
| `admin_user_view` | ✔ | · | · | · | · | · | ✔ | · |  |
| `admin_warehouse` | ✔ | · | · | ✔ | · | · | · | · |  |
| `admin_warehouse_move` | ✔ | · | · | · | ✔ | · | ✔ | · | โค้ดเดิมไม่ได้ตรวจ |
| `admin_warehouse_section` | ✔ | · | · | · | · | · | · | · |  |
| `admin_withdrawcash` | ✔ | · | · | · | · | · | ✔ | ✔ |  |
| `admin_withdrawpoint` | ✔ | · | · | · | · | · | · | · |  |
| `alipay` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `balance` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `balance_cn` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `export` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `findproduct` | · | · | · | · | · | · | · | · | ไม่มีใน role_detail (ไม่มีใครได้) |
| `goodsconfirm` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `lost_package` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `lot` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `mall` | · | · | · | · | · | · | · | · | โค้ดเดิมไม่ได้ตรวจ |
| `message` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `order` | ✔ | · | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `payment` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `point` | ✔ | · | · | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `profile` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `refill` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `shop_cashback` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `taobao` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `tracking` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `transfer` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |  |
| `view_bol` | · | · | · | · | · | · | · | · | ไม่มีใน role_detail (ไม่มีใครได้) |

ไม่ตรงกัน: 0 ช่อง

## 3. ตรวจซ้ำบน production ก่อนสลับ (อ่านอย่างเดียว)

```bash
grep -rhoE "getPermission(Alert(_Page)?)?\(\s*['\"][a-z_]+['\"]" /home/admin/bench/sr-old --include=*.php --exclude-dir=vendor \
  | grep -oE "['\"][a-z_]+['\"]" | tr -d "'\"" | sort -u > /tmp/sr_perm_names.txt
php8.4 tools/role-matrix.php /tmp/sr_perm_names.txt     # .env ชี้ DB ไหน = ตรวจ DB นั้น (ตอนนี้ admin_sr_bench)
```
```sql
-- ใครถือสิทธิ์อะไรใน production (admin_sr) — legacy ไม่กรอง role_detail.is_delete (SR มีค่า NULL 54 แถว)
SELECT d.role_detail_name, GROUP_CONCAT(DISTINCT o.role_id ORDER BY o.role_id)
FROM role_detail d LEFT JOIN role_on_detail o ON o.role_detail_id = d.id AND o.permission = 1 AND o.is_delete = 0
GROUP BY d.role_detail_name ORDER BY 1;
```
