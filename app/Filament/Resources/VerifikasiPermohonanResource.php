<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VerifikasiPermohonanResource\Pages;
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
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VerifikasiPermohonanResource extends Resource
{
    protected static ?string $model = PermohonanSurat::class;
    protected static ?string $modelLabel = 'Penomoran Surat';
    protected static ?string $pluralModelLabel = 'Daftar Verifikasi Surat';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $slug = 'dashboard-operator';

    // === 1. SKEMA HALAMAN EDIT PENUH ===
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // INFORMASI PENGAJU (Read-Only)
                Section::make('Informasi Pengaju')
                    ->schema([
                        TextInput::make('user.name')
                            ->label('Nama Dosen')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                $component->state($record->user?->name ?? '-');
                            }), 
                        TextInput::make('user.profile.nip')
                            ->label('NIP')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                $component->state($record->user?->profile?->nip ?? '-');
                            }),
                    ])->columns(2),

                // GERBANG UTAMA RELASI (Bisa diedit OCS & Auto-Save)
                Group::make()
                    ->relationship('keteranganEssai')
                    ->schema([
                        
                        // REPEATER DOSEN ANGGOTA (Dropdown + Email - Bisa Diedit OCS)
                        Section::make('Data Dosen Anggota')
                            ->schema([
                                Forms\Components\Repeater::make('anggota_tim')
                                    ->label('Daftar Anggota')
                                    ->schema([
                                        Select::make('user_id')
                                            ->label('Nama Dosen Anggota')
                                            ->placeholder('Pilih Nama Dosen...')
                                            ->options(User::where('role', 'Dosen')->get()->mapWithKeys(fn ($user) => [
                                                $user->id => "{$user->name} ({$user->email})"
                                            ]))
                                            ->searchable()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
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
                                            // ->required(),

                                        Hidden::make('nama'),
                                        Hidden::make('nip'),
                                        Hidden::make('pangkat'),
                                    ])
                                    ->columns(2)
                                    ->addActionLabel('Tambah Dosen Lain')
                                    ->minItems(0),
                            ]),
                        // VERIFIKASI DETAIL ESAI (Bisa Diedit OCS)
                        Section::make('Verifikasi Detail Esai')
                            ->description('Label dan kolom muncul otomatis sesuai jenis surat') 
                            ->schema([
                                TextInput::make('kolom_1')
                                    ->label(fn ($livewire) => match ((int) $livewire->record?->config_id) { 
                                        1, 4 ,5 => 'Nama Jurnal',
                                        3, 2 => 'Nama Kegiatan', 
                                        default => 'Detail 1',
                                    }) 
                                    ->required()
                                    ->columnSpanFull(),

                                TextInput::make('kolom_2')
                                    ->label(fn ($livewire) => match ((int) $livewire->record?->config_id) { 
                                        1, 4, 5=> 'e-ISSN',
                                        3 => 'Penyelenggara',    // 🔥 TUKER JADI 3 (Narasumber)
                                        2 => 'Tanggal Kegiatan', // 🔥 TUKER JADI 2 (Penunjang)
                                        default => 'Detail 2',
                                    })
                                    ->visible(fn ($livewire) => in_array((int) $livewire->record?->config_id, [1, 2, 3, 4, 5]))
                                    ->required()
                                    ->columnSpanFull(),

                                TextInput::make('kolom_3')
                                    ->label(fn ($livewire) => match ((int) $livewire->record?->config_id) { 
                                        1, 4, 5 => 'Judul Penelitian',
                                        3 => 'Tempat Kegiatan', // 🔥 TUKER JADI 3
                                        default => 'Detail 3',
                                    })
                                    ->visible(fn ($livewire) => in_array((int) $livewire->record?->config_id, [1, 3, 4, 5])) // 🔥 2 GANTI 3
                                    ->columnSpanFull(),

                                TextInput::make('kolom_4')
                                    ->label(fn ($livewire) => match ((int) $livewire->record?->config_id) { 
                                        1, 4, 5 => 'Link Jurnal',
                                        3 => 'Tanggal Kegiatan', // 🔥 TUKER JADI 3
                                        default => 'Detail 4',
                                    })
                                    ->visible(fn ($livewire) => in_array((int) $livewire->record?->config_id, [1, 3, 4, 5])) // 🔥 2 GANTI 3
                                    ->columnSpanFull(),

                                Textarea::make('kolom_5')
                                    ->label(fn ($livewire) => match ((int) $livewire->record?->config_id) { 
                                        3 => 'Nama Kegiatan / Keterangan', // 🔥 TUKER JADI 3
                                        default => 'Keterangan Tambahan',
                                    })
                                    ->visible(fn ($livewire) => in_array((int) $livewire->record?->config_id, [3])) // 🔥 2 GANTI 3
                                    ->columnSpanFull()
                            ])->columns(2),
                    ])->columnSpanFull(),

                // CATATAN REVISI PIMPINAN
                Section::make('Catatan Penolakan Pimpinan')
                    ->description('Alasan mengapa pimpinan menolak permohonan ini sebelumnya.')
                    ->schema([
                        Forms\Components\Placeholder::make('alasan_terakhir')
                            ->label('Alasan Terakhir')
                            ->content(fn ($record) => 
                                $record->logPersetujuans() 
                                    ->where('status_aksi', 'Revisi')
                                    ->latest()
                                    ->first()?->catatan ?? 'Belum ada catatan penolakan.'
                            )
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => $record->status_terakhir === 'Revisi OCS'),
            ]);
    }

    // === 2. SKEMA TABEL LIST DASHBOARD ===
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Dosen Pengaju')->searchable(),
                Tables\Columns\TextColumn::make('config.value')->label('Perihal'),
                Tables\Columns\TextColumn::make('status_terakhir')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Menunggu Verifikasi OCS' => 'warning',
                        'Selesai_Pimpinan', 'Surat_Terbit' => 'success',
                        default => 'info',
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Detail')->icon('heroicon-o-check-badge'),
            ]);
    }

    public static function getRelations(): array { return []; }

    public static function canViewAny(): bool
    {
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