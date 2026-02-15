<?php
namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Form;

class FormStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Forms', Form::count()),
            Stat::make('Registration Forms', Form::where('type', 'registration')->count()),
            Stat::make('Evaluation Forms', Form::where('type', 'evaluation')->count()),
            Stat::make('Draft Forms', Form::where('status', 'draft')->count()),
            Stat::make('Published Forms', Form::where('status', 'published')->count()),
        ];
    }
}
