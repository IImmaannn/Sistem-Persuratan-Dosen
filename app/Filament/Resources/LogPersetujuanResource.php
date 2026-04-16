<?php

namespace App\Filament\Resources;

use App\Models\LogPersetujuan;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\LogPersetujuanResource\Pages;

class LogPersetujuanResource extends Resource
{
    protected static ?string $model = LogPersetujuan::class;

    // Label di Sidebar
    protected static ?string $navigationLabel = 'Log Aktivitas';
    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?int $navigationSort = 2; // Muncul di bawah menu Dashboard Admin

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 1. Waktu Kejadian
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                // 2. Info Surat (Nama Dosen Pengaju & Perihal)
                TextColumn::make('permohonan.user.name')
                    ->label('Dosen Pengaju')
                    ->searchable(),

                TextColumn::make('permohonan.config.value')
                    ->label('Jenis Surat'),

                // 3. Aktor (Pimpinan yang melakukan aksi)
                TextColumn::make('user.name')
                    ->label('Aktor (Pimpinan)')
                    ->searchable()
                    ->description(fn (LogPersetujuan $record): string => "Role: {$record->user->role}"),

                // 4. Aksi yang Dilakukan
                TextColumn::make('status_aksi')
                    ->label('Aksi/Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Revisi' => 'danger',
                        'Setuju' => 'success',
                        default => 'info',
                    }),

                // 5. Catatan/Alasan
                TextColumn::make('catatan')
                    ->label('Catatan/Alasan')
                    ->wrap()
                    ->limit(50),
            ])
            ->defaultSort('created_at', 'desc') // Log terbaru paling atas
            ->filters([
                // Lo bisa nambahin filter berdasarkan pimpinan di sini nanti
            ])
            ->actions([
                // Cukup ViewAction saja, log tidak boleh di-Edit atau Delete
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLogPersetujuans::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Hanya Admin yang bisa akses menu ini
        return auth()->user()->role === 'Admin';
    }
}