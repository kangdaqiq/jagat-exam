<?php

namespace App\Providers\Filament;

use App\Filament\Resources\BankSoals\BankSoalResource;
use App\Filament\Resources\HasilUjians\HasilUjianResource;
use App\Filament\Resources\JadwalRuangs\JadwalRuangResource;
use App\Filament\Resources\RuangUjians\RuangUjianResource;
use App\Filament\Resources\Ujians\UjianResource;
use App\Filament\Widgets\TailadminCbtMetricsWidget;
use App\Filament\Widgets\TailadminLiveExamsWidget;
use App\Filament\Widgets\TailadminQuickActionWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class GuruPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('guru')
            ->path('guru')
            ->login()
            ->maxContentWidth(Width::Full)
            ->brandName('Portal Guru CBT')
            ->brandLogo(fn () => view('filament.components.brand-logo'))
            ->brandLogoHeight('2.2rem')
            ->favicon(asset('images/logo/pwa-pengawas-icon.png'))
            ->font('Outfit')
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => [
                    50 => '#ecf3ff',
                    100 => '#dde9ff',
                    200 => '#c2d6ff',
                    300 => '#9cb9ff',
                    400 => '#7592ff',
                    500 => '#465fff',
                    600 => '#3641f5',
                    700 => '#2a31d8',
                    800 => '#252dae',
                    900 => '#262e89',
                    950 => '#161950',
                ],
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
            ])
            ->resources([
                BankSoalResource::class,
                UjianResource::class,
                RuangUjianResource::class,
                JadwalRuangResource::class,
                HasilUjianResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                TailadminQuickActionWidget::class,
                TailadminCbtMetricsWidget::class,
                TailadminLiveExamsWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('@vite(["resources/css/app.css"])' . "\n" . '@include("filament.math-arabic-scripts")')
            )
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
    }
}

