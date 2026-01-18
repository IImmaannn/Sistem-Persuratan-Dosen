<?php

namespace App\Filament\Resources\PermohonanSuratResource\Pages;

use App\Filament\Resources\PermohonanSuratResource;
use Filament\Resources\Pages\Page;

class SelectionPermohonan extends Page
{
    protected static string $resource = PermohonanSuratResource::class;

    protected static string $view = 'filament.resources.permohonan-surat-resource.pages.selection-permohonan';

    // TAMBAHKAN KODE INI
    public function getTitle(): string 
    {
        return 'Pilih Jenis Permohonan Surat'; // Sesuai instruksi untuk kustomisasi judul
    }
}

