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
        Schema::create('proposal_logs', function (Blueprint $table) {
            $table->id();
            // Siapa yang melakukan aksi (Pejabat/Mahasiswa)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Relasi Polymorphic (id dokumen & tipe modelnya)
            $table->unsignedBigInteger('loggable_id');
            $table->string('loggable_type');

            // Detail Aksi
            $table->string('action'); // Contoh: 'approved', 'revision', 'submitted'
            $table->text('notes')->nullable(); // Catatan revisi di sini
            $table->string('file_result')->nullable(); // Simpan file yang sudah di-TTD pejabat

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_logs');
    }
};
