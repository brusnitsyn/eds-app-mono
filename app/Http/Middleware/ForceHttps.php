<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Принудительное использование HTTPS вне локальной среды.
 *
 * Мера ФСТЭК: ИАФ.5, ЗИС.9 — защита данных при передаче (TLS обязателен).
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isSecure() && ! app()->environment('local', 'testing')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
