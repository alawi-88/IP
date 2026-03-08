<?php

namespace App\Filament\Widgets;

use App\Enums\VentureStatus;
use App\Models\Venture;
use App\Models\VentureSection;
use App\Models\Participant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VentureStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalVentures = Venture::count();
        $activeVentures = Venture::active()->count();
        $completedVentures = Venture::where('status', VentureStatus::Completed)->count();
        $partiallyCompleted = Venture::where('status', VentureStatus::PartiallyCompleted)->count();
        $generating = Venture::where('status', VentureStatus::Generating)->count();
        $failed = Venture::where('status', VentureStatus::Failed)->count();

        $avgViability = Venture::where('viability_score', '>', 0)->avg('viability_score');
        $totalTokens = Venture::sum('total_tokens_used');
        $uniqueParticipants = Venture::distinct('participant_id')->count('participant_id');
        $totalSections = VentureSection::count();
        $completedSections = VentureSection::where('status', 'completed')->count();

        $completionRate = $totalVentures > 0
            ? round(($completedVentures + $partiallyCompleted) / $totalVentures * 100, 1)
            : 0;

        $sectionCompletionRate = $totalSections > 0
            ? round($completedSections / $totalSections * 100, 1)
            : 0;

        return [
            Stat::make('Total Ventures', $totalVentures)
                ->description("{$activeVentures} active, " . ($totalVentures - $activeVentures) . " archived")
                ->descriptionIcon('heroicon-m-light-bulb')
                ->color('primary'),

            Stat::make('Completion Rate', $completionRate . '%')
                ->description("{$completedVentures} completed, {$partiallyCompleted} partial")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($completionRate >= 50 ? 'success' : 'warning'),

            Stat::make('Avg Viability Score', $avgViability ? number_format($avgViability, 1) . '/100' : 'N/A')
                ->description('Across completed ventures')
                ->descriptionIcon('heroicon-m-star')
                ->color($avgViability >= 70 ? 'success' : ($avgViability >= 40 ? 'warning' : 'danger')),

            Stat::make('Active Participants', $uniqueParticipants)
                ->description('Unique venture creators')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Total AI Tokens', number_format($totalTokens))
                ->description('Across all generations')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('gray'),

            Stat::make('Section Completion', $sectionCompletionRate . '%')
                ->description("{$completedSections}/{$totalSections} sections completed")
                ->descriptionIcon('heroicon-m-document-check')
                ->color($sectionCompletionRate >= 70 ? 'success' : 'warning'),
        ];
    }
}
