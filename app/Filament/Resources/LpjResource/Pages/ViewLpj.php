<?php

namespace App\Filament\Resources\LpjResource\Pages;

use App\Filament\Resources\LpjResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLpj extends ViewRecord
{
    protected static string $resource = LpjResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Memunculkan tombol edit di kanan atas halaman view jika status belum completed
            Actions\EditAction::make()
                ->visible(fn() => auth()->user()->role === 'mahasiswa' &&
                    $this->record->user_id === auth()->id() &&
                    $this->record->status !== 'completed'),
        ];
    }
}
