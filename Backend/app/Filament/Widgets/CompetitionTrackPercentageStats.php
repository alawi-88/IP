<?php

namespace App\Filament\Widgets;

use App\Models\Path;
use App\Models\Team;
use App\Models\Track;
use App\Traits\HasDateRangeFilter;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class CompetitionTrackPercentageStats extends ChartWidget
{
    use HasDateRangeFilter;
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Track';

    protected function getData(): array
    {
        $from = $this->filters['startDate'] ?? null;
        $to = $this->filters['endDate'] ?? null;

        $teamsQuery = Team::byCompetition();
        $teamsQuery = $this->applyDateFilter($teamsQuery, $from, $to);
        $allTeams = $teamsQuery->count();

        return [
            'labels' => Track::pluck('name')->toArray(), // Get path titles as labels
            'datasets' => [
                [
                    'label' => 'Idea Distribution',
                    'data' => Track::pluck('id')->map(function ($id) use ($from, $to, $allTeams) {
                        $trackTeamsQuery = Team::byCompetition()->where('track_id', $id);
                        $trackTeamsQuery = $this->applyDateFilter($trackTeamsQuery, $from, $to);
                        return [
                            number_format($trackTeamsQuery->count() / ($allTeams ?: 1) * 100, 2)
                        ];
                    })->toArray(),
                    'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56'], // Colors for pie chart
                    'hoverBackgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56'],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
