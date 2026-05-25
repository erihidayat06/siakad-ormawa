<?php

namespace App\Filament\Resources\LpjResource\Pages;

use App\Filament\Resources\LpjResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLpj extends EditRecord
{
    protected static string $resource = LpjResource::class;

    protected function beforeFill(): void
    {
        // Jika bukan mahasiswa pemilik, atau LPJ sudah disetujui, tolak akses edit langsung
        if (
            auth()->user()->role !== 'mahasiswa' ||
            $this->record->user_id !== auth()->id() ||
            $this->record->status === 'completed'
        ) {

            \Filament\Notifications\Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki hak untuk mengubah dokumen ini.')
                ->danger()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // Tombol hapus di halaman edit juga disembunyikan jika sudah selesai
            Actions\DeleteAction::make()
                ->visible(fn() => $this->record->status !== 'completed'),
        ];
    }
}
