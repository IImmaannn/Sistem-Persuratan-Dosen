<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
        $data = [
            // Kategori Jenis Surat
            ['kategori' => 'jenis_surat', 'key' => 'penelitian', 'value' => 'Surat Penelitian'],
            ['kategori' => 'jenis_surat', 'key' => 'narasumber', 'value' => 'Surat Narasumber'],
            ['kategori' => 'jenis_surat', 'key' => 'penunjang', 'value' => 'Surat Penunjang'],

            // Kategori Detail (Dulu Jenis Penelitian)
            ['kategori' => 'jenis_penelitian', 'key' => 'jurnal', 'value' => 'Jurnal Penelitian'],
            ['kategori' => 'jenis_penelitian', 'key' => 'haki', 'value' => 'HAKI'],
        ];

        foreach ($data as $item) {
            \App\Models\Config::create($item);
        }
    }
}
