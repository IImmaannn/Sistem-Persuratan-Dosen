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
                // 1. INFORMASI DOSEN (Read-Only)
                Forms\Components\Section::make('Informasi Pengaju')
                    ->description('Data identitas dosen pengaju surat')
                    ->schema([
                        Forms\Components\TextInput::make('user.name')
                            ->label('Nama Dosen')
                            ->disabled(), // Operator tidak boleh ubah nama dosen

                        Forms\Components\TextInput::make('user.profile.nip')
                            ->label('NIP')
                            ->disabled(),
                    ])->columns(2),

                // 2. KETERANGAN ESAI (Bisa Direvisi oleh Operator)
                // Menggunakan relasi hasOne yang sudah kita perbaiki 
                Forms\Components\Section::make('Verifikasi Detail Esai')
                    ->description('Operator dapat merevisi kolom di bawah jika informasi tidak jelas (SRS-Permohonan-03)') 
                    ->schema([
                        Forms\Components\Group::make()
                            ->relationship('keteranganEssai') // Kunci sinkronisasi ke Navicat
                            ->schema([
                                // Tampilkan semua kolom agar bisa dicek/direvisi
                                Forms\Components\TextInput::make('kolom_1')->label('Kolom 1 / Nama Jurnal / Nama Kegiatan'),
                                Forms\Components\TextInput::make('kolom_2')->label('Kolom 2 / e-ISSN / Penyelenggara'),
                                Forms\Components\TextInput::make('kolom_3')->label('Kolom 3 / Judul / Tempat'),
                                Forms\Components\TextInput::make('kolom_4')->label('Kolom 4 / Link / Tanggal'),
                                Forms\Components\Textarea::make('kolom_5')->label('Kolom 5 / Keterangan Tambahan')->rows(3),
                                // Tambahkan kolom 6-7 jika diperlukan sesuai ERD [cite: 98, 99]
                            ]),
                    ]),

                // 3. STATUS VERIFIKASI
                Forms\Components\Section::make('Tindakan Operator')
                    ->schema([
                        Forms\Components\Select::make('status_terakhir')
                            ->label('Status Verifikasi Administrasi')
                            ->options([
                                'Proses Verifikasi' => 'Proses Verifikasi',
                                'Revisi' => 'Perlu Revisi',
                                'Disetujui Operator' => 'Lanjut ke Pimpinan',
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
}
