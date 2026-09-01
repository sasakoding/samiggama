<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // For databases supporting column modification
        Schema::table('articles', function (Blueprint $table) {
            $table->string('category', 100)->default('Kajian Dhamma')->change();
        });
    }

    public function down(): void
    {
        // Revert to original if needed
    }
};
