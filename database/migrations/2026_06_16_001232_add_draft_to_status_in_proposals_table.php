<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            // Menambahkan 'draft' ke dalam pilihan enum status
            $table->enum('status', ['draft', 'pending', 'revision', 'completed'])
                ->default('draft')
                ->change();

            // Mengubah original_file menjadi nullable agar draf bisa disimpan tanpa file dulu
            $table->string('original_file')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            // Mengembalikan ke struktur awal jika di-rollback
            $table->enum('status', ['pending', 'revision', 'completed'])
                ->default('pending')
                ->change();

            $table->string('original_file')->nullable(false)->change();
        });
    }
};
