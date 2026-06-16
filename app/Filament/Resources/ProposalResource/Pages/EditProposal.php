<?php

namespace App\Filament\Resources\ProposalResource\Pages;

use App\Filament\Resources\ProposalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProposal extends EditRecord
{
    protected static string $resource = ProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol DeleteAction dihapus agar mahasiswa tidak bisa menghapus berkas dari halaman edit
        ];
    }

    /**
     * Otomatis mengubah data SEBELUM disimpan ke database
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ketika mahasiswa menyimpan hasil edit (baik saat masa 'revision' atau 'pending' awal),
        // paksa status kembali ke 'pending' dan posisinya kembali ke antrean meja pertama ('bem')
        $data['status'] = 'pending';
        $data['current_step'] = 'bem';

        return $data;
    }

    /**
     * Otomatis berjalan SELESAI proses simpan berhasil dilakukan
     */
    protected function afterSave(): void
    {
        // Menambahkan catatan riwayat ke tabel logs agar BEM tahu mahasiswa sudah mengirimkan perbaikan
        $this->record->logs()->create([
            'user_id' => auth()->id(),
            'action' => 'submitted',
            'notes' => 'Mahasiswa telah memperbarui dokumen dan mengirimkan kembali proposal ke sistem.',
        ]);
    }

    /**
     * Mengarahkan langsung ke halaman Index (daftar proposal) setelah klik Save
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
