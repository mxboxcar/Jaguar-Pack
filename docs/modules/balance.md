# กลุ่ม balance — รายงานการพอร์ต

branch `mod/balance` · worktree `/home/admin/laravel/jl-wt/balance` · port 8205

## Route ที่พอร์ต (URL เดิมทุกตัว)

| เดิม | ใหม่ | middleware |
|---|---|---|
| `^balance$` → `_balance/index.php` | `GET /balance` | `legacy.perm:balance` |
| `_balance/grid.php` | `POST /_balance/grid` | `legacy.auth` + `legacy.ajax` (สิทธิ์ `balance` ตรวจใน controller: เดิม exit เงียบ) |
| `_balance/load_withdraw.php` | `POST /_balance/load_withdraw` | เหมือนข้างบน |
| `_balance/save_withdraw.php` | `POST /_balance/save_withdraw` | เหมือนข้างบน |
| `^balance_cn$` → `_balance_cn/index.php` | `GET /balance_cn` | `legacy.perm:balance_cn` |
| `_balance_cn/grid.php`, `load_withdraw.php`, `save_withdraw.php` | `POST /_balance_cn/{grid,load_withdraw,save_withdraw}` | `legacy.auth` + `legacy.ajax` (สิทธิ์ `balance_cn` ใน controller) |
| `^admin/balance$` → `_admin/_balance/index.php` | `GET /admin/balance` | `legacy.perm:admin_balance` |
| `_admin/_balance/grid.php` | `POST /_admin/_balance/grid` | `legacy.auth` + `legacy.ajax` (สิทธิ์ `admin_balance` ใน controller) |
| `_admin/_balance/load_amount.php`, `save_amount.php` | `POST /_admin/_balance/{load_amount,save_amount}` | `legacy.ajax` + `legacy.perm:admin_balance` |
| `^admin/balance_cn$` → `_admin/_balance_cn/index.php` | `GET /admin/balance_cn` | `legacy.perm:admin_balance_cn` |
| `_admin/_balance_cn/grid.php` | `POST /_admin/_balance_cn/grid` | `legacy.auth` + `legacy.ajax` (สิทธิ์ใน controller) |
| `_admin/_balance_cn/load_amount.php`, `save_amount.php` | `POST /_admin/_balance_cn/{load_amount,save_amount}` | `legacy.ajax` + `legacy.perm:admin_balance_cn` (เพิ่มรายการต้องมี `admin_balance_cn_add` เหมือนเดิม) |
| `^admin/balance_company$` → `_admin/_balance_company/index.php` | `GET /admin/balance_company` | `legacy.perm:admin_balance_company` |
| `_admin/_balance_company/grid.php` | `POST /_admin/_balance_company/grid` | `legacy.auth` + `legacy.ajax` (สิทธิ์ใน controller) |
| `_admin/_balance_company/load_amount.php`, `save_amount.php` | `POST /_admin/_balance_company/{load_amount,save_amount}` | `legacy.ajax` + `legacy.perm:admin_balance_company` (+ `admin_balance_company_delete` สำหรับยอดบวก/แก้ไข เหมือนเดิม) |

ไม่ได้ทำ route: `/_balance/index`, `/_balance_cn/index`, `/_admin/_balance*/index` (url-map ตารางที่ 2) — เป็นชิ้นส่วนที่ `index.php` include
ถ้าเรียกตรงในเว็บเดิมจะ fatal (ไม่มี `system_config.php` / `$resource`) จึงไม่มีพฤติกรรมให้เลียนแบบ หน้าเต็มใช้ RewriteRule ด้านบนแทน

## ไฟล์

- `routes/modules/balance.php`
- `app/Http/Controllers/Balance/{BalanceController,BalanceCnController}.php`, `Admin/{AdminBalanceController,AdminBalanceCnController,AdminBalanceCompanyController}.php`
- `app/Domain/Balance/{Ledger,Withdraw,Php74}.php`
- `resources/views/balance/{customer,cn,admin,admin_cn,company,partials}/*`
- `tests/Feature/Balance/BalanceLogicTest.php` + `tests/fixtures/balance-php74-cases.json` (สร้างจากนิพจน์เดิมด้วย php7.4)

## ผลเทียบกับเว็บเดิม (:8101 vs :8205, cookie เดียวกัน, ตัดช่องว่าง + แทน href)

ตรงกันทุกตัวอักษร (หลังตัดช่องว่าง) — ลูกค้า (zz_fdn_cust, user 137) และแอดมิน (zz_fdn_admin):
หน้า `/balance`, `/balance_cn`, `/admin/balance`, `/admin/balance_cn`, `/admin/balance_company`;
grid ทั้ง 5 (หน้า 1–3, ค้นหาด้วยรหัส `BLA-…`/`BLA-CN-…`/`BLA-COMP-…`, รหัสลูกค้า, prefix_ref_id, กรองประเภท, ไม่พบข้อมูล, page ว่าง);
`load_withdraw` ทั้งสอง; `load_amount` ทั้งสาม (JSON byte ต่อ byte รวม key ตัวเลขแบบ `mysqli_fetch_array`, `null` เมื่อไม่พบ);
`save_withdraw` ทางที่ไม่บันทึก (0, เกินยอด, `abc`, ว่าง, `-0.00`, ชื่อในบัญชีดำ); `save_amount` ไม่พบผู้ใช้; การไม่มีสิทธิ์ (grid ว่าง / ErrorBox)

ทางที่บันทึกลง DB ทดสอบเฉพาะฝั่งใหม่ แล้วเทียบแถวกับ SQL เดิม (ค่า/คอลัมน์/การแปลง `''`→0 แบบ non-strict ตรงกัน):
**ไม่ได้ยิง endpoint บันทึกของเว็บเดิม** เพราะ `mysqlquerys()` → `goLog()` เขียนไฟล์ log ไปที่ webroot production (`/home/admin/web/portal.jlcargoth.com/public_html/logs/`)

## bench (`bin/bench`, -n 30 -c 1, artisan optimize)

| หน้า | เดิม mean ms | ใหม่ mean ms |
|---|---|---|
| `/balance` (หน้าเต็ม) | 283–396 | 39–44 |
| `/_balance/grid` (ลูกค้า) | 56–66 | 30 |
| `/_admin/_balance/grid` | 59–70 | 32–41 |
| `/_admin/_balance_cn/grid` | 87–96 | 52–53 |

## ต่างจากเดิมโดยตั้งใจ

- ค่าจาก DB/ผู้ใช้ escape ทั้งหมด (`detail`, ชื่อประเภท, รหัสลูกค้า, คำค้นใน `value=`) — เดิม echo ดิบ (stored/reflected XSS); `'` ในคำค้นแสดงเป็น `&#039;`
- `$_POST['page']` ที่เดิมฝังลง JS (`fn_public = "...'<page>'..."`) ผ่าน `Alerts::js()` + `e()`
- LIKE ของ grid ลูกค้า/แอดมินหยวน/บริษัท escape `%` `_` (CONVENTIONS ข้อ 4) — เดิม `%`/`_` ที่ผู้ใช้พิมพ์เป็น wildcard
- N+1: ยอดคงเหลือ (SUM ต่อแถว), รูปสลิปถอน (`balance_withdraw` ต่อแถว), ผู้ทำรายการ (`user` ต่อแถว) → subquery/LEFT JOIN ในคำสั่งเดียว; ค้นหาแอดมินบาทยังเป็น 2 ขั้น (UNION หา id แล้ว `IN`) เหมือนเดิมเพื่อใช้ index
- `save_withdraw` (บาท/หยวน): ห่อ `DB::transaction()` + `SELECT … FOR UPDATE` แถว user แล้วตรวจยอดใน transaction — เดิมตรวจยอดกับ INSERT แยกกัน กดซ้อนกันถอนเกินยอดได้
- `save_withdraw` บาท: ไม่รัน query นับรายการถอนในสัปดาห์ + `bypass_withdraw` (เดิมคำนวณแล้วไม่ได้ใช้ — โค้ดที่ใช้ถูก comment)
- `save_withdraw` หยวน: `branch_no/branch_bank/branch_name` = `''` เสมอ (เดิมเป็นตัวแปรที่ไม่ได้กำหนด แต่ตั้งได้ผ่าน `?bank_no=` เพราะ `extract($_GET)`)
- เทียบตัวเลขแบบ PHP 7.4 ชัดแจ้ง (`Php74::cmp`) — ใน 8.x `"abc" > 0` เป็นจริง (เดิมเท็จ) ซึ่งจะทำให้ `save_amount` บริษัทตรวจสิทธิ์ผิด
- หน้าไม่มีสิทธิ์: ใช้ `legacy.perm` (layout + ErrorBox → dashboard) — เดิมแสดงหัวข้อ/ปุ่มของหน้าก่อนเด้ง ErrorBox
- ajax ที่ยังไม่ล็อกอิน: redirect `/login` (เดิม grid ตอบว่าง, load/save_amount ตอบ ErrorBox)
- POST ต้องผ่าน `LegacyCsrf` (Origin/Referer เดียวกันหรือ XHR) — เดิมไม่มีการกัน CSRF เลย
- `page` ที่ไม่ใช่จำนวนเต็ม (`abc`, `1.5`): แถวที่แสดงเหมือนเดิม แต่ลิงก์หน้าใน `Paging::getpaging()` (ฐานร่วม, cast เป็น int) ต่างจากเดิม — เกิดได้เฉพาะยิง request เอง

## ช่องโหว่เดิมที่เจอ (แก้แล้วในฝั่งใหม่)

1. SQL injection ทุกไฟล์: `txtsearch` (ทั้งคำค้นและประเภท), `id`, `code`, `type`, `detail`, `amount`, `wantcash`, `bank_no`/`branch_*` ต่อ string ตรง — กันอยู่ได้เพียงเพราะ system_config.php แปลง `'`→`''` และลบ `\` ทุก POST (พังทันทีถ้าโค้ดใดอ่าน `php://input`/ค่าที่ไม่ผ่านการแปลง หรือ sql_mode เปลี่ยนเป็น NO_BACKSLASH_ESCAPES ฯลฯ) → ใหม่ bind ทุกค่า
2. Stored XSS: `balance.detail` (แอดมินพิมพ์ได้อิสระใน save_amount), `balance_type.name`, `user.code`; reflected XSS: `txtsearch`, `page` ใน `<script>`
3. CSRF: save_withdraw / save_amount ไม่มีการกัน → หน้าเว็บอื่นสั่งถอนเงิน/แก้ยอดเงินแทนผู้ใช้ที่ล็อกอินได้
4. Race condition ถอนเงินเกินยอด (ไม่มี transaction/lock)
5. แอดมิน `save_amount` แก้ `balance`/`balance_cn` ได้ทุกแถวด้วย id ใดๆ และไม่บันทึก `mod_by` (คงพฤติกรรมไว้ — เป็นสิทธิ์แอดมินโดยออกแบบ แต่ควรพิจารณาเพิ่ม log/mod_by)

## ให้เจ้าของตัดสินใจ

- `admin_balance_company`, `admin_balance_company_delete` (และ `admin_balance_company_add` ใน role_detail) ไม่มี role ใดได้รับใน DB → หน้าบัญชีบริษัทเข้าไม่ได้แม้แอดมิน (เหมือนเดิม) — ทดสอบโดยเพิ่มสิทธิ์ให้ role 1 ชั่วคราวแล้วลบออก
- ปุ่ม "เพิ่มรายการ" ของบัญชีบริษัทใช้ไม่ได้ในเว็บเดิม (`load_amount(0)` ได้ `null` แล้ว JS อ่าน `data.id` → error) — คงไว้เหมือนเดิม
- ปุ่ม Delete/DeleteAll ใน JS ของ grid ทั้งหมดเรียก `<grid>/../delete` ซึ่งไม่มีไฟล์จริง (โค้ดตาย) — คงไว้
- typeahead รหัสลูกค้าในแอดมินเรียก `/_admin/_lot/load_autocomplete_user` (กลุ่ม lot)

## ฐานร่วมที่แก้

- `foundation: keep raw POST values (drop TrimStrings / ConvertEmptyStringsToNull)` — cherry-pick commit เดียวกับ branch `mod/tracking` (diff เหมือนกันทุกบรรทัด merge ได้ไม่ชน) จำเป็นเพราะคำค้น/`detail`/`amount` เดิมไม่ถูก trim และ `id=''` ต้องเป็น `''` ไม่ใช่ `null` (save_amount แยกเพิ่ม/แก้ด้วย `id == ""`)
- ข้อเสนอ: `App\Domain\Balance\Php74` ซ้ำกับ `App\Domain\Tracking\Php74` ของกลุ่ม tracking — ควรย้ายเป็น helper ฐานร่วมตอน merge
