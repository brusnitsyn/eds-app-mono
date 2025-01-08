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
            $table->string('valid_from');
            $table->string('valid_to');
            $table->boolean('is_valid')->default(false);
            $table->boolean('is_request_new')->default(false);
            $table->string('path_certification')->nullable();
            $table->string('file_certification')->nullable();
            $table->foreignIdFor(\App\Models\Staff::class);
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
