<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/logo.png'))
            ->colors([
                'primary' => [
                    50 => '#FFCDD2',
                    100 => '#FFCDD2',
                    200 => '#FFCDD2',
                    300 => '#FFCDD2',
                    400 => '#FFCDD2',
                    500 => '#850A0D',
                    600 => '#850A0D',
                    700 => '#8E0000',
                    800 => '#8E0000',
                    900 => '#8E0000',
                ],
                'secondary' => [
                    50 => '#90CAF9',
                    100 => '#90CAF9',
                    200 => '#90CAF9',
                    300 => '#90CAF9',
                    400 => '#90CAF9',
                    500 => '#1565C0',
                    600 => '#1565C0',
                    700 => '#003C8F',
                    800 => '#003C8F',
                    900 => '#003C8F',
                ],
                'success' => '#4CAF50',
                'warning' => '#FFA000',
                'danger' => '#D32F2F',
            ])
            ->darkMode(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                \App\Filament\Widgets\StatsOverview::class,

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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        // Add Filament Shield Plugin if available
        if (class_exists(\BezhanSalleh\FilamentShield\FilamentShieldPlugin::class)) {
            $panel->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
            ]);
        }

        return $panel;
    }
}
