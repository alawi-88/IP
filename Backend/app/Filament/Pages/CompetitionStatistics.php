<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CompetitionResource\Widgets\CompetitionStatsCount;
use App\Filament\Widgets\CompetitionSubTrackPercentageStats;
use App\Filament\Widgets\CompetitionTrackPercentageStats;
use Filament\Pages\Page;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Actions\Action;

class CompetitionStatistics extends BaseDashboard
{
    use HasFiltersForm;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Program Statistics';

    protected static ?string $breadcrumb = 'Program Statistics';
    protected static string $routePath = '/competition-statistics';

    protected static ?string $navigationGroup = 'Programs';

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

    // Define the route path for this page

    public function getWidgets(): array
    {
        return [
            CompetitionStatsCount::class,
            CompetitionTrackPercentageStats::class,
            CompetitionSubTrackPercentageStats::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Program Statistics';
    }

//    public static function canView(): bool
//    {
//        return auth()->user()?->can('view Statistics');
//    }
//
//    public static function shouldRegisterNavigation(): bool
//    {
//        return auth()->user()?->can('view Statistics');
//    }
}
