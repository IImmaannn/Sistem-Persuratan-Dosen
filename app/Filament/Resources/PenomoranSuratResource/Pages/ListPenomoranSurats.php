<?php

namespace App\Filament\Resources\PenomoranSuratResource\Pages;

use App\Filament\Resources\PenomoranSuratResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenomoranSurats extends ListRecords
{
    protected static string $resource = PenomoranSuratResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\CreateAction::make(),
    //     ];
    // }
}
