<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles; // Cukup 1 kali import ini
use Spatie\MediaLibrary\HasMedia; 
use Spatie\MediaLibrary\InteractsWithMedia; 
use BezhanSalleh\FilamentShield\Traits\HasPanelShield; // Tambahin ini

class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmail, HasMedia
{
    use HasFactory, Notifiable, HasRoles, TwoFactorAuthenticatable, HasApiTokens, InteractsWithMedia, HasPanelShield;
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
    // Cuma Super Admin yang boleh ngerasukin akun orang lain
    public function canImpersonate()
    {
        return $this->hasRole('super_admin');
    }

    // Tentukan siapa aja yang BOLEH dirasuki (dalam hal ini, Super Admin gak boleh dirasuki)
    public function canBeImpersonated()
    {
        return !$this->hasRole('super_admin');
    }

    public function profile()
    {
        return $this->hasOne(DosenProfile::class); 
    }

    public function permohonans()
    {
        return $this->hasMany(PermohonanSurat::class);
    }
}