<?php

namespace App\Console\Commands;

use App\Models\Mentor;
use Illuminate\Console\Command;

class SyncMentorsToCompetitions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mentors:sync-competitions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync existing mentors to competitions using the many-to-many relationship';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing mentors to competitions...');

        $mentors = Mentor::whereNotNull('competition_id')->get();
        $total = $mentors->count();
        $synced = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($mentors as $mentor) {
            // Check if mentor already has competitions
            if ($mentor->competitions()->count() === 0 && $mentor->competition_id) {
                try {
                    $mentor->competitions()->attach($mentor->competition_id);
                    $synced++;
                } catch (\Exception $e) {
                    // Competition might not exist, skip
                    $this->warn("\nMentor {$mentor->id} skipped: " . $e->getMessage());
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Successfully synced {$synced} mentors to competitions out of {$total} total.");
        
        // Show statistics
        $withCompetitions = Mentor::has('competitions')->count();
        $withoutCompetitions = Mentor::doesntHave('competitions')->count();
        
        $this->table(
            ['Status', 'Count'],
            [
                ['Mentors with competitions', $withCompetitions],
                ['Mentors without competitions', $withoutCompetitions],
                ['Total mentors', $withCompetitions + $withoutCompetitions],
            ]
        );

        return Command::SUCCESS;
    }
}

