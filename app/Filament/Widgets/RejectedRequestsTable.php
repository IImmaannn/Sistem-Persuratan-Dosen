<?php

namespace App\Filament\Widgets;

use App\Models\PermohonanSurat;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\VerifikasiPermohonanResource;
use Filament\Widgets\TableWidget as BaseWidget;

class RejectedRequestsTable extends BaseWidget
{
    protected static ?string $heading = 'Surat yang Perlu Direvisi (Ditolak Pimpinan)';
    
    // Biar tabel ini memanjang penuh
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Ambil surat yang statusnya dikembalikan ke OCS
                PermohonanSurat::query()->where('status_terakhir', 'Revisi OCS')
            )
            ->columns([
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Tanggal Ditolak')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Nama Dosen'),
                Tables\Columns\TextColumn::make('config.value')->label('Perihal'),
                
                // AMBIL ALASAN PENOLAKAN TERAKHIR DARI LOG
                Tables\Columns\TextColumn::make('latestLog.catatan')
                    ->label('Alasan Penolakan')
                    ->wrap()
                    ->color('danger'),
            ])
            ->actions([
                // Tombol untuk langsung edit/benerin
                Tables\Actions\Action::make('perbaiki')
                    ->label('Perbaiki')
                    ->url(fn (PermohonanSurat $record): string => 
                       VerifikasiPermohonanResource::getUrl('edit', ['record' => $record])
                    )
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning'),
            ]);
    }
    public static function canView(): bool
    {
        // Widget ini HANYA boleh dilihat oleh OCS
        // Dan TIDAK AKAN muncul untuk Admin, Dosen, atau Pimpinan
        return auth()->user()->role === 'Operator_Surat'; 
    }

}