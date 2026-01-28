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
				                Forms\Components\TextInput::make('kolom_2')
                                    ->label('e-ISSN')
                                    ->required()
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'penelitian'),

                                Forms\Components\TextInput::make('kolom_3')
                                    ->label('Judul Penelitian')
                                    ->required()
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'penelitian'),

                                Forms\Components\TextInput::make('kolom_4')
                                    ->label('Link Jurnal')
                                    ->url()
                                    ->required()
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'penelitian'),                                        
                                Forms\Components\TextInput::make('kolom_1_penunjang')
                                    ->label('Nama Kegiatan')
                                    ->required()
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'penunjang')
                                    ->statePath('kolom_1'),

                                Forms\Components\DatePicker::make('kolom_2_penunjang')
                                    ->label('Tanggal Kegiatan')
                                    ->required()
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'penunjang')
                                    ->statePath('kolom_2'),

                                Forms\Components\TextInput::make('kolom_1_nara')
                                    ->label('Nama Kegiatan')
                                    ->required()
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'narasumber')
                                    ->statePath('kolom_1'),
				                Forms\Components\TextInput::make('kolom_2_nara')
                                    ->label('Penyelenggara')
                                    ->required()
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'narasumber')
                                    ->statePath('kolom_2'),

                                Forms\Components\TextInput::make('kolom_3_nara')
                                    ->label('Tempat Kegiatan')
                                    ->required()
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'narasumber')
                                    ->statePath('kolom_3'),

                                Forms\Components\DatePicker::make('kolom_4_nara')
                                    ->label('Tanggal Kegiatan')
                                    ->required()
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'narasumber')
                                    ->statePath('kolom_4'),

                                Forms\Components\Textarea::make('kolom_5_nara')
                                    ->label('Keterangan')
                                    ->rows(3)
                                    ->visible(fn (Get $get) => $get('../memori_tipe') === 'narasumber')
                                    ->statePath('kolom_5'),
                                
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
            Tables\Actions\EditAction::make('verifikasi')
                ->label('Verifikasi')
                ->icon('heroicon-o-check-badge')
                ->color('primary')
                ->modalHeading('Verifikasi Permohonan Surat')
                ->modalWidth('4xl')
                // 1. KITA ISI FORM MODAL DENGAN DATA DOSEN & DETAIL
                ->form([
                    Grid::make(2)
                        ->schema([
                            // TextInput::make('nama_dosen')->disabled(), // Ambil dari tabel permohonan
                            // TextInput::make('nip')->disabled(),
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
                            // Tampilkan kolom sesuai jenis surat (Logika Match)
                            TextInput::make('detail_1')
                                ->label(fn ($record) => match($record->config_id) {
                                    1 => 'Nama Jurnal', // Penelitian
                                    2 => 'Nama Kegiatan', // Narasumber
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
                // 2. KITA TARIK DATA DARI DATABASE KE MODAL
                ->fillForm(function ($record) {
                    $detail = $record->keteranganEssai; // Relasi yang kita buat kemarin
                    return [
                        'nama_dosen' => $record->user->name,
                        'nip' => $record->user->profile?->nip,
                        // Mapping data dari kolom_1, kolom_2, dsb ke form modal
                        'detail_1' => $detail?->kolom_1,
                        'detail_2' => $detail?->kolom_2,
                        'detail_3' => match($record->config_id) {
                            1 => $detail?->kolom_3, // Isinya "half marathon" atau "bukit nevada"
                            3 => $detail?->kolom_5,
                            default => $detail?->kolom_3,
                        },
                    ];
                })
                // 3. KITA SIMPAN HASIL VERIFIKASI
                ->action(function (array $data, $record): void {
                    $record->update([
                        'status_terakhir' => $data['status_terakhir'],
                        // Pastikan lo punya kolom catatan_operator di tabel permohonan_surats
                        'nomor_surat' => $data['status_terakhir'] === 'Selesai' ? 'NOMOR/OTOMATIS/2026' : null,
                    ]);

                    // Notification biar keren
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