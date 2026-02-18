<?php

namespace App\Providers\Filament;

use Althinect\FilamentSpatieRolesPermissions\FilamentSpatieRolesPermissionsPlugin;
use App\Filament\Pages\Auth\CustomLogin;
use App\Filament\Pages\CompetitionStatistics;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\EnsureOtpIsVerified;
use App\Livewire\CustomProfileComponent;
use App\Models\Competition;
use App\Models\UserCompetition;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use Rmsramos\Activitylog\ActivitylogPlugin;
use App\Filament\Pages\BrandingSettings;
use App\Filament\Pages\NafathSettingsPage;
use App\Models\BrandingSetting;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\Schema;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $branding = null;
        if (Schema::hasTable('branding_settings')) {
            $branding = BrandingSetting::current();
        }

        $app = str(config('app.name'))->lower();
        $app = trim(str_replace( 'system', '', $app));
        //primary color
        $primary_color = match (true) {
            blank($branding?->primary_color) && $app === 'filmathon' => '#ED593E',
            blank($branding?->primary_color) && $app === 'innovation' => '#6E62E5',
            blank($branding?->primary_color) && $app === 'judging' => '#007298',
            blank($branding?->primary_color) => '#000000',
            default => $branding?->primary_color,
        };
        //secondary color
        $secondary_color = blank($branding?->secondary_color)
            ? $primary_color
            : $branding?->secondary_color;

        return $panel
            ->renderHook('panels::head.start', function () {
                return <<<HTML
                <style>
                    /* Rotate the arrow icon used for import progress */
                    .import-rotate svg {
                        animation: spin 1s linear infinite;
                        transform-origin: center;
                    }

                    @keyframes spin {
                        from { transform: rotate(0deg); }
                        to   { transform: rotate(360deg); }
                    }
                </style>
                <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
                HTML;
            })
            ->renderHook('panels::topbar.start', function () {
                $current = Competition::find(session('current_competition_id'));

                if (! $current) return null;

                $user = auth()->user();

                if ($user->isSuperAdmin()) {
                    $competitions = Competition::where('id', '!=', $current->id)->active()->get();
                } else {
                    $supervisorCompetitions = UserCompetition::where('user_id', $user->id)->pluck('competition_id')->toArray();

                    $competitions = Competition::where('id', '!=', $current->id)
                        ->whereIn('id', $supervisorCompetitions)
                        ->active()
                        ->get();
                }

                $currentStage = $current->currentStage();
                $stageTitle = $currentStage ? $currentStage->getTranslation('title', 'en') : null;
                //return view('filament.components.competition-header', compact('current', 'competitions', 'currentStage'))->render();

                return view('filament.components.competition-header', [
                    'current' => $current,
                    'competitions' => $competitions,
                    'currentStage' => $currentStage,
                    'stageTitle' => $stageTitle
                ])->render();
            })
            ->default()
            ->id('admin')
            ->path('admin/')
            ->login(CustomLogin::class)
            ->profile()
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('Profile')
                    ->url(fn(): string => EditProfilePage::getUrl())
                    ->icon('heroicon-m-user-circle')
                    ->visible(function (): bool {
                        return auth()->check();
                    }),
            ])
            ->plugins([
                FilamentEditProfilePlugin::make()
                    ->customProfileComponents([
                        CustomProfileComponent::class,
                    ])
                    ->shouldShowDeleteAccountForm(false)
                    ->shouldShowBrowserSessionsForm(false)
                    ->shouldShowEditProfileForm(false)
                    ->shouldRegisterNavigation(false),
                ActivitylogPlugin::make()
                    ->navigationSort(93)
            ])
            ->databaseNotifications()
            ->darkModeBrandLogo(
                $branding && $branding->white_logo
                    ? url($branding->white_logo)
                    : url('media/' . $app . '-dark-logo.png')
            )
            ->brandLogo(
                $branding && $branding->logo
                    ? url($branding->logo)
                    : url('media/' . $app . '-light-logo.png')
            )
            ->favicon(
                $branding && $branding->favicon
                    ? url($branding->favicon)
                    : url('media/' . $app . '-favicon.png')
            )
            ->colors([
                'primary' => $primary_color,
                'gray' => $secondary_color,
            ])
            ->font($branding->font ?? 'Inter')
            ->passwordReset()
            ->sidebarWidth('13rem')
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Programs'),
                NavigationGroup::make('Forms & Content'),
                NavigationGroup::make('Forms & Configuration'),
                NavigationGroup::make('Users & Roles'),
                NavigationGroup::make('Notifications & Approvals'),
                NavigationGroup::make('Approvals & Communication'),
                NavigationGroup::make('System'),
                NavigationGroup::make('AI & Automation'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->pages([
                Dashboard::class,
                CompetitionStatistics::class,
                BrandingSettings::class,
                NafathSettingsPage::class,
                \App\Filament\Pages\MentorshipAnalytics::class,
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
                EnsureOtpIsVerified::class,
            ]);
    }
}
