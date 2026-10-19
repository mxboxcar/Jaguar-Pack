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
    /** view (ชื่อไฟล์เดิม) => URL ใหม่ */
    public const URLS = [
        'index' => '/',
        'how_to' => '/how-to',
        'how_to_order' => '/how-to/order',
        'how_to_payment' => '/how-to/payment',
        'how_to_alipay' => '/how-to/alipay',
        'how_to_profile' => '/how-to/profile',
        'how_to_refill' => '/how-to/refill',
        'how_to_tracking' => '/how-to/tracking',
        'how_to_transfer' => '/how-to/transfer',
        'page_02' => '/contact',
        'page_03' => '/terms',
    ];

    public const PAGES = ['index', 'how_to', 'how_to_order', 'how_to_payment', 'how_to_alipay', 'how_to_profile',
        'how_to_refill', 'how_to_tracking', 'how_to_transfer', 'page_02', 'page_03'];

    public function show(Request $request, string $page = 'index'): Response
    {
        if (! array_key_exists($page, self::URLS)) {
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

    /** URL เดิม (/how_to_order.php, /page_02.php, ...) → 301 ไป URL ใหม่ บน www. (เก็บ query string) */
    public function legacyRedirect(Request $request, string $page): Response
    {
        $host = $request->getHost();
        $host = str_starts_with($host, 'www.') ? $host : 'www.'.$host;
        $qs = $request->getQueryString();

        return redirect()->away($request->getScheme().'://'.$host.self::URLS[$page].($qs !== null ? '?'.$qs : ''), 301);
    }
}
