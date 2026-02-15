<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class EvaluationStageConfig extends Model
{
    use LogsActivity, HasActivityLog;

    protected $fillable = [
        'competition_id',
        'number_of_stages',
        'stages',
        'is_active',
    ];

    protected $casts = [
        'stages' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected array $logFields = [
        'number_of_stages',
        'stages',
        'is_active',
        'competition.title',
        'competition_id',
    ];

    protected string $moduleName = 'Evaluation Stage Config';
    protected string $logName = 'evaluation_stage_config';

    // Relationships
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }



    public static function hasDuplicateEvaluationForms(array $stages): bool
    {
        $formIds = [];

        foreach ($stages as $stage) {
            if (!empty($stage['evaluation_form_id'])) {
                if (in_array($stage['evaluation_form_id'], $formIds)) {
                    return true;
                }
                $formIds[] = $stage['evaluation_form_id'];
            }
        }

        return false;
    }

    public function tapActivity(\Spatie\Activitylog\Contracts\Activity $activity, string $eventName)
    {
        // Handle new values (attributes)
        if (isset($activity->properties['attributes']['stages'])) {
            $stages = $activity->properties['attributes']['stages'];
            if (is_array($stages)) {
                $readableStages = $this->formatStagesForLog($stages);
                $properties = $activity->properties->toArray();
                $properties['attributes']['stages'] = $readableStages;
                $activity->properties = $properties;
            }
        }

        // Handle old values
        if (isset($activity->properties['old']['stages'])) {
            $oldStages = $activity->properties['old']['stages'];
            if (is_array($oldStages)) {
                $readableOldStages = $this->formatStagesForLog($oldStages);
                $properties = $activity->properties->toArray();
                $properties['old']['stages'] = $readableOldStages;
                $activity->properties = $properties;
            }
        }
    }

    protected function formatStagesForLog(array $stages): string
    {
        return collect($stages)->map(function ($stage, $index) {
            $formName = 'Unknown Form';
            if (!empty($stage['evaluation_form_id']) && is_numeric($stage['evaluation_form_id'])) {
                $form = Form::find($stage['evaluation_form_id']);
                if ($form) {
                    $formName = is_array($form->name)
                        ? ($form->name['en'] ?? $form->name['ar'] ?? 'Untitled Form')
                        : $form->name;
                }
            }

            $trackInfo = 'All Tracks';
            if (!($stage['apply_to_all_tracks'] ?? true) && !empty($stage['track_ids'])) {
                $trackNames = Track::whereIn('id', $stage['track_ids'])
                    ->get()
                    ->map(function ($track) {
                        return is_array($track->name)
                            ? ($track->name['en'] ?? $track->name['ar'])
                            : $track->name;
                    })
                    ->implode(', ');
                $trackInfo = $trackNames ?: 'No tracks selected';
            }

            $stageNumber = $stage['stage_number'] ?? ($index + 1);
            $requirement = ucfirst($stage['submission_requirement'] ?? 'new');

            $previousStage = !empty($stage['previous_stage_number'])
                ? "\n- Previous Stage: Stage {$stage['previous_stage_number']}"
                : '';

            return "Stage {$stageNumber}\n- Evaluation Form: {$formName}\n- Tracks: {$trackInfo}\n- Submission Requirement: {$requirement}{$previousStage}";
        })->implode("\n\n");
    }
}
