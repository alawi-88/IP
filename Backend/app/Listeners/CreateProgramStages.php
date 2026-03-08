<?php

namespace App\Listeners;

use App\Events\ProgramCreated;
use App\Models\ProgramTab;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateProgramStages
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
    public function handle(ProgramCreated $event): void
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
            ProgramTab::updateOrCreate(
                ['program_id' => $event->program->id, 'tab' => $tab],
                ['is_visible' => true]
            );
        }
    }
}
