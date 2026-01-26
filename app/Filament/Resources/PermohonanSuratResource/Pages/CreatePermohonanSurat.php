<?php

namespace App\Filament\Resources\PermohonanSuratResource\Pages;

use App\Filament\Resources\PermohonanSuratResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePermohonanSurat extends CreateRecord
{
    protected static string $resource = PermohonanSuratResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Permohonan surat berhasil dikirim!';
    }

   protected function mutateFormDataBeforeCreate (array $data): array
    {
        // 1. Ambil data kolom 1-7 yang ada di dalam array relasi
        // Filament biasanya membungkus data ini sesuai nama relasi di Resource
        $detailData = $data['keteranganEssai'] ?? null;

        if ($detailData) {
            // 2. KITA BIKIN MANUAL record di tabel keterangan_essais SEKARANG JUGA
            $detail = \App\Models\KeteranganEssai::create($detailData);
            
            // 3. Masukkan ID yang baru saja dibuat ke kolom FK di tabel utama
            // Ini kuncinya agar di Navicat tidak NULL lagi
            $data['keterangan_essai_id'] = $detail->id;
            
            // Hapus array detail dari $data agar tidak diproses ulang oleh Filament
            // unset($data['keteranganEssai']);
        }

        // 4. Logika config_id dan user_id yang sudah ada
        $type = request()->query('type');

        // Paksa isi config_id berdasarkan tipe URL
        if ($type === 'penunjang') {
            $data['config_id'] = 3; // ID 3 = Penunjang
        } elseif ($type === 'narasumber') {
            $data['config_id'] = 2; // ID 2 = Narasumber
        }
        
        $data['user_id'] = auth()->id();

        return $data;
    }
}
