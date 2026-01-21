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
        ];
    }

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
