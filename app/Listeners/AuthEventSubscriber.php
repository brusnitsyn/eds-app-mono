<?php

namespace App\Listeners;

use App\Facades\Audit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;

/**
 * Регистрация событий аутентификации в журнале безопасности.
 *
 * Меры ФСТЭК: РСБ.2 (вход/выход, ошибки аутентификации), ИАФ.6 (блокировка).
 */
class AuthEventSubscriber
{
    public function handleLogin(Login $event): void
    {
        Audit::log('auth.login.success', 'login', $this->resource($event->user));
    }

    public function handleLogout(Logout $event): void
    {
        Audit::log('auth.logout', 'logout', $this->resource($event->user));
    }

    public function handleFailed(Failed $event): void
    {
        // В журнал пишем только введённый логин, без пароля.
        Audit::log(
            eventType: 'auth.login.failed',
            action: 'login',
            resource: null,
            result: 'failure',
            details: ['login' => $event->credentials['login'] ?? null],
        );
    }

    public function handleLockout(Lockout $event): void
    {
        Audit::log(
            eventType: 'auth.locked',
            action: 'lockout',
            result: 'failure',
            details: ['login' => $event->request->input('login')],
        );
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        Audit::log('auth.password.changed', 'password_reset', $this->resource($event->user));
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }

    private function resource(mixed $user): ?string
    {
        return $user ? 'User:'.$user->getAuthIdentifier() : null;
    }
}
