<?php

namespace App\Http\Middleware;

use App\Support\Legacy\Legacy;
use App\Support\Legacy\Permissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * legacy.own:<route param>,<module>[,<module>...] — route ที่รับ id ผู้ใช้ใน URL (เช่น /dev/tracking/{userId})
 * ผ่านได้เมื่อ id นั้นเป็นของคนที่ล็อกอินอยู่ หรือมีสิทธิ์อย่างน้อย 1 โมดูล (กัน IDOR: เปลี่ยนเลขใน URL ดูของคนอื่น)
 * ยังไม่ล็อกอิน → /login
 */
class LegacyOwnUserOrPermission
{
    public function __construct(private Permissions $permissions) {}

    public function handle(Request $request, Closure $next, string $param, string ...$modules): Response
    {
        $me = Legacy::userId();
        if ($me === null) {
            return redirect()->to(Legacy::href('login'));
        }
        if ((string) $request->route($param) === (string) $me || ($modules !== [] && $this->permissions->any(...$modules))) {
            return $next($request);
        }

        return response('Forbidden', 403);
    }
}
