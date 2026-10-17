<?php

namespace App\Auth;

use App\Models\User;
use App\Support\Legacy\LegacyCrypt;
use App\Support\Legacy\LegacySession;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;

/**
 * guard "legacy": ผู้ใช้มาจาก session ของเว็บเดิม (PHPSESSID → sessions_v2) ทุก request
 * ไม่มี session ของ Laravel เกี่ยวข้อง → ล็อกอิน/ออก ที่ฝั่งไหนก็มีผลทั้งสองฝั่งทันที
 *
 * ตรรกะเท่ากับ getUserID() + checkUser() เดิม:
 *  - user_id = encrypt(username&id) → ใช้แค่ id (เดิมไม่ได้เทียบ username)
 *  - user_role ไม่ว่าง และ decrypt(user_role) != user.password → session_destroy (เปลี่ยนรหัสแล้ว session เก่าหลุด)
 * เพิ่มจากเดิม (ตามสเปก): user ถูกลบ/ระงับ/ไม่อนุมัติ (เงื่อนไขเดียวกับตอนล็อกอิน) → ถือว่าไม่ได้ล็อกอิน
 */
class LegacySessionGuard implements StatefulGuard
{
    use GuardHelpers;

    private bool $resolved = false;

    public function __construct(UserProvider $provider, private LegacySession $session)
    {
        $this->provider = $provider;
    }

    public function user(): ?Authenticatable
    {
        if ($this->resolved) {
            return $this->user;
        }
        $this->resolved = true;

        return $this->user = $this->resolveFromSession();
    }

    /** id ที่ได้จาก session แบบ getUserID() เดิม (ไม่ตรวจว่า user ยังใช้งานได้) */
    public function legacyUserId(): ?string
    {
        $enc = (string) $this->session->get('user_id', '');
        if ($enc === '') {
            return null;
        }
        $parts = explode('&', LegacyCrypt::decrypt($enc));

        return $parts[1] ?? null;
    }

    /** decode_member_cookie() เดิม: ['username' => ..., 'id' => ...] (null = ไม่มี session) */
    public function legacyIdentity(): ?array
    {
        $enc = (string) $this->session->get('user_id', '');
        if ($enc === '') {
            return null;
        }
        $parts = explode('&', LegacyCrypt::decrypt($enc));

        return ['username' => $parts[0], 'id' => $parts[1] ?? null];
    }

    private function resolveFromSession(): ?User
    {
        $id = $this->legacyUserId();
        if ($id === null || $id === '') {
            return null;
        }
        /** @var User|null $user */
        $user = User::query()->whereKey((int) $id)->first();

        $role = (string) $this->session->get('user_role', '');
        // เทียบแบบหลวม (!=) เหมือน PHP เดิม
        if ($role !== '' && LegacyCrypt::decrypt($role) != ($user?->password)) {
            $this->session->destroy();

            return null;
        }
        if (! $user || ! $user->isActiveAccount()) {
            return null;
        }

        return $user;
    }

    public function validate(array $credentials = []): bool
    {
        return LegacyLogin::findByCredentials($credentials['username'] ?? '', $credentials['password'] ?? '') !== null;
    }

    public function attempt(array $credentials = [], $remember = false): bool
    {
        $user = LegacyLogin::findByCredentials($credentials['username'] ?? '', $credentials['password'] ?? '');
        if ($user && (int) $user->is_approved !== 0) {
            $this->login($user, $remember);

            return true;
        }

        return false;
    }

    public function once(array $credentials = []): bool
    {
        $user = LegacyLogin::findByCredentials($credentials['username'] ?? '', $credentials['password'] ?? '');
        if ($user) {
            $this->setUser($user);

            return true;
        }

        return false;
    }

    /** เขียน session แบบ system_check_login.php เดิม → เว็บเดิมรู้จักทันที */
    public function login(Authenticatable $user, $remember = false): void
    {
        /** @var User $user */
        $this->session->regenerate();
        $this->session->put('user_id', LegacyCrypt::encrypt($user->username.'&'.$user->id));
        $this->session->put('user_role', LegacyCrypt::encrypt((string) $user->password));
        $this->session->save();
        $this->setUser($user);
        $this->resolved = true;
    }

    public function loginUsingId($id, $remember = false): Authenticatable|false
    {
        $user = User::query()->find($id);
        if (! $user) {
            return false;
        }
        $this->login($user, $remember);

        return $user;
    }

    public function onceUsingId($id): Authenticatable|false
    {
        $user = User::query()->find($id);
        if (! $user) {
            return false;
        }
        $this->setUser($user);

        return $user;
    }

    public function viaRemember(): bool
    {
        return false;
    }

    /** = session_destroy() ใน system_checkuser.php (action=logout) */
    public function logout(): void
    {
        $this->session->destroy();
        $this->user = null;
        $this->resolved = true;
    }
}
