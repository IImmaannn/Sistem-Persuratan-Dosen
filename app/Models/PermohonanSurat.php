<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PermohonanSurat extends Model
{
    protected $fillable = [
        'user_id', 
        'config_id', 
        // 'keterangan_essai_id', 
        'nomor_surat', 
        'file_surat_selesai', 
        'status_terakhir'
    ];

    protected $guarded = ['id'];
    protected $hidden = ['created_at', 'updated_at'];

    // Relasi balik ke User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

        // Relasi ke Config (Jenis Surat)
    public function config(): BelongsTo {
            return $this->belongsTo(Config::class, 'config_id');
    }

    // Relasi ke Detail Form (Keterangan Essai)
    public function keteranganEssai(): HasOne {
            return $this->hasOne(KeteranganEssai::class, 'permohonan_surat_id');
    }



}