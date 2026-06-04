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
                Widgets\AccountWidget::class,
                ...(auth()->user()?->role !== 'Admin' ? [Widgets\AccountWidget::class] : []),
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
            ->plugins($this->getPlugins());
            // ->plugins([
            //     \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
            // ]);
            
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

        if ($this->settings->sso_enabled ?? true) {
            $plugins[] = FilamentSocialitePlugin::make()
                ->providers([
                    Provider::make('google')->label('Google')->icon('fab-google')->color(Color::hex('#2f2a6b'))->outlined(true)
                ])
                ->registration(true)
                ->createUserUsing(function ($provider, $oauthUser) {
                    return User::updateOrCreate(
                        ['email' => $oauthUser->getEmail()],
                        ['name' => $oauthUser->getName(), 'email_verified_at' => now()]
                    );
                });
        }
        return $plugins;
    }
}