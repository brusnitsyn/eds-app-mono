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
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number');
            $table->unsignedBigInteger('valid_from');
            $table->unsignedBigInteger('valid_to');
            $table->boolean('is_valid')->default(false);
            $table->boolean('is_request_new')->default(false);
            $table->string('path_certification')->nullable();
            $table->string('file_certification')->nullable();
            $table->unsignedBigInteger('close_key_valid_to')->nullable(); // Закрытый ключ до
            $table->boolean('close_key_is_valid')->default(false); // Актуальный ли ключ
            $table->foreignIdFor(\App\Models\Staff::class);

            $table->string('mis_serial_number')->nullable();
            $table->unsignedBigInteger('mis_valid_from')->nullable();
            $table->unsignedBigInteger('mis_valid_to')->nullable();
            $table->boolean('mis_is_identical')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};
