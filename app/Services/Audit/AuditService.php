<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Журналирование событий безопасности (меры РСБ.*).
 *
 *  - РСБ.2: запись событий с обязательными атрибутами (время, субъект, IP,
 *    объект, результат);
 *  - РСБ.3: HMAC-подпись каждой записи + хеш-цепочка (prev_hash) для контроля
 *    целостности и выявления удаления записей;
 *  - РСБ.7: дублирование в SIEM-канал;
 *
 * ВАЖНО: в журнал не записываются сами персональные данные — только
 * идентификаторы объектов.
 */
class AuditService
{
    /**
     * Зарегистрировать событие безопасности.
     *
     * @param  array<string,mixed>  $details  Контекст без ПДн.
     */
    public function log(
        string $eventType,
        string $action = '',
        ?string $resource = null,
        string $result = 'success',
        array $details = [],
    ): AuditLog {
        $request = $this->currentRequest();

        $entry = new AuditLog([
            'event_id' => (string) Str::uuid(),
            'event_type' => $eventType,
            'user_id' => optional(auth()->user())->getAuthIdentifier(),
            'ip' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 500, '') : null,
            'session_id' => $this->sessionId($request),
            'resource' => $resource,
            'action' => $action !== '' ? $action : $eventType,
            'result' => $result,
            'details' => $details,
        ]);

        // Фиксируем время с точностью до секунды, чтобы подпись считалась от той
        // же величины, что сохранится в колонке created_at (без микросекунд).
        $entry->created_at = now()->startOfSecond();
        $entry->prev_hash = $this->lastHash();
        $entry->signature = $this->sign($entry);
        $entry->save();

        $this->mirrorToSiem($entry);

        return $entry;
    }

    /**
     * Проверить целостность журнала (HMAC + цепочка). Используется командой
     * проверки и при аудите. Возвращает массив id повреждённых записей.
     *
     * @return array<int, mixed>
     */
    public function verifyIntegrity(): array
    {
        $broken = [];
        $prevHash = null;

        AuditLog::query()->orderBy('id')->each(function (AuditLog $entry) use (&$broken, &$prevHash): void {
            $chainOk = config('audit.chain') ? $entry->prev_hash === $prevHash : true;
            $sigOk = hash_equals($entry->signature, $this->sign($entry));

            if (! $chainOk || ! $sigOk) {
                $broken[] = $entry->getKey();
            }

            $prevHash = $entry->signature;
        });

        return $broken;
    }

    /**
     * Каноничное представление записи для подписи — только хранимые колонки
     * в фиксированном порядке, время берётся из created_at.
     */
    private function sign(AuditLog $entry): string
    {
        $canonical = [
            'event_id' => $entry->event_id,
            'event_time' => optional($entry->created_at)?->startOfSecond()->toIso8601String(),
            'event_type' => $entry->event_type,
            'user_id' => $entry->user_id,
            'ip' => $entry->ip,
            'user_agent' => $entry->user_agent,
            'session_id' => $entry->session_id,
            'resource' => $entry->resource,
            'action' => $entry->action,
            'result' => $entry->result,
            'details' => $entry->details,
            'prev_hash' => $entry->prev_hash,
        ];

        $json = json_encode($canonical, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha256', (string) $json, $this->hmacKey());
    }

    private function lastHash(): ?string
    {
        if (! config('audit.chain')) {
            return null;
        }

        return AuditLog::query()->orderByDesc('id')->value('signature');
    }

    private function hmacKey(): string
    {
        $key = (string) config('audit.hmac_key');

        if ($key === '') {
            throw new RuntimeException('Не задан AUDIT_HMAC_KEY для подписи журнала аудита.');
        }

        return str_starts_with($key, 'base64:')
            ? (string) base64_decode(substr($key, 7), true)
            : $key;
    }

    private function mirrorToSiem(AuditLog $entry): void
    {
        if (! config('audit.siem.enabled')) {
            return;
        }

        try {
            Log::channel(config('audit.siem.channel', 'siem'))->info('audit', $entry->getAttributes());
        } catch (Throwable $e) {
            // Сбой SIEM не должен прерывать основную запись журнала.
            Log::error('audit.siem_failed', ['error' => $e->getMessage()]);
        }
    }

    private function currentRequest(): ?Request
    {
        return app()->bound('request') ? app('request') : null;
    }

    private function sessionId(?Request $request): ?string
    {
        if ($request === null || ! $request->hasSession()) {
            return null;
        }

        return $request->session()->getId();
    }
}
