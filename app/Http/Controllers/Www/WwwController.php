<?php

namespace App\Http\Controllers\Www;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * เว็บหน้าแรก jlcargoth.com / www.jlcargoth.com (เดิม /home/admin/web/jlcargoth.com/public_html/*.php — PHP 7.4 แยก)
 *
 * HTML ของทุกหน้าคัดจากไฟล์เดิมตรงตัว (resources/views/www/*.php) — แทนเฉพาะส่วน PHP:
 *  - ทุกหน้า: host ที่ไม่ขึ้นต้นด้วย www. → 302 ไป www. (เดิมต่อ '/'.REQUEST_URI จึงได้ "//path" — ใหม่ได้ "/path" ปกติ)
 *  - index: เรตแลกเงิน 3 ตัวจากตาราง setting (DB เดียวกับ portal) — เดิมต่อ mysqli ด้วยรหัสที่ฝังในไฟล์ + extract($_GET)
 * asset (css/ js/ img/ image/ master/ XDX/) ยังเสิร์ฟจากโฟลเดอร์เดิมโดย nginx
 */
class WwwController extends Controller
{
    public const PAGES = [
        'index', 'how_to', 'how_to_order', 'how_to_payment', 'how_to_alipay', 'how_to_profile',
        'how_to_refill', 'how_to_tracking', 'how_to_transfer', 'page_02', 'page_03',
    ];

    public function show(Request $request, string $page = 'index'): Response
    {
        if (! in_array($page, self::PAGES, true)) {
            abort(404);
        }

        $host = $request->getHost();
        if (! str_starts_with($host, 'www.')) {
            return redirect()->away($request->getScheme().'://www.'.$host.$request->getRequestUri(), 302);
        }

        $data = [];
        if ($page === 'index') {
            $data['rates'] = DB::table('setting')
                ->whereIn('key', ['rate_order', 'rate_alipay', 'rate_transfer'])
                ->pluck('value', 'key')->map(fn ($v) => (string) $v)->all();
        }

        return response()->view('www.'.$page, $data);
    }
}
