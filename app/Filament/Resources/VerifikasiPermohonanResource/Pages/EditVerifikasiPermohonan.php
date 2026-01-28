<?php

namespace App\Filament\Resources\VerifikasiPermohonanResource\Pages;

use App\Filament\Resources\VerifikasiPermohonanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
// use Illuminate\Database\Eloquent\Model;


class EditVerifikasiPermohonan extends EditRecord
{
    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected static string $resource = VerifikasiPermohonanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // 1. Update data utama (Status, dll di tabel permohonan_surats)
        $record->update($data);

        // 2. Update data detail (keterangan_essais) manual
        // Kita cek apakah ada inputan 'keteranganEssai' dari form
        if (isset($data['keteranganEssai'])) {
            // Update relasi
            $record->keteranganEssai()->update($data['keteranganEssai']);
        }

        return $record;
    }

}
