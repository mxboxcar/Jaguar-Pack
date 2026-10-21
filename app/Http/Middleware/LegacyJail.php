<?php

namespace App\Http\Middleware;

use App\Support\Legacy\Jail;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** legacy.jail — check_jail_ip_v3() เดิม (ทุก request) */
class LegacyJail
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('legacy.jail.enabled') && Jail::check()) {
            return response(Jail::forbiddenBody(), 403);
        }

        return $next($request);
    }
}
