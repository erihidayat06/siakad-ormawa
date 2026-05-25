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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            // Relasi ke User (Mahasiswa yang mengajukan)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Data Dokumen
            $table->string('title');
            $table->string('original_file'); // File awal yang diupload mahasiswa
            $table->string('current_file')->nullable(); // File terbaru yang sudah ada TTD pejabat

            // Status Utama
            $table->enum('status', ['pending', 'revision', 'completed'])->default('pending');

            // Logika Estafet (Siklus 1)
            $table->enum('current_step', [
                'bem',
                'kaprodi',
                'dekan',
                'wd3',
                'wd2',
                'tu',
                'selesai'
            ])->default('bem');

            // Untuk menyimpan bukti transfer/pembayaran dari TU nantinya
            $table->string('payment_proof')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
