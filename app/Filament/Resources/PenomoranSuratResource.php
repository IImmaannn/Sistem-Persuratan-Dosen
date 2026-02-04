<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenomoranSuratResource\Pages;
use App\Filament\Resources\PenomoranSuratResource\RelationManagers;
use App\Models\PermohonanSurat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PenomoranSuratResource extends Resource
{
    protected static ?string $model = PermohonanSurat::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Penomoran Surat';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 1. INFORMASI SURAT (Read-Only)
            Forms\Components\Section::make('Detail Permohonan')
                ->description('Data ini tidak dapat diubah oleh Operator Penomoran.')
                ->schema([
                    Forms\Components\TextInput::make('user.name')
                    ->label('Nama Dosen')
                    ->disabled()
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($component, $record) {
                        // Ambil nama dari relasi user
                        $component->state($record->user?->name ?? '-');
                    }),
                    Forms\Components\TextInput::make('config.value')
                    ->label('Jenis Surat')
                    ->disabled()
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($component, $record) {
                        // Ambil nama dari relasi user
                        $component->state($record->config?->value ?? '-');
                    }),
                    Forms\Components\Textarea::make('keteranganEssai.kolom_1')
                    ->label(fn ($record) => match ($record?->config_id) {
                        1, 4 ,5 => 'Nama Jurnal',
                        2, 3 => 'Nama Kegiatan',
                        default => 'Detail 1',
                    })
                    ->disabled()
                    ->columnSpanFull()
                    ->afterStateHydrated(function ($component, $record) {
                        // Ambil nama dari relasi user
                        $component->state($record->keteranganEssai?->kolom_1 ?? '-');
                    }),
                    Forms\Components\Textarea::make('keteranganEssai.kolom_2')
                    ->label(fn ($record) => match ($record?->config_id) {
                        1, 4, 5=> 'e-ISSN',
                        2 => 'Penyelenggara',
                        3 => 'Tanggal Kegiatan', // Sesuai isian dosen
                        default => 'Detail 2',
                    })
                    ->disabled()
                    ->visible(fn ($record) => in_array($record?->config_id, [1, 2, 3, 4, 5]))
                    ->columnSpanFull()
                    ->afterStateHydrated(function ($component, $record) {
                        // Ambil nama dari relasi user
                        $component->state($record->keteranganEssai?->kolom_2 ?? '-');
                    }),
                    Forms\Components\Textarea::make('keteranganEssai.kolom_3')
                    ->label(fn ($record) => match ($record?->config_id) {
                        1, 4, 5 => 'Judul Penelitian',
                        2 => 'Tempat Kegiatan',
                        default => 'Detail 3',
                    })
                    ->disabled()
                    ->visible(fn ($record) => in_array($record?->config_id, [1, 2, 4, 5]))
                    ->columnSpanFull()
                    ->afterStateHydrated(function ($component, $record) {
                        // Ambil nama dari relasi user
                        $component->state($record->keteranganEssai?->kolom_3 ?? '-');
                    }),
                    Forms\Components\Textarea::make('keteranganEssai.kolom_4')
                    ->label(fn ($record) => match ($record?->config_id) {
                        1, 4, 5 => 'Link Jurnal',
                        2 => 'Tanggal Kegiatan',
                        default => 'Detail 4',
                    })
                    ->disabled()
                    ->visible(fn ($record) => in_array($record?->config_id, [1, 2, 4, 5]))
                    ->columnSpanFull()
                    ->afterStateHydrated(function ($component, $record) {
                        // Ambil nama dari relasi user
                        $component->state($record->keteranganEssai?->kolom_4 ?? '-');
                    }),
                    Forms\Components\Textarea::make('keteranganEssai.kolom_5')
                    ->label(fn ($record) => match ($record?->config_id) {
                        2 => 'Nama Kegiatan / Keterangan',
                        default => 'Keterangan Tambahan',
                    })
                    ->disabled()
                    ->visible(fn ($record) => in_array($record?->config_id, [2]))
                    ->columnSpanFull()
                    ->afterStateHydrated(function ($component, $record) {
                        // Ambil nama dari relasi user
                        $component->state($record->keteranganEssai?->kolom_5 ?? '-');
                    }),
                ])->columns(2),

            // 2. INPUT NOMOR SURAT (Tugas Utama OPS)
            Forms\Components\Section::make('Penomoran Resmi')
                ->schema([
                    Forms\Components\TextInput::make('no_surat') // Pastikan ada kolom ini di tabel permohonan_surats
                        ->label('Nomor Surat Resmi')
                        ->placeholder('Contoh: 123/UN7.F3.3/HK/2026')
                        ->required()
                        ->unique(ignoreRecord: true), // Biar gak ada nomor surat ganda
                    
                    Forms\Components\Hidden::make('status_terakhir')
                        ->default('Surat_Terbit'), // Otomatis selesai setelah disimpan
                ])
                //
            ]);
    }
    public static function canViewAny(): bool
    {
        // Sesuaikan nama role-nya di database lo, misalnya 'Operator_Penomoran'
        return auth()->user()->role === 'Operator_Nomor';
    }

    public static function getEloquentQuery(): Builder
    {
        // OPS hanya melihat surat yang sudah disetujui Dekan dan belum diberi nomor
        return parent::getEloquentQuery()
            ->with(['keteranganEssai', 'config'])
            ->where('status_terakhir', 'Selesai_Pimpinan');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                    Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Nama')->searchable(),
                Tables\Columns\TextColumn::make('user.profile.nip')->label('NIP'),
                Tables\Columns\TextColumn::make('config.value')->label('Perihal'),
                Tables\Columns\TextColumn::make('keteranganEssai.kolom_1')
                    ->label('Keterangan')
                    ->limit(30),
            ])

            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Beri Nomor')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success'),
                // Tables\Actions\DeleteAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenomoranSurats::route('/'),
            'create' => Pages\CreatePenomoranSurat::route('/create'),
            'edit' => Pages\EditPenomoranSurat::route('/{record}/edit'),
        ];
    }
}
