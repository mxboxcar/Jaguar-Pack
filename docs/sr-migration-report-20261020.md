# รายงานการย้ายระบบ portal.srshippingsystem.com (PHP 7.4 → Laravel 13)

วันที่ 2026-10-20 · repo `/home/admin/laravel/sr` → GitHub `mxboxcar/Jaguar-SRShipping` (main `1a5dca3`) · แม่แบบ: งาน jl (`/root/jl-migration-report-20261018.md`)

---

## 1. สรุปภาพรวม

- **พอร์ตครบทั้ง 13 กลุ่ม** โดย fork จาก app ของ jl แล้วปรับตามโค้ดเดิมของ SR (ต่างจาก jl ราว 40% ของไฟล์) ได้แก่ tracking, shop, order, payment, balance, money-move, points, lot, warehouse, users, messaging, reports และ settings
  - แต่ละกลุ่มทำใน branch `mod/<group>` แล้ว merge แบบ `--no-ff` เข้า main ไม่มี conflict
  - รายงานรายกลุ่มอยู่ในหัวข้อ "## SR (2026-10-20)" ที่ด้านบนของ `docs/modules/<group>.md`
- **ผลทดสอบ**
  - phpunit ผ่าน **323/323** ทั้งแบบไม่ใช้ DB และแบบใช้ DB (`JL_BENCH_DB_TESTS=1` บน `admin_sr_bench`)
  - มีทั้งหมด **566 routes** และ secret-scan ไม่พบ secret หลุดเข้า git (ตรวจเทียบค่าลับ 24 ตัว รวม SendGrid key ที่ฝังในโค้ดเดิม)
- **Smoke test บน :8300**
  - GET 235 routes ด้วยสามสถานะ: ไม่ล็อกอิน, ลูกค้า (11937) และแอดมิน (11938) **ไม่มี 500 เลย**
  - ไม่ล็อกอินถูก redirect ไป login ทุก route ยกเว้นหน้าสาธารณะ 12 หน้า
  - ลูกค้าเปิดหน้าแอดมิน 105 หน้า ได้หน้าแจ้งไม่มีสิทธิ์ทุกหน้า
  - POST ทั้ง 361 routes ที่ไม่มี Origin/Referer ได้ 403 ทุกตัว
- **สิทธิ์** (`docs/role-matrix.md`): ตรรกะเดิม `getPermission()` กับ `Permissions` ใหม่ตรงกันทุกช่องของทุก role
  - SR ไม่มีสิทธิ์กำพร้า map fallback จึงว่าง ถ้าใช้ map ของ jl จะให้สิทธิ์เกินที่ SR ตั้งไว้
- **การตัดสินใจของ jl ที่ใช้กับ SR ทุกข้อ**: `*_view` อ่านอย่างเดียว, แก้บั๊กเงินทั้งหมด, คง save_lock, เปลี่ยนประเภทส่งแล้วยอดติดลบได้, nobilling แบ่งหน้า, 404/405 พากลับหน้าแรก, ไม่มี `target="_blank"`, favicon (ใช้ไอคอนของ SR เอง), กันกดซ้ำ, row lock, Azure ผ่าน REST และ Mailgun
- **สิ่งที่ตัดออกตามที่เจ้าของสั่ง**: mall/mall_category (SR ไม่มีตาราง), LINE Notify, gateway และเว็บ www (เป็นแค่ index.html นิ่งๆ ไม่แตะ)
- **ยังไม่ได้ขึ้น /new/ และยังไม่ได้สลับเว็บจริง** ไฟล์สำหรับขึ้นระบบเตรียมไว้ที่ `/root/sr-new-subfolder/` แต่ยังไม่ได้ติดตั้ง

## 2. ผลวัดความเร็ว (mean ms, เดิม → ใหม่, bench :8111 → Laravel)

| กลุ่ม | หน้า / endpoint | เดิม | ใหม่ |
|---|---|---:|---:|
| tracking | `/tracking` | 347–367 | 37–43 |
| tracking | `_admin/_tracking/grid` | 128–167 | 54–56 |
| tracking | `/admin/check` (SR) | 336 | 37 |
| shop | `taobao` | 462 | 42 |
| shop | `cart/checkout/{ids}` | 475 | 37 |
| order | `/order` | 354–404 | 36–65 |
| order | `_admin/_order/load_detail` | 303 | 55 |
| order | printorder print (SR) | 216 | 53 |
| payment | `/payment` | 368 | 43 |
| payment | `/admin/payment/32` (44 พัสดุ) | 733 | 69 |
| balance | `/balance` | 301–370 | 33–44 |
| balance | `_admin/_balance/grid` | 89–105 | 42–66 |
| money-move | `/admin/refill` | 537 | 37 |
| money-move | `/admin/transfer/148` | 490 | 39 |
| points | `/point` | 357–361 | 39 |
| lot | `/admin/lot` | 414 | 39 |
| lot | `/admin/lot/219` | 491 | 41 |
| warehouse | `/admin/warehouse/{id}` | 374–550 | 39–48 |
| users | `/admin/user/24` | 394 / 379 | 47 / 48 |
| messaging | `/admin/send` | 418 | 52 |
| reports | dashboard | 382 | 44 |
| reports | `admin/export/184` | 564 | 49 |
| reports | ใบเสร็จ `admin/export/receipt/184` (SR) | 402 | 36 |
| settings | `/admin/setting` | 377–384 | 38–40 |

- หน้าเต็มเร็วขึ้นราว 8–10 เท่า
- ajax เล็กๆ ที่เดิมใช้ไม่ถึง 25 ms ช้าลง 2–20 ms เพราะ Laravel มีต้นทุนคงที่ต่อ request
- DB ของ SR เล็ก (ราว 5 MB, ผู้ใช้ 24 คน) จึงโคลนทั้งก้อนมาเป็น bench ตัวเลขข้างบนจึงวัดกับข้อมูลจริงทั้งหมด

## 3. ที่ต่างจาก jl และจากเว็บเดิมของ SR

### 3.1 ฟีเจอร์ที่มีเฉพาะ SR (พอร์ตแล้ว)
- **tracking**
  - หน้า `admin/check` ใช้ตาราง `tracking_check` เป็นคิวตรวจพัสดุ
  - รูปพัสดุจากตาราง `tracking_image`
  - ช่องถ่ายรูป (`is_takeapic`) และรหัสย่อย (`subuser`)
  - หมายเหตุแอดมินของพัสดุหาย
- **order**
  - หน้าพิมพ์ใบสั่งซื้อ (`admin/printorder`)
  - เปลี่ยนลูกค้า, เรทใหม่, เปลี่ยนประเภทส่ง, ยกเลิกพร้อมคืนเงิน และ toggle ถ่ายรูป
  - การจ่ายเงินต้องใส่ PIN
- **payment**: ไม่มีระบบ VAT แบบ jl, `calPoint()` (ให้แต้มเมื่ออนุมัติบิล หักแต้มเมื่อยกเลิก), alipay หักจากยอดหยวนก่อน และหน้ารายการใบเสร็จ `admin/receipt`
- **reports**: ใบเสร็จและใบกำกับภาษี (receipt, receipt_1/2, calvat, save_receipt ที่ใช้เลขรันจากตาราง `running`, print_receipt), รายงานภาษีขาย (type 97, printtax), พิมพ์บิลพร้อมราคา (printprice) และ dashboard ของ SR
- **money-move**: การโอนใช้ยอดหยวนก่อน และต้องใส่ PIN, refill บันทึกผู้อนุมัติ
- **balance / points**
  - ถอนเงินได้สัปดาห์ละครั้ง และต้องใส่ PIN
  - แลกแต้มปิดอยู่ ("ยังไม่เปิดบริการให้แลก")
  - cashback ต้องใส่ PIN
- **warehouse**: ลูกค้าราว 30 คนมีชั้นวางที่กำหนดตายตัวไว้ในโค้ด และกฎนับรหัสต่อชั้นนับคู่ user + user_agent
- **messaging**: badge "รอตรวจ" และ "รับเอง", หน้า `admin/sendself`, แชทแบบของ SR
- **shop**: หน้าหาสินค้า (`findproduct` ไม่มี role ไหนมีสิทธิ์ทั้งเดิมและใหม่), ถ่ายรูปในตะกร้า, Taobao ต้องใส่ PIN, `_admin/_mallstock` ใน SR เป็นสำเนาของ `_admin/_balance`
- **settings**: `/resource`, อัปโหลดรูปของ CKEditor (`system_ckupload` ส่งไปเก็บผ่าน BlobStore)
- **users**: รหัสลูกค้า `SU####`, ช่อง LINE/WeChat, โปรไฟล์ต้องใส่ PIN และอีเมลทั้ง 6 แบบเป็นข้อความของ SR

### 3.2 ฐานร่วม
- `config/legacy.php` ใช้ค่าคงที่และชื่อ cookie ของ SR (`srshippingsystem-*`) และ `config('legacy.mail')` เป็นผู้ส่ง `SRShippingSystem <no-reply@srshippingsystem.com>` พร้อมแม่แบบที่ทุก mailer ใช้
- layout, เมนู และภาษาสร้างใหม่จากโค้ด SR แล้ว merge กับการแก้มือที่ทำไว้ใน jl
- model สร้างจาก schema ของ SR
- **Pricing** ใช้แบบของ SR: GetCalPrice/CalPrice ปรับประเภทสินค้าตามล็อตเสมอ (มีแบบอ่านอย่างเดียวให้ผู้ใช้สิทธิ์ view), getPrice ใช้ cubicformat และค่าเปลี่ยนผู้ส่งกำหนดต่อรหัสลูกค้าได้
- ลำดับความเหมือนเดิมของการเทียบ: ไฟล์เดิมที่เหมือนกับ jl เทียบผลซ้ำแล้วทุกไฟล์ (`docs/sr-diff.md` มีรายการไฟล์ต่อกลุ่มและ schema ที่ต่าง)

### 3.3 ต่างจากเว็บเดิมโดยตั้งใจ (เหมือน jl)
- escape ค่าก่อนแสดง, CSRF ตรวจ Origin/Referer, `%`/`_` ไม่เป็น wildcard ในการค้นหา, ajax ที่ไม่ล็อกอินได้ 302 ไป login และหน้าไม่มีสิทธิ์แสดง ErrorBox มาตรฐาน
- **ตามกลุ่ม** (รายละเอียดอยู่ใน docs/modules):
  - การรวมพัสดุเขียนรหัสย่อย/ถ่ายรูปลงคอลัมน์ที่ถูกต้อง
  - รูปของ tracking ใหม่ผูกกับ id ใหม่
  - ชั้นวางได้แถวเดียวต่อพัสดุ (SR เดิมเขียน 2 แถว)
  - goodsconfirm คงการตรวจเจ้าของไว้ (SR เดิมตัดออก)
  - `_setting/save` อัปเดตเฉพาะ key ที่ส่งมา
  - เลขใบเสร็จออกซ้ำไม่ได้แล้ว
  - endpoint เงินใหม่ของ SR ทุกตัวมี transaction, row lock และกันกดซ้ำ

## 4. เรื่องที่รอเจ้าของตัดสินใจ

ตอนนี้ทุกข้อใช้ค่าตามเว็บเดิม หรือแบบที่ปลอดภัยกว่า ซึ่งระบุไว้ในแต่ละข้อ

**การเงิน**
1. ลูกค้ายกเลิกบิลที่ยังไม่จ่ายแล้วโดนหักแต้มที่ไม่เคยได้ (แต้มติดลบ บน bench มี 5 แถว) ตอนนี้ทำตามเดิม ควรหักเฉพาะแต้มที่ได้ไปจริงไหม
2. นับแต้มซ้ำเฉพาะแถวของบิลนั้น (เดิมรวมแถวถอนแต้มด้วย) และ alipay นับยอดหยวนที่ติดลบเป็น 0 ตกลงไหม
3. `delete_withcashback` คืนค่าธรรมเนียม 50 บาทได้เฉพาะเมื่อมีแถวค่าธรรมเนียมจริง และควรมีปุ่มเรียกใช้ไหม
4. เปลี่ยนลูกค้าและเรทใหม่ได้เฉพาะ order ที่ยังไม่จ่าย ปุ่มเปลี่ยนประเภทส่งจำกัดไว้ที่ user 84 และ 3 ควรเปลี่ยนเป็นตรวจสิทธิ์ไหม
5. การโอน: แอดมินบันทึกได้เฉพาะสถานะ 1 และตอนยกเลิกไม่เขียนแถว 0.00 ตกลงไหม
6. การถอนเงิน: รายการที่ยกเลิกควรนับเป็นโควตาสัปดาห์ไหม และสิทธิ์ถอนเงินที่แยกเป็น `admin_withdrawcash` กับ `admin_withdrawpoint` ควรรวมกันไหม
7. รายงานภาษีขายรวมยอดภาษีผิดเมื่อภาษีตั้งแต่ 1,000 บาทขึ้นไป (มีจุลภาคในตัวเลข) ควรแก้ไหม ผมแนะนำให้แก้
8. ใบเสร็จฝังชื่อและเลขผู้เสียภาษีของ "บริษัท โกโก-คาร์โก้ จำกัด" ไว้ในโค้ด ข้อมูลยังถูกต้องไหม ควรย้ายไปเป็น setting ไหม และควรเติมยอดบิลให้อัตโนมัติไหม

**สิทธิ์และการใช้งาน**

9. `findproduct` ไม่มีใครใช้ได้ทั้งเดิมและใหม่ จะเปิดใช้หรือตัดทิ้ง
10. `_admin/_mallstock` เป็นทางเข้าที่สองของ `_admin/_balance` แนะนำให้ปิด
11. badge "รอตรวจ" ใช้สิทธิ์ `admin_export` แต่หน้า admin/check ใช้ `admin_tracking`
12. การลบ notice ต้องมีสองสิทธิ์ ทำให้ role 10–12 ลบไม่ได้
13. `admin_user_view` ควรได้ฟอร์มอ่านอย่างเดียวแบบ jl ไหม
14. หน้าพิมพ์ใบสั่งซื้อใช้สิทธิ์ 3 ตัว ควรรวมเป็นตัวเดียวไหม
15. `system_ckupload` ให้ผู้ใช้ทุกคนที่ล็อกอินอัปโหลดรูปได้ ควรจำกัดเฉพาะ `admin_notice` ไหม

**ข้อมูลและเรื่องอื่นๆ**

16. รูปที่ค้างด้วย tracking_id 0 จำนวน 1,987 รูปจะเก็บไว้ไหม, admin/check ซ้ำเมื่อแก้ tracking หลายครั้ง, ฟอร์ม lot ไม่บันทึกช่องถ่ายรูป, `save_file_lot` รับชื่อไฟล์อะไรก็ได้
17. บัตรประชาชนตอนสมัคร: เก็บในโฟลเดอร์ `upload/` ของเว็บเดิม หรือย้ายไป Azure
18. ตอน approve user type 4 โค้ดเดิมล้ม ควรส่งเมลแบบ type 3 แทนไหม
19. ต้องตั้ง token ใหม่ของ `_api` (unban, order) ใน `.env` และ URL ใน cron ของ unban
20. ต้องตรวจว่าบน production มีผู้ใช้รหัส `nobody` (ใช้ใน goodsconfirm) และค่า `bypass_withdraw`

## 5. ช่องโหว่ของโค้ดเดิม SR (production ยังเปิดอยู่จนกว่าจะสลับ)

**แก้บน production แล้ววันนี้ (เจ้าของอนุมัติ)**
- `password.php` ของ **SR และ ship2you** เปิดได้โดยไม่ต้องล็อกอิน และพิมพ์รหัสผ่านที่ถอดรหัสแล้วของผู้ใช้ทุกคน
  - แก้: แทนเนื้อไฟล์ด้วยคำตอบ 404 (backup และ rollback อยู่ที่ `/root/webroot-backups/password-php-20261020`)
  - ใน log ที่ยังเก็บอยู่ไม่พบการเรียก แต่ log เก็บย้อนหลังได้สั้น **ควรให้ผู้ใช้สองเว็บนี้เปลี่ยนรหัสผ่าน โดยเฉพาะบัญชีแอดมิน**

**ยังเปิดอยู่ (แก้ใน Laravel แล้ว)**
- `_admin/_refill/grid`/`index` ไม่ตรวจสิทธิ์ ลูกค้าทุกคนดูรายการเติมเงินของทุกคนได้ (ยืนยันบน bench แล้ว) **แนะนำให้ hotfix ก่อนสลับ**
- `_admin/_order/delete_withcashback` คืน 50 บาทเข้าบัญชีแอดมินที่กดทุกครั้ง (ต้องมีสิทธิ์ `admin_order`) และ `save_changeuser` ย้าย order ที่จ่ายแล้วไปให้ลูกค้าอื่นได้
- `_admin/_withdrawcash/cancel` คืนเงินซ้ำได้ และ `save_approved` อนุมัติรายการที่ยกเลิกแล้วได้ (เฉพาะ role 1)
- `_register/save` คัดลอกไฟล์จาก path ใดก็ได้ (เช่น `../../system_config.php`) ลงโฟลเดอร์ `upload/` สาธารณะ
- `save_receipt` และ `printtax` เปิดให้ทำ SQL injection และเลขใบเสร็จออกซ้ำได้
- `_transfer/save` ไม่ lock และเขียน PIN ที่พิมพ์ผิดลง log แบบอ่านออกได้
- ช่องโหว่แบบเดียวกับ jl (SQLi, XSS, CSRF, path traversal ฯลฯ) ตามหัวข้อ 5 ของรายงาน jl ยกเว้นส่วนที่ hotfix ไปแล้วเมื่อ 2026-10-18

**ความลับที่ต้อง rotate** (นอกเหนือจากรายการของ jl)
- **SendGrid API key** ที่ฝังใน `system_config.php` ของ SR (มีการพิมพ์ออกมาใน tool output ของ agent หนึ่งครั้ง)
- token ของ `_api` ของ SR
- salt ของ SR **ใช้ค่าเดียวกับ jl**

**เรื่องเล็ก**: hotfix email-guard เมื่อ 2026-10-18 ทำให้บรรทัด `<?php ... ?>` ติดไปในอีเมลของทุก portal (โปรแกรมอ่านอีเมลไม่แสดง เพราะถือเป็น comment) Laravel ไม่มีปัญหานี้

## 6. เหตุการณ์ระหว่างทำงาน

- **ไม่มีข้อมูล production เสียหาย** ทุกการเขียนอยู่บน `admin_sr_bench` (user `sr_bench` เข้าได้เฉพาะ DB นี้) และทุกแถว zz_ ลบตาม id แล้ว
- **lot**: ตอนเทียบหน้า `load_tracking` โค้ดคำนวณราคาของ SR เขียน `product_type_id` ลง 4 แถวบน bench ผมเทียบกับ snapshot แล้ว ค่าเท่าเดิมทุกแถว (ต่อมาเพิ่มแบบอ่านอย่างเดียวให้ผู้ใช้ view)
- **users**: ทดสอบ lost-password แล้ว แถว `ips` 88 ถูกลบตามที่โค้ดตั้งใจ (เป็นแถวบันทึก login พลาดจากการทดสอบของผมเอง)
- **ส่งคำขอที่มีอักขระพิเศษไปที่โค้ดเดิมบน bench 2 ครั้ง** (order, points): เป็นคำขออ่านอย่างเดียว ไม่มีข้อมูลเปลี่ยน
- **agent หยุดกลางทางเมื่อ Claude Code ปิด-เปิดใหม่** (payment, order) จึงสั่งให้ทำต่อจาก branch เดิม ไม่มีงานหาย
- ข้อมูลส่วนตัวที่ปรากฏใน tool output: อีเมลจริงของลูกค้า 3 รายการ (จาก bench) และ SendGrid key 1 ตัว (ไม่ได้อยู่ใน git)

## 7. ขั้นต่อไป (ทุกข้อต้องได้รับอนุมัติก่อน เพราะแตะ production)

1. hotfix `_admin/_refill/grid`/`index` บนเว็บเดิม (แนะนำให้ทำก่อน)
2. rotate SendGrid key, token ของ `_api` และ salt/รหัส DB ตามแผนของ jl
3. ขึ้น `/new/` ตาม `/root/sr-new-subfolder/README.md` ต้องตัดสินใจ:
   - (ก) จะสร้าง user ของ pool `site_srlv` ไหม (แนะนำ) หรือใช้ `site_jllv` ร่วมกับ jl
   - (ข) จะใช้ DB user เดิม `admin_sr` หรือสร้างใหม่
   - (ค) ต้องทำสำเนา key ของ Azure/Mailgun ให้ user ของ pool
   - (ง) ต้องสร้างโฟลเดอร์ upload root
4. ทดสอบบน `/new/` ต่อ DB จริงด้วย gate cookie แล้วสลับทั้งเว็บ (สลับ cron mail worker พร้อมกัน, มี rollback คำสั่งเดียว) และเฝ้าดู 24 ชั่วโมง
