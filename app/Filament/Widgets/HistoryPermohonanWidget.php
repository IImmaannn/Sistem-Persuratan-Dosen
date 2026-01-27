<?php

namespace App\Filament\Widgets;

use App\Models\PermohonanSurat;
use App\Filament\Resources\PermohonanSuratResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class HistoryPermohonanWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'History'; // Sesuai label wireframe 

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PermohonanSurat::query()
                    ->where('user_id', auth()->id())
                    // ->whereIn('status_terakhir', ['Selesai', 'Ditolak']) // Hanya data final 
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal') // [cite: 241]
                    ->date(),

                // Tables\Columns\TextColumn::make('jenisSurat.nama_jenis')
                //     ->label('Perihal') // [cite: 242]
                //     ->searchable(), // Fitur Search sesuai wireframe 

                // Tables\Columns\TextColumn::make('keterangan_esai')
                //     ->label('Keterangan') // [cite: 244]
                //     ->limit(100),
                Tables\Columns\TextColumn::make('config.value')
                    ->label('Perihal')
                    ->searchable(),
                // PERBAIKAN: Ambil data dari relasi 'keteranganEssai' secara dinamis
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->getStateUsing(function ($record) {
                        $detail = $record->keteranganEssai;
                        if (!$detail) return 'Tidak ada detail';

                        // Ambil kolom yang sesuai berdasarkan jenis surat
                        return match ($record->config_id) {
                            1, 4, 5 => $detail->kolom_3, // Penelitian: Judul Penelitian
                            3 => $detail->kolom_1, // Penunjang: Nama Kegiatan
                            2 => $detail->kolom_1, // Narasumber: Nama Kegiatan
                            default => '-',
                        };
                })
                ->limit(100),
         ]);
    }
}