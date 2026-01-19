<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Config extends Model
{
    // Nama tabel di database
    protected $table = 'configs';

    // Kolom yang boleh diisi secara massal
    protected $fillable = [
        'kategori', // Contoh: 'jenis_surat' atau 'jenis_penelitian'
        'key',      // Slug atau identitas singkat
        'value',    // Teks yang akan muncul di UI
    ];

    /**
     * Relasi ke tabel PermohonanSurat
     */
    public function permohonanSurat(): HasMany
    {
        return $this->hasMany(PermohonanSurat::class, 'config_id');
    }
}