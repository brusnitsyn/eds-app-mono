<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;

/**
 * `email`/`login` хранятся в БД зашифрованными (см. HasPdnEncryption), поэтому
 * стандартный `EloquentUserProvider::retrieveByCredentials()` (`where('email', $value)`)
 * не находит пользователей — используется встроенным сбросом пароля Laravel
 * (`Illuminate\Auth\Passwords\PasswordBroker`). Подменяем `email`/`login` на
 * сравнение с блайнд-индексом перед делегированием родительской реализации.
 */
class EncryptedEloquentUserProvider extends EloquentUserProvider
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        foreach (['email', 'login'] as $exactField) {
            if (array_key_exists($exactField, $credentials) && is_string($credentials[$exactField])) {
                $credentials["{$exactField}_hash"] = User::pdnExactHash($credentials[$exactField]);
                unset($credentials[$exactField]);
            }
        }

        return parent::retrieveByCredentials($credentials);
    }
}
