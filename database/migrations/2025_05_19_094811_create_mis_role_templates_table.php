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
        Schema::create('mis_role_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('roles');
            $table->foreignIdFor(\App\Models\User::class, 'create_user_id')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mis_role_templates');
    }
};
