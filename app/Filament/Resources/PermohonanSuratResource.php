<?php

namespace App\Filament\Resources;

use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Filament\Resources\PermohonanSuratResource\Pages;
use App\Models\PermohonanSurat;
use App\Models\Config;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
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
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                
                // State control tipe surat (Berada di Root Level)
                // === 1. TANGKAP PARAMETER DARI URL (JALAN TOL) ===
                Hidden::make('memori_tipe')
                    ->default(fn() => request()->query('jenis')) // 🔥 Ubah 'type' jadi 'jenis'
                    ->live()
                    ->afterStateHydrated(fn($state, $set) => $set('memori_tipe', request()->query('jenis') ?? $state)),

                // === 2. SET CONFIG_ID OTOMATIS ===
                Hidden::make('config_id')
                    ->default(match (request()->query('jenis')) {
                        'penelitian' => 1, // ID Config Surat Penelitian
                        'narasumber' => 3, // ID Config Surat Narasumber
                        'penunjang'  => 2, // ID Config Surat Penunjang
                        default => null,
                    })
                    ->required(),

                // 1. DATA DOSEN PENGAJU (Otomatis)
                Section::make('Data Dosen Pengaju')
                    ->description('Informasi otomatis dari profil Anda')
                    ->schema([
                        TextInput::make('nama_dosen')
                            ->default(fn () => auth()->user()->name)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('nip')
                            ->default(fn () => auth()->user()->profile?->nip)
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                // 2. LOGIKA UTAMA (GABUNGAN DATA ANGGOTA & DETAIL SURAT)
                Group::make()
                    ->relationship('keteranganEssai') // Bind ke tabel keterangan_essais
                    ->schema([
                        
                        // BAGIAN DATA DOSEN ANGGOTA (2 Kolom - Sesuai Request)
                        Section::make('Data Dosen Anggota')
                            ->description('Pilih dosen tambahan dari daftar yang tersedia')
                            ->schema([
                                Forms\Components\Repeater::make('anggota_tim')
                                    ->label('Data Dosen Anggota')
                                    ->schema([
                                        Select::make('user_id')
                                            ->label('Nama Dosen Anggota')
                                            ->placeholder('Pilih Nama Dosen...')
                                            ->options(User::where('role', 'Dosen')->get()->mapWithKeys(fn ($user) => [
                                                $user->id => "{$user->name} ({$user->email})"
                                            ]))
                                            ->searchable()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                $user = User::with('profile')->find($state);
                                                if ($user) {
                                                    $set('email', $user->email);
                                                    $set('nama', $user->name);
                                                    $set('nip', $user->profile?->nip ?? '-');
                                                    $set('pangkat', $user->profile?->golongan ?? '-');
                                                }
                                            }),
                                            // ->required(),

                                        TextInput::make('email')
                                            ->label('Email')
                                            ->readonly(),
                                            // ->required()

                                        // Data Hidden tersimpan otomatis ke JSON anggota_tim untuk isi surat
                                        Hidden::make('nama'),
                                        Hidden::make('nip'),
                                        Hidden::make('pangkat'),
                                    ])
                                    ->columns(2)
                                    ->addActionLabel('Tambah Dosen Lain')
                                    ->minItems(0),
                            ]),

                        // DETAIL ISIAN SURAT (Dinamis & Menggunakan ../memori_tipe)
                        Section::make('Detail Isian Surat')
                            ->schema([
                                
                                // === FORM JIKA JENISNYA: PENELITIAN (REVISI STRUKTUR DI SINI) ===
                                Select::make('ui_penelitian')
                                    ->label('Jenis Output Penelitian')
                                    ->placeholder('Pilih Jenis Penelitian...')
                                    ->options(Config::where('kategori', 'jenis_penelitian')->pluck('value', 'id'))
                                    ->live()
                                    ->dehydrated(false) // Mencegah bentrok dengan relasi tabel keterangan_essais
                                    ->afterStateHydrated(function ($set, $livewire) {
                                        if ($livewire->record) {
                                            $set('ui_penelitian', $livewire->record->config_id);
                                        }
                                    })
                                    ->afterStateUpdated(fn ($state, $set) => $set('../config_id', $state)) // Set ke config_id root level
                                    ->required(fn (Get $get) => $get('../memori_tipe') === 'penelitian')
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'penelitian')
                                    ->columnSpanFull(),

                                TextInput::make('kolom_1_penelitian')
                                    ->label('Nama Jurnal')
                                    ->statePath('kolom_1')
                                    ->required()
                                    ->visible(fn(Get $get) => $get('../memori_tipe') === 'penelitian')
                                    ->columnSpanFull(),

                                TextInput::make('kolom_2_penelitian')
                                    ->label('e-ISSN')
                                    ->statePath('kolom_2')
                                    ->required()
                                    ->visible(fn(Get $get) => $get('../memori_tipe') === 'penelitian')
                                    ->columnSpanFull(),

                                TextInput::make('kolom_3_penelitian')
                                    ->label('Judul Penelitian')
                                    ->statePath('kolom_3')
                                    ->required()
                                    ->visible(fn(Get $get) => $get('../memori_tipe') === 'penelitian')
                                    ->columnSpanFull(), // Judul Penelitian Berada di bawah e-ISSN dan Panjang

                                TextInput::make('kolom_4_penelitian')
                                    ->label('Link Jurnal')
                                    ->statePath('kolom_4')
                                    ->url()
                                    ->required()
                                    ->visible(fn(Get $get) => $get('../memori_tipe') === 'penelitian')
                                    ->columnSpanFull(),

                                // === FORM JIKA JENISNYA: PENUNJANG ===
                                TextInput::make('kolom_1_penunjang')
                                    ->label('Nama Kegiatan')
                                    ->statePath('kolom_1')
                                    ->required()
                                    ->visible(fn(Get $get) => $get('../memori_tipe') === 'penunjang')
                                    ->columnSpanFull(),
                                DatePicker::make('kolom_2_penunjang')
                                    ->label('Tanggal Kegiatan')
                                    ->statePath('kolom_2')
                                    ->native(false)
                                    ->required()
                                    ->columnSpanFull()
                                    ->visible(fn(Get $get) => $get('../memori_tipe') === 'penunjang'),

                                // === FORM JIKA JENISNYA: NARASUMBER ===
                                TextInput::make('kolom_1_narasumber')
                                    ->label('Nama Kegiatan')
                                    ->statePath('kolom_1')
                                    ->required()
                                    ->visible(fn(Get $get) => $get('../memori_tipe') === 'narasumber')
                                    ->columnSpanFull(),
                                TextInput::make('kolom_2_narasumber')
                                    ->label('Penyelenggara')
                                    ->statePath('kolom_2')
                                    ->required()
                                    ->visible(fn(Get $get) => $get('../memori_tipe') === 'narasumber')
                                    ->columnSpanFull(),
                                TextInput::make('kolom_3_narasumber')
                                    ->label('Tempat Kegiatan')
                                    ->statePath('kolom_3')
                                    ->required()
                                    ->visible(fn(Get $get) => $get('../memori_tipe') === 'narasumber')
                                    ->columnSpanFull(),
                                DatePicker::make('kolom_4_narasumber')
                                    ->label('Tanggal Kegiatan')
                                    ->statePath('kolom_4')
                                    ->native(false)
                                    ->required()
                                    ->visible(fn(Get $get) => $get('../memori_tipe') === 'narasumber')
                                    ->columnSpanFull(),
                                Textarea::make('kolom_5_narasumber')
                                    ->label('Keterangan Tambahan')
                                    ->statePath('kolom_5')
                                    ->visible(fn(Get $get) => $get('../memori_tipe') === 'narasumber')
                                    ->columnSpanFull(),
                                    
                            ])->columns(2),
                    ])->columnSpanFull(),
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
                        'Menunggu Verifikasi OCS' => 'warning',
                        'Revisi OCS' => 'danger',
                        'Terverifikasi', 'Disetujui Supervisor', 'Disetujui Manager', 'Disetujui Wakil Dekan' => 'gray',
                        'Selesai_Pimpinan', 'Surat_Terbit' => 'success',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Draft' => 'Draf (Belum Dikirim)',
                        'Menunggu Verifikasi OCS' => 'Sedang Diverifikasi OCS',
                        'Revisi OCS' => 'Perlu Revisi (Cek Catatan)',
                        'Terverifikasi' => 'Proses di Supervisor',
                        'Disetujui Supervisor' => 'Proses di Manager',
                        'Disetujui Manager' => 'Proses di Wakil Dekan',
                        'Disetujui Wakil Dekan' => 'Proses di Dekan',
                        'Selesai Pimpinan' => 'Menunggu Penomoran Surat',
                        'Surat Terbit' => 'Selesai (Siap Download)',
                        default => $state,
                    }),
            ])
            ->actions([
                // === MODAL VERIFIKASI UTK OPERATOR (TETAP TERJAGA) ===
                Tables\Actions\EditAction::make('verifikasi')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->modalHeading('Verifikasi Permohonan Surat')
                    ->modalWidth('4xl')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nama_dosen')
                                    ->default(fn () => auth()->user()->name)
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('nip')
                                    ->default(fn () => auth()->user()->profile?->nip)
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                        
                        Section::make('Detail Isian Dosen')
                            ->description('Data di bawah ini adalah inputan dari Dosen')
                            ->schema([
                                TextInput::make('detail_1')
                                    ->label(fn ($record) => match($record->config_id) {
                                        1 => 'Nama Jurnal',
                                        2 => 'Nama Kegiatan',
                                        default => 'Info 1'
                                    })
                                    ->disabled(),
                                
                                TextInput::make('detail_2')
                                    ->label(fn ($record) => match($record->config_id) {
                                        1 => 'e-ISSN',
                                        2 => 'Penyelenggara',
                                        default => 'Info 2'
                                    })
                                    ->disabled(),

                                Textarea::make('detail_3')
                                    ->label('Judul / Keterangan')
                                    ->disabled()
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Keputusan Operator')
                            ->schema([
                                Select::make('status_terakhir')
                                    ->label('Hasil Verifikasi')
                                    ->options([
                                        'Selesai' => 'Setujui & Terbitkan',
                                        'Ditolak' => 'Tolak Permohonan',
                                    ])
                                    ->required()
                                    ->live(),
                                Textarea::make('catatan_operator')
                                    ->label('Alasan (Jika Ditolak)')
                                    ->visible(fn ($get) => $get('status_terakhir') === 'Ditolak')
                                    ->required(fn ($get) => $get('status_terakhir') === 'Ditolak'),
                            ]),
                    ])
                    ->fillForm(function ($record) {
                        $detail = $record->keteranganEssai;
                        return [
                            'nama_dosen' => $record->user->name,
                            'nip' => $record->user->profile?->nip,
                            'detail_1' => $detail?->kolom_1,
                            'detail_2' => $detail?->kolom_2,
                            'detail_3' => match($record->config_id) {
                                1 => $detail?->kolom_3,
                                3 => $detail?->kolom_5,
                                default => $detail?->kolom_3,
                            },
                        ];
                    })
                    ->action(function (array $data, $record): void {
                        $record->update([
                            'status_terakhir' => $data['status_terakhir'],
                            'nomor_surat' => $data['status_terakhir'] === 'Selesai' ? 'NOMOR/OTOMATIS/2026' : null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Status Berhasil Diperbarui')
                            ->success()
                            ->send();
                    }),
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