<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeteranganEssai extends Model
{
    protected $table = 'keterangan_essais';

    // Kolom 1 sampai 7 sesuai rancangan ERD
    protected $fillable = [
        'permohonan_surat_id',
        'kolom_1', 
        'kolom_2', 
        'kolom_3', 
        'kolom_4', 
        'kolom_5', 
        'kolom_6', 
        'kolom_7',
        'anggota_tim'
    ];
    protected $casts = [
        'anggota_tim' => 'array',
    ];

    /**
     * Relasi balik ke tabel PermohonanSurat
     */
    public function permohonanSurat(): BelongsTo
    {
        return $this->belongsTo(PermohonanSurat::class, 'permohonan_surat_id');
    }
}