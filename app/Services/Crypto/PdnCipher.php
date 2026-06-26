<?php

namespace App\Services\Crypto;

/**
 * Контракт криптографического драйвера для защиты персональных данных.
 *
 * Мера ФСТЭК: ЗНИ (защита носителей информации), ОЦЛ.2.
 * Абстракция позволяет менять реализацию (AES Laravel ↔ ГОСТ/КриптоПро)
 * без изменения моделей и бизнес-логики (мера ЗИС.16).
 */
interface PdnCipher
{
    /**
     * Зашифровать значение.
     */
    public function encrypt(string $plaintext): string;

    /**
     * Расшифровать значение.
     */
    public function decrypt(string $ciphertext): string;

    /**
     * Идентификатор алгоритма (для аудита и маркировки).
     */
    public function algorithm(): string;
}
