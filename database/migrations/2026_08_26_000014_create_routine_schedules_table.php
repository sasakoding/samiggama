<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('event_date');
            $table->string('activity_name');
            $table->string('leader_1');
            $table->string('leader_2')->nullable();
            $table->string('speaker');
            $table->string('topic');
            $table->string('time_range', 100)->default('08:30 - 10:30 WIB')->nullable();
            $table->enum('status', ['aktif', 'selesai', 'dibatalkan'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_schedules');
    }
};
