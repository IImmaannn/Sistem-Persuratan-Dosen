<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisPenelitian extends Model
{
    protected $fillable = ['jenis_surat_id', 'jenis_penelitian'];

    public function jenisSurat() {
        return $this->belongsTo(JenisSurat::class);
    }
}