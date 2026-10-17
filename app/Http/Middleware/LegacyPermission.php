<?php

namespace App\Http\Middleware;

use App\Support\Legacy\Alerts;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Permissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * legacy.perm:admin_tracking[,admin_lot_view,...] — ต้องมีสิทธิ์อย่างน้อย 1 โมดูล (= getPermission(a) || getPermission(b))
 *
 * ไม่มีสิทธิ์ (ตอบ 200 แบบเดิม เพราะ JS เดิม eval() ผลลัพธ์):
 *   ajax (X-Requested-With) → "ErrorBox('ไม่มีสิทธิ์...');"           = getPermissionAlert($module)
 *   หน้าเว็บ                → layout + <script>ErrorBox(...) → dashboard = getPermissionAlert_Page($module)
 * ยังไม่ล็อกอิน → /login
 */
class LegacyPermission
{
    public function __construct(private Permissions $permissions) {}

    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        if (Legacy::userId() === null) {
            return redirect()->to(Legacy::href('login'));
        }
        if ($modules === [] || $this->permissions->any(...$modules)) {
            return $next($request);
        }
        $module = $modules[0];
        if ($request->ajax()) {
            return response(Alerts::permission($module), 200)->header('X-Legacy-Denied', '1');
        }

        return response()->view('legacy.no-permission', ['module' => $module], 200)->header('X-Legacy-Denied', '1');
    }
}
