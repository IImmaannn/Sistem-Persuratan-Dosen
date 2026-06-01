<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenomoranSuratResource\Pages;
use App\Filament\Resources\PenomoranSuratResource\RelationManagers;
use App\Models\PermohonanSurat;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Services\PenomoranService;

class PenomoranSuratResource extends Resource
{
    protected static ?string $model = PermohonanSurat::class;
    protected static ?string $modelLabel = 'Verifikasi Surat';
    protected static ?string $pluralModelLabel = 'Daftar Penomoran Surat';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Penomoran Surat';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 1. INFORMASI SURAT (Read-Only Sesuai Tampilan Lo Bro)
                Section::make('Detail Permohonan')
                    ->description('Data ini tidak dapat diubah oleh Operator Penomoran.')
                    ->schema([
                        TextInput::make('user.name')
                            ->label('Nama Dosen')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                $component->state($record->user?->name ?? '-');
                            }),
                        TextInput::make('config.value')
                            ->label('Jenis Surat')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                $component->state($record->config?->value ?? '-');
                            }),
                        Textarea::make('keteranganEssai.kolom_1')
                            ->label(fn ($record) => match ($record?->config_id) {
                                1, 4 ,5 => 'Nama Jurnal',
                                2, 3 => 'Nama Kegiatan',
                                default => 'Detail 1',
                            })
                            ->disabled()
                            ->columnSpanFull()
                            ->afterStateHydrated(function ($component, $record) {
                                $component->state($record->keteranganEssai?->kolom_1 ?? '-');
                            }),
                        Textarea::make('keteranganEssai.kolom_2')
                            ->label(fn ($record) => match ($record?->config_id) {
                                1, 4, 5=> 'e-ISSN',
                                2 => 'Penyelenggara',
                                3 => 'Tanggal Kegiatan',
                                default => 'Detail 2',
                            })
                            ->disabled()
                            ->visible(fn ($record) => in_array($record?->config_id, [1, 2, 3, 4, 5]))
                            ->columnSpanFull()
                            ->afterStateHydrated(function ($component, $record) {
                                $component->state($record->keteranganEssai?->kolom_2 ?? '-');
                            }),
                        Textarea::make('keteranganEssai.kolom_3')
                            ->label(fn ($record) => match ($record?->config_id) {
                                1, 4, 5 => 'Judul Penelitian',
                                2 => 'Tempat Kegiatan',
                                default => 'Detail 3',
                            })
                            ->disabled()
                            ->visible(fn ($record) => in_array($record?->config_id, [1, 2, 4, 5]))
                            ->columnSpanFull()
                            ->afterStateHydrated(function ($component, $record) {
                                $component->state($record->keteranganEssai?->kolom_3 ?? '-');
                            }),
                        Textarea::make('keteranganEssai.kolom_4')
                            ->label(fn ($record) => match ($record?->config_id) {
                                1, 4, 5 => 'Link Jurnal',
                                2 => 'Tanggal Kegiatan',
                                default => 'Detail 4',
                            })
                            ->disabled()
                            ->visible(fn ($record) => in_array($record?->config_id, [1, 2, 4, 5]))
                            ->columnSpanFull()
                            ->afterStateHydrated(function ($component, $record) {
                                $component->state($record->keteranganEssai?->kolom_4 ?? '-');
                            }),
                        Textarea::make('keteranganEssai.kolom_5')
                            ->label(fn ($record) => match ($record?->config_id) {
                                2 => 'Nama Kegiatan / Keterangan',
                                default => 'Keterangan Tambahan',
                            })
                            ->disabled()
                            ->visible(fn ($record) => in_array($record?->config_id, [2]))
                            ->columnSpanFull()
                            ->afterStateHydrated(function ($component, $record) {
                                $component->state($record->keteranganEssai?->kolom_5 ?? '-');
                            }),
                    ])->columns(2),

                // 2. DATA DOSEN ANGGOTA (SEKARANG SUDAH MASUK JALUR UTAMA FORM)
                Group::make()
                    ->relationship('keteranganEssai')
                    ->schema([
                        Section::make('Data Dosen Anggota')
                            ->schema([
                                Forms\Components\Repeater::make('anggota_tim')
                                    ->label('Daftar Anggota')
                                    ->schema([
                                        Select::make('user_id')
                                            ->label('Nama Dosen Anggota')
                                            ->options(User::where('role', 'Dosen')->get()->mapWithKeys(fn ($user) => [
                                                $user->id => "{$user->name} ({$user->email})"
                                            ]))
                                            ->disabled(), // Dikunci agar OPS hanya bisa meninjau

                                        TextInput::make('email')
                                            ->label('Email')
                                            ->disabled(),
                                    ])
                                    ->columns(2)
                                    ->disabled() // Mencegah manipulasi baris oleh OPS
                                    ->dehydrated(false),
                            ]),
                    ])->columnSpanFull(),

                // 3. INPUT NOMOR SURAT (Tugas Utama OPS)
                Section::make('Penomoran Resmi')
                    ->schema([
                        TextInput::make('nomor_surat')
                            ->label('Nomor Surat Resmi')
                            ->placeholder('Contoh: 123/UN7.F3.3/HK/2026')
                            ->required()
                            ->unique(ignoreRecord: true), // Validasi anti nomor ganda
                        
                        Hidden::make('status_terakhir')
                            ->default('Surat_Terbit'), // Auto update status berkas
                    ])
            ]); // Gerbang utama penutup array ->schema() sekarang berada di sini bro!
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                $q->whereNull('nomor_surat')->orWhere('nomor_surat', '');
            }))
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
                    ->limit(30)
                    ->searchable(),
                
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('beri_nomor_otomatis')
                    ->label('Otomatis')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->button()
                    ->requiresConfirmation() 
                    ->modalHeading('Penomoran Otomatis')
                    ->modalDescription('Sistem akan otomatis menghitung nomor, membuat PDF, dan mengirim email. Lanjutkan?')
                    ->action(function (PermohonanSurat $record) {
                        
                        $tahun = date('Y');
                        $bulan = date('n');
                        $romawi = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
                        $bulanRomawi = $romawi[$bulan];
                        $kodeTetap = 'UN7.F1/DK'; 
                        
                        $akhiranSurat = '/' . $bulanRomawi . '/' . $tahun; 
                        
                        $suratBulanIni = \App\Models\PermohonanSurat::whereNotNull('nomor_surat')
                            ->where('nomor_surat', 'LIKE', '%' . $akhiranSurat)
                            ->get();

                        $maxUrutan = 0;
                        foreach ($suratBulanIni as $surat) {
                            $pecahan = explode('/', $surat->nomor_surat);
                            if (isset($pecahan[0]) && is_numeric($pecahan[0])) {
                                $angka = (int)$pecahan[0];
                                if ($angka > $maxUrutan) {
                                    $maxUrutan = $angka;
                                }
                            }
                        }

                        $urutan = $maxUrutan + 1;
                        $nomorBaru = sprintf("%d", $urutan) . '/' . $kodeTetap . '/' . $bulanRomawi . '/' . $tahun;

                        // UPDATE KE DATABASE SEMENTARA
                        $record->update([
                            'nomor_surat' => $nomorBaru
                        ]);

                        // PANGGIL MESIN UTAMA 
                        $service = new PenomoranService();
                        $service->prosesPenerbitanPDFdanEmail($record);
                    }),

                // TOMBOL 2: JALAN ARTERI (Edit Manual)
                Tables\Actions\Action::make('edit_manual')
                    ->label('Manual')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->button()   
                    ->outlined()
                    ->url(fn (PermohonanSurat $record): string => PenomoranSuratResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array { return []; }

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'Operator_Nomor';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['keteranganEssai', 'config'])
            ->where('status_terakhir', 'Selesai_Pimpinan');
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