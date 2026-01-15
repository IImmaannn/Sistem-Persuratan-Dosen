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

                Tables\Columns\TextColumn::make('jenisSurat.nama_jenis')
                    ->label('Perihal') // [cite: 242]
                    ->searchable(), // Fitur Search sesuai wireframe 

                Tables\Columns\TextColumn::make('keterangan_esai')
                    ->label('Keterangan') // [cite: 244]
                    ->limit(100),
            ]);
    }
}