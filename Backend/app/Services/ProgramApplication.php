<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Models\ProgramApplication as ProgramApplicationModel;
use Illuminate\Support\Facades\DB;

readonly class ProgramApplication
{
    public function getMyApplications(): Collection
    {
        $user = auth()->user();
        if (!$user) {
            return collect();
        }
        
        $applicationsAsTeamLeader = $user->applications()->where('type', '!=', 'draft')->active()->latest()->get();

        $excludedFormIds = $applicationsAsTeamLeader->pluck('form_id')->toArray();

        $applicationsAsTeamMembers = DB::table('program_applications')
            ->select('program_applications.*')
            ->join('teams', 'program_applications.id', '=', 'teams.application_id')
            ->join('team_members', 'teams.id', '=', 'team_members.team_id')
            ->join('programs', 'program_applications.program_id', '=', 'programs.id')
            ->where('team_members.is_leader', false)
            ->where('team_members.participant_id', $user->id)
            ->whereNotIn('program_applications.form_id', $excludedFormIds)
            ->where('program_applications.type', '!=', 'draft')
            ->where('program_applications.is_archived', false)
            ->where('programs.is_archived', false)
            ->latest('program_applications.created_at')
            ->get();

        $applicationsAsTeamMembers = ProgramApplicationModel::hydrate($applicationsAsTeamMembers->toArray());

        return $applicationsAsTeamLeader->merge($applicationsAsTeamMembers);
    }


    public function store($data): ProgramApplicationModel
    {
        $applicationData = Arr::only($data,
            [
                'program_id',
                'has_team',
                'has_idea',
                'idea_description',
                'participation_interest',
                'status',
                'type'
            ]);

        return ProgramApplicationModel::create($applicationData);
    }


    public function show($application): ProgramApplicationModel
    {
        return $this->getMyApplications()->findOrFail($application);
    }
}
