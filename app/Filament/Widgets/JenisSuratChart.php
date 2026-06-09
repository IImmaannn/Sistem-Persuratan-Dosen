<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\PermohonanSurat;
use Illuminate\Support\Facades\DB; 

class JenisSuratChart extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Surat per Jenis';
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'Admin']);
    }

    protected function getData(): array
    {
        // 1. Grouping berdasarkan config_id, dan load relasi 'config'
        $dataSurat = PermohonanSurat::with('config') 
            ->select('config_id', DB::raw('count(*) as total'))
            ->groupBy('config_id')
            ->get();

        $labels = [];
        $totals = [];

        foreach ($dataSurat as $item) {
            $namaJenis = $item->config ? $item->config->value : 'Tidak Diketahui'; 
            
            $labels[] = $namaJenis;
            $totals[] = $item->total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Surat',
                    'data' => $totals,
                    'backgroundColor' => ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}