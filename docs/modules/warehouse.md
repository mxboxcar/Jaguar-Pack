# กลุ่ม warehouse — รายงานการพอร์ต

## PACK (2026-10-22)

ทำใน worktree เดียวกับกลุ่ม tracking: branch `mod/tracking` · `/home/admin/laravel/pack-wt/tracking` · dev http://127.0.0.1:8801 · เทียบกับ PACK เดิม :8114 (`admin_pack_bench`)

### สรุป: ระบบรับเข้าโกดัง/ชั้นวางของ SR = dead ใน PACK — เหลือเฉพาะ goodsconfirm

ตรวจแล้ว 4 ทาง (ทุกข้อชี้ไปทางเดียวกัน):
1. `content.php` ของ PACK (เท่ากับ production) **ไม่มี** page `admin_warehouse`, `admin_warehouse_detail`, `admin_warehouse_section`, `admin_warehouse_section_detail`, `admin_warehouse_section_move` (SR มี)
   → URL `admin/warehouse`, `admin/warehouse/{id|create}`, `admin/warehouse_section(/{id|create})`, `admin/warehouse_section_move` บน :8114 (แอดมิน) ได้ **layout เปล่า** — ทั้ง 5 หน้าได้ HTML เดียวกันทุกไบต์ (md5 เท่ากัน) ไม่มีเนื้อหา
2. ตาราง `warehouse`, `warehouse_detail`, `warehouse_section`, `warehouse_section_detail` ไม่มีใน DB ของ PACK
3. สิทธิ์ `admin_warehouse` (role_detail 58) ไม่มี role ใดถือ → ajax `_admin/_warehouse/*` เดิมตอบ `ErrorBox('คุณไม่มีสิทธิ์...ผู้ดูแล - จัดการโกดัง')` กับทุกคนแม้แอดมิน (เทียบแล้ว: load_lot_detail, load_tracking); `admin_warehouse_move` ก็ไม่มีใครถือ
4. เมนู (menu.php / menu_mobile.php) ไม่มีลิงก์ไปหน้าเหล่านี้ และไม่มีหน้าใดเรียก `_admin/_warehouse*` (grid ที่ตรวจ `admin_lot`/`admin_warehouse_section` เปิดตรงได้แต่ได้ตารางว่างเพราะไม่มีตาราง)

→ ลบ route/controller/view/test ทั้งหมดของ `_admin/_warehouse`, `_admin/_warehouse_section`, `_admin/_warehouse_section_move` (`WarehouseController`, `WarehouseSectionController`, `ShelfAllocator` = การจัดชั้นวางของ SR/jl ใน save_lot_detail, `LotDetailRenderer`, views `warehouse/admin/{warehouse,section,section_move}`); URL เหล่านี้บน Laravel = 404 → หน้าแรก (เหมือนแนวทาง `admin/mall_stock` ของ SR) · `docs/url-map.md` ทำเครื่องหมาย dead แล้ว
ไฟล์ diff ของ pack-diff (ไม่ได้พอร์ตเพราะ dead): `_admin/_warehouse/form.php` (188), `grid.php` (27), `load_lot_detail.php` (31), `load_lot_detail_thai.php` (28), `load_tracking.php` (39), `load_tracking_edit.php` (276), `save_lot_detail.php` (577 — PACK เป็นแบบ jl: ชั้นวางตามพื้นที่ ไม่มีลูกค้าชั้นตายตัวของ SR), `save_push_track.php` (2), `save_track_weight.php` (21), `_admin/_warehouse_section/delete.php` (2) · SR-only: `load_editshelf.php`, `save_editshelf.php`
ถ้าวันหน้าเจ้าของต้องการระบบนี้ใน PACK ต้องสร้างตาราง + เพิ่ม page ใน content.php ก่อน (= งานใหม่ ไม่ใช่การพอร์ต) — โค้ด SR อยู่ในประวัติ git (ก่อน commit "warehouse: drop the shelf module PACK does not have")

### goodsconfirm (ทุกไฟล์ `same` กับ SR)

- ไม่ต้องแก้ logic — `nobody` = `config('legacy.nobody')` = `PACKnocode` (ฐานร่วม; bench มีผู้ใช้ 394 รหัสนี้)
- กล่องอัปโหลด: `App\Domain\Warehouse\UploadBox` เปลี่ยนเป็น markup รุ่นเก่าของ PACK (สำเนาเดียวกับของกลุ่ม tracking — ดู docs/modules/tracking.md)
- เทียบกับ :8114 ตรงกันทุกตัวอักษร: `/goodsconfirm`, `/goodsconfirm/46`, `_goodsconfirm/grid` (หน้า 1/2/สถานะ), `load_tracking_by_id` (ลูกค้า 348); `/admin/goodsconfirm` (+status/0), `/admin/goodsconfirm/{create,46,33,42}`, `_admin/_goodsconfirm/grid` (หน้า 1/2, สถานะ 0/2, ค้นหา), `load_tracking_by_id`, `load_upload`, `load_tracking` (autocomplete); role 6 → grid ว่างเหมือนเดิม
- ต่างตามที่ตั้งใจ (เดิมจาก SR/jl): `/goodsconfirm/33` (รายการ status 2 ของคนอื่น) เดิมแสดงฟอร์ม → ใหม่ไม่มีสิทธิ์; `load_tracking_by_id` ของ tracking ที่ลูกค้าไม่เกี่ยว = ไม่แสดงข้อมูล; หน้าไม่มีสิทธิ์แสดง layout + alert
- E2E (Laravel เท่านั้น, tracking `ZZE2E*G` ของผู้ใช้ PACKnocode): แอดมินสร้าง (ยิงซ้อน → 1 แถว + goodsconfirm_detail 1 แถว) → ลูกค้า 11937 ยืนยัน (status 1, ไฟล์แนบ) → ไม่อนุมัติ (0) → ยืนยันใหม่ → อนุมัติ (2, tracking.user = 11937) · รายการที่ 2 → แอดมินยกเลิก (-1, `ยกเลิกโดย ZZFDN2`) — ลบแล้ว (goodsconfirm 60–63, detail 51/52, tracking ที่สร้าง)

### Bench (`bin/bench -n 15 -c 1`, `artisan optimize`, load 7–9)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `_admin/_goodsconfirm/grid` | 146 | 47 |

### Test

- `tests/Feature/Warehouse/WarehouseRoutesTest.php`: route goodsconfirm + URL dead ของ PACK ไม่มี route, ไม่มี model `Warehouse`
- `tests/Feature/Warehouse/WarehousePackDbTest.php` (แทน WarehouseSrDbTest): goodsconfirm save ใช้ `PACKnocode`, grid ลูกค้าแสดงเฉพาะ status 0 — ลบ test จัดชั้นวาง (ShelfAllocator) ใน `WarehouseSrDbTest` และ `Money/AdminWritesDbTest`, ลบ route warehouse ออกจาก `SubmitGuardTest`

### คำถามถึงเจ้าของ

1. ยืนยันว่าระบบรับเข้าโกดัง/ชั้นวาง (admin/warehouse*) ไม่ใช้ใน PACK (หน้าเดิมเปิดได้แค่ layout เปล่า) — ถ้าจะใช้ ต้องสร้างตารางและเปิดหน้าใหม่
2. `_dashboard/index.php` (กลุ่ม reports/dashboard) ยังคิดพื้นที่ชั้นวางจาก `warehouse_section` ที่ไม่มีใน PACK

## SR (2026-10-20)

branch `mod/warehouse` · worktree `/home/admin/laravel/sr-wt/warehouse` · dev port 8309 · เทียบกับ :8111 (`/home/admin/bench/sr-old`, DB `admin_sr_bench`)

### ไฟล์ตาม docs/sr-diff.md

| ไฟล์เดิม | สถานะ | ผล |
|---|---|---|
| `_admin/_warehouse/save_lot_detail.php` | diff 357 | **ทำแล้ว** — `App\Domain\Warehouse\ShelfAllocator` (ดูด้านล่าง) |
| `_admin/_goodsconfirm/save.php` | diff 3 | **ทำแล้ว** — tracking ต้องเป็นของผู้ใช้ code `nobody` (SR `define('nobody','nobody')`; jl ใช้ `nocode` ที่ไม่ได้ define) |
| `_goodsconfirm/grid.php` | diff 12 | **ทำแล้ว** — แสดงเฉพาะ status 0 เสมอ, ไม่กรอง user, ไม่ตัดแถวที่ไม่มี tracking; ตัวกรองสถานะอื่น (ไม่ใช่ -2/ว่าง) = AND status = x → ไม่มีข้อมูล |
| `_goodsconfirm/form.php` | diff 5 | **คงของ jl** — SR ตัดการตรวจ "status != 0 และไม่ใช่ของตัวเอง → ไม่มีสิทธิ์" ออก (= IDOR) → ใหม่ยังปฏิเสธ (ต่างจากเดิมโดยตั้งใจ) |
| `_admin/_goodsconfirm/form.php` (same) | same | **แก้** — `getuploadmultiple()` ของ SR (อยู่ใน system_status_function.php) markup ต่างจาก jl → `App\Domain\Warehouse\UploadBox::multiple()` ใช้ markup ของ SR (สำเนาเดียวกับ `App\Domain\Tracking\UploadBox::multiple()` ของกลุ่ม tracking) ต่างแค่ไม่มี `target="_blank"` |
| ไฟล์ `same` อื่นทั้งหมด (34) | same | ไม่ต้องแก้ — เทียบผลกับ :8111 แล้วตรงกัน |
| `_admin/_section` | — | โฟลเดอร์ว่าง (เหมือน jl) |

ไม่มีไฟล์ SR-only / JL-only ในกลุ่มนี้ · `_admin/_warehouse_section/delete.php` ของ SR ได้ hotfix 2026-10-18 (intval id) — ใหม่ bind ค่าอยู่แล้ว

### save_lot_detail ของ SR (จัดชั้นวางอัตโนมัติ)

- **ลูกค้าชั้นตายตัว** (`ShelfAllocator::FIXED_SECTIONS`, user id → section id ฝังในโค้ดเดิม 30 กว่ารายการ เช่น 4→33, 1/2/2110/2392/2371→56, 3621→59) INSERT ชั้นนั้นทันทีโดยไม่สนพื้นที่ แล้วไม่เข้าการจัดอัตโนมัติ (แถวที่ comment ไว้ในไฟล์เดิมไม่ใส่)
- getCountCode นับ "รหัส" เป็นคู่ `user, user_agent` (`GROUP BY user, user_agent`; NULL เป็นกลุ่มของตัวเอง) — jl นับ user
- ไม่มีกติกา "ชั้นลงท้าย 4 ไม่รับของ ≥ 15 kg" ของ jl (เหลือเฉพาะลงท้าย 3) และไม่ echo ตัวท้ายชื่อชั้นระหว่างวน
- แถวที่จัดชั้น 1 / อัตโนมัติ ใส่ `warehouse_section_detail.user_agent` (คอลัมน์ SR) = `tracking.user_agent` ซึ่งไม่มีใน tracking → `''` → 0; แถว nobody/ชั้นตายตัวไม่ใส่ → default 0 (ผลเท่ากัน)
- คงการแก้ของ jl ทั้งหมด: bind `prints`, ข้าม tracking ที่จัดชั้นแล้ว (กดซ้ำไม่ซ้ำ), ล้าง warehouse_detail ของใบนี้ (ไม่ใช่ `WHERE id = <ใบรับเข้า>`), transaction, `legacy.once:lock`, โหลดพื้นที่/รหัสครั้งเดียว
- **แก้ (ต่างจากเดิมโดยตั้งใจ)**: เดิม SR ต่อผล query ที่ 2 (`user IN (753, 619, …)` = `REPEAT_USERS`) ท้ายรายการ tracking ทั้งหมดโดยไม่ล้างก่อน
  → tracking ของลูกค้าที่อยู่ทั้งใน REPEAT_USERS และ FIXED_SECTIONS ถูก INSERT ชั้นวาง **2 แถว** (ตารางผลลัพธ์แสดงแถวนั้นซ้ำ, ยอดรวม hid_sum_* นับซ้ำ, พื้นที่ชั้นนับซ้ำ)
  → ใหม่ INSERT แถวเดียวต่อ tracking; รายการ "จัดแล้ว" ใน print_r (`<pre>`) ยังซ้ำเหมือนเดิม
- ตรวจ 1:1 ด้วยการรันโค้ดเดิมของ SR (`sed -n '14,$p'` ของไฟล์ + helper เดิม) ด้วย php7.4 CLI ใน transaction เดียวที่ ROLLBACK เสมอ
  เทียบกับ `ShelfAllocator` (php8.4, fixture เดียวกัน, rollback) — 2 ชุด: (ก) ชั้น 6 ชั้น + ชั้นแรกมี 10 รหัส user เดียวคนละ user_agent (ใช้พื้นที่เกินครึ่ง),
  ลูกค้า 3/5/7/9/10/11/0/NULL, ชั้นตายตัว 4/1/2/1362, ของ ≥0.7 cbm, ≥50 kg, ≥15 kg; (ข) + ผู้ใช้ code nobody และ 2 ล็อต
  → แถว warehouse_section_detail (section, user, tracking, warehouse, user_agent, ขนาด, create_by) และ warehouse_detail **ตรงกันทุกแถว** เมื่อตัดแถวซ้ำของเดิม (เดิม 15/16 แถว = ใหม่ 12/13 + ซ้ำ 3);
  HTML ต่างเฉพาะแถวซ้ำ/ยอดรวม (ข้อบน) และ `<pre>` ต่างเฉพาะ tracking ที่จัดชั้นแล้ว (การแก้ของ jl)

### ผลเทียบกับเว็บเดิม (:8111 vs :8309, cookie เดียวกัน)

ข้อมูล bench ของ SR ไม่มี goodsconfirm / warehouse / warehouse_section เลย → ใส่แถว zz ชั่วคราว (lot 99001–2, tracking 990001–23, goodsconfirm 99001–15 ทุกสถานะ + แถวไม่มี tracking + remark ที่มี `<b>&"'`, goodsconfirm_detail, warehouse 12, warehouse_section 12, warehouse_section_detail 12 แถว) แล้วลบตาม id หลังทดสอบ (checksum ทุกตารางกลับเท่าเดิม)

- แอดมิน zz_fdn_admin 11938: `/admin/goodsconfirm` (+ status 0/1/2), create, form 6 id, grid ทุกสถานะ/ค้นหา/หน้า 2, load_tracking_by_id, load_tracking, load_upload;
  `/admin/warehouse`, form 2 id + create, grid (หน้า 1/2/ค้นหา/ตัวกรองประเภท), load_lot_detail, load_lot_detail_thai, load_tracking, load_tracking_edit, load_editshelf;
  `/admin/warehouse_section` + form + create + grid (หน้า 1/2/ค้นหา), `/admin/warehouse_section_move` + grid (หน้า 1/2/ค้นหา) — **ตรงกันทุกตัว** (ต่างแค่ target=_blank ที่เอาออก และ escape ค่า `<"'`)
- ลูกค้า 3 / 5 (role 8), 11 (role 7): `/goodsconfirm`, grid ทุกสถานะ/ค้นหา/หน้า 2, form ของรายการ status 0 และของตัวเอง, load_tracking_by_id — ตรงกัน
  ต่างโดยตั้งใจ: form/load_tracking_by_id ของรายการที่คนอื่นยึดแล้ว (ใหม่ = ไม่มีสิทธิ์ / ไม่แสดงข้อมูล tracking)
- ปฏิเสธสิทธิ์: user 6 (role 12: มี admin_goodsconfirm ไม่มี admin_warehouse/section), 11, 3 — ทุกหน้า/grid/ajax ตรงกัน ยกเว้นหน้าเดิมพิมพ์หัวหน้าก่อน alert (เดิมจาก jl)
- `/admin/goodsconfirm/status/-1` ไม่ตรง RewriteRule (`[0-9]+`) → 404 → หน้าแรก (เว็บจริงก็เข้าไม่ได้)

### E2E (Laravel เท่านั้น, แถว zz, ลบตาม id แล้ว)

- `save_lot_detail` (GET + X-Requested-With) ใบรับเข้า zz: ดับเบิลคลิกพร้อมกัน 2 คำขอ → คำขอที่ 2 ได้ ErrorBox "กำลังดำเนินการ", ชั้นตายตัว 4→33, 2→56, user NULL→ชั้น 2, user_agent = 0 ทุกแถว, ไม่มีแถวซ้ำ;
  กดซ้ำ = ไม่เพิ่มแถวชั้นวาง, warehouse_detail เดิมถูกปิดแล้วเพิ่มชุดใหม่ 1 แถว · `save_cancel_warehouse_calculate` → ปิดแถวชั้นวาง 11 แถว + warehouse_detail ของใบนี้
- ลูกค้า `save_approved` (ส่งซ้ำ 2 ครั้ง): status 1, user = ผู้ยึด, approved_upload, approved_remark '' (ครั้งที่ 2 ไม่เปลี่ยนอะไร — เดิมก็ตอบสำเร็จ), ลูกค้าอื่นยึดซ้ำ = ErrorBox สิทธิ์
- แอดมิน `_admin/_goodsconfirm/save` กับ tracking ของผู้ใช้ทั่วไป = ErrorBox "ยังไม่มีรหัสพัสดุ" (bench ไม่มีผู้ใช้ nobody); ทางสำเร็จทดสอบใน DB test (สร้างผู้ใช้ nobody ใน transaction)
- test: `tests/Feature/Warehouse/WarehouseSrDbTest.php` (JL_BENCH_DB_TESTS=1): ชั้นตายตัว INSERT ครั้งเดียว + print_r เดิม, นับรหัสตาม user_agent + ไม่มีกติกาลงท้าย 4, goodsconfirm save ใช้ nobody (nocode ไม่ได้), grid ลูกค้าแสดงเฉพาะ status 0

### Bench (`bin/bench -n 20`, artisan optimize, 2 รอบ)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/admin/goodsconfirm` (แอดมิน) | 340–354 | 35–39 |
| `/_admin/_goodsconfirm/grid` | 61–83 | 31–35 |
| `/admin/warehouse/{id}` | 374–550 | 39–48 |
| `/_admin/_warehouse/grid` | 76–83 | 33–34 |
| `/_admin/_warehouse/load_lot_detail` (2 ล็อต 23 แถว) | 23–24 | 27–28 |
| `/_goodsconfirm/grid` (ลูกค้า 3) | 45–46 | 31–34 |

load_lot_detail ช้ากว่าเดิมเล็กน้อย = overhead คงที่ของ Laravel (~20–30 ms) กับ ajax ที่เดิมเร็วมาก (query เดียว)

### ช่องโหว่ในโค้ดเดิมของ SR ที่เจอเพิ่ม (อ่านโค้ด — ไม่ได้ยิงใส่เว็บเดิม)

- `_goodsconfirm/form.php`: ลูกค้าคนใดก็ดูรายการที่คนอื่นยึดแล้วได้ (รวมไฟล์แนบหลักฐาน `approved_upload`) ด้วยการเดา id — ใหม่ปฏิเสธเหมือน jl
- `save_lot_detail.php`: แถวชั้นวางซ้ำสำหรับลูกค้าชั้นตายตัว (ข้อมูลผิด ไม่ใช่ความปลอดภัย) — แก้แล้ว; ที่เหลือเหมือน jl (SQL injection ใน `prints`/`id`, เขียนผ่าน GET)

### คำถามถึงเจ้าของ

- รายการลูกค้าชั้นตายตัว (FIXED_SECTIONS) และ REPEAT_USERS ฝังในโค้ดเหมือนเดิม — ถ้าต้องการให้แอดมินแก้เองได้ ต้องมีตาราง/ตั้งค่าใหม่ (ไม่ได้ทำ: ห้ามแก้ schema)
- ยืนยันการแก้แถวชั้นวางซ้ำของลูกค้าชั้นตายตัว (เดิม 2 แถวต่อ tracking) — ถ้าต้องการให้ซ้ำเหมือนเดิมจริง (เช่น มีรายงานที่พึ่งจำนวนแถว) แจ้งได้
- ยืนยันการคงการตรวจสิทธิ์ของ jl ใน `_goodsconfirm/form` (SR เดิมเปิดให้ดูรายการของคนอื่น)
- bench SR ไม่มีผู้ใช้ code `nobody` → `_admin/_goodsconfirm/save` และ autocomplete ใช้ไม่ได้บน bench (ต้องมีใน production) — ตรวจว่า production มีผู้ใช้นี้

### ผลรวม test

`php8.4 vendor/bin/phpunit` ผ่าน (313 tests, 108 skipped) · `JL_BENCH_DB_TESTS=1 php8.4 vendor/bin/phpunit` ผ่าน (313 tests, 2 skipped)

---

branch `mod/warehouse` · worktree `/home/admin/laravel/jl-wt/warehouse` · dev port 8209

โมดูลเดิม: `_goodsconfirm`, `_admin/_goodsconfirm`, `_admin/_warehouse`, `_admin/_warehouse_section`, `_admin/_warehouse_section_move`
(`_admin/_section` ในเว็บเดิมเป็นโฟลเดอร์ว่าง — ไม่มีอะไรให้พอร์ต)

## Routes (เดิม → ใหม่)

| เดิม | ใหม่ | สิทธิ์ |
|---|---|---|
| `?page=goodsconfirm` (`_goodsconfirm/index.php`) | `GET /goodsconfirm` | goodsconfirm |
| `?page=goodsconfirm_detail&id=` (`_goodsconfirm/form.php`) | `GET /goodsconfirm/{id}` | goodsconfirm + เจ้าของ/ไม่มีเจ้าของ |
| `_goodsconfirm/grid.php` | `POST /_goodsconfirm/grid` | ajax, goodsconfirm (ไม่มี = ตอบว่าง) |
| `_goodsconfirm/load_tracking_by_id.php` | `POST /_goodsconfirm/load_tracking_by_id` | ajax, goodsconfirm |
| `_goodsconfirm/save_approved.php` | `POST /_goodsconfirm/save_approved` | ajax, goodsconfirm |
| `?page=admin_goodsconfirm[&status=]` | `GET /admin/goodsconfirm`, `/admin/goodsconfirm/status/{status}` | admin_goodsconfirm |
| `?page=admin_goodsconfirm_detail&id=` | `GET /admin/goodsconfirm/{id}`, `/admin/goodsconfirm/create` | admin_goodsconfirm |
| `_admin/_goodsconfirm/{grid,load_tracking_by_id}.php` | `POST /_admin/_goodsconfirm/{grid,load_tracking_by_id}` | ajax, admin_goodsconfirm (เช็คใน controller แบบเดิม) |
| `_admin/_goodsconfirm/{cancel,load_tracking,load_upload,save,save_approved,save_image,save_unapproved}.php` | `POST /_admin/_goodsconfirm/...` | ajax, admin_goodsconfirm |
| `?page=admin_warehouse`, `admin_warehouse_detail` | `GET /admin/warehouse`, `/admin/warehouse/{id}`, `/admin/warehouse/create` | admin_warehouse |
| `_admin/_warehouse/grid.php` | `POST /_admin/_warehouse/grid` | ajax, admin_lot (เดิม; ไม่มี = ตอบว่าง) |
| `_admin/_warehouse/{load_editshelf,load_lot_detail,load_lot_detail_thai,load_tracking,load_tracking_edit,save_cancel_warehouse_calculate,save_editshelf,save_new_warehouse,save_push_track,save_track_weight}.php` | `POST /_admin/_warehouse/...` | ajax, admin_warehouse |
| `_admin/_warehouse/save_lot_detail.php` (JS เรียกด้วย GET) | `GET /_admin/_warehouse/save_lot_detail` | ajax (X-Requested-With บังคับ = กัน CSRF), admin_warehouse |
| `_admin/_warehouse/save.php` (สำเนา ไม่มีหน้าเรียก) | `POST /_admin/_warehouse/save` | ajax, admin_warehouse_section |
| `?page=admin_warehouse_section`, `_detail` | `GET /admin/warehouse_section`, `/{id}`, `/create` | admin_warehouse_section |
| `_admin/_warehouse_section/{grid,save}.php` | `POST /_admin/_warehouse_section/{grid,save}` | ajax, admin_warehouse_section |
| `_admin/_warehouse_section/delete.php` | `POST /_admin/_warehouse_section/delete` | ajax, **admin_product_type** (เหมือนเดิม) |
| `?page=admin_warehouse_section_move` | `GET /admin/warehouse_section_move` | admin_warehouse_section |
| `_admin/_warehouse_section_move/grid.php` | `POST /_admin/_warehouse_section_move/grid` | ajax, admin_warehouse_section |

`index.php` / `form.php` ของทุกโมดูลเป็นหน้า (ไม่ใช่ ajax) — พอร์ตเป็น route หน้าตาม RewriteRule

## ผลเทียบกับเว็บเดิม (:8101 vs :8209, cookie เดียวกัน)

- HTML ตรงกันทุกหน้า/ทุก ajax อ่าน (ต่างแค่ช่องว่าง) — ลูกค้า 137, zz_fdn_cust, แอดมิน zz_fdn_admin:
  ทุกหน้าใน routes, grid ทุกสถานะ/ค้นหา/หน้า 2–5, load_* ทั้งหมด (รวมข้อมูล zz ที่จัดชั้นวางแล้ว), ลูกค้าเรียก ajax แอดมิน (ตอบว่าง/ErrorBox เหมือนเดิม)
- **ทดสอบ endpoint ที่เขียน DB กับเว็บเดิมไม่ได้**: ทุกคำสั่งเขียนของเว็บเดิมเรียก `goLog()` ซึ่ง `fopen` log ไปที่ webroot production
  แล้ว `exit("Can't open ...")` ก่อนรันคำสั่ง (บน bench เปิดไม่ได้ จึงไม่มีอะไรถูกเขียน) → เทียบพฤติกรรมเขียนโดยอ่านโค้ดเดิม
  แล้วยิง endpoint ใหม่กับแถว `zz_wh_*` (lot, tracking, warehouse, warehouse_detail, goodsconfirm, warehouse_section)
  พร้อม checksum ของทุกแถวนอก scope zz ในตารางที่ถูกเขียน ก่อน/หลังทุกครั้ง (ไม่เปลี่ยน) แล้วลบข้อมูล zz ทั้งหมด
- ทดสอบแล้ว: cancel / save / save_approved / save_unapproved / save_image (แอดมิน), save_approved ลูกค้า (เจ้าของ/ไม่ใช่เจ้าของ/ไม่แนบไฟล์),
  save_new_warehouse, save_cancel_warehouse_calculate, save_push_track, save_track_weight (+CalPriceAutoDiff), save_lot_detail (จัดชั้นวาง: ชั้น 1 สำหรับ ≥0.7 cbm, ชั้นค้นหาตามพื้นที่, ข้ามชั้นลงท้าย 3/4 สำหรับ ≥15 kg),
  save_editshelf, warehouse_section save (ใหม่/แก้)/delete (id, arr_id), `_admin/_warehouse/save`
- payload injection ยิงเฉพาะ Laravel (grid, load_lot_detail, load_tracking_by_id, section delete/cancel ด้วย id ของแถว zz) — ไม่มีผล

## Bench (`bin/bench`, -n 20, แอดมิน)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/admin/goodsconfirm` | 301–411 | 46–50 |
| `/_admin/_goodsconfirm/grid` | 119–120 | 52 |
| `/_admin/_warehouse/load_lot_detail` (135 แถว) | 49–55 | 64 |

load_lot_detail ช้ากว่าเดิมเล็กน้อย = overhead คงที่ของ Laravel (~20–30 ms) — query เดิมเป็น query เดียวอยู่แล้ว

## ต่างจากเดิมโดยตั้งใจ

- **กันกดซ้ำ/ส่งซ้อน (double submit)** (เจ้าของอนุมัติ 2026-10-18, E2E: ดับเบิลคลิก `_taobao/save` = 2 รายการ ตัดเงิน 2 ครั้ง — เว็บเดิมก็เป็น): middleware `legacy.once` (`App\Support\SubmitGuard`) — lock ต่อ ผู้ใช้+route ระหว่างประมวลผล (คำขอที่ซ้อนได้ `ErrorBox('รายการนี้กำลังดำเนินการ กรุณารอสักครู่')`) + payload เดิมที่สำเร็จแล้วภายใน 8 วินาทีได้ `ErrorBox('รายการนี้ถูกส่งไปแล้ว (ส่งซ้ำ) ...')` (คำตอบ error/กล่อง PIN ไม่ถูกจำ ส่งใหม่ได้ทันที): `_admin/_goodsconfirm/save`, `_admin/_warehouse/save_new_warehouse` (lock อย่างเดียว) · test: `tests/Feature/SubmitGuardTest.php`, `tests/Feature/Money/DoubleSubmitDbTest.php` (JL_BENCH_DB_TESTS=1)
  - ชั้นนี้กันรายการซ้ำ; การกันยอดติดลบเมื่อคำขอต่างกันมาพร้อมกันยังเป็น transaction + `SELECT … FOR UPDATE` แถว user แล้วตรวจยอดภายใน (ทุก endpoint ตัดเงินตอนสร้าง)
- ลูกค้า `goodsconfirm`: `save_approved` / `load_tracking_by_id` ตรวจเจ้าของ (เดิมไม่ตรวจ: เขียนทับ `user` ของรายการที่อนุมัติให้คนอื่นแล้วได้ / ดูล็อต-วันที่ของ tracking ใดก็ได้)
  - `save_approved` ใช้เงื่อนไขเดียวกับหน้า form: ยึดได้เฉพาะรายการที่ยังไม่ถูกลบ (`is_delete = 0`) และ status 0 หรือเป็นของตัวเอง → รายการที่ยกเลิกแล้ว (status -1) หรือถูกลบ ยึดไม่ได้ (เดิมยึดได้ผ่าน POST ตรงๆ แม้หน้า form ไม่ให้เข้า)
- `save_track_weight`: JS `go_receive()` เดิมไม่ส่ง `tracking` → เดิมเขียนเลข tracking เป็น `''` (ข้อมูลหาย) — ใหม่: ไม่ส่งมา = ไม่แก้เลข tracking
- `save_cancel_warehouse_calculate`: ทำเฉพาะ id ที่เป็นจำนวนเต็มบวก (เดิม id ว่าง → `warehouse_id = ''` = ทุกแถวที่ warehouse_id 0)
- `save_push_track`: tracking ว่าง = ไม่พบข้อมูล (เดิมรับเข้าไทยให้แถวแรกที่เลข tracking ว่าง)
- `save_lot_detail`: `UPDATE warehouse_detail ... WHERE id = <warehouse id>` (ผิดคอลัมน์ → ลบแถวของใบรับเข้าอื่น) → แตะเฉพาะแถวที่ id และ warehouse_id ตรงใบนี้; ทั้งหมดอยู่ใน transaction; getSpace/getCountCode (2 query ต่อลูกค้า×ชั้น) โหลดครั้งเดียว
- grid ทุกตัว: query รูป/จำนวนต่อแถว (N+1) → batch query เดียวต่อหน้า (`firstImages()`, counts ของ warehouse)
- เขียนหลายตาราง (approve, save, track weight, cancel calc) ห่อ `DB::transaction()`
- หน้าที่เดิม `getPermissionAlert_Page()` + `exit()` กลางหน้า (HTML ขาดครึ่ง) → ใหม่แสดง layout ครบ ส่วนเนื้อหา/สคริปต์แจ้งเตือนเหมือนเดิม
- ค่าจาก DB ใน HTML ผ่าน escape (เดิม echo ดิบ = stored XSS)

## ช่องโหว่ในโค้ดเดิม (อ่านโค้ดเท่านั้น — ไม่ได้ยิง payload ใส่เว็บเดิม)

- SQL injection เกือบทุกไฟล์ (`$_POST` ต่อลง SQL ตรงๆ) — ร้ายแรงสุด:
  `_admin/_warehouse_section/delete.php` (`id IN ($id)` → `1) OR (1=1` = ปิดชั้นวางทุกชั้น),
  `_admin/_warehouse/save_cancel_warehouse_calculate.php` (`warehouse_id = '$id'` → ลบ warehouse_detail/warehouse_section_detail ทั้งตาราง),
  `_admin/_warehouse/save_lot_detail.php` (GET `prints` ใน `IN (...)` ของ INSERT/SELECT หลายตัว — เรียกด้วย GET จึงโดน CSRF ได้ด้วย),
  `save_editshelf.php`, `save_track_weight.php`, `save_push_track.php`, `_admin/_goodsconfirm/*`, `_goodsconfirm/*`
- IDOR ลูกค้า: `_goodsconfirm/save_approved.php` (ยึดรายการของคนอื่น), `_goodsconfirm/load_tracking_by_id.php`
- ไม่มี CSRF ทุก endpoint; `save_lot_detail` เขียน DB ผ่าน GET
- stored XSS: tracking/submit_remark/ชื่อชั้นวาง echo ดิบใน grid/form
- path traversal เล็กน้อย: `file_exists('../../upload/temp/'.$upload_file)` (ใหม่รับเฉพาะชื่อไฟล์)
- `_api/blockblob.php` มี credential ของ Azure Storage ฝังในโค้ด (ไม่ได้คัดลอกมา) — ควร rotate
- เว็บเดิมบน bench พยายามเขียน log ไป `/home/admin/web/portal.jlcargoth.com/public_html/logs/` (path production) ทุกคำสั่งเขียน

## ไม่ได้พอร์ต / ต้องให้เจ้าของตัดสินใจ

- `_admin/_warehouse_section/delete.php` ตรวจสิทธิ์ `admin_product_type` (ไม่ใช่ `admin_warehouse_section`) — คงไว้ตามเดิม
- `_admin/_warehouse_section_move/grid.php` ชี้ paging ไป `_admin/_warehouse_section_detail/grid` ที่ไม่มีอยู่ — คงไว้ (ปุ่มเปลี่ยนหน้าใช้ไม่ได้เหมือนเดิม)
- `_admin/_warehouse/grid.php` ตัวกรองประเภท (`lot.type` ที่ไม่มีใน query) เดิม SQL error → ใหม่ตอบ "ไม่มีข้อมูล" แบบเดิมโดยไม่ query
- อีเมล `email_approved.php` / `email_cancel.php` ไม่มีไฟล์ในเว็บเดิม → ไม่เคยส่ง (ใหม่ก็ไม่ส่ง)
- upload ไป Azure: เดิมตอบ JS ให้เบราว์เซอร์เรียก `_api/blockblob_ajax` (กลุ่ม settings) — ใหม่ตอบ JS เดิม ไม่เรียก Azure ฝั่ง server

## ไฟล์ฐานร่วมที่แก้ (commit แยก `foundation:`)

- `bootstrap/app.php`: เอา `TrimStrings` / `ConvertEmptyStringsToNull` ออก — เว็บเดิมไม่ trim/ไม่แปลง `''` เป็น null (ค่าที่ลง DB/แสดงผลต้องเป็นค่าดิบ)

## Review (adversarial, 2026-10-18)

- เทียบ HTTP จริง :8101 vs :8209 ซ้ำ (ลูกค้า 137 / zz_fdn_cust, แอดมิน zz_fdn_admin, role 4/5/7 ที่มีสิทธิ์บางส่วน, ภาษา th/en/cn): ทุกหน้า/grid/load_* ตรงกัน
  ต่างเฉพาะ (1) หน้าที่ไม่มีสิทธิ์: เดิมพิมพ์หัวหน้า (ชื่อ/ปุ่ม) ก่อน `getPermissionAlert_Page()` ใหม่แสดง layout + alert อย่างเดียว,
  (2) grid ที่ส่ง `page` ไม่ใช่ตัวเลข: ลิงก์ paging/`fn_public` ต่าง (ใหม่ตัดอักขระที่ไม่ใช่ตัวเลข = กัน XSS; ส่วน `Paging::getpaging` เป็นของฐานร่วม)
- `save_lot_detail`: เทียบการจัดชั้นวางกับโค้ดเดิมโดยรันไฟล์เดิมด้วย php7.4 CLI ใน transaction เดียวที่ ROLLBACK เสมอ (แทน `mysqlquerys` ด้วยตัวที่ใช้ connection เดียว, ไม่ผ่าน goLog)
  กับ tracking zz 12 แถว / ลูกค้า 10 คน (ชั้น A03 เต็ม 17 รหัสแล้วย้ายไป A04, ลูกค้า ≥50 kg ไปชั้น 1): ชั้นที่ได้ทุกแถวและ HTML ที่ตอบตรงกับเดิม
- แก้: ลูกค้า `save_approved` ยึดรายการที่ถูกลบได้ (ดูด้านบน)
- ยังเหมือนเดิม (แอดมินเท่านั้น): `save_lot_detail` ไม่มี `id` = บันทึกด้วย warehouse_id 0; `cancel`/`save_approved`/`save_unapproved` ไม่ตรวจ `is_delete`;
  ค่า id ที่เป็น string เช่น `5abc` ถูก MySQL แปลงเป็น 5 (bind แล้ว ไม่ใช่ injection)

## E2E admin write-flow (2026-10-18, fix/admin-writes)

- `save_lot_detail` กดคำนวณซ้ำ/ดับเบิลคลิก: `LEFT JOIN (SELECT id AS ct ... LIMIT 1) ... a.ct IS NULL` เดิมไม่ข้าม tracking ที่จัดชั้นแล้ว
  → `warehouse_section_detail` ซ้ำทุก tracking (E2E: 3 tracking → 9 แถว) และ `warehouse_detail` ซ้ำทุกล็อต (UPDATE เดิม `WHERE id = <ใบรับเข้า>` ไม่โดนแถวของใบนี้)
  → ข้าม tracking ที่มีแถวชั้นวาง (is_delete = 0) อยู่แล้ว, ล้าง `warehouse_detail` ของใบนี้ (warehouse_id = id, ไม่ทำเมื่อ id = 0) ก่อนบันทึกชุดล็อตที่เลือก,
  route ใช้ `legacy.once:lock` (คำขอซ้อน = ErrorBox) · ยกเลิกการคำนวณ (`save_cancel_warehouse_calculate`) แล้วคำนวณใหม่ได้เหมือนเดิม
- กันดับเบิลคลิก `_admin/_warehouse_section/save` (`legacy.once`)
- test: tests/Feature/Money/AdminWritesDbTest.php (JL_BENCH_DB_TESTS=1), `tests/Feature/SubmitGuardTest.php`
