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
    protected static ?string $modelLabel = 'Persetujuan Surat';
    protected static ?string $pluralModelLabel = 'Daftar Persetujuan Surat';
    protected static ?string $navigationLabel = 'Dashboard';

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
                            $component->state($record->user?->profile?->nip ?? '-');
                        }),
                    ])->columns(2),

                    Forms\Components\Group::make()
                    ->relationship('keteranganEssai')
                    ->schema([
                        Forms\Components\Repeater::make('anggota_tim')
                        ->label('Data Dosen Anggota')
                        ->schema([
                            Forms\Components\TextInput::make('nama')->required(),
                            Forms\Components\TextInput::make('email')->required(),
                        ])
                        ->columns(2)
                        ->addActionLabel('Tambah Dosen Lain')
                        ->minItems(0)
                        ->disabled(),
                    ])->columnSpanFull(),                  

                // 2. VERIFIKASI DETAIL ESAI (Konteks Tetap di PermohonanSurat)
                Forms\Components\Section::make('Verifikasi Detail Esai')
                    ->description('Label dan kolom muncul otomatis sesuai jenis surat')
                    ->schema([
                        // KOLOM 1: Nama Jurnal / Kegiatan
                        Forms\Components\TextInput::make('keteranganEssai.kolom_1')
                            ->label(fn ($record) => match ((int) $record?->config_id) { 
                                1, 4 ,5 => 'Nama Jurnal',
                                3, 2 => 'Nama Kegiatan',
                                default => 'Detail 1',
                            })
                            ->disabled()
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_1 ?? '-')),
            
                        // KOLOM 2: e-ISSN / Penyelenggara / Tanggal
                        Forms\Components\TextInput::make('keteranganEssai.kolom_2')
                            ->label(fn ($record) => match ((int) $record?->config_id) { 
                                1, 4, 5=> 'e-ISSN',
                                3 => 'Penyelenggara',    
                                2 => 'Tanggal Kegiatan', 
                                default => 'Detail 2',
                            })
                            ->visible(fn ($record) => in_array((int) $record?->config_id, [1, 2, 3, 4, 5]))
                            ->disabled()
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_2 ?? '-')),

                        // KOLOM 3: Judul / Tempat
                        Forms\Components\TextInput::make('keteranganEssai.kolom_3')
                            ->label(fn ($record) => match ((int) $record?->config_id) { 
                                1, 4, 5 => 'Judul Penelitian',
                                3 => 'Tempat Kegiatan', 
                                default => 'Detail 3',
                            })
                            ->visible(fn ($record) => in_array((int) $record?->config_id, [1, 3, 4, 5]))
                            ->disabled()
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_3 ?? '-')),

                        // KOLOM 4: Link / Tanggal
                        Forms\Components\TextInput::make('keteranganEssai.kolom_4')
                            ->label(fn ($record) => match ((int) $record?->config_id) { 
                                1, 4, 5 => 'Link Jurnal',
                                3 => 'Tanggal Kegiatan', 
                                default => 'Detail 4',
                            })
                            ->visible(fn ($record) => in_array((int) $record?->config_id, [1, 3, 4, 5]))
                            ->disabled()
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_4 ?? '-')),

                        // KOLOM 5: Keterangan Tambahan
                        Forms\Components\Textarea::make('keteranganEssai.kolom_5')
                            ->label(fn ($record) => match ((int) $record?->config_id) { 
                                3 => 'Nama Kegiatan / Keterangan', 
                                default => 'Keterangan Tambahan',
                            })
                            ->visible(fn ($record) => in_array((int) $record?->config_id, [3]))
                            ->columnSpanFull()
                            ->disabled()
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record->keteranganEssai?->kolom_5 ?? '-'))
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('user.name') 
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
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Detail Permohonan Surat')
                    ->modalWidth('4xl') 
                    ->extraModalFooterActions([
                        
                        // 1. TOMBOL SETUJUI (DI DALAM POP-UP)
                        Tables\Actions\Action::make('setuju_modal')
                            ->label('Setujui')
                            ->color('success')
                            ->icon('heroicon-o-check-circle')
                            ->requiresConfirmation()
                            ->action(function (PermohonanSurat $record) {
                                try {
                                    $user = auth()->user();
                                    
                                    $nextStatus = match ($user->role) {
                                        'Supervisor' => 'Disetujui_Supervisor',
                                        'Manager' => 'Disetujui_Manager',
                                        'Wakil_Dekan' => 'Disetujui_Wakil_Dekan',
                                        'Dekan' => 'Selesai_Pimpinan',
                                        default => $record->status_terakhir,
                                    };
                
                                    \App\Models\LogPersetujuan::create([
                                        'permohonan_id' => $record->id,
                                        'pimpinan_id'   => $user->id,
                                        'status_aksi'   => $nextStatus, 
                                        'catatan'       => 'Disetujui oleh ' . $user->role . ' untuk lanjut ke tahap berikutnya.',
                                    ]);
                
                                    $record->update(['status_terakhir' => $nextStatus]);
                
                                    Notification::make()
                                        ->title('Surat berhasil disetujui')
                                        ->success()
                                        ->send();

                                    return redirect(request()->header('Referer'));

                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Gagal Menyimpan! (Error Database)')
                                        ->body($e->getMessage()) 
                                        ->danger()
                                        ->send();
                                }
                            }),

                        // 2. TOMBOL TOLAK (DI DALAM POP-UP)
                        Tables\Actions\Action::make('tolak_modal')
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
                                try {
                                    $record->update(['status_terakhir' => 'Revisi OCS']);
                                    
                                    \App\Models\LogPersetujuan::create([
                                        'permohonan_id' => $record->id,
                                        'pimpinan_id'   => auth()->id(), 
                                        'status_aksi'   => 'Revisi',
                                        'catatan'       => $data['catatan'],
                                    ]);
                                    
                                    Notification::make()
                                        ->title('Surat dikembalikan ke OCS')
                                        ->danger()
                                        ->send();

                                    return redirect(request()->header('Referer'));

                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Gagal Menyimpan! (Error Database)')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
                    ]),
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
        return in_array(auth()->user()->role, [
            'Supervisor', 'Manager', 'Wakil_Dekan', 'Dekan'
        ]);
    }
    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true; 
    }
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();
        return $query->where(function (Builder $q) use ($user) {
            if ($user->role === 'Supervisor') {
                $q->where('status_terakhir', 'Terverifikasi');
            } 
            elseif ($user->role === 'Manager') {
                $q->where('status_terakhir', 'Disetujui_Supervisor');
            } 
            elseif ($user->role === 'Wakil_Dekan') {
                $q->where('status_terakhir', 'Disetujui_Manager');
            } 
            elseif ($user->role === 'Dekan') {
                $q->where('status_terakhir', 'Disetujui_Wakil_Dekan');
            } 
            else {
                $q->whereRaw('1 = 0');
            }
        });
    }
}
