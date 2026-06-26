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
            $table->text('first_name')->change();
            $table->text('middle_name')->nullable()->change();
            $table->text('last_name')->change();
            $table->text('full_name')->change();
            $table->text('job_title')->change();
            $table->text('mis_login')->nullable()->change();
        });

        Schema::table('staff', function (Blueprint $table) {
            // Детерминированный HMAC для точного поиска по зашифрованному полю
            // (фильтр по должности в UI, см. job_title_hash).
            $table->string('job_title_hash')->nullable()->after('job_title');

            $table->index('job_title_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropIndex(['job_title_hash']);
            $table->dropColumn('job_title_hash');
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->string('first_name')->change();
            $table->string('middle_name')->nullable()->change();
            $table->string('last_name')->change();
            $table->string('full_name')->change();
            $table->string('job_title')->change();
            $table->string('mis_login')->nullable()->change();
        });
    }
};
