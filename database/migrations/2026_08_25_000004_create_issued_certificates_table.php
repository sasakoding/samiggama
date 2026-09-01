<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issued_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number', 100)->unique();
            $table->foreignId('donation_id')->constrained('donations')->cascadeOnDelete();
            $table->foreignId('certificate_template_id')->constrained('certificate_templates')->cascadeOnDelete();
            $table->string('recipient_name');
            $table->unsignedBigInteger('amount');
            $table->date('issued_date');
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_certificates');
    }
};
