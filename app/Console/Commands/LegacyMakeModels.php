<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * สร้าง Eloquent model ครบทุกตารางจาก information_schema
 *  - app/Models/Base/<Class>Base.php  = สร้างใหม่ทุกครั้ง (ห้ามแก้มือ)
 *  - app/Models/<Class>.php           = สร้างครั้งแรกครั้งเดียว โมดูลเพิ่ม relation/scope ที่นี่ได้
 */
class LegacyMakeModels extends Command
{
    protected $signature = 'legacy:make-models {--dry-run : แสดงรายการเฉยๆ ไม่เขียนไฟล์}';

    protected $description = 'สร้าง model ทุกตารางของ DB เดิมจาก information_schema';

    /** ชื่อ class ที่ตั้งเองเพราะชื่อตารางชนหรือสื่อความผิด */
    private const CLASS_OVERRIDES = [
        '_user' => 'UnderscoreUser',
        'log' => 'LogRecord',
        'sessions' => 'PhpSession',
        'sessions_v2' => 'SessionV2',
    ];

    /** base class พิเศษ */
    private const PARENT_OVERRIDES = [
        'user' => \Illuminate\Foundation\Auth\User::class,
    ];

    public function handle(): int
    {
        $db = DB::connection()->getDatabaseName();
        $tables = DB::select('SELECT table_name AS t, table_comment AS c FROM information_schema.tables WHERE table_schema = ? AND table_type = "BASE TABLE" ORDER BY table_name', [$db]);
        $baseDir = app_path('Models/Base');
        if (! $this->option('dry-run') && ! is_dir($baseDir)) {
            mkdir($baseDir, 0750, true);
        }

        $rows = [];
        foreach ($tables as $t) {
            $table = $t->t;
            $cols = DB::select('SELECT column_name AS name, data_type AS type, column_type AS ctype, is_nullable AS nullable,
                    column_default AS def, column_key AS ckey, extra AS extra
                FROM information_schema.columns WHERE table_schema = ? AND table_name = ? ORDER BY ordinal_position', [$db, $table]);
            $class = self::CLASS_OVERRIDES[$table] ?? Str::studly($table);
            $pkCols = array_values(array_filter($cols, fn ($c) => $c->ckey === 'PRI'));
            $pk = count($pkCols) === 1 ? $pkCols[0] : null;
            $rows[] = [$table, $class, $pk?->name ?? '(none)', count($cols)];
            if ($this->option('dry-run')) {
                continue;
            }
            file_put_contents("$baseDir/{$class}Base.php", $this->baseFile($table, $class, $cols, $pk));
            $model = app_path("Models/$class.php");
            if (! file_exists($model)) {
                file_put_contents($model, $this->modelFile($table, $class));
            }
        }
        $this->table(['table', 'class', 'pk', 'columns'], $rows);
        $this->info(count($rows).' ตาราง');

        return self::SUCCESS;
    }

    private function baseFile(string $table, string $class, array $cols, ?object $pk): string
    {
        $parent = self::PARENT_OVERRIDES[$table] ?? \Illuminate\Database\Eloquent\Model::class;
        $parentShort = class_basename($parent);
        $parentAlias = $parentShort === 'User' ? 'Authenticatable' : $parentShort;
        $use = $parentShort === 'User' ? "use $parent as Authenticatable;" : "use $parent;";

        $props = [];
        $casts = [];
        $columns = [];
        foreach ($cols as $c) {
            $columns[] = $c->name;
            $phpType = match (true) {
                in_array($c->type, ['int', 'tinyint', 'smallint', 'mediumint', 'bigint'], true) => 'int',
                in_array($c->type, ['decimal', 'float', 'double'], true) => 'string',
                in_array($c->type, ['date', 'datetime', 'timestamp'], true) => '\\Carbon\\CarbonImmutable',
                default => 'string',
            };
            $nullable = $c->nullable === 'YES' || $phpType === '\\Carbon\\CarbonImmutable' ? '|null' : '';
            $props[] = " * @property {$phpType}{$nullable} \${$c->name} {$c->ctype}".($c->def !== null ? " default '{$c->def}'" : '');
            if (in_array($c->type, ['date', 'datetime', 'timestamp'], true)) {
                // คอลัมน์ที่ค่าว่างของระบบเดิมคือ 0000-00-00: null ที่เขียนลงไปจะกลายเป็นค่าศูนย์แบบเดิม
                $zero = $c->nullable === 'NO' || str_starts_with((string) $c->def, '0000-00-00');
                $fmt = $c->type === 'date' ? 'date' : 'datetime';
                $casts[$c->name] = "LegacyDateTime::class.':{$fmt}".($zero ? ',zero' : '')."'";
            }
        }
        $pkName = $pk?->name;
        $incrementing = $pk && str_contains((string) $pk->extra, 'auto_increment');
        $keyType = $pk && in_array($pk->type, ['int', 'tinyint', 'smallint', 'mediumint', 'bigint'], true) ? 'int' : 'string';

        $castLines = '';
        foreach ($casts as $k => $v) {
            $castLines .= "            '$k' => $v,\n";
        }
        $colList = "'".implode("', '", $columns)."'";
        $guarded = $incrementing ? "['$pkName']" : '[]';
        $pkLine = $pkName ? "    protected \$primaryKey = '$pkName';" : "    protected \$primaryKey = null; // ไม่มี primary key เดี่ยว";
        $propBlock = implode("\n", $props);

        return <<<PHP
<?php

// สร้างอัตโนมัติด้วย `php8.4 artisan legacy:make-models` จาก information_schema — ห้ามแก้ไฟล์นี้ด้วยมือ
// ต้องการเพิ่ม relation/scope/accessor ให้แก้ที่ app/Models/{$class}.php

namespace App\Models\Base;

use App\Casts\LegacyDateTime;
$use

/**
 * ตาราง `{$table}` ของระบบเดิม
 *
$propBlock
 */
abstract class {$class}Base extends {$parentAlias}
{
    protected \$table = '$table';

$pkLine

    public \$incrementing = {$this->bool($incrementing)};

    protected \$keyType = '$keyType';

    public \$timestamps = false;

    protected \$guarded = $guarded;

    /** คอลัมน์ทั้งหมดตามลำดับในตาราง (ใช้ตรวจ parity ตอนเขียน DB) */
    public const COLUMNS = [$colList];

    protected function casts(): array
    {
        return [
$castLines        ];
    }
}

PHP;
    }

    private function modelFile(string $table, string $class): string
    {
        return <<<PHP
<?php

namespace App\Models;

use App\Models\Base\\{$class}Base;

/**
 * ตาราง `{$table}` — เพิ่ม relation / scope ของโมดูลได้ที่นี่ (คอลัมน์/ค่าพื้นฐานอยู่ใน Base ห้ามแก้)
 */
class {$class} extends {$class}Base
{
}

PHP;
    }

    private function bool(bool $b): string
    {
        return $b ? 'true' : 'false';
    }
}
