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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Uraian Pengeluaran
            $table->text('description')->nullable(); // Keterangan / Rincian Tambahan
            $table->decimal('amount', 15, 2); // Nominal Pengeluaran (Rp)
            $table->date('expense_date'); // Tanggal Pengeluaran
            $table->string('category')->default('Operasional'); // Kategori Pengeluaran
            $table->string('recipient')->nullable(); // Penerima / Penanggung Jawab
            $table->string('receipt_proof')->nullable(); // Foto Bukti Nota / Kuitansi
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
