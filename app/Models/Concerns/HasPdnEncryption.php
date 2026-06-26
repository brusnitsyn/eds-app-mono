<?php

namespace App\Models\Concerns;

use App\Services\Crypto\PdnCipher;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Прозрачное шифрование полей персональных данных на уровне приложения.
 *
 * Мера ФСТЭК: ЗНИ (защита носителей), ОЦЛ.2 (контроль целостности данных).
 *
 * Использование в модели:
 *
 *   use HasPdnEncryption;
 *   protected array $encrypted = ['last_name', 'passport', 'snils'];
 *   protected array $pdnLookupColumns = ['snils' => 'snils_hash'];
 *
 * Шифрование выполняется выбранным драйвером (config/security.php → encryption),
 * что позволяет заменить AES на сертифицированное СКЗИ (ГОСТ) без правки моделей.
 *
 * Шифр AES-256-GCM недетерминирован, поэтому точный поиск (`WHERE snils = ?`)
 * по зашифрованному значению невозможен напрямую. Для атрибутов, перечисленных
 * в $pdnLookupColumns, при записи дополнительно вычисляется детерминированный
 * HMAC-SHA256 («блайнд-индекс») и сохраняется в указанную колонку — по ней и
 * выполняется точный поиск (см. Staff::findBySnils()/scopeBySnilsIn()).
 */
trait HasPdnEncryption
{
    public function setAttribute($key, $value)
    {
        if ($this->isPdnEncrypted($key) && $value !== null) {
            $lookupColumn = $this->pdnLookupColumns()[$key] ?? null;

            if ($lookupColumn !== null) {
                $hash = in_array($key, $this->pdnExactLookupColumns(), true)
                    ? static::pdnExactHash((string) $value)
                    : static::pdnLookupHash((string) $value);

                parent::setAttribute($lookupColumn, $hash);
            }

            $value = $this->pdnCipher()->encrypt((string) $value);
        }

        return parent::setAttribute($key, $value);
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if ($this->isPdnEncrypted($key) && $value !== null && is_string($value)) {
            try {
                return $this->pdnCipher()->decrypt($value);
            } catch (Throwable $e) {
                // Нарушение целостности/невозможность расшифровки — инцидент (ИНЦ.1).
                Log::channel(config('audit.siem.channel', 'stack'))->error('pdn.decrypt_failed', [
                    'model' => static::class,
                    'attribute' => $key,
                    'id' => $this->getKey(),
                ]);

                return null;
            }
        }

        return $value;
    }

    /**
     * Список зашифрованных атрибутов модели.
     *
     * @return array<int, string>
     */
    public function encryptedAttributes(): array
    {
        return $this->encrypted;
    }

    /**
     * Карта «зашифрованный атрибут → колонка детерминированного хеша для поиска».
     *
     * @return array<string, string>
     */
    public function pdnLookupColumns(): array
    {
        return $this->pdnLookupColumns ?? [];
    }

    /**
     * Подмножество ключей $pdnLookupColumns, для которых нужен «строгий» хеш
     * (см. pdnExactHash) вместо «нестрогого» (pdnLookupHash).
     *
     * @return array<int, string>
     */
    public function pdnExactLookupColumns(): array
    {
        return $this->pdnExactLookupColumns ?? [];
    }

    /**
     * Детерминированный HMAC-SHA256 для точного поиска по зашифрованному полю.
     * Нормализует ввод (пробелы/дефисы, регистр), чтобы хеш не зависел от формата
     * ввода — нужно для СНИЛС/ИНН, которые пользователи вводят с разделителями
     * по-разному («123-456-789 01» и «12345678901» должны совпасть).
     *
     * ВАЖНО: не использовать для полей, где дефис/пробел значимы (login, email,
     * job_title) — это привело бы к ложным совпадениям разных значений
     * (например, разных логинов, отличающихся только дефисом). Для таких полей
     * используйте pdnExactHash().
     */
    public static function pdnLookupHash(string $value): string
    {
        $normalized = mb_strtolower((string) preg_replace('/[\s-]+/u', '', $value));

        return hash_hmac('sha256', $normalized, (string) config('security.encryption.pseudonym_key'));
    }

    /**
     * Детерминированный HMAC-SHA256 для точного поиска, без удаления
     * пробелов/дефисов (значимы для login/email/job_title) — только регистр
     * и крайние пробелы нормализуются.
     */
    public static function pdnExactHash(string $value): string
    {
        $normalized = mb_strtolower(trim($value));

        return hash_hmac('sha256', $normalized, (string) config('security.encryption.pseudonym_key'));
    }

    protected function isPdnEncrypted(string $key): bool
    {
        return in_array($key, $this->encryptedAttributes(), true);
    }

    protected function pdnCipher(): PdnCipher
    {
        return app(PdnCipher::class);
    }
}
