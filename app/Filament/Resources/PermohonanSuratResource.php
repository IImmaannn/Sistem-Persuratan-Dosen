<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermohonanSuratResource\Pages;
use App\Models\PermohonanSurat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
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
                Section::make('Data Dosen')
                    ->description('Informasi otomatis dari profil Anda')
                    ->schema([
                        TextInput::make('nama_dosen')
                            ->default(fn () => auth()->user()->name)
                            ->disabled()
                            ->dehydrated(false), // Tambahkan ini agar tidak error saat simpan

                        TextInput::make('nip')
                            ->default(fn () => auth()->user()->profile?->nip)
                            ->disabled()
                            ->dehydrated(false), // Tambahkan ini agar tidak error saat simpan
                    ])
                    ->columns(2),

                Section::make('Detail Permohonan')
                    ->schema([
                        // UBAH BAGIAN INI: sesuaikan dengan nama kolom di Navicat
                        Select::make('jenis_surat_id') 
                            ->label('Jenis Surat')
                            ->relationship('jenisSurat', 'nama_jenis') // Mengambil dari tabel jenis_surats
                            ->required(), 

                        Textarea::make('keterangan_esai')
                            ->label('Keterangan')
                            ->required()
                            ->rows(5), 
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(), // [cite: 74]

                TextColumn::make('jenisSurat.nama_jenis')
                    ->label('Perihal'), // [cite: 72]

                TextColumn::make('keterangan_esai')
                    ->label('Keterangan')
                    ->limit(50), // [cite: 73]

                TextColumn::make('status_terakhir')
                    ->label('Status')
                    ->badge() // Menggantikan BadgeColumn di Filament v3
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Proses Verifikasi' => 'warning',
                        'Selesai' => 'success',
                        'Ditolak' => 'danger',
                        default => 'secondary',
                    }), // Representasi lingkaran status di wireframe [cite: 75]
            ])
            ->filters([
                //
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
        // Agar Dosen hanya melihat datanya sendiri sesuai wireframe biru
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }
}