<?php

namespace App\Filament\Resources\PermohonanSuratResource\Pages;

use App\Filament\Resources\PermohonanSuratResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPermohonanSurats extends ListRecords
{
    protected static string $resource = PermohonanSuratResource::class;

    // 1. Mengubah Judul di bagian atas halaman
    public function getTitle(): string 
    {
        return 'History Permohonan Surat';
    }

    // 2. Menampilkan tombol "New/Create" di pojok kanan atas
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Permohonan Baru'), // Opsional: mengubah label tombol
        ];
    }
}