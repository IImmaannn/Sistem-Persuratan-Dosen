<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemAsset;

class SystemAssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data default yang wajib dikunci kodenya untuk mesin surat lo
        $assets = [
            [
                'nama_aset' => 'Tanda Tangan Dekan',
                'key_aset' => 'ttd_dekan',
                'file_path' => null, // Mula-mula kosong sebelum di-upload admin
            ],
            [
                'nama_aset' => 'Cap Resmi Fakultas',
                'key_aset' => 'cap_fakultas',
                'file_path' => null,
            ],
            [
                'nama_aset' => 'Logo Kop Surat',
                'key_aset' => 'logo_kop',
                'file_path' => null,
            ],
        ];

        foreach ($assets as $asset) {
            // Menggunakan updateOrCreate agar kalau di-seed ulang datanya gak dobel
            SystemAsset::updateOrCreate(
                ['key_aset' => $asset['key_aset']], 
                $asset
            );
        }
    }
}