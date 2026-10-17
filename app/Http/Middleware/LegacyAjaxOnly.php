<?php

namespace App\Http\Middleware;

use App\Support\Legacy\Legacy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** legacy.ajax — system_check_ajax.php เดิม: ไม่มี X-Requested-With → ไป /403 */
class LegacyAjaxOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->server->get('HTTP_X_REQUESTED_WITH')) {
            return redirect()->to(Legacy::href('403'));
        }

        return $next($request);
    }
}
