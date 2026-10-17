<?php

namespace App\Support\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

/**
 * session ของเว็บเดิม: cookie PHPSESSID + ตาราง sessions_v2 (programster/mysql-session-handler)
 * เก็บ $_SESSION['user_id'] = encrypt(username.'&'.id), $_SESSION['user_role'] = encrypt(password)
 *
 * ผูกกับ request เดียว (singleton ต่อ request) — middleware LegacySessionMiddleware เป็นคน start/save
 * โมดูลไม่ต้องเรียกตรง ให้ใช้ Auth / Legacy::userId() แทน
 */
final class LegacySession
{
    private ?string $id = null;

    /** @var array<string, mixed> */
    private array $data = [];

    private bool $started = false;

    private bool $dirty = false;

    private bool $sendCookie = false;

    public function start(Request $request): void
    {
        if ($this->started) {
            return;
        }
        $this->started = true;
        $sid = (string) $request->cookies->get($this->cookieName(), '');
        if (! self::validId($sid)) {
            return;
        }
        $this->id = $sid;
        $this->sendCookie = true; // เดิม setcookie ต่ออายุทุก request ที่มี session

        $this->gc();
        $row = DB::table($this->table())
            ->where('id', $sid)
            ->whereRaw('`modified_timestamp` >= NOW() - INTERVAL ? SECOND', [$this->maxAge()])
            ->first(['data']);
        $this->data = $row ? PhpSessionCodec::decode($row->data) : [];

        // ต่ออายุแบบเดิม: อย่างมากครั้งเดียวต่อ 5 นาที
        if ($row) {
            DB::update(
                'UPDATE `'.$this->table().'` SET `modified_timestamp` = NOW() WHERE `id` = ? AND `modified_timestamp` < NOW() - INTERVAL ? SECOND',
                [$sid, (int) config('legacy.session.touch_every', 300)]
            );
        }
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
        $this->dirty = true;
    }

    public function forget(string $key): void
    {
        unset($this->data[$key]);
        $this->dirty = true;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->data;
    }

    /** ออก id ใหม่ (ใช้ตอนล็อกอิน กัน session fixation — เว็บเดิมไม่ได้ทำ แต่อ่าน id ใหม่ได้ตามปกติ) */
    public function regenerate(): void
    {
        if ($this->id !== null) {
            DB::table($this->table())->where('id', $this->id)->delete();
        }
        $this->id = self::newId();
        $this->sendCookie = true;
        $this->dirty = true;
    }

    /** = session_destroy() เดิม: ลบแถว แต่ cookie ยังอยู่ (เว็บเดิมก็ไม่ลบ cookie) */
    public function destroy(): void
    {
        if ($this->id !== null) {
            DB::table($this->table())->where('id', $this->id)->delete();
        }
        $this->data = [];
        $this->dirty = false;
    }

    /** เขียนกลับแบบ SessionHandler::write เดิม: REPLACE INTO เฉพาะตอนข้อมูลไม่ว่าง */
    public function save(): void
    {
        if (! $this->dirty || $this->id === null) {
            return;
        }
        $this->dirty = false;
        $payload = PhpSessionCodec::encode($this->data);
        if ($payload === '') {
            return;
        }
        DB::statement('REPLACE INTO `'.$this->table().'` (id, data) VALUES (?, ?)', [$this->id, $payload]);
    }

    /** cookie ที่ต้องส่งกลับ (path /, อายุ 7 วัน, ไม่ httponly — ตรงกับ php.ini เดิม) */
    public function cookie(): ?\Symfony\Component\HttpFoundation\Cookie
    {
        if (! $this->sendCookie || $this->id === null) {
            return null;
        }

        return Cookie::make(
            $this->cookieName(), $this->id, (int) ceil($this->maxAge() / 60),
            '/', null, null, false, false, null
        );
    }

    public static function validId(string $sid): bool
    {
        // PHP ยอมรับ [a-zA-Z0-9,-] และคอลัมน์ id ยาว 32
        return $sid !== '' && strlen($sid) <= 32 && preg_match('/^[a-zA-Z0-9,-]+$/', $sid) === 1;
    }

    /** รูปแบบเดียวกับ PHP: sid_length=26, sid_bits_per_character=5 (0-9a-v) */
    public static function newId(): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuv';
        $bytes = random_bytes(26);
        $id = '';
        for ($i = 0; $i < 26; $i++) {
            $id .= $chars[ord($bytes[$i]) & 31];
        }

        return $id;
    }

    private function gc(): void
    {
        [$hit, $of] = config('legacy.session.gc_lottery', [2, 100]);
        if (random_int(1, $of) <= $hit) {
            DB::delete(
                'DELETE FROM `'.$this->table().'` WHERE `modified_timestamp` < (NOW() - INTERVAL ? SECOND)',
                [$this->maxAge()]
            );
        }
    }

    private function table(): string
    {
        return (string) config('legacy.session.table', 'sessions_v2');
    }

    private function cookieName(): string
    {
        return (string) config('legacy.session.cookie', 'PHPSESSID');
    }

    private function maxAge(): int
    {
        return (int) config('legacy.session.max_age', 604800);
    }
}
