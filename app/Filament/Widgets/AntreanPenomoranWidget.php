<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\PermohonanSurat;
use App\Filament\Resources\PenomoranSuratResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use App\Models\SystemAsset;
use App\Models\User;
use App\Services\PenomoranService;


class AntreanPenomoranWidget extends BaseWidget
{
    protected static ?int $sort = 2; 
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Antrean Surat (Belum Dinomori)';

    // Cuma OPS yang boleh liat tabel ini di Dashboard
    public static function canView(): bool
    {
        return auth()->user()->hasRole('Operator_Nomor');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PermohonanSurat::where('status_terakhir', 'Selesai_Pimpinan')
                    ->where(function ($q) {
                        $q->whereNull('nomor_surat')->orWhere('nomor_surat', '');
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Tanggal')->date()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Nama')->searchable(),
                Tables\Columns\TextColumn::make('user.profile.nip')->label('NIP'),
                Tables\Columns\TextColumn::make('config.value')->label('Perihal'),
                Tables\Columns\TextColumn::make('keteranganEssai.kolom_1')->label('Keterangan')->limit(30),
            ])
            ->actions([
                // Beri Nomor Otomatis
                Tables\Actions\Action::make('beri_nomor_otomatis')
                    ->label('Beri Nomor')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->button()
                    ->requiresConfirmation() // Pop-up cegah salah klik
                    ->modalHeading('Penomoran Otomatis')
                    ->modalDescription('Sistem akan otomatis menghitung nomor, membuat file PDF, dan mengirim email ke Dosen terkait. Lanjutkan?')
                    ->action(function (PermohonanSurat $record) {
                        
                        // --- 1. LOGIKA MENCARI NOMOR SELANJUTNYA ---
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

                        // --- 2. UPDATE KE DATABASE SEMENTARA ---
                        $record->update([
                            'nomor_surat' => $nomorBaru
                        ]);
                        // 2. PANGGIL MESIN UTAMA BUAT BIKIN PDF & KIRIM EMAIL!
                        $service = new PenomoranService();
                        $service->prosesPenerbitanPDFdanEmail($record);
                    }),

                // Edit Manual
                Tables\Actions\Action::make('edit_manual')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->button()   
                    ->outlined()
                    // Arahkan ke halaman EditPenomoranSurat
                    ->url(fn (PermohonanSurat $record): string => PenomoranSuratResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}