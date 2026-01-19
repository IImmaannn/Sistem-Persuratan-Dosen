<?php

namespace App\Filament\Resources;

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

    public static function form(Form $form): Form
    {
        $type = request()->query('type');

        return $form
            ->schema([
                // 1. DATA DOSEN (Informasi Statis)
                Section::make('Data Dosen')
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
                    ])
                    ->columns(2),

                // 2. DETAIL PERMOHONAN PENELITIAN (Hanya muncul jika klik tombol Hijau)
                Section::make('Detail Permohonan Penelitian')
                    ->visible($type === 'penelitian')
                    ->schema([
                        // Tanggal di baris tersendiri sesuai wireframe
                        DatePicker::make('created_at')
                            ->label('Tanggal')
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->columnSpanFull(),

                        // Dropdown Jurnal Penelitian (Milik tabel PermohonanSurat)
                        Select::make('config_id')
                            ->label('Jurnal Penelitian')
                            ->relationship('config', 'value', fn ($query) => 
                                $query->where('kategori', 'jenis_penelitian')
                            )
                            ->required()
                            ->columnSpanFull(),

                        // SCOPE RELASI: Menghubungkan kolom_1 s/d kolom_4 ke tabel keterangan_essais
                        Group::make()
                            ->relationship('keteranganEssai') 
                            ->schema([
                                TextInput::make('kolom_1')
                                    ->label('Nama Jurnal')
                                    ->placeholder('Masukkan Nama Jurnal')
                                    ->required()
                                    ->columnSpanFull(),

                                TextInput::make('kolom_2')
                                    ->label('e-ISSN')
                                    ->placeholder('Contoh: 1234-5678')
                                    ->required()
                                    ->columnSpanFull(),

                                TextInput::make('kolom_3')
                                    ->label('Judul Penelitian')
                                    ->placeholder('Masukkan Judul Lengkap Penelitian')
                                    ->required()
                                    ->columnSpanFull(),

                                TextInput::make('kolom_4')
                                    ->label('Link Jurnal')
                                    ->placeholder('https://...')
                                    ->url()
                                    ->required()
                                    ->columnSpanFull(),
                            ])->columnSpanFull(),
                    ])->columns(1),

                // 3. SEKSI BARU: DETAIL PERMOHONAN PENUNJANG
                Section::make('Detail Permohonan Penunjang')
                    ->visible($type === 'penunjang') // Muncul jika klik tombol Merah
                    ->schema([
                        // Tampilan Tanggal
                        DatePicker::make('created_at')
                            ->label('Tanggal')
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->columnSpanFull(),

                        // Otomatis set config_id ke 'Surat Penunjang' agar tidak perlu pilih lagi
                        Forms\Components\Hidden::make('config_id')
                            ->default(fn () => \App\Models\Config::where('key', 'penunjang')->first()?->id),

                        // Relasi ke tabel detail untuk kolom_5 dan kolom_6
                        Group::make()
                            ->relationship('keteranganEssai')
                            ->schema([
                                TextInput::make('kolom_5')
                                    ->label('Nama Kegiatan')
                                    ->placeholder('Masukkan Nama Kegiatan Penunjang')
                                    ->required()
                                    ->columnSpanFull(),

                                DatePicker::make('kolom_6')
                                    ->label('Tanggal Kegiatan')
                                    ->placeholder('Pilih Tanggal Pelaksanaan')
                                    ->required()
                                    ->columnSpanFull(),
                            ])->columnSpanFull(),
                    ])->columns(1),

                
                Forms\Components\Section::make('Detail Permohonan Narasumber')
                    ->visible($type === 'narasumber') // Muncul jika klik tombol Biru
                    ->schema([
                        // Tampilan Tanggal Pengajuan
                        Forms\Components\DatePicker::make('created_at')
                            ->label('Tanggal')
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->columnSpanFull(),

                        // Otomatis set config_id ke 'Surat Narasumber'
                        Forms\Components\Hidden::make('config_id')
                            ->default(fn () => \App\Models\Config::where('key', 'narasumber')->first()?->id),

                        // Relasi ke tabel detail untuk kolom spesifik
                        Forms\Components\Group::make()
                            ->relationship('keteranganEssai')
                            ->schema([
                                Forms\Components\TextInput::make('kolom_1')
                                    ->label('Nama Kegiatan')
                                    ->placeholder('Masukkan Nama Kegiatan')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('kolom_2')
                                    ->label('Penyelenggara')
                                    ->placeholder('Masukkan Nama Instansi/Penyelenggara')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('kolom_3')
                                    ->label('Tempat Kegiatan')
                                    ->placeholder('Masukkan Lokasi Kegiatan')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\DatePicker::make('kolom_4')
                                    ->label('Tanggal Kegiatan')
                                    ->placeholder('Pilih Tanggal Pelaksanaan')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('kolom_5')
                                    ->label('Keterangan')
                                    ->placeholder('Tambahkan keterangan jika ada')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])->columnSpanFull(),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),

                // Perbaikan: Memanggil relasi 'config' yang baru
                TextColumn::make('config.value')
                    ->label('Perihal'),

                TextColumn::make('keterangan_esai')
                    ->label('Keterangan')
                    ->limit(50),

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
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

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
}