<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
    /**
     * Menentukan apakah user bisa melihat daftar proposal (halaman index).
     */
    public function viewAny(User $user): bool
    {
        // Izinkan semua role kecuali admin (atau sesuaikan dengan kebutuhan panel kamu)
        return $user->role !== 'admin';
    }

    /**
     * Menentukan apakah user bisa melihat detail proposal (tombol view & baris di tabel).
     */
    public function view(User $user, Proposal $proposal): bool
    {
        // 1. Jika yang login adalah mahasiswa, hanya bisa melihat proposal miliknya sendiri
        if ($user->role === 'mahasiswa') {
            return $user->id === $proposal->user_id;
        }

        // 2. Jika pejabat (BEM, Kaprodi, Dekan, WD3, WD2, TU), izinkan melihat SEMUA proposal
        // agar mereka bisa memantau alur dokumen secara real-time sejak awal masuk.
        $pejabatRoles = ['bem', 'kaprodi', 'dekan', 'wd3', 'wd2', 'tu'];

        return in_array($user->role, $pejabatRoles);
    }

    /**
     * Menentukan apakah user bisa membuat proposal baru.
     */
    public function create(User $user): bool
    {
        // Hanya mahasiswa yang bisa mengajukan proposal
        return $user->role === 'mahasiswa';
    }

    /**
     * Menentukan apakah user bisa mengubah/mengedit proposal.
     */
    public function update(User $user, Proposal $proposal): bool
    {
        // Logika disamakan dengan canEdit di Resource
        return $user->role === 'mahasiswa' &&
            $user->id === $proposal->user_id && (
                $proposal->status === 'revision' ||
                ($proposal->status === 'pending' && $proposal->current_step === 'bem')
            );
    }

    /**
     * Menentukan apakah user bisa menghapus proposal.
     */
    public function delete(User $user, Proposal $proposal): bool
    {

        return $user->role === 'admin';
    }
}
