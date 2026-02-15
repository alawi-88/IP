<?php

namespace App\Models\Scopes;

use App\Models\CompetitionApplication;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompetitionApplicationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // skip from dashboard.
        if (auth()->guard('web')->check()
            || request()->is('api/judges/*')
            || request()->is('api/participants/competition-applications')
            || request()->is('api/mentors/auth/*')
            || request()->is('api/mentors/resend-otp')
            || request()->is('api/mentors/forgot-password')
            || request()->is('api/mentors/reset-password')
            || request()->is('api/mentors/check-password-reset-code')
            || request()->is('api/mentors/check-auth')
            || request()->is('api/mentors/profile')
        ) return;

        $applicationId = request('application_id');
        
        // Only validate and apply scope if application_id is provided
        if ($applicationId) {
            request()->validate([
                'application_id' => 'exists:competition_applications,id',
            ]);
            
            $competitionId = CompetitionApplication::findOrFail($applicationId)->competition_id;

            if ($model instanceof Team) {
                $builder->whereHas('application', function (Builder $query) use ($competitionId) {
                    $query->where('competition_id', $competitionId);
                });
            } else {
                $builder->where('competition_id', $competitionId);
            }
        }
    }
}
