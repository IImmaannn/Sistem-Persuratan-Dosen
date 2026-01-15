<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisSurat extends Model
{
    protected $fillable = ['nama_jenis'];

    public function permohonans() {
        return $this->hasMany(PermohonanSurat::class);
    }

    public function jenisPenelitians() {
        return $this->hasMany(JenisPenelitian::class);
    }
}
