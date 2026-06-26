<?php

namespace App\Http\Middleware;

use App\Facades\Audit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Журналирование обращений к защищаемым маршрутам (мера РСБ.2).
 *
 * Назначается группе маршрутов, работающих с ПДн. Фиксирует факт
 * изменяющего обращения (POST/PUT/PATCH/DELETE) и результат по HTTP-коду.
 * Точечный аудит конкретных операций выполняется через App\Facades\Audit
 * непосредственно в контроллерах, где нужен осмысленный resource id.
 */
class AuditLog
{
    /** HTTP-методы, изменяющие состояние и подлежащие регистрации. */
    private const MUTATING = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), self::MUTATING, true)) {
            $status = $response->getStatusCode();

            Audit::log(
                eventType: 'http.request',
                action: $request->method().' '.$request->path(),
                resource: $request->route()?->getName() ?? $request->path(),
                result: $status < 400 ? 'success' : 'failure',
                details: ['status' => $status],
            );
        }

        return $response;
    }
}
