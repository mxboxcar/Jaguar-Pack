<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /download_file?path=<url>&filename=<ชื่อ> — download_file.php (ลิงก์ไฟล์แนบใน _admin/_lot/form.php, grid.php)
 *
 * ก่อน hotfix: ไม่ต้องล็อกอิน, file_get_contents(urldecode($path)) ของ URL/ไฟล์อะไรก็ได้ (SSRF + อ่านไฟล์ในเครื่อง)
 *       แล้ว file_put_contents("upload/".$filename) (SR; jl = upload/temp/) — path traversal → เขียนไฟล์ .php ลง webroot = RCE
 * SR production hotfix 2026-10-18 (= ที่นี่): ต้องมีสิทธิ์ admin_lot หรือ admin_lot_view, path ต้องขึ้นต้นด้วย href_blob และไม่มี '..',
 *       ส่งไฟล์ให้เบราว์เซอร์ตรง ไม่เขียนลงดิสก์ — pdf/รูป = inline ตามชนิด, อื่นๆ = attachment octet-stream, ชื่อไฟล์ = basename(filename) หรือ 'file'
 *       ผลแบบ hotfix: path ผิด = 400 "bad request", ดึงไม่ได้ = 404 "not found"
 *       (ไม่มีสิทธิ์: hotfix ตอบ 403 "forbidden" — ที่นี่ legacy.perm ตอบหน้าแจ้งไม่มีสิทธิ์แบบหน้าอื่น)
 *  - LEGACY_EXTERNAL_ENABLED=false (dev): ไม่ดึงไฟล์เอง → redirect ไป URL ของ blob (เบราว์เซอร์โหลดเอง)
 *  - เปิด (production): ดึงจาก blob (timeout 20 วินาที, ไม่ตาม redirect) แล้วส่งต่อ
 */
class DownloadFileController extends Controller
{
    use ReadsLegacyInput;

    private const INLINE = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];

    public function __invoke(Request $request): Response
    {
        $path = urldecode($this->get($request, 'path'));
        $raw = $request->query('filename');
        $name = basename(str_replace('\\', '/', is_string($raw) ? $this->get($request, 'filename') : 'file'));
        $blob = Legacy::hrefBlob();
        if ($path === '' || $blob === '' || ! str_starts_with($path, $blob) || str_contains($path, '..')
            || preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            return response('bad request', 400);
        }
        if (! config('legacy.external.enabled')) {
            return redirect()->away($path);
        }
        try {
            $res = Http::timeout(20)->withOptions(['allow_redirects' => false])->get($path);
        } catch (\Throwable) {
            return response('not found', 404);
        }
        if (! $res->successful()) {
            return response('not found', 404);
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?? 'file';
        $inline = isset(self::INLINE[$ext]);

        return response($res->body(), 200, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Type' => $inline ? self::INLINE[$ext] : 'application/octet-stream',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$safe.'"; filename*=UTF-8\'\''.rawurlencode($name),
        ]);
    }
}
