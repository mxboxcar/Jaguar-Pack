# กลุ่ม lot — รายงานการพอร์ต

## PACK (2026-10-22)

branch `mod/lot` (worktree `/home/admin/laravel/pack-wt/lot`, กลุ่ม lot + money-move ใน worktree เดียว) · dev http://127.0.0.1:8808 · เทียบกับ PACK เดิม :8114 (`admin_pack_bench`)
PACK = SR ส่วนใหญ่ แต่หน้า detail ของล็อตเป็นแบบ jl (ตารางเดียวจาก `load_lot_detail`) + ส่วนเสริมของ SR (รูปพัสดุ, ค้างจ่าย, แนบรูปด่วน) และมีการล็อก UI ให้ user id 5 เท่านั้น
ข้อความด้านล่าง (หัวข้อ SR และของ jl) ยังใช้ได้กับ PACK ยกเว้นที่ระบุในหัวข้อนี้

### สิ่งที่เปลี่ยนสำหรับ PACK (ตาม `docs/pack-diff.md` กลุ่ม lot)

| ไฟล์เดิม | สถานะ | ที่ทำ |
|---|---|---|
| `_admin/_lot/form.php` | diff 375 (jl 208) | แม่แบบ jl (ช่องค้นหา/หัว-ท้ายตาราง/ผลรวมมากับ `load_lot_detail` คำขอเดียว, `makeTopBottom()` แบบ jl) + ของ PACK: ช่องแนบรูปพัสดุ (`#attachment_tracking`, `data[7]` ของ load_tracking), `#lot_unpayment` + `LoadUnpaid()`, modal แนบรูปด่วน (`load_quickfile`/`quicksavefile()`), `loadImage()` หลังโหลดตาราง; **`$allowEdit` = `checkUser()['id'] == 5`**: ฟอร์มเพิ่ม/แก้ tracking, ช่องแก้ชื่อ/หมายเหตุ/ค่าขนส่ง/ไฟล์ของล็อต, แถวตั้งวันที่ + ปุ่มพิมพ์ แสดงเฉพาะ user 5 — คนอื่นเห็นหัวล็อตแบบอ่านอย่างเดียว (ไฟล์แนบเป็นลิงก์ `download_file?path=…&filename=…`); ไม่มี "ถ่ายรูป"/"รหัสย่อย" (คอลัมน์ไม่มีใน PACK) |
| `_admin/_lot/load_lot_detail.php` | diff 169 (jl 36) | แม่แบบ jl (ทั้งตาราง + hidden ผลรวม, แถว "ไม่พบข้อมูล" colspan 14) + ช่องเลือก/แก้/ลบเฉพาะ user 5 (คนอื่นได้ `<th>`/`<td>` ว่าง), คอลัมน์ราคา kg/คิว (หัว/ผลรวม/แถว) ซ่อนเมื่อมีสิทธิ์ `admin_lot_view` (เช่น user 5 role 6 ถือทั้งสองสิทธิ์ → ไม่เห็นราคา), ปุ่ม + แนบรูปด่วน และ `div.img_track`; ไม่มีพื้นเทา `is_checked` |
| `_admin/_lot/save.php` | diff 45 (jl 86) | ไม่มี `subuser`; รูป `upload_upload_track_mul[]` แบบ SR port (ไม่ส่ง = ไม่แตะ, ส่ง = แทนทั้งหมด) — **ทางเพิ่มใหม่ของ PACK ผูกรูปกับ id ใหม่อยู่แล้ว** (ตรงกับที่ SR port แก้ไว้); ตอบ JS ย้ายรูป (`_api/blockblob_ajax`) ตามด้วย `SuccessBox(… , 'CloseLoading();LoadDetail();')` แบบ jl (SR = reload) |
| `_admin/_lot/index.php` | diff 3 | ปุ่ม "เพิ่มรายการใหม่" (สร้างล็อต) เฉพาะ user 5 |
| `_admin/_lot/load_image.php`, `load_quickfile.php`, `save_quickfile.php` | diff 12/9/9 | ตรวจ `getPermission('admin_lot')` อย่างเดียว (SR ยอม `admin_lot_view`) → route `legacy.perm:admin_lot` |
| `_admin/_lot/load_toptable.php` | SR-only | **ลบ route/method** — PACK ไม่มีไฟล์และไม่มีหน้าใดเรียก (ผลรวมมากับ load_lot_detail) → `docs/url-map.md` ทำเครื่องหมาย dead |
| `_admin/_lot/load_ref_print.php` | diff 2 (jl 0) | `LIMIT 10` แบบ jl |
| `_admin/_lot/load_tracking.php` | diff 3 | ต่างแค่การจัดบรรทัด — ใช้ของ SR port (`data[5]` = "" หรือแถวลูก, `data[7]` ช่องแนบรูป; GetCalPrice ของ PACK = SR) |
| `_admin/_lot/print.php` | diff 8 (jl 2) | แบบ jl: คอลัมน์ราคา kg/คิว + ผลรวมต่อลูกค้า (colspan 7) แต่ไม่มีเครื่องหมาย ✔ (`is_checked` ไม่มี) |
| `_admin/_lot/email.php` | diff 6 (jl 2) | ช่อง "เลขที่ใบสั่งซื้อ" และ "วลีช่วยจำ" = `-` เสมอ (`china_order_number`/`order_id` ไม่มี — แบบ jl), ปุ่มสี `#eb4823` padding ขวา 0; ผู้ส่ง/หัว/ท้าย = `config('legacy.mail')` ของ PACK (ฐานร่วม) |
| `_lot/form.php` | diff 1 (jl 0) | legend ไม่มี "ถ่ายรูป" (partial `row_color` กลับเป็นของ jl) |
| ไฟล์ `same` อีก 33 ไฟล์ | same | ไม่ต้องแก้โค้ด — เทียบผลแล้ว (ด้านล่าง) |

ไฟล์ที่แก้: `routes/modules/lot.php`, `AdminLotController` (form/index ส่ง `allowEdit`, `loadLotDetail` แบบ jl, ลบ `loadToptable`), `AdminLotSaveController::save`, `LotLookups::allowEdit()` (ค่าคงที่ `EDITOR_USER_ID = 5`), `LotPrint`, `LotMailer`, views `lot/admin/{form,detail,index}`, `lot/customer/form`, `lot/partials/row_color` · test: `LotPackDbTest` (แทน `LotSrDbTest`), `LotRoutesTest`, `LotViewOnlyTest`, `LotPrintHeaderCountDbTest`

### ต่างจาก PACK เดิมโดยตั้งใจ

- คงทุกข้อของ jl/SR port (admin_lot_view = อ่านอย่างเดียว, กันกดซ้ำ, bind SQL, escape, ไม่มี target blank, CSRF/cross-site GET ฯลฯ)
- **user 5 เป็นแค่การซ่อนปุ่มบนหน้าจอ** (แบบเดิม): endpoint เขียน (`save`, `save_new_lot`, `save_lot_*`, `save_status_*`, `delete`, …) ยังตรวจสิทธิ์ `admin_lot` ตามเดิม — แอดมิน role 1/7/9 ยิงตรงได้ (เดิมก็ได้) — ดูคำถามข้อ 1
- รูปเล็กข้างเลขพัสดุมาพร้อมแถวของ `load_lot_detail` (เดิม `loadImage()` ยิง `load_image` ทีละแถว) — เฉพาะผู้ถือ `admin_lot` (load_image ของ PACK ต้องมี admin_lot; ผู้ถือ view อย่างเดียวได้ div ว่างแล้ว JS เดิมยิง load_image → ErrorBox สิทธิ์แบบเดิม — PACK ไม่มี role แบบนี้)
- `LoadUnpaid()` ใส่ผลลง `#lot_unpayment` (เดิมทับ `#lot_memo`) — เหมือน SR port; JS `ontempcal` ใช้ `parseFloat` แทน `eval` (เหมือน jl/SR port)
- ฟอร์ม: รูปของ "tracking_id = id ล็อต" ที่เดิมแสดง/ส่งกลับไปบันทึก → เริ่มว่าง (เหมือน SR port)

### ช่องโหว่/บั๊กเดิมของ PACK ที่เจอ

1. `_admin/_lot/save.php` ฯลฯ ยังต่อ `$_POST` เข้า SQL ทุกไฟล์ (เหมือน SR/jl — port bind หมดแล้ว), `load_image`/`load_quickfile`/`save_quickfile` ต่อ id ลง SQL ดิบ
2. การจำกัด "แก้ได้เฉพาะ user 5" อยู่แค่ใน HTML — endpoint เขียนไม่ตรวจ (ใครมี admin_lot หรือ admin_lot_view ก็ POST ได้ในเว็บเดิม)
3. `_lot/grid.php` (ลูกค้า) บน bench ใช้เวลาหลายนาที: subquery `COUNT` ต่อแถวล็อต × 5,307 ล็อต โดยตาราง tracking ของ PACK **ไม่มี index `lot_id`** (คำขอทดสอบของลูกค้า 348 ค้าง >6 นาที จน :8114 ค้าง — ต้อง kill query เอง) — Laravel ตอบ ~185 ms (นับเฉพาะล็อตในหน้า); ข้อเสนอ index `tracking(lot_id, is_delete, user)` (ไม่ได้สร้างเอง)

### การตรวจ (2026-10-22, :8114 เดิม vs :8808 ใหม่, session เดียวกัน, normalize host/รูป blob/ช่องว่าง/คอมเมนต์ HTML/target blank)

- แอดมิน 11938 (role 1): `/admin/lot` (ตรง), grid หน้า 1/2/3 + ค้นหา `5294` + รถ `|0` + เรือ `|1` = **ตรง**, `/admin/lot/5294`, `/admin/lot/5305`, `/admin/lot/create` (ต่างเฉพาะ `#lot_unpayment` + `parseFloat`), `load_lot_detail` 5305/5294/ค้นรหัสลูกค้า/ล็อตไม่มี/`search=undefined` (ต่างเฉพาะรูปเล็กที่มาพร้อมแถว), `load_image`/`load_quickfile` (รูปของ 98864/98865), `load_unpaid`, `load_lot_memo` (ต่าง `.php` ใน URL autocomplete แบบ jl), `load_ref_print` ×2, `load_user_rate`, `load_lastest_lot`, autocomplete ×2, `print_receive` = ตรง
- user 5 (PACKMAN role 6 — ผู้แก้ล็อตได้ + ถือ admin_lot_view): `/admin/lot` (มีปุ่มสร้าง) = ตรง, `/admin/lot/{5294,5305,create}` (ฟอร์มเต็ม, ภาษา en ด้วย) ต่างเฉพาะข้อตั้งใจข้างบน, `load_lot_detail` (ช่องเลือก/แก้/ลบ, ไม่มีคอลัมน์ราคา; ภาษา cn) = ตรง
- role 7 (923): `/admin/lot`, `/admin/lot/5305` = ตรง (อ่านอย่างเดียว); ลูกค้า/role 3/role 4: ปฏิเสธ — ข้อความ ErrorBox ตรงกับเดิม
- ลูกค้า 348: `/lot`, `/lot/5305`, `_lot/load_lot_detail` 5305/5294 = ตรง; `_lot/grid` เทียบสดไม่ได้ (ข้อ 3 — ไฟล์ same กับ SR ที่เทียบครบแล้ว)
- ไม่ได้เทียบสด (ไฟล์เดิมเขียน DB): `load_tracking`, `print`, `email_lot`, ทุก `save*` — ตรวจด้วยการอ่านโค้ด + แถวที่เขียน
- `load_toptable` → 404 (route ลบแล้ว)
- หลัง merge main (UploadBox ของกลุ่ม tracking เป็น markup ของ PACK แล้ว, layout ฐานร่วม): `/admin/lot`, `/admin/lot/{5294,create}` (แอดมินและ user 5), `load_quickfile`, grid = ต่างเฉพาะข้อตั้งใจ

### E2E เขียน (ฝั่ง Laravel :8808 เท่านั้น, แอดมิน 11938, ลูกค้า ZZFDN1 11937, แถว zz แล้วลบตาม id)

`save_new_lot` (ล็อต 5380 ชื่อ = ชื่อมากสุด+1 แบบเดิม) → `save_lot_name` (`zz_lot_e2e'1` เก็บ `'`), `save_lot_name_admin`, `save_lot_ship_amount` (`1,234.5` → 1 แบบ MySQL non-strict เดิม) → `save` เพิ่มใหม่ ส่งซ้อน 2 คำขอ (= 1 แถว + "กำลังดำเนินการ"; ช่องว่างในเลขพัสดุ/ออเดอร์ถูกตัด, 2.55 → 2.5, `1,000` → 1000, รูป 2 ไฟล์ผูก id ใหม่ + JS blockblob 2 ตัว, `../x.jpg` ถูกทิ้ง; ตอบ JS + SuccessBox/LoadDetail) → `save` แก้ไข (ไม่ส่งรูป = รูปเดิมอยู่; ส่ง 1 รูป = แทนทั้งหมด; payload เดิมซ้ำ = "ส่งซ้ำ") → `save_quickfile` → `load_tracking` → `save_lot_memo` → `save_status_tracking` (วันที่รูปแบบ "21 ตุลาคม 2026"; `id=… ) OR (1=1` ไม่แก้แถวอื่น) / `reset` → `save_status_lot` / `_date` → `print` (is_print, cross-site = 403, คอลัมน์ราคา+ผลรวม) → `email_lot` ×2 (คิว 1 แถว) → `lot:mail-worker` (เมลใน log: ผู้ส่ง PACK-Cargo, ปุ่ม #eb4823, ช่อง `-` สองช่อง) → `delete` ล็อตที่มี tracking (ErrorBox) → `save_print` (array, ค่าถูก escape ใน JS) → `save_verified` (11938 = ไม่มีสิทธิ์) → `save_remove_tracking` (+ probe injection) → `delete` (is_delete=1) · `_admin/_lot/cancel` (refill zz: ยกเลิก 1 ครั้ง, ครั้งที่ 2 ErrorBox)
ลบแล้ว: lot 5380, tracking 99443, tracking_image 47241–47245, lot_memo 4, lot_mail_queue 1 (+ แถวของ money-move ด้านใน money-move.md), ไฟล์ temp zz — นับหลังลบ = 0

### bench (`bin/bench`, -n 20 -c 1 ×2 รอบ, `artisan optimize`; mean ms รอบ 2)

| หน้า | เดิม mean ms (รอบ 1 / 2) | ใหม่ mean ms (รอบ 1 / 2) |
|---|---|---|
| `/admin/lot` (แอดมิน, -n 10) | 1404 / 1691 | 246 / 98 |
| `_admin/_lot/grid` page 1 (-n 5) | 11492 / 11031 | 466 / 474 |
| `/admin/lot/5305` (-n 10) | 3317 / 2343 | 440 / 381 |
| `_admin/_lot/load_lot_detail` id=5305 (-n 10) | 2043 / 1448 | 560 / 1059 |
| `/lot` (ลูกค้า 348) | 888 / 687 | 110 / 76 |
| `_lot/load_lot_detail` 5305 (ลูกค้า 348) | 142 / 392 | 105 / 112 |

(-n 10 -c 1 ×2 รอบ ยกเว้นที่ระบุ; เครื่องมี agent อื่นรันพร้อมกัน ตัวเลขแกว่ง) · ตาราง tracking ของ PACK ไม่มี index `lot_id` → ทุก query ที่กรองล็อตสแกนครึ่งตาราง (~250 ms) ทั้งเดิมและใหม่: grid แอดมินเดิมยิง 4 query ต่อแถว (~11 วินาทีต่อหน้า) ใหม่ดึงครั้งเดียว (~0.4 วินาที), `/admin/lot/{id}` และ `load_lot_detail` ใหม่ยังมี 1 สแกน (~0.3–0.5 วินาที) — ข้อเสนอ index `tracking(lot_id, is_delete)` จะทำให้เหลือหลักสิบ ms (ไม่ได้สร้างเอง); `_lot/grid` ลูกค้าเดิมเกินหลายนาทีบน bench จึงไม่ได้ bench (ใหม่ ~185 ms)

### test

`php8.4 vendor/bin/phpunit` และ `JL_BENCH_DB_TESTS=1` — ดูผลรวมในรายงานของ money-move (รันชุดเดียวกัน)

### คำถามถึงเจ้าของ

1. **user 5 เท่านั้นที่แก้ล็อตได้บนหน้าจอ** (ฮาร์ดโค้ด id 5 = PACKMAN) แต่ endpoint เขียนยังยอมทุกคนที่มี `admin_lot` (เดิมก็เป็น) — ต้องการบังคับที่ endpoint ด้วยหรือไม่ (เช่น ให้เฉพาะ user 5 หรือเปลี่ยนเป็นสิทธิ์) และถ้าเปลี่ยนคนดูแลล็อตต้องแก้ `LotLookups::EDITOR_USER_ID`
2. user 5 (role 6) ถือ `admin_lot_view` ด้วย → **ไม่เห็นคอลัมน์ราคา** ในตารางล็อตและช่องราคาในฟอร์ม (เหมือนเดิม) — ตั้งใจหรือไม่
3. index `tracking(lot_id, …)` สำหรับ `_lot/grid` ลูกค้า (ข้อ 3 ของช่องโหว่) — production มี index นี้หรือไม่ (bench ไม่มี)

## SR (2026-10-20)

branch `mod/lot` ของ repo sr · worktree `/home/admin/laravel/sr-wt/lot` · dev http://127.0.0.1:8308 · เทียบกับ SR เดิม :8111 (`admin_sr_bench`)
ข้อความด้านล่าง (ตั้งแต่ "ไฟล์ของกลุ่ม") เป็นรายงานเดิมของ jl — ยังใช้ได้กับ SR ยกเว้นที่ระบุในหัวข้อนี้

### สิ่งที่เปลี่ยนสำหรับ SR (ตาม `docs/sr-diff.md` กลุ่ม lot)

| ไฟล์เดิม | สถานะ | ที่ทำ |
|---|---|---|
| `_admin/_lot/index.php` | diff 79 | ตัดกล่อง "รับพัสดุเข้าไทย" (ค้นหา + ตาราง `#resultserach` + JS `on_search`/`keypressInBox5`) — SR ไม่มี |
| `_admin/_lot/grid.php` | diff 26 | คอลัมน์ "ค้างจ่าย" (จำนวนลูกค้าที่มี tracking `payment_id = 0` ในล็อต — เดิม `GROUP BY user` ต่อแถว → query เดียวต่อหน้า `COUNT(DISTINCT user)`), วันที่ 3 ช่อง = tracking ที่ยังไม่ลบ ที่ `date_warehouse` น้อยสุด (เดิม 3 query ต่อแถว `ORDER BY date_warehouse LIMIT 1` → `ROW_NUMBER()` query เดียว, เสมอกันใช้ id — เทียบครบ 23 หน้ากับ :8111 ตรงทุกหน้า), ไม่มีปุ่มดาวน์โหลด `print_all`, ปุ่ม Verified ที่ SR comment ไว้ไม่แสดง (เป็น HTML comment ที่มี target blank) |
| `_admin/_lot/form.php` | diff 399 | ช่อง "ถ่ายรูป" (`is_takeapic` — **SR save.php ไม่บันทึกค่านี้** คงไว้แบบเดิม), "รหัสย่อย" (`subuser`), ช่องแนบรูปพัสดุ (`UploadBox::multiple('upload_track')` ของกลุ่ม tracking) + รูปเดิม; ช่องค้นหา + หัว/ท้ายตารางย้ายมาอยู่ในหน้า, `LoadDetail(page)` ทีละ 10 แถวต่อกันจนหมดแล้ว `loadendtable()` (= load_toptable) → `makeTopBottom()` รวมผล; `LoadUnpaid()`, modal แนบรูปด่วน (`load_quickfile` / `quicksavefile()` → `save_quickfile`), `loadImage()`; หลังลบออกจากล็อต/ตั้งวันที่/ล้างวันที่ = `window.location.reload()`; สีและ legend "ถ่ายรูป" (`#768cf2`); alert "ถ่ายรูป" ใน getTracking |
| `_admin/_lot/load_lot_detail.php` | diff 143 | ตอบเฉพาะแถว (`LIMIT page*10, 10`) + `<script>LoadDetail(page+1)</script>` หรือ `loadendtable()` เมื่อหมด; รหัสลูกค้า + `subuser` สีแดง, ปุ่ม + แนบรูป, `div.img_track`; ไม่มีพื้นสีเทา `is_checked` (SR ไม่มีคอลัมน์); ไม่มีแถว "ไม่พบข้อมูล" และ hidden ผลรวม (ย้ายไป load_toptable) |
| `_admin/_lot/load_tracking.php` | diff 35 | `data[5]` = `""` หรือ `[แถวลูก...]` (jl `[[]]`), `data[7]` = HTML ช่องแนบรูป + รูปเดิมของ tracking |
| `_admin/_lot/save.php` | diff 94 | `subuser` (`nStr` ซ้ำ = `'` เก็บเป็น `''` แบบเดิม), รูป `upload_upload_track_mul[]`: ส่งมา = ลบ (`is_delete=1`) ของเดิมทั้งหมดแล้ว INSERT ตามรายการ (ไม่ส่ง = ไม่แตะ), ไฟล์ที่อยู่ใน temp ได้ JS `_api/blockblob_ajax` (`App\Domain\Tracking\TempUpload` ของกลุ่ม tracking), ตอบ `window.location.reload();` (jl: SuccessBox) |
| `_admin/_lot/load_ref_print.php` | diff 2 | ไม่มี `LIMIT 10` |
| `_admin/_lot/print.php` | diff 10 | ไม่มีคอลัมน์ "ราคา kg / ราคาคิว" และผลรวมราคา, หัวรหัสลูกค้า `colspan="5"`, ไม่มีเครื่องหมาย ✔ (`is_checked`) — แก้ test `LotPrintHeaderCountDbTest` ที่ล้มเพราะ `$item->is_checked` |
| `_admin/_lot/email.php` | diff 6 | ช่อง "วลีช่วยจำ" = `tracking.quote` (escape), ช่อง "เลขที่ใบสั่งซื้อ" = `-` (คอลัมน์ `china_order_number` ไม่มี), ปุ่มสี `#a3b2f1`; `LotMailer` ใช้ `config('legacy.mail')` (ผู้ส่ง `SRShippingSystem <no-reply@srshippingsystem.com>` + header/center/footer ของ SR) แทนแบรนด์ jl |
| `_lot/form.php` | diff 1 | legend "ถ่ายรูป" (ฝั่งลูกค้า; `_lot/load_lot_detail.php` ของ SR ไม่มีสีถ่ายรูป — คงไว้) |
| `send_a_lot_mail()` (system_config.php SR-only) | — | ไม่มีใครเรียก (email.php ใช้ `sendmail()`) → ไม่พอร์ต; worker = `php8.4 artisan lot:mail-worker` เดิม |
| ไฟล์ `same` อีก 31 ไฟล์ | same | ไม่ต้องแก้โค้ด — เทียบผลกับ :8111 แล้ว (ด้านล่าง) |

### SR-only ที่พอร์ตใหม่

| ไฟล์เดิม | route | สิทธิ์ |
|---|---|---|
| `_admin/_lot/load_image.php` | `POST /_admin/_lot/load_image` | `admin_lot,admin_lot_view` |
| `_admin/_lot/load_quickfile.php` | `POST /_admin/_lot/load_quickfile` | `admin_lot,admin_lot_view` |
| `_admin/_lot/load_toptable.php` | `POST /_admin/_lot/load_toptable` | `admin_lot,admin_lot_view` |
| `_admin/_lot/load_unpaid.php` | `POST /_admin/_lot/load_unpaid` | `admin_lot,admin_lot_view` |
| `_admin/_lot/save_quickfile.php` | `POST /_admin/_lot/save_quickfile` | **`admin_lot`** (เดิมยอม `admin_lot_view` — view = ดูอย่างเดียว; SR role 10/11/12 ถือ view อย่างเดียว) |

ไฟล์ใหม่: views `lot/admin/{attachment,image_list,img_track,quickfile,unpaid}.blade.php`, `LotLookups::trackingImages()` (รูปหลาย tracking ใน query เดียว), test `tests/Feature/Lot/LotSrDbTest.php`

### JL-only ที่ตัดออก

- `_admin/_lot/load_check_tracking.php` (รับพัสดุเข้าไทย, เขียน `tracking.is_checked` ที่ SR ไม่มี) และ `_admin/_lot/print_all.php` (.xls) — SR ไม่มีไฟล์ และไม่มีหน้า/JS ใดของ SR เรียก (`grep` ทั้ง webroot) → ลบ route, `AdminLotController::loadCheckTracking`, view `check_tracking`, `AdminLotPrintController::printAll`, `LotPrint::printAll` (URL ตกไป 404 → หน้าแรก)
- `LotRoutesTest::test_route_count_matches_url_map` = 46 ไฟล์ตาม url-map ของ SR (เดิมคาด 43 ของ jl)

### ต่างจาก SR เดิมโดยตั้งใจ (เพิ่มจากของ jl ด้านล่าง)

- **admin_lot_view = อ่านอย่างเดียว** ครอบ SR-only ด้วย: `save_quickfile` ต้องมี `admin_lot`; `load_tracking` ของ SR เรียก `GetCalPrice()` ซึ่ง **เขียน** `tracking.product_type_id` (แก้ให้ตรงประเภทล็อต) → ผู้ถือ view อย่างเดียวได้ราคาเท่ากันแต่ไม่เขียน (`Pricing::getCalPrice($id, false)` — commit `foundation:`); ผู้ถือ `admin_lot` เขียนแบบเดิม
- **กันกดซ้ำ** `_admin/_lot/save` (`legacy.once`): E2E ดับเบิลคลิกเพิ่ม tracking ใหม่ = 2 แถวเลขเดียวกัน (เว็บเดิมก็เป็น) → คำขอที่ซ้อนได้ `ErrorBox('รายการนี้กำลังดำเนินการ…')`, payload เดิมภายใน 8 วินาทีได้ `ErrorBox('…ส่งซ้ำ…')`
- **รูปของ tracking ที่เพิ่มใหม่** (save.php ทางเพิ่มใหม่): เดิมใช้ `$tracking_id` ที่ว่าง → `UPDATE tracking_image SET is_delete=1 WHERE tracking_id=''` (ลบรูปกำพร้าทั้งหมดที่ tracking_id 0) แล้ว INSERT รูปด้วย tracking_id 0 (รูปหลุด — ใน bench มีแถว tracking_id 0 อยู่ 1,987 แถว) → ผูกกับ id ของ tracking ที่เพิ่งสร้าง และไม่แตะรูปกำพร้า
- **รูปที่ไม่มีเจ้าของไม่ถูกแสดง**: `form.php` เดิมแสดงรูปของ `tracking_id = id ล็อต` (ตัวแปร `$data` ของหน้าเป็นแถว lot = รูปของพัสดุอื่น และถูกส่งกลับไปบันทึกให้ tracking ที่กรอก), `load_tracking`/`load_quickfile`/`load_image` ที่หา tracking ไม่พบ = `tracking_id ''` (= 0) แสดงรูปกำพร้า → ทั้งหมดเริ่มว่าง
- **รูปเล็กในตารางมาพร้อมแถว**: เดิม `loadImage()` ยิง `load_image` ทีละ tracking (ล็อต 100 พัสดุ = 100 ajax) → `load_lot_detail` ใส่รูปใน `div.img_track` มาเลย (1 query ต่อ 10 แถว) แถวที่ไม่มีรูปใส่ `<!-- -->` ให้ JS เดิมข้าม — DOM ที่เห็นเท่ากัน; route `load_image` ยังอยู่
- `LoadUnpaid()` เดิมใส่ผลลง `#lot_memo` (ชนกับ `LoadMemo()` — กล่องที่ตอบทีหลังทับอีกกล่อง; PHP session lock ของเว็บเดิมทำให้ส่วนใหญ่รายการค้างจ่ายทับ memo) ทั้งที่หน้ามี `#lot_unpayment` ว่างไว้ → ใส่ลง `#lot_unpayment` (เห็นทั้งสองกล่อง) — ดูคำถามข้อ 2
- `save_quickfile`: id ต้องเป็นตัวเลขของ tracking ที่มีอยู่ (เดิมค่าอะไรก็ได้ = tracking_id 0), ชื่อไฟล์รูปต้องเป็นชื่อล้วน (`TempUpload::names` — เดิมบันทึก `../x` ได้) เหมือน save
- JS ผลรวม (`makeTopBottom`) / `ontempcal`: SR ใช้ `eval()` กับค่าใน input → `parseFloat()` (ค่าจาก load_toptable เป็นตัวเลขไม่มีจุลภาค ผลเท่ากัน; ช่องที่ผู้ใช้พิมพ์ "1+1" เดิมได้ 2 ตอนนี้ได้ 1 — เฉพาะตัวเลขแสดงตัวอย่างราคา ไม่กระทบค่าที่บันทึก)
- ค่าจาก DB ถูก escape (ชื่อล็อต/remark/รหัสย่อย/quote ในเมล ฯลฯ) และไม่มี target blank (ลิงก์รูป, ลิงก์ order, ปุ่มดูรายละเอียดในเมล)

### ช่องโหว่/บั๊กเดิมของ SR ที่เจอ

1. `save_quickfile.php` / `save.php` บันทึกชื่อไฟล์จาก POST ตรงๆ (ชื่ออย่าง `../x` ได้) และ `save_quickfile.php` ยอม `admin_lot_view` เขียน; `save_quickfile.php` ไม่ตรวจ id
2. `save.php` ทางเพิ่มใหม่ผูกรูปกับ tracking_id ว่าง (ข้างบน) และ `form.php` แสดง/ส่งรูปของพัสดุอื่นกลับไปบันทึก
3. `load_tracking.php` (อ่าน) เขียน `product_type_id` ผ่าน `GetCalPrice()` ให้ทุกคนที่เปิดได้ (รวม view อย่างเดียว) — และเรียกซ้ำ 2 ครั้งต่อคำขอ
4. ดับเบิลคลิก "บันทึก" = tracking ซ้ำ
5. `load_unpaid.php` / `load_image.php` / `load_quickfile.php` / `load_toptable.php` / `save_quickfile.php` ต่อ `$_POST` เข้า SQL เป็น string (อาศัยแค่การแปลง `'` ของ POST) — ใหม่ bind ทั้งหมด

### การตรวจ (2026-10-20, :8111 เดิม vs :8308 ใหม่, session เดียวกัน, normalize host/ช่องว่าง/คอมเมนต์ HTML)

- แอดมิน 11938: `/admin/lot` (ต่างเฉพาะ layout ของฐานร่วม: favicon/morris), grid ทุกหน้า 1–23 + กรอง รถ/เรือ/ทั้งหมด + ค้นหา = **ตรง**, `/admin/lot/219` (ต่างเฉพาะรูปผิดเจ้าของที่ตัด + ลำดับฟังก์ชัน JS + eval→parseFloat + `#lot_unpayment`), `load_lot_detail` page 0/1/2/ว่าง/-1/ล็อตไม่มี/`search=undefined` (ต่างเฉพาะรูปเล็กที่มาพร้อมแถว), `load_toptable` / `load_unpaid` / `load_quickfile` / `load_image` / `load_lot_memo` (ต่าง `.php` ใน URL autocomplete แบบ jl) / `load_user_rate` / `load_lastest_lot` / `load_ref_print` / autocomplete ×2 / `print_receive` = ตรง
- ล็อต E2E ที่สร้างเอง (ชื่อ zz) เทียบอีกรอบหลังเขียน: detail/toptable/unpaid/quickfile/image = ตรง (ต่างแค่ escape `'` `<`)
- ลูกค้า 8: `/lot`, `/lot/216`, `_lot/grid` หน้า 1/2/8/ค้นหา, `_lot/load_lot_detail` = ตรง
- ผู้ถือ admin_lot_view อย่างเดียว (user 6 role 12): หน้า/grid/detail อ่านได้ ตรงกับเดิม, ปุ่มเขียนทุกตัว (`save`, `save_quickfile`, `save_new_lot`, `save_lot_memo`, `delete`, `save_remove_tracking`) = ErrorBox สิทธิ์ ไม่มีแถวใน log การเขียน, `load_tracking` ไม่เขียน
- ไม่มีสิทธิ์ (user 11 role 7, ลูกค้า 8): หน้าแอดมิน/ajax = ErrorBox ของ `legacy.perm` (เดิมหน้าว่าง/ตอบว่าง — แบบเดียวกับที่ jl บันทึกไว้)
- ไม่ได้เทียบสดกับเดิม (ไฟล์เดิมเขียน DB → `goLog()` ของ bench exit ก่อนรัน SQL): `load_tracking` (GetCalPrice เขียน), `print`, `email_lot` และทุก `save*` — ตรวจด้วยการอ่านโค้ด + แถวที่เขียน
- `print_all` / `load_check_tracking` → 302 หน้าแรก

### E2E เขียน (ฝั่ง Laravel เท่านั้น, แถว zz ที่สร้างเอง แล้วลบตาม id)

`save_new_lot` (ยิงซ้อน 2 = 1 ล็อต + ErrorBox), `save_lot_name` / `_admin` / `ship_amount` / `status_lot` / `status_lot_date`, `save` เพิ่มใหม่ (subuser `zz_sub'1` → `zz_sub''1`, น้ำหนัก 2.55 → 2.5, `1,000` → 1000, รูป 2 ไฟล์ผูก id ใหม่, JS blockblob เฉพาะไฟล์ที่อยู่ใน temp; ยิงซ้อนก่อนใส่ guard = 2 แถว → หลังใส่ = 1 แถว + ErrorBox ซ้อน/ซ้ำ), `save` แก้ไข (ไม่ส่งรูป = รูปเดิมอยู่, ส่งรายการ = แทนทั้งหมด, craft → แถวลูก header), `save_quickfile` (JSON [JS, รูปเล็ก]; view = ปฏิเสธ), `save_lot_memo` / `save_delete_lot_memo`, `save_status_tracking` / `reset` (+ id `1) OR (1=1` ไม่แก้แถวใด), `print` (is_print, cross-site = 403), `email_lot` ×2 (คิว 1 แถว) + `lot:mail-worker` ส่งผ่าน log mailer (From SRShippingSystem, ปุ่ม #a3b2f1), `save_file_lot` (ชื่อ `../../.env` ไม่ copy), `save_verified` (11938 = ไม่มีสิทธิ์, user 1 = คำนวณใหม่), `cancel` (refill zz: ครั้งแรกยกเลิก ครั้งที่สอง ErrorBox), `save_remove_tracking`, `delete` (มี tracking = ErrorBox, ว่าง = is_delete)
ลบแล้ว: lot 267, tracking 1496/1497/1498, tracking_image 6068–6074 (ของ 1496–1498), lot_memo 1, lot_mail_queue 1, refill 259, ไฟล์ zz ใน `storage/app/legacy-upload` — ตรวจนับหลังลบ = 0
หมายเหตุ: ระหว่างเทียบ `load_tracking` ด้วยแอดมินบน Laravel (SR GetCalPrice เขียน product_type_id แบบเดิม) มี UPDATE กับแถวจริงของ bench 4 แถว (tracking 1, 417, 1040, 1410) — ค่าที่เขียนเท่ากับค่าที่สอดคล้องกับประเภทล็อตอยู่แล้ว (ล็อตเรือทุกแถวใน bench ใช้ type 5–8, 417 ไม่มีล็อตและ product type ไม่มีจริง → 0) จึงเชื่อว่าไม่มีค่าเปลี่ยน; หลังจากนั้นทดสอบด้วย user view หรือแถว zz เท่านั้น

### bench (`bin/bench`, -n 30 -c 1 ×2 รอบ, `artisan optimize`; mean ms รอบ 2)

| หน้า | เดิม | ใหม่ |
|---|---|---|
| `/admin/lot` (แอดมิน) | 414 | 39 |
| `_admin/_lot/grid` page 1 | 123 | 39 |
| `/admin/lot/219` (-n 20) | 491 | 41 |
| `_admin/_lot/load_lot_detail` id=219 page 0 | 53 | 34 |
| `_admin/_lot/load_toptable` id=219 | 23 | 26 |
| `_admin/_lot/load_unpaid` id=219 | 26 | 28 |
| `/lot` (ลูกค้า 8) | 334 | 33 |
| `_lot/grid` page 1 (ลูกค้า 8) | 33 | 36 |

`load_toptable`, `load_unpaid`, `_lot/grid` ช้ากว่าเดิม 2–3 ms = overhead คงที่ของ Laravel (~25 ms: boot + session/jail/สิทธิ์) กับ query เล็กมากบน bench ที่มี tracking ~1,400 แถว · หน้า detail ของล็อตทั้งหน้าเร็วขึ้นมากเพราะไม่ต้องยิง `load_image` ทีละ tracking

### test

`php8.4 vendor/bin/phpunit` ผ่าน · `JL_BENCH_DB_TESTS=1` กลุ่ม lot/tracking ผ่าน (`LotSrDbTest` 7 test ใหม่) — ที่ล้มเป็นของกลุ่มอื่น (มีบน main เหมือนกัน): `Money/GridPerfDbTest`, `Money/LegacyRisksDbTest` ×3, `Money/BillGridDbTest` (`$row->is_vat` ใน payment/customer/grid — SR ไม่มีคอลัมน์)

### คำถามถึงเจ้าของ

1. รูปของ tracking ที่ **เพิ่มใหม่** ผูกกับ id ใหม่ (แก้บั๊กเดิมที่รูปหลุดเป็น tracking_id 0) — ยืนยันหรือให้คงแบบเดิม? และรูปกำพร้า tracking_id 0 ใน production (bench 1,987 แถว) จะเก็บไว้หรือไม่
2. รายการลูกค้าค้างจ่ายแสดงในกล่องของตัวเอง (`#lot_unpayment`) แทนการทับกล่อง memo — ยืนยันหรือไม่
3. ช่อง "ถ่ายรูป" ในฟอร์มล็อต: SR `save.php` ไม่บันทึก `is_takeapic` (แสดงอย่างเดียว) — ต้องการให้บันทึกหรือไม่ (ตอนนี้คงแบบเดิม)
4. `save_file_lot` ยังบันทึกชื่อไฟล์ที่ส่งมาลง `lot.attachment` แม้ไม่ copy (ของเดิม/jl) — ควรปฏิเสธชื่อที่ไม่ใช่ชื่อไฟล์ล้วนหรือไม่
5. `save_verified` ยังฮาร์ดโค้ด user id 1, 2 (ปุ่มใน grid ของ SR ถูก comment ไว้ — ไม่มีทางกดจาก UI)


branch `mod/lot` · worktree `/home/admin/laravel/jl-wt/lot` · port 8208 (`bin/wt-serve lot --bg`)
ไฟล์เดิม: `_lot/*`, `_lot/_message/*`, `_admin/_lot/*` (43 ไฟล์ + RewriteRule 5 ตัว ใน `docs/url-map.md`)

## ไฟล์ของกลุ่ม

| ส่วน | ไฟล์ |
|---|---|
| routes | `routes/modules/lot.php` |
| ลูกค้า | `app/Http/Controllers/Lot/LotController.php`, `resources/views/lot/customer/*` |
| แอดมิน (อ่าน) | `app/Http/Controllers/Lot/Admin/AdminLotController.php`, `resources/views/lot/admin/*` |
| แอดมิน (เขียน) | `app/Http/Controllers/Lot/Admin/AdminLotSaveController.php` |
| พิมพ์ / Excel / อีเมล | `app/Http/Controllers/Lot/Admin/AdminLotPrintController.php`, `app/Domain/Lot/LotPrint.php`, `app/Domain/Lot/LotMailer.php` |
| worker อีเมล (แทน cron_lot_mail.php) | `app/Console/Commands/LotMailWorker.php` → `php8.4 artisan lot:mail-worker [--test]` |
| ตัวช่วย | `app/Domain/Lot/LotLookups.php` (prefix/paging/ดึงข้อมูลประกอบแบบ IN แทน N+1), `app/Domain/Lot/Php74.php` (`==`/ตัวเลขแบบ 7.4, `idList()`) |
| test | `tests/Feature/Lot/LotRoutesTest.php`, `tests/Unit/Lot/Php74Test.php` |

## routes (เดิม → ใหม่)

สิทธิ์: `VIEW` = `legacy.perm:admin_lot,admin_lot_view` (= `getPermission('admin_lot_view') || getPermission('admin_lot')` เดิม), `LOT` = `legacy.perm:admin_lot`
POST ทุกตัว (ยกเว้น `_lot/_message/*`) มี `legacy.ajax` และผ่าน `LegacyCsrf` (Origin/Referer เดียวกัน หรือ XHR — JS เดิมใช้ `$.ajax` จึงผ่าน)

| เดิม | ใหม่ | สิทธิ์ |
|---|---|---|
| `^lot$` → `_lot/index.php` | `GET /lot` | `legacy.perm:lot` |
| `^lot/([0-9]+)$` → `_lot/form.php` | `GET /lot/{id}` | `legacy.perm:lot` |
| `_lot/grid.php` | `POST /_lot/grid` | `legacy.auth` (+ ตรวจ `perm('lot')` ใน controller: ไม่มีสิทธิ์ = ตอบว่างแบบเดิม) |
| `_lot/load_lot_detail.php` | `POST /_lot/load_lot_detail` | `legacy.perm:lot` |
| `_lot/_message/{index,load,load_dept,send}.php` | `GET|POST /_lot/_message/*` → 404 | `legacy.auth` (ดู "ต่างจากเดิม") |
| `^admin/lot$` → `_admin/_lot/index.php` | `GET /admin/lot` | VIEW |
| `^admin/lot/create$`, `^admin/lot/([0-9]+)$` → `_admin/_lot/form.php` | `GET /admin/lot/create`, `GET /admin/lot/{id}` | VIEW |
| `_admin/_lot/grid.php` | `POST /_admin/_lot/grid` | VIEW |
| `_admin/_lot/load_autocomplete_tracking.php` | `POST /_admin/_lot/load_autocomplete_tracking` | VIEW |
| `_admin/_lot/load_autocomplete_user.php` | `POST /_admin/_lot/load_autocomplete_user` (JS ของ load_lot_memo เดิมเรียกแบบมี `.php` — view ใหม่เรียกแบบไม่มี `.php` เพราะ nginx ไม่ส่ง `*.php` มา Laravel) | VIEW |
| `_admin/_lot/load_check_tracking.php` | `POST /_admin/_lot/load_check_tracking` | VIEW (เขียน `is_checked` เฉพาะ admin_lot) |
| `_admin/_lot/load_lastest_lot.php` | `POST /_admin/_lot/load_lastest_lot` | VIEW |
| `_admin/_lot/load_lot_detail.php` | `POST /_admin/_lot/load_lot_detail` | VIEW |
| `_admin/_lot/load_lot_memo.php` | `POST /_admin/_lot/load_lot_memo` | VIEW |
| `_admin/_lot/load_ref_print.php` | `POST /_admin/_lot/load_ref_print` | VIEW |
| `_admin/_lot/load_tracking.php` | `POST /_admin/_lot/load_tracking` | VIEW |
| `_admin/_lot/load_user_rate.php` | `POST /_admin/_lot/load_user_rate` | VIEW |
| `_admin/_lot/save.php` | `POST /_admin/_lot/save` | LOT (เดิม VIEW — แก้ 2026-10-18) |
| `_admin/_lot/save_delete_lot_memo.php` | `POST /_admin/_lot/save_delete_lot_memo` | LOT (เดิม VIEW — แก้ 2026-10-18) |
| `_admin/_lot/save_file_lot.php` | `POST /_admin/_lot/save_file_lot` | LOT (เดิม VIEW — แก้ 2026-10-18) |
| `_admin/_lot/save_lot_memo.php` | `POST /_admin/_lot/save_lot_memo` | LOT (เดิม VIEW — แก้ 2026-10-18) |
| `_admin/_lot/save_lot_name.php` | `POST /_admin/_lot/save_lot_name` | LOT (เดิม VIEW — แก้ 2026-10-18) |
| `_admin/_lot/save_lot_name_admin.php` | `POST /_admin/_lot/save_lot_name_admin` | LOT (เดิม VIEW — แก้ 2026-10-18) |
| `_admin/_lot/save_lot_ship_amount.php` | `POST /_admin/_lot/save_lot_ship_amount` | LOT (เดิม VIEW — แก้ 2026-10-18) |
| `_admin/_lot/save_new_lot.php` | `POST /_admin/_lot/save_new_lot` | LOT (เดิม VIEW — แก้ 2026-10-18) |
| `_admin/_lot/save_print.php` | `POST /_admin/_lot/save_print` | LOT (เดิม VIEW — แก้ 2026-10-18) |
| `_admin/_lot/save_remove_tracking.php` | `POST /_admin/_lot/save_remove_tracking` | LOT (เดิม VIEW — แก้ 2026-10-18) |
| `_admin/_lot/save_status_lot.php` | `POST /_admin/_lot/save_status_lot` | LOT (เดิม VIEW — แก้ 2026-10-18) |
| `_admin/_lot/save_status_lot_date.php` | `POST /_admin/_lot/save_status_lot_date` | LOT (เดิม VIEW — แก้ 2026-10-18) |
| `_admin/_lot/save_status_reset_tracking.php` | `POST /_admin/_lot/save_status_reset_tracking` | LOT (เดิม VIEW — แก้ 2026-10-18) |
| `_admin/_lot/save_status_tracking.php` | `POST /_admin/_lot/save_status_tracking` | LOT (เดิม VIEW — แก้ 2026-10-18) |
| `_admin/_lot/print_all.php` | `GET /_admin/_lot/print_all?lot=` (.xls) | VIEW (เขียน `is_print` เฉพาะ admin_lot) |
| `_admin/_lot/print.php` | `GET /_admin/_lot/print?lot=` | LOT |
| `_admin/_lot/print_receive.php` | `GET /_admin/_lot/print_receive?id=` | LOT |
| `_admin/_lot/email_lot.php` | `GET /_admin/_lot/email_lot?id=` (เข้าคิว `lot_mail_queue`) | LOT |
| `_admin/_lot/delete.php` | `POST /_admin/_lot/delete` | LOT |
| `_admin/_lot/save_verified.php` | `POST /_admin/_lot/save_verified` | LOT + user id 1/2 เท่านั้น (แบบเดิม) |
| `_admin/_lot/cancel.php` (ยกเลิก refill) | `POST /_admin/_lot/cancel` | `legacy.perm:admin_refill` |
| `_admin/_lot/cron_lot_mail.php` | `GET|POST /_admin/_lot/cron_lot_mail` → 403 "cron only"; งานจริง = `php8.4 artisan lot:mail-worker` | LOT |
| `_admin/_lot/email.php` | `GET|POST /_admin/_lot/email` → 403; เนื้อหาเมลอยู่ใน `LotMailer::sendMail()` | LOT |

## การตรวจ (2026-10-18, :8101 เดิม vs :8208 ใหม่, DB `admin_jl_bench`)

- **อ่าน** (ลูกค้า user 137 / zz_fdn_cust 11937, แอดมิน zz_fdn_admin 11938): `/lot`, `/lot/{id}`, `_lot/grid`, `_lot/load_lot_detail`, `/admin/lot`, `/admin/lot/{id}`, `_admin/_lot/grid`, `load_*`, `print*` — HTML ตรงกับเดิม ต่างเฉพาะช่องว่าง/ขึ้นบรรทัด (diff หลัง normalize host + whitespace)
- **เขียน**: ทดสอบกับ Laravel เท่านั้น ด้วยแถว zz ที่สร้างเอง (lot `zz_lot_test`, tracking `zztrk1/zztrk2`, refill `zz_lot_cancel_test`) แล้วนับแถวที่เปลี่ยนด้วย SELECT ที่กรองช่วงเวลา/ id — มีแต่แถวที่ตั้งใจเท่านั้น
  - `save` (insert/update/craft/ผู้ใช้ไม่พบ/craft ไม่พบ), `save_new_lot`, `delete` (ล็อตมี tracking → error, ว่าง → is_delete=1), `save_verified` (user 11938 → ไม่มีสิทธิ์, user 1 → คำนวณใหม่ + is_verified), `save_file_lot` (copy ไฟล์ temp → upload, ชื่อไฟล์ `../` ไม่ copy), `save_print`, `cancel`, `save_remove_tracking`, `email_lot` (เข้าคิว 1 แถว), worker (`--test` และส่งจริงผ่าน log mailer — รันใน transaction แล้ว rollback)
  - ค่าที่ลง DB เทียบกับ SQL ที่ไฟล์เดิมสร้าง (อ่านจากโค้ด): `'`→`''`, ตัด `\`, numberformat แบบตัดทศนิยม, `date_warehouse = NOW()` เมื่อเดิมเป็นศูนย์, ship = type ของล็อต ฯลฯ
- **เว็บเดิมบน :8101 เขียน DB ไม่ได้อยู่แล้ว**: `mysqlquerys()` ทุกคำสั่งที่ไม่ใช่ SELECT เรียก `goLog()` → `fopen('/home/admin/web/portal.jlcargoth.com/public_html/logs/<วันที่>.log')` ซึ่งเปิดไม่ได้บน bench → `exit("Can't open ...")` ก่อนรัน SQL
  (ทดลอง `save_new_lot.php` 1 ครั้ง: ตอบ `Can't open ...` และไม่มีแถวใหม่) — **ถ้าไฟล์ log ของวันนั้นเปิดได้ เว็บ bench จะเขียน log ลง webroot production** ⇒ ไม่ได้ยิง write ไปเว็บเดิมอีก และไม่ยิง payload ใดๆ ไปเว็บเดิม (กฎหลังเหตุการณ์ tracking)
- SQL injection: ทดสอบเฉพาะ Laravel — `_lot/grid` (txtsearch `x' OR 1=1 -- `) ตอบปกติ, `print?lot=5441) OR (1=1` ไม่แก้แถวใด, `delete arr_id=,5442) OR (1=1` ไม่แก้แถวใด; `Php74::idList()` ปฏิเสธ payload ทุกแบบ (unit test)
- ผู้ไม่ล็อกอิน → 302 `/login` (หน้า, ajax, print, email_lot); POST ข้าม origin → 403
- `php8.4 vendor/bin/phpunit` ผ่าน (รวม RouteSecurityTest, RouteModulesTest, LotRoutesTest, Php74Test)

## bench (`bin/bench`, -n 30 -c 1, opcache + `artisan optimize`)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/admin/lot` (แอดมิน) | 306 | 41 |
| `_admin/_lot/grid` page 1 | 3359 | 46 |
| `/lot` (ลูกค้า 137) | 300 | 35 |
| `_lot/grid` page 1 (ลูกค้า 137) | 2841 | 239 |
| `_admin/_lot/load_lot_detail` id=5400 (-n 20) | 175 | 46 |

`_lot/grid` ใหม่ยังใช้เวลา ~240 ms เพราะต้องนับ tracking ของผู้ใช้ต่อล็อต (ไม่มี index `tracking(user, lot_id)`) — ข้อเสนอ: เพิ่ม index `(user, lot_id, is_delete)` (ไม่ได้สร้างเอง)

## ต่างจากเดิมโดยตั้งใจ

- **กันกดซ้ำ/ส่งซ้อน (double submit)** (เจ้าของอนุมัติ 2026-10-18, E2E: ดับเบิลคลิก `_taobao/save` = 2 รายการ ตัดเงิน 2 ครั้ง — เว็บเดิมก็เป็น): middleware `legacy.once` (`App\Support\SubmitGuard`) — lock ต่อ ผู้ใช้+route ระหว่างประมวลผล (คำขอที่ซ้อนได้ `ErrorBox('รายการนี้กำลังดำเนินการ กรุณารอสักครู่')`) + payload เดิมที่สำเร็จแล้วภายใน 8 วินาทีได้ `ErrorBox('รายการนี้ถูกส่งไปแล้ว (ส่งซ้ำ) ...')` (คำตอบ error/กล่อง PIN ไม่ถูกจำ ส่งใหม่ได้ทันที): `_admin/_lot/save_new_lot` (lock อย่างเดียว — สร้าง lot ว่างต่อกันได้) · test: `tests/Feature/SubmitGuardTest.php`, `tests/Feature/Money/DoubleSubmitDbTest.php` (JL_BENCH_DB_TESTS=1)
  - ชั้นนี้กันรายการซ้ำ; การกันยอดติดลบเมื่อคำขอต่างกันมาพร้อมกันยังเป็น transaction + `SELECT … FOR UPDATE` แถว user แล้วตรวจยอดภายใน (ทุก endpoint ตัดเงินตอนสร้าง)
- **admin_lot_view = ดูอย่างเดียว** (เจ้าของอนุมัติ 2026-10-18, รายงานข้อ 4.2 — เช่น user 74 role 4 โกดังจีน เคยสร้าง lot/memo ได้): endpoint เขียนทุกตัว (`save`, `save_new_lot`, `save_lot_memo`, `save_delete_lot_memo`, `save_lot_name*`, `save_lot_ship_amount`, `save_status_*`, `save_remove_tracking`, `save_file_lot`, `save_print`, `delete`, `save_verified`, `print`, `print_receive`, `email_lot`) ต้องมี `admin_lot` — ผู้ถือ `admin_lot_view` อย่างเดียวได้ `ErrorBox('ไม่มีสิทธิ์...admin_lot')` แบบ `getPermissionAlert` เดิม (header `X-Legacy-Denied: 1`) ไม่มีการเขียน DB
  - endpoint อ่านที่เดิมมีผลข้างเคียง: `load_check_tracking` (ทำเครื่องหมาย `tracking.is_checked`) และ `print_all` (`lot.is_print = 1`) — ผู้ถือ `admin_lot_view` เห็นผลเหมือนเดิมแต่ไม่เขียน
  - หน้า `/admin/lot/create` และปุ่มต่างๆ ยังแสดงเหมือนเดิม (กดแล้วได้ ErrorBox สิทธิ์) — ไม่ได้ซ่อนปุ่มใน view
  - เว็บเดิมไม่เปลี่ยน · test: `tests/Feature/Lot/LotViewOnlyTest.php`, `LotRoutesTest.php` · ตรวจสดบน :8298 ด้วย session user 74: save_new_lot/save_lot_memo/save/delete/... = ErrorBox, grid/หน้า admin/lot อ่านได้, `load_check_tracking` ไม่เปลี่ยน is_checked, `print_all` ไม่เปลี่ยน is_print
- `_admin/_lot/cancel` (ยกเลิก refill, สิทธิ์ admin_refill — สำเนาของ `_admin/_refill/cancel.php` เดิม): เดิมยกเลิกได้ทุกสถานะ (รวมรายการที่อนุมัติ/เติมเงินแล้ว) → กติกาเดียวกับ `_admin/_refill/cancel` ผ่าน `App\Domain\Money\Money::cancelPendingRefill()`: เฉพาะ status 1, อนุมัติแล้ว/ยกเลิกแล้ว/ไม่พบ = `ErrorBox` ไม่เขียนแถว (กดซ้ำได้ผลเดิม); คอลัมน์ที่เขียนเท่าไฟล์เดิม (status, mod_date, mod_by — ไม่มี cancel_remark/อีเมล) · test: `tests/Feature/Money/MoneyMoveDbTest.php` (JL_BENCH_DB_TESTS=1)
- ทุก query bind ค่า; รายการ id ที่เดิมฝังลง `IN (...)` ตรงๆ (`delete`, `save_remove_tracking`, `save_status_*`, `print`, `print_all`) รับเฉพาะตัวเลขล้วน — ไม่ใช่ตัวเลข = ไม่แก้อะไร (เดิมเป็น SQL error หรือ injection) แต่ตอบข้อความสำเร็จเหมือนเดิม
- ค่าจาก DB/ผู้ใช้ใน HTML/JS/อีเมล ถูก escape (เดิม echo ดิบ)
- `/lot/{id}` ลูกค้า: แสดงหัวล็อตเฉพาะล็อตที่มี tracking ของตัวเอง (เดิมเปิดหัวล็อตของ id ใดก็ได้); รายการ tracking กรอง `user = Legacy::userId()` เหมือนเดิม
- `_lot/_message/*`: เดิม `require '../system_config.php'` ผิด path → HTTP 500 ทุกครั้ง (ใช้งานไม่ได้) → 404; แชทจริงอยู่กลุ่ม messaging
- `delete` ล็อตที่ยังมี tracking: เดิม `getError()` ไม่มีอาร์กิวเมนต์ = fatal (HTTP 500 เปล่า) → ตอบ `ErrorBox('');`
- `save_file_lot`: เดิม `copy('../../upload/temp/'.$value, ...)` โดยไม่ตรวจชื่อ (path traversal) → รับเฉพาะชื่อไฟล์ล้วน; dev เขียน `storage/app/legacy-upload` (driver local, ไม่ตอบ JS อัป Azure); production (driver azure) ตอบ JS `_api/blockblob_ajax` แบบเดิม
- `cron_lot_mail.php` / `email.php`: เดิมเปิดผ่านเว็บได้โดยไม่ล็อกอิน (ใครก็สั่ง worker/ส่งเมลได้) → route ตอบ 403, worker เป็นคำสั่ง artisan; ต้องตั้ง cron `* * * * * php8.4 artisan lot:mail-worker` ตอนสลับ (ยังไม่ได้ตั้ง — ห้ามแตะ cron)
- อีเมล: ส่งผ่าน Mail ของ Laravel (dev `MAIL_MAILER=log`, และถ้า `legacy.external.enabled` = false บังคับ log mailer) — ไม่มีการส่งจริง; ข้อความ/HTML (header/center/footer + CRLF ของ email.php) เท่ากับเดิมตามโค้ด
- LINE: `sendLine()` เดิมเป็นฟังก์ชันว่าง → ไม่ส่ง (log ไว้เท่านั้น)
- หน้า GET ของแอดมิน (`print`, `print_receive`, `email_lot`) เมื่อไม่มีสิทธิ์: เดิมพิมพ์ข้อความ `ErrorBox(...)` เปล่าๆ ในแท็บใหม่ → หน้า no-permission มาตรฐานของ `legacy.perm`; `print_all` เดิมตอบว่าง → เช่นเดียวกัน; ไม่ล็อกอิน → `/login`
- `print`, `print_all`, `email_lot` (GET ที่เขียนข้อมูล: `is_print`, เข้าคิวส่งเมลถึงลูกค้าทั้งล็อต): คำขอที่มาจากเว็บอื่น (`Sec-Fetch-Site: cross-site/same-site` หรือ Referer ต่าง host) → 403 — `LegacyCsrf` ตรวจแค่ POST; เดิมแค่ `<img src=".../email_lot?id=N">` ในหน้าที่แอดมินเปิดก็สั่งส่งเมลได้ (review 2026-10-18)
- `delete` `id=5,6`: เดิมนับ tracking เฉพาะ `lot_id = '5,6'` (= 5) แต่ลบทุก id ใน `IN (5,6)` → ลบล็อตที่ยังมี tracking ได้ → ต้องไม่มี tracking ทุกล็อตที่จะลบ (JS เดิมส่ง id เดียว ผลเหมือนเดิม)
- `print_all`: ชื่อไฟล์ใน `Content-Disposition` เหลือแค่ตัวเลขและ `,` (เดิม header injection ได้)
- `load_check_tracking` ช่องว่าง: เดิม `LIKE '%%'` ดึง tracking ทั้งระบบ (~470k แถว) จนหน้าค้าง → ถือว่าไม่พบข้อมูล
- `save` / `save_verified` ห่อ `DB::transaction()` (เดิมไม่มี); `email_lot` เป็น UPDATE + INSERT IGNORE (idempotent) ไม่ได้ห่อ
- N+1 ที่แก้: grid แอดมิน (COUNT ทั้งตาราง + 3 query ต่อแถว), grid ลูกค้า (subquery COUNT ต่อแถว), แถวลูก craft (`header = id`) ใน detail/print, autocomplete (2 query ต่อแถว), worker (ไม่เปลี่ยน — ทีละคิวแบบเดิม)
- `print` / `print_all`: `header_count` เดิมเป็น subquery ต่อแถว `t2.header = tracking.id` (TEXT เทียบ int → ใช้ index ไม่ได้ = สแกน tracking ทั้งตารางต่อแถว; ล็อต 102 tracking ≈ 75 วินาที → 504) → นับครั้งเดียวด้วย `header IN ('id', ...)` (ค่า header เป็นตัวเลขล้วน → ผลเท่าเดิม, เทียบหน้าพิมพ์ล็อต 5437/4795/6 กับเว็บเดิมแล้วตรงกัน — acceptance A 2026-10-18)
- `print_receive` ไม่มี `id` / `id=0`: เดิม `lot_id = ''` = ดึง tracking ที่ยังไม่เข้าล็อตทั้งระบบ (~69k แถว, หน้า ~48MB) → ตารางว่าง (UI เปิดพร้อม id ล็อตเสมอ)
- `save_verified` ยังเรียก `Pricing::calPriceAutoDiff()` ทีละ tracking (ตรรกะคำนวณต่อแถวของฐานร่วม)

## ช่องโหว่เดิมที่เจอ (แก้ในพอร์ตแล้ว)

1. SQL injection ทุกไฟล์ที่รับ POST/GET (ต่อ string ตรงๆ) — ร้ายแรงสุด: `save_remove_tracking.php`, `delete.php`, `save_status_tracking.php`, `save_status_reset_tracking.php` ฝัง `id` ลง `WHERE id IN (...)` ของ UPDATE โดยไม่ quote → `id=1) OR (1=1` แก้ทั้งตาราง (เหตุการณ์ที่ล้าง `tracking.lot_id` ทั้ง 546k แถวใน bench) และ `print.php` / `print_all.php` (`UPDATE lot SET is_print` + SELECT) ผ่าน GET `?lot=`
2. `cron_lot_mail.php` และ `email.php` ไม่ตรวจล็อกอิน/สิทธิ์
3. `save_file_lot.php` path traversal ใน `copy()` (อ่านไฟล์ใดๆ ที่ PHP อ่านได้ไปไว้ใน `upload/` ที่เปิดสาธารณะ)
4. Stored XSS: ชื่อล็อต, tracking, remark, memo ฯลฯ ถูก echo ดิบในหน้าแอดมิน/ลูกค้า/อีเมล/JS (`save_print` ฝังค่าลง `window.open('...')`)
5. `_lot/form.php` (IDOR) แสดงหัวล็อตของ id ใดก็ได้
6. `getUserID()` (`explode('&')[1]`) ใน `_lot/grid.php`, `_lot/load_lot_detail.php` — username ที่มี `&` ได้ id ของคนอื่น (CONVENTIONS ข้อ 3.5) → ใช้ `Legacy::userId()`
7. `print_all.php` header injection ผ่าน `filename=$id`
8. ไม่มี CSRF ทุก POST (ใหม่: `LegacyCsrf`) และ GET ที่เขียนข้อมูล (`email_lot`, `print`, `print_all`) ยิงข้ามเว็บได้ (ใหม่: ตรวจ `Sec-Fetch-Site`/Referer)
10. `delete.php` ตรวจ tracking เฉพาะ id แรกของรายการ แต่ลบทุก id
9. `goLog()` เขียน log ไปไฟล์ใน webroot production (`/home/admin/web/portal.jlcargoth.com/public_html/logs/`) — ไฟล์ log ที่อยู่ใต้ webroot อาจเปิดอ่านได้จากเว็บ (ไม่ได้ตรวจ production ตามกติกา) และ bench copy ชี้ path เดียวกัน

## ไม่ได้พอร์ต / ค้าง

- ไม่ได้ตั้ง cron ของ `lot:mail-worker` (ต้องทำตอนสลับ — นอกขอบเขต)
- `_lot/_message/*` ตอบ 404 (เดิมใช้งานไม่ได้)
- เทียบผลการเขียนกับเว็บเดิมแบบยิงจริงไม่ได้ (เว็บเดิมบน bench exit ก่อนรัน SQL) — เทียบด้วยการอ่านโค้ด + SQL ที่ไฟล์เดิมสร้าง
- `save_verified` อนุญาตเฉพาะ user id 1, 2 แบบเดิม (ฮาร์ดโค้ด) — ให้เจ้าของตัดสินใจว่าจะเปลี่ยนเป็นสิทธิ์หรือไม่
