<?php

namespace App\Listeners;

use App\Events\CompetitionCreated;
use App\Models\CompetitionTab;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateCompetitionStages
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CompetitionCreated $event): void
    {
        $tabs = [
            'teams',
            'my-team',
            'events',
            'mentors',
            'guidelines',
            'projects',
            'winners',
            'leaderboard',
        ];

        foreach ($tabs as $tab) {
            CompetitionTab::updateOrCreate(
                ['competition_id' => $event->competition->id, 'tab' => $tab],
                ['is_visible' => true]
            );
        }
    }
}
