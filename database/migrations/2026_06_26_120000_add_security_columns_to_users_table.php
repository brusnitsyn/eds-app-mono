<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('last_login_at')->nullable();
        });

        // Свежий 90-дневный отсчёт с момента выкладки для всех существующих
        // пользователей — иначе все аккаунты, созданные раньше срока действия
        // пароля, оказались бы немедленно просрочены при первом входе.
        DB::table('users')->whereNull('password_changed_at')->update([
            'password_changed_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password_changed_at', 'is_blocked', 'last_login_at']);
        });
    }
};
