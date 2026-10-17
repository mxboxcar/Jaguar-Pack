# jl — พอร์ทัล portal.jlcargoth.com บน Laravel

ย้ายเว็บเดิม (PHP 7.4 + mysqli, ~65K บรรทัด) มาเป็น Laravel 13 ธรรมดา (ไม่ใช้ Octane) แบบทยอยย้าย (strangler)
ใช้ **DB เดิม** และ **session เดิม** ร่วมกัน: ล็อกอินฝั่งไหนก็ใช้ได้ทั้งสองฝั่ง ย้ายทีละหน้าได้โดยผู้ใช้ไม่รู้ตัว

> ยังไม่สลับเว็บจริง จนกว่าเจ้าของสั่ง — ทุกอย่างตอนนี้รันบน dev (127.0.0.1) กับ DB `admin_jl_bench`

เอกสาร:
- [docs/CONVENTIONS.md](docs/CONVENTIONS.md) — กติกาการพอร์ต (อ่านก่อนเริ่มทุกครั้ง)
- [docs/url-map.md](docs/url-map.md) — ทุก RewriteRule (171) และทุกไฟล์ PHP เดิม → กลุ่มที่รับผิดชอบ

`CLAUDE.md` / `AGENTS.md` เป็น boilerplate ของ Laravel Boost — **ไม่ต้องทำตาม** (ห้ามติดตั้ง PHP ผ่าน php.new, ห้ามติดตั้ง laravel/boost)

## เครื่องและคำสั่งพื้นฐาน (leopard)

| | |
|---|---|
| PHP ของ Laravel | `php8.4` (คำสั่ง `php` = 7.4 ของเว็บเดิม ห้ามเปลี่ยน alternatives) |
| composer | `COMPOSER_ALLOW_SUPERUSER=1 php8.4 /opt/frankenphp/bin/composer ...` |
| Laravel dev (main) | http://127.0.0.1:8100 (nginx + php8.4-fpm pool `site_jllv`, open_basedir = โฟลเดอร์นี้) |
| เว็บเดิมสำหรับเทียบ | http://127.0.0.1:8101 = `/home/admin/bench/jl-old` (php7.4, ไม่มี rewrite: ใช้ `index.php?page=...` และ `xxx.php`) |
| DB dev | `admin_jl_bench` (ตั้งใน `.env`) — ห้ามเขียน `admin_jl` |
| หลังแก้ไฟล์ | `bin/fix-perms && sudo -u site_jllv php8.4 artisan optimize:clear` |
| test | `php8.4 vendor/bin/phpunit` (ไม่แตะ DB) |
| routes ทั้งหมด | `php8.4 artisan route:list` |

สิทธิ์ไฟล์: `root:site_jllv` dir 2750 / file 640, `storage/` และ `bootstrap/cache` เป็นของ `site_jllv`, `public/` group `www-data` (`bin/fix-perms` ทำให้)
รัน artisan ที่เขียนไฟล์ (cache/view) ด้วย `sudo -u site_jllv php8.4 artisan ...` ไม่งั้นไฟล์จะเป็นของ root แล้ว php-fpm เขียนต่อไม่ได้

## ฐานร่วม (foundation) มีอะไรบ้าง

| ส่วน | ไฟล์ |
|---|---|
| session ร่วม | `app/Support/Legacy/LegacySession.php` (cookie `PHPSESSID` → `sessions_v2`, รูปแบบ serialize ของ PHP: `PhpSessionCodec`) |
| เข้ารหัสแบบเดิม | `app/Support/Legacy/LegacyCrypt.php` — phpseclib3 Rijndael block 256 ECB = mcrypt เดิม byte ต่อ byte (salt ใน `.env` `LEGACY_SESSION_SALT`) |
| guard `legacy` | `app/Auth/LegacySessionGuard.php` (`Auth::user()`), `app/Auth/LegacyLogin.php` (ตรวจรหัสแบบเดิม) |
| login / logout / ภาษา | `routes/legacy.php` → `LegacyLoginController`: `GET /login`, `POST /system_check_login`, `POST /system_checkuser`, `POST /system_change_language` |
| middleware | group `legacy` (session เดิม + `LegacyJail` + `LegacyCsrf`), `legacy.auth`, `legacy.perm:<module>`, `legacy.own:<param>,<module>`, `legacy.ajax` |
| สิทธิ์ | `app/Support/Legacy/Permissions.php` = `getPermission()` (โหลดทั้ง role ครั้งเดียวต่อ request), helper `perm()`, `@perm` |
| layout | `resources/views/legacy/layout.blade.php` (+ `partials/menu*`, `login.blade.php`) แปลงจาก index.php / menu.php เดิมด้วย `tools/port-layout.php` — HTML ตรงกับเดิม (ต่างแค่ช่องว่าง) |
| ภาษา | `lang/{th,en,cn}/legacy.php` (จาก `resource/*.php` เดิม) + `res('key')` |
| helper เดิม | `app/Support/Legacy/{Format,Status,Lookup,Pricing,Paging,Blocklist,Jail,Alerts,LegacyLog,Notify,LayoutData,Legacy}.php` (ตารางเทียบใน CONVENTIONS ข้อ 8) |
| model | `app/Models/*` ครบ 68 ตาราง (`app/Models/Base/*` สร้างด้วย `php8.4 artisan legacy:make-models`) |
| routes ของกลุ่ม | `routes/modules/<group>.php` 13 ไฟล์ โหลดอัตโนมัติ (ดู `bootstrap/app.php`) |
| asset เดิม | `public/assets` → symlink ไป mirror `/home/admin/laravel/legacy-assets` (ไม่ commit, `bin/link-assets`) |

หน้า dev สำหรับตรวจ (ไม่เปิดใน production): `/dev/whoami` (ใครล็อกอินอยู่ + สิทธิ์), `/dev/layout` (layout ตัวอย่าง), `/dev/admin-only` (ตัวอย่าง `legacy.perm`), `/dev/tracking/{id}` + `/dev/tracking-legacy/{id}` (ต้องส่ง cookie PHPSESSID: ดูได้เฉพาะของตัวเองหรือแอดมิน `admin_tracking`)

## การพอร์ตโมดูล (ทีละกลุ่ม)

13 กลุ่ม: `tracking shop order payment balance money-move points lot warehouse users messaging reports settings` (ไฟล์/URL ของแต่ละกลุ่มดู `docs/url-map.md`)

```bash
bin/wt-new tracking                 # worktree /home/admin/laravel/jl-wt/tracking, branch mod/tracking (จาก main)
cd /home/admin/laravel/jl-wt/tracking
bin/wt-serve tracking --bg          # http://127.0.0.1:8201 (php8.4 -S, user site_jllv — ไม่แตะ nginx/systemd)
#   แก้ routes/modules/tracking.php, app/Http/Controllers/Tracking/..., resources/views/tracking/...
bin/fix-perms . && sudo -u site_jllv php8.4 artisan optimize:clear
bin/bench 'http://127.0.0.1:8101/index.php?page=tracking' http://127.0.0.1:8201/tracking
bin/wt-serve tracking --stop
bin/wt-rm tracking                  # ลบ worktree เมื่อ merge แล้ว (branch ยังอยู่; --branch ลบ branch ด้วย)
```

- ทดลองเครื่องมือ/ลองพอร์ตทิ้ง: `bin/wt-new zz-probe` (port 8299) → `bin/wt-rm zz-probe --branch --force` (ห้าม merge `mod/zz-*`)
- `vendor/` ของ worktree ไม่ใช่ symlink ทั้งโฟลเดอร์ (ไม่งั้นคลาส `App\*` ถูกโหลดจาก main) — `bin/wt-vendor <worktree>` สร้างให้ (wt-new เรียกเอง; รันใหม่เมื่อ `composer.lock` ของ main เปลี่ยน)
- เทียบ HTML เดิม/ใหม่: แทน `https://portal.jlcargoth.com/` กับ `http://127.0.0.1:82xx/` เป็นค่าเดียวกันก่อน diff (ดู CONVENTIONS ข้อ 10)
- ผลต่าง PHP 7.4 → 8.4 ที่ทำให้พอร์ตแล้วพัง (TypeError, `==`, `html_entity_decode`, SQL error) ดู CONVENTIONS ข้อ 13

port ของ worktree: tracking 8201, shop 8202, order 8203, payment 8204, balance 8205, money-move 8206, points 8207, lot 8208, warehouse 8209, users 8210, messaging 8211, reports 8212, settings 8213 (`bin/wt-serve --port-of <group>`)

ขั้นตอนต่อหน้า:
1. เปิดไฟล์เดิม (`/home/admin/bench/jl-old/...`) ดู SQL, การตรวจสิทธิ์ (`getPermission`), ค่าที่ตอบกลับ
2. เพิ่ม route (URL เดิม), controller, view ตาม CONVENTIONS — query ใหม่ต้อง bind ค่าและไม่มี N+1
3. เทียบกับเดิมด้วย cookie เดียวกัน: HTML/JS ที่ตอบ, แถวที่เขียนลง DB (ใช้ข้อมูลใน `admin_jl_bench`)
4. `bin/bench` เดิม vs ใหม่ แล้วบันทึกผล + สิ่งที่ต่างจากเดิม + ช่องโหว่เดิมที่เจอ ใน `docs/modules/<group>.md`

## session สำหรับทดสอบ / bench

```bash
SID=$(cat /root/webroot-backups/php84-install-20261017/bench_sid)                    # user 137 (ลูกค้า)
SID=$(php7.4 /root/webroot-backups/php84-install-20261017/make_bench_session.php 42) # user อื่น (เช่นแอดมิน)
curl -b "PHPSESSID=$SID" http://127.0.0.1:8100/dev/whoami
```

บัญชีทดสอบใน `admin_jl_bench` เท่านั้น: `zz_fdn_cust` (role 3 ลูกค้า), `zz_fdn_admin` (role 1 แอดมิน) — รหัสอยู่ใน `/root/jl-tools/bench-test-password` (root 600, ไม่อยู่ใน git)

ตรวจ login ร่วมสองทาง:
```bash
PW=$(cat /root/jl-tools/bench-test-password)
# เดิม → ใหม่: ล็อกอินด้วยโค้ดเดิมจริง (endpoint :8101 ใช้ไม่ได้เพราะ goLogMessage เขียน log ไป webroot production แล้ว exit)
SID=$(php7.4 tools/legacy-login-sim.php zz_fdn_cust "$PW"); curl -b "PHPSESSID=$SID" http://127.0.0.1:8100/dev/whoami
# ใหม่ → เดิม
curl -c jar -H 'X-Requested-With: XMLHttpRequest' -H 'Origin: http://127.0.0.1:8100' \
     --data-urlencode username=zz_fdn_admin --data-urlencode "password=$PW" http://127.0.0.1:8100/system_check_login
curl -b jar -d RandomStr=1 http://127.0.0.1:8101/system_checkuser.php     # {"username":"zz_fdn_admin","id":"..."}
```

## กติกาที่ห้ามละเมิด (เครื่อง production มีเว็บลูกค้าจริง)

- ห้ามแตะ `/home/admin/web/**`, DB `admin_jl` (อ่านอย่างเดียวถ้าจำเป็น), `/etc/nginx`, `/etc/php`, systemd, cron, เว็บอื่น
- ห้าม restart/reload service, ห้าม `apt install`, ห้ามแก้ grant MySQL
- ห้าม secret ใน git: ค่าจริงอยู่ใน `.env` เท่านั้น, ตรวจ `git diff --cached` ก่อน commit ทุกครั้ง, ห้ามพิมพ์ `.env` โดยไม่ mask
- ห้ามส่งอีเมล/LINE/SMS จริง หรือเรียก payment gateway/API ภายนอกด้วย credential จริง (`MAIL_MAILER=log`)
- ลดโหลด: `ab` concurrency ≤ 2 และ ≤ 50 requests ต่อรอบ (`bin/bench` บังคับให้), ห้าม dump/import DB ใหญ่, ห้าม build หนักๆ พร้อมกัน
- งานนี้เป็นของกลุ่ม 1 — ห้ามแตะอะไรของกลุ่ม 2
- commit ภาษาอังกฤษ ลงท้าย `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`; ไม่ push จาก branch กลุ่ม

## สิ่งที่ต่างจากเว็บเดิมโดยตั้งใจ (ฐานร่วม)

- login: ออก session id ใหม่ทุกครั้ง (กัน session fixation), ไม่เก็บ/ไม่ log รหัสผ่านที่พิมพ์ผิด (เดิมเก็บ plaintext ใน `ips.password` และไฟล์ log), cookie `PHPSESSID` ที่ Laravel ออกเป็น SameSite=Lax
- บัญชีที่ถูกลบ/ปิด/ปฏิเสธ หลุดจากระบบทันที (เดิมค้างใน session ได้ 7 วัน)
- cookie ภาษา รับเฉพาะ `th/en/cn` (เดิม `include('resource/'.$cookie.'.php')` = LFI)
- CSRF: POST ต้องมาจาก host เดียวกัน (Origin/Referer) หรือเป็น XHR (เดิมไม่มีการกันเลย)
- เมนู: ตรวจสิทธิ์จาก cache ต่อ request (เดิม ~60 ครั้ง × 2 query ต่อหน้า) — layout อย่างเดียว เดิม ~270 ms → ใหม่ ~50 ms
- log คำสั่ง SQL: ปิดค่าในคำสั่งที่แตะ `password`/`pin`
