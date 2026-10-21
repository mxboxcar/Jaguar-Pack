<?php
// ใช้: php8.4 tools/role-matrix.php <ไฟล์รายชื่อสิทธิ์ที่โค้ดเดิมตรวจ>  (สร้างรายชื่อ: ดู docs/role-matrix.md ข้อ 3)
// role matrix: legacy getPermission() (SQL เดิม ทีละสิทธิ์) เทียบ Permissions::build() ของ Laravel — อ่าน DB อย่างเดียว (DB ตาม .env ของ repo นี้)
chdir(dirname(__DIR__)); require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$names = array_filter(array_map('trim', file($argv[1])));
foreach (DB::table('role_detail')->pluck('role_detail_name') as $n) $names[] = $n;
$names = array_values(array_unique($names)); sort($names);
$roles = DB::table('role')->orderBy('id')->get(['id', 'role_name']);
$users = DB::table('user')->where('is_delete', 0)->where('username', 'not like', 'zz\\_%')->selectRaw('role, count(*) c')->groupBy('role')->pluck('c', 'role');
$codeNames = array_filter(array_map('trim', file($argv[1])));
echo "| id | ชื่อ | ผู้ใช้ (is_delete=0, ไม่นับ zz) |\n|---|---|---|\n";
foreach ($roles as $r) echo "| {$r->id} | {$r->role_name} | ".($users[$r->id] ?? 0)." |\n";
echo "\n| สิทธิ์ | ".implode(' | ', $roles->pluck('id')->all())." | หมายเหตุ |\n|---|".str_repeat('---|', count($roles))."---|\n";
$mismatch = 0;
$built = [];
foreach ($roles as $r) {
    $rows = DB::table('role_on_detail')->leftJoin('role_detail', 'role_detail.id', '=', 'role_on_detail.role_detail_id')
        ->where('role_on_detail.role_id', $r->id)->where('role_on_detail.is_delete', 0)->orderBy('role_on_detail.id')
        ->get(['role_detail.role_detail_name as name', 'role_on_detail.permission']);
    $built[$r->id] = App\Support\Legacy\Permissions::build($rows);
}
foreach ($names as $n) {
    $cells = [];
    foreach ($roles as $r) {
        // SQL เดิม: role_detail_name = '$module' (collation ไม่สนตัวพิมพ์/ช่องว่างท้าย) AND role_on_detail.is_delete = 0 → แถวแรก
        $old = DB::selectOne("SELECT role_on_detail.permission FROM role_on_detail LEFT JOIN role_detail ON role_detail.id = role_on_detail.role_detail_id WHERE role_on_detail.role_id = ? AND role_detail.role_detail_name = ? AND role_on_detail.is_delete = 0", [$r->id, $n]);
        $o = $old && (string) $old->permission === '1';
        $k = mb_strtolower(rtrim($n));
        $new = $built[$r->id][$k] ?? false;
        $cells[] = $o && $new ? '✔' : (! $o && ! $new ? '·' : ($new ? '**F**' : '✘!'));
        if ($o !== $new) $mismatch++;
    }
    $note = [];
    if (! in_array($n, $codeNames, true)) $note[] = 'โค้ดเดิมไม่ได้ตรวจ';
    if (! DB::table('role_detail')->where('role_detail_name', $n)->exists()) $note[] = 'ไม่มีใน role_detail (ไม่มีใครได้)';
    echo "| `$n` | ".implode(' | ', $cells)." | ".implode(' · ', $note)." |\n";
}
echo "\nไม่ตรงกัน: $mismatch ช่อง\n";
