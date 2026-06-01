<?php

namespace App\Filament\Resources\PenomoranSuratResource\Pages;

use App\Filament\Resources\PenomoranSuratResource;
use App\Models\SystemAsset;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Services\PenomoranService;

class EditPenomoranSurat extends EditRecord
{
    protected static string $resource = PenomoranSuratResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['status_terakhir'] = 'Selesai_Pimpinan';
        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (empty($data['nomor_surat'])) {
            
            $tahun = date('Y');
            $bulan = date('n'); 

            $romawi = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
            $bulanRomawi = $romawi[$bulan];
            
            $kodeTetap = 'UN7.F1/DK'; 

            $akhiranSurat = '/' . $bulanRomawi . '/' . $tahun; 
            
            $suratBulanIni = \App\Models\PermohonanSurat::whereNotNull('nomor_surat')
                ->where('nomor_surat', 'LIKE', '%' . $akhiranSurat)
                ->get();

            $maxUrutan = 0;

            foreach ($suratBulanIni as $surat) {
                $pecahan = explode('/', $surat->nomor_surat);
                
                if (isset($pecahan[0]) && is_numeric($pecahan[0])) {
                    $angka = (int)$pecahan[0];
                    if ($angka > $maxUrutan) {
                        $maxUrutan = $angka;
                    }
                }
            }
            $urutan = $maxUrutan + 1;
            
            $data['nomor_surat'] = sprintf("%d", $urutan) . '/' . $kodeTetap . '/' . $bulanRomawi . '/' . $tahun;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $service = new PenomoranService();
        $service->prosesPenerbitanPDFdanEmail($record);
    }
}