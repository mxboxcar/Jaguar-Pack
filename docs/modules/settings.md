# กลุ่ม settings — รายงานการพอร์ต

## PACK (2026-10-22)

ทำใน worktree เดียวกับกลุ่ม users (`/home/admin/laravel/pack-wt/users`, branch `mod/users`, port 8810) · เทียบกับ :8114 (`/home/admin/bench/pack-old`, DB `admin_pack_bench`)

### ไฟล์ตาม docs/pack-diff.md

| ไฟล์เดิม | สถานะ | ผล |
|---|---|---|
| `_admin/_setting/index.php` | diff 2 (jl 0) | **ทำแล้ว** — textarea `address_cn` มี class `addcomma` (แบบ jl) |
| `get_resource.php` | diff 2 (jl 0) | **ทำแล้ว** — PACK `require system_config.php` → ข้อความทุกตัวของภาษาตาม cookie `pack-cargo-lang` (แบบ jl; SR = จีนเสมอ) — byte ตรงกับเดิมทั้ง th/en/cn |
| `system_ckupload.php` | diff 35 | **ทำแล้ว** — ต้องมีสิทธิ์ `admin_notice` หรือ `admin_mall` (ไม่มี = `callFunction(fn, '', 'No permission.')`) — SR ยอมทุกคนที่ล็อกอิน |
| `system_upload.php` | diff 44 (jl 44) | **ทำแล้ว** — รายการนามสกุลของ PACK (ตรงตัวพิมพ์): jpg/png/jpeg/JPG/JPEG/PNG/pdf/PDF/zip/rar/7z/doc/docx/DOC/DOCX/xls/xlsx/XLS/XLSX, ชื่อใหม่ `randd().'.'.<นามสกุลเดิม>`, ข้อความ error ของ PACK (`error : รองรับ .png, .jpg, .jpeg, .pdf, .zip, .rar, .7z เท่านั้น`, `error: เกิดข้อผิพลาดในการอัพโหลดไฟล์`), ย่อรูป |
| `resource.php` | same | ตรง (th, `admin_email` ไม่ถูก define แบบเดิม) |
| ไฟล์ `same` อื่น (exchange, product_type, shipping_type, setting/save, get_price, index_close, load_quick_search, system_upload_2) | same | ไม่ต้องแก้ — เทียบผลกับ :8114 แล้วตรง |

ไม่มี SR-only / pack-only ในกลุ่มนี้

### getupload() ของ PACK (App\Domain\Settings\UploadBox — ของกลาง)
- PACK ยังใช้ `getupload()` / `getupload_inline()` รุ่นเก่า (แบบ jl ก่อนแก้ UI): รูป = นามสกุลใน `jpg,png,jpeg,JPG,JPEG,PNG` (ตรงตัวพิมพ์), ไฟล์อื่น = ลิงก์ `class='fancybox'` ชื่อไฟล์; หลังอัปโหลด `.zip/.rar/.7z` แสดงชื่อไฟล์ อื่นๆ เป็นรูปตัวอย่างจาก `upload/temp`
- `UploadBox::getupload()` / `getuploadInline()` เปลี่ยนเป็นรุ่น PACK (ไม่มี target="_blank", ชื่อไฟล์ escape); `getuploadmultiple()` ปุ่มลบ onclick = `$(this).parent().remove()` แบบ PACK
- `App\Domain\Users\UploadBox::single()` (กล่องแนบบัตรหน้าสมัคร) เรียกของกลางตัวนี้ — หน้า register ตรงกับเดิม (ต่างแค่ target/favicon)
- กลุ่มอื่นยังมีสำเนาของตัวเอง (points/reports ทำรุ่น PACK แล้ว) — รวมเป็นตัวเดียวภายหลัง

### ต่างจาก PACK เดิมโดยตั้งใจ
- `system_upload`: นามสกุลรูป/PDF ต้องเป็นรูป JPEG/PNG หรือ PDF จริง (finfo — การแก้ของ jl/SR) ไม่งั้นตอบ error รายการนามสกุล; ชนิดจริงไม่ตรงนามสกุล (เช่น PNG ชื่อ .jpg) ใช้นามสกุลตามชนิดจริง; zip/rar/7z/doc/docx/xls/xlsx ไม่ตรวจเนื้อไฟล์ (แบบเดิม); ต้องล็อกอิน (หรือมาจากหน้าสมัคร) — เดิมไม่ตรวจ
- `system_ckupload`: ไม่ล็อกอิน = redirect /login (เดิม script "No permission."); เก็บผ่าน BlobStore เสิร์ฟที่ `href_blob` (เดิมเขียน webroot `upload/`)
- `_admin/_setting/save`: อัปเดตเฉพาะคีย์ที่ส่งมา (การแก้ของ jl/SR — PACK เดิมเขียน `''` ทับทุกคีย์ที่ไม่ส่ง) และต้องมี `admin_setting` (เดิมไม่ตรวจ) — PACK: role 1 และ 7 ถือ admin_setting
- ข้อที่เหลือของ SR/jl ด้านล่างยังอยู่ทั้งหมด (legacy.once ของ product_type/shipping_type, lock ของ exchange, LIKE escape, escape ค่าจาก DB)

### ผลเทียบ (:8114 vs :8810, normalize href + ช่องว่าง)
- แอดมิน 11938: admin/setting, product_type (+create, /1, /99999), shipping_type (+create, /1, /13, /62), exchange (+status/1, 2, /18, /1) — ต่างแค่ favicon, morris/dashboard.js ของ layout, target="_blank"
- staff: role 4 (302: ไม่มี setting/shipping_type), role 7 (923: ไม่มี exchange), role 3 (524), role 6 (5), ลูกค้า — หน้าที่ไม่มีสิทธิ์ = no-permission (ตั้งใจ), grid ที่ไม่มีสิทธิ์ = ว่างเหมือนเดิม
- ajax: product_type grid ×2, shipping_type grid ×3, exchange grid ×3, load_cal_1/2 ×5, get_price (view_bol ไม่มีใครถือ = ErrorBox สิทธิ์), load_quick_search ×3 — ตรง (ต่างแค่ target)
- สาธารณะ th/en/cn: get_resource, resource = byte ตรง; index_close ต่างแค่ favicon

### E2E (Laravel เท่านั้น, แถว zz_ แล้วลบตาม id, snapshot ก่อน)
- system_upload: png/jpg/zip/PNG ผ่าน (นามสกุลตามที่ส่ง), PHP ชื่อ .jpg และ .Jpg = error, ไม่ล็อกอิน = 302 /login
- system_ckupload: แอดมิน, role 4, role 7 ได้ URL `https://image.pack-cargo.net/upload/<time>_<16>.png`; role 3 และลูกค้า = "No permission."
- setting: บันทึกทั้งฟอร์มด้วยค่าเดิม = ไม่มีแถวเปลี่ยน, คีย์เดียว = เปลี่ยนแถวเดียว, POST ว่าง = ErrorBox, ลูกค้า = ปฏิเสธ, role 7 = บันทึกได้ → คืนค่า = ตรง snapshot ทุกแถว
- product_type: สร้าง (ส่งซ้อน = แถวเดียว + "ส่งซ้ำ"), แก้, ลบ (`arr_id=,<id>` แบบเดิม)
- shipping_type: สร้าง (ส่งซ้อน = แถวเดียว), save_new_price_by_kg/cubic (+ max ≤ min = ErrorBox), load_cal_1, แก้เป็น is_use_rate 1 + rate_id[], save_listno, delete_cal, delete
- exchange (balance_withdraw_cn zz 2 แถวของ 11937): อนุมัติ 10 หยวน ส่งซ้อน 2 ครั้ง → balance +42.30 (× 4.23) แถวเดียว; ยกเลิก 20.50 ส่งซ้อน → balance_cn +20.50 แถวเดียว + "ยกเลิกโดย ZZFDN2"; อนุมัติรายการที่ยกเลิกแล้ว = ErrorBox
- ลบแล้ว: product_type 10, shipping_type 76 + shipping_type_rate 11–12, balance_withdraw_cn 27–28, balance 65076, balance_cn 2263, ไฟล์ทดสอบใน storage/app/legacy-upload

### bench (`bin/bench -n 30 -c 1`, แอดมิน, `artisan optimize`, 2 รอบ — :8114 มีงานของ agent อื่นพร้อมกัน ตัวเลขเดิมสูงกว่าปกติ)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| /admin/setting | 5191 / 2283 | 117 / 79 |
| /_admin/_shipping_type/grid | 1262 / 287 | 63 / 50 |

### test
- `tests/Feature/Settings/SettingsUploadTest.php`: system_ckupload ต้องมี admin_notice/admin_mall, system_upload รายการนามสกุล PACK

### คำถามถึงเจ้าของ
1. `system_upload` ยังตรวจเนื้อไฟล์รูป/PDF (การแก้ของ jl/SR) แต่รับ zip/rar/7z/doc/docx/xls/xlsx ตาม PACK โดยไม่ตรวจเนื้อไฟล์ — ต้องการจำกัดเฉพาะรูป/PDF แบบ jl/SR หรือไม่
2. `getupload()` ใช้หน้าตารุ่น PACK (PDF ที่อัปโหลดแสดงเป็นรูปเสีย/fancybox ว่างแบบเดิม) — ต้องการรุ่นที่แก้ UI แล้วของ jl/SR หรือไม่
3. รูปจาก CKEditor ใหม่อยู่ที่ `image.pack-cargo.net/upload/` (Azure) แทน `portal.pack-cargo.net/upload/` — production ต้องให้ nginx เสิร์ฟ `upload/` เดิมต่อ (รูปเก่าในประกาศ)

## SR (2026-10-20)

branch `mod/settings` · worktree `/home/admin/laravel/sr-wt/settings` · dev port 8313 · เทียบกับ :8111 (`/home/admin/bench/sr-old`, DB `admin_sr_bench`)

### ไฟล์ตาม docs/sr-diff.md

| ไฟล์เดิม | สถานะ | ผล |
|---|---|---|
| `_admin/_setting/index.php` | diff 2 | **ทำแล้ว** — textarea `address_cn` ไม่มี class `addcomma` |
| `get_resource.php` | diff 2 | **ทำแล้ว** — SR `require resource/cn.php` ตรงๆ → ข้อความภาษาจีนเสมอ (jl: ตาม cookie); ค่าคงที่ `admin_email` ใน `unapproved_user` ไม่ได้ define (ไม่โหลด system_config) → พิมพ์ `admin_email` ตามเดิม — byte ตรงกับเดิมทุกภาษา cookie |
| `index_close.php` | diff 2 | **ทำแล้ว** — เวลาเปิดบริการ 02:00 น. |
| `resource.php` | SR-only | **พอร์ตใหม่** — `GET /resource` (สาธารณะเหมือนเดิม, `PUBLIC` ใน RouteSecurityTest — commit `foundation:` แยก) ข้อความภาษาไทยทุกตัวต่อด้วย `<br />` — byte ตรงกับเดิม |
| `system_ckupload.php` | SR-only | **พอร์ตใหม่** — `POST /system_ckupload` (ดูด้านล่าง) |
| `_admin/_product_type/delete.php`, `_admin/_shipping_type/{delete,delete_cal}.php` | same (+hotfix SR 2026-10-18 intval) | ไม่ต้องแก้ — ใหม่รับเฉพาะรายการเลขจำนวนเต็มอยู่แล้ว |
| ไฟล์ `same` อื่น (23) | same | ไม่ต้องแก้ — เทียบผลกับ :8111 แล้วตรงกัน |

ไม่มี JL-only · `gateway.php` ไม่มีใน SR (ตัดออกในฐานร่วมแล้ว ไม่มี route)

### system_ckupload (SR-only)

- เดิม: CKEditor (`assets/ckeditor/config.js` `filebrowserUploadUrl = rooturl + 'system_ckupload'` — ใช้ในฟอร์มประกาศ `_admin/_notice`) ส่ง form multipart ช่อง `upload` เข้า iframe + GET `CKEditorFuncNum`;
  สิทธิ์ = admin_notice หรือ admin_mall หรือใครก็ได้ที่ล็อกอิน (= ผู้ที่ล็อกอินทุกคน); jpg/jpeg/png/gif ตามนามสกุล, 1 byte–10 MB, `getimagesize` ต้องเป็น JPEG/PNG/GIF,
  ชื่อใหม่ `<time>_<randd 16>.<นามสกุลตามชนิดจริง>` ย้ายลง webroot `upload/` ตอบ `<script>window.parent.CKEDITOR.tools.callFunction(fn, 'url', 'message')</script>`
- ใหม่: `UploadController::ckupload` ตรวจและตอบข้อความเหมือนเดิมทุกข้อ (No file. / Bad size. / Invalid. / Only JPG/PNG/GIF. / Not an image. / Move failed.)
  route `legacy.auth` (ไม่ใช้ `legacy.ajax` เพราะไม่ใช่ XHR; CSRF = LegacyCsrf ตรวจ Origin/Referer — ต่างโดเมน = 403)
- **ต่างจากเดิมโดยตั้งใจ**: เก็บไฟล์ผ่านทางเดียวกับไฟล์อัปโหลดอื่น — temp_dir → `BlobStore::uploadFromTemp()` (dev = `legacy.upload.local_dir`, production = Azure container upload, Content-Type รูปจริง) แล้วลบไฟล์ temp;
  URL ที่ตอบ = `href_blob` (`https://image.srshippingsystem.com/upload/<ชื่อ>`) แทน `https://portal.srshippingsystem.com/upload/<ชื่อ>` (Laravel ไม่เขียนลง webroot เดิม; ไม่มีไฟล์ที่ไม่ใช่รูปถูกเก็บ/เสิร์ฟ ชื่อไฟล์สร้างเองล้วน)
  · ไม่ล็อกอิน = redirect /login (เดิมตอบ script "No permission.") · ข้อความใน script escape `\ ' < CR LF` (เดิม escape ผิด แต่ค่าไม่เคยมีอักขระพวกนี้)
- E2E (HTTP จริง, multipart): แอดมินและลูกค้า 3 อัป GIF ได้ URL blob + ไฟล์ลง local_dir (ลบแล้ว), ไฟล์ PHP ชื่อ .jpg = "Not an image.", ไม่ล็อกอิน = 302 /login, Origin ต่างโดเมน = 403

### UploadBox (helper ที่ settings เป็นเจ้าของ — review ที่ jl ค้างไว้)

- SR `getupload()` / `getupload_inline()` เหมือน jl (เทียบโค้ดแล้ว); `getuploadmultiple()` ของ SR อยู่ใน system_status_function.php และต่างจาก jl
  → `App\Domain\Settings\UploadBox::getuploadmultiple()` ใช้ markup ของ SR (ผลเท่ากับ `App\Domain\Tracking\UploadBox::multiple()` ที่กลุ่ม tracking เทียบกับ :8111 แล้ว — ตรวจด้วยสคริปต์ 4 กรณี: ตรงกันทุกตัว) ไม่มี target=_blank
- ยังไม่มีใครเรียก `App\Domain\Settings\UploadBox` (ทุกกลุ่มมีสำเนาของตัวเอง) — รวมเป็นตัวเดียวภายหลัง

### `_admin/_setting/save` — การตัดสินใจ (ให้เจ้าของยืนยัน)

- SR เดิมมีปัญหาเดียวกับ jl: `save.php` ของ SR เหมือน jl ทุกบรรทัด → อ่าน `$_POST` ทั้ง 16 คีย์ แล้ว UPDATE ทุกคีย์ — คีย์ที่ไม่ได้ส่งมา = `''` ทับ (POST ที่ไม่มีช่องครั้งเดียว = ล้างอัตราแลกเปลี่ยน/เลขบัญชี/ที่อยู่โกดังจีนทั้งหมด) และไม่ตรวจสิทธิ์
- จึงคงการแก้ของ jl ไว้: **อัปเดตเฉพาะคีย์ที่อยู่ใน POST** (ค่าว่างที่ส่งมายังบันทึก `''`), ไม่มีคีย์เลย = ErrorBox ไม่แก้อะไร, ต้องมีสิทธิ์ admin_setting — ฟอร์มจริงส่งครบทุกช่อง ผลจึงเหมือนเดิม
- E2E (snapshot ทั้งตาราง `setting` ก่อน แล้ว diff): บันทึกทั้งฟอร์มด้วยค่าเดิม = ไม่มีแถวเปลี่ยน; ส่ง `phone_th` อย่างเดียว (2 ครั้ง) = เปลี่ยนแถวเดียว; POST ว่าง = ErrorBox ไม่เปลี่ยน; คืนค่าด้วยฟอร์มเต็ม = ตรงกับ snapshot ทุกแถว

### product_type.thaiword (คอลัมน์ SR)

- โค้ดเดิมของ SR **ไม่มีไฟล์ใดใช้** `thaiword` (grep ทั้ง webroot) — ไม่แสดง ไม่บันทึก; INSERT เดิมไม่ระบุคอลัมน์ → NULL, UPDATE ไม่แตะ
- ใหม่เหมือนเดิม: E2E สร้างแถว zz → thaiword NULL, ตั้ง thaiword แล้วแก้ผ่านฟอร์ม → ค่าเดิมคงอยู่

### ผลเทียบกับเว็บเดิม (:8111 vs :8313, cookie เดียวกัน)

- แอดมิน 11938: `/admin/setting`, `/admin/product_type` (+create, id 1, id ไม่มีจริง), `/admin/shipping_type` (+create, id 1/3/4/13/30/77), `/admin/exchange` (+status 1/2, form 2 id),
  grid 3 ตัว (หน้า 1/2/8, page ว่าง, ค้นหา, สถานะ), load_cal_1/2 (id ต่างๆ/ว่าง), get_price, index_close, get_resource/resource (th/en/cn) — **ตรงกันทุกตัว** (index_close ต่างแค่ favicon ของเจ้าของ)
- exchange: bench ไม่มีแถว → ใส่ balance_withdraw_cn zz 3 แถวชั่วคราว (ลบแล้ว)
- quick search 13 รหัส × 2 current_href × (ลูกค้า 3, แอดมิน): แอดมินตรงกันทุกกรณี; ลูกค้าต่างเฉพาะกรณี IDOR ที่ jl ปิดไว้ (โหมดแอดมินจาก `admin` ใน href / `sa-` โดยไม่มีสิทธิ์)
- ปฏิเสธสิทธิ์: user 6 (role 12: admin_exchange/admin_shipping_type ไม่มี admin_setting/product_type), 11 (role 7), 3 (ลูกค้า) — ตรงกัน ยกเว้นหน้าเดิมพิมพ์หัวหน้าก่อน alert (เดิมจาก jl)

### E2E (Laravel เท่านั้น — snapshot `setting`, `product_type`, `shipping_type`, `shipping_type_rate`, `balance_withdraw_cn` ทั้งตารางก่อน, ลบแถว zz ตาม id, diff หลัง = ตรงกันทุกตาราง)

- product_type: สร้าง zz (ดับเบิลคลิก **เดิมได้ 2 แถว** → เพิ่ม `legacy.once` ให้ `_admin/_product_type/save` และ `_admin/_shipping_type/save` ตามการตัดสินใจของเจ้าของเรื่อง double-submit; หลังแก้: คำขอซ้อน = "กำลังดำเนินการ", ส่งซ้ำใน 8 วินาที = "ส่งซ้ำ"), แก้, ลบ id / arr_id
- shipping_type: สร้าง (ดับเบิลคลิก = แถวเดียว), save_new_price_by_kg/cubic (+ error ช่องว่าง, max ≤ min), load_cal_1, แก้เป็น is_use_rate 1 + rate_id[] (price_extened → 0, ไม่แตะ mod_date/mod_by แบบเดิม), save_listno, delete_cal, delete
- exchange: อนุมัติ 10 หยวน (ดับเบิลคลิก) → balance +49.00 (×4.90) แถวเดียว, ยกเลิก (ดับเบิลคลิก) → balance_cn +20.50 แถวเดียว + cancel_remark "… ,ยกเลิกโดย <code>", อนุมัติรายการที่ยกเลิกแล้ว = ErrorBox, ยกเลิกรายการที่อนุมัติแล้ว = ไม่เปลี่ยน (การแก้ของ jl) — `admin_withdrawpoint` ใน SR มี role 1 ถือ จึงยกเลิกได้จริง (ใน jl ไม่มีใครถือ)
- setting / system_ckupload: ดูหัวข้อด้านบน

### Bench (`bin/bench -n 20`, แอดมิน, artisan optimize, 2 รอบ)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/admin/setting` | 377–384 | 38–40 |
| `/admin/shipping_type` | 369–411 | 34–57 |
| `/_admin/_shipping_type/grid` | 40–52 | 29–47 |
| `/_admin/_product_type/grid` | 80–99 | 41–47 |
| `/get_resource` | 0.5–1.2 | 17–21 |

get_resource ช้ากว่าเดิม: ไฟล์เดิม include ไฟล์ภาษาไฟล์เดียวแล้ว echo (ไม่มี session/DB) — ใหม่มี overhead คงที่ของ Laravel (boot + middleware group legacy ~17 ms)

### ช่องโหว่/ข้อสังเกตในโค้ดเดิมของ SR (อ่านโค้ด — ไม่ได้ยิงใส่เว็บเดิม)

- `_admin/_setting/save.php` เหมือน jl: ไม่ตรวจสิทธิ์ + ล้างทุกคีย์เมื่อไม่ส่งมา (ข้างบน)
- `system_ckupload.php` เขียนลง webroot `upload/` ตรงๆ (ปลอดภัยพอสมควร: ตรวจนามสกุล+getimagesize, ชื่อสุ่ม) แต่ escape ข้อความใน script ผิด (`'` → `\\'` ปิด string) — ไม่มีผลเพราะค่าคงที่
- กดบันทึกซ้อนใน product_type / shipping_type = แถวซ้ำ (แก้ด้วย legacy.once)

### คำถามถึงเจ้าของ

1. ยืนยัน `_admin/_setting/save` อัปเดตเฉพาะคีย์ที่ส่งมา (SR เดิมเขียน `''` ทับทุกคีย์ที่ไม่ส่ง)
2. รูปจาก CKEditor ใหม่เสิร์ฟจาก `image.srshippingsystem.com/upload/` (Azure) แทน `portal.srshippingsystem.com/upload/` — รูปเก่าในประกาศยังชี้ portal/upload (ต้องให้ nginx เสิร์ฟ `upload/` ของ webroot เดิมต่อหลังสลับ)
3. ผู้ที่ล็อกอินทุกคน (รวมลูกค้า) อัปรูปผ่าน system_ckupload ได้ตามเดิม — ต้องการจำกัดเฉพาะ admin_notice หรือไม่
4. product_type.thaiword ไม่มีโค้ดใช้ — ต้องการแสดง/แก้ในฟอร์มหรือไม่

### ผลรวม test

`php8.4 vendor/bin/phpunit` ผ่าน (310 tests, 104 skipped) · `JL_BENCH_DB_TESTS=1 php8.4 vendor/bin/phpunit` ผ่าน (310 tests, 2 skipped)

---

Worktree `/home/admin/laravel/jl-wt/settings` (branch `mod/settings`), dev server port 8213.

## Route ที่พอร์ต (URL เดิม → route ใหม่)

| เดิม | route ใหม่ | สิทธิ์ / middleware |
|---|---|---|
| `admin/setting` (`?page=admin_setting`, `_admin/_setting/index.php`) | `GET /admin/setting` | `legacy.perm:admin_setting` |
| `_admin/_setting/save.php` | `POST /_admin/_setting/save` | `legacy.ajax`, `legacy.perm:admin_setting` (เดิมไม่ตรวจสิทธิ์) |
| `admin/exchange`, `admin/exchange/status/N` | `GET /admin/exchange`, `GET /admin/exchange/status/{status}` | `legacy.perm:admin_exchange` |
| `admin/exchange/N` (`_admin/_exchange/form.php`) | `GET /admin/exchange/{id}` | `legacy.perm:admin_exchange` |
| `_admin/_exchange/grid.php` | `POST /_admin/_exchange/grid` | `legacy.ajax`, `legacy.auth` + `perm('admin_withdrawcash')` ใน controller (เดิม exit เงียบ) |
| `_admin/_exchange/save_approved.php` | `POST /_admin/_exchange/save_approved` | `legacy.ajax`, `legacy.perm:admin_exchange` |
| `_admin/_exchange/cancel.php` | `POST /_admin/_exchange/cancel` | `legacy.ajax`, `legacy.perm:admin_withdrawpoint` (ชื่อโมดูลตามไฟล์เดิม) |
| `admin/product_type`, `/create`, `/N` | `GET /admin/product_type`, `/admin/product_type/create`, `/admin/product_type/{id}` | `legacy.perm:admin_product_type` |
| `_admin/_product_type/grid.php` | `POST /_admin/_product_type/grid` | `legacy.ajax`, `legacy.auth` + perm ใน controller |
| `_admin/_product_type/save.php`, `delete.php` | `POST /_admin/_product_type/save`, `/delete` | `legacy.ajax`, `legacy.perm:admin_product_type` |
| `admin/shipping_type`, `/create`, `/N` | `GET /admin/shipping_type`, `/create`, `/{id}` | `legacy.perm:admin_shipping_type` |
| `_admin/_shipping_type/grid.php` | `POST /_admin/_shipping_type/grid` | `legacy.ajax`, `legacy.auth` + perm ใน controller |
| `_admin/_shipping_type/{save,save_listno,delete,delete_cal,load_cal_1,load_cal_2,save_new_price_by_kg,save_new_price_by_cubic}.php` | `POST /_admin/_shipping_type/<ชื่อเดียวกัน>` | `legacy.ajax`, `legacy.perm:admin_shipping_type` |
| `load_quick_search.php` | `POST /load_quick_search` | `legacy.ajax`, `legacy.auth` (เดิมไม่ตรวจล็อกอิน) |
| `get_price.php` | `POST /get_price` | `legacy.ajax`, `legacy.perm:view_bol` (ไม่มี role ใดมี view_bol = ปฏิเสธทุกคนเหมือนเดิม) |
| `system_upload.php`, `system_upload_2.php` | `POST /system_upload`, `POST /system_upload_2` | `legacy.ajax`, `legacy.auth` (เดิมไม่ตรวจล็อกอิน) |
| `gateway.php`, `index_close.php`, `get_resource.php` | `GET /gateway`, `GET /index_close`, `GET /get_resource` | สาธารณะ (เพิ่มใน `PUBLIC` ของ RouteSecurityTest — commit foundation แยก) |

helper ที่ใช้ร่วม (CONVENTIONS ข้อ 8 "ยังไม่พอร์ต" ที่ settings เป็นเจ้าของ):

- `getupload()`, `getupload_inline()`, `getuploadmultiple()` → `App\Domain\Settings\UploadBox::getupload() / getuploadInline() / getuploadmultiple()` (HTML/JS ตรงเดิม, ชื่อไฟล์ escape)
- `getproducttype()` → `App\Domain\Settings\ProductTypeSelect::getproducttype()` (เดิมใช้คอลัมน์ `product_type.ship_type` ที่ไม่มีจริง → `<select>` ว่างเสมอ; ไม่มีไฟล์ไหนเรียกแล้ว)
- `_api/image_shrink.php` → `App\Domain\Settings\ImageShrink` (สำเนาจากของกลุ่ม shop — ผู้ merge เลือกตัวเดียวได้)

## ไม่ได้พอร์ต / ตั้งใจให้ต่าง

- เรียกไฟล์หน้าโมดูลตรงๆ (`/_admin/_setting/index`, `/_admin/_exchange/form` ฯลฯ ในตาราง url-map): เดิมไฟล์พวกนี้ไม่ include `system_config.php` เอง → fatal error เมื่อเรียกตรง ใช้ได้เฉพาะผ่าน `index.php?page=` ซึ่งพอร์ตเป็น route หน้าแล้ว
- `/upload/temp/<file>` (ภาพตัวอย่างหลังอัปโหลด): เป็นของ nginx/infra (RewriteRule `^upload/`) — production ต้องให้ nginx ชี้ `upload/temp` ไปที่ `LEGACY_UPLOAD_TEMP_DIR`
- ค่าจาก DB/ผู้ใช้ escape ด้วย `{{ }}` (เดิม echo ดิบ): เห็นต่างเฉพาะข้อความที่มี `& " < >` เช่น `J&T` → `J&amp;T`, ข้อความ setting ใน textarea (`"` → `&quot;`) — แสดงผลเหมือนเดิม และกดบันทึกซ้ำแล้วค่าไม่เพี้ยน (เดิม `&amp;` ใน DB จะกลายเป็น `&`)
- LIKE ในช่องค้นหา grid escape `% _` (CONVENTIONS ข้อ 4)
- `load_quick_search`: โหมดแอดมิน (ไม่กรองเจ้าของ) ต้องมีสิทธิ์ `admin_<prefix.name>`; ไม่มี → ค้นเฉพาะรายการของตัวเอง; ข้อความยืนยัน escape เลขที่/รหัสลูกค้า; ตารางใน `prefix.table_name` ต้องมีคอลัมน์ `id,user,is_delete` (เดิม SQL error = ไม่พบ ผลเท่าเดิม)
- `_admin/_exchange/cancel`: เดิมกดซ้ำได้เงินหยวนคืนซ้ำทุกครั้ง (เทียบ `$data['status']` ก่อนอ่านแถว) และยกเลิกรายการที่อนุมัติแล้วได้ → ใหม่ทำเฉพาะ status ไม่ใช่ -1/2, id ที่ไม่มีจริงไม่ INSERT แถวขยะ
- `_admin/_exchange/save_approved`: เดิมอนุมัติรายการที่ยกเลิกแล้ว (คืนหยวนแล้ว) ได้ → ได้ทั้งหยวนคืนและเงินบาท; ใหม่ตอบ ErrorBox; id ที่ไม่มีจริงไม่ INSERT balance ขยะ; ทั้งสองทำใน transaction + `lockForUpdate`
- อีเมลแจ้งผลแลกเงิน: เดิม `include_once('email_success.php' / 'email_cancel.php')` แต่ไม่มีไฟล์ในโฟลเดอร์ = ไม่เคยส่ง → ไม่ส่งเหมือนเดิม
- `delete.php` (product_type, shipping_type, delete_cal): รับเฉพาะรายการเลขจำนวนเต็ม; รูปแบบอื่นไม่ทำอะไร (เดิม SQL syntax error) แต่ตอบ SuccessBox เหมือนเดิม
- `system_upload*`: ต้องล็อกอิน, ตอบ `text/plain` + `nosniff`, เก็บที่ `legacy.upload.temp_dir` (dev = `storage/app/legacy-upload/temp`, ไม่ขึ้น Azure)
- `_admin/_setting/save` (review 2026-10-18): เดิมคีย์ที่ไม่ส่งมาถูกเขียน `''` ทับทั้ง 16 คีย์ (POST ว่างครั้งเดียว = ล้างเลขบัญชี/อัตราแลกเปลี่ยน — เคยล้าง bench จริง) → ใหม่อัปเดตเฉพาะคีย์ที่อยู่ใน POST body เป็นค่า scalar; ไม่มีเลย = `ErrorBox('ไม่มีข้อมูลที่จะบันทึก')` ไม่แก้อะไร (ฟอร์มจริงส่งครบทุกช่อง → ผลเหมือนเดิม; ช่องที่ส่งมาว่างยังบันทึก `''`)
- `_admin/_product_type/save`, `_admin/_shipping_type/save`: POST ที่ไม่มีช่อง `name` เลย เดิม INSERT แถวว่าง (id ว่าง) หรือล้างทุกคอลัมน์ของ id นั้น → ใหม่ ErrorBox ไม่แก้อะไร
- `system_upload_2`: เดิมไม่ตรวจเนื้อไฟล์ → ใหม่นามสกุลรูป/PDF ต้องเป็นรูป/PDF จริง (finfo) ไม่ใช่ = error แบบเดิม; ชนิดจริงไม่ตรงนามสกุลใช้นามสกุลตามชนิดจริง (เหมือน `system_upload`); xls/xlsx ยังไม่ตรวจเนื้อไฟล์
- `fn_public = "loadAjaxGrid(..., '<page>')"` ใน grid ทั้ง 3: ค่า `page` escape 2 ชั้น (`Input::jsEval`) เพราะ string ถูก `eval()` อีกรอบ (เดิมรอดเพราะ `'`→`''` เท่านั้น)
- grid: ค่า `page` ที่ฝังใน JS ผ่าน `Input::js()` (เดิม echo ดิบ)
- หน้าแอดมินที่ไม่มีสิทธิ์: ได้หน้า `legacy.no-permission` ของ foundation (เดิมแสดงหัวหน้า + ErrorBox แล้วหน้าถูกตัดกลาง)

## ช่องโหว่เดิมที่เจอ (อ่านโค้ดเท่านั้น — ไม่ได้ยิง payload ใส่เว็บเดิม)

1. SQL injection ทุกไฟล์ที่รับค่า POST/GET (setting/save, exchange grid/form/cancel/save_approved, product_type/shipping_type grid/save/delete, load_cal, save_listno, save_new_price_*, load_quick_search) — `delete.php` 3 ไฟล์ `id IN ($id)` ลบ (soft delete) ได้ทั้งตาราง เหมือนเหตุการณ์ save_remove_tracking
2. `_admin/_setting/save.php` ไม่ตรวจสิทธิ์: ลูกค้าที่ล็อกอิน (หรือใครก็ได้ที่ส่ง X-Requested-With) แก้เลขบัญชีรับโอน/อัตราแลกเปลี่ยนได้
3. `load_quick_search.php`: IDOR — ส่ง `current_href=...admin...` หรือ `sa-` เพื่อดูว่ารายการของคนอื่นมีจริงและเป็นของรหัสลูกค้าใด; ไม่ล็อกอินก็ค้นแถว `user = 0` ได้; ชื่อตารางจาก DB ต่อเข้า SQL
4. `system_upload.php` / `system_upload_2.php` ไม่ตรวจล็อกอิน — ใครก็อัปโหลดไฟล์ลง webroot `upload/temp` ได้; `system_upload_2` ไม่ตรวจเนื้อไฟล์
5. `_admin/_exchange/cancel.php` คืนเงินหยวนซ้ำได้ไม่จำกัด / `save_approved.php` อนุมัติรายการที่ยกเลิกแล้วได้ (เงินงอก)
6. Stored XSS: ค่า setting, ชื่อประเภทสินค้า/การจัดส่ง, cancel_remark, detail, ชื่อไฟล์ใน getupload*, ข้อความ quick search (`ConfirmBox` html:true)
7. Reflected JS injection: `$_POST['page']`, `$_POST['txtsearch']` ใน grid; `$_GET['status']` ใน exchange index
8. `getPermission('view_bol')` ใน get_price: ไม่มีโมดูลนี้ = endpoint ตาย (คงไว้ ให้เจ้าของตัดสินใจ); สิทธิ์ของ exchange ใช้ 3 ชื่อปนกัน (`admin_exchange` / `admin_withdrawcash` / `admin_withdrawpoint`) — คงไว้

## การทดสอบ

เทียบ HTML เดิม (:8101) กับใหม่ (:8213) ด้วย session เดียวกัน (แอดมิน zz_fdn_admin, ลูกค้า 137), normalize href + ช่องว่าง:
หน้า setting / exchange (+status, +id 49, +id 0) / product_type (list, create, id 1, id ไม่มีจริง) / shipping_type (list, create, id 3, 13),
grid ทั้ง 3 ตัว (หลายหน้า, page ว่าง/0, คำค้น, สถานะ), load_cal_1/2 (มี/ไม่มีแถว, id ว่าง), gateway, index_close, get_resource, get_price
→ เหมือนเดิมทั้งหมด ยกเว้นข้อ escape ข้างบน; quick search 19 กรณี เหมือนเดิมยกเว้นกรณี IDOR ที่ตั้งใจปิด

SQL injection ทดสอบเฉพาะฝั่ง Laravel บน endpoint อ่าน (quick search, exchange grid) → ไม่มีแถวรั่ว; `tests/Feature/Settings/SettingsRoutesTest.php` ครอบ route/สิทธิ์ และ idList ที่กัน payload `id=910719) OR (1=1`

**ยังไม่ได้ทดสอบเขียน DB** (save/delete/approve/cancel/save_new_price/save_listno/upload ที่บันทึกไฟล์): หยุดก่อนเพราะพบตาราง `bill_payment`, `bill_payment_detail`, `balance`, `refill` ใน admin_jl_bench ว่าง (ดูรายงานส่งกลับ) — ต้องทดสอบด้วยแถว zz_ ก่อน merge

## bench (`bin/bench`, -n 20 -c 1)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/admin/setting` | 343 | 47 |
| `/_admin/_shipping_type/grid` | 36 | 37 |

หน้า setting เร็วขึ้นเพราะ getKey() เดิม 1 query ต่อช่อง (16 ครั้ง) + layout เดิม; grid เล็กเท่าเดิม (overhead คงที่ของ Laravel)

## Review รอบ 2 (2026-10-18)

- เทียบเดิม/ใหม่ (normalize href+ช่องว่าง) ด้วย zz_fdn_admin: หน้า setting/exchange(+status, id)/product_type/shipping_type (list, create, id) ทั้ง th/en/cn, grid 3 ตัว (หน้า 1/2/16/99/ว่าง, คำค้นไม่พบ, สถานะ -1/2), load_cal_1/2 (มี/ไม่มีแถว, id ว่าง) → 0 บรรทัดต่าง
- สิทธิ์: ไม่ล็อกอิน → /login; ลูกค้า (zz_fdn_cust) → ErrorBox สิทธิ์ทุก route แอดมิน, grid ตอบว่าง; ชื่อสิทธิ์ตรง `getPermission()` เดิมทุกไฟล์
- CSRF: Origin/Referer ต่างโดเมน → 403 ทุก route เขียน 13 ตัว; ไม่มี `X-Requested-With` → /403
- SQLi (เฉพาะ Laravel): quote/UNION/`1) OR (1=1`/`\`/`%`/`_` ใน txtsearch, id ของ load_cal, code ของ quick search, ค่า setting, id/arr_id ของ delete → bind ทั้งหมด ไม่มีแถวรั่ว ไม่มี error ใน log
- เขียนจริง (แถว zz_ แล้วลบตาม id): product_type เพิ่ม/แก้/ลบ (arr_id), shipping_type เพิ่ม/แก้ (is_use_rate 0/1 + rate_id[]), save_listno, save_new_price_by_kg/cubic (+ error max<=min, ช่องว่าง), delete_cal (id, arr_id), delete; exchange อนุมัติ (balance +10×4.95 = 49.50 ครั้งเดียว, อนุมัติซ้ำไม่เพิ่ม, รายการยกเลิกแล้ว → ErrorBox); cancel = ไม่มี role ใดมี `admin_withdrawpoint` (ปฏิเสธแม้ role 1 เหมือนเดิม); setting บันทึกทั้งฟอร์มด้วยค่าเดิม → ทุกแถวเหมือนเดิม, บันทึกคีย์เดียว → แถวเดียวเปลี่ยน
- upload: png/jpg/jpeg/pdf ผ่าน; .php/.phtml/.php.jpg/รูปปลอม/`x.png%00.php` ถูกปฏิเสธ; ชื่อ `../../x.png` ไม่ใช้เป็น path (ชื่อสุ่ม 10 ตัวใน temp_dir เท่านั้น)
- test: `tests/Feature/Settings/SettingsSaveTest.php`, `SettingsUploadTest.php` (ไม่แตะ DB: DB facade เป็นตัวบันทึก)
- ยังเหลือ: `UploadBox` มีสำเนาใน 7 กลุ่ม และ `ImageShrink` 2 ชุด (Settings/Shop) — รวมเป็นตัวเดียวภายหลัง; ชื่อไฟล์เดิมที่ `system_upload_2` ตอบกลับ (`[NIDA]<ชื่อเดิม>`) ไม่ escape (text/plain; JS ฝั่งผู้ใช้ต้อง escape เอง)
- bench รอบ 2 (`bin/bench -n 30 -c 1`, zz_fdn_admin, artisan optimize, เฉลี่ย 2 รอบ เดิม → ใหม่): `/admin/setting` 381 → 51 ms, `/admin/exchange` 333 → 49 ms, `/_admin/_product_type/grid` 30.5 → 28.1 ms
