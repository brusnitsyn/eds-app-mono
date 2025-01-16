<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static encryptFile(string $path)
 * @method static decryptFile(string $path)
 *
 * @see \App\Services\CryptoService
 */
class Crypto extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'crypto.facade';
    }
}
