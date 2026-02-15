<?php

namespace App\Filament\Pages;

use App\Filament\Resources\MentorResource\Widgets\MentorshipStatsWidget;
use App\Models\Competition;
use App\Models\Mentor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class MentorshipAnalytics extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Mentorship Analytics & Reports';

    protected static ?string $title = 'Mentorship Analytics & Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Mentor Management';

    protected static string $routePath = '/mentors/analytics';

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Section::make(__('analytics.filters'))
                ->schema([
                    Select::make('competition_id')
                        ->label(__('analytics.program'))
                        ->placeholder(__('analytics.select_program'))
                        ->options(function () {
                            $competitions = Competition::all();

                            if ($competitions->isEmpty()) {
                                return ['' => __('analytics.no_data_available_for_selection')];
                            }

                            $options = ['' => __('analytics.all_programs')]; // "All Programs" option

                            return $options + $competitions->mapWithKeys(function ($competition) {
                                $title = is_array($competition->title)
                                    ? ($competition->title[app()->getLocale()] ?? $competition->title['en'] ?? '')
                                    : $competition->title;
                                return [$competition->id => $title];
                            })->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Select::make('mentor_id')
                        ->label(__('analytics.mentor'))
                        ->placeholder(__('analytics.select_mentor'))
                        ->options(function () {
                            $mentors = Mentor::where('is_visible', true)
                                ->where('status', 'approved')
                                ->get();

                            if ($mentors->isEmpty()) {
                                return ['' => __('analytics.no_data_available_for_selection')];
                            }

                            $options = ['' => __('analytics.all_mentors')]; // "All Mentors" option

                            return $options + $mentors->mapWithKeys(function ($mentor) {
                                $name = is_array($mentor->name)
                                    ? ($mentor->name[app()->getLocale()] ?? $mentor->name['en'] ?? '')
                                    : $mentor->name;
                                return [$mentor->id => $name];
                            })->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    DatePicker::make('start_date')
                        ->label(__('analytics.start_date'))
                        ->placeholder(__('analytics.enter_date_range'))
                        ->maxDate(fn ($get) => $get('end_date') ?: now())
                        ->displayFormat('yyyy-MM-dd') // Ensures English number display
                        ->native(false)
                        ->nullable()
                        ->rules([
                            function ($get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    if ($value && $get('end_date')) {
                                        $startDate = \Carbon\Carbon::parse($value);
                                        $endDate = \Carbon\Carbon::parse($get('end_date'));

                                        if ($startDate->greaterThan($endDate)) {
                                            $fail(__('analytics.invalid_date_range'));
                                        }

                                        if ($startDate->greaterThan(now())) {
                                            $fail(__('analytics.invalid_date_range'));
                                        }
                                    }
                                };
                            },
                        ])
                        ->suffixAction(
                            \Filament\Forms\Components\Actions\Action::make('clear_start')
                                ->icon('heroicon-m-x-mark')
                                ->action(function ($set) {
                                    $set('start_date', null);
                                })
                                ->visible(fn ($get) => filled($get('start_date')))
                        ),

                    DatePicker::make('end_date')
                        ->label(__('analytics.end_date'))
                        ->placeholder(__('analytics.enter_date_range'))
                        ->minDate(fn ($get) => $get('start_date'))
                        ->maxDate(now())
                        ->displayFormat('yyyy-MM-dd') // Ensures English number display
                        ->native(false)
                        ->nullable()
                        ->rules([
                            function ($get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    if ($value && $get('start_date')) {
                                        $startDate = \Carbon\Carbon::parse($get('start_date'));
                                        $endDate = \Carbon\Carbon::parse($value);

                                        if ($startDate->greaterThan($endDate)) {
                                            $fail(__('analytics.invalid_date_range'));
                                        }

                                        if ($endDate->greaterThan(now())) {
                                            $fail(__('analytics.invalid_date_range'));
                                        }
                                    }
                                };
                            },
                        ])
                        ->suffixAction(
                            \Filament\Forms\Components\Actions\Action::make('clear_end')
                                ->icon('heroicon-m-x-mark')
                                ->action(function ($set) {
                                    $set('end_date', null);
                                })
                                ->visible(fn ($get) => filled($get('end_date')))
                        ),
                ])
                ->columns(4)
                ->collapsible(),
        ]);
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label(__('analytics.export_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    try {
                        $query = \App\Models\MentorSession::query()->with(['competition', 'mentor', 'participant']);

                        // Apply filters (empty string means "all")
                        if (($competitionId = $this->filters['competition_id'] ?? null) && $competitionId !== '') {
                            $query->where('competition_id', $competitionId);
                        }
                        if (($mentorId = $this->filters['mentor_id'] ?? null) && $mentorId !== '') {
                            $query->where('mentor_id', $mentorId);
                        }
                        if ($startDate = $this->filters['start_date'] ?? null) {
                            $query->whereDate('scheduled_at', '>=', \Carbon\Carbon::parse($startDate)->startOfDay());
                        }
                        if ($endDate = $this->filters['end_date'] ?? null) {
                            $query->whereDate('scheduled_at', '<=', \Carbon\Carbon::parse($endDate)->endOfDay());
                        }

                        $sessions = $query->get();

                        // Generate CSV content
                        $csvData = [];
                        $csvData[] = [
                            __('analytics.session_id'),
                            __('analytics.program'),
                            __('analytics.mentor'),
                            __('analytics.participant'),
                            __('analytics.session_title'),
                            __('analytics.scheduled_at'),
                            __('analytics.duration_minutes'),
                            __('analytics.status'),
                            __('analytics.created_at'),
                        ];

                        foreach ($sessions as $session) {
                            $competitionTitle = $session->competition
                                ? (is_array($session->competition->title)
                                    ? ($session->competition->title[app()->getLocale()] ?? $session->competition->title['en'] ?? 'N/A')
                                    : $session->competition->title)
                                : 'N/A';

                            $mentorName = $session->mentor
                                ? (is_array($session->mentor->name)
                                    ? ($session->mentor->name[app()->getLocale()] ?? $session->mentor->name['en'] ?? 'N/A')
                                    : $session->mentor->name)
                                : 'N/A';

                            $participantName = $session->participant
                                ? (is_array($session->participant->name)
                                    ? ($session->participant->name[app()->getLocale()] ?? $session->participant->name['en'] ?? 'N/A')
                                    : $session->participant->name)
                                : 'N/A';

                            $statusLabel = __("sessions.status.{$session->status}") ?? $session->status;

                            $csvData[] = [
                                $session->id,
                                $competitionTitle,
                                $mentorName,
                                $participantName,
                                $session->title ?? 'N/A',
                                $session->scheduled_at ? $session->scheduled_at->format('Y-m-d H:i:s') : 'N/A',
                                $session->duration_minutes ?? 30,
                                $statusLabel,
                                $session->created_at ? $session->created_at->format('Y-m-d H:i:s') : 'N/A',
                            ];
                        }

                        // Convert to CSV string
                        $filename = 'mentorship-analytics-' . now()->format('Y-m-d') . '.csv';
                        $output = fopen('php://temp', 'r+');

                        foreach ($csvData as $row) {
                            fputcsv($output, $row);
                        }

                        rewind($output);
                        $csvContent = stream_get_contents($output);
                        fclose($output);

                        return response()->streamDownload(function () use ($csvContent) {
                            echo $csvContent;
                        }, $filename, [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                        ]);
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('analytics.export_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

        ];
    }

    public function resetFilters(): void
    {
        // Clear the filters property
        $this->filters = [];

        // Reset form fields explicitly
        $this->filtersForm->fill([
            'competition_id' => null,
            'mentor_id' => null,
            'start_date' => null,
            'end_date' => null,
        ]);

        // Ensure filters remains empty after form fill
        $this->filters = [];

        \Filament\Notifications\Notification::make()
            ->title(__('analytics.filters_reset'))
            ->success()
            ->send();
    }

    public function hasActiveFilters(): bool
    {
        $competitionId = $this->filters['competition_id'] ?? null;
        $mentorId = $this->filters['mentor_id'] ?? null;

        // Empty string means "all", so it's not an active filter
        return (filled($competitionId) && $competitionId !== '')
            || (filled($mentorId) && $mentorId !== '')
            || filled($this->filters['start_date'] ?? null)
            || filled($this->filters['end_date'] ?? null);
    }


    public function getWidgets(): array
    {
        return [
            MentorshipStatsWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 3;
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view Mentor') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view Mentor') ?? false;
    }

}

