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
        Schema::create('lpjs', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke proposal yang sudah selesai
            $table->foreignId('proposal_id')->unique()->constrained('proposals')->onDelete('cascade');

            // Mahasiswa yang bertanggung jawab
            $table->foreignId('user_id')->constrained('users');

            $table->string('title');
            $table->string('original_file'); // File LPJ mentah dari mahasiswa
            $table->string('current_file')->nullable(); // File yang sudah diproses/TTD pejabat

            // Status LPJ
            $table->enum('status', ['pending', 'revision', 'completed'])->default('pending');

            // Alur Estafet LPJ (Sesuai Gambar 2): Kaprodi -> WD3 -> WD2 -> Selesai
            $table->enum('current_step', [
                'kaprodi',
                'wd3',
                'wd2',
                'selesai'
            ])->default('kaprodi');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lpjs');
    }
};
