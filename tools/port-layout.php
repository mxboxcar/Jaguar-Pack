<?php
/*
| แปลง index.php / menu.php / menu_mobile.php / login.php / assets/validate/validationengine.php ของเว็บเดิม
| → resources/views/legacy/*.blade.php แบบกลไก (หน้าตา/JS เหมือนเดิมทุกบรรทัด)
| ใช้: php8.4 tools/port-layout.php /home/admin/bench/pack-old
| แปลงแล้วตรวจด้วย: grep -n 'mysqlquerys\|\$_GET\|\$_COOKIE\|\$resource\|href;' resources/views/legacy  (ต้องว่าง)
*/

[$_, $src] = $argv + [null, '/home/admin/bench/pack-old'];
$dst = dirname(__DIR__).'/resources/views/legacy';
@mkdir($dst.'/partials', 0750, true);

function convert(string $s): string
{
    $L = '\\App\\Support\\Legacy';
    $rules = [
        // ค่าคงที่ define() เดิม
        '/<\?php\s+echo\s+href;?\s*\?>/' => '{{ legacy_href() }}',
        '/<\?php\s+echo\s+href_blob;?\s*\?>/' => '{{ legacy_blob() }}',
        '/<\?php\s+echo\s+trim\(project_name\);?\s*\?>/' => "{{ trim(config('legacy.project_name')) }}",
        '/<\?php\s+echo\s+project_name;?\s*\?>/' => "{{ config('legacy.project_name') }}",
        '/<\?php\s+echo\s+version;?\s*\?>/' => "{{ config('legacy.version') }}",
        '/<\?php\s+echo\s+cookie_path;?\s*\?>/' => "{{ config('legacy.cookie_path') }}",
        '/<\?php\s+echo\s+cookiename_admin_menu;?\s*\?>/' => "{{ config('legacy.cookies.admin_menu') }}",
        '/<\?php\s+echo\s+\$cookie;?\s*\?>/' => '{{ $lang }}',
        '/<\?php\s+echo\s+date\("Y"\);?\s*\?>/' => "{{ date('Y') }}",
        // PACK login.php: $site / $error ไม่เคยถูกกำหนด → echo ค่าว่าง
        '/<\?php\s+echo\s+\$site\[\'data\'\]\[\'stitle\'\];?\s*\?>/' => '',
        '/<\?php\s+echo\s+\$error;?\s*\?>/' => '',
        '/<\?php\s+echo\s+notify_token\(\);?\s*\?>/' => "{{ $L\\Notify::token() }}",
        // $resource['x'] เดิม echo ดิบ (ข้อความมี HTML) → {!! res() !!}
        '/<\?php\s+echo\s+\$resource\[[\'"]([a-zA-Z0-9_]+)[\'"]\];?\s*\?>/' => "{!! res('$1') !!}",
        // include
        "/<\?php\s+require_once\('assets\/validate\/validationengine\.php'\);?\s*\?>/" => "@include('legacy.partials.validationengine')",
        "/<\?php\s+require_once\('menu\.php'\);\s*\?>/" => "@include('legacy.partials.menu')",
        "/require_once\('menu_mobile\.php'\);/" => "echo view('legacy.partials.menu_mobile', get_defined_vars()['__data'])->render();",
        "/<\?php\s+require_once\('content\.php'\)\s*\?>/" => "@yield('content')",
    ];
    $s = preg_replace(array_keys($rules), array_values($rules), $s);

    // SQL ในเมนู → LayoutData (bind ค่า + cache ต่อ request)
    $sql = [
        '/\$sql = "SELECT \* FROM notice WHERE is_show = 1 ORDER BY create_date DESC";\s*\$result = mysqlquerys\(\$sql\);\s*\$count = mysqli_num_rows\(\$result\);/'
            => "\$count = $L\\LayoutData::noticeCount();",
        '/\$sql = "SELECT COUNT\(id\) as c FROM `chat` WHERE is_read_admin = \'0\' ";\s*\$result = mysqlquerys\(\$sql\);\s*\$data = mysqli_fetch_array\(\$result\);/'
            => "\$data = ['c' => $L\\LayoutData::chatUnreadAdmin()];",
        '/\$sql = "SELECT COUNT\(id\) as c FROM `chat` WHERE is_read = \'0\' AND `to` = \'"\.getUserID\(\)\."\' ";\s*\$result = mysqlquerys\(\$sql\);\s*\$data = mysqli_fetch_array\(\$result\);/'
            => "\$data = ['c' => $L\\LayoutData::chatUnreadUser()];",
        '/\$sql = "SELECT COUNT\(id\) as count FROM goodsconfirm WHERE status = 0 ";\s*\$result = mysqlquerys\(\$sql\);\s*\$data = mysqli_fetch_array\(\$result\);/'
            => "\$data = ['count' => $L\\LayoutData::goodsconfirmPending()];",
    ];
    $s = preg_replace(array_keys($sql), array_values($sql), $s);

    // ตัวแปร/ฟังก์ชันที่เหลือในบล็อก php (control flow ของเดิม)
    $s = str_replace(
        ["\$_GET['page']", '$detect->isMobile()', 'getPermission(', 'getSetting(', '$_COOKIE[cookiename_admin_menu]', 'href.'],
        ['$page', '$isMobile', 'perm(', "$L\\Lookup::getSetting(", "$L\\Legacy::rawCookie('admin_menu')", 'legacy_href().'],
        $s
    );
    $s = preg_replace('/\$resource\[[\'"]([a-zA-Z0-9_]+)[\'"]\]/', "res('$1')", $s);

    return $s;
}

$jobs = [
    'index.php' => 'layout.blade.php',
    'login.php' => 'login.blade.php',
    'menu.php' => 'partials/menu.blade.php',
    'menu_mobile.php' => 'partials/menu_mobile.blade.php',
    'assets/validate/validationengine.php' => 'partials/validationengine.blade.php',
];
foreach ($jobs as $from => $to) {
    $s = file_get_contents("$src/$from");
    if ($from === 'index.php' || $from === 'login.php') {
        // ส่วนหัวก่อน <!DOCTYPE (ตรวจ login/ภาษา) ย้ายไป middleware + LayoutComposer แล้ว
        $s = substr($s, strpos($s, '<!DOCTYPE')); // PACK login.php ใช้ <!DOCTYPE html PUBLIC ...
    }
    $s = convert($s);
    $head = "{{-- สร้างจาก $from ของเว็บเดิมด้วย tools/port-layout.php — แก้ได้ แต่ให้เทียบหน้าตากับเดิมทุกครั้ง --}}\n";
    file_put_contents("$dst/$to", $head.$s);
    echo "$from -> resources/views/legacy/$to\n";
}
