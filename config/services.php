<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'certificate_parser' => [
        'python_binary' => env('CERTIFICATE_PARSER_PYTHON_BINARY', 'python3'),
        'script_path' => env('CERTIFICATE_PARSER_SCRIPT_PATH', 'python-services/certificate_parser.py'),
        'timeout' => env('CERTIFICATE_PARSER_TIMEOUT', 30),

        // Common Name'ы аккредитованных УЦ, которым доверяет организация.
        // Список пуст по умолчанию — пока он не заполнен, проверка цепочки
        // доверия honest-но помечается как "не настроена", а не подделывается.
        'trusted_issuers' => array_filter(array_map(
            'trim',
            explode(',', (string) env('CERTIFICATE_TRUSTED_ISSUERS', ''))
        )),
    ],

];
