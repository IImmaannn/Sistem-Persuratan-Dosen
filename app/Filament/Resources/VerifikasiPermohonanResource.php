<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VerifikasiPermohonanResource\Pages;
use App\Models\PermohonanSurat;
use App\Filament\Resources\VerifikasiPermohonanResource\RelationManagers;
use App\Models\VerifikasiPermohonan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VerifikasiPermohonanResource extends Resource
{
    protected static ?string $model = PermohonanSurat::class;
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $slug = 'dashboard-operator';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 1. INFORMASI PENGAJU (Read-Only)
                Forms\Components\Section::make('Informasi Pengaju')
                    ->schema([
                        Forms\Components\TextInput::make('user.name')
                        ->label('Nama Dosen')
                        ->disabled()
                        ->dehydrated(false) // Gak usah disimpen, cuma tampil
                        ->afterStateHydrated(function ($component, $record) {
                            // Ambil nama dari relasi user
                            $component->state($record->user?->name ?? '-');
                        }), 
                        Forms\Components\TextInput::make('user.profile.nip')
                        ->label('NIP')
                        ->disabled()
                        ->dehydrated(false)
                        ->afterStateHydrated(function ($component, $record) {
                            // Ambil NIP dari relasi user -> profile
                            // Pastikan model User lo punya relasi 'profile' ya!
                            $component->state($record->user?->profile?->nip ?? '-');
                        }),
                    ])->columns(2),

                // 2. VERIFIKASI DETAIL ESAI (Konteks Tetap di PermohonanSurat)
                Forms\Components\Section::make('Verifikasi Detail Esai')
                    ->description('Label dan kolom muncul otomatis sesuai jenis surat') 
                    ->schema([
                        // KOLOM 1: Nama Jurnal / Kegiatan
                        // Gunakan dot notation 'keteranganEssai.kolom_1'
                        Forms\Components\TextInput::make('keteranganEssai.kolom_1')
                            ->label(fn ($record) => match ($record?->config_id) {
                                1, 4 ,5 => 'Nama Jurnal',
                                2, 3 => 'Nama Kegiatan',
                                default => 'Detail 1',
                            }) 
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_1)),

                        // KOLOM 2: e-ISSN / Penyelenggara / Tanggal
                        Forms\Components\TextInput::make('keteranganEssai.kolom_2')
                            ->label(fn ($record) => match ($record?->config_id) {
                                1, 4, 5=> 'e-ISSN',
                                2 => 'Penyelenggara',
                                3 => 'Tanggal Kegiatan', // Sesuai isian dosen
                                default => 'Detail 2',
                            })
                            // Sekarang visible() akan bekerja karena bisa baca config_id
                            ->visible(fn ($record) => in_array($record?->config_id, [1, 2, 3, 4, 5]))
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_2)),

                        // KOLOM 3: Judul / Tempat
                        Forms\Components\TextInput::make('keteranganEssai.kolom_3')
                            ->label(fn ($record) => match ($record?->config_id) {
                                1, 4, 5 => 'Judul Penelitian',
                                2 => 'Tempat Kegiatan',
                                default => 'Detail 3',
                            })
                            ->visible(fn ($record) => in_array($record?->config_id, [1, 2, 4, 5]))
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_3)),

                        Forms\Components\TextInput::make('keteranganEssai.kolom_4')
                            ->label(fn ($record) => match ($record?->config_id) {
                                1, 4, 5 => 'Link Jurnal',
                                2 => 'Tanggal Kegiatan',
                                default => 'Detail 3',
                            })
                            ->visible(fn ($record) => in_array($record?->config_id, [1, 2, 4, 5]))
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_4)),

                        // KOLOM 5: Keterangan / Nama Kegiatan Penunjang
                        Forms\Components\Textarea::make('keteranganEssai.kolom_5')
                            ->label(fn ($record) => match ($record?->config_id) {
                                2 => 'Nama Kegiatan / Keterangan',
                                default => 'Keterangan Tambahan',
                            })
                            ->visible(fn ($record) => in_array($record?->config_id, [2]))
                            ->columnSpanFull()
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_5))
                    ]),
                Forms\Components\Section::make('Catatan Penolakan Pimpinan')
                    ->description('Alasan mengapa pimpinan menolak permohonan ini sebelumnya.')
                    ->schema([
                        Forms\Components\Placeholder::make('alasan_terakhir')
                            ->label('Alasan Terakhir')
                            ->content(fn ($record) => 
                                $record->logPersetujuans() // Pastikan ada relasi ini di model
                                    ->where('status_aksi', 'Ditolak')
                                    ->latest()
                                    ->first()?->catatan ?? 'Belum ada catatan penolakan.'
                            )
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => $record->status_terakhir === 'Revisi OCS'),

                // 3. TINDAKAN OPERATOR
                Forms\Components\Section::make('Tindakan Operator')
                    ->schema([
                        Forms\Components\Select::make('status_terakhir')
                            ->label('Status Verifikasi Administrasi')
                            ->options([
                                'Proses Verifikasi' => 'Proses Verifikasi',
                                // 'Revisi' => 'Perlu Revisi',
                                'Terverifikasi' => 'Lanjut ke Pimpinan',
                            ])
                            ->required()
                            ->native(false),
                    ])
                
            ]);
    }

   public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 1. TANGGAL [cite: 262]
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y')
                    ->sortable(),


                // 2. NAMA (Dari Relasi User) [cite: 263]
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama')
                    ->searchable(),
                    // ->preload(false),

                // 3. NIP (Dari Relasi Profile Dosen melalui User) [cite: 264]
                Tables\Columns\TextColumn::make('user.profile.nip')
                    ->label('NIP')
                    ->copyable(),

                // 4. PERIHAL (Kategori Surat) [cite: 265]
                Tables\Columns\TextColumn::make('config.value')
                    ->label('Perihal'),

                // 5. KETERANGAN (Cuplikan isi esai kolom 1) [cite: 269]
                Tables\Columns\TextColumn::make('keteranganEssai.kolom_1')
                    ->label('Keterangan')
                    ->limit(30)
                    ->placeholder('Tidak ada detail'),
            ])
            ->filters([
                // FILTER PRIHAL sesuai wireframe [cite: 261]
                Tables\Filters\SelectFilter::make('config_id')
                    ->label('Prihal')
                    ->relationship('config', 'value', fn ($query) => 
                        $query->where('kategori', 'jenis_penelitian')
                            ->orWhere('key', 'penunjang')
                            ->orWhere('key', 'narasumber')
                    ),
            ])
            ->actions([
                // Operator bisa mengedit untuk verifikasi/revisi esai [cite: 8]
                Tables\Actions\EditAction::make()->label('Verifikasi'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canViewAny(): bool
    {
        // Hanya user dengan role 'Operator_Surat' yang bisa lihat halaman ini [cite: 60, 24]
        return auth()->user()->role === 'Operator_Surat';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVerifikasiPermohonans::route('/'),
            'create' => Pages\CreateVerifikasiPermohonan::route('/create'),
            'edit' => Pages\EditVerifikasiPermohonan::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['keteranganEssai', 'config'])
            ->whereIn('status_terakhir', ['Draft', 'Proses Verifikasi', 'Revisi OCS']); 
    }
}
