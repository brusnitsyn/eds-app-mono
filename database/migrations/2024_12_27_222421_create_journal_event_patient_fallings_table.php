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
        Schema::create('journal_event_patient_fallings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_at');
            $table->foreignIdFor(\App\Models\Division::class);
            $table->string('full_name_patient')->nullable();
            $table->string('reason_event')->nullable();
            $table->string('place_event')->nullable();
            $table->string('has_helping')->nullable();
            $table->string('consequences')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_event_patient_fallings');
    }
};
