<?php
namespace App\Listeners;
use App\Events\FormCompetitionStagesCreated;
use App\Models\Stage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
class HandleFormCompetitionStagesCreated
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
    public function handle(FormCompetitionStagesCreated $event)
    {
        $type = $event->form->type;
        $formId = $event->form->id;
        $competitionId = $event->form->competition_id;

        $stages = [
            'registration' => [
                'slug' => 'registration',
                'title' => ['en' => 'Registration', 'ar' => 'التسجيل'],
                'description' => ['en' => 'Registration', 'ar' => 'التسجيل'],
            ],
            'project' => [
                'slug' => 'project-' . uniqid(),
                'title' => ['en' => 'Project Submission', 'ar' => 'تقديم المشروع'],
                'description' => ['en' => 'Project Submission', 'ar' => 'تقديم المشروع'],
            ],
            'evaluation' => [
                'slug' => 'evaluation-' . uniqid(),
                'title' => ['en' => 'Evaluation', 'ar' => 'تقييم المشروع'],
                'description' => ['en' => 'Evaluation', 'ar' => 'تقييم المشروع'],
            ],
        ];

        if ($type === 'registration') {
            $stage = $stages['registration'];

            Stage::updateOrCreate(
                [
                    'slug' => $stage['slug'],
                    'form_id' => $formId,
                ],
                [
                    'title' => $stage['title'],
                    'description' => $stage['description'],
                    'competition_id' => $competitionId,
                ]
            );
        }

        elseif (in_array($type, ['evaluation', 'project']) && isset($stages[$type])) {
            $stage = $stages[$type];

            Stage::create([
                'slug' => $stage['slug'],
                'title' => $stage['title'],
                'description' => $stage['description'],
                'form_id' => $formId,
                'competition_id' => $competitionId,
            ]);
        }
    }


}
