<?php

namespace App\Providers\Filament;

// use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Login;
use App\Models\User;
use App\Settings\KaidoSetting;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\PersetujuanSuratResource;
use App\Filament\Resources\VerifikasiPermohonanResource;
use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Forms\Components\FileUpload;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Hasnayeen\Themes\Http\Middleware\SetTheme;
use Hasnayeen\Themes\ThemesPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Rupadana\ApiService\ApiServicePlugin;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use App\Filament\Resources\PermohonanSuratResource;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class AdminPanelProvider extends PanelProvider
{
    private ?KaidoSetting $settings = null;

    public function __construct()
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $this->settings = app(KaidoSetting::class);
            }
        } catch (\Exception $e) {
            $this->settings = null;
        }
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
        ->navigationGroups([
                // Bikin Header Dropdown-nya
                NavigationGroup::make()
                    ->label('Permohonan Surat')
                    ->icon('heroicon-o-document-duplicate'),
            ])
            ->navigationItems([
                // 1. Sub-menu Surat Penelitian
                NavigationItem::make('Surat Penelitian')
                    ->group('Permohonan Surat') // Masukin ke dalam dropdown
                    // ->icon('heroicon-o-document-text')
                    // 🔥 GANTI URL DI BAWAH INI SESUAI TUJUAN TOMBOL HIJAU LO SEBELUMNYA
                    ->url(fn (): string => PermohonanSuratResource::getUrl('create', ['jenis' => 'penelitian'])) 
                    ->visible(fn (): bool => auth()->user()?->role === 'Dosen'),

                // 2. Sub-menu Surat Narasumber
                NavigationItem::make('Surat Narasumber')
                    ->group('Permohonan Surat')
                    // ->icon('heroicon-o-user-group')
                    // 🔥 GANTI URL DI BAWAH INI SESUAI TUJUAN TOMBOL BIRU LO SEBELUMNYA
                    ->url(fn (): string => PermohonanSuratResource::getUrl('create', ['jenis' => 'narasumber']))
                    ->visible(fn (): bool => auth()->user()?->role === 'Dosen'),

                // 3. Sub-menu Surat Penunjang
                NavigationItem::make('Surat Penunjang')
                    ->group('Permohonan Surat')
                    // ->icon('heroicon-o-beaker')
                    // 🔥 GANTI URL DI BAWAH INI SESUAI TUJUAN TOMBOL MERAH LO SEBELUMNYA
                    ->url(fn (): string => PermohonanSuratResource::getUrl('create', ['jenis' => 'penunjang']))
                    ->visible(fn (): bool => auth()->user()?->role === 'Dosen'),
            ])
            ->default()
            ->id('admin')
            ->path('')
            ->login()
            ->brandName('SPS DOSEN')
            ->brandLogo(asset('images/logo-undip.jpg')) // Pastikan path gambar lo bener
            ->brandLogoHeight('3rem') // Atur tinggi logo biar proporsional
            
            ->colors([
                'primary' => Color::hex('#800000'), // Kode warna Marun
            ])
            ->brandName('Form')
            // REDIRECTION UTAMA: Admin langsung ke UserResource
            ->homeUrl(fn () => match (auth()->user()?->role) {
                // 'Admin' => UserResource::getUrl(),
                'Supervisor', 'Manager', 'Wakil_Dekan', 'Dekan' => PersetujuanSuratResource::getUrl(),
                'Operator_Surat' => VerifikasiPermohonanResource::getUrl(),
                default => '/',
            })
            ->pages([
                Pages\Dashboard::class, 
            ])
            ->when($this->settings->login_enabled ?? true, fn($panel) => $panel->login(Login::class))
            ->emailVerification()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->authGuard('web')
            ->databaseNotifications()
            ->widgets([
                // Widgets\AccountWidget::class,
                // ...(auth()->user()?->role !== 'Admin' ? [Widgets\AccountWidget::class] : []),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetTheme::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins($this->getPlugins()) 
            // ->plugins([
            //     \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
            // ])


            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('
                    <style>
                        /* --- UBAH TOPBAR (HEADER) JADI MARUN --- */
                        .fi-topbar > nav {
                            background-color: #800000 !important; 
                            border-bottom: none !important;
                        }
                        .fi-topbar .fi-icon-btn, 
                        .fi-topbar button, 
                        .fi-topbar a {
                            color: #ffffff !important;
                        }

                        .fi-sidebar-header {
                            background-color: #800000 !important;
                            border-bottom: none !important;
                            border-right: none !important;
                        }

                        /* --- UBAH SIDEBAR JADI ABU-ABU GELAP --- */
                        aside.fi-sidebar, 
                        aside.fi-sidebar > nav {
                            background-color: #003049 !important;
                        }
                        .fi-logo {
                            color: #ffffff !important;
                        }
                        .fi-sidebar-group-label,
                        .fi-sidebar-item-label {
                            color: #ffffff !important;
                        }
                        .fi-sidebar-item-icon {
                            color: #d1d5db !important; 
                        }

                        /* --- UBAH MENU SIDEBAR SAAT AKTIF (KOTAK PUTIH KAKU) --- */
                        .fi-sidebar-item-active > a,
                        .fi-sidebar-item-active > button {
                            background-color: #ffffff !important;
                            border-radius: 8px !important; 
                        }
                        .fi-sidebar-item-active .fi-sidebar-item-label,
                        .fi-sidebar-item-active .fi-sidebar-item-icon {
                            color: #000000 !important;
                        }
                        .fi-sidebar-item-button:hover {
                            background-color: rgba(255, 255, 255, 0.2) !important;
                        }
                        /* Paksa menu dropdown (anaknya) terbuka jadi tipe flex (bawaan Filament) saat induknya di-hover kursor */
                        .fi-sidebar-group:hover .fi-sidebar-group-items {
                            display: flex !important;
                            visibility: visible !important;
                            opacity: 1 !important;
                            height: auto !important;
                            overflow: visible !important;
                        }
                        
                        /* (Opsional) Putar ikon panah Chevron ke atas saat di-hover biar keliatan realistis */
                        .fi-sidebar-group:hover button svg, 
                        .fi-sidebar-group:hover div[role="button"] svg {
                            /* Filament biasanya naruh efek transisi di panahnya, kita paksa putar 180 derajat */
                            transform: rotate(180deg) !important; 
                        }
                        .fi-simple-layout {
                            background-color: #800000 !important;
                        }
                        
                        /* 2. Hilangkan teks "Sign in to your account" biar polos */
                        .fi-simple-layout .fi-simple-header-heading {
                            display: none !important;
                        }
                        
                        /* 3. Bikin kotak Card lebih clean dengan shadow elegan */
                        .fi-simple-layout .fi-card {
                            border: none !important;
                            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3) !important;
                            padding-top: 1rem !important; /* Jarak logo ke atas */
                        }
                        
                        /* 4. Ubah kotak input (Email & Password) jadi abu-abu solid */
                        .fi-simple-layout .fi-input-wrapper {
                            background-color: #e5e7eb !important; /* Warna abu-abu */
                            border: none !important;
                            box-shadow: none !important;
                            border-radius: 4px !important;
                        }
                        .fi-simple-layout .fi-input-wrapper input {
                            background-color: transparent !important;
                        }
                        
                        /* 5. Paksa tombol Login jadi Biru Terang (Ngalahin warna Marun) */
                        .fi-simple-layout button[type="submit"]: hover {
                            background-color: #003049 !important;
                            border-radius: 8px !important;
                            color: white !important;
                            box-shadow: 0 4px 10px rgba(0, 48, 73, 0.3) !important;
                        }

                        /* 🔥 6. PERBESAR LOGO & ANTI GEPENG (UDAH DIPERBAIKI 100%) 🔥 */
                        .fi-simple-layout img.fi-logo {
                            height: 7rem !important; 
                            max-height: none !important; 
                            width: auto !important; 
                            display: block !important; 
                            margin: 0 auto 1.5rem auto !important; 
                            object-fit: contain !important; 
                        }
                        
                    </style>
                ')
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => Blade::render('
                    <div style="flex: 1; display: flex; justify-content: center; align-items: center; color: white; font-size: 1.5rem; font-weight: bold; letter-spacing: 1px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 2.5rem; height: 2.5rem; margin-right: 12px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
                        </svg>
                        SPS DOSEN
                    </div>
                ')
            ); 
            
    } 

    private function getPlugins(): array
    {
        $plugins = [
            ThemesPlugin::make(),
            FilamentShieldPlugin::make(),
            // ApiServicePlugin::make(),
            BreezyCore::make()
                ->myProfile(
                    shouldRegisterUserMenu: true,
                    shouldRegisterNavigation: true,
                    navigationGroup: 'Settings',
                    hasAvatars: true,
                    slug: 'my-profile'
                )
                ->avatarUploadComponent(fn() => FileUpload::make('avatar_url')->image()->disk('public'))
                ->enableTwoFactorAuthentication(),
        ];
        return $plugins;
    }
}