<?php

namespace App\Models;

use App\Traits\Program\FilterByProgram;
use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;


/**
 * @method static byProgram()
 */

class Form extends Model
{
    use HasTranslations, FilterByProgram, LogsActivity, HasActivityLog;

    public array $translatable = ['name', 'description'];

    protected $with = ['fields'];

    protected $fillable = [
        'program_id',
        'type',
        'name',
        'description',
        'evaluation_config',
        'status',
        'is_published',
        'is_archived',
        'archived_at',
    ];


    protected array $logFields = [
        'name',
        'description',
        'type',
        'evaluation_config',
        'status',
        'is_published',
        'is_archived',
        'archived_at',
        'program.title',
        'program_id',
    ];

    protected string $moduleName = 'Form';
    protected string $logName = 'form';

    protected $casts = [
        'evaluation_config' => 'array',
        'is_published' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    CONST FIELD_TYPES = [
        'text' => 'Text Input',
        'textarea' => 'Text Area',
        'number' => 'Number Input',
        'email' => 'Email Input',
        'phone' => 'Phone Number',
        'date' => 'Date Picker',
        'time' => 'Time Picker',
        'dropdown' => 'Dropdown/Select List',
        'multi_select' => 'Multi-Select Dropdown',
        'radio' => 'Radio Buttons',
        'checkbox' => 'Checkboxes',
        'file' => 'File Upload',
        'url' => 'URL/Website',
        'rating' => 'Rating Scale',
        'section_header' => 'Section Header',
        'paragraph' => 'Paragraph Text',
    ];

    public static function getAvailableFormTypes(): array
    {
        return [
            'registration' => 'Registration',
            'project' => 'Project',
            'evaluation' => 'Evaluation',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class);
    }

    /**
     * Get stages that have this form in form_ids array or form_id.
     */
    public function stages(): \Illuminate\Database\Eloquent\Collection
    {
        // Get stages where this form is in form_ids JSON array
        $stagesByFormIds = Stage::whereJsonContains('form_ids', $this->id)->get();
        
        // Get stages where this form is the primary form_id
        $stagesByFormId = Stage::where('form_id', $this->id)->get();
        
        // Merge and return unique collection
        return $stagesByFormIds->merge($stagesByFormId)->unique('id');
    }

    /**
     * Get stages that have this form as the primary form_id (for backward compatibility).
     */
    public function stagesByFormId(): HasMany
    {
        return $this->hasMany(Stage::class, 'form_id');
    }

    public function ProgramApplication(): HasMany
    {
        return $this->hasMany(ProgramApplication::class);
    }

    public function Projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function Evaluations(): HasMany
    {
        return $this->hasMany(ProjectEvaluation::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(FormSection::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
    public function FormSteps()
    {
        return $this->hasMany(FormStep::class)->orderBy('step_order', 'asc');
    }

    public function ProjectSteps()
    {
        return $this->hasMany(ProjectStep::class)->orderBy('step_order', 'asc');
    }

    public function projectFormConfig(): HasOne
    {
        return $this->hasOne(\App\Models\ProjectFormConfig::class)->active();
    }

    public function aiScoringConfig(): HasOne
    {
        return $this->hasOne(FormAiScoringConfig::class);
    }

    public function aiEnhancementConfig(): HasOne
    {
        return $this->hasOne(FormAiEnhancementConfig::class);
    }

    public function assessmentCriteria(): HasMany
    {
        return $this->hasMany(FormAssessmentCriterion::class)->orderBy('sort_order');
    }

    public function activeAssessmentCriteria(): HasMany
    {
        return $this->assessmentCriteria()->where('status', 'active');
    }

    public function scopeRegistrationType($query)
    {
        return $query->where('type', 'registration');
    }

    public function scopeProjectType($query)
    {
        return $query->where('type', 'project');
    }

    public function scopeEvaluationType($query)
    {
        return $query->where('type', 'evaluation');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function submissionTrend()
    {
        if ($this->type == 'registration'){
            $submissionTrend =  $this->ProgramApplication()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

        }elseif ($this->type == 'project'){
            $submissionTrend =  $this->Projects()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }else{
            $submissionTrend = $this->Evaluations()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }
        return $submissionTrend;

    }

    public function getTrendAttribute(): string
    {
        $submissionTrend = $this->submissionTrend();

        if ($submissionTrend->count() < 2) {
            return 'down';
        }

        $counts = $submissionTrend->pluck('count');

        $lastCount = $counts->last();
        $prevCount = $counts->slice(-2, 1)->first();

        return $lastCount >= $prevCount ? 'up' : 'down';
    }

    public static function availableEvaluationForms(int $programId, array $stages, array $currentStage): array
    {
        $currentFormId = $currentStage['evaluation_form_id'] ?? null;

        // Get all evaluation forms for this program
        $forms = self::where('type', 'evaluation')
            ->where('program_id', $programId)
            ->get()
            ->keyBy('id');
        
        // If there's a current form ID that's not already in the collection,
        // check if it's a valid evaluation form and add it
        if ($currentFormId && !$forms->has($currentFormId)) {
            $currentForm = self::find($currentFormId);
            // Only include if it's actually an evaluation form for this program
            if ($currentForm && $currentForm->type === 'evaluation' && $currentForm->program_id == $programId) {
                $forms->put($currentFormId, $currentForm);
            }
        }

        $currentIndex = collect($stages)->search(fn($stage) => $stage === $currentStage);
        $usedFormIds = collect($stages)
            ->filter(fn($stage, $index) => $index !== $currentIndex)
            ->pluck('evaluation_form_id')
            ->filter()
            ->all();

        return $forms
            ->reject(fn($form, $id) => in_array($id, $usedFormIds) && $id != $currentFormId)
            ->mapWithKeys(function ($form) {
                $name = is_array($form->name)
                    ? ($form->name['en'] ?? reset($form->name))
                    : $form->name;

                return [$form->id => $name ?: 'Untitled #' . $form->id];
            })
            ->toArray();
    }

    public function getEvaluationStagesAttribute()
    {
        $formId = $this->id;
        $locale = app()->getLocale();

        $allTracks = Track::select('id', 'name')->get()->map(function ($track) use ($locale) {
            return [
                'id' => $track->id,
                'name' => is_array($track->name) ? ($track->name[$locale] ?? reset($track->name)) : $track->name,
            ];
        });

        return EvaluationStageConfig::all()
            ->map(function ($config) use ($formId, $allTracks) {
                $matchedStages = collect($config->stages)->filter(function ($stage) use ($formId) {
                    return (string) $stage['evaluation_form_id'] === (string) $formId;
                })->map(function ($stage) use ($allTracks) {
                    if ($stage['apply_to_all_tracks'] === true) {
                        $stage['tracks'] = $allTracks;
                    } elseif (!empty($stage['track_ids'])) {
                        $stage['tracks'] = $allTracks->whereIn('id', $stage['track_ids'])->values();
                    } else {
                        $stage['tracks'] = [];
                    }

                    return $stage;
                })->values();

                if ($matchedStages->isNotEmpty()) {
                    return [
                        'id' => $config->id,
                        'program_id' => $config->program_id,
                        'number_of_stages' => $config->number_of_stages,
                        'is_active' => $config->is_active,
                        'stages' => $matchedStages,
                    ];
                }

                return null;
            })
            ->filter()
            ->values();
    }

    public function getEvaluationConfigAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getLocalizedEvaluationConfigAttribute()
    {
        $config = $this->evaluation_config;
        $locale = app()->getLocale();

        // Localize evaluation_agreement_text if it exists
        if (isset($config['evaluation_agreement_text'])) {
            if (is_array($config['evaluation_agreement_text'])) {
                // New bilingual format: return the text for current locale
                $config['evaluation_agreement_text'] = $config['evaluation_agreement_text'][$locale] 
                    ?? $config['evaluation_agreement_text']['en'] 
                    ?? $config['evaluation_agreement_text']['ar'] 
                    ?? '';
            }
            // If it's a string (old format), keep it as is for backward compatibility
        }

        if (
            isset($config['evaluation_criteria']) &&
            (is_array($config['evaluation_criteria']) || is_object($config['evaluation_criteria']))
        ) {
            foreach ($config['evaluation_criteria'] as &$criterion) {
                if (isset($criterion['label']) && is_array($criterion['label'])) {
                    $criterion['label'] = $criterion['label'][$locale] ?? reset($criterion['label']);
                }

                if (
                    !empty($criterion['subcriteria']) &&
                    (is_array($criterion['subcriteria']) || is_object($criterion['subcriteria']))
                ) {
                    foreach ($criterion['subcriteria'] as &$sub) {
                        if (isset($sub['label']) && is_array($sub['label'])) {
                            $sub['label'] = $sub['label'][$locale] ?? reset($sub['label']);
                        }
                    }
                }
            }
        }

        return $config;
    }

    public function setEvaluationConfigAttribute($value)
    {
        if (isset($value['evaluation_criteria']) && is_array($value['evaluation_criteria'])) {
            foreach ($value['evaluation_criteria'] as $i => $main) {
                // Handle string or array label
                $mainLabel = is_array($main['label'] ?? null)
                    ? ($main['label']['en'] ?? null)
                    : $main['label'];

                if ($mainLabel) {
                    $value['evaluation_criteria'][$i]['slug'] = $this->toSnakeCase($mainLabel);
                }

                // Subcriteria
                if (!empty($main['subcriteria']) && is_array($main['subcriteria'])) {
                    foreach ($main['subcriteria'] as $j => $sub) {
                        $subLabel = is_array($sub['label'] ?? null)
                            ? ($sub['label']['en'] ?? null)
                            : $sub['label'];

                        if ($subLabel) {
                            $value['evaluation_criteria'][$i]['subcriteria'][$j]['slug'] = $this->toSnakeCase($subLabel);
                        }
                    }
                }
            }
        }

        $this->attributes['evaluation_config'] = json_encode($value);
    }

    protected function toSnakeCase(string $value): string
    {
        $value = preg_replace('/\s+/', '_', trim($value));               // spaces to underscores
        $value = preg_replace('/([a-z])([A-Z])/', '$1_$2', $value);     // camelCase to snake_case
        return strtolower($value);
    }

    /**
     * Check if the form is archived
     */
    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    /**
     * Archive the form
     */
    public function archive(): bool
    {
        $result = $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);


        return $result;
    }

    /**
     * Restore the form from archive
     */
    public function restore(): bool
    {
        $result = $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);


        return $result;
    }

    /**
     * Scope to get only active (non-archived) forms
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope to get only archived forms
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

}
