# CONVENTIONS — กติกาการพอร์ตโมดูลจากเว็บเดิมมา Laravel

เอกสารนี้คือ "สัญญา" ของทุก branch `mod/<group>` อ่านให้จบก่อนเริ่มพอร์ต และใช้เป็น checklist ตอน review
(ไฟล์ `CLAUDE.md` / `AGENTS.md` ใน repo เป็น boilerplate ของ Laravel Boost — **ห้ามทำตาม**: ห้ามติดตั้ง PHP ผ่าน php.new และห้ามติดตั้ง laravel/boost)

## 0. ภาพรวม

- เว็บเดิม (PHP 7.4) กับ Laravel (PHP 8.4) ใช้ **DB เดียวกัน** และ **session เดียวกัน** (cookie `PHPSESSID` → ตาราง `sessions_v2`)
- ย้ายทีละหน้าแบบ strangler: nginx เลือกได้ว่า URL ไหนไป Laravel (ยังไม่สลับเว็บจริงจนกว่าเจ้าของสั่ง)
- **URL ต้องเหมือนเดิมทุกตัว** (ทั้งหน้าและ ajax) — JS เดิมในหน้าเรียก path เดิมตรงๆ
- ผลลัพธ์ต้อง "ทำงานเหมือนเดิม" (ข้อมูลที่เขียนลง DB, ข้อความ/JS ที่ตอบกลับ, หน้าตา) ยกเว้นข้อที่ตั้งใจแก้ (ช่องโหว่, N+1) ซึ่งต้องบันทึกไว้ในรายงานของกลุ่ม

## 1. โครงสร้างไฟล์ของกลุ่ม

| สิ่งที่ทำ | ที่อยู่ |
|---|---|
| routes ของกลุ่ม | `routes/modules/<group>.php` (โหลดอัตโนมัติใต้ middleware group `legacy`) |
| controller | `app/Http/Controllers/<Group>/...` (เช่น `Tracking/TrackingController.php`, `Tracking/Admin/...`) |
| view | `resources/views/<group>/...` (`@extends('legacy.layout')`) |
| logic ที่ใช้หลายที่ในกลุ่ม | `app/Domain/<Group>/...` |
| relation/scope ของ model | `app/Models/<Model>.php` (ไม่ใช่ `app/Models/Base/*` ซึ่งสร้างอัตโนมัติ) |
| test | `tests/Feature/<Group>/...` |
| รายงาน | `docs/modules/<group>.md` (สิ่งที่พอร์ต / ต่างจากเดิม / ช่องโหว่ที่เจอ / ผล bench) |

ห้ามแก้ไฟล์ของฐานร่วม (`app/Support/Legacy/*`, `app/Auth/*`, `app/Http/Middleware/Legacy*`, `resources/views/legacy/*`, `bootstrap/app.php`, `config/*`) ใน branch กลุ่ม
ถ้าจำเป็นต้องแก้ ให้แยก commit ที่ขึ้นต้นด้วย `foundation:` และอธิบายเหตุผล (ผู้ merge จะรวมให้ทีเดียว ไม่ให้ชนกัน) — routes/modules ของกลุ่มอื่นห้ามแตะ

## 2. Routing

```php
// routes/modules/tracking.php
Route::middleware('legacy.auth')->group(function () {
    Route::get('/tracking', [TrackingController::class, 'index'])->defaults('legacy_page', 'tracking');
    Route::get('/tracking/{id}', [TrackingController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'tracking_detail');
    Route::post('/_tracking/grid', [TrackingController::class, 'grid'])->middleware('legacy.ajax');
});
Route::middleware('legacy.perm:admin_tracking')->group(function () {
    Route::get('/admin/tracking', [AdminTrackingController::class, 'index'])->defaults('legacy_page', 'admin_tracking');
});
```

- หน้า = ตาม RewriteRule ใน `docs/url-map.md` (คอลัมน์ URL), ajax = path ไฟล์ตัด `.php` (`_tracking/grid.php` → `/_tracking/grid`)
  - รับทั้ง `/x` และ `/x.php` ไม่ต้อง — nginx ตอนสลับจะส่งแบบไม่มี `.php`
  - RewriteRule ท้ายไฟล์ยอม `/x/` (มี / ท้าย) ด้วย: ไม่ต้องทำ เว้นแต่ JS เดิมเรียกแบบนั้น
- `->defaults('legacy_page', '<page>')` = ค่า `$_GET['page']` เดิม (เมนูใช้ไฮไลต์, layout ซ่อนเมนูซ้ายในบาง page)
- ใช้ method เดียวกับ JS เดิม (ajax เดิมเกือบทั้งหมดเป็น POST)
- ห้ามใส่ routes ใน `routes/web.php` (group `web` มี CSRF token ของ Laravel → JS เดิมโดน 419)

## 3. Auth และสิทธิ์

| เดิม | ใหม่ |
|---|---|
| `read_member_cookie()` ว่าง → `Redirect(href.'login')` | middleware `legacy.auth` |
| `getUserID()` | `Legacy::userId()` (int\|null) |
| `checkUser()` | `Legacy::user()` (`App\Models\User`) |
| `getPermission('x')` | `perm('x')` / `@perm('x') ... @endperm` / middleware `legacy.perm:x` |
| `getPermission('a') \|\| getPermission('b')` | `perm('a', 'b')` / `legacy.perm:a,b` |
| `getPermissionAlert('x')` (ajax) | `Alerts::permission('x')` — `legacy.perm` ตอบให้อัตโนมัติ |
| `getPermissionAlert_Page('x')` | `Alerts::permissionPage('x')` — `legacy.perm` ตอบให้อัตโนมัติ |
| `system_check_ajax.php` | middleware `legacy.ajax` |

กฎ:
1. **หน้าแอดมินทุกหน้าและทุก ajax ของแอดมิน** ต้องมี `legacy.perm:<ชื่อโมดูลเดิม>` (ใช้ชื่อเดียวกับที่ไฟล์เดิมเรียก `getPermission(...)`) — ถ้าไฟล์เดิม **ไม่ได้ตรวจสิทธิ์** (มีหลายไฟล์) ให้ใส่สิทธิ์ของหน้าแม่ แล้วบันทึกเป็น "ช่องโหว่เดิมที่แก้"
2. **หน้าลูกค้า**: ทุก query ต้องกรอง `user = Legacy::userId()` (หรือ scope `ownedBy()`) — ห้ามเชื่อ id ที่ส่งมาจาก browser โดยไม่ตรวจเจ้าของ (IDOR)
3. ตรวจสิทธิ์ใน controller ด้วย ถ้าหน้าเดียวกันแสดงต่างกันตามสิทธิ์ (`perm('admin_lot_view')` ฯลฯ)
4. Laravel ตรวจเข้มกว่าเดิม: บัญชีที่ถูกลบ/ปิด/ไม่อนุมัติ หลุดจาก session ทันที (เดิมค้างได้จนหมดอายุ)
5. id ผู้ใช้ใน session = ส่วนหลัง `&` **ตัวสุดท้าย** ของ `decrypt(user_id)` — เดิม `getUserID()` ใช้ `explode('&')[1]` ทำให้ username ที่มี `&` (เช่น `x&5`, สมัครได้) ได้ id ของคนอื่นในไฟล์เดิมที่เรียกแค่ `getUserID()` โดยไม่ผ่าน `checkUser()`/`getPermission()` → **ห้ามพอร์ตไฟล์ใดโดยอ่าน id จาก session เอง** ใช้ `Legacy::userId()` เท่านั้น (ตรวจ `user_role` ด้วยแล้ว)

6. route ที่มี id ผู้ใช้ใน URL (`/x/{userId}`) ใช้ `legacy.own:userId,<โมดูลแอดมิน>` = เป็นของตัวเอง หรือมีสิทธิ์โมดูลนั้น
7. `tests/Feature/RouteSecurityTest.php` ล้มถ้ามี route ที่ไม่มี `legacy.auth`/`legacy.perm`/`legacy.own` หรือ route POST ที่ไม่อยู่ใต้ group `legacy` — หน้าสาธารณะจริง (register, lostpassword ฯลฯ) ให้เพิ่มใน `PUBLIC` ของ test พร้อมเหตุผล
8. IP ผู้ใช้ = `Legacy::clientIp()` เท่านั้น (เชื่อ `X-Real-IP` เฉพาะเมื่อมาจาก proxy ในเครื่อง/วงใน หรือ `LEGACY_TRUSTED_PROXIES`)
9. production ต้องตั้ง `LEGACY_HREF` (หรือ `APP_URL`) — ห้ามสร้าง URL/redirect จาก Host header

## 4. รับค่า (input) และ SQL

- **ห้ามต่อ string เข้า SQL เด็ดขาด** — ใช้ query builder / Eloquent / `DB::select('... ?', [$v])` เท่านั้น
  - `DB::raw()` / `whereRaw()` / `orderByRaw()` ใช้ได้เฉพาะข้อความคงที่ในโค้ด หรือค่าที่ผ่าน whitelist (เช่น ชื่อคอลัมน์ sort)
  - LIKE: `'%'.addcslashes($term, '%_\\').'%'`
- ค่าที่เดิม "ลง DB จริง" (เพื่อให้ข้อมูลเหมือนเดิม): `LegacyInput::post($request, 'name')` / `LegacyInput::query($request, 'id')`
  - เดิม POST ถูกแปลง `'`→`''` แล้วตัด `\` ทิ้ง → ค่าที่ลง DB = ค่าดิบที่ไม่มี `\`
  - เดิม GET ถูก `strip_tags(trim())` และ `id` ถูก `(int)`; **เดิม `extract($_GET)` ทำให้ทุก GET param กลายเป็นตัวแปร** — ห้ามเลียนแบบ
  - ถ้าค่าถูกใช้ฝั่ง PHP แบบเดิม (เช่น encrypt รหัสผ่าน) ใช้ `LegacyInput::postPhp()`
- validate ด้วย `$request->validate()` ได้ แต่ข้อความ error ต้องตอบเป็นรูปแบบเดิม (JS `ErrorBox(...)`) ไม่ใช่ redirect/422 ของ Laravel
- งานที่เขียนหลายตาราง (จ่ายเงิน, ตัดยอด, ย้ายของ) ห่อ `DB::transaction()` (เดิมไม่มี — บันทึกว่าแก้)

## 5. ผลลัพธ์ (response)

- ajax เดิมส่วนใหญ่ตอบเป็น **JS ที่หน้าเว็บ `eval()`** หรือ HTML ชิ้นส่วน → ตอบ string เดิมทุกตัวอักษร `response($js)` (status 200)
  - `getSuccess()` → `Alerts::success($fn, $text)`, `getError()` → `Alerts::error($fn, $text)`, `getNodataAlert()` → `Alerts::noData($fn)`
  - ค่าจากผู้ใช้ที่ต้องฝังใน JS string ต้องผ่าน `Alerts::js($v)` (เดิมไม่ escape = XSS)
- ตัวเลข/วันที่/สถานะ ใช้ helper ที่พอร์ตแล้ว (ตารางข้อ 8) ไม่เขียนใหม่ — ผลต้องตรงกับเดิม (เช่น numberformat **ตัด** ทศนิยม ไม่ปัด)
- JSON เดิม: `response()->json()` จะ escape `/` และ unicode ต่างจาก `json_encode` เดิม → ถ้า JS เดิม parse ได้ทั้งสองแบบ ใช้ `response()->json()` ได้ ถ้าเทียบ byte ให้ `response(json_encode($x))`

## 6. View

```blade
@extends('legacy.layout')
@section('content')
  <h4 class="page-title">{!! res('tracking') !!}</h4>
  <td>{{ $row->tracking }}</td>                       {{-- ค่าจาก DB/ผู้ใช้: {{ }} เสมอ --}}
  <td>{!! Format::numberformat($row->price) !!}</td>  {{-- HTML จาก helper ที่เราคุม: {!! !!} --}}
@endsection
@push('scripts') <script>...</script> @endpush
```

- คัด HTML/JS ของหน้าเดิมมาให้เหมือนเดิม (class, id, ชื่อ input, ฟังก์ชัน JS) — layout ให้ตัวแปร `$page`, `$isMobile`, `$lang`, `$user` ทุก view
- `res('key')` = `$resource['key']` (ภาษาตาม cookie `jlcargo-lang`, key ไม่มี → `''`) ใช้ `{!! res() !!}` เพราะข้อความมี HTML
- **ค่าจาก DB/ผู้ใช้ต้อง `{{ }}`** (เดิม echo ดิบ = stored XSS) ยกเว้นฟิลด์ที่ตั้งใจเป็น HTML (เช่น setting ที่แอดมินเขียน, CKEditor) ให้ระบุในรายงาน
- `href` เดิม → `legacy_href('path')` / `{{ legacy_href() }}path`, `href_blob` → `legacy_blob('file')`
- ไม่ใช้ `@csrf` (ไม่มี session Laravel) — CSRF กันด้วย middleware `LegacyCsrf` (ตรวจ Origin/Referer) อยู่แล้ว
- asset เดิมอยู่ที่ `public/assets` (symlink — `bin/link-assets`) ห้าม commit asset และห้ามใช้ Vite/npm

## 7. Model / DB

- model ครบ 68 ตารางใน `app/Models` (สร้างด้วย `php8.4 artisan legacy:make-models` จาก information_schema)
  - `app/Models/Base/*Base.php` สร้างใหม่ได้ทุกเมื่อ **ห้ามแก้มือ**; relation/scope/accessor ใส่ใน `app/Models/<Model>.php`
  - ทุกตาราง `$timestamps = false` — ตั้ง `create_date/mod_date/create_by/mod_by` เองแบบเดิม (`DB::raw('NOW()')`, `Legacy::userId()`)
  - วันที่ cast เป็น `CarbonImmutable|null`; ค่าว่างของระบบเดิมคือ `'0000-00-00 00:00:00'` → อ่านเป็น `null`, เขียน null ลงคอลัมน์แบบ zero → ได้ค่าศูนย์แบบเดิม; ค่าดิบ `$m->getRawOriginal('col')`
  - ตารางชื่อชน: `log` → `LogRecord`, `sessions` → `PhpSession`, `sessions_v2` → `SessionV2`, `_user` → `UnderscoreUser`
- **ห้าม migration / seeder / แก้ schema** (DB เดียวกับเว็บจริง) — ถ้าต้องมี index ใหม่ ให้เขียนข้อเสนอในรายงาน ไม่ต้องสร้างเอง
- DB dev = `admin_jl_bench` (ผ่าน .env) — ห้ามต่อ `admin_jl` ห้าม dump/import ขนาดใหญ่
- **mass assignment**: ห้าม `fill($request->all())` / `create($request->all())` / `update($request->all())` — ใช้ `$request->only([...])` หรือ validated() เท่านั้น; `User` guard คอลัมน์สิทธิ์/สถานะ/เงิน (`role`, `is_approved`, `commission` ฯลฯ) ไว้แล้ว ต้องตั้งทีละตัว
- ห้าม N+1: eager load (`with()`) หรือ join; หน้า list ต้องมีจำนวน query คงที่ไม่ขึ้นกับจำนวนแถว
- connection ตั้งเหมือนเดิม: utf8mb3, time_zone +07:00, sql_mode ว่าง (non-strict) — ข้อมูลเดิมพึ่งพฤติกรรมนี้
- ทุกคำสั่งที่ไม่ใช่ SELECT ถูกบันทึกลง `storage/logs/legacy/YYYY-MM-DD.log` อัตโนมัติ (แทน `mysqlquerys()`/`goLog()`) ไม่ต้องเรียกเอง

## 8. helper เดิม → ของใหม่

| เดิม | ใหม่ |
|---|---|
| `href`, `href_blob`, ค่าคงที่ `define()` | `Legacy::href()`, `Legacy::hrefBlob()`, `config('legacy.*')` |
| `$_COOKIE[cookiename_lang]` / `$cookie` | `Legacy::lang()` / `$lang` |
| `$_COOKIE[cookiename_*]` | `Legacy::rawCookie('admin_menu')` (cookie เดิมไม่ถูก Laravel เข้ารหัส) |
| `$detect->isMobile()` | `Legacy::isMobile()` / `$isMobile` |
| `get_client_ip()`, `get_user_agent()` | `Legacy::clientIp()`, `Legacy::userAgent()` |
| `encrypt()`, `decrypt()` | `LegacyCrypt::encrypt()`, `LegacyCrypt::decrypt()` (byte ต่อ byte กับ mcrypt) |
| `numberformat*`, `cubicformat`, `numformat`, `numbercal`, `getZero`, `getBold`, `nStr`, `cleanSql`, `bankformat`, `utf8_strlen`, `booltoint`, `randd`, `randomColor`, `hex2rgb` | `Format::<ชื่อเดิม>()` |
| `getthaidate`, `getshortdate`, `getendate`, `getdatesubmit`, `gettime`, `getToday` | `Format::<ชื่อเดิม>()` |
| `getChinaSymbol`, `getThaiSymbol`, `GetFancyBox`, `goLogImg` | `Format::getChinaSymbol()` ฯลฯ, `Format::getFancyBox()`, `Format::goLogImg()` |
| `upload_image_extensions`, `upload_allowed_extensions`, `upload_is_image` | `Format::uploadImageExtensions()`, `uploadAllowedExtensions()`, `uploadIsImage()` |
| `MakePaging`, `MakePagingFixed` | `Format::pagingOffset($page)` + `->offset()->limit(10)` |
| `getpaging($page, $total, $limit, $ajaxpage)` | `Paging::getpaging(...)` (HTML เดิม) |
| `getstatus*`, `getlotstatus`, `getsender`, `getShipType`, `getShippingTypeName` | `Status::<ชื่อเดิม>()` |
| `getSetting`, `getPrefix`, `getPrefixID`, `getCurrentCash`, `getCurrentCashCN`, `getCurrentPoint`, `getStock`, `getStock2`, `convertchina` | `Lookup::<ชื่อเดิม>()` |
| `SaveLog()` | `Lookup::saveLog()` = no-op (เดิม INSERT ผิดคอลัมน์ ไม่เคยบันทึกได้) |
| `goLog()`, `goLogMessage()` | `LegacyLog::goLog()`, `LegacyLog::goLogMessage()` |
| `GetCalPrice`, `CalPrice`, `CalPriceAutoDiff`, `getPrice`, `getShippingTypePrice`, `getShippingTypePrice_EditSender` | `Pricing::getCalPrice()`, `calPrice()`, `calPriceAutoDiff()`, `getPrice()`, `getShippingTypePrice()`, `getShippingTypePriceEditSender()` |
| `normalize_text`, `is_blocked_name`, `normalize_person_name`, `blocked_person_reason`, `blocklist_guard` | `Blocklist::normalizeText()`, `isBlockedName()`, `normalizePersonName()`, `blockedPersonReason()`, `guard()` |
| `check_jail_ip_v3()` | middleware `LegacyJail` (ทุก request อัตโนมัติ) + `Jail::recordFailure()` |
| `getPermissionAlert*`, `getSuccess`, `getError`, `getNodataAlert` | `Alerts::*` |
| `notify_token()` | `Notify::token()` |
| `Redirect($url)` / `defaultpage()` | `redirect()->to(Legacy::href(...))` / `redirect()->to(Legacy::href('403'))` |

**ยังไม่พอร์ต (กลุ่มที่ใช้เป็นเจ้าของ — ย้ายไป `app/Domain/<Group>` ของกลุ่มตัวเอง):**

| เดิม | เจ้าของ | หมายเหตุ |
|---|---|---|
| `CombineTrack()` | tracking | ~250 บรรทัด เขียนหลายตาราง |
| `getPinBox()`, `getNewPinBox()` | payment | HTML+JS กล่อง PIN |
| `getupload()`, `getupload_inline()`, `getuploadmultiple()`, `system_upload*.php` | settings (ใช้ร่วม) | dev ต้องเขียนลง `storage/app/legacy-upload` (`LEGACY_UPLOAD_DRIVER=local`) ห้ามขึ้น Azure จริง |
| `sendmail()`, `dosendmail()` | messaging | ใช้ Mail ของ Laravel (`MAIL_MAILER=log` ใน dev) |
| `notify_message()`, `sendLine()`, `sendLineImage()`, `pushMsgLine()` | messaging | ต้องผ่าน config `legacy.external.enabled` (dev = false → ไม่ยิงจริง) |
| `getcache()`, `savecache()`, `readRedis()`, `writeRedis()` | ผู้ใช้คนแรก | ใช้ `Cache` ของ Laravel ใน dev; ค่าเชื่อมต่อจริงอยู่ใน .env เท่านั้น |
| `getproducttype()` | settings | HTML `<select>` ประเภทสินค้า |
| `check_unjail()` | settings | ไม่มีใครเรียกในโค้ดเดิม (น่าจะเป็น cron) |
| `push_notification()` | — | เดิมเป็นฟังก์ชันว่าง |

## 9. บริการภายนอก / secret

- ห้ามส่งอีเมล / LINE / SMS จริง และห้ามเรียก payment gateway / API ภายนอกด้วย credential จริง ใน dev และ test
- secret (token, รหัส, key, salt) อยู่ใน `.env` หรือไฟล์นอก repo เท่านั้น อ่านผ่าน `config('legacy.*')` — ห้ามเขียนค่าลงโค้ด/เอกสาร/test (โค้ดเดิมมี secret ฝังอยู่ ห้ามคัดลอกมา)
- ก่อน commit ทุกครั้ง: `git diff --cached` แล้วตรวจหา secret
- ห้ามพิมพ์ไฟล์ที่มีรหัสออกจอโดยไม่ mask (เช่น `grep -o '^[A-Z_]*=' .env`)

## 10. ความเร็ว / bench

- ทุกกลุ่มต้องมีผล 1:1 ของหน้าหลักอย่างน้อย 1 หน้า (และ grid ajax หลัก): `bin/bench <old-url> <new-url>` ใส่ตารางในรายงาน
- กติกาเครื่อง: `-c` ไม่เกิน 2, `-n` ไม่เกิน 50 ต่อรอบ, ไม่รัน bench หลายกลุ่มพร้อมกัน
- หน้าใหม่ช้ากว่าเดิม = ต้องอธิบาย (ปกติควรเร็วกว่า เพราะ permission/setting cache ต่อ request และแก้ N+1)

## 11. Git / worktree

- 1 กลุ่ม = 1 branch `mod/<group>` ใน worktree `/home/admin/laravel/jl-wt/<group>` (`bin/wt-new <group>`)
- commit ภาษาอังกฤษ ลงท้ายด้วยบรรทัดว่างแล้ว `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`
- ห้าม push จาก branch กลุ่ม, ห้าม rebase main, ห้าม `composer require` ใน worktree (vendor ใช้ร่วม) — ต้องการ package ใหม่ให้เขียนในรายงาน
- หลังแก้ไฟล์: `bin/fix-perms <worktree>` และ `sudo -u site_jllv php8.4 artisan optimize:clear`

## 12. Checklist ก่อนส่ง review

- [ ] ทุก URL/ajax ของกลุ่มใน `docs/url-map.md` มี route แล้ว หรือระบุเหตุผลที่ไม่ทำ
- [ ] `php8.4 artisan route:list` ผ่าน, `php8.4 vendor/bin/phpunit` ผ่าน
- [ ] หน้าแอดมินทุกหน้ามี `legacy.perm`, หน้าลูกค้ากรองข้อมูลด้วย `Legacy::userId()`
- [ ] ไม่มี SQL ต่อ string, ค่าผู้ใช้ใน view ใช้ `{{ }}`, ค่าใน JS ผ่าน `Alerts::js()`
- [ ] POST ทุกตัวผ่าน `LegacyCsrf` (อยู่ใน group `legacy` อัตโนมัติ — ห้าม `withoutMiddleware`)
- [ ] เทียบผลกับเว็บเดิม (:8101) ด้วยข้อมูลเดียวกัน: HTML/JS ที่ตอบ, แถวที่เขียนลง DB
- [ ] ผล `bin/bench` และรายการ "ต่างจากเดิม/ช่องโหว่เดิม" อยู่ใน `docs/modules/<group>.md`
- [ ] `git diff --cached` ไม่มี secret
