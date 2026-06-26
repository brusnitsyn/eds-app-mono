<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ограничение доступа к административным разделам по списку IP/подсетей.
 *
 * Мера ФСТЭК: УПД — управление доступом, ЗИС.17. Включается через
 * config('security.ip_whitelist'). Используется для admin-маршрутов.
 */
class IpWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.ip_whitelist.enabled')) {
            return $next($request);
        }

        $ranges = (array) config('security.ip_whitelist.ranges');

        if ($ranges !== [] && ! IpUtils::checkIp($request->ip(), $ranges)) {
            Log::channel(config('audit.siem.channel', 'stack'))->warning('ip_whitelist.denied', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            abort(Response::HTTP_FORBIDDEN, 'Доступ с данного адреса запрещён.');
        }

        return $next($request);
    }
}
