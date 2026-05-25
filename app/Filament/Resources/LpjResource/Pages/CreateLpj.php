<?php

namespace App\Filament\Resources\LpjResource\Pages;

use App\Filament\Resources\LpjResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLpj extends CreateRecord
{
    protected static string $resource = LpjResource::class;

    // Proteksi sebelum halaman dirender oleh Livewire
    public function mount(): void
    {
        parent::mount();

        // Jika bukan mahasiswa, tendang kembali ke halaman utama tabel
        if (auth()->user()->role !== 'mahasiswa') {
            \Filament\Notifications\Notification::make()
                ->title('Akses Ditolak')
                ->body('Hanya mahasiswa yang dapat membuat dokumen LPJ.')
                ->danger()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->logs()->create([
            'user_id' => auth()->id(),
            'action' => 'submitted',
            'notes' => 'Mengirimkan dokumen LPJ pertama kali untuk diperiksa Kaprodi.',
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
