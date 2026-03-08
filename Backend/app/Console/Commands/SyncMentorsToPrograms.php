<?php

namespace App\Console\Commands;

use App\Models\Mentor;
use Illuminate\Console\Command;

class SyncMentorsToPrograms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mentors:sync-programs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync existing mentors to programs using the many-to-many relationship';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing mentors to programs...');

        $mentors = Mentor::whereNotNull('program_id')->get();
        $total = $mentors->count();
        $synced = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($mentors as $mentor) {
            // Check if mentor already has programs
            if ($mentor->programs()->count() === 0 && $mentor->program_id) {
                try {
                    $mentor->programs()->attach($mentor->program_id);
                    $synced++;
                } catch (\Exception $e) {
                    // Program might not exist, skip
                    $this->warn("\nMentor {$mentor->id} skipped: " . $e->getMessage());
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Successfully synced {$synced} mentors to programs out of {$total} total.");
        
        // Show statistics
        $withPrograms = Mentor::has('programs')->count();
        $withoutPrograms = Mentor::doesntHave('programs')->count();
        
        $this->table(
            ['Status', 'Count'],
            [
                ['Mentors with programs', $withPrograms],
                ['Mentors without programs', $withoutPrograms],
                ['Total mentors', $withPrograms + $withoutPrograms],
            ]
        );

        return Command::SUCCESS;
    }
}

