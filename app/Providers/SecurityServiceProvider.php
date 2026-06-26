<?php

namespace App\Providers;

use App\Auth\EncryptedEloquentUserProvider;
use App\Models\Staff;
use App\Policies\StaffPolicy;
use App\Services\Audit\AuditService;
use App\Services\Crypto\GostCipher;
use App\Services\Crypto\LaravelAesCipher;
use App\Services\Crypto\PdnCipher;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрация мер защиты информации (ФСТЭК №21).
 *
 * Связывает реализацию криптографического драйвера, сервис аудита и политики
 * доступа к ПДн сотрудников. Подписка на события аутентификации регистрируется
 * отдельно через App\Providers\EventServiceProvider.
 */
class SecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Драйвер шифрования ПДн (ЗНИ): AES Laravel или ГОСТ-заглушка.
        $this->app->singleton(PdnCipher::class, function ($app): PdnCipher {
            $driver = (string) config('security.encryption.driver', 'laravel');

            return match ($driver) {
                'gost' => new GostCipher([
                    'binary' => config('security.encryption.gost.binary'),
                    'container' => config('security.encryption.gost.container'),
                ]),
                default => new LaravelAesCipher($app->make(Encrypter::class)),
            };
        });

        $this->app->singleton(AuditService::class);
    }

    public function boot(): void
    {
        // Разграничение доступа к ПДн сотрудников (УПД.2).
        Gate::policy(Staff::class, StaffPolicy::class);

        // login/email зашифрованы — поиск по встроенному сбросу пароля Laravel
        // (PasswordBroker::retrieveByCredentials) должен идти через блайнд-индекс.
        Auth::provider('eloquent-encrypted', function ($app, array $config) {
            return new EncryptedEloquentUserProvider($app['hash'], $config['model']);
        });
    }
}
