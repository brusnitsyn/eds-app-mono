<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Установка заголовков безопасности HTTP-ответа.
 *
 * Меры ФСТЭК: ЗИС (защита информационной системы), защита от XSS/clickjacking.
 * HSTS добавляется только для HTTPS-запросов (ИАФ.5, ЗИС.9).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // CSP не выставляется в dev: Vite dev-server грузит ассеты с другого
        // origin и использует eval, дефолтный CSP сломал бы `npm run dev`.
        if (! app()->environment('local', 'testing') && ! config('app.debug')) {
            $response->headers->set('Content-Security-Policy', (string) config('security.headers.csp'));
        }

        // Не раскрываем используемое ПО (ОДТ/ЗИС).
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        if ($request->isSecure()) {
            $maxAge = (int) config('security.headers.hsts_max_age');
            $response->headers->set(
                'Strict-Transport-Security',
                "max-age={$maxAge}; includeSubDomains; preload"
            );
        }

        return $response;
    }
}
