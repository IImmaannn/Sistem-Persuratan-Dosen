<?php

namespace App\Filament\Widgets;

use App\Models\PermohonanSurat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $query = PermohonanSurat::query();

        // 1. Filter status berdasarkan siapa yang login (Sama dengan tabel)
        match ($user->role) {
            'Supervisor' => $query->where('status_terakhir', 'Terverifikasi'),
            'Manager'    => $query->where('status_terakhir', 'Disetujui_Supervisor'),
            'Wakil_Dekan'=> $query->where('status_terakhir', 'Disetujui_Manager'),
            'Dekan'      => $query->where('status_terakhir', 'Disetujui_Wakil_Dekan'),
            default      => $query->whereRaw('1 = 0'),
        };

        // 2. Hitung jumlah per kategori (ID Config sesuai database lo)
        // Contoh: 1 = Penelitian, 2 = Penunjang, 3 = Narasumber
        $penelitian = (clone $query)->whereIn('config_id', [1, 4, 5])->count();
        $narasumber = (clone $query)->where('config_id', 3)->count();
        $penunjang  = (clone $query)->where('config_id', 2)->count();

        return [
            Stat::make('Surat Penelitian', $penelitian)
                ->description('Menunggu Persetujuan')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'), // Warna Kuning

            Stat::make('Surat Narasumber', $narasumber)
                ->description('Menunggu Persetujuan')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('danger'), // Warna Pink/Merah

            Stat::make('Surat Penunjang', $penunjang)
                ->description('Menunggu Persetujuan')
                ->descriptionIcon('heroicon-m-beaker')
                ->color('primary'), // Warna Ungu/Biru
        ];
    }
}