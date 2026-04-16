<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    // Hilangkan menu 'Dashboard' asli dari Sidebar buat Admin
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->role !== 'Admin';
    }

    // Blokir akses manual via URL buat Admin
    public static function canView(): bool
    {
        return auth()->user()->role !== 'Admin';
    }
}