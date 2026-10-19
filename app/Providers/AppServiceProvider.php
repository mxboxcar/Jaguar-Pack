<?php

namespace App\Providers;

use App\Auth\LegacySessionGuard;
use App\Support\Legacy\LegacyLog;
use App\Support\Legacy\LegacySession;
use App\Support\Legacy\Permissions;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // สถานะต่อ request (scoped = ล้างทุก request / ทุก test)
        $this->app->scoped(LegacySession::class);
        $this->app->scoped(Permissions::class);

        // Mailgun secret จากไฟล์ (MAILGUN_SECRET_FILE) — อ่านเมื่อใช้ mailer ครั้งแรก ไม่เข้า config cache / .env
        $this->app->afterResolving('mail.manager', fn () => self::loadMailgunSecret());
    }

    /** services.mailgun.secret ว่าง + ตั้ง secret_file ที่อ่านได้ → ใส่ค่าจากไฟล์ (trim) */
    public static function loadMailgunSecret(): void
    {
        $file = (string) config('services.mailgun.secret_file');
        if ((string) config('services.mailgun.secret') !== '' || $file === '' || ! is_readable($file)) {
            return;
        }
        $secret = trim((string) @file_get_contents($file));
        if ($secret !== '') {
            config(['services.mailgun.secret' => $secret]);
        }
    }

    public function boot(): void
    {
        // guard "legacy": ผู้ใช้จาก session ของเว็บเดิม
        Auth::extend('legacy', function ($app, string $name, array $config) {
            return new LegacySessionGuard(
                Auth::createUserProvider($config['provider'] ?? 'users'),
                $app->make(LegacySession::class)
            );
        });

        // @perm('admin_tracking', 'admin_lot') ... @endperm  (= getPermission(a) || getPermission(b))
        Blade::if('perm', fn (string ...$modules) => perm(...$modules));

        // ตัวแปรที่ layout เดิมใช้ ($page, $isMobile, $lang, $user) — ทุก view (หน้าโมดูลที่ @extends layout ก็ได้ด้วย)
        View::composer('*', \App\View\LegacyLayoutComposer::class);

        // mysqlquerys() เดิมบันทึกทุกคำสั่งที่ไม่ใช่ SELECT ลง logs/YYYY-MM-DD.log
        if (config('legacy.log_queries')) {
            DB::listen(fn (QueryExecuted $q) => LegacyLog::query($q->sql, $q->bindings));
        }
    }
}
