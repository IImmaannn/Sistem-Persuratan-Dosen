<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemAsset extends Model
{
    use HasFactory;

    // Kunci nama tabelnya karena kita pakai custom name
    protected $table = 'system_assets';

    // Kolom yang diizinkan untuk diisi mass-assignment
    protected $fillable = [
        'nama_aset',
        'key_aset',
        'file_path',
    ];
}