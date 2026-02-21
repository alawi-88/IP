<?php

namespace App\Filament\Widgets;

use App\Models\SubTrack;
use App\Models\Team;
use App\Models\Track;
use Filament\Widgets\ChartWidget;

class ProgramSubTrackPercentageStats extends ChartWidget
{
    protected static ?string $heading = 'SubTrack';

    protected function getData(): array
    {
        $allTeams = Team::byProgram()
            ->whereIn('track_id',Track::pluck('id'))
            ->count();

        return [
            'labels' => SubTrack::pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => 'Idea Distribution',
                    'data' => SubTrack::pluck('id')->map(fn($id) => [
                        number_format(Team::byProgram()->where('sub_track_id', $id)->count() / ($allTeams != 0 ? $allTeams : 1) * 100, 2)
                    ])->toArray(),
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
