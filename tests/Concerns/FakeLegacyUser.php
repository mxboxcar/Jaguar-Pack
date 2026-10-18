<?php

namespace Tests\Concerns;

use App\Models\User;
use App\Support\Legacy\Permissions;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ผู้ใช้ + สิทธิ์สมมติโดยไม่แตะ DB (php8.4 บนเครื่องนี้ไม่มี pdo_sqlite และ test ห้ามต่อ MySQL)
 *   - guard "legacy" ถูกแทนด้วย guard ในหน่วยความจำ (Legacy::userId()/user() ได้ผู้ใช้ที่กำหนด)
 *   - Permissions ถูกเติม map จาก Permissions::build() ของแถว role_on_detail สมมติ (เส้นทางเดียวกับของจริง รวม fallback)
 */
trait FakeLegacyUser
{
    protected function setUpLegacyWorld(): void
    {
        config(['legacy.log_dir' => '', 'legacy.jail.enabled' => false]);
        Auth::extend('legacy', fn () => new class implements Guard
        {
            use GuardHelpers;

            public function user()
            {
                return $this->user;
            }

            public function validate(array $credentials = [])
            {
                return false;
            }
        });
        Auth::forgetGuards();
    }

    /**
     * เริ่ม "request" ใหม่ที่ล็อกอินเป็นผู้ใช้ id $userId ที่ role ถือสิทธิ์ $granted (permission=1) และ $denied (permission=0)
     *
     * @param  array<string, mixed>  $attrs  คอลัมน์อื่นของ user (เช่น pin)
     */
    protected function actAs(?int $userId, array $granted = [], array $denied = [], array $post = [], string $uri = '/x', string $ip = '10.1.2.3', array $attrs = []): Request
    {
        $this->app->forgetScopedInstances();
        $req = Request::create('http://portal.test'.$uri, 'POST', $post, [], [], [
            'REMOTE_ADDR' => $ip, 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'HTTP_HOST' => 'portal.test',
        ]);
        $this->app->instance('request', $req);

        $guard = Auth::guard('legacy');
        $guard->forgetUser();
        if ($userId === null) {
            return $req;
        }
        $user = new User;
        $user->setRawAttributes($attrs + ['id' => $userId, 'username' => 'zz_test_'.$userId, 'code' => 'ZZT'.$userId,
            'is_delete' => 0, 'is_enable' => 1, 'is_approved' => 1, 'pin' => '', 'password' => ''], true);
        $user->exists = true;
        $guard->setUser($user);

        $rows = [];
        foreach ($granted as $n) {
            $rows[] = (object) ['name' => $n, 'permission' => '1'];
        }
        foreach ($denied as $n) {
            $rows[] = (object) ['name' => $n, 'permission' => '0'];
        }
        $map = Permissions::build($rows);
        $p = $this->app->make(Permissions::class);
        (function () use ($userId, $map) {
            $this->forUser = $userId;
            $this->map = $map;
        })->call($p);

        return $req;
    }
}
