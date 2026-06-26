<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Журнал регистрации событий безопасности (меры РСБ.2, РСБ.3).
 *
 * Размещается в ОТДЕЛЬНОМ соединении (audit). Запускается командой:
 *   php artisan migrate --database=audit --path=database/migrations/audit
 *
 * В production учётной записи приложения выдаются права только INSERT/SELECT
 * (см. database/sql/audit_grants.sql), что обеспечивает неизменяемость журнала.
 */
return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('audit.connection', 'audit');
    }

    public function up(): void
    {
        Schema::connection($this->getConnection())->create(config('audit.table', 'audit_log'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('event_id')->index();
            $table->string('event_type')->index();
            $table->string('user_id')->nullable()->index();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('session_id')->nullable();
            $table->string('resource')->nullable()->index();
            $table->string('action');
            $table->string('result', 16)->index();
            $table->json('details')->nullable();

            // Контроль целостности (РСБ.3): HMAC-подпись и хеш-цепочка.
            $table->string('prev_hash')->nullable();
            $table->string('signature');

            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists(config('audit.table', 'audit_log'));
    }
};
