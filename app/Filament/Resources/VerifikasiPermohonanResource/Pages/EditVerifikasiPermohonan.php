<?php

namespace App\Filament\Resources\VerifikasiPermohonanResource\Pages;

use App\Filament\Resources\VerifikasiPermohonanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVerifikasiPermohonan extends EditRecord
{
    protected static string $resource = VerifikasiPermohonanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // 1. SULAP TOMBOL "SAVE CHANGES" MENJADI TOMBOL "VERIFIKASI" DENGAN IKON MENARIK
    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Verifikasi')
            ->icon('heroicon-o-check-badge'); 
    }

    // 2. LOGIKA OTOMATIS: SAAT DIKLIK, SISTEM MENYUNTIKKAN STATUS 'TERVERIFIKASI' KE DATABASE
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['status_terakhir'] = 'Terverifikasi';

        return $data;
    }

    // 3. SETELAH BERHASIL VERIFIKASI, LANGSUNG REDIRECT KEMBALI KE HALAMAN UTAMA DASHBOARD OPERATOR
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}