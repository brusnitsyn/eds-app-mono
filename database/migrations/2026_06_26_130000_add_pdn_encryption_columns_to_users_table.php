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
        Schema::table('users', function (Blueprint $table) {
            // Зашифрованное значение (AES-256-GCM/base64) существенно длиннее
            // исходного, поэтому string (varchar 255) недостаточно.
            $table->text('login')->change();
            $table->text('email')->nullable()->change();
            $table->text('name')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            // Детерминированный HMAC для точного поиска по зашифрованным полям
            // (логин при аутентификации, email при сбросе пароля).
            $table->string('login_hash')->nullable()->after('login');
            $table->string('email_hash')->nullable()->after('email');

            $table->index('login_hash');
            $table->index('email_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['login_hash']);
            $table->dropIndex(['email_hash']);
            $table->dropColumn(['login_hash', 'email_hash']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('login')->change();
            $table->string('email')->nullable()->change();
            $table->string('name')->change();
        });
    }
};
