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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('full_name');
            $table->string('job_title');
            $table->string('inn');
            $table->string('snils');
            $table->enum('gender', ['liza', 'slava']);
            $table->timestamp('dob')->nullable();
            $table->string('tel')->nullable();
            $table->foreignIdFor(\App\Models\Division::class);
            $table->integer('mis_user_id')->nullable();
            $table->unsignedBigInteger('mis_sync_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
