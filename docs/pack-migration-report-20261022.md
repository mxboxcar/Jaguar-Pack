# รายงานการย้าย portal.pack-cargo.net (pack) ไป Laravel

วันที่ 2026-10-22 · repo `/home/admin/laravel/pack` → GitHub `mxboxcar/Jaguar-Pack` (main `3330e00`, push แล้ว) · แม่แบบ: งาน ship2u (`/root/ship2u-migration-report-20261021.md`)

## 0. สลับเว็บจริงแล้ว (2026-10-22 09:06 — เจ้าของสั่ง "สลับทั้ง portal และ www เลย")

- **www** (`switch-www.sh`): `pack-cargo.net` เสิร์ฟจาก Laravel · หน้าเว็บ 14 หน้าตรงกับ snapshot ทุกไบต์ · `/wp-login.php`, `/xmlrpc.php` → 404 · ปิด pool PHP 7.4 ของ WordPress แล้ว · ถอยกลับ: `/root/pack-new-subfolder/rollback-switch-www.sh`
- **portal** (`switch.sh`): `portal.pack-cargo.net` ทั้งเว็บเป็น Laravel (`pack-live` `3330e00`) · `.env` URL หลัก + ส่งเมลจริง (mailgun) · cron เมล lot/notice ย้ายเป็น `artisan` ใน `/etc/cron.d/pack-live` (ของเดิม comment ไว้) · ถอยกลับ: `/root/pack-new-subfolder/rollback-switch.sh`
- **unban ไม่เปลี่ยนเป็น GET** (ต่างจาก brief): บทเรียนจาก ship2u/jl หลังสลับ — GET ทำให้ token ไปอยู่ใน access log ของ nginx · แก้ให้ Laravel รับ POST ที่ `_api/unban` โดยยกเว้นการตรวจ Origin เฉพาะ route นี้ (`3330e00`, ยังต้องมี token) · cron เดิมยิง POST ได้ 200 แล้ว
- ตรวจหลังสลับ: login 200, หน้าที่ต้องล็อกอิน → login, asset 200, socket.io 200, URL `.php` เดิม → Laravel, `/new/...` → 301 URL หลัก, POST ไม่มี Origin 403 · ผ่าน Cloudflare ทั้งสองโดเมน 200 · ลูกค้าจริงบันทึกการชำระเงิน/ใบเสนอราคาผ่าน Laravel ได้แล้ว · ไม่มี 5xx · cron เมลรันแล้วไม่มี error
- autodeploy ใช้ URL หลักแล้ว (24 หน้า) · เพิ่มทั้งสองโดเมนในระบบเฝ้า 24 ชม. (`/root/leopard-watch/php8-watch.py`, backup `.bak-pack-20261022`)
- **ปิด PHP 7.4 แล้ว** (09:20, เจ้าของสั่ง "ปิดเลย"): pool ของ portal เดิมย้ายไป `/root/legacy-pools-disabled-20261022/` · เป็น pool สุดท้าย จึงหยุดและ disable `php7.4-fpm` ด้วย · `reenable.sh` แก้ให้ start service ได้ (ใช้ได้กับ rollback ของ jl/SR/ship2u/sang ด้วย) · ตรวจแล้วทุกเว็บบนเครื่องยังตอบปกติ
- **เก็บกวาด WordPress แล้ว** (10:42, เจ้าของสั่ง): export DB `admin_packwp2` เก็บไว้ แล้วลบ MySQL user `packwp2` · ลบไฟล์ 4,073 ไฟล์ (.php ทั้งหมด, `wp-admin/`, ไฟล์ตั้งค่า/แปลภาษา) เหลือรูป/CSS/JS/ฟอนต์ 2,480 ไฟล์ — asset 98 ไฟล์ที่หน้า www อ้างถึงยัง 200 ครบ · ลบสำเนาที่โดนเจาะ `public_html.infected-20261014` (310 MB) · archive ของที่ลบ (ยกเว้นสำเนาที่โดนเจาะ): `/root/pack-wp-archive-20261022/` (root 600) · `rollback-switch-www.sh` กู้ให้เอง · ยังเหลือ: DB `admin_packwp2` (ไม่มี user เข้าได้นอกจาก root), user Linux `site_packwp` + โฟลเดอร์ `tmp`

## 1. สรุป

- **fork จาก SR** (`7e7f1e5`) ตาม brief — แต่ pack ต่างจาก SR มากกว่า ship2u มาก
  - ไฟล์ .php ที่ต่างจาก SR 182 ไฟล์ (~6,200 บรรทัด) · ship2u ต่างแค่ 58
  - pack เป็น **ลูกผสม**: ฐานเป็นโค้ด SR (114 ไฟล์ใกล้ SR) แต่มีฟีเจอร์แบบ jl ที่ SR ตัดไป
    - VAT ในบิลชำระ (`bill_payment.is_vat/vat/v/shipv/...`), คูปองส่วนลด (ตาราง `coupon`, `bill_payment.discount`), รายงานแบบ jl
  - pack **ไม่มี** ฟีเจอร์เฉพาะ SR: ใบเสร็จ/ใบกำกับภาษี (`receipt_*`), printorder, findproduct, เช็คแทร็ก, ถ่ายรูปสินค้า, subuser, เลขรัน, ระบบชั้นวางในโกดัง (ไม่มีตาราง `warehouse*`)
  - ส่วนที่ pack เหมือน jl ใช้โค้ด Laravel ของ jl เป็นแม่แบบ
  - รายการไฟล์ที่ต่างทั้งหมด: `docs/pack-diff.md` (สร้างด้วย `tools/pack-diff.py`) + ตาราง "foundation helpers"
- **ทุกกลุ่มพอร์ตครบ** (foundation + 13 กลุ่ม แบ่ง 7 ชุดทำพร้อมกัน) รายละเอียดต่อกลุ่มอยู่ในหัวข้อ "## PACK (2026-10-22)" ของ `docs/modules/<group>.md`
- **ตัดออก**: LINE Notify (ตามที่เจ้าของสั่ง), หน้าของ SR ที่ pack ไม่มี, ระบบชั้นวางโกดัง (pack ไม่มีตาราง/หน้า/สิทธิ์)
- **ติดตั้ง /new/ แล้ว** (08:10): `https://portal.pack-cargo.net/new/` ต้องมี cookie `pknew` (ค่าใน `/root/pack-new-subfolder/gate-cookie`) · ต่อ DB จริง `admin_packcargo` · เมลยังเป็น `log` · ถอดออกคำสั่งเดียว `rollback-new.sh`
- **autodeploy เปิดแล้ว** (`/etc/cron.d/pack-autodeploy` ทุก 2 นาที) — รอบจริงรอบแรก deploy `5e88111` ผ่าน (phpunit ใน `pack-ci` + เช็ค 19 หน้า) · Beacon หน้า Repo มี `pack` แล้ว
- **ย้าย www (`pack-cargo.net`) ด้วย** (เจ้าของสั่งเพิ่ม): เดิมเป็น WordPress 5.2 + Divi บน PHP 7.4 (เคยโดนฝัง webshell 14 ต.ค.) → Laravel ของ pack เสิร์ฟหน้าเดิมทุกไบต์ (หัวข้อ 7) · ดูตัวอย่างที่ `https://pack-cargo.net/new/` (cookie เดียวกัน) · **ยังไม่สลับ** รอเจ้าของสั่ง (`switch-www.sh --yes`)

## 2. ผลทดสอบ

| ตรวจอะไร | ผล |
|---|---|
| phpunit ไม่ใช้ DB | **336 ผ่าน** (114 ข้าม = เทส DB) — รวมเทส www 8 ตัว |
| phpunit ใช้ bench DB `admin_pack_bench` | **336/336 ผ่าน** |
| /new/ บนเว็บจริง (cookie gate) | ไม่มี cookie → เด้งหน้าแรก · มี cookie `/new/login` 200 · asset 200 · POST ไม่มี Origin (`system_check_login`, `_tracking/save`, `_admin/_user/save`) 403 · ต่อ DB จริงได้ (user 1,063 คน) · อ่าน key Azure/Mailgun ได้ · เว็บเดิม `/login` ยัง 200 |
| www เทียบกับ WordPress เดิม (266 URL รวมค้นหา 31 คำ × 5 แบบ) | **เหมือนทุกไบต์ 253** · ที่เหลือ = หัวข้อ 7 · ผ่าน nginx + pool จริงที่ `/new/`: 24/24 เหมือน |
| smoke ทุก route บน :8800 | GET 201 route × 3 สถานะ (ไม่ล็อกอิน / ลูกค้า / แอดมิน): **ไม่มี 500** · POST 327 route ที่ไม่มี Origin: **403 ทุกตัว** |
| เทียบ 1:1 หน้า GET กับเว็บเดิม bench (:8114) session เดียวกัน | แอดมิน 139 หน้า เหมือน 121 · ลูกค้าจริง 53 หน้า เหมือน 41 · ที่เหลือตรวจทีละหน้าแล้ว = ต่างโดยตั้งใจ (หัวข้อ 3) |
| เทียบ 1:1 ajax อ่านอย่างเดียว | แอดมิน 109 endpoint (151 ครั้ง) · ลูกค้า 42 endpoint (57 ครั้ง) → ต่างแค่บรรทัดว่าง + จุดที่ตั้งใจ |
| เทียบรายกลุ่มโดย agent | รวม ~550 หน้า/ajax ทั้งแอดมิน, staff role 3/4/6/7, ลูกค้า, ภาษา th/en/cn, หน้า 1/2 ของ grid, การปฏิเสธสิทธิ์ |
| E2E การเขียน (ฝั่ง Laravel เท่านั้น ใน bench) | ทุก flow เงิน/คำสั่งซื้อ/ล็อต/บิล รวมกดซ้ำพร้อมกัน — แถวที่เขียนตรงกับที่โค้ดเดิมจะเขียน · ลบแถวทดสอบทุกแถวแล้ว |
| สิทธิ์ (`docs/role-matrix.md`) | `getPermission()` เดิมกับ `Permissions` ใหม่ **ตรงกันทุกช่อง** (9 role × 66 สิทธิ์) · role 2 = ลูกค้า |
| secret-scan | 0 hit (ตรวจกับค่าลับ 33 ตัว) |

**ความเร็ว** (ms, เว็บเดิม bench → Laravel dev; ตอนวัดเครื่องโหลดสูงจากหลาย agent ตัวเลขเว็บเดิมจึงสูงเกินจริง)

| หน้า | เดิม | ใหม่ |
|---|---:|---:|
| `/order` (ลูกค้า) | 4,963 | 80 |
| `/payment` | 2,400 | 104 |
| `_payment/grid` | 8,087 | 124 |
| `/admin/order` | 1,737 | 93 |
| `/admin/export` | 4,172 | 169 |
| `/admin/tracking` | 1,140–2,795 | 117–170 |
| `_admin/_lot/grid` | 11,031 | 474 |
| `/admin/user/348` | 1,225–6,120 | 108–117 |
| `/admin/setting` | 2,283–5,191 | 79–117 |
| `/balance` | 458 | 59 |

## 3. ต่างจากเว็บเดิมโดยตั้งใจ

**ทุกข้อที่ใช้กับ jl/SR/ship2u ใช้กับ pack ด้วย**: escape ค่า, CSRF, ตรวจเจ้าของรายการ, หน้าไม่มีสิทธิ์แสดงกล่องแจ้ง + layout เต็ม, ไม่มี `target="_blank"`, favicon, 404 → หน้าแรก, row lock และกันกดซ้ำในงานเงิน, ล็อกอินอยู่แล้วเปิด `/login` → ไปแดชบอร์ด

**ช่องโหว่/บั๊กของเว็บเดิม pack ที่ Laravel ปิดแล้ว**
1. **สินค้าสูญหาย (เงิน)**: ถอยการอนุมัติแล้วไม่ดึงเงินคืน (หา `¥77.00` แต่ตอนอนุมัติเขียน `¥77`) → อนุมัติซ้ำได้เงินสองรอบ · ใน bench เจอ **LOS-1519 (ลูกค้า 60) เกิน 244.80 บาท** และ **LOS-1484 (ลูกค้า 55) เกิน 1.00 บาท** · Laravel หาเจอทั้งสองแบบ และไม่ยอมอนุมัติถ้าคำสั่งซื้อไม่มีเรต (เดิมหารศูนย์แล้วจ่ายเต็ม)
2. **ปุ่มลบคูปอง** ไม่ลบคูปอง แต่ไป soft-delete **ประเภทสินค้า** ที่ id เดียวกัน (เช็คสิทธิ์ `admin_product_type`) → Laravel ตอบสำเร็จแต่ไม่แตะอะไร
3. **คูปองใช้ซ้ำได้** ถ้าชำระสองบิลพร้อมกัน → ล็อกแล้ว · ช่องค้นหาคูปอง XSS → escape · "1,000" ถูกเก็บเป็น 1.00 → บังคับตัวเลข
4. **`/export/0` เผยโค้ดคูปองที่ยังไม่มีคนใช้** ให้ลูกค้าทุกคน → ปิดแล้ว
5. **ฟอร์มโอนเงินของแอดมิน** เช็คแค่สิทธิ์ `transfer` (ลูกค้าก็มี) → ลูกค้าเปิดรายการโอนของคนอื่นได้ → ต้องมี `admin_transfer`
6. **สมัครสมาชิกกดซ้ำเร็วๆ** ได้ผู้ใช้ชื่อซ้ำสองคน → เช็คซ้ำใต้ล็อก
7. แถบแจ้งเตือนแอดมินอ่านตาราง `tracking_check` ที่ pack ไม่มี (500) → ตัดรายการนั้น

**ต่างเล็กน้อยอื่นๆ**: `save_add_new_track` เดิมไม่เคยผูก tracking ใหม่กับรายการ (ตัวแปร `$ins` ไม่ถูกกำหนด) → Laravel ผูกให้ (เหมือน SR/jl) · เทียบ goodsconfirm ของ tracking ที่ไม่ใช่ของตัวเอง แสดงข้อมูลว่าง (แก้ IDOR) · ปัดเศษยอดบาทใช้ `round()` แบบ PHP 7.4 (ทดสอบกับ php7.4 1.5 ล้านค่า ไม่มีต่าง)

## 4. เรื่องที่รอเจ้าของตัดสินใจ (ตอนนี้ยึดพฤติกรรมเว็บที่ใช้อยู่ทุกข้อ)

**เงิน**
1. แก้ยอดของ 2 รายการสินค้าสูญหายที่ได้เงินเกิน (LOS-1519, LOS-1484) ใน DB จริงไหม — ยังไม่แตะข้อมูล (เช็คใน `admin_packcargo` ก่อนเพราะตัวเลขนี้มาจาก bench)
2. ลูกค้ากดชำระจากหน้าบิลส่งออก ตัดเงิน**เต็มยอด** แต่ตารางแสดงยอด**หลังหักส่วนลด** — ควรตัดยอดหลังหักไหม
3. `save_vat` คำนวณใหม่โดยไม่รวมคูปอง และพอเปิด VAT ไม่รวมค่าส่งในไทย (ตัวอย่าง 279.27 → 301.00 → 322.07) — คงไว้ไหม
4. ปุ่มลบคูปองควรลบคูปองที่ยังไม่ใช้ได้จริงไหม · เช็คชื่อคูปองซ้ำตอนเพิ่มด้วยไหม

**สิทธิ์/พฤติกรรม**
5. แก้ล็อตได้เฉพาะ user 5 (ฝังในโค้ด) แต่ปลายทางบันทึกยังรับทุกคนที่มี `admin_lot` — บังคับฝั่งบันทึกด้วยไหม · user 5 ไม่เห็นราคาในล็อต (มี `admin_lot_view`) ตั้งใจไหม
6. ลูกค้า 769 จาก 1,012 คน เปิดฟอร์มเพิ่ม tracking ได้แต่บันทึกไม่ได้ (เพิ่มได้เฉพาะ user type 2)
7. แลกแต้มเปิดอยู่ (SR ปิด) · ลูกค้ายกเลิกโอน/ฝากจ่ายหยวนเองไม่ได้ (ไฟล์เดิมถูก comment) — ยืนยัน
8. ปุ่มเปลี่ยนโหมดขนส่งฝังไว้ให้ user 84/3 (เหมือน ship2u) · ยอดนับในหน้า order แอดมิน cache 10 นาทีแบบเดิม
9. อัปโหลดไฟล์ยังรับ zip/rar/7z/doc/xls ตาม pack — จำกัดเหลือรูป/PDF แบบ jl/SR ไหม · ช่องอัปโหลดแบบเก่าของ pack (PDF แสดงเป็นรูปเสีย, เลือกไฟล์เดิมซ้ำไม่ได้) — เปลี่ยนเป็นแบบที่แก้แล้วของ jl/SR ไหม
10. ไม่มี role ไหนถือ `admin_warehouse` → ระบบชั้นวางเปิดไม่ได้เลยในเว็บเดิม (ผมจึงตัดออก) — ยืนยันว่า pack ไม่ใช้
11. `download_file`: พนักงานทุกคน (แบบ pack) หรือเฉพาะ `admin_lot` (แบบ SR)
12. แดชบอร์ดลูกค้ามีข้อความฝัง (ลังไม้ 1,500) ไม่ตรงกับตั้งค่า (1,300) — ให้อ่านจากตั้งค่าไหม
13. `track_external.php` (หน้าค้นพัสดุไม่ต้องล็อกอิน ไม่มีที่ไหนลิงก์ มี SQL injection/XSS แสดงรหัสลูกค้า/ล็อต/รูปส่งของ) **ไม่ได้พอร์ต** — หลังสลับ URL นี้จะใช้ไม่ได้
14. อายุ session: เว็บเดิมหลุดเมื่อปิดเบราว์เซอร์ · Laravel 7 วันแบบ jl/SR (คงไว้)
15. อนุมัติผู้ใช้ประเภท 4 → 500 (เว็บเดิมก็เป็น)
15b. สิทธิ์เปลี่ยนผู้ส่ง (`admin_change_sender`) มีแค่ role 1 → เฉพาะแอดมินหลักเปลี่ยนผู้ส่งได้ ตั้งใจไหม · สคริปต์หน้าชำระเงินฝั่งลูกค้า error ตอนโหลด (`CalculateTotal()`) ทำให้ช่องคูปองไม่ reset — คงไว้เหมือนเดิม แก้ไหม

**DB (เสนอ ยังไม่ทำ)**
16. เพิ่ม index `tracking(lot_id, is_delete)` — DB จริงไม่มี · หน้าล็อตลูกค้าของเว็บเดิมใช้เวลา >6 นาทีบน bench · Laravel หน้าล็อตยัง 0.3–0.5 วิ เพราะเรื่องนี้
17. เพิ่ม index `bill_payment_detail_temp(random)` — `load_shipping_price` ยัง ~0.5 วิ

## 5. สิ่งที่ติดตั้ง/เตรียมบนเครื่องวันนี้

- **dev / ทดสอบ** (ไม่แตะเว็บจริง):
  - repo `/home/admin/laravel/pack` (พอร์ต 8800–8813, 8899) · worktree ของกลุ่มใน `/home/admin/laravel/pack-wt/*`
  - เว็บเดิม bench `/home/admin/bench/pack-old` ที่ :8114 (ปิดเมล/LINE/HTTP ออก) · `/root/pack-tools/pack-old-serve`
  - DB `admin_pack_bench` = clone เต็มของ `admin_packcargo` (user `pack_bench` เข้าได้เฉพาะ DB นี้) · test user 11937 (ลูกค้า role 2) / 11938 (แอดมิน)
  - เครื่องมือ `/root/pack-tools` (`compare.py`, `compare_ajax.py`, `smoke.py`, `rewrite.htaccess` จาก nginx ของ pack)
- **GitHub**: deploy key `/root/.ssh/pack_deploy_ed25519` (alias `github-pack`, เขียนได้)
- **Beacon**: เพิ่ม repo `pack` ใน `/etc/beacon/config.json` (backup `/root/beacon-config.json.bak-pack-repo-20261022`) แล้ว restart `beacon-api`
- **/new/ (ติดตั้งแล้ว)** — `/root/pack-new-subfolder` (ดู `README.md`):
  - user `site_pklv`, `/home/admin/laravel/pack-upload-root`, key `azure-pklv.key`/`mailgun-pklv.key` + `pack-live/.env` (เจ้าของรัน `owner-secrets.sh`)
  - pool `/etc/php/8.4/fpm/pool.d/pack-live.conf` (max_children 8) · `/etc/nginx/conf.d/00-pack-new-map.conf` · `snippets/pack-new.conf` (portal) + `snippets/pack-www-new.conf` (www)
  - include ใน `portal.pack-cargo.net.conf` และ `pack-cargo.net.conf` · backup ก่อนแก้: `LAST_NEW_BACKUP` · ถอดทั้งหมด: `rollback-new.sh`
  - autodeploy: `/etc/cron.d/pack-autodeploy` → `autodeploy.sh` (เทสใน `pack-ci`, เช็ค 24 หน้าใน `/var/lib/pack-autodeploy/pages.txt` รวมหน้า www) · log `/var/log/pack-autodeploy.log` · หยุด: `touch /var/lib/pack-autodeploy/PAUSE`
- **ไฟล์สลับเว็บ (ห้ามรันจนกว่าเจ้าของสั่ง)**: `portal.pack-cargo.net.laravel.conf`, `cron-pack-live.txt`, `switch.sh --yes` (backup → `.env` URL หลัก + mailgun → nginx → cron เมล → `pack-unban.sh` เป็น `curl -G` → autodeploy BASE/pages.txt → reload → เขียน `rollback-switch.sh` ให้อัตโนมัติ) · `switch.sh` ไม่ยอมรันถ้า `.env` ยังไม่มี `LEGACY_SHOP_UNBAN_TOKEN` (ตอนนี้มีแล้ว) · หยุด autodeploy ระหว่างสลับ
- **ไฟล์สลับ www (ห้ามรันจนกว่าเจ้าของสั่ง)**: `pack-cargo.net.laravel.conf` + `nginx-pack-www-fastcgi.conf`, `switch-www.sh --yes` (backup → เขียน `rollback-switch-www.sh` → nginx → ปิด pool PHP 7.4 ของ WordPress → pages.txt → เช็คหน้า) · ผ่าน `nginx -t` ในสำเนาทดลองแล้ว · สลับแยกจาก portal ได้

## 6. เหตุการณ์ระหว่างทำงาน

- agent กลุ่ม messaging ส่ง POST 2 ครั้งไปหน้าที่เขียนข้อมูลของเว็บเดิม bench (ทดสอบการปฏิเสธสิทธิ์) — โค้ดเดิมเช็คสิทธิ์แล้วหยุดก่อนเขียน ตรวจแล้วไม่มีข้อมูลถูกเขียน (bench เท่านั้น)
- `git stash` ใช้ร่วมกันทุก worktree → งานค้างของบางกลุ่มสลับกันชั่วคราว ตรวจแล้วไม่มีงานหาย · ห้ามใช้ stash แล้ว
- เว็บเดิม bench ค้าง 2 ครั้ง (query ล็อตลูกค้าไม่มี index) → ฆ่า query และเปิดใหม่ 4 worker
- **ค่าลับที่ฝังในโค้ดเดิมของ pack**: unban token และ token ของ API ที่ crawler ใช้ — agent กลุ่ม order เห็นค่าใน output ระหว่าง diff (ไม่ได้เขียนลงไฟล์/commit/รายงาน) · ตอนสลับต้องตั้งค่าใน `.env` (`owner-secrets.sh` ใส่ unban token ให้แล้ว)

## 7. www — pack-cargo.net (เพิ่มตามคำสั่งเจ้าของ "ย้าย www ด้วยนะ")

- **เดิม**: WordPress 5.2.24 (หมดอายุ) + ธีม Divi + ปลั๊กอิน 10 ตัว บน PHP 7.4 pool `pack-cargo.net` (user `site_packwp`), DB `admin_packwp2` · เนื้อหา 10 หน้า + 4 บทความ (2020) ไม่มีฟอร์มติดต่อ มีแค่ช่องค้นหา · log มีแต่บอทยิงหา webshell
- **ใหม่** (แบบเดียวกับ ship2you-cargo.com): Laravel ของ pack ผูกโดเมน `pack-cargo.net`/`www` (`routes/www.php` → `WwwController`) เสิร์ฟ snapshot ของ WordPress ตรงทุกไบต์ (`resources/www/`, สร้างด้วย `tools/www-snapshot.py`)
  - URL เดิมทุกหน้า + redirect แบบ WordPress (ไม่มี `/` ท้าย, `/index.php`, `/home/`, `?p=`, `?page_id=`, ตัวพิมพ์ใหญ่ก็เปิดได้)
  - ค้นหา `?s=` ทำแบบ WordPress 5.2 ทุกขั้น (แยกคำ, stopword ภาษาไทย, `-คำ` = ไม่เอา, ลำดับ relevance, หน้าละ 5) โดยให้ MySQL ทำ LIKE ด้วย collation เดิม (ไม่สนวรรณยุกต์ไทย) บนข้อมูลใน `site.json` — ไม่อ่านตารางใด
  - ฟีด `/feed/`, ฟีดความเห็น, หน้าหมวดหมู่ 2 หน้า, หน้าผู้เขียน, `robots.txt`, หน้า 404 ของธีมเดิม
  - รูป/CSS/JS ใน `wp-content`, `wp-includes` nginx เสิร์ฟจากโฟลเดอร์เดิม · **ไม่รัน PHP ของ WordPress อีก** (`.php` ทุกไฟล์ 404) · ตอนสลับปิด pool PHP 7.4 ของ WordPress
- **ต่างจากเดิม (ตั้งใจ)**: `/wp-admin/`, `/login` (เดิมพาไปหน้า login ของ WordPress) → 404 · หน้า archive ตามวันที่ (`/2020/` ฯลฯ) และ `/page/2/` ที่ไม่มีลิงก์ไหนชี้ → 404 · WordPress เดา redirect ให้ URL ผิด (เช่น `/new/` → `/news/`) → 404 · `?page_id=abc` (WordPress พาไปบทความล่าสุด) → 404
- **ผลต่อเจ้าของ**: แก้เนื้อหาผ่าน wp-admin ไม่ได้แล้ว — ถ้าจะแก้ ให้แก้ไฟล์ใน `resources/www/` (หรือเปิด WordPress กลับชั่วคราว แก้ แล้วรัน `tools/www-snapshot.py` ใหม่) · ไฟล์และ DB ของ WordPress ยังอยู่ครบ
- **คำถาม**: 18. ~~สลับ www เลยไหม~~ สลับแล้ว · 19. ~~ลบ WordPress~~ เก็บกวาดแล้ว (หัวข้อ 0)
