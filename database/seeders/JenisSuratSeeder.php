<?php

namespace Database\Seeders;

use App\Models\JenisSurat;
use Illuminate\Database\Seeder;

class JenisSuratSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama_jenis' => 'Penelitian'],
            ['nama_jenis' => 'Narasumber'],
            ['nama_jenis' => 'Pengabdian Masyarakat'],
            ['nama_jenis' => 'Surat Tugas'],
            ['nama_jenis' => 'Penunjang'],
        ];

        foreach ($data as $item) {
            JenisSurat::create($item);
        }
    }
}