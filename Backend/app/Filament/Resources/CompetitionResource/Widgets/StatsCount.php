<?php

namespace App\Filament\Resources\CompetitionResource\Widgets;

use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Models\Event;
use App\Models\Guideline;
use App\Models\Judge;
use App\Models\JudgeProject;
use App\Models\Mentor;
use App\Models\Participant;
use App\Models\Project;
use App\Models\Satisfaction;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Traits\HasDateRangeFilter;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsCount extends BaseWidget
{
    use InteractsWithPageFilters, HasDateRangeFilter;

    public ?Competition $record;

    protected string|int|array $columnSpan = [
        'md' => 4,
    ];

    protected function getStats(): array
    {
        $from = $this->filters['startDate'] ?? null;
        $to   = $this->filters['endDate']   ?? null;

        $competitions = $this->applyDateFilter(Competition::query(), $from, $to)->get();
        $allCompetitionsCount = $competitions->count();
        $openCompetitionsCount = $competitions->filter(function ($competition) {
            $registrationStage = $competition->registrationStage();
            return $registrationStage && $registrationStage->ends_at && $registrationStage->ends_at->isFuture();
        })->count();
        
        $closedCompetitionsCount = $competitions->filter(function ($competition) {
            $registrationStage = $competition->registrationStage();
            return !$registrationStage || !$registrationStage->ends_at || $registrationStage->ends_at->isPast();
        })->count();

        $judgesCount = $this->applyDateFilter(Judge::query(), $from, $to)->count();
        $supervisorsCount = $this->applyDateFilter(
            User::whereHas('roles', fn ($q) => $q->where('name', 'supervisor')),
            $from,
            $to
        )->count();
        $participantsCount = $this->applyDateFilter(Participant::query(), $from, $to)->count();

        $applications = $this->applyDateFilter(CompetitionApplication::query(), $from, $to)->submission()->active()->get();

        $allApplicationsCount = $applications->count();
        $approvedApplicationsCount = $applications->where('status', 'approved')->count();
        $pendingApplicationsCount = $applications->where('status', 'pending')->count();
        $rejectedApplicationsCount = $applications->where('status', 'rejected')->count();

        $eventsCount = $this->applyDateFilter(Event::query(), $from, $to)->count();
        $mentorsCount = $this->applyDateFilter(Mentor::query(), $from, $to)->count();
        $teamCount = $this->applyDateFilter(Team::query(), $from, $to)->count();
        $guidelines = $this->applyDateFilter(Guideline::query(), $from, $to)->count();

        $projects = $this->applyDateFilter(Project::query(), $from, $to)->get();
        $allProjects = $projects->count();
        $pendingProjects = $projects->where('status', 'pending')->count();
        $qualifiedProjects = $projects->where('status', 'qualified')->count();
        $notQualifiedProjects = $projects->where('status', 'not_qualified')->count();
        $winningProjects = $projects->where('status', 'winner')->count();

        $evaluatedProjectsCount = $projects->where('total_score', '!=', 0)->count();

        $satisfactionsCount = $this->applyDateFilter(Satisfaction::query(), $from, $to)->get()->groupBy('participant_id')->count();

        // Number of Assigned Judges judge_id
        $assignedJudgesCount = JudgeProject::whereIn('project_id', $projects->pluck('id'))->count('judge_id');

        $teamsIds = Team::whereIn('application_id', $applications->pluck('id')->toArray())->pluck('id');

        $membersCount = TeamMember::whereIn('team_id', $teamsIds)->where('is_leader', false)->count();


        return [
            Stat::make('All Programs', $allCompetitionsCount),
            Stat::make('Open Programs', $openCompetitionsCount),
            Stat::make('Closed Programs', $closedCompetitionsCount),

            Stat::make('Judges', $judgesCount),
            Stat::make('Supervisors', $supervisorsCount),
            Stat::make('Participants', $participantsCount),


            Stat::make('All Applications', $allApplicationsCount),

            Stat::make('Accepted applications', $approvedApplicationsCount),

            Stat::make('Pending applications', $pendingApplicationsCount),

            Stat::make('Rejected applications', $rejectedApplicationsCount),

            Stat::make(
                'Registered as Individual Percentage',
                $allApplicationsCount != 0 ? number_format($applications->filter(fn($app) => data_get($app->form_submissions, 'register_as') == 'individual')->count() / $allApplicationsCount * 100) . '%' : 0
            ),

            Stat::make(
                'Registered as Individual',
                $allApplicationsCount != 0 ?
                    number_format($applications->filter(fn($app) => data_get($app->form_submissions, 'register_as') == 'individual')->count()) : 0
            ),

            Stat::make(
                'Registered as Team Percentage',
                $allApplicationsCount != 0 ?
                    number_format($applications->filter(fn($app) => data_get($app->form_submissions, 'register_as') == 'team')->count() / $allApplicationsCount * 100) . '%' : 0
            ),

            Stat::make(
                'Registered as Team',
                number_format($applications->filter(fn($app) => data_get($app->form_submissions, 'register_as') == 'team')->count())
            ),

            Stat::make('Events', $eventsCount),

            Stat::make('Mentors', $mentorsCount),

            Stat::make('Teams', $teamCount),

            Stat::make('Guidelines', $guidelines),

            Stat::make('All Projects', $allProjects),

            Stat::make('Pending Projects', $pendingProjects),

            Stat::make('Qualified Projects', $qualifiedProjects),

            Stat::make('Excluded Projects', $notQualifiedProjects),

            Stat::make('Winning Projects', $winningProjects),

            Stat::make('Number of Evaluations', $evaluatedProjectsCount),

            Stat::make('Evaluations Percentage', $allProjects != 0 ? number_format($evaluatedProjectsCount / $allProjects * 100) . '%' : 0),

            Stat::make('Satisfactions', $satisfactionsCount),

            Stat::make('Number of Assigned Judges', $assignedJudgesCount),

            Stat::make('All Applications with Members', $allApplicationsCount),
        ];
    }
}
