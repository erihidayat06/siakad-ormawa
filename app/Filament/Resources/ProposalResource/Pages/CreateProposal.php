<?php

namespace App\Filament\Resources\ProposalResource\Pages;

use App\Filament\Resources\ProposalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProposal extends CreateRecord
{
    protected static string $resource = ProposalResource::class;
    protected function getRedirectUrl(): string
    {
        // Mengarahkan langsung ke halaman Index (daftar proposal)
        return $this->getResource()::getUrl('index');
    }
    protected function afterCreate(): void
    {
        $this->record->logs()->create([
            'user_id' => auth()->id(),
            'action' => 'submitted',
            'notes' => 'Proposal baru telah diajukan ke sistem.',
        ]);
    }
}
