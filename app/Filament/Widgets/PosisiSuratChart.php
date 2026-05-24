<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\PermohonanSurat;

class PosisiSuratChart extends ChartWidget
{
    protected static ?string $heading = 'Posisi Surat Saat Ini';
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'Admin']);
    }

    protected function getData(): array
    {
        // 🔥 PENTING: Ganti string 'Menunggu_...' di bawah ini dengan isi status_terakhir 
        // yang bener-bener lo pake di database/kodingan lo ya!
        
        $ocs = PermohonanSurat::whereIn('status_terakhir', ['Menunggu_Verifikasi', 'Revisi_Operator'])->count();
        $supervisor = PermohonanSurat::where('status_terakhir', 'Terverifikasi')->count();
        $manager = PermohonanSurat::where('status_terakhir', 'Disetujui_Supervisor')->count();
        $wadek = PermohonanSurat::where('status_terakhir', 'Disetujui_Manager')->count();
        $dekan = PermohonanSurat::where('status_terakhir', 'Disetujui_Wakil_Dekan')->count();
        $ops = PermohonanSurat::where('status_terakhir', 'Selesai_Pimpinan')->count(); // Surat yg nunggu dinomorin

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Surat Mengantri',
                    'data' => [$ocs, $supervisor, $manager, $wadek, $dekan, $ops],
                    // Warna setiap batang
                    'backgroundColor' => ['#f59e0b', '#3b82f6', '#8b5cf6', '#ec4899', '#ef4444', '#10b981'],
                ],
            ],
            // Label di bawah grafik batang
            'labels' => ['OCS (Cek Berkas)', 'Supervisor', 'Manager', 'Wakil Dekan', 'Dekan', 'OPS (Penomoran)'],
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Grafik bentuk batang
    }
}