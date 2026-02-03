<?php

namespace App\Filament\Resources\PersetujuanSuratResource\Pages;

use App\Filament\Resources\PersetujuanSuratResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePersetujuanSurat extends CreateRecord
{
    protected static string $resource = PersetujuanSuratResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
