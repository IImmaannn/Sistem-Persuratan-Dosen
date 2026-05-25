<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\PermohonanSurat;

class SuratSelesaiDinomori extends BaseWidget
{
    // Bikin tabelnya membentang penuh (full width)
    protected int | string | array $columnSpan = 'full';

    // Kasih judul tabelnya
    protected static ?string $heading = 'Riwayat Surat Telah Dinomori (LIFO)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // LOGIKA LIFO & SUDAH DINOMORI
                PermohonanSurat::whereNotNull('nomor_surat')
                    ->where('status_terakhir', 'Surat_Terbit') // Pastikan statusnya udah terbit
                    ->orderBy('updated_at', 'desc') // LIFO: Yang paling baru di-update ada di atas!
            )
            ->columns([
                // Sesuaikan 'created_at' atau tanggal pengajuan lo
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date(),
                
                // Asumsi relasi user
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama'),
                
                // Asumsi relasi profil buat NIP
                Tables\Columns\TextColumn::make('user.profile.nip')
                    ->label('NIP'),
                
                // Asumsi nama perihal
                Tables\Columns\TextColumn::make('config.value')
                    ->label('Perihal'),

                // 🔥 INI PENGGANTI KOLOM KETERANGAN
                Tables\Columns\TextColumn::make('nomor_surat')
                    ->label('Nomor Surat')
                    ->badge() // Biar tampilannya keren kayak tombol
                    ->color('success')
                    ->searchable()
                    ->copyable(), // Biar OPS gampang kalau mau copy nomor suratnya
            ]);
    }
    public static function canView(): bool
    {
        // Widget ini HANYA boleh dilihat oleh Dosen
        return auth()->user()->role === 'Operator_Nomor'; 
    }
}