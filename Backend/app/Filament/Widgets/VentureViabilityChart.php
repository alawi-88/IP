<?php

namespace App\Filament\Widgets;

use App\Models\Venture;
use Filament\Widgets\ChartWidget;

class VentureViabilityChart extends ChartWidget
{
    protected static ?string $heading = 'Viability Score Distribution';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $ranges = [
            '0-20' => [0, 20, '#ef4444'],
            '21-40' => [21, 40, '#f97316'],
            '41-60' => [41, 60, '#f59e0b'],
            '61-80' => [61, 80, '#84cc16'],
            '81-100' => [81, 100, '#10b981'],
        ];

        $data = [];
        $labels = [];
        $colors = [];

        foreach ($ranges as $label => [$min, $max, $color]) {
            $count = Venture::where('viability_score', '>=', $min)
                ->where('viability_score', '<=', $max)
                ->count();
            $data[] = $count;
            $labels[] = $label;
            $colors[] = $color;
        }

        // Also count "Not Scored" (score = 0 or null)
        $notScored = Venture::where(function ($q) {
            $q->whereNull('viability_score')->orWhere('viability_score', 0);
        })->count();

        if ($notScored > 0) {
            $data[] = $notScored;
            $labels[] = 'Not Scored';
            $colors[] = '#9ca3af';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ventures',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
