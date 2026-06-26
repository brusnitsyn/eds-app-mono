<?php

namespace App\Services\Crypto;

use Illuminate\Contracts\Encryption\Encrypter;

/**
 * Драйвер шифрования на встроенном шифраторе Laravel (AES-256-GCM).
 *
 * Применяется по умолчанию. Для государственных ИС, требующих
 * сертифицированные СКЗИ, замените на {@see GostCipher}.
 */
class LaravelAesCipher implements PdnCipher
{
    public function __construct(private readonly Encrypter $encrypter) {}

    public function encrypt(string $plaintext): string
    {
        return $this->encrypter->encryptString($plaintext);
    }

    public function decrypt(string $ciphertext): string
    {
        return $this->encrypter->decryptString($ciphertext);
    }

    public function algorithm(): string
    {
        return 'AES-256-GCM';
    }
}
