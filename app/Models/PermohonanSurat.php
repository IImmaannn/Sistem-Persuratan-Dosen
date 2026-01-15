<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermohonanSurat extends Model
{
    protected $fillable = [
        'user_id', 
        'jenis_surat_id', 
        'keterangan_esai', 
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

    public function jenisSurat() {
    return $this->belongsTo(JenisSurat::class);
    }

    public function jenisPenelitian() {
    return $this->belongsTo(JenisPenelitian::class);
    }

}