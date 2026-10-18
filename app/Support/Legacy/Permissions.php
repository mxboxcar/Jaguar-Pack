<?php

namespace App\Support\Legacy;

use Illuminate\Support\Facades\DB;

/**
 * getPermission($module) เดิม (system_config.php):
 *   role.id ← user.role (LEFT JOIN role)  → role_on_detail (role_id, is_delete = 0)
 *   JOIN role_detail.role_detail_name = $module → แถวแรก permission == 1 → true
 *
 * เดิมยิง 2 query ต่อการเรียก 1 ครั้ง (เมนูเรียก ~60 ครั้งต่อหน้า) — ที่นี่โหลดทั้ง role ครั้งเดียวต่อ request
 * ผลเท่าเดิม: ใช้ค่าแถวแรกต่อชื่อโมดูล (เรียงตาม role_on_detail.id เหมือนลำดับที่ MySQL คืนแถวเดิม)
 * ผูกเป็น scoped singleton (ล้างทุก request)
 */
final class Permissions
{
    /** @var array<string, bool>|null */
    private ?array $map = null;

    private ?int $forUser = null;

    public function has(string $module): bool
    {
        return $this->load()[self::key($module)] ?? false;
    }

    /** true ถ้ามีสิทธิ์อย่างน้อย 1 โมดูล (เหมือน getPermission(a) || getPermission(b) ...) */
    public function any(string ...$modules): bool
    {
        foreach ($modules as $m) {
            if ($this->has($m)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, bool> */
    public function all(): array
    {
        return $this->load();
    }

    /** collation utf8_general_ci เดิม: ไม่สนตัวพิมพ์และช่องว่างท้าย */
    private static function key(string $name): string
    {
        return strtolower(rtrim($name, ' '));
    }

    /** @return array<string, bool> */
    private function load(): array
    {
        $uid = Legacy::userId();
        if ($this->map !== null && $this->forUser === $uid) {
            return $this->map;
        }
        $this->forUser = $uid;
        $this->map = [];
        if ($uid === null) {
            return $this->map;
        }
        $roleId = DB::table('user')
            ->leftJoin('role', 'role.id', '=', 'user.role')
            ->where('user.id', $uid)
            ->value('role.id');
        if ($roleId === null || $roleId === '') {
            return $this->map;
        }
        $rows = DB::table('role_on_detail')
            ->leftJoin('role_detail', 'role_detail.id', '=', 'role_on_detail.role_detail_id')
            ->where('role_on_detail.role_id', $roleId)
            ->where('role_on_detail.is_delete', 0)
            ->orderBy('role_on_detail.id')
            ->get(['role_detail.role_detail_name as name', 'role_on_detail.permission']);

        return $this->map = self::build($rows);
    }

    /**
     * แถว role_on_detail ของ role (เรียงตาม id) → map สิทธิ์ (+ fallback ของสิทธิ์กำพร้า)
     *
     * @param  iterable<object{name: ?string, permission: mixed}>  $rows
     * @return array<string, bool>
     */
    public static function build(iterable $rows): array
    {
        $map = [];
        foreach ($rows as $r) {
            if ($r->name === null || array_key_exists(self::key($r->name), $map)) {
                continue; // แถวแรกเท่านั้น (mysqli_fetch_array ครั้งเดียว)
            }
            $map[self::key($r->name)] = (string) $r->permission === '1';
        }

        return self::applyFallbacks($map);
    }

    /**
     * config legacy.permission_fallbacks: สิทธิ์ที่ไม่มี role ใดถือ → ให้ผู้ที่ถือสิทธิ์ต้นทาง (ต่างจากเว็บเดิมโดยตั้งใจ)
     * ชั้นเดียว: ตรวจต้นทางจากสิทธิ์ใน DB เท่านั้น ไม่ใช่สิทธิ์ที่ได้จาก fallback ตัวอื่น · เพิ่มอย่างเดียว ไม่ถอนสิทธิ์ที่ DB ให้
     *
     * @param  array<string, bool>  $held
     * @return array<string, bool>
     */
    public static function applyFallbacks(array $held): array
    {
        if (! config('legacy.permission_fallbacks_enabled', true)) {
            return $held;
        }
        $out = $held;
        foreach ((array) config('legacy.permission_fallbacks', []) as $orphan => $sources) {
            $k = self::key((string) $orphan);
            if ($out[$k] ?? false) {
                continue;
            }
            foreach ((array) $sources as $src) {
                if ($held[self::key((string) $src)] ?? false) {
                    $out[$k] = true;
                    break;
                }
            }
        }

        return $out;
    }
}
