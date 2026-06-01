<?php

namespace App\Filament\Widgets;

use App\Models\PermohonanSurat;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class HistoryPermohonanWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'History Surat'; 
    
    protected static ?int $sort = 2; 

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
                                  $q->where('anggota_tim', 'LIKE', '%"user_id":"' . $userId . '"%')
                                    ->orWhere('anggota_tim', 'LIKE', '%"user_id":' . $userId . '%');
                              });
                    })
                    //FILTER HISTORY: Cuma tampilkan yang UDAH TERBIT & PUNYA NOMOR
                    ->where('status_terakhir', 'Surat_Terbit') 
                    ->whereNotNull('nomor_surat')
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Tanggal')->date(),
                Tables\Columns\TextColumn::make('config.value')->label('Perihal')->searchable(),
                Tables\Columns\TextColumn::make('nomor_surat')
                    ->label('Nomor Surat')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->copyable(),
            ]);
    }
    
    public static function canView(): bool
    {
        return auth()->user()->role === 'Dosen'; 
    }
}