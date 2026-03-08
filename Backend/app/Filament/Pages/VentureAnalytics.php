<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\VentureStatsOverview;
use App\Filament\Widgets\VentureStatusChart;
use App\Filament\Widgets\VentureViabilityChart;
use App\Filament\Widgets\VentureTopPerformers;
use App\Filament\Widgets\VentureParticipantActivity;
use App\Filament\Widgets\VentureSectionCompletionChart;
use Filament\Pages\Page;

class VentureAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Venture Analytics';
    protected static ?string $title = 'Venture Analytics Dashboard';
    protected static ?string $navigationGroup = 'Startup Builder';
    protected static ?int $navigationSort = 0;
    protected static string $view = 'filament.pages.venture-analytics';

    public function getHeaderWidgets(): array
    {
        return [
            VentureStatsOverview::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            VentureStatusChart::class,
            VentureViabilityChart::class,
            VentureSectionCompletionChart::class,
            VentureTopPerformers::class,
            VentureParticipantActivity::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 3;
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return 2;
    }
}
