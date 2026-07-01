<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workstation_software', function (Blueprint $table) {
            $table->id();
            $table->string('key');        // chromium_gost | cryptopro_plugin | cryptopro_csp
            $table->string('label');      // отображаемое имя
            $table->string('version')->nullable();
            $table->string('file_path');
            $table->string('original_name');
            $table->unsignedBigInteger('file_size');
            $table->timestamps();

            $table->unique('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workstation_software');
    }
};
