<?php

namespace App\Filament\Resources;

use Filament\Forms\Get;
use App\Filament\Resources\PermohonanSuratResource\Pages;
use App\Models\PermohonanSurat;
use App\Models\Config;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class PermohonanSuratResource extends Resource
{
    protected static ?string $model = PermohonanSurat::class;

    protected static ?string $navigationLabel = 'Permohonan Surat';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 1. DATA DOSEN (Tetap Sama)
                Forms\Components\Section::make('Data Dosen')
                    ->description('Informasi otomatis dari profil Anda')
                    ->schema([
                        Forms\Components\TextInput::make('nama_dosen')
                            ->default(fn () => auth()->user()->name)
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('nip')
                            ->default(fn () => auth()->user()->profile?->nip)
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),
                    
                // 2. DETAIL PERMOHONAN
                Forms\Components\Section::make('Detail Permohonan')
                    ->schema([
                        // PENTING: Memori tipe ditaruh di sini agar bisa diakses semua
                        Forms\Components\Hidden::make('memori_tipe')
                            ->default(fn() => request()->query('type'))
                            ->live()
                            ->afterStateHydrated(fn($state, $set) => $set('memori_tipe', request()->query('type') ?? $state))
                            ->dehydrated(false),

                        // === INTEGRASI LOGIKA CONFIG_ID BARU ===
                        
                        // A. HIDDEN FIELD TUNGGAL (Si Penampung ID Asli ke Database)
                        Forms\Components\Hidden::make('config_id')
                            ->required()
                            ->dehydrated(true),

                        // B. UI SELECT (Hanya muncul untuk Penelitian)
                        Forms\Components\Select::make('ui_penelitian')
                            ->label('Jenis Output Penelitian')
                            ->options(\App\Models\Config::where('kategori', 'jenis_penelitian')->pluck('value', 'id'))
                            ->visible(fn (Get $get) => $get('memori_tipe') === 'penelitian')
                            ->live()
                            // Setiap dosen milih di sini, nilainya dilempar ke config_id yang asli
                            ->afterStateUpdated(fn ($state, $set) => $set('config_id', $state))
                            ->required(fn (Get $get) => $get('memori_tipe') === 'penelitian'),

                        // C. LOGIKA OTOMATIS (Untuk Penunjang & Narasumber)
                        Forms\Components\Placeholder::make('auto_id_trigger')
                            ->hidden()
                            ->afterStateHydrated(function ($set, $get) {
                                $tipe = request()->query('type') ?? $get('memori_tipe');
                                // ID 3 = Penunjang, ID 2 = Narasumber
                                if ($tipe === 'penunjang') $set('config_id', 3);
                                if ($tipe === 'narasumber') $set('config_id', 2);
                            }),

                        // === DETAIL KETERANGAN (Jalur Relasi ke keterangan_essais) ===
                        Forms\Components\Group::make()
                            ->relationship('keteranganEssai')
                            ->schema([
                                // PENTING: Gunakan ../ karena memori_tipe ada di luar Group ini
                                Forms\Components\TextInput::make('kolom_1')
                                    ->label('Nama Jurnal')
                                    ->required()
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'penelitian'),
                                    
                                Forms\Components\TextInput::make('kolom_5_penunjang')
                                    ->label('Nama Kegiatan')
                                    ->required()
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'penunjang')
                                    ->statePath('kolom_5'),

                                Forms\Components\DatePicker::make('kolom_6_penunjang')
                                    ->label('Tanggal Kegiatan')
                                    ->required()
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'penunjang')
                                    ->statePath('kolom_6'),

                                Forms\Components\TextInput::make('kolom_1_nara')
                                    ->label('Nama Kegiatan')
                                    ->required()
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'narasumber')
                                    ->statePath('kolom_1'),
                                
                                // ... Tambahkan kolom narasumber lainnya jika perlu
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('config.value')->label('Perihal'),
                TextColumn::make('status_terakhir')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Proses Verifikasi' => 'warning',
                        'Selesai' => 'success',
                        'Ditolak' => 'danger',
                        default => 'secondary',
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\SelectionPermohonan::route('/'),
            'create' => Pages\CreatePermohonanSurat::route('/create'),
            'edit' => Pages\EditPermohonanSurat::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'Dosen';
    }
}