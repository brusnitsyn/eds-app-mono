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
        // $table->enum('gender', ['liza', 'slava'])->default('slava')->change();
        DB::statement("
            ALTER TABLE staff ALTER COLUMN gender SET DEFAULT 'slava'
        ");

        Schema::table('staff', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\Division::class)->nullable()->change();
        });
    }
};
