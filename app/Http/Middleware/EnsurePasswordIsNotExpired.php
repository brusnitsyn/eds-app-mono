<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Принудительная смена пароля по истечении срока действия (мера ИАФ.3).
 */
class EnsurePasswordIsNotExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->passwordExpired()) {
            return $next($request);
        }

        if ($request->routeIs('profile.show', 'user-password.update', 'logout')) {
            return $next($request);
        }

        return redirect()->route('profile.show')
            ->with('status', 'Срок действия пароля истёк, необходимо установить новый пароль.');
    }
}
