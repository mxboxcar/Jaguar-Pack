<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /download_file?path=<url>&filename=<ชื่อ> — download_file.php เดิม (ลิงก์ไฟล์แนบใน _admin/_lot/form.php, grid.php)
 *
 * เดิม: ไม่ต้องล็อกอิน, file_get_contents(urldecode($path)) ของ URL/ไฟล์อะไรก็ได้ (SSRF + อ่านไฟล์ในเครื่อง)
 *       แล้ว file_put_contents("upload/temp/".$filename) (path traversal → เขียนไฟล์ .php ลง webroot = RCE) แล้ว redirect ไปที่ไฟล์นั้น
 * ใหม่: ต้องมีสิทธิ์ admin_lot หรือ admin_lot_view (หน้าที่มีลิงก์นี้), path ต้องอยู่ใต้ LEGACY_HREF_BLOB เท่านั้น, ไม่เขียนไฟล์ลงดิสก์
 *       - LEGACY_EXTERNAL_ENABLED=false (dev): ไม่ดึงไฟล์เอง → redirect ไป URL ของ blob (เบราว์เซอร์โหลดเอง)
 *       - เปิด: ดึงจาก blob (timeout 20 วินาที) แล้วส่งเป็น attachment ชื่อ filename
 */
class DownloadFileController extends Controller
{
    use ReadsLegacyInput;

    public function __invoke(Request $request): Response
    {
        $path = urldecode($this->get($request, 'path'));
        $filename = basename(str_replace('\\', '/', $this->get($request, 'filename')));
        $blob = Legacy::hrefBlob();
        if ($blob === '' || ! str_starts_with($path, $blob) || str_contains(substr($path, strlen($blob)), '..')
            || preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            return response("<meta charset=\"utf-8\">\n", 404);
        }
        if (! config('legacy.external.enabled')) {
            return redirect()->away($path);
        }
        try {
            $res = Http::timeout(20)->withOptions(['allow_redirects' => false])->get($path);
        } catch (\Throwable) {
            return response("<meta charset=\"utf-8\">\n", 502);
        }
        if (! $res->successful()) {
            return response("<meta charset=\"utf-8\">\n", 404);
        }
        $name = $filename !== '' && $filename !== '.' ? $filename : basename(parse_url($path, PHP_URL_PATH) ?: 'file');

        return response($res->body(), 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.addcslashes(preg_replace('/[^\x20-\x7E]/', '_', $name) ?? 'file', '"\\').'"; filename*=UTF-8\'\''.rawurlencode($name),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
