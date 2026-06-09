<?php

namespace App\Filament\Widgets;

use App\Models\PermohonanSurat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    public static function canView(): bool
    {
        $user = auth()->user();

        // Widget HANYA muncul jika role BUKAN 'Dosen'
        // Atau lo bisa sebutkan role yang BOLEH melihatnya saja
        return in_array($user->role, [
            'Supervisor', 
            'Manager', 
            'Wakil_Dekan', 
            'Dekan', 
            'Operator_Surat',
            'Operator_Nomor'  // Tambahkan role OCS/Operator lo di sini
        ]);
    }
    protected function getStats(): array
    {
        $user = auth()->user();
        $query = PermohonanSurat::query();

        // 1. Filter status berdasarkan siapa yang login
        match ($user->role) {
            // Saran gue: Hapus 'Draft' biar angkanya akurat murni kerjaan OCS. 
            // Tapi kalau pembimbing lo minta 'Draft' tetep dihitung, masukin lagi aja bray.
            'Operator_Surat' => $query->whereIn('status_terakhir', ['Draft','Revisi OCS', 'Proses Verifikasi']),
            'Supervisor'     => $query->where('status_terakhir', 'Terverifikasi'),
            'Manager'        => $query->where('status_terakhir', 'Disetujui_Supervisor'),
            'Wakil_Dekan'    => $query->where('status_terakhir', 'Disetujui_Manager'),
            'Dekan'          => $query->where('status_terakhir', 'Disetujui_Wakil_Dekan'),
            'Operator_Nomor' => $query->where('status_terakhir', 'Selesai_Pimpinan'),
            default          => $query->whereRaw('1 = 0'),
        };

        $penelitian = (clone $query)->whereIn('config_id', [1, 4, 5])->count();
        $narasumber = (clone $query)->where('config_id', 2)->count(); 
        $penunjang  = (clone $query)->where('config_id', 3)->count(); 

        return [
            Stat::make('Surat Penelitian', $penelitian)
                ->description('Permohonan Surat')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'), 

            Stat::make('Surat Narasumber', $narasumber)
                ->description('Permohonan Surat')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('danger'), 

            Stat::make('Surat Penunjang', $penunjang)
                ->description('Permohonan Surat')
                ->descriptionIcon('heroicon-m-beaker')
                ->color('primary'), 
        ];
    }
}