<?php

namespace App\Filament\Resources\PenomoranSuratResource\Pages;

use App\Filament\Resources\PenomoranSuratResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenomoranSurat extends EditRecord
{
    protected static string $resource = PenomoranSuratResource::class;

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
