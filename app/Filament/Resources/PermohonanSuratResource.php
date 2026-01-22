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
        // Ambil type dari request (untuk tampilan awal)
        $type = request()->query('type');

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
                        
                        // === BAGIAN LOGIKA CONFIG_ID (Teknik Pecah Jalur) ===
                        
                        // JALUR 1: Jika Surat PENELITIAN (Dosen Milih Sendiri)
                        Forms\Components\Select::make('config_id_penelitian')
                            ->label('Jenis Output Penelitian')
                            ->options(\App\Models\Config::where('kategori', 'jenis_penelitian')->pluck('value', 'id'))
                            ->required()
                            // Hanya muncul & aktif jika memori mencatat 'penelitian'
                            ->visible(fn (\Filament\Forms\Get $get) => $get('memori_tipe') === 'penelitian')
                            ->statePath('config_id'), // SIMPAN KE KOLOM config_id

                        // JALUR 2: Jika Surat PENUNJANG (Otomatis ID 3)
                        Forms\Components\Hidden::make('config_id_penunjang')
                            ->default(3) // ID 3 = Penunjang
                            ->visible(fn (\Filament\Forms\Get $get) => $get('memori_tipe') === 'penunjang')
                            ->statePath('config_id'), // SIMPAN KE KOLOM config_id

                        // JALUR 3: Jika Surat NARASUMBER (Otomatis ID 2)
                        Forms\Components\Hidden::make('config_id_narasumber')
                            ->default(2) // ID 2 = Narasumber
                            ->visible(fn (\Filament\Forms\Get $get) => $get('memori_tipe') === 'narasumber')
                            ->statePath('config_id'), // SIMPAN KE KOLOM config_id

                        // === BAGIAN DETAIL KETERANGAN ESAI ===

                        Forms\Components\Group::make()
                            ->relationship('keteranganEssai')
                            ->schema([
                                // MEMORI TIPE (Penyimpan Status Surat)
                                Forms\Components\Hidden::make('memori_tipe')
                                    ->default(fn() => request()->query('type'))
                                    ->dehydrated(false), // Tidak disimpan ke DB

                                // --- BAGIAN PENELITIAN ---
                                Forms\Components\TextInput::make('kolom_1')
                                    ->label('Nama Jurnal')
                                    ->required()
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('memori_tipe') === 'penelitian'),
                                    
                                Forms\Components\TextInput::make('kolom_2')
                                    ->label('e-ISSN')
                                    ->required()
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('memori_tipe') === 'penelitian'),

                                Forms\Components\TextInput::make('kolom_3')
                                    ->label('Judul Penelitian')
                                    ->required()
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('memori_tipe') === 'penelitian'),

                                Forms\Components\TextInput::make('kolom_4')
                                    ->label('Link Jurnal')
                                    ->url()
                                    ->required()
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('memori_tipe') === 'penelitian'),

                                // --- BAGIAN PENUNJANG ---
                                Forms\Components\TextInput::make('kolom_5_penunjang')
                                    ->label('Nama Kegiatan')
                                    ->required()
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('memori_tipe') === 'penunjang')
                                    ->statePath('kolom_5'), // Simpan ke kolom_5

                                Forms\Components\DatePicker::make('kolom_6_penunjang')
                                    ->label('Tanggal Kegiatan')
                                    ->required()
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('memori_tipe') === 'penunjang')
                                    ->statePath('kolom_6'),

                                // --- BAGIAN NARASUMBER ---
                                Forms\Components\TextInput::make('kolom_1_nara')
                                    ->label('Nama Kegiatan')
                                    ->required()
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('memori_tipe') === 'narasumber')
                                    ->statePath('kolom_1'), // Simpan ke kolom_1

                                Forms\Components\TextInput::make('kolom_2_nara')
                                    ->label('Penyelenggara')
                                    ->required()
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('memori_tipe') === 'narasumber')
                                    ->statePath('kolom_2'),

                                Forms\Components\TextInput::make('kolom_3_nara')
                                    ->label('Tempat Kegiatan')
                                    ->required()
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('memori_tipe') === 'narasumber')
                                    ->statePath('kolom_3'),

                                Forms\Components\DatePicker::make('kolom_4_nara')
                                    ->label('Tanggal Kegiatan')
                                    ->required()
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('memori_tipe') === 'narasumber')
                                    ->statePath('kolom_4'),

                                Forms\Components\Textarea::make('kolom_5_nara')
                                    ->label('Keterangan')
                                    ->rows(3)
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('memori_tipe') === 'narasumber')
                                    ->statePath('kolom_5'),
                            ]),
                    ]),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Perihal mengambil dari relasi config
                TextColumn::make('config.value')
                    ->label('Perihal'),

                // Menampilkan status dengan badge
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
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }
    public static function canViewAny(): bool
    {
        // Menu ini CUMA boleh dilihat sama Dosen
        return auth()->user()->role === 'Dosen';
    }
}

