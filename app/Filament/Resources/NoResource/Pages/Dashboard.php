<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Resources\VerifikasiPermohonanResource;

class Dashboard extends BaseDashboard
{
    // Judul di browser tab & header
    protected static ?string $title = 'Dashboard';

    // "Satpam" yang bertugas mengecek siapa yang login
    public function mount(): void
    {
        // Jika Operator yang login, langsung tendang ke halaman Verifikasi
        if (auth()->user()?->role === 'Operator_Surat') {
            $this->redirect(VerifikasiPermohonanResource::getUrl());
        }
    }
}