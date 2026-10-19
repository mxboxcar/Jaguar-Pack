<?php
/*
| สร้าง docs/url-map.md: ทุก RewriteRule ใน .htaccess + ทุกไฟล์ PHP ของเว็บเดิม → กลุ่มที่รับผิดชอบ
| ใช้: php8.4 tools/url-map.php /home/admin/bench/sr-old > docs/url-map.md
*/

[$_, $src] = $argv + [null, '/home/admin/bench/sr-old'];

const GROUPS = ['tracking', 'shop', 'order', 'payment', 'balance', 'money-move', 'points', 'lot', 'warehouse', 'users', 'messaging', 'reports', 'settings'];

// โฟลเดอร์ → กลุ่ม (ตาม brief)
$dirs = [
    'tracking' => ['_tracking', '_admin/_tracking', '_admin/_combine_track', '_lost_package', '_admin/_lost_package', '_admin/_check'],
    'shop' => ['_cart', '_taobao', '_api', '_admin/_mallstock', '_findproduct', '_admin/_findproduct'],
    'order' => ['_order', '_admin/_order', '_admin/_taobao', '_admin/_printorder'],
    'payment' => ['_payment', '_admin/_payment', '_alipay', '_admin/_alipay', '_admin/_receipt'],
    'balance' => ['_balance', '_balance_cn', '_admin/_balance', '_admin/_balance_cn', '_admin/_balance_company'],
    'money-move' => ['_refill', '_admin/_refill', '_transfer', '_admin/_transfer', '_admin/_withdrawcash'],
    'points' => ['_point', '_admin/_point', '_admin/_withdrawpoint', '_shop_cashback', '_admin/_shop_cashback'],
    'lot' => ['_lot', '_admin/_lot'],
    'warehouse' => ['_admin/_warehouse', '_admin/_warehouse_section', '_admin/_warehouse_section_move', '_admin/_section', '_goodsconfirm', '_admin/_goodsconfirm'],
    'users' => ['_profile', '_register', '_admin/_user', '_admin/_user_type', '_admin/_role'],
    'messaging' => ['_message', '_admin/_message', '_notice', '_admin/_notice', '_admin/_send'],
    'reports' => ['_export', '_admin/_export', '_admin/_report', '_admin/_log', '_admin/_nobilling', '_dashboard', '_admin/_dashboard'],
    'settings' => ['_admin/_setting', '_admin/_exchange', '_admin/_product_type', '_admin/_shipping_type'],
];
// ไฟล์ที่ root → กลุ่ม (foundation = ฐานร่วม ทำแล้ว/ไม่ต้องพอร์ตแยก)
$rootFiles = [
    'foundation' => ['index.php', 'content.php', 'menu.php', 'menu_mobile.php', 'login.php', 'system_config.php', 'system_db.php',
        'system_status_function.php', 'system_check_ajax.php', 'system_check_login.php', 'system_checkuser.php',
        'system_change_language.php', 'system_paging.php', 'system_dedup_guard.php', 'system_blocklist.php',
        'assets/validate/validationengine.php', 'resource/th.php', 'resource/en.php', 'resource/cn.php', 'sessionmaster/database.class.php', 'sessionmaster/mysql.sessions.php'],
    'users' => ['register.php', 'lost_password.php', 'password.php', 'system_check_username.php'],
    'messaging' => ['notification.php', 'notifications.php', 'notify_counts.php', 'notify_token.php', 'email_center.php', 'email_header.php', 'email_footer.php'],
    'reports' => ['download_file.php', 'print_header.php', 'print_footer.php'],
    'settings' => ['get_price.php', 'get_resource.php', 'load_quick_search.php', 'resource.php', 'system_ckupload.php', 'system_upload.php', 'system_upload_2.php', 'index_close.php'],
];
$foundationNote = [
    'index.php' => 'resources/views/legacy/layout.blade.php + routes/legacy.php', 'content.php' => 'routes/modules/*.php (แทน switch page)',
    'menu.php' => 'resources/views/legacy/partials/menu.blade.php', 'menu_mobile.php' => 'resources/views/legacy/partials/menu_mobile.blade.php',
    'login.php' => 'GET /login → LegacyLoginController@show', 'system_check_login.php' => 'POST /system_check_login',
    'system_checkuser.php' => 'POST /system_checkuser (logout/RandomStr)', 'system_change_language.php' => 'POST /system_change_language',
    'system_config.php' => 'config/legacy.php + app/Support/Legacy/*', 'system_status_function.php' => 'app/Support/Legacy/{Status,Lookup,Pricing,Jail,Blocklist,LegacyLog}',
    'system_check_ajax.php' => 'middleware legacy.ajax', 'system_paging.php' => 'Format::pagingOffset / paginator ของ Laravel',
    'system_db.php' => '.env (DB_*)', 'system_dedup_guard.php' => '(ปิดใช้งานในเว็บเดิม)', 'system_blocklist.php' => 'ข้อมูล: bin/link-assets คัดลอกไป storage/app/legacy',
    'assets/validate/validationengine.php' => 'resources/views/legacy/partials/validationengine.blade.php',
    'resource/th.php' => 'lang/th/legacy.php (res())', 'resource/en.php' => 'lang/en/legacy.php (res())', 'resource/cn.php' => 'lang/cn/legacy.php (res())',
    'sessionmaster/database.class.php' => '(ไม่ใช้แล้ว — session อยู่ใน sessions_v2)', 'sessionmaster/mysql.sessions.php' => '(ไม่ใช้แล้ว)',
];

// หมายเหตุของไฟล์ในกลุ่มต่างๆ (พฤติกรรมแปลกของเว็บเดิมที่คนพอร์ตต้องรู้)
$fileNote = [
    'get_price.php' => 'ตรวจ `getPermission(\'view_bol\')` แต่ไม่มีโมดูล view_bol ใน role_detail → ทุกคนได้ ErrorBox สิทธิ์ (endpoint ตาย)',
    'password.php' => 'ใช้ตาราง `user_copy` ซึ่งไม่มีใน DB (query ล้มเงียบ)',
    '_payment/save_create_receiptaddress.php' => 'ตาราง `user_receipt_address` ไม่มีใน DB (query ล้มเงียบ)',
    '_payment/load_receipt_address.php' => 'ตาราง `user_receipt_address` ไม่มีใน DB (query ล้มเงียบ)',
    '_payment/save_deletereceiptaddress.php' => 'ตาราง `user_receipt_address` ไม่มีใน DB (query ล้มเงียบ)',
    '_payment/save_deleteaddress.php' => 'ตาราง `user_address` ไม่มีใน DB (query ล้มเงียบ)',
    '_admin/_order/load_paging.php' => 'ตาราง `caching` ไม่มีใน DB (query ล้มเงียบ)',
    '_admin/_mall_category/index.php' => 'ตรวจ `getPermission(\'admin_mall_category\')` แต่ไม่มีโมดูลนี้ใน role_detail → ไม่มีใครเข้าได้ (แม้แอดมิน)',
    '_admin/_mall_category/grid.php' => 'ตรวจ `getPermission(\'admin_mall_category\')` แต่ไม่มีโมดูลนี้ใน role_detail → ไม่มีใครเข้าได้ (แม้แอดมิน)',
    '_admin/_mall_category/save.php' => 'ตรวจ `getPermission(\'admin_mall_category\')` แต่ไม่มีโมดูลนี้ใน role_detail → ไม่มีใครเข้าได้ (แม้แอดมิน)',
    '_admin/_payment/load_select_lot.php' => 'ตรวจ `getPermission(\'admin_payment_createpayment\')` แต่ไม่มีโมดูลนี้ใน role_detail → ไม่มีใครเข้าได้ (แม้แอดมิน)',
];

function groupOfFile(string $rel, array $dirs, array $rootFiles): string
{
    foreach ($rootFiles as $g => $files) {
        if (in_array($rel, $files, true)) {
            return $g;
        }
    }
    $best = '';
    $bestG = '';
    foreach ($dirs as $g => $ds) {
        foreach ($ds as $d) {
            if (str_starts_with($rel, $d.'/') && strlen($d) > strlen($best)) {
                [$best, $bestG] = [$d, $g];
            }
        }
    }
    if ($bestG !== '') {
        return $bestG;
    }
    // SR: ร้านค้า (_admin/_mall) ไม่มีตาราง mall* ใน admin_sr และไม่มีใน content.php — เจ้าของให้ตัดออก (2026-10-20)
    if (str_starts_with($rel, '_admin/_mall/')) {
        return 'dropped (SR ไม่มีร้านค้า — เจ้าของให้ตัด 2026-10-20)';
    }
    if (str_starts_with($rel, 'assets/phpexcel/')) {
        return '3rd-party (PHPExcel → ใช้ไลบรารีใหม่ในกลุ่ม reports)';
    }
    if (str_starts_with($rel, 'assets/')) {
        return '3rd-party (assets — ไม่เสิร์ฟผ่าน Laravel)';
    }

    return 'UNASSIGNED';
}

// page → ไฟล์ จาก content.php
preg_match_all('/case\s+\'([a-z0-9_]+)\'\s*:\s*require_once\s*\(\s*"([^"]+)"\s*\)/', file_get_contents("$src/content.php"), $m, PREG_SET_ORDER);
$pages = [];
foreach ($m as [$_, $page, $file]) {
    $pages[$page] ??= $file;
}

// RewriteRule
$rules = [];
foreach (file("$src/.htaccess") as $n => $line) {
    $t = trim($line);
    if (! str_starts_with($t, 'RewriteRule')) {
        continue;
    }
    $parts = preg_split('/\s+/', $t);
    $rules[] = ['line' => $n + 1, 'pattern' => $parts[1] ?? '', 'target' => $parts[2] ?? '', 'flags' => $parts[3] ?? ''];
}

$out = [];
$out[] = '# URL map: เว็บเดิม → กลุ่มที่รับผิดชอบ';
$out[] = '';
$out[] = 'สร้างอัตโนมัติด้วย `php8.4 tools/url-map.php /home/admin/bench/sr-old > docs/url-map.md` — ห้ามแก้มือ (แก้ตารางใน tools/url-map.php แล้วสร้างใหม่)';
$out[] = '';
$out[] = '- กลุ่ม: '.implode(', ', array_map(fn ($g) => "`$g`", GROUPS)).' · `foundation` = ฐานร่วม (ทำแล้ว)';
$out[] = '- bench เดิม (:8111) ไม่มี rewrite: เปิดหน้าด้วย `http://127.0.0.1:8111/index.php?page=<page>` และ ajax ด้วย `/<path>.php`';
$out[] = '- กฎท้ายไฟล์ `^(.*?)/?$ → $1.php` ทำให้ทุกไฟล์ `.php` เรียกได้โดยไม่ต้องมี `.php` (ajax ทั้งหมดใช้แบบนี้) — ดูตารางที่ 2';
$out[] = '';
$out[] = '## 1. RewriteRule ทั้งหมด ('.count($rules).' บรรทัด)';
$out[] = '';
$out[] = '| # | บรรทัด | URL (regex) | ปลายทางเดิม | page | ไฟล์ | กลุ่ม |';
$out[] = '|---|---|---|---|---|---|---|';
$counts = [];
foreach ($rules as $i => $r) {
    $page = '';
    $file = '';
    $g = '';
    if (preg_match('/[?&]page=([a-z0-9_]+)/', $r['target'], $pm)) {
        $page = $pm[1];
        $file = $pages[$page] ?? '(ไม่มีใน content.php)';
        if (isset($pages[$page]) && ! is_file($src.'/'.$pages[$page])) {
            $file .= ' (**ไม่มีไฟล์นี้ในเว็บเดิม** — หน้าเดิม error/ว่าง)';
        }
        $g = isset($pages[$page]) ? groupOfFile($pages[$page], $dirs, $rootFiles) : 'UNASSIGNED';
        // SR: page ที่ไม่มีใน content.php หรือไฟล์ไม่มีจริง = หน้าเดิมเปิดไม่ได้ → ไม่พอร์ต (Laravel 404 → หน้าแรก)
        if (! isset($pages[$page]) || ! is_file($src.'/'.$pages[$page])) {
            $g = 'dead (หน้าเดิมไม่มี — ไม่พอร์ต)';
        }
    } elseif (str_ends_with($r['target'], '.php') && ! str_contains($r['target'], '$')) {
        $file = $r['target'];
        $g = groupOfFile($file, $dirs, $rootFiles);
    } else {
        $g = 'foundation (nginx/infra)';
    }
    $counts[$g] = ($counts[$g] ?? 0) + 1;
    $esc = fn ($s) => str_replace('|', '\\|', $s);
    $out[] = sprintf('| %d | %d | `%s` | `%s` %s | %s | %s | **%s** |', $i + 1, $r['line'], $esc($r['pattern']), $esc($r['target']), $esc($r['flags']), $page, $file, $g);
}

// ทุกไฟล์ PHP
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
$files = [];
foreach ($it as $f) {
    $rel = substr($f->getPathname(), strlen($src) + 1);
    if ($f->getExtension() !== 'php' || str_starts_with($rel, 'vendor/')) {
        continue;
    }
    $files[] = $rel;
}
sort($files);
$byGroup = [];
foreach ($files as $rel) {
    $byGroup[groupOfFile($rel, $dirs, $rootFiles)][] = $rel;
}
$pageOf = array_flip(array_reverse($pages, true));

$out[] = '';
$out[] = '## 2. ไฟล์ PHP ทั้งหมดของเว็บเดิม ('.count($files).' ไฟล์, ไม่รวม vendor/)';
$out[] = '';
$out[] = 'URL ajax = ชื่อไฟล์ตัด `.php` (เช่น `_tracking/grid.php` → `POST /_tracking/grid`) · ไฟล์ที่เป็นหน้า (page) แสดง page ไว้ด้วย';
$order = [...GROUPS, 'foundation'];
foreach (array_keys($byGroup) as $g) {
    if (! in_array($g, $order, true)) {
        $order[] = $g;
    }
}
foreach ($order as $g) {
    if (empty($byGroup[$g])) {
        continue;
    }
    $out[] = '';
    $out[] = "### $g (".count($byGroup[$g]).' ไฟล์)';
    $out[] = '';
    if (str_starts_with($g, '3rd-party')) {
        $dirsShown = array_unique(array_map(fn ($f) => implode('/', array_slice(explode('/', $f), 0, 2)).'/', $byGroup[$g]));
        foreach ($dirsShown as $d) {
            $n = count(array_filter($byGroup[$g], fn ($f) => str_starts_with($f, $d)));
            $out[] = "- `{$d}**` ($n ไฟล์)";
        }

        continue;
    }
    $out[] = '| ไฟล์ | URL เดิม | page | หมายเหตุ |';
    $out[] = '|---|---|---|---|';
    foreach ($byGroup[$g] as $rel) {
        $url = '/'.substr($rel, 0, -4);
        $out[] = sprintf('| `%s` | `%s` | %s | %s |', $rel, $url, $pageOf[$rel] ?? '', $g === 'foundation' ? ($foundationNote[$rel] ?? '') : ($fileNote[$rel] ?? ''));
    }
}

$out[] = '';
$out[] = '## 3. สรุปจำนวน';
$out[] = '';
$out[] = '| กลุ่ม | RewriteRule | ไฟล์ PHP |';
$out[] = '|---|---|---|';
foreach ($order as $g) {
    if (! isset($counts[$g]) && empty($byGroup[$g])) {
        continue;
    }
    $out[] = sprintf('| %s | %d | %d |', $g, $counts[$g] ?? 0, count($byGroup[$g] ?? []));
}
foreach ($counts as $g => $c) {
    if (! in_array($g, $order, true)) {
        $out[] = sprintf('| %s | %d | 0 |', $g, $c);
    }
}
$un = count($byGroup['UNASSIGNED'] ?? []) + ($counts['UNASSIGNED'] ?? 0);
$out[] = '';
$out[] = $un === 0 ? 'ไม่มีรายการที่ยังไม่มีเจ้าของ (UNASSIGNED = 0)' : "**มี $un รายการที่ยังไม่มีเจ้าของ (UNASSIGNED)**";
echo implode("\n", $out), "\n";
