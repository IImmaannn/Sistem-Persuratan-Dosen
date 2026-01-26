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
        if (!isset($data['config_id'])) {
            $type = request()->query('type') ?? 'penelitian';
            $config = \App\Models\Config::where('key', $type)->first();
            if ($config) {
                $data['config_id'] = $config->id;
            }
        }
        
        $data['user_id'] = auth()->id();

        return $data;
    }
}
