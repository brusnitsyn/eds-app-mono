<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // Зашифрованное значение (AES-256-GCM/base64) существенно длиннее
            // исходного, поэтому string (varchar 255) недостаточно.
            $table->text('snils')->change();
            $table->text('inn')->change();
        });

        Schema::table('staff', function (Blueprint $table) {
            // Детерминированный HMAC для точного поиска по зашифрованным полям
            // (мера ЗНИ — поиск без расшифровки всей таблицы).
            $table->string('snils_hash')->nullable()->after('snils');
            $table->string('inn_hash')->nullable()->after('inn');

            // Контрольная сумма записи для контроля целостности (ОЦЛ.2).
            $table->string('pdn_checksum')->nullable();

            $table->index('snils_hash');
            $table->index('inn_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropIndex(['snils_hash']);
            $table->dropIndex(['inn_hash']);
            $table->dropColumn(['snils_hash', 'inn_hash', 'pdn_checksum']);
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->string('snils')->change();
            $table->string('inn')->change();
        });
    }
};
