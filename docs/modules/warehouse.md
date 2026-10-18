# กลุ่ม warehouse — รายงานการพอร์ต

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
