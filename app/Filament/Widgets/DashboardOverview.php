<?php

namespace App\Filament\Widgets;

use App\Models\Proposal;
use App\Models\Lpj;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // 1. Ambil data user yang sedang login beserta role dan department_id-nya
        $user = auth()->user();
        $role = $user->role;
        $userDepartmentId = $user->department_id;

        // 2. Inisialisasi Query Dasar
        $proposalQuery = Proposal::query();
        $lpjQuery = Lpj::query();

        // 3. FILTER BERDASARKAN DEPARTEMEN/JURUSAN (Hanya untuk Mahasiswa dan Kaprodi)
        // Pejabat tingkat fakultas (wd3, wd2, wd4) tidak difilter agar bisa melihat seluruh jurusan
        if (in_array($role, ['mahasiswa', 'kaprodi']) && $userDepartmentId) {
            $proposalQuery->whereHas('user', function ($query) use ($userDepartmentId) {
                $query->where('department_id', $userDepartmentId);
            });

            $lpjQuery->whereHas('user', function ($query) use ($userDepartmentId) {
                $query->where('department_id', $userDepartmentId);
            });
        }

        // 4. FILTER BERDASARKAN ROLE & ALUR VALIDASI (Masing-masing Jabatan)
        if ($role === 'mahasiswa') {
            // Jika mahasiswa, hanya melihat dokumen milik dia sendiri
            $proposalQuery->where('user_id', $user->id);
            $lpjQuery->where('user_id', $user->id);
        } else {
            // Jika pejabat (kaprodi, wd3, wd2, wd4), hanya melihat dokumen yang saat ini mandek di mejanya
            $proposalQuery->where('current_step', $role);
            $lpjQuery->where('current_step', $role);
        }

        // 5. Query Khusus untuk Menghitung Total Dokumen Selesai (Completed)
        $totalCompletedProposal = Proposal::where('status', 'completed');
        $totalCompletedLpj = Lpj::where('status', 'completed');

        // Untuk counter "Selesai", Kaprodi hanya melihat arsip jurusannya, WD melihat arsip se-Fakultas
        if (in_array($role, ['mahasiswa', 'kaprodi']) && $userDepartmentId) {
            $totalCompletedProposal->whereHas('user', function ($query) use ($userDepartmentId) {
                $query->where('department_id', $userDepartmentId);
            });
            $totalCompletedLpj->whereHas('user', function ($query) use ($userDepartmentId) {
                $query->where('department_id', $userDepartmentId);
            });
        }

        // 6. Tentukan teks deskripsi kotak completed agar dinamis sesuai jabatan
        $completedDescription = in_array($role, ['mahasiswa', 'kaprodi'])
            ? 'Total arsip jurusan Anda yang disetujui'
            : 'Total arsip fakultas yang telah disetujui';

        return [
            Stat::make('Proposal Diproses', (clone $proposalQuery)->where('status', 'pending')->count())
                ->description('Butuh validasi / pengecekan Anda')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('LPJ Butuh Validasi', (clone $lpjQuery)->where('status', 'pending')->count())
                ->description('Laporan kegiatan ormawa')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info'),

            Stat::make('Dokumen Selesai (Completed)', $totalCompletedProposal->count() + $totalCompletedLpj->count())
                ->description($completedDescription)
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
