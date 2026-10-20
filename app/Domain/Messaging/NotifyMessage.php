<?php

namespace App\Domain\Messaging;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * notify_message($params, $token) ของ SR (system_status_function.php) — ส่งรูปเข้า LINE Notify
 * (jl คอมเมนต์ตัวฟังก์ชันทิ้งทั้งหมด, SR ยังทำงาน: POST notify-api.line.me/api/notify
 *  message = "\n\t\t", imageFullsize = imageThumbnail = $params['imageFullsize'], Authorization: Bearer $token)
 *
 * เดิมไม่มีโค้ดไหนเรียก (จุดเดียวที่เรียกใน sendLineImage() ถูกคอมเมนต์ไว้) และ LINE Notify ปิดบริการแล้ว (31 มี.ค. 2025)
 * พอร์ตไว้ให้ครบตามเดิม: dev (legacy.external.enabled = false) ไม่ยิงออกนอกเครื่องเด็ดขาด
 * เดิม file_get_contents() ไม่สนผลลัพธ์/ข้อผิดพลาด → ที่นี่ก็ไม่คืนค่า (ลง log เมื่อพัง)
 */
final class NotifyMessage
{
    public const URL = 'https://notify-api.line.me/api/notify';

    /** ข้อมูลที่เดิมส่ง (แยกไว้ให้ test ตรวจได้โดยไม่ยิงจริง) */
    public static function payload(array $params): array
    {
        return [
            'message' => "\n\t\t",
            'imageFullsize' => $params['imageFullsize'] ?? null,
            'imageThumbnail' => $params['imageFullsize'] ?? null,
        ];
    }

    public static function send(array $params, string $token): void
    {
        if (! config('legacy.external.enabled')) {
            return;
        }
        try {
            Http::asForm()->withToken($token)->timeout(10)->post(self::URL, self::payload($params));
        } catch (\Throwable $e) {
            Log::warning('notify_message failed', ['error' => $e->getMessage()]);
        }
    }
}
