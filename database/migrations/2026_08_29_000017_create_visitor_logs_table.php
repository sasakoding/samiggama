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
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_hash', 64)->index();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('url', 255)->index();
            $table->string('page_name', 100)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('device_type', 20)->default('desktop'); // desktop, mobile, tablet
            $table->string('referer', 255)->nullable();
            $table->date('visited_date')->index();
            $table->timestamps();

            $table->index(['visited_date', 'ip_hash']);
            $table->index(['visited_date', 'url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};
