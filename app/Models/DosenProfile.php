<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DosenProfile extends Model
{
    // Tambahkan kolom yang boleh diisi (mass assignment)
    protected $fillable = [
        'user_id', 
        'nip', 
        'pangkat', 
        'jabatan'
    ];

    protected $guarded = ['id'];
    protected $hidden = ['created_at', 'updated_at'];

    // Relasi balik ke User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}