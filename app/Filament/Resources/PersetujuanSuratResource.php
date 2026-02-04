<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersetujuanSuratResource\Pages;
use App\Filament\Resources\PersetujuanSuratResource\RelationManagers;
use App\Models\PersetujuanSurat;
use App\Models\PermohonanSurat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class PersetujuanSuratResource extends Resource
{
    protected static ?string $model = PermohonanSurat::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                  Forms\Components\Section::make('Informasi Pengaju')
                    ->schema([
                        Forms\Components\TextInput::make('user.name')
                        ->label('Nama Dosen')
                        ->disabled()
                        ->dehydrated(false) // Gak usah disimpen, cuma tampil
                        ->afterStateHydrated(function ($component, $record) {
                            // Ambil nama dari relasi user
                            $component->state($record->user?->name ?? '-');
                        }), 
                        Forms\Components\TextInput::make('user.profile.nip')
                        ->label('NIP')
                        ->disabled()
                        ->dehydrated(false)
                        ->afterStateHydrated(function ($component, $record) {
                            // Ambil NIP dari relasi user -> profile
                            // Pastikan model User lo punya relasi 'profile' ya!
                            $component->state($record->user?->profile?->nip ?? '-');
                        }),
                    ])->columns(2),

                // 2. VERIFIKASI DETAIL ESAI (Konteks Tetap di PermohonanSurat)
                Forms\Components\Section::make('Verifikasi Detail Esai')
                    ->description('Label dan kolom muncul otomatis sesuai jenis surat')
                    ->schema([
                        // KOLOM 1: Nama Jurnal / Kegiatan
                        // Gunakan dot notation 'keteranganEssai.kolom_1'
                        Forms\Components\TextInput::make('keteranganEssai.kolom_1')
                            ->label(fn ($record) => match ($record?->config_id) {
                                1, 4 ,5 => 'Nama Jurnal',
                                2, 3 => 'Nama Kegiatan',
                                default => 'Detail 1',
                            })
                            ->disabled()
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_1)),
            
                        // KOLOM 2: e-ISSN / Penyelenggara / Tanggal
                        Forms\Components\TextInput::make('keteranganEssai.kolom_2')
                            ->label(fn ($record) => match ($record?->config_id) {
                                1, 4, 5=> 'e-ISSN',
                                2 => 'Penyelenggara',
                                3 => 'Tanggal Kegiatan', // Sesuai isian dosen
                                default => 'Detail 2',
                            })
                            // Sekarang visible() akan bekerja karena bisa baca config_id
                            ->visible(fn ($record) => in_array($record?->config_id, [1, 2, 3, 4, 5]))
                            ->disabled()
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_2)),

                        // KOLOM 3: Judul / Tempat
                        Forms\Components\TextInput::make('keteranganEssai.kolom_3')
                            ->label(fn ($record) => match ($record?->config_id) {
                                1, 4, 5 => 'Judul Penelitian',
                                2 => 'Tempat Kegiatan',
                                default => 'Detail 3',
                            })
                            ->visible(fn ($record) => in_array($record?->config_id, [1, 2, 4, 5]))
                            ->disabled()
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_3)),

                        Forms\Components\TextInput::make('keteranganEssai.kolom_4')
                            ->label(fn ($record) => match ($record?->config_id) {
                                1, 4, 5 => 'Link Jurnal',
                                2 => 'Tanggal Kegiatan',
                                default => 'Detail 3',
                            })
                            ->visible(fn ($record) => in_array($record?->config_id, [1, 2, 4, 5]))
                            ->disabled()
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_4)),

                        // KOLOM 5: Keterangan / Nama Kegiatan Penunjang
                        Forms\Components\Textarea::make('keteranganEssai.kolom_5')
                            ->label(fn ($record) => match ($record?->config_id) {
                                2 => 'Nama Kegiatan / Keterangan',
                                default => 'Keterangan Tambahan',
                            })
                            ->visible(fn ($record) => in_array($record?->config_id, [2]))
                            ->columnSpanFull()
                            ->disabled()
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_5))
                    ]),

                // 3. TINDAKAN OPERATOR
                Forms\Components\Section::make('Tindakan Pimpinan')
                    ->schema([
                        Forms\Components\Select::make('status_terakhir')
                            ->label('Keputusan Persetujuan')
                            ->options(function () {
                                $role = auth()->user()->role;
                                return match ($role) {
                                    'Supervisor' => ['Disetujui_Supervisor' => 'Setujui (Kirim ke Manager)', 'Revisi OCS' => 'Tolak (Kembalikan ke OCS)'],
                                    'Manager' => ['Disetujui_Manager' => 'Setujui (Kirim ke Wadek)', 'Revisi OCS' => 'Tolak (Kembalikan ke OCS)'],
                                    'Wakil_Dekan' => ['Disetujui_Wakil_Dekan' => 'Setujui (Kirim ke Dekan)', 'Revisi OCS' => 'Tolak (Kembalikan ke OCS)'],
                                    'Dekan' => ['Selesai_Pimpinan' => 'Setujui Akhir', 'Revisi OCS' => 'Tolak (Kembalikan ke OCS)'],
                                    default => [],
                                };
                            })
                            ->required()
                            ->native(false),
                    ])
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tgl_pengajuan')
                ->label('Tanggal')
                ->date()
                ->sortable(),
                Tables\Columns\TextColumn::make('user.name') // Relasi ke User
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.profile.nip')
                    ->label('NIP')
                    ->copyable(),
                Tables\Columns\TextColumn::make('config.value')
                    ->label('Perihal'),
                Tables\Columns\TextColumn::make('keteranganEssai.kolom_1')
                    ->label('Keterangan')
                    ->limit(30)
                    ->placeholder('Tidak ada detail'),
                Tables\Columns\BadgeColumn::make('status_terakhir')
                    ->label('Status')
                    ->colors([
                        'warning' => 'Pending',
                        'success' => 'Disetujui',
                        'danger' => 'Ditolak',
                    ]),
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('setujui')
                ->label('Setujui')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function (PermohonanSurat $record) {
                    $user = auth()->user();
                    
                    // 1. Tentukan status berikutnya
                    $nextStatus = match ($user->role) {
                        'Supervisor' => 'Disetujui_Supervisor',
                        'Manager' => 'Disetujui_Manager',
                        'Wakil_Dekan' => 'Disetujui_Wakil_Dekan',
                        'Dekan' => 'Selesai_Pimpinan',
                        default => $record->status_terakhir,
                    };

                    // 2. Simpan Log (Pastikan kolom-kolom ini sudah ada di $fillable model LogPersetujuan)
                    \App\Models\LogPersetujuan::create([
                        'permohonan_id' => $record->id,
                        'pimpinan_id'   => $user->id,
                        'status_aksi'   => $nextStatus, // Ini yang tadi bikin error SQL
                        'catatan'       => 'Disetujui oleh ' . $user->role . ' untuk lanjut ke tahap berikutnya.',
                    ]);

                    // 3. Update status utama
                    $record->update(['status_terakhir' => $nextStatus]);

                    // 4. Munculkan Notifikasi (Tadi error di sini karena belum import)
                    Notification::make()
                        ->title('Surat berhasil disetujui')
                        ->success()
                        ->send();
                }),
            Tables\Actions\Action::make('tolak')
                ->label('Tolak')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('catatan')
                        ->label('Alasan Penolakan')
                        ->required(),
                ])
                ->action(function (PermohonanSurat $record, array $data) {
                    $record->update(['status_terakhir' => 'Revisi OCS']);
                    \App\Models\LogPersetujuan::create([
                        'permohonan_id' => $record->id,
                        'pimpinan_id' => auth()->id(),
                        'status_aksi' => 'Revisi',
                        'catatan' => $data['catatan'], // Ini alasan yang mau dilihat OCS
                        'timestamp' => now(),
                    ]);
                    \Filament\Notifications\Notification::make()
                        ->title('Surat berhasil dikembalikan ke OCS')
                        ->danger()
        ->send();
                }),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPersetujuanSurats::route('/'),
            'create' => Pages\CreatePersetujuanSurat::route('/create'),
            'edit' => Pages\EditPersetujuanSurat::route('/{record}/edit'),
        ];
    }
    public static function canViewAny(): bool
    {
        // Hanya role pimpinan yang bisa liat menu ini
        return in_array(auth()->user()->role, [
            'Supervisor', 'Manager', 'Wakil_Dekan', 'Dekan'
        ]);
    }
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        // Pastikan ini hanya berjalan untuk pimpinan
        return $query->where(function (Builder $q) use ($user) {
            if ($user->role === 'Supervisor') {
                // Supervisor hanya melihat surat yang sudah selesai dicek Operator (OCS)
                $q->where('status_terakhir', 'Terverifikasi');
            } 
            elseif ($user->role === 'Manager') {
                // Manager hanya melihat surat yang sudah disetujui Supervisor
                $q->where('status_terakhir', 'Disetujui_Supervisor');
            } 
            elseif ($user->role === 'Wakil_Dekan') {
                // Wakil Dekan hanya melihat surat yang sudah disetujui Manager
                $q->where('status_terakhir', 'Disetujui_Manager');
            } 
            elseif ($user->role === 'Dekan') {
                // Dekan hanya melihat surat yang sudah disetujui Wakil Dekan
                $q->where('status_terakhir', 'Disetujui_Wakil_Dekan');
            } 
            else {
                // Keamanan tambahan: role lain tidak melihat data apa pun di sini
                $q->whereRaw('1 = 0');
            }
        });
    }
}
