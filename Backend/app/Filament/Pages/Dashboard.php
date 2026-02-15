<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CompetitionResource\Widgets\StatsCount;
use App\Filament\Widgets\ChallengePercentageStats;
use App\Filament\Widgets\EducationalBackgroundPercentageStats;
use App\Filament\Widgets\ExperiencePercentageStats;
use App\Filament\Widgets\GenderPercentageStats;
use App\Filament\Widgets\RolePercentageStats;
use App\Filament\Widgets\TrackPercentageStats;
use App\Filament\Widgets\YearsOfExperiencePercentageStats;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Actions\Action;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    // protected static string $view = 'filament.pages.dashboard';

    protected static string $routePath = '/admin';

    protected static ?string $navigationLabel = 'Platform Statistics';

    protected static ?int $navigationSort = 1;

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Section::make()
                ->schema([
                    DatePicker::make('startDate')
                        ->label('From')
                        ->maxDate(now()),
                    DatePicker::make('endDate')
                        ->label('To')
                        ->maxDate(now()),
                ])
                ->columns(2),
        ]);
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('resetFilters')
                ->label('Reset Filters')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->action('resetFilters')
                ->visible(fn() => filled($this->filters['startDate'] ?? null)
                    || filled($this->filters['endDate']   ?? null)),
        ];
    }

    public function resetFilters(): void
    {
        $this->filters = [];

        $this->filtersForm->fill([]);
    }

    public function getColumns(): int|string|array
    {
        return 3;
    }

    public function getWidgets(): array
    {
        return [
            StatsCount::class,
            TrackPercentageStats::class,
            ChallengePercentageStats::class,
            GenderPercentageStats::class,
            EducationalBackgroundPercentageStats::class,
            YearsOfExperiencePercentageStats::class,
            RolePercentageStats::class,
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view Statistics');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view Statistics');
    }
}
