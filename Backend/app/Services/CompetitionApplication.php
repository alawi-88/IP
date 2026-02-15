<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Models\CompetitionApplication as CompetitionApplicationModel;
use Illuminate\Support\Facades\DB;

readonly class CompetitionApplication
{
    public function getMyApplications(): Collection
    {
        $user = auth()->user();
        if (!$user) {
            return collect();
        }
        
        $applicationsAsTeamLeader = $user->applications()->where('type', '!=', 'draft')->active()->latest()->get();

        $excludedFormIds = $applicationsAsTeamLeader->pluck('form_id')->toArray();

        $applicationsAsTeamMembers = DB::table('competition_applications')
            ->select('competition_applications.*')
            ->join('teams', 'competition_applications.id', '=', 'teams.application_id')
            ->join('team_members', 'teams.id', '=', 'team_members.team_id')
            ->join('competitions', 'competition_applications.competition_id', '=', 'competitions.id')
            ->where('team_members.is_leader', false)
            ->where('team_members.participant_id', $user->id)
            ->whereNotIn('competition_applications.form_id', $excludedFormIds)
            ->where('competition_applications.type', '!=', 'draft')
            ->where('competition_applications.is_archived', false)
            ->where('competitions.is_archived', false)
            ->latest('competition_applications.created_at')
            ->get();

        $applicationsAsTeamMembers = CompetitionApplicationModel::hydrate($applicationsAsTeamMembers->toArray());

        return $applicationsAsTeamLeader->merge($applicationsAsTeamMembers);
    }


    public function store($data): CompetitionApplicationModel
    {
        $applicationData = Arr::only($data,
            [
                'competition_id',
                'has_team',
                'has_idea',
                'idea_description',
                'participation_interest',
                'status',
                'type'
            ]);

        return CompetitionApplicationModel::create($applicationData);
    }


    public function show($application): CompetitionApplicationModel
    {
        return $this->getMyApplications()->findOrFail($application);
    }
}
