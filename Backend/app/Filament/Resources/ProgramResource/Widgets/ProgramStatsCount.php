<?php

namespace App\Filament\Resources\ProgramResource\Widgets;

use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\Event;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\Guideline;
use App\Models\JudgeProject;
use App\Models\Mentor;
use App\Models\Project;
use App\Models\Satisfaction;
use App\Models\Team;
use App\Models\TeamMember;
use App\Traits\HasDateRangeFilter;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProgramStatsCount extends BaseWidget
{
    use HasDateRangeFilter;
    use InteractsWithPageFilters;

    public ?Program $record;

    protected string|int|array $columnSpan = [
        'md' => 4,
    ];

    protected function getStats(): array
    {
        $from = $this->filters['startDate'] ?? null;
        $to = $this->filters['endDate'] ?? null;

        $applicationsQuery = ProgramApplication::byProgram()->submission()->active();
        $applicationsQuery = $this->applyDateFilter($applicationsQuery, $from, $to);
        $applications = $applicationsQuery->get();

        $allApplicationsCount = $applications->count();
        $approvedApplicationsCount = $applications->where('status', 'approved')->count();
        $pendingApplicationsCount = $applications->where('status', 'pending')->count();
        $rejectedApplicationsCount = $applications->where('status', 'rejected')->count();

        $eventsCount = $this->applyDateFilter(Event::byProgram(), $from, $to)->count();
        $mentorsCount = $this->applyDateFilter(Mentor::byProgram(), $from, $to)->count();
        $teamCount = $this->applyDateFilter(Team::byProgram(), $from, $to)->count();
        $guidelines = $this->applyDateFilter(Guideline::byProgram(), $from, $to)->count();

        $projectsQuery = Project::byProgram()->submission();
        $projectsQuery = $this->applyDateFilter($projectsQuery, $from, $to);
        $projects = $projectsQuery->get();

        $allProjects = $projects->count();
        $pendingProjects = $projects->where('status', 'pending')->count();
        $qualifiedProjects = $projects->where('status', 'qualified')->count();
        $notQualifiedProjects = $projects->where('status', 'not_qualified')->count();
        $winningProjects = $projects->where('status', 'winner')->count();
        $evaluatedProjectsCount = $projects->where('total_score', '!=', 0)->count();

        $satisfactionsQuery = Satisfaction::byProgram();
        $satisfactionsQuery = $this->applyDateFilter($satisfactionsQuery, $from, $to);
        $satisfactionsCount = $satisfactionsQuery->get()->groupBy('participant_id')->count();

        $assignedJudgesCount = JudgeProject::whereIn('project_id', $projects->pluck('id'))->count('judge_id');

        $teamsIds = Team::byProgram()->whereIn('application_id', $applications->pluck('id')->toArray())->pluck('id');
        $membersCount = TeamMember::whereIn('team_id', $teamsIds)->where('is_leader', false)->count();

        $stats = [
            Stat::make('All Applications', $allApplicationsCount),
            Stat::make('Accepted Applications', $approvedApplicationsCount),
            Stat::make('Pending Applications', $pendingApplicationsCount),
            Stat::make('Rejected Applications', $rejectedApplicationsCount),
            Stat::make('Registered as Individual', $applications->filter(fn($app) => data_get($app->form_submissions, 'register_as') == 'individual')->count()),
            Stat::make('Registered as Individual Percentage',
                $allApplicationsCount ? number_format($applications->filter(fn($app) => data_get($app->form_submissions, 'register_as') == 'individual')->count() / $allApplicationsCount * 100) . '%' : '0%'),
            Stat::make('Registered as Team', $applications->filter(fn($app) => data_get($app->form_submissions, 'register_as') == 'team')->count()),
            Stat::make('Registered as Team Percentage',
                $allApplicationsCount ? number_format($applications->filter(fn($app) => data_get($app->form_submissions, 'register_as') == 'team')->count() / $allApplicationsCount * 100) . '%' : '0%'),
            Stat::make('Teams', $teamCount),
            Stat::make('Team Members (excluding leaders)', $membersCount),
            Stat::make('Events', $eventsCount),
            Stat::make('Mentors', $mentorsCount),
            Stat::make('Guidelines', $guidelines),
            Stat::make('All Projects', $allProjects),
            Stat::make('Pending Projects', $pendingProjects),
            Stat::make('Qualified Projects', $qualifiedProjects),
            Stat::make('Excluded Projects', $notQualifiedProjects),
            Stat::make('Winning Projects', $winningProjects),
            Stat::make('Number of Evaluations', $evaluatedProjectsCount),
            Stat::make('Evaluations Percentage',
                $allProjects ? number_format($evaluatedProjectsCount / $allProjects * 100) . '%' : '0%'),
            Stat::make('Satisfactions', $satisfactionsCount),
            Stat::make('Assigned Judges', $assignedJudgesCount),
            Stat::make('All Applications Including Team Members', $allApplicationsCount + $membersCount),
        ];

        $formIds = Form::byProgram()->pluck('id');
        $fields = FormField::whereIn('form_id', $formIds)
            ->whereIn('type', ['dropdown', 'multi_select', 'radio', 'checkbox'])
            ->get();

        foreach ($fields as $field) {
            $optionCounts = [];

            $submissions = ProgramApplication::where('form_id', $field->form_id)->get();

            foreach ($submissions as $submission) {
                $answers = $submission->form_submissions;

                if (!isset($answers[$field->id])) {
                    continue;
                }

                $value = $answers[$field->id];

                if (in_array($field->type, ['multi_select', 'checkbox'])) {
                    if (is_array($value)) {
                        foreach ($value as $option) {
                            $optionCounts[$option] = ($optionCounts[$option] ?? 0) + 1;
                        }
                    }
                } else {
                    $optionCounts[$value] = ($optionCounts[$value] ?? 0) + 1;
                }
            }

            foreach ($optionCounts as $option => $count) {
                $stats[] = Stat::make("{$field->label} - {$option}", $count);
            }
        }

        return $stats;
    }


}
