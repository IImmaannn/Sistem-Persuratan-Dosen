<?php

namespace App\Filament\Widgets;

use App\Models\PermohonanSurat;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StatusPermohonanWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Status'; // Sesuai label wireframe 

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PermohonanSurat::query()
                    ->where('user_id', auth()->id())
                    ->whereIn('status_terakhir', ['Draft', 'Proses Verifikasi']) // Surat aktif [cite: 75]
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal') // [cite: 237]
                    ->date(),

               // PERBAIKAN: Ubah dari jenisSurat.nama_jenis ke config.value
                Tables\Columns\TextColumn::make('config.value')
                    ->label('Perihal'), // Tetap pakai label Prihal sesuai maumu

                Tables\Columns\TextColumn::make('status_terakhir')
                    ->label('Status') // 
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Proses Verifikasi' => 'warning',
                        default => 'secondary',
                    }),
            ]);
    }
}