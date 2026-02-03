<?php

namespace App\Filament\Resources\PersetujuanSuratResource\Pages;

use App\Filament\Resources\PersetujuanSuratResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPersetujuanSurats extends ListRecords
{
    protected static string $resource = PersetujuanSuratResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\CreateAction::make(),
    //     ];
    // }
}
