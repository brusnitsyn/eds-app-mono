<?php

/*
|--------------------------------------------------------------------------
| Централизованные настройки безопасности (ФСТЭК №21, УЗ-1 / К1)
|--------------------------------------------------------------------------
|
| Файл собирает все параметры мер защиты в одном месте, чтобы их было
| удобно проверять при аттестации ИСПДн. Значения берутся из .env, чтобы
| настройки можно было менять без правки кода (мера УКФ.1).
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Парольная политика (ИАФ.3, УПД.5)
    |--------------------------------------------------------------------------
    */
    'password' => [
        // Минимальная длина пароля. Для УЗ-1 — не менее 12 символов.
        'min_length' => (int) env('SECURITY_PASSWORD_MIN_LENGTH', 12),

        // Требования к сложности.
        'require_mixed_case' => env('SECURITY_PASSWORD_MIXED_CASE', true),
        'require_numbers' => env('SECURITY_PASSWORD_NUMBERS', true),
        'require_symbols' => env('SECURITY_PASSWORD_SYMBOLS', true),

        // Проверка по базе утёкших паролей (HIBP). В закрытом контуре отключить.
        'check_compromised' => env('SECURITY_PASSWORD_UNCOMPROMISED', false),

        // Запрет повтора последних N паролей (история).
        'history_limit' => (int) env('SECURITY_PASSWORD_HISTORY', 5),

        // Принудительная смена пароля каждые N дней (для УЗ-1 — 90).
        'max_age_days' => (int) env('SECURITY_PASSWORD_MAX_AGE_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Блокировка учётной записи (ИАФ.6, УПД.6)
    |--------------------------------------------------------------------------
    */
    'lockout' => [
        // Количество неудачных попыток до блокировки.
        'max_attempts' => (int) env('SECURITY_LOCKOUT_MAX_ATTEMPTS', 5),

        // Длительность блокировки в минутах.
        'decay_minutes' => (int) env('SECURITY_LOCKOUT_DECAY_MINUTES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Многофакторная аутентификация (ИАФ.4)
    |--------------------------------------------------------------------------
    |
    | По умолчанию выключено (false): в системе уже есть активные пользователи
    | без настроенной MFA, включение по умолчанию заблокировало бы им вход.
    | Включать через SECURITY_MFA_REQUIRED=true после того, как пользователи
    | настроят 2FA в личном кабинете (Profile → Two Factor Authentication).
    |
    */
    'mfa' => [
        'required' => env('SECURITY_MFA_REQUIRED', false),

        // Количество резервных кодов восстановления (используется Fortify).
        'recovery_codes' => (int) env('SECURITY_MFA_RECOVERY_CODES', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Шифрование персональных данных (ЗНИ, ОЦЛ.2)
    |--------------------------------------------------------------------------
    |
    | driver: laravel | gost
    |   laravel — встроенный шифратор Laravel (AES-256-GCM);
    |   gost    — заглушка под СКЗИ КриптоПро CSP (ГОСТ Р 34.12-2015).
    |
    */
    'encryption' => [
        'driver' => env('SECURITY_PDN_CIPHER_DRIVER', 'laravel'),

        // Ключ для псевдонимизации (HMAC хеш-колонок поиска и контрольных сумм).
        // Хранить отдельно от APP_KEY и AUDIT_HMAC_KEY.
        'pseudonym_key' => env('SECURITY_PSEUDONYM_KEY'),

        'gost' => [
            // Параметры подключения к внешнему СКЗИ (КриптоПро CSP и т.п.).
            'binary' => env('SECURITY_GOST_BINARY', '/opt/cprocsp/bin/amd64/csptest'),
            'container' => env('SECURITY_GOST_CONTAINER'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP-заголовки безопасности (ЗИС.*)
    |--------------------------------------------------------------------------
    |
    | CSP учитывает используемый стек: NaiveUI (style-src 'unsafe-inline' для
    | CSS-in-JS) и Laravel Reverb (connect-src ws:/wss: для websocket).
    | Заголовок CSP не выставляется в окружении local (см. SecurityHeaders).
    |
    */
    'headers' => [
        'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
        'csp' => env(
            'SECURITY_CSP',
            "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; ".
            "img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self' ws: wss:; ".
            "object-src 'none'; frame-ancestors 'none'; base-uri 'self'"
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ограничение доступа по IP (УПД.13, опционально)
    |--------------------------------------------------------------------------
    */
    'ip_whitelist' => [
        'enabled' => env('SECURITY_IP_WHITELIST_ENABLED', false),
        // Список через запятую в .env: SECURITY_IP_WHITELIST="10.0.0.0/8,192.168.1.5"
        'ranges' => array_filter(explode(',', (string) env('SECURITY_IP_WHITELIST', ''))),
    ],

];
