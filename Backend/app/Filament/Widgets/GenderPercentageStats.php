<?php

namespace App\Filament\Widgets;

use App\Traits\HasDateRangeFilter;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\ChartWidget;
use App\Models\Participant;

class GenderPercentageStats extends ChartWidget
{
    use HasDateRangeFilter;
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Gender Distribution';

    protected function getData(): array
    {
        $from = $this->filters['startDate'] ?? null;
        $to = $this->filters['endDate'] ?? null;

        $query = Participant::query();
        $query = $this->applyDateFilter($query, $from, $to);

        $total = $query->count();
        $maleCount = $this->applyDateFilter(Participant::query()->where('gender', 'male'), $from, $to)->count();
        $femaleCount = $this->applyDateFilter(Participant::query()->where('gender', 'female'), $from, $to)->count();

        return [
            'datasets' => [
                [
                    'label' => 'Gender Distribution',
                    'data' => [
                        number_format(($maleCount / ($total ?: 1)) * 100, 2),
                        number_format(($femaleCount / ($total ?: 1)) * 100, 2),
                    ],
                    'backgroundColor' => ['#36A2EB', '#FF6384'],
                    'hoverBackgroundColor' => ['#36A2EB', '#FF6384'],
                ],
            ],
            'labels' => ['Male', 'Female'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
