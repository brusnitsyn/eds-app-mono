<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Запись журнала регистрации событий безопасности.
 *
 * Мера ФСТЭК: РСБ.2, РСБ.3. Хранится в отдельном соединении (audit) с правами
 * только на INSERT/SELECT. Изменение/удаление записей пользователями запрещено.
 *
 * @property string $event_id
 * @property string $event_type
 * @property string|null $user_id
 * @property string|null $ip
 * @property string|null $user_agent
 * @property string|null $session_id
 * @property string|null $resource
 * @property string $action
 * @property string $result
 * @property array<string, mixed>|null $details
 * @property string|null $prev_hash
 * @property string $signature
 * @property int $id
 * @property CarbonImmutable|null $created_at
 */
class AuditLog extends Model
{
    // Журнал неизменяемый: updated_at не нужен.
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function getConnectionName(): ?string
    {
        return config('audit.connection', 'audit');
    }

    public function getTable(): string
    {
        return config('audit.table', 'audit_log');
    }
}
