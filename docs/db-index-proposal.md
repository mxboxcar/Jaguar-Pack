# ข้อเสนอ index สำหรับ production (ยังไม่ได้ใช้ — รอเจ้าของอนุมัติ)

สถานะ: **ข้อเสนอ** — โค้ดใน `main` ไม่พึ่ง index นี้ (เร็วขึ้นแล้วโดยไม่มีมัน) ห้ามรันบน `admin_jl` จนกว่าเจ้าของสั่ง

## 1. `balance (user, is_delete, id, amount)`

```sql
-- production (admin_jl) — online DDL: ไม่ล็อกตาราง อ่าน/เขียนได้ระหว่างสร้าง
ALTER TABLE `balance`
  ADD INDEX `idx_balance_user_del_id_amount` (`user`, `is_delete`, `id`, `amount`),
  ALGORITHM=INPLACE, LOCK=NONE;

-- ย้อนกลับ
ALTER TABLE `balance` DROP INDEX `idx_balance_user_del_id_amount`, ALGORITHM=INPLACE, LOCK=NONE;
```

### ทำไม

grid บัญชีเงินบาท (`_balance/grid`, `_admin/_balance/grid`) ต้องแสดง "คงเหลือ" ของแต่ละแถว
= `SUM(amount)` ของรายการทั้งหมดของลูกค้าคนนั้นที่ `id` น้อยกว่า และ `is_delete = 0`

- index ที่มีตอนนี้ `idx_user (user)` ไม่มี `is_delete` / `amount` → MySQL ต้องเปิดแถวจริงทุกแถวของลูกค้า
  (ลูกค้า 137 มี 37,399 แถวทั้งบน production และ bench) ≈ 110–150 ms ต่อครั้งบนเครื่องนี้ แม้ข้อมูลอยู่ใน buffer pool
- index ใหม่ครอบทุกคอลัมน์ที่ใช้ (covering) → `SUM` / `COUNT` อ่านจาก index อย่างเดียวเป็นช่วง `(user, 0, id < x)`
- `GridQuery::attachPrevSums()` และ `GridQuery::liveCount()` (app/Support/GridQuery.php) ใช้ index นี้ได้ทันทีโดยไม่ต้องแก้โค้ด
  (ไม่มี index hint ในโค้ด — optimizer เลือกเอง)

### ผลที่วัดบน admin_jl_bench (170,239 แถว, เพิ่มชั่วคราวแล้วลบออก 2026-10-18)

| query (ลูกค้า 137) | ไม่มี index ใหม่ | มี index ใหม่ |
|---|---|---|
| `SUM(amount) WHERE user=137 AND is_delete=0 AND id < x` | 120–150 ms | 24–45 ms |
| `COUNT(*) WHERE user=137 AND is_delete=0` (แบบตรงๆ) | 135–155 ms | 17 ms |

| request (`bin/bench`, c=1, wt-serve + opcache) | เดิม PHP 7.4 | ใหม่ ไม่มี index | ใหม่ + index |
|---|---|---|---|
| `_balance/grid` ลูกค้า 137 หน้า 1 | 2,049 ms | 218 ms | 111 ms |
| `_admin/_balance/grid` หน้า 1 | 2,173 ms | 284 ms | 163 ms |

ลูกค้าทั่วไป (≤ 3k แถว) เร็วอยู่แล้วโดยไม่มี index — index นี้ช่วยลูกค้าที่มีประวัติยาวมาก (และหน้าแอดมินที่มีลูกค้าเหล่านั้นในหน้า)

### ต้นทุน

- ขนาด ≈ 40–50 byte/แถว × 614k แถว ≈ 25–30 MB
- สร้างบน bench 170k แถวใช้ 1.9 s → production 614k แถว ประมาณ 5–10 s (online, `LOCK=NONE`)
- ทุก INSERT/UPDATE ของ `balance` เขียน index เพิ่ม 1 ตัว (ตารางนี้เขียนไม่ถี่ — ต่อการเติม/จ่าย/ถอน)
- `idx_user (user)` จะซ้ำซ้อน (เป็น prefix ของ index ใหม่) — **ไม่เสนอให้ลบ** ในรอบนี้ (ความเสี่ยงต่ำกว่า); ลบทีหลังได้ถ้าต้องการลดต้นทุนเขียน

### ตรวจหลังสร้าง

```sql
EXPLAIN SELECT SUM(amount) FROM balance WHERE user = 137 AND is_delete = 0 AND id < 999999999;
-- key = idx_balance_user_del_id_amount, Extra มี "Using index"
```

## 2. `bill_payment (user, is_delete, status)` (ข้อเสนอ — ยังไม่ได้วัดบน bench)

ตาราง bill_payment มีแค่ PRIMARY, `total_status_ref_id`, `idx_notif (is_delete, status)` — **ไม่มี index `user`**
→ grid ลูกค้า (`_payment/grid`, `_export/grid`) และหน้า/รายงานที่กรองบิลของลูกค้าต้องสแกนทั้งตาราง (bench 31,638 แถว / 16.5 MB ≈ 100 ms ต่อครั้ง; production ใหญ่กว่า)
โค้ดใน main ไม่พึ่ง index นี้ (fix/bill-data ลดเหลือสแกนรอบเดียวต่อ request แล้ว) — index ช่วยให้เหลือแค่แถวของลูกค้าคนนั้น

```sql
ALTER TABLE `bill_payment` ADD INDEX `idx_bill_payment_user` (`user`, `is_delete`, `status`), ALGORITHM=INPLACE, LOCK=NONE;
-- ย้อนกลับ
ALTER TABLE `bill_payment` DROP INDEX `idx_bill_payment_user`, ALGORITHM=INPLACE, LOCK=NONE;
```

## 3. ที่ไม่เสนอ

- `refill`: grid ลูกค้า/แอดมินเร็วแล้วด้วย index เดิม (`idx_refill_user (user)` มี `id` อยู่แล้ว, `idx_notif (is_delete, status)`) — ใหม่ 64 / 74 ms
- `balance_cn`, `balance_company`, `bill_transfer`, `balance_withdraw`: ตารางเล็ก (≤ 4k แถว)

## หมายเหตุ bench

- `admin_jl_bench.balance` ไม่มีสถิติ index (cardinality 0, หลังโหลดข้อมูลตัวอย่าง) → รัน `ANALYZE TABLE balance` บน bench แล้ว (ไม่แก้ข้อมูล) ให้ optimizer เลือกแผนเหมือน production ที่มีสถิติอยู่แล้ว
- index ทดสอบ `zz_test_user_del_id_amount` ถูกลบออกจาก bench แล้วหลังวัด
