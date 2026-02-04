<?php

namespace App\Filament\Resources\PenomoranSuratResource\Pages;

use App\Filament\Resources\PenomoranSuratResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePenomoranSurat extends CreateRecord
{
    protected static string $resource = PenomoranSuratResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
