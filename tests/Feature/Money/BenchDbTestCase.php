<?php

namespace Tests\Feature\Money;

use App\Auth\LegacySessionGuard;
use App\Domain\MoneyMove\Lookups;
use App\Models\User;
use Dotenv\Dotenv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * test เงินที่ต้องรันกับ MySQL จริง (php8.4 บนเครื่องนี้ไม่มี pdo_sqlite) — **opt-in**: JL_BENCH_DB_TESTS=1
 *
 * ความปลอดภัย:
 *  - ต่อเฉพาะ DB ชื่อ admin_jl_bench (อ่านจาก .env; ชื่ออื่น = skip) และตรวจ SELECT DATABASE() อีกชั้น
 *  - ทุก test อยู่ใน transaction เดียวที่ rollBack ตอนจบเสมอ (ตารางที่ใช้เป็น InnoDB ทั้งหมด) → ไม่มีข้อมูลค้างใน bench
 *  - สร้างเฉพาะแถว zz_ ของตัวเอง, แก้/ล็อกเฉพาะแถวที่สร้าง, ไม่มี DDL (DDL = implicit commit)
 *  - Mail::fake() — ไม่ส่งอีเมล; LINE ในโค้ดเป็น no-op อยู่แล้ว
 * เรียก controller ตรง (ไม่ผ่าน HTTP) เพราะ guard legacy อ่าน user จาก session เว็บเดิม — ตั้ง user ให้ guard โดยตรง
 */
abstract class BenchDbTestCase extends TestCase
{
    private bool $inTx = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('JL_BENCH_DB_TESTS') !== '1') {
            $this->markTestSkipped('ตั้ง JL_BENCH_DB_TESTS=1 เพื่อรัน test เงินกับ admin_jl_bench (ทุกอย่าง rollback)');
        }
        $env = Dotenv::parse((string) @file_get_contents(base_path('.env')));
        if (($env['DB_DATABASE'] ?? '') !== 'admin_jl_bench') {
            $this->markTestSkipped('.env ไม่ได้ชี้ admin_jl_bench');
        }
        config(['database.connections.bench' => array_merge(config('database.connections.mysql'), [
            'host' => $env['DB_HOST'] ?? '127.0.0.1', 'port' => $env['DB_PORT'] ?? '3306', 'database' => 'admin_jl_bench',
            'username' => $env['DB_USERNAME'] ?? '', 'password' => $env['DB_PASSWORD'] ?? '', 'unix_socket' => $env['DB_SOCKET'] ?? '',
        ])]);
        config(['database.default' => 'bench']);
        DB::purge('bench');
        $this->assertSame('admin_jl_bench', DB::selectOne('SELECT DATABASE() AS d')->d);
        DB::beginTransaction();
        $this->inTx = true;
        Mail::fake();
        Lookups::flush();
    }

    protected function tearDown(): void
    {
        if ($this->inTx) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::disconnect('bench');
        }
        parent::tearDown();
    }

    /** ผู้ใช้ zz_ ใหม่ (rollback ตอนจบ) */
    protected function zzUser(int $role = 3): User
    {
        $tag = 'zz_mm_'.bin2hex(random_bytes(4));
        $id = DB::table('user')->insertGetId([
            'create_date' => DB::raw('NOW()'), 'create_by' => 0, 'mod_date' => DB::raw('NOW()'), 'mod_by' => 0, 'is_delete' => 0,
            'username' => $tag, 'password' => '', 'code' => strtoupper($tag), 'role' => $role, 'name' => $tag, 'email' => '',
            'is_enable' => 1, 'is_approved' => 1,
        ]);

        return User::query()->findOrFail($id);
    }

    /** ผู้ใช้ปัจจุบันของ guard legacy (แทน session เว็บเดิม) */
    protected function actAs(User $u): void
    {
        $guard = Auth::guard('legacy');
        $this->assertInstanceOf(LegacySessionGuard::class, $guard);
        $guard->user(); // ผูก guard กับ LegacySession (scoped) ปัจจุบันก่อน ไม่งั้น syncSession จะล้าง user ที่ตั้ง
        $guard->setUser($u);
        (new \ReflectionProperty(LegacySessionGuard::class, 'resolved'))->setValue($guard, true);
        $this->assertSame($u->id, \App\Support\Legacy\Legacy::userId());
    }

    /** เรียก action ของ controller ด้วย POST แบบ ajax เดิม แล้วคืนข้อความที่ตอบ */
    protected function act(string $controller, string $method, array $post = []): string
    {
        $req = Request::create('/test', 'POST', $post);
        $req->headers->set('X-Requested-With', 'XMLHttpRequest');
        app()->instance('request', $req);

        return (string) app($controller)->{$method}($req)->getContent();
    }

    protected function insertRow(string $table, array $row): int
    {
        $now = DB::raw('NOW()');

        return (int) DB::table($table)->insertGetId($row + ['create_date' => $now, 'create_by' => 0, 'mod_date' => $now, 'mod_by' => 0, 'is_delete' => 0]);
    }

    /** เงินเข้า/ออกให้ลูกค้าทดสอบ */
    protected function credit(User $u, float $amount, string $table = 'balance'): void
    {
        $this->insertRow($table, ['user' => $u->id, 'amount' => $amount, 'detail' => 'zz_test seed', 'type' => 1, 'prefix' => 0, 'prefix_ref_id' => 0]);
    }

    protected function cash(User $u, string $table = 'balance'): float
    {
        return round((float) DB::table($table)->where('user', $u->id)->where('is_delete', 0)->sum('amount'), 2);
    }

    protected function prefixId(string $name): int
    {
        return (int) DB::table('prefix')->where('name', $name)->value('id');
    }
}
