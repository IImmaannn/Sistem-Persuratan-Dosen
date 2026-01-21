<?php

namespace App\Filament\Resources\VerifikasiPermohonanResource\Pages;

use App\Filament\Resources\VerifikasiPermohonanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVerifikasiPermohonan extends CreateRecord
{
    protected static string $resource = VerifikasiPermohonanResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
