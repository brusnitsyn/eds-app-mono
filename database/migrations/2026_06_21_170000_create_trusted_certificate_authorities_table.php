<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trusted_certificate_authorities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['root', 'intermediate']);
            $table->string('file_path');
            $table->string('subject_cn')->nullable();
            $table->string('serial_number')->nullable();
            $table->unsignedBigInteger('valid_to')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_certificate_authorities');
    }
};
