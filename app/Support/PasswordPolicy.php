<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Единая парольная политика приложения (мера ИАФ.3).
 *
 * Параметры берутся из config/security.php → password. Использовать во всех
 * Form Request'ах/Action'ах, где задаётся/меняется пароль, чтобы политика
 * была единой.
 */
class PasswordPolicy
{
    public static function rule(): Password
    {
        $cfg = config('security.password');

        $rule = Password::min((int) $cfg['min_length']);

        if ($cfg['require_mixed_case']) {
            $rule->mixedCase();
        }

        if ($cfg['require_numbers']) {
            $rule->numbers();
        }

        if ($cfg['require_symbols']) {
            $rule->symbols();
        }

        if ($cfg['check_compromised']) {
            $rule->uncompromised();
        }

        return $rule;
    }

    /**
     * Количество хранимых в истории паролей (запрет повтора).
     */
    public static function historyLimit(): int
    {
        return (int) config('security.password.history_limit');
    }

    /**
     * Срок действия пароля в днях (0 — без ограничения).
     */
    public static function maxAgeDays(): int
    {
        return (int) config('security.password.max_age_days');
    }
}
