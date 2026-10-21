<?php
// สร้าง tests/fixtures/legacy-format-cases.json: php7.4 tools/gen-format-cases.php > tests/fixtures/legacy-format-cases.json
// php7.4: ดึงฟังก์ชัน pure จากโค้ดเดิมมา eval แล้วคำนวณค่าคาดหวัง (ไม่ include system_config.php ทั้งไฟล์ เพื่อไม่ต่อ DB)
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
chdir('/home/admin/bench/pack-old');
define('cookiename_lang', 'pack-cargo-lang'); define('project_name', 'PACK-Cargo '); define('href_blob', 'https://image.pack-cargo.net/upload/'); define('admin_email','packcargo789@gmail.com');
$_COOKIE['pack-cargo-lang'] = 'th';
function grab($file, $names) {
    $toks = token_get_all(file_get_contents($file)); $out = ''; $n = count($toks);
    for ($i = 0; $i < $n; $i++) {
        if (is_array($toks[$i]) && $toks[$i][0] === T_FUNCTION) {
            $k = $i + 1; while (is_array($toks[$k]) && $toks[$k][0] === T_WHITESPACE) $k++;
            if (!is_array($toks[$k]) || !in_array($toks[$k][1], $names, true)) continue;
            $buf = ''; $depth = 0; $started = false;
            for ($j = $i; $j < $n; $j++) {
                $t = $toks[$j]; $txt = is_array($t) ? $t[1] : $t; $buf .= $txt;
                if ($t === '{' || (is_array($t) && in_array($t[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES]))) { $depth++; $started = true; }
                if ($t === '}') { $depth--; if ($started && $depth === 0) break; }
            }
            $out .= $buf."
";
        }
    }
    return $out;
}
eval(grab('system_config.php', ['numberformat','numberformatpoint','numberformatzero','numberformatone','cubicformat','getthaidate','getshortdate','getendate','bankformat','utf8_strlen','hex2rgb']));
eval(grab('system_paging.php', ['getpaging']));
eval(grab('system_status_function.php', ['getstatus','getstatusorder','getlotstatus','getShipType','getZero','normalize_text','is_blocked_name','normalize_person_name']));
$cases = [];
foreach (['1234.567', '0', '-12.3', 1234.5, '1,234.5', '', '7', 0.1+0.2, '1.0E-5'] as $v) {
    $cases[] = ['numberformat', [$v], numberformat($v)];
    $cases[] = ['numberformat', [$v, true, true, true], numberformat($v, true, true, true)];
    $cases[] = ['numberformat', [$v, false], numberformat($v, false)];
    $cases[] = ['numberformatpoint', [$v], numberformatpoint($v)];
    $cases[] = ['numberformatzero', [$v], numberformatzero($v)];
    $cases[] = ['numberformatone', [$v], numberformatone($v)];
}
foreach (['0.12345', 3, '1.5', 0.000049] as $v) $cases[] = ['cubicformat', [$v], cubicformat($v)];
foreach (['2026-10-17', '2026-01-05 13:00:00', '0000-00-00', '2026-12-31'] as $v) {
    $cases[] = ['getthaidate', [$v], getthaidate($v)];
    $cases[] = ['getthaidate', [$v, true, true], getthaidate($v, true, true)];
    $cases[] = ['getthaidate', [$v, false, true], getthaidate($v, false, true)];
}
include 'resource/th.php';
foreach (['5 '.$resource['jan'].' 2026', '17 '.$resource['oct'].' 2026'] as $v) { $cases[] = ['getshortdate', [$v], getshortdate($v)]; $cases[] = ['getendate', [$v], getendate($v)]; }
foreach (['1234567890', '123'] as $v) { $cases[] = ['bankformat', [$v], bankformat($v)]; $cases[] = ['bankformat', [$v, false], bankformat($v, false)]; }
foreach (['#fff', '#10a0ff'] as $v) $cases[] = ['hex2rgb', [$v], hex2rgb($v)];
foreach (['0.00', '0', 0, '0.000', '5', '<span class="currency">0.00</span>', 'abc'] as $v) $cases[] = ['getZero', [$v], getZero($v)];
foreach ([-1, 0, 1, 2, 3, '2', 9] as $v) { $cases[] = ['getstatus', [$v], getstatus($v)]; $cases[] = ['getstatusorder', [$v], getstatusorder($v)]; }
foreach ([0, 1, 2] as $v) $cases[] = ['getlotstatus', [$v], getlotstatus($v)];
foreach ([0, 1] as $v) { $cases[] = ['getShipType', [$v], getShipType($v)]; $cases[] = ['getShipType', [$v, false], getShipType($v, false)]; }
foreach (['admin', 'Adm1n', 'boyd', 'a-d-r-n-i-n', 'somchai', 'jl', 'srs', 'srshipping', 's-r-s', 'pack', 'packcargo', 'p-a-c-k', 'backpack', 'r00t', 'ａｄｍｉｎ'] as $v) { $cases[] = ['isBlockedName', [$v], is_blocked_name($v)]; $cases[] = ['normalizeText', [$v], normalize_text($v)]; }
foreach (['นาย สมชาย  ใจดี', 'Mr. John Smith', "น.ส.\xE2\x80\x8Bสมหญิง"] as $v) $cases[] = ['normalizePersonName', [$v], normalize_person_name($v)];
foreach ([[1, 5, 15], [1, 100, 10], [5, 100, 10], [9, 100, 10], [10, 100, 10], [2, 30, 15], [3, 200, 15]] as [$p, $t, $l]) $cases[] = ['getpaging', [$p, $t, $l, '_tracking/grid'], getpaging($p, $t, $l, '_tracking/grid')];
echo json_encode($cases, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
