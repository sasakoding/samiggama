<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('donation_program_id')->nullable()->constrained('donation_programs')->nullOnDelete();
            $table->string('donor_name');
            $table->string('phone', 30)->nullable();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('total_amount');
            $table->string('payment_method', 50);
            $table->text('donor_message')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
