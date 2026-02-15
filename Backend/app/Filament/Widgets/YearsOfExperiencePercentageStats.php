<?php

namespace App\Filament\Widgets;

use App\Models\Participant;
use App\Traits\HasDateRangeFilter;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\ChartWidget;

class YearsOfExperiencePercentageStats extends ChartWidget
{
    use HasDateRangeFilter;
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Years of Experience';

    protected int | string | array $columnStart = 2;

    protected function getData(): array
    {
        $from = $this->filters['startDate'] ?? null;
        $to = $this->filters['endDate'] ?? null;

        $experiences = [
            'less_than_one' => 'Less than 1 year',
            'one_to_three' => '1-3 years',
            'three_to_five' => '3-5 years',
            'five_to_ten' => '5-10 years',
            'more_than_ten' => 'More than 10 years',
            'no_experience' => 'No experience'
        ];

        $query = Participant::query();
        $query = $this->applyDateFilter($query, $from, $to);
        $total = $query->count();

        $data = collect($experiences)->map(function ($label, $value) use ($from, $to, $total) {
            $query = Participant::where('years_of_experience', $value);
            $count = $this->applyDateFilter($query, $from, $to)->count();
            return number_format(($count / ($total ?: 1)) * 100, 2);
        })->values()->toArray();

        return [
            'labels' => array_values($experiences),
            'datasets' => [
                [
                    'label' => 'Years of Experience Distribution',
                    'data' => $data,
                    'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'],
                    'hoverBackgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
