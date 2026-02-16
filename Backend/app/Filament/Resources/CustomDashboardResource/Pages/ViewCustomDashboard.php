<?php

namespace App\Filament\Resources\CustomDashboardResource\Pages;

use App\Filament\Resources\CustomDashboardResource;
use App\Models\Competition;
use App\Models\Dashboard;
use App\Services\DashboardAggregationService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;

class ViewCustomDashboard extends Page
{
    protected static string $resource = CustomDashboardResource::class;

    protected static string $view = 'filament.resources.custom-dashboard-resource.pages.view-custom-dashboard';

    public Dashboard $record;

    #[Url]
    public ?string $filterCompetition = null;

    #[Url]
    public ?string $filterStatus = null;

    #[Url]
    public ?string $filterDateFrom = null;

    #[Url]
    public ?string $filterDateTo = null;

    public array $widgetData = [];
    public bool $isLoading = true;
    public ?string $errorMessage = null;

    public function mount(int|string $record): void
    {
        $this->record = Dashboard::with('widgets.formField', 'creator')->findOrFail($record);
        $this->loadDashboardData();
    }

    public function getTitle(): string
    {
        return $this->record->getTranslation('name', app()->getLocale()) ?? __('dashboard.view_dashboard');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label(__('dashboard.edit'))
                ->url(fn () => CustomDashboardResource::getUrl('edit', ['record' => $this->record]))
                ->icon('heroicon-o-pencil')
                ->visible(fn () => !$this->record->isArchived()),

            Actions\Action::make('exportCsv')
                ->label(__('dashboard.export_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => $this->exportCsv()),

            Actions\Action::make('exportExcel')
                ->label(__('dashboard.export_excel'))
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->action(fn () => $this->exportExcel()),

            Actions\Action::make('restore')
                ->label(__('dashboard.restore'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->isArchived())
                ->action(function () {
                    $this->record->restore();
                    Notification::make()
                        ->title(__('dashboard.dashboard_restored'))
                        ->success()
                        ->send();
                    $this->redirect(CustomDashboardResource::getUrl('index'));
                }),
        ];
    }

    public function loadDashboardData(): void
    {
        $this->isLoading = true;
        $this->errorMessage = null;

        try {
            $filters = array_filter([
                'competition_id' => $this->filterCompetition,
                'status' => $this->filterStatus,
                'date_from' => $this->filterDateFrom,
                'date_to' => $this->filterDateTo,
            ]);

            $service = new DashboardAggregationService(
                $this->record->competition_id ?? currentCompetitionId(),
                $filters
            );

            $this->widgetData = $service->getDashboardData($this->record);
            $this->isLoading = false;
        } catch (\Exception $e) {
            $this->errorMessage = __('dashboard.error_loading');
            $this->isLoading = false;
            report($e);
        }
    }

    public function applyFilters(): void
    {
        DashboardAggregationService::clearDashboardCache($this->record->id);
        $this->loadDashboardData();
    }

    public function resetFilters(): void
    {
        $this->filterCompetition = null;
        $this->filterStatus = null;
        $this->filterDateFrom = null;
        $this->filterDateTo = null;
        $this->applyFilters();
    }

    public function exportCsv()
    {
        try {
            $service = new DashboardAggregationService(
                $this->record->competition_id ?? currentCompetitionId(),
                array_filter([
                    'competition_id' => $this->filterCompetition,
                    'status' => $this->filterStatus,
                    'date_from' => $this->filterDateFrom,
                    'date_to' => $this->filterDateTo,
                ])
            );

            $data = $service->exportData($this->record);
            $filename = 'dashboard_' . str_replace(' ', '_', $this->record->getTranslation('name', 'en')) . '_' . now()->format('Y-m-d') . '.csv';

            $csv = implode(',', $data['headers']) . "\n";
            foreach ($data['rows'] as $row) {
                $csv .= implode(',', array_map(function ($cell) {
                    return '"' . str_replace('"', '""', $cell ?? '') . '"';
                }, $row)) . "\n";
            }

            return response()->streamDownload(function () use ($csv) {
                echo $csv;
            }, $filename, ['Content-Type' => 'text/csv']);
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('dashboard.export_failed'))
                ->danger()
                ->send();
            report($e);
        }
    }

    public function exportExcel()
    {
        return $this->exportCsv(); // Simplified - uses same CSV format
    }

    public function getCompetitionOptions(): array
    {
        return Competition::active()
            ->pluck('title', 'id')
            ->map(fn ($title) => is_array($title) ? ($title[app()->getLocale()] ?? $title['en'] ?? '') : $title)
            ->toArray();
    }

    public function getStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'qualified' => 'Qualified',
            'not_qualified' => 'Not Qualified',
        ];
    }
}
