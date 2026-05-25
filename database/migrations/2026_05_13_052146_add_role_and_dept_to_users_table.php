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
        Schema::table('users', function (Blueprint $table) {
            // Menambahkan kolom role untuk menentukan siapa yang berhak approve
            $table->enum('role', [
                'mahasiswa',
                'bem',
                'kaprodi',
                'dekan',
                'wd3',
                'wd2',
                'tu',
                'admin'
            ])->after('password')->default('mahasiswa');

            // Menghubungkan user ke departemen/prodi tertentu
            $table->foreignId('department_id')->nullable()->after('role')->constrained('departments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['role', 'department_id']);
        });
    }
};
