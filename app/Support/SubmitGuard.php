<?php

namespace App\Support;

use App\Support\Legacy\Alerts;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * กันกดซ้ำ/ส่งซ้อน (double submit) ของ endpoint ที่ "สร้างรายการใหม่" และ/หรือตัด/เติมเงินตอนสร้าง
 * เว็บเดิมไม่มีอะไรกันเลย: ดับเบิลคลิก = 2 รายการ + ตัดเงิน 2 ครั้ง (เจ้าของอนุมัติให้แก้ 2026-10-18)
 *
 * สองชั้น (ใช้ผ่าน middleware legacy.once ใน routes/modules/*.php):
 *  1) lock ต่อ "ผู้ใช้ + route" (Cache::lock — store file/array/redis รองรับ atomic lock) ระหว่างประมวลผลคำขอ
 *     → คำขอที่ซ้อนเข้ามาระหว่างคำขอแรกยังทำงาน ถูกปฏิเสธทันทีด้วย ErrorBox (ไม่รอ ไม่ทำซ้ำ)
 *  2) หน้าต่างกันส่งซ้ำ WINDOW_SECONDS วินาที: payload เดิม (hash ของ POST + ไฟล์) ของผู้ใช้+route เดิมที่ "สำเร็จ" ไปแล้ว
 *     → ปฏิเสธ (กดซ้ำหลังคำขอแรกเสร็จพอดี) — ไม่จำคำขอที่ตอบ error/กล่อง PIN จึงแก้ข้อมูลแล้วส่งใหม่ได้ทันที
 *
 * ชั้นนี้กันรายการซ้ำเท่านั้น — การกันยอดติดลบเมื่อคำขอ "ต่างกัน" มาพร้อมกัน ต้องทำใน controller เสมอ:
 * DB::transaction + ล็อกแถว user (Money::lockUser) แล้วตรวจยอดภายใน transaction
 * cache ใช้ไม่ได้ (เช่นสิทธิ์ไฟล์) → ปล่อยผ่าน + log (ไม่ล็อกเงินลูกค้าทั้งระบบ; ชั้น DB ยังกันยอดติดลบอยู่)
 */
final class SubmitGuard
{
    /** อายุสูงสุดของ lock (กัน lock ค้างถ้า worker ตาย) — คำขอปกติปลดเองตอนจบ */
    public const LOCK_SECONDS = 60;

    public const WINDOW_SECONDS = 8;

    public const BUSY = 'รายการนี้กำลังดำเนินการ กรุณารอสักครู่';

    public const DUPLICATE = 'รายการนี้ถูกส่งไปแล้ว (ส่งซ้ำ) กรุณารอสักครู่แล้วตรวจสอบรายการอีกครั้ง';

    /**
     * @param  string  $action  ชื่อ endpoint (uri ของ route เช่น "_taobao/save")
     * @param  bool  $window  false = ใช้แค่ lock (endpoint ที่ส่ง payload เดิมซ้ำได้โดยตั้งใจ เช่น "เพิ่มแถวว่าง")
     * @param  Closure(string): Response  $refuse  สร้างคำตอบปฏิเสธจากข้อความ
     * @param  Closure(): Response  $next
     */
    public static function run(int $uid, string $action, string $hash, bool $window, Closure $refuse, Closure $next): Response
    {
        $base = 'submit:'.$uid.':'.$action;
        try {
            $lock = Cache::lock($base, self::LOCK_SECONDS);
            $got = $lock->get();
        } catch (\Throwable $e) {
            Log::warning('SubmitGuard: cache lock unavailable, request not guarded', ['action' => $action, 'error' => $e->getMessage()]);

            return $next();
        }
        if (! $got) {
            return $refuse(self::BUSY);
        }
        try {
            $dupKey = $base.':'.$hash;
            if ($window && Cache::has($dupKey)) {
                return $refuse(self::DUPLICATE);
            }
            $response = $next();
            if ($window && self::succeeded($response)) {
                Cache::put($dupKey, 1, self::WINDOW_SECONDS);
            }

            return $response;
        } finally {
            try {
                $lock->release();
            } catch (\Throwable) {
                // หมดอายุเอง (LOCK_SECONDS)
            }
        }
    }

    /** hash ของ payload: ค่า POST/GET ทั้งหมด (เรียง key) + ชื่อ/ขนาด/hash ของไฟล์ที่อัปโหลด */
    public static function payloadHash(Request $request): string
    {
        $files = [];
        foreach ($request->allFiles() as $key => $f) {
            foreach (is_array($f) ? Arr::flatten($f) : [$f] as $i => $one) {
                if ($one instanceof UploadedFile) {
                    $path = $one->getRealPath();
                    $files[$key.'#'.$i] = [$one->getClientOriginalName(), $one->getSize(), $path ? @sha1_file($path) : ''];
                }
            }
        }

        return hash('sha256', (string) json_encode([self::sorted($request->request->all()), self::sorted($request->query->all()), $files]));
    }

    /** คำตอบที่นับว่า "สร้างสำเร็จ" (SuccessBox / redirect JS) — ErrorBox, ค่าว่าง, กล่อง PIN ไม่นับ */
    public static function succeeded(Response $response): bool
    {
        if ($response->getStatusCode() >= 300) {
            return false;
        }
        $body = (string) $response->getContent();

        return ! str_contains($body, 'ErrorBox(')
            && (str_contains($body, 'SuccessBox(') || str_contains($body, 'window.location'));
    }

    /** ErrorBox แบบเดิม (ปิด loading แล้วแจ้ง) — $html = ผลที่หน้าเว็บแทรกเป็น HTML (ไม่ได้ eval) */
    public static function refusal(string $text, bool $html = false): string
    {
        $js = Alerts::error('CloseLoading()', $text);

        return $html ? '<script>'.$js.'</script>' : $js;
    }

    private static function sorted(array $a): array
    {
        ksort($a);
        foreach ($a as $k => $v) {
            if (is_array($v)) {
                $a[$k] = self::sorted($v);
            }
        }

        return $a;
    }
}
