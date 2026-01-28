<?php

namespace App\Filament\Resources\VerifikasiPermohonanResource\Pages;

use App\Filament\Resources\VerifikasiPermohonanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVerifikasiPermohonans extends ListRecords
{
    protected static string $resource = VerifikasiPermohonanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
