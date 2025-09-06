<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\AdmissionsStatsOverview;
use App\Filament\Widgets\ChedProgramsChart;
use App\Filament\Widgets\TesdaProgramsChart;
use App\Filament\Widgets\EnrolledCoursesWidget;
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
        return $panel
            ->id('admin')
            ->path('admin')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AdmissionsStatsOverview::class,
                ChedProgramsChart::class,
                TesdaProgramsChart::class,
                EnrolledCoursesWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                // Add profile menu item
                \Filament\Navigation\MenuItem::make()
                    ->label('Profile')
                    ->url(fn (): string => route('filament.admin.resources.employees.edit', ['record' => auth()->user()->employee?->id ?? 1]))
                    ->icon('heroicon-o-user')
                    ->visible(fn (): bool => auth()->user()->employee !== null),
            ])
            ->defaultAvatarProvider(
                \App\Filament\AvatarProviders\CustomAvatarProvider::class
            );
    }
} 