<?php

namespace App\Filament\Widgets;

use App\Enums\VentureStatus;
use App\Models\Venture;
use Filament\Widgets\ChartWidget;

class VentureStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Venture Status Distribution';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $statuses = [
            VentureStatus::Completed->value => ['label' => 'Completed', 'color' => '#10b981'],
            VentureStatus::PartiallyCompleted->value => ['label' => 'Partial', 'color' => '#f59e0b'],
            VentureStatus::Generating->value => ['label' => 'Generating', 'color' => '#3b82f6'],
            VentureStatus::Failed->value => ['label' => 'Failed', 'color' => '#ef4444'],
        ];

        $data = [];
        $labels = [];
        $colors = [];

        foreach ($statuses as $value => $info) {
            $count = Venture::where('status', $value)->count();
            if ($count > 0) {
                $data[] = $count;
                $labels[] = $info['label'] . " ({$count})";
                $colors[] = $info['color'];
            }
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
