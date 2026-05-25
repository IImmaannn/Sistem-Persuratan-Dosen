<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\PermohonanSurat;
use App\Filament\Resources\PenomoranSuratResource;

class AntreanPenomoranWidget extends BaseWidget
{
    protected static ?int $sort = 2; // Urutan ke-2 (di bawah kotak statistik)
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Antrean Surat (Belum Dinomori)';

    // Cuma OPS yang boleh liat tabel ini di Dashboard
    public static function canView(): bool
    {
        return auth()->user()->hasRole('Operator_Nomor');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PermohonanSurat::where('status_terakhir', 'Selesai_Pimpinan')
                    ->where(function ($q) {
                        $q->whereNull('nomor_surat')->orWhere('nomor_surat', '');
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Tanggal')->date()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Nama')->searchable(),
                Tables\Columns\TextColumn::make('user.profile.nip')->label('NIP'),
                Tables\Columns\TextColumn::make('config.value')->label('Perihal'),
                Tables\Columns\TextColumn::make('keteranganEssai.kolom_1')->label('Keterangan')->limit(30),
            ])
            ->actions([
                // Bikin tombol manual buat nge-link ke halaman Edit Penomoran
                Tables\Actions\Action::make('beri_nomor')
                    ->label('Beri Nomor')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->url(fn (PermohonanSurat $record): string => PenomoranSuratResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}