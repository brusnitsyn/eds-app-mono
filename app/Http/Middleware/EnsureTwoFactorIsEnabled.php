<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Обязательность многофакторной аутентификации (мера ИАФ.4).
 */
class EnsureTwoFactorIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! config('security.mfa.required') || $user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        if ($request->routeIs('profile.show', 'two-factor.*', 'logout')) {
            return $next($request);
        }

        return redirect()->route('profile.show')
            ->with('status', 'Для продолжения работы необходимо настроить двухфакторную аутентификацию.');
    }
}
