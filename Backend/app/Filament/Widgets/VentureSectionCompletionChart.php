<?php

namespace App\Filament\Widgets;

use App\Models\VentureTab;
use Filament\Widgets\ChartWidget;

class VentureSectionCompletionChart extends ChartWidget
{
    protected static ?string $heading = 'Section Completion by Tab';
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $tabNames = [
            'Dashboard', 'Strategic Frameworks', 'Path to MVP',
            'Unique Selling Points', 'Customer Persona', 'Finances',
            'Go-to-Market Strategy', 'Competitive Analysis'
        ];

        $tabKeys = [
            'dashboard', 'strategic_frameworks', 'path_to_mvp',
            'unique_selling_points', 'customer_persona', 'finances',
            'go_to_market_strategy', 'competitive_analysis'
        ];

        $completedData = [];
        $failedData = [];
        $pendingData = [];

        foreach ($tabKeys as $key) {
            $tabs = VentureTab::where('tab_key', $key)->get();
            $completed = 0;
            $failed = 0;
            $other = 0;

            foreach ($tabs as $tab) {
                $sections = $tab->sections;
                foreach ($sections as $section) {
                    if ($section->status === 'completed') {
                        $completed++;
                    } elseif ($section->status === 'failed') {
                        $failed++;
                    } else {
                        $other++;
                    }
                }
            }

            $completedData[] = $completed;
            $failedData[] = $failed;
            $pendingData[] = $other;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Completed',
                    'data' => $completedData,
                    'backgroundColor' => '#10b981',
                    'borderRadius' => 2,
                ],
                [
                    'label' => 'Failed',
                    'data' => $failedData,
                    'backgroundColor' => '#ef4444',
                    'borderRadius' => 2,
                ],
                [
                    'label' => 'Pending/Generating',
                    'data' => $pendingData,
                    'backgroundColor' => '#d1d5db',
                    'borderRadius' => 2,
                ],
            ],
            'labels' => $tabNames,
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
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
