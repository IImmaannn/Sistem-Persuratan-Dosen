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
                // 1. DATA DOSEN (Tetap Sama)
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

                // 2. DETAIL PERMOHONAN (Logika Tanpa Tabrakan)
                // match memastikan hanya SATU relationship yang aktif per request
                ...match ($type) {
                    'penelitian' => [
                        Section::make('Detail Permohonan Penelitian')
                            ->schema([
                                // config_id di luar Group relasi agar masuk ke tabel permohonan_surats
                                Select::make('config_id')
                                    ->label('Jurnal Penelitian')
                                    ->relationship('config', 'value', fn ($query) => 
                                        $query->where('kategori', 'jenis_penelitian')
                                    )
                                    ->required()
                                    ->columnSpanFull(),

                                // Relasi detail ke keterangan_essais
                                Group::make()
                                    ->relationship('keteranganEssai') 
                                    ->schema([
                                        TextInput::make('kolom_1')->label('Nama Jurnal')->required()->columnSpanFull(),
                                        TextInput::make('kolom_2')->label('e-ISSN')->required()->columnSpanFull(),
                                        TextInput::make('kolom_3')->label('Judul Penelitian')->required()->columnSpanFull(),
                                        TextInput::make('kolom_4')->label('Link Jurnal')->url()->required()->columnSpanFull(),
                                    ])->columnSpanFull(),
                            ])->columns(1),
                    ],

                    'penunjang' => [
                        Section::make('Detail Permohonan Penunjang')
                            ->schema([
                                Group::make()
                                    ->relationship('keteranganEssai')
                                    ->schema([
                                        TextInput::make('kolom_5')->label('Nama Kegiatan')->required()->columnSpanFull(),
                                        DatePicker::make('kolom_6')->label('Tanggal Kegiatan')->required()->columnSpanFull(),
                                    ])->columnSpanFull(),
                            ])->columns(1),
                    ],

                    'narasumber' => [
                        Section::make('Detail Permohonan Narasumber')
                            ->schema([
                                Group::make()
                                    ->relationship('keteranganEssai')
                                    ->schema([
                                        TextInput::make('kolom_1')->label('Nama Kegiatan')->required()->columnSpanFull(),
                                        TextInput::make('kolom_2')->label('Penyelenggara')->required()->columnSpanFull(),
                                        TextInput::make('kolom_3')->label('Tempat Kegiatan')->required()->columnSpanFull(),
                                        DatePicker::make('kolom_4')->label('Tanggal Kegiatan')->required()->columnSpanFull(),
                                        Textarea::make('kolom_5')->label('Keterangan')->rows(3)->columnSpanFull(),
                                    ])->columnSpanFull(),
                            ])->columns(1),
                    ],

                    default => [],
                },
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
}