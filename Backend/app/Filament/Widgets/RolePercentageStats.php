<?php

namespace App\Filament\Widgets;

use App\Models\Participant;
use App\Traits\HasDateRangeFilter;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\ChartWidget;

class RolePercentageStats extends ChartWidget
{
    use HasDateRangeFilter;
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Current Role';

    protected int | string | array $columnStart = 3;

    protected function getData(): array
    {
        $from = $this->filters['startDate'] ?? null;
        $to = $this->filters['endDate'] ?? null;

        $roles = ['high_school_student', 'university_student', 'recently_graduated', 'private_sector_employee', 'government_sector_employee', 'non_profit_sector_employee', 'freelancer', 'unemployed'];
        $query = Participant::query();
        $query = $this->applyDateFilter($query, $from, $to);
        $total = $query->count();

        $data = collect($roles)->map(function ($role) use ($from, $to, $total) {
            $roleQuery = Participant::where('current_role', $role);
            $count = $this->applyDateFilter($roleQuery, $from, $to)->count();
            return number_format(($count / ($total ?: 1)) * 100, 2);
        })->values()->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Role Distribution',
                    'data' => $data,
                    'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56', '#FF6384', '#36A2EB', '#FFCE56', '#FF6384', '#36A2EB'],
                    'hoverBackgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56', '#FF6384', '#36A2EB', '#FFCE56', '#FF6384', '#36A2EB'],
                ],
            ],
            'labels' => ['High School Student', 'University Student', 'Recent Graduate', 'Private Sector Employee', 'Government Sector Employee', 'Non-Profit Sector Employee', 'Freelancer', 'Unemployed'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
