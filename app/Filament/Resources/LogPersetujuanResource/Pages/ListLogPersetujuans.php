<?php

namespace App\Filament\Resources\LogPersetujuanResource\Pages;

use App\Filament\Resources\LogPersetujuanResource;
use Filament\Resources\Pages\ListRecords;

class ListLogPersetujuans extends ListRecords
{
    protected static string $resource = LogPersetujuanResource::class;

    public function getTitle(): string 
    {
        return 'Riwayat Aktivitas Sistem';
    }
}