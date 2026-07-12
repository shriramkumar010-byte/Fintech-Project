<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Widgets\CibilOverviewStats;
use App\Filament\Widgets\CibilScoreDistributionChart;
use App\Filament\Widgets\LoanApplicationsChart;
use App\Filament\Widgets\LoanApplicationStats;
use App\Filament\Widgets\RecentLoanApplications;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use App\Filament\Pages\Auth\EditProfile;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(EditProfile::class)
            ->brandName('Capsafe Fintech')
            ->favicon(asset('images/favicon.ico'))
            ->brandLogo(asset('asset/images/logo/logo.png'))
            ->passwordReset()
            ->passwordReset(RequestPasswordReset::class)
            ->databaseNotifications()
            ->pages([
                Pages\Dashboard::class,
            ])
            ->colors([
                'primary' => "#0071BC",
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                CibilOverviewStats::class,
                CibilScoreDistributionChart::class,
                LoanApplicationsChart::class,
                LoanApplicationStats::class,
                RecentLoanApplications::class,
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
            ->plugins([
                FilamentShieldPlugin::make(),
            ]);
        }
}
