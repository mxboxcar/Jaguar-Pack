<?php

namespace App\Http\Middleware;

use App\Support\Legacy\Legacy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * legacy.auth — ลูกค้าต้องล็อกอิน (index.php เดิม: ไม่มี session → Redirect(href.'login'))
 * ไม่ได้ล็อกอิน: หน้าเว็บ → 302 ไป /login, ajax → 302 เหมือนกัน (เดิม ajax ส่วนใหญ่ไม่ตรวจเลย)
 */
class LegacyAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Legacy::userId() === null) {
            return redirect()->to(Legacy::href('login'));
        }

        return $next($request);
    }
}
