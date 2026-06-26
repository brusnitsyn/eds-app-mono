<?php

namespace App\Services\Crypto;

use RuntimeException;

/**
 * ЗАГЛУШКА драйвера шифрования по ГОСТ Р 34.12-2015 («Кузнечик»/«Магма»).
 *
 * Мера ФСТЭК/ФСБ: ЗИС.16, приказ ФСБ №378 — применение сертифицированных СКЗИ.
 *
 * Для государственных ИСПДн встроенный AES не является сертифицированным СКЗИ.
 * Реальная реализация должна вызывать внешний криптопровайдер (КриптоПро CSP,
 * VipNet) — например через CLI `csptest`, расширение PHP с поддержкой ГОСТ или
 * gRPC/REST-шлюз к СКЗИ. Здесь намеренно оставлен каркас, чтобы:
 *   - не создавать ложного ощущения сертифицированной защиты;
 *   - дать понятную точку интеграции при аттестации.
 *
 * Чтобы активировать: SECURITY_PDN_CIPHER_DRIVER=gost и реализовать методы.
 */
class GostCipher implements PdnCipher
{
    /**
     * @param  array{binary:?string,container:?string}  $config
     */
    public function __construct(private readonly array $config) {}

    public function encrypt(string $plaintext): string
    {
        throw new RuntimeException(
            'Драйвер ГОСТ не реализован. Подключите сертифицированное СКЗИ '
            .'(КриптоПро CSP / VipNet) в App\Services\Crypto\GostCipher.'
        );
    }

    public function decrypt(string $ciphertext): string
    {
        throw new RuntimeException(
            'Драйвер ГОСТ не реализован. Подключите сертифицированное СКЗИ '
            .'(КриптоПро CSP / VipNet) в App\Services\Crypto\GostCipher.'
        );
    }

    public function algorithm(): string
    {
        return 'GOST-R-34.12-2015';
    }

    /**
     * Настроено ли внешнее СКЗИ (путь к бинарю и контейнер ключа).
     * Используйте в health-check перед включением драйвера gost.
     */
    public function isAvailable(): bool
    {
        return ! empty($this->config['binary'])
            && ! empty($this->config['container'])
            && is_executable((string) $this->config['binary']);
    }
}
