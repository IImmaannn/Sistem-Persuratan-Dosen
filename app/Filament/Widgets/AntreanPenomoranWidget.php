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


class AntreanPenomoranWidget extends BaseWidget
{
    protected static ?int $sort = 2; // Urutan ke-2 (di bawah kotak statistik)
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
                // 🚀 TOMBOL 1: JALAN TOL (Beri Nomor Otomatis)
                Tables\Actions\Action::make('beri_nomor_otomatis')
                    ->label('Beri Nomor')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
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
                        // Kita update nomornya dulu, status_terakhir biar di-update di script PDF nanti
                        $record->update([
                            'nomor_surat' => $nomorBaru
                        ]);
                        
                        $essai = $record->keteranganEssai; 

                        // === LANGKAH A: PROSES PENYUSUNAN FILE WORD (.docx) ===
                        $config = $record->config; 

                        if (!$config) {
                            \Filament\Notifications\Notification::make()
                                ->title('Relasi Config Gagal!')
                                ->body('Data permohonan ini tidak terikat dengan Jenis Surat apa pun.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $templateFile = $config->template_path;

                        if (!$templateFile || !Storage::disk('public')->exists($templateFile)) {
                            \Filament\Notifications\Notification::make()
                                ->title('File Template .docx Hilang!')
                                ->body('Sistem tidak menemukan file fisik template di server storage.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $templatePath = storage_path('app/public/' . $templateFile);
                        $templateProcessor = new TemplateProcessor($templatePath);

                        $formatTanggalIndo = function($tanggal) {
                            if (!$tanggal || $tanggal === '-') return '-';
                            // Cek apakah formatnya YYYY-MM-DD
                            if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $tanggal)) {
                                $bulanIndo = [
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
                                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
                                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                ];
                                $pecah = explode('-', $tanggal);
                                // Hasilkan: 16 Oktober 2026
                                return (int)$pecah[2] . ' ' . $bulanIndo[(int)$pecah[1]] . ' ' . $pecah[0];
                            }
                            return $tanggal;
                        };

                        $templateProcessor->setValue('NOMOR_SURAT', $record->nomor_surat);
                        
                        // Panggil fungsi tanggal untuk semua kolom yang berpotensi berisi tanggal
                        $templateProcessor->setValue('kolom_1', $formatTanggalIndo($essai?->kolom_1 ?? '-'));
                        $templateProcessor->setValue('kolom_2', $formatTanggalIndo($essai?->kolom_2 ?? '-'));
                        $templateProcessor->setValue('kolom_3', $formatTanggalIndo($essai?->kolom_3 ?? '-'));
                        $templateProcessor->setValue('kolom_4', $formatTanggalIndo($essai?->kolom_4 ?? '-'));

                        // KUMPULKAN DATA DOSEN SECARA DINAMIS (ARRAY)
                        $listDosen = []; 
                        $targetEmails = [];

                        if ($record->user) {
                            $listDosen[] = [
                                'nama' => $record->user->name,
                                'nip' => $record->user->profile?->nip ?? '-',
                                'pangkat' => $record->user->profile?->golongan ?? $record->user->profile?->pangkat_golongan ?? '-',
                            ];
                            if ($record->user->email) {
                                $targetEmails[] = $record->user->email;
                            }
                        }

                        if ($essai && !empty($essai->anggota_tim)) {
                            $anggotaTim = is_string($essai->anggota_tim) ? json_decode($essai->anggota_tim, true) : $essai->anggota_tim;

                            if (is_array($anggotaTim)) {
                                foreach ($anggotaTim as $anggota) {
                                    if (!empty($anggota['user_id'])) {
                                        $userAnggota = User::find($anggota['user_id']);
                                        if ($userAnggota) {
                                            $listDosen[] = [
                                                'nama' => $userAnggota->name,
                                                'nip' => $userAnggota->profile?->nip ?? '-',
                                                'pangkat' => $userAnggota->profile?->golongan ?? $userAnggota->profile?->pangkat_golongan ?? '-',
                                            ];
                                            if ($userAnggota->email) {
                                                $targetEmails[] = $userAnggota->email;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // KLONING BLOK DOSEN DI WORD SESUAI JUMLAH ORANG
                        $templateProcessor->cloneBlock('block_dosen', count($listDosen), true, true);

                        // ISI DATA MASING-MASING ORANG & LOGIKA PENOMORAN
                        $totalDosen = count($listDosen);
                        foreach ($listDosen as $index => $dosen) {
                            $i = $index + 1; 
                            
                            // Logika Penomoran: Kalau dosen lebih dari 1, kasih angka "1. ", kalau cuma 1, kosongin!
                            $teksNomor = ($totalDosen > 1) ? $i . ". " : "";
                            
                            $templateProcessor->setValue('NO_DOSEN#' . $i, $teksNomor);
                            $templateProcessor->setValue('NAMA_DOSEN#' . $i, $dosen['nama']);
                            $templateProcessor->setValue('NIP_DOSEN#' . $i, $dosen['nip']);
                            $templateProcessor->setValue('PANGKAT_DOSEN#' . $i, $dosen['pangkat']);
                        }

                        $ttdAsset = SystemAsset::where('key_aset', 'ttd_dekan')->first();
                        if ($ttdAsset && Storage::disk('public')->exists($ttdAsset->file_path)) {
                            $imagePath = storage_path('app/public/' . $ttdAsset->file_path);
                            $templateProcessor->setImageValue('TTD_IMAGE', [
                                'path' => $imagePath,
                                'width' => 120,
                                'height' => 90,
                                'ratio' => true
                            ]);
                        } else {
                            $templateProcessor->setValue('TTD_IMAGE', '');
                        }

                        $tempDocxName = 'temp_mail_surat_' . time() . '.docx';
                        $tempDocxPath = storage_path('app/public/' . $tempDocxName);
                        $templateProcessor->saveAs($tempDocxPath);


                        // === LANGKAH B: KONVERSI KE PDF (VIA HTML INTERCEPTION HACK) ===
                        try {
                            ini_set('memory_limit', '512M');
                            
                            $phpWord = IOFactory::load($tempDocxPath);
                            
                            try {
                                $phpWord->setDefaultFontName('Times New Roman');
                                $phpWord->setDefaultFontSize(12);

                                foreach ($phpWord->getSections() as $section) {
                                    foreach ($section->getElements() as $element) {
                                        if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                                            $tableStyle = $element->getStyle();
                                            if (is_object($tableStyle) && method_exists($tableStyle, 'setBorderTopSize')) {
                                                $tableStyle->setBorderTopSize(0);
                                                $tableStyle->setBorderBottomSize(0);
                                                $tableStyle->setBorderLeftSize(0);
                                                $tableStyle->setBorderRightSize(0);
                                                if (method_exists($tableStyle, 'setInsideHSize')) {
                                                    $tableStyle->setInsideHSize(0); 
                                                    $tableStyle->setInsideVSize(0); 
                                                }
                                            }
                                        }
                                    }
                                }
                            } catch (\Throwable $th) {
                                \Log::warning('Gagal memanipulasi border tabel: ' . $th->getMessage());
                            }

                            $pdfFolder = 'surat_selesai';
                            if (!Storage::disk('public')->exists($pdfFolder)) {
                                Storage::disk('public')->makeDirectory($pdfFolder);
                            }

                            $pdfName = 'Surat_Tugas_Resmi_' . $record->id . '_' . time() . '.pdf';
                            $pdfRelativePath = $pdfFolder . '/' . $pdfName; 
                            $pdfFullPath = storage_path('app/public/' . $pdfRelativePath); 
                            
                            $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
                            $tempHtmlPath = storage_path('app/public/temp_html_' . time() . '.html');
                            $htmlWriter->save($tempHtmlPath);

                            $htmlContent = file_get_contents($tempHtmlPath);

                            $cssInjection = '
                            <style>
                                /* 1. MARGIN KERTAS UTAMA*/
                                @page { margin: 1cm 1cm 1cm 1cm !important; } 
                                
                                body { 
                                    font-family: "Times New Roman", Times, serif; 
                                    font-size: 12pt; 
                                    line-height: 1.15; 
                                }
                                
                                /* 2. DORONG SEMUA TAG KE DALAM*/
                                p, ul, ol, div, table:not(:first-of-type) {
                                    margin-left: 0.75cm !important;  
                                    margin-right: 0.75cm !important; 
                                }

                                p { 
                                    margin-top: 2px !important; 
                                    margin-bottom: 2px !important; 
                                }

                                table { 
                                    width: 100% !important; 
                                    border-collapse: collapse; 
                                    table-layout: fixed; 
                                    border: none !important;
                                    margin-bottom: 5px !important; 
                                }
                                td { 
                                    vertical-align: middle !important; 
                                    padding: 3px; 
                                    word-wrap: break-word; 
                                    border: none !important;
                                }
                                
                                hr { display: none !important; }
                                
                                
                                
                                table:first-of-type {
                                    width: 100% !important; 
                                    margin-left: 0 !important; 
                                    margin-right: 0 !important; 
                                    border: none !important;
                                }
                                
                                table:first-of-type p, 
                                table:first-of-type div {
                                    margin-left: 0 !important;
                                    margin-right: 0 !important;
                                }

                                /* Kolom 1: Logo UNDIP*/
                                table:first-of-type td:nth-child(1) { 
                                    width: 16% !important; 
                                    text-align: left !important; 
                                    padding-left: 0 !important; 
                                }
                                
                                /* LOGO DIBESARKAN 115px*/
                                table:first-of-type td:nth-child(1) img {
                                    width: 115px !important; 
                                    max-width: none !important; 
                                    height: auto !important;
                                    display: block !important;
                                }
                                
                                /* Kolom 2: Teks Kementerian & Fakultas */
                                table:first-of-type td:nth-child(2) { 
                                    width: 53% !important; 
                                    text-align: left !important; 
                                    line-height: 1.1; 
                                }
                                
                                /* Kolom 3: Alamat & Kontak*/
                                table:first-of-type td:nth-child(3) { 
                                    width: 31% !important; 
                                    text-align: right !important; 
                                    padding-right: 0 !important; 
                                    font-size: 1.0 !important; 
                                    line-height: 1.0 !important; 
                                }
                                /* ========================================================= */
                                
                            </style>
                            </head>';

                            $htmlContent = str_replace('</head>', $cssInjection, $htmlContent);

                            $dompdf = new \Dompdf\Dompdf();
                            $options = $dompdf->getOptions();
                            $options->set('isHtml5ParserEnabled', true);
                            $options->set('isRemoteEnabled', true);
                            $dompdf->setOptions($options);
                            
                            $dompdf->loadHtml($htmlContent);
                            $dompdf->setPaper('A4', 'portrait');
                            $dompdf->render();

                            file_put_contents($pdfFullPath, $dompdf->output());

                            if (file_exists($tempHtmlPath)) {
                                unlink($tempHtmlPath);
                            }

                        } catch (\Throwable $e) {
                            \Log::error('CRASH PADA PROSES DOMPDF: ' . $e->getMessage());
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal Render PDF DomPDF!')
                                ->body('Pesan Error: ' . $e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();

                            if (file_exists($tempDocxPath)) { unlink($tempDocxPath); }
                            return;
                        }
                        
                        if (file_exists($tempDocxPath)) {
                            unlink($tempDocxPath);
                        }

                        $targetEmails = array_unique($targetEmails);

                        //BLAST EMAIL MENGGUNAKAN MAIL::HTML
                        try {
                            $htmlBody = '
                                <p>Halo, Bapak/Ibu Dosen FH Universitas Diponegoro,</p>
                                <p>Pemberian nomor resmi untuk permohonan Surat Tugas Anda telah selesai diproses oleh Operator Dokumen.</p>
                                <p>Berikut adalah detail rincian dokumen Anda:</p>
                                <ul>
                                    <li><strong>Nomor Surat Resmi:</strong> ' . $record->nomor_surat . '</li>
                                    <li><strong>Nama Perihal Kegiatan:</strong> ' . ($essai?->kolom_1 ?? '-') . '</li>
                                </ul>
                                <p>Silakan unduh file berkas format <strong>PDF resmi</strong> yang telah kami lampirkan bersama pesan email ini.</p>
                                <br>
                                <p>Salam Hormat,<br><strong>Bagian Tata Usaha Fakultas Hukum UNDIP</strong></p>
                            ';

                            foreach ($targetEmails as $email) {
                                Mail::html($htmlBody, function ($message) use ($email, $pdfFullPath, $record) {
                                    $message->to($email)
                                        ->subject('Surat Tugas Resmi Telah Terbit: ' . ($record->nomor_surat))
                                        ->attach($pdfFullPath); 
                                });
                            }

                            // === LANGKAH D: UPDATE DATABASE KETIKA SEMUA PROSES BERHASIL ===
                            \DB::table($record->getTable())
                                ->where('id', $record->id)
                                ->update([
                                    'status_terakhir' => 'Surat_Terbit',
                                    'file_surat_selesai' => $pdfRelativePath,
                                    'updated_at' => now()
                                ]);

                            $emailListString = empty($targetEmails) ? 'KOSONG! (Gagal narik email)' : implode(', ', $targetEmails);

                            \Filament\Notifications\Notification::make()
                                ->title('Surat Tugas Berhasil Diproses!')
                                ->body('Status berhasil diubah. Target Email: ' . $emailListString)
                                ->success()
                                ->persistent() 
                                ->send();

                        } catch (\Throwable $e) {
                            \Log::error('CRASH PADA PROSES SMTP EMAIL: ' . $e->getMessage());

                            \Filament\Notifications\Notification::make()
                                ->title('Proses Pengiriman Email Gagal!')
                                ->body('Pesan Error SMTP: ' . $e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                        
                    }),

                // 🛠️ TOMBOL 2: JALAN ARTERI (Edit Manual)
                Tables\Actions\Action::make('edit_manual')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    // Arahkan ke halaman EditPenomoranSurat
                    ->url(fn (PermohonanSurat $record): string => PenomoranSuratResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}