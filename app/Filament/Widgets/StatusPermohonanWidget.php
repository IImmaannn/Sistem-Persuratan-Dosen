<?php

namespace App\Filament\Widgets;

use App\Models\PermohonanSurat;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class StatusPermohonanWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Status Surat'; 
    
    protected static ?int $sort = 1; 

    public function table(Table $table): Table
    {
        $userId = auth()->id();

        return $table
            ->query(
                PermohonanSurat::query()
                    ->where(function (Builder $query) use ($userId) {
                        // Cek apakah dia Pembuat Surat
                        $query->where('user_id', $userId)
                              // ATAU apakah dia Anggota Tim di dalam keteranganEssai
                              ->orWhereHas('keteranganEssai', function (Builder $q) use ($userId) {
                                  // Pencarian JSON yang aman untuk database MySQL/Postgres
                                  $q->where('anggota_tim', 'LIKE', '%"user_id":"' . $userId . '"%')
                                    ->orWhere('anggota_tim', 'LIKE', '%"user_id":' . $userId . '%');
                              });
                    })
                    //FILTER STATUS: Cuma tampilkan yang BELUM terbit
                    ->where('status_terakhir', '!=', 'Surat_Terbit') 
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Tanggal')->date(),
                Tables\Columns\TextColumn::make('config.value')->label('Perihal'),
                Tables\Columns\TextColumn::make('status_terakhir')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Draft' => 'Draft',
                        'Terverifikasi' => 'Sedang di Supervisor',
                        'Disetujui_Supervisor' => 'Disetujui Supervisor', 
                        'Disetujui_Manager' => 'Disetujui Manager',
                        'Disetujui_Wakil_Dekan' => 'Disetujui Wakil Dekan',
                        'Selesai_Pimpinan' => 'Proses Penomoran',
                        'Surat_Terbit' => 'Selesai',
                        default => str_replace('_', ' ', $state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Proses Verifikasi' => 'warning',
                        default => 'info',
                    }),
            ]);
    }
    
    public static function canView(): bool
    {
        return auth()->user()->role === 'Dosen'; 
    }
}