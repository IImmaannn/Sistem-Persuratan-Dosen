<?php

namespace App\Filament\Resources\PersetujuanSuratResource\Pages;

use App\Filament\Resources\PersetujuanSuratResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPersetujuanSurats extends ListRecords
{
    protected static string $resource = PersetujuanSuratResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverview::class,
        ];
    }
}
