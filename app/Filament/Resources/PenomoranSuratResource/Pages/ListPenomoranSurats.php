<?php

namespace App\Filament\Resources\PenomoranSuratResource\Pages;

use App\Filament\Resources\PenomoranSuratResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Widgets\SuratSelesaiDinomori;

class ListPenomoranSurats extends ListRecords
{
    protected static string $resource = PenomoranSuratResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\CreateAction::make(),
    //     ];
    // }

    protected function getFooterWidgets(): array
    {
        return [
            SuratSelesaiDinomori::class,
        ];
    }
}
