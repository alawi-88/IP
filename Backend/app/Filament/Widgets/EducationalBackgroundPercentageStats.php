<?php

namespace App\Filament\Widgets;

use App\Models\Participant;
use App\Traits\HasDateRangeFilter;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\ChartWidget;

class EducationalBackgroundPercentageStats extends ChartWidget
{
    use HasDateRangeFilter;
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Educational Background Distribution';

    protected int | string | array $columnStart = 1;

    protected function getData(): array
    {
        $from = $this->filters['startDate'] ?? null;
        $to = $this->filters['endDate'] ?? null;

        $query = Participant::query();
        $query = $this->applyDateFilter($query, $from, $to);
        $total = $query->count();

        $backgrounds = ['undergraduate', 'graduate', 'phd', 'other'];
        $counts = [];
        $percentages = [];

        foreach ($backgrounds as $background) {
            $countQuery = Participant::where('educational_background', $background);
            $count = $this->applyDateFilter($countQuery, $from, $to)->count();
            $counts[] = $count;
            $percentages[] = number_format(($count / ($total ?: 1)) * 100, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Educational Background Distribution',
                    'data' => $percentages,
                    'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
                    'hoverBackgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
                ],
            ],
            'labels' => array_map('ucfirst', $backgrounds),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
