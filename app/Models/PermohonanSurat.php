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
        return $this->belongsTo(User::class, 'user_id');
    }

        // Relasi ke Config (Jenis Surat)
    public function config(): BelongsTo {
            return $this->belongsTo(Config::class, 'config_id');
    }

    // Relasi ke Detail Form (Keterangan Essai)
    public function keteranganEssai(): HasOne {
            return $this->hasOne(KeteranganEssai::class, 'permohonan_surat_id');
    }
    public function logPersetujuans()
    {
        // Gunakan 'permohonan_id' sesuai yang terlihat di error SQL lo sebelumnya
        return $this->hasMany(LogPersetujuan::class, 'permohonan_id');
    }



}