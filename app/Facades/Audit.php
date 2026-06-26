<?php

namespace App\Facades;

use App\Models\AuditLog;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static AuditLog log(string $eventType, string $action = '', ?string $resource = null, string $result = 'success', array<string, mixed> $details = [])
 * @method static array<int, mixed> verifyIntegrity()
 *
 * @see AuditService
 */
class Audit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuditService::class;
    }
}
