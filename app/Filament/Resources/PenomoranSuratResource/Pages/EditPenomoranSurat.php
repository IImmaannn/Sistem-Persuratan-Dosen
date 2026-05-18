<?php

namespace App\Filament\Resources\PenomoranSuratResource\Pages;

use App\Filament\Resources\PenomoranSuratResource;
use App\Models\SystemAsset;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EditPenomoranSurat extends EditRecord
{
    protected static string $resource = PenomoranSuratResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * OTOMATIS REDIRECT BALIK KE TABEL UTAMA SETELAH BERHASIL SAVE
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * OTOMATISASI PASCA KLIK TOMBOL 'SAVE CHANGES' (VERSI SINKRONISASI TOTAL)
     */
    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $essai = $record->keteranganEssai; // Ambil data biner teks pembantu

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
                ->body('Sistem tidak menemukan file fisik template di server storage. Silakan periksa halaman Asset.')
                ->danger()
                ->send();
            return;
        }

        $templatePath = storage_path('app/public/' . $templateFile);
        $templateProcessor = new TemplateProcessor($templatePath);

        // Isi placeholder teks dokumen standar dari tabel nomor surat dan tabel essai bray
        $templateProcessor->setValue('NOMOR_SURAT', $record->nomor_surat);
        $templateProcessor->setValue('kolom_1', $essai?->kolom_1 ?? '-');
        $templateProcessor->setValue('kolom_2', $essai?->kolom_2 ?? '-');
        $templateProcessor->setValue('kolom_3', $essai?->kolom_3 ?? '-');
        $templateProcessor->setValue('kolom_4', $essai?->kolom_4 ?? '-');

        // KUMPULKAN DATA DOSEN SECARA AKURAT (PENGAJU VIA RELASI USER)
        $namaDosenArr = [];
        $nipDosenArr = [];
        $pangkatDosenArr = [];
        $targetEmails = [];

        if ($record->user) {
            $namaDosenArr[] = $record->user->name;
            $nipDosenArr[] = $record->user->profile?->nip ?? '-';
            $pangkatDosenArr[] = $record->user->profile?->pangkat_golongan ?? '-';
            
            if ($record->user->email) {
                $targetEmails[] = $record->user->email;
            }
        }

        // KUMPULKAN DATA DOSEN ANGOTA KELOMPOK JIKA ADA (DARI REPEATER JSON ANGGOTA TIM)
        if ($essai && is_array($essai->anggota_tim)) {
            foreach ($essai->anggota_tim as $anggota) {
                if (!empty($anggota['user_id'])) {
                    $userAnggota = User::find($anggota['user_id']);
                    if ($userAnggota) {
                        $namaDosenArr[] = $userAnggota->name;
                        $nipDosenArr[] = $userAnggota->profile?->nip ?? '-';
                        $pangkatDosenArr[] = $userAnggota->profile?->pangkat_golongan ?? '-';
                        if ($userAnggota->email) {
                            $targetEmails[] = $userAnggota->email;
                        }
                    }
                }
            }
        }

        $templateProcessor->setValue('NAMA_DOSEN', implode(", ", $namaDosenArr));
        $templateProcessor->setValue('NIP_DOSEN', implode(" / ", $nipDosenArr));
        $templateProcessor->setValue('PANGKAT_DOSEN', implode(" / ", $pangkatDosenArr));

        // Sinkronisasi Gambar TTD berdasarkan key_aset di halaman admin bray
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

        // Simpan file sementara Word (.docx)
        $tempDocxName = 'temp_mail_surat_' . time() . '.docx';
        $tempDocxPath = storage_path('app/public/' . $tempDocxName);
        $templateProcessor->saveAs($tempDocxPath);


        // === LANGKAH B: KONVERSI KE PDF & SIMPAN PERMANEN KE STORAGE ===
        try {
            ini_set('memory_limit', '512M');

            Settings::setPdfRendererName(Settings::PDF_RENDERER_MPDF);
            Settings::setPdfRendererPath(base_path('vendor/mpdf/mpdf'));

            $phpWord = IOFactory::load($tempDocxPath);
            
            $pdfFolder = 'surat_selesai';
            if (!Storage::disk('public')->exists($pdfFolder)) {
                Storage::disk('public')->makeDirectory($pdfFolder);
            }

            $phpWord->setDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(12);

            // ============================================================
            // 🔥 KODE PENYELAMAT KOP SURAT: HAPUS PAKSA BORDER TABEL 🔥
            // ============================================================
            // Sistem akan menyisir semua tabel (termasuk tabel Kop Surat) 
            // dan memaksa ukuran bingkainya menjadi 0 (Tanpa Bingkai)
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                        $tableStyle = $element->getStyle();
                        
                        // Set ukuran border luar menjadi 0
                        $tableStyle->setBorderTopSize(0);
                        $tableStyle->setBorderBottomSize(0);
                        $tableStyle->setBorderLeftSize(0);
                        $tableStyle->setBorderRightSize(0);
                        
                        // Set ukuran border dalam (gridlines) menjadi 0
                        $tableStyle->setInsideHSize(0); // Garis horizontal dalam
                        $tableStyle->setInsideVSize(0); // Garis vertikal dalam
                    }
                }
            }

            // 2. Kunci ukuran kertas & margin biar tetep 1 halaman rapi
            foreach ($phpWord->getSections() as $section) {
                $sectionStyle = $section->getStyle();
                $sectionStyle->setPageSizeW(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(21)); 
                $sectionStyle->setPageSizeH(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(29.7)); 
                $sectionStyle->setMarginTop(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(1.5));  
                $sectionStyle->setMarginBottom(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(1.5));
                $sectionStyle->setMarginLeft(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2));
                $sectionStyle->setMarginRight(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2));
            }
            
            $pdfName = 'Surat_Tugas_Resmi_' . $record->id . '_' . time() . '.pdf';
            $pdfRelativePath = $pdfFolder . '/' . $pdfName; 
            $pdfFullPath = storage_path('app/public/' . $pdfRelativePath); 
            
            $xmlWriter = IOFactory::createWriter($phpWord, 'PDF');
            $xmlWriter->save($pdfFullPath);

        } catch (\Throwable $e) {
            \Log::error('CRASH PADA PROSES MPDF: ' . $e->getMessage());
            if (file_exists($tempDocxPath)) { unlink($tempDocxPath); }
            return;
        }

        if (file_exists($tempDocxPath)) {
            unlink($tempDocxPath);
        }

        $targetEmails = array_unique($targetEmails);


        // === LANGKAH C: BLAST EMAIL MENGGUNAKAN MAIL::HTML ===
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

            // === LANGKAH D: UPDATE DATA DATABASE (FIX KUNCI UTAMA) ===
            // 1. Kita ubah status_terakhir menjadi 'Surat_Terbit' agar otomatis tendang keluar dari antrean dashboard!
            // 2. Simpan jalur file fisik ke kolom file_surat_selesai
            $record->update([
                'status_terakhir' => 'Surat_Terbit', 
                'file_surat_selesai' => $pdfRelativePath, 
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Surat Tugas Berhasil Diproses!')
                ->body('Nomor resmi disematkan, berkas disimpan, dan PDF sudah meluncur ke Mailtrap!')
                ->success()
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
    }
}