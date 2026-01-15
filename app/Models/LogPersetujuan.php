<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogPersetujuan extends Model
{
    protected $fillable = [
        'permohonan_surat_id', 
        'user_id', 
        'status', 
        'catatan'
    ];

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function permohonan() : BelongsTo {
        return $this->belongsTo(PermohonanSurat::class);
    }

    public function user() : BelongsTo {
        return $this->belongsTo(User::class);
    }
}