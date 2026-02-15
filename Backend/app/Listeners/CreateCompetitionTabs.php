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
        // Create only the Team Formation stage
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
