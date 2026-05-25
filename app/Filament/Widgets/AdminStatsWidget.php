<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\PermohonanSurat;

class AdminStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        // Ngecek lewat sistem role Spatie (Filament Shield)
        return auth()->user()->hasAnyRole(['super_admin', 'Admin']);
    }
    protected function getStats(): array
    {
        return [
            Stat::make('Total Pengguna Sistem', User::count())
                ->description('Seluruh user yang terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            
            Stat::make('Total Permohonan Surat', PermohonanSurat::count())
                ->description('Seluruh permohonan dari dosen')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Surat Selesai (Terbit)', PermohonanSurat::where('status_terakhir', 'Surat_Terbit')->count())
                ->description('Surat resmi yang sudah memiliki nomor')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}