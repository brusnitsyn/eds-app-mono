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
        Schema::table('certifications', function (Blueprint $table) {
            // Зашифрованное значение (AES-256-GCM/base64) существенно длиннее
            // исходного, поэтому string (varchar 255) недостаточно. Точный
            // поиск/сортировка по этим полям не требуются — hash-колонки не нужны.
            $table->text('serial_number')->change();
            $table->text('file_certification')->nullable()->change();
            $table->text('mis_serial_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certifications', function (Blueprint $table) {
            $table->string('serial_number')->change();
            $table->string('file_certification')->nullable()->change();
            $table->string('mis_serial_number')->nullable()->change();
        });
    }
};
