<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lpj_logs', function (Blueprint $table) {
            $table->id();
            // Menghubungkan log ke data LPJ utama
            $table->foreignId('lpj_id')->constrained('lpjs')->onDelete('cascade');
            // Menghubungkan ke user yang melakukan aksi (Kaprodi/WD3/Mhs)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->string('action'); // 'submitted', 'approved', 'revision'
            $table->text('notes')->nullable(); // Catatan revisi atau alasan approve
            $table->string('file_result')->nullable(); // Tempat menyimpan file PDF jika ada upload TTD baru di tengah alur

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lpj_logs');
    }
};
