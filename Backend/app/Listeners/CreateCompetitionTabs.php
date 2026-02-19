<?php

namespace App\Listeners;

use App\Events\CompetitionCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateCompetitionTabs
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
        // Create all participation hub tabs for the new competition
        $tabs = ['journey', 'events', 'mentors', 'my-team', 'teams', 'projects', 'tasks', 'guidelines', 'leaderboard'];

        foreach ($tabs as $tab) {
            $event->competition->tabs()->updateOrCreate(
                ['tab' => $tab],
                ['is_visible' => true]
            );
        }

        // Create the Team Formation stage
        $event->competition->stages()->updateOrCreate(
            ['slug' => 'team-formation'],
            [
                'title' => [
                    'en' => 'Team Formation',
                    'ar' => 'تكوين الفريق',
                ],
                'description' => [
                    'en' => 'Team Formation Stage',
                    'ar' => 'مرحلة تكوين الفريق',
                ],
                'slug' => 'team-formation',
            ]
        );
    }
}
