<?php

namespace App\Filament\Resources\VerifikasiPermohonanResource\Pages;

use App\Filament\Resources\VerifikasiPermohonanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
// use Illuminate\Database\Eloquent\Model;


class EditVerifikasiPermohonan extends EditRecord
{
    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected static string $resource = VerifikasiPermohonanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // 1. Update data utama (Status, dll di tabel permohonan_surats)
        $record->update($data);

        // 2. Update data detail (keterangan_essais) manual
        // Kita cek apakah ada inputan 'keteranganEssai' dari form
        if (isset($data['keteranganEssai'])) {
            // Update relasi
            $record->keteranganEssai()->update($data['keteranganEssai']);
        }

        return $record;
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Cek jika OCS memilih "Lanjut ke Pimpinan"
        if ($data['status_terakhir'] === 'Terverifikasi') {
            
            // 1. Cari log terakhir di mana pimpinan melakukan penolakan (Revisi)
            $lastReject = \App\Models\LogPersetujuan::where('permohonan_id', $this->record->id)
                ->where('status_aksi', 'Revisi') // Sesuaikan dengan isi DB lo
                ->latest()
                ->first();

            // 2. Jika ada jejak penolakan, kita "Tembak" langsung ke pimpinan tersebut
            if ($lastReject) {
                $pimpinan = \App\Models\User::find($lastReject->pimpinan_id);
                
                // Logika Loncat Antrean (Fast-Track)
                $data['status_terakhir'] = match ($pimpinan->role) {
                    'Manager' => 'Disetujui_Supervisor',   // Langsung ke meja Manager
                    'Wakil_Dekan' => 'Disetujui_Manager',  // Langsung ke meja Wadek
                    'Dekan' => 'Disetujui_Wakil_Dekan',    // Langsung ke meja Dekan
                    default => 'Terverifikasi',            // Balik normal ke Supervisor
                };
            }
        }

        return $data;
    }
    // Tambahkan method ini di dalam class EditVerifikasiPermohonan
    protected function afterSave(): void
    {
        // Cek apakah OCS mengubah status menjadi 'Terverifikasi' (Lanjut ke Pimpinan)
        if ($this->record->status_terakhir === 'Terverifikasi') {
            \App\Models\LogPersetujuan::create([
                'permohonan_id' => $this->record->id,
                'pimpinan_id'   => auth()->id(), // ID si OCS
                'status_aksi'   => 'Setuju',
                'catatan'       => 'Administrasi lengkap. Diteruskan ke Pimpinan (Supervisor).',
            ]);
        }
    }

}
