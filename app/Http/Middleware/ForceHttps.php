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
        $isDevMode = app()->environment('local', 'testing') || config('app.debug');

        if (! $request->isSecure() && ! $isDevMode) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
