<?php

namespace App\Filament\Widgets;

use App\Traits\HasDateRangeFilter;
use App\Models\SubTrack;
use App\Models\Team;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ChallengePercentageStats extends ChartWidget
{
    use HasDateRangeFilter;
    use InteractsWithPageFilters;

    protected static ?string $heading = 'SubTrack';

    protected function getData(): array
    {
        $from = $this->filters['startDate'] ?? null;
        $to = $this->filters['endDate'] ?? null;

        $teamsQuery = Team::query();
        $teamsQuery = $this->applyDateFilter($teamsQuery, $from, $to);
        $allTeams = $teamsQuery->count();

        return [
            'labels' => SubTrack::pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => 'Idea Distribution',
                    'data' => SubTrack::pluck('id')->map(function ($id) use ($from, $to, $allTeams) {
                        $trackTeamsQuery = Team::where('sub_track_id', $id);
                        $trackTeamsQuery = $this->applyDateFilter($trackTeamsQuery, $from, $to);
                        return number_format($trackTeamsQuery->count() / ($allTeams != 0 ? $allTeams : 1) * 100, 2);
                    })->toArray(),
                    'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56'],
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
