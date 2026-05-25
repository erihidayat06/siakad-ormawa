<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    // Mengubah tulisan title header dashboard utama
    public function getTitle(): string
    {
        return 'Selamat Datang di SIAKAD-ORMAWA FEB';
    }
}
