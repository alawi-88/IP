<?php

namespace App\Models;

use App\Traits\Program\FilterByProgram;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Carbon\Carbon;
/**
 * @method static updateOrCreate(array $array, array $array1)
 * @method static byProgram()
 */
class Stage extends Model
{
    use HasTranslations, FilterByProgram;

    public array $translatable = ['title', 'description'];

    protected $fillable = [
        'program_id',
        'form_id', // Keep for backward compatibility
        'form_ids', // JSON array of form IDs
        'slug',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'is_visible',
    ];

    protected $casts = [
        // Use datetime to allow precise timing control for stage availability
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_visible' => 'boolean',
        'form_ids' => 'array', // Cast JSON to array
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Stage $stage) {
            self::generateSlug($stage);
        });

        static::updating(function (Stage $stage) {
            self::generateSlug($stage);
        });

        // Sync form_id with first form_ids value (only for non-registration stages)
        static::saving(function (Stage $stage) {
            // For registration stages, only use form_id, clear form_ids
            if ($stage->slug === 'registration') {
                $stage->form_ids = null;
            }
            // For team-formation stages, clear form_ids (team formation doesn't require forms)
            elseif ($stage->slug === 'team-formation') {
                $stage->form_ids = null;
                $stage->form_id = null;
            }
            // For evaluation stages, ensure only one form is selected
            elseif (($stage->slug === 'evaluation' || (is_string($stage->slug) && str_starts_with($stage->slug, 'evaluation'))) 
                && $stage->form_ids && is_array($stage->form_ids) && count($stage->form_ids) > 1) {
                // Keep only the first form for evaluation stages
                $stage->form_ids = [$stage->form_ids[0]];
            }
            // For project-submission stages, sync form_id with first form_ids AND ENSURE PROJECT FORM IDS ARE UNIQUE ACROSS ALL STAGES
            elseif ($stage->form_ids && is_array($stage->form_ids) && !empty($stage->form_ids)) {
                // Uniqueness logic for project-submission
                if (
                    $stage->slug === 'project-submission' ||
                    (is_string($stage->slug) && str_starts_with($stage->slug, 'project-'))
                ) {
                    // Find all form_ids already used by project-submission stages (except this stage)
                    $usedFormIds = self::where('program_id', $stage->program_id)
                        ->where(function ($q) {
                            $q->where('slug', 'project-submission')
                                ->orWhere('slug', 'like', 'project-%');
                        })
                        ->where('id', '!=', $stage->id ?? 0)
                        ->get()
                        ->flatMap(function ($s) {
                            return is_array($s->form_ids) ? $s->form_ids : [];
                        })
                        ->unique()
                        ->toArray();
                    // Filter out any already used form_ids
                    $stage->form_ids = array_values(array_diff($stage->form_ids, $usedFormIds));
                    // Set form_id to first form for backward compatibility, or clear if empty
                    if (!empty($stage->form_ids)) {
                        if (!$stage->form_id || !in_array($stage->form_id, $stage->form_ids)) {
                            $stage->form_id = $stage->form_ids[0];
                        }
                    } else {
                        $stage->form_id = null;
                    }
                } else {
                    // Set form_id to first form for backward compatibility, or clear if empty
                    if (!empty($stage->form_ids)) {
                        if (!$stage->form_id || !in_array($stage->form_id, $stage->form_ids)) {
                            $stage->form_id = $stage->form_ids[0];
                        }
                    } else {
                        $stage->form_id = null;
                    }
                }
            }
            // Clear form_id when form_ids is empty or null (for non-registration, non-team-formation stages)
            elseif ($stage->slug !== 'registration' && $stage->slug !== 'team-formation') {
                if (empty($stage->form_ids) || (is_array($stage->form_ids) && count($stage->form_ids) === 0)) {
                    $stage->form_id = null;
                }
            }
        });
    }

    protected static function generateSlug(Stage $stage)
    {
        // If slug is already set, don't override it
        if ($stage->slug) {
            return;
        }

        // Try to get form IDs from form_ids array first (for new records)
        $formIds = [];
        
        // Check form_ids attribute directly (for new records before saving)
        if ($stage->form_ids && is_array($stage->form_ids) && !empty($stage->form_ids)) {
            $formIds = $stage->form_ids;
        }
        // Fallback to form_id if form_ids is empty
        elseif ($stage->form_id) {
            $formIds = [$stage->form_id];
        }
        // If still empty, try getFormIds() method (for existing records)
        else {
            $formIds = $stage->getFormIds();
        }

        if (!empty($formIds)) {
            // Get the first form's type to generate slug
            $firstForm = Form::find($formIds[0]);
            if ($firstForm && !empty($firstForm->type)) {
                // Only use valid form types to generate slug
                $validTypes = ['project', 'registration', 'evaluation'];
                $formType = trim($firstForm->type);
                
                // Extract base type if type contains additional characters (e.g., "project-6930415bda21b" -> "project")
                // Check if type starts with a valid type or is an exact match
                $typeToUse = null;
                
                // First check for exact match
                if (in_array($formType, $validTypes)) {
                    $typeToUse = $formType;
                } else {
                    // Check if type starts with a valid type (for cases like "project-6930415bda21b")
                    foreach ($validTypes as $validType) {
                        if (strpos($formType, $validType) === 0) {
                            $typeToUse = $validType;
                            break;
                        }
                    }
                }
                
                if ($typeToUse) {
                    if ($typeToUse === 'project') {
                        $stage->slug = 'project-submission';
                    } elseif ($typeToUse === 'registration') {
                        $stage->slug = 'registration';
                    } elseif ($typeToUse === 'evaluation') {
                        $stage->slug = 'evaluation';
                    }
                } else {
                    // If type is not valid, log warning
                    \Log::warning("Invalid form type '{$formType}' for Form ID {$firstForm->id}. Expected one of: " . implode(', ', $validTypes));
                    // Don't set slug, let it be handled elsewhere or remain null
                }
            }
        }
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('title')
                ->label('Stage Title')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('description')
                ->limit(50)
                ->wrap()
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('starts_at')
                ->label('Starts At')
                ->dateTime()
                ->sortable(),

            Tables\Columns\TextColumn::make('ends_at')
                ->label('Ends At')
                ->dateTime()
                ->sortable(),

            Tables\Columns\TextColumn::make('forms_count')
                ->label('Forms Count')
                ->badge()
                ->color('info')
                ->getStateUsing(function ($record) {
                    $formIds = $record->getFormIds();
                    return count($formIds);
                })
                ->formatStateUsing(function ($state) {
                    return $state > 0 ? $state : '0';
                })
                ->sortable(query: function ($query, string $direction): \Illuminate\Database\Eloquent\Builder {
                    // Sort by form_id first (for single form stages)
                    // Then by JSON array length for form_ids
                    return $query->orderByRaw("
                        CASE
                            WHEN form_ids IS NOT NULL AND JSON_LENGTH(form_ids) > 0
                            THEN JSON_LENGTH(form_ids)
                            WHEN form_id IS NOT NULL
                            THEN 1
                            ELSE 0
                        END {$direction}
                    ");
                }),

            Tables\Columns\IconColumn::make('has_forms')
                ->label('Has Forms')
                ->icon(fn ($record): string => !empty($record->getFormIds()) ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                ->color(fn ($record): string => !empty($record->getFormIds()) ? 'success' : 'danger')
                ->default(false),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Last Updated')
                ->since()
                ->sortable(),

            // ✅ New toggle column
            Tables\Columns\ToggleColumn::make('is_visible')
                ->label('Visible')
                ->sortable(),
        ];
    }


    public static function details(): array
    {
        return [
            Section::make()
                ->columns()
                ->schema([
                    TextEntry::make('title.ar')
                        ->label('العنوان')
                        ->extraFieldWrapperAttributes(['class' => 'text-right'])
                        ->getStateUsing(fn($record) => $record->getTranslation('title', 'ar')),
                    TextEntry::make('title.en')
                        ->label('Title')
                        ->getStateUsing(fn($record) => $record->getTranslation('title', 'en')),
                    TextEntry::make('description.ar')
                        ->label('الوصف')
                        ->extraFieldWrapperAttributes(['class' => 'text-right'])
                        ->getStateUsing(fn($record) => $record->getTranslation('description', 'ar')),
                    TextEntry::make('description.en')
                        ->label('Description')
                        ->getStateUsing(fn($record) => $record->getTranslation('description', 'en')),
                    TextEntry::make('starts_at')
                        ->label('Starts At')
                        ->dateTime(),
                    TextEntry::make('ends_at')
                        ->label('Ends At')
                        ->dateTime(),
                ]),
        ];
    }

    public static function form(): array
    {
        return [
            Forms\Components\TextInput::make('title.ar')
                ->label('العنوان')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->required()
                ->placeholder('Title (ar)'),

            Forms\Components\TextInput::make('title.en')
                ->label('Title')
                ->required()
                ->placeholder('Title (en)'),

            Forms\Components\Textarea::make('description.ar')
                ->label('الوصف')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->required()
                ->placeholder('Description (ar)'),

            Forms\Components\Textarea::make('description.en')
                ->label('Description')
                ->required()
                ->placeholder('Description (en)'),

            Forms\Components\Select::make('program_id')
                ->label('Program / البرنامج')
                ->relationship('program', 'title')
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->visible(function ($livewire) {
                    // Show program field only when creating from standalone page (no ownerRecord)
                    return !isset($livewire->ownerRecord);
                })
                ->columnSpanFull(),

                // Stage type selector - must be selected first when creating new stage
                Forms\Components\Select::make('slug')
                    ->label('Stage Type / نوع المرحلة')
                    ->options([
                        'registration' => 'Registration / التسجيل',
                        'team-formation' => 'Team Formation / تكوين الفريق',
                        'project-submission' => 'Project Submission / تقديم المشروع',
                        'evaluation' => 'Evaluation / التقييم',
                    ])
                    ->required(function (callable $get, $livewire) {
                        // Only required when creating new stage
                        // Safely check if record exists
                        $hasRecord = false;
                        if (property_exists($livewire, 'record') && isset($livewire->record)) {
                            $hasRecord = $livewire->record && $livewire->record->getKey();
                        }
                        // Required only when creating (no existing record)
                        return !$hasRecord;
                    })
                    ->reactive()
                    ->visible(function (callable $get, $livewire) {
                        // Show type selector when creating new stage or when editing
                        // This ensures team-formation can be selected when setting stage as current
                        return true;
                    })
                    ->afterStateUpdated(function (callable $set, $state) {
                        // Clear form selections when type changes
                        $set('form_id', null);
                        $set('form_ids', null);
                    })
                    ->helperText('Select the stage type first, then select the form(s)')
                    ->columnSpanFull(),

                Forms\Components\DateTimePicker::make('starts_at')
                ->label('Starts At')
                ->displayFormat('d/m/Y H:i')
                ->seconds(false)
                ->reactive()
                ->minDate(function ($livewire, callable $get) {
                    // Get program_id from form field or ownerRecord
                    $programId = $get('program_id') ?? ($livewire->ownerRecord->id ?? null);
                    $currentStageId = $get('id');
                    if ($programId) {
                        $query = Stage::where('program_id', $programId);

                        if ($currentStageId) {
                            $query->where('id', '!=', $currentStageId);
                        }
                        $lastStage = $query->orderBy('ends_at', 'desc')->first();

                        if ($lastStage && !empty($lastStage->ends_at)) {
                            // Normalize to the start of the minute to avoid browser step validation issues
                            return $lastStage->ends_at->copy()->startOfMinute();
                        } else {
                            return \Carbon\Carbon::now()->startOfMinute();
                        }
                    }
                    return \Carbon\Carbon::now()->startOfMinute();
                }),

              Forms\Components\DateTimePicker::make('ends_at')
                ->label('Ends At')
                ->displayFormat('d/m/Y H:i')
                ->seconds(false)
                ->reactive()
                ->minDate(function (callable $get) {
                    return $get('starts_at')
                        ? Carbon::parse($get('starts_at'))->startOfMinute()
                        : null;
                })
                ->disabled(fn (callable $get) => $get('starts_at') === null)
                ->afterStateUpdated(function (callable $get, callable $set, $state) {
                    $start = $get('starts_at') ? Carbon::parse($get('starts_at'))->startOfMinute() : null;
                    $end = $state ? Carbon::parse($state)->startOfMinute() : null;
                    if ($start && $end && $end->lt($start)) {
                        $set('ends_at', null);
                    }
                })
                ->helperText('The end date and time must be the same as or after the start date and time'),

                // Single form selection for registration stages
                Forms\Components\Select::make('form_id')
                    ->label('Form / النموذج')
                    ->options(function ($livewire, callable $get) {
                        // Get program_id from form field or ownerRecord
                        $programId = $get('program_id') ?? ($livewire->ownerRecord->id ?? null);
                        $currentSlug = $get('slug');

                        if ($programId) {
                            $query = \App\Models\Form::where('program_id', $programId);

                            // For registration stages, show only registration forms
                            if ($currentSlug === 'registration') {
                                $query->where('type', 'registration');
                            } else {
                                // For other stages, show all form types except project (project forms use form_ids)
                                $query->where('type', '!=', 'project');
                            }

                            // Exclude forms used in other stages
                            $currentStageId = null;
                            // Check if running within a table action (like edit in RelationManager)
                            if (method_exists($livewire, 'getMountedTableActionRecord')) {
                                $currentStageId = $livewire->getMountedTableActionRecord()?->getKey();
                            }
                            // Fallback to standard record property if not set
                            if (!$currentStageId && isset($livewire->record)) {
                                $currentStageId = $livewire->record->getKey();
                            }

                            $usedFormIds = \App\Models\Stage::where('program_id', $programId)
                                ->when($currentStageId, fn($q) => $q->where('id', '!=', $currentStageId))
                                ->get()
                                ->flatMap(fn($stage) => $stage->getFormIds())
                                ->unique()
                                ->toArray();
                            
                            $query->whereNotIn('id', $usedFormIds);

                            return $query->get()
                                ->mapWithKeys(function ($form) {
                                    $name = is_array($form->name)
                                        ? ($form->name['en'] ?? reset($form->name))
                                        : $form->name;
                                    return [$form->id => $name ?: "Form #{$form->id}"];
                                })
                                ->toArray();
                        }
                        return [];
                    })
                    ->disabled(function (callable $get) {
                        // Disable until stage type is selected
                        $slug = $get('slug');
                        return empty($slug) || $slug !== 'registration';
                    })
                    ->reactive()
                    ->visible(function (callable $get, $livewire) {
                        // Get program_id to check if it's available
                        $programId = $get('program_id') ?? ($livewire->ownerRecord->id ?? null);
                        if (!$programId) {
                            return false; // Don't show if no program selected
                        }
                        
                        $slug = $get('slug');
                        $formIds = $get('form_ids');
                        
                        // Don't show if form_ids is already selected (to avoid showing both fields)
                        if (!empty($formIds)) {
                            return false;
                        }
                        
                        // Show single form select ONLY for registration stages
                        // Team formation stages don't require forms
                        return $slug === 'registration';
                    })
                    ->helperText('Select a form for this stage')
                    ->columnSpanFull(),

                // Forms selection for project-submission and evaluation stages
                Forms\Components\Select::make('form_ids')
                    ->label('Forms / النماذج')
                    ->multiple(function (callable $get) {
                        // Only allow multiple for project-submission stages (or stages starting with "project-")
                        $slug = $get('slug');
                        $isProjectSubmission = $slug === 'project-submission' 
                            || (is_string($slug) && str_starts_with($slug, 'project-'));
                        return $isProjectSubmission;
                    })
                    ->preload()
                    ->searchable()
                    ->disabled(function (callable $get) {
                        // Disable until stage type is selected
                        $slug = $get('slug');
                        return empty($slug);
                    })
                    ->options(function ($livewire, callable $get) {
                        // Get program_id from form field or ownerRecord
                        $programId = $get('program_id') ?? ($livewire->ownerRecord->id ?? null);
                        $currentSlug = $get('slug');
                        $currentStageId = null;
                        // Check if running within a table action (like edit in RelationManager)
                        if (method_exists($livewire, 'getMountedTableActionRecord')) {
                            $currentStageId = $livewire->getMountedTableActionRecord()?->getKey();
                        }
                        // Fallback to standard record property if not set
                        if (!$currentStageId && isset($livewire->record)) {
                            $currentStageId = $livewire->record->getKey();
                        }
                        
                        if ($programId) {
                            $query = \App\Models\Form::where('program_id', $programId);
                            
                            // For evaluation stages, show only evaluation forms
                            if ($currentSlug === 'evaluation' || (is_string($currentSlug) && str_starts_with($currentSlug, 'evaluation'))) {
                                $query->where('type', 'evaluation');
                            } elseif ($currentSlug === 'project-submission' || (is_string($currentSlug) && str_starts_with($currentSlug, 'project-'))) {
                                // For project-submission stages, show only project forms
                                $query->where('type', 'project');
                                // Enforce unique Project Form IDs across all stages
                                $usedFormIds = \App\Models\Stage::where('program_id', $programId)
                                    ->where(function ($q) {
                                        $q->where('slug', 'project-submission')
                                          ->orWhere('slug', 'like', 'project-%');
                                    })
                                    ->when($currentStageId, fn($q) => $q->where('id', '!=', $currentStageId))
                                    ->get()
                                    ->flatMap(function ($stage) {
                                        return is_array($stage->form_ids) ? $stage->form_ids : [];
                                    })
                                    ->unique()
                                    ->toArray();
                                // Only show forms NOT used in other project-submission/-* stages
                                $query->whereNotIn('id', $usedFormIds);
                            }
                            
                            return $query->get()
                                ->mapWithKeys(function ($form) {
                                    $name = is_array($form->name)
                                        ? ($form->name['en'] ?? reset($form->name))
                                        : $form->name;
                                    return [$form->id => $name ?: "Form #{$form->id}"];
                                })
                                ->toArray();
                        }
                        return [];
                    })
                    ->default(function ($record) {
                        if ($record) {
                            $formIds = $record->getFormIds();
                            // For evaluation stages, ensure we return a single value (not array) when not using multiple
                            if ($record->slug === 'evaluation' || (is_string($record->slug) && str_starts_with($record->slug, 'evaluation'))) {
                                return !empty($formIds) ? $formIds[0] : null;
                            }
                            return $formIds;
                        }
                        return [];
                    })
                    ->reactive()
                    ->visible(function (callable $get, $livewire) {
                        // Get program_id to check if it's available
                        $programId = $get('program_id') ?? ($livewire->ownerRecord->id ?? null);
                        if (!$programId) {
                            return false; // Don't show if no program selected
                        }
                        
                        $slug = $get('slug');
                        $formId = $get('form_id');
                        $formIds = $get('form_ids');
                        
                        // Don't show if form_id is already selected for registration stages
                        if ($slug === 'registration' && !empty($formId) && empty($formIds)) {
                            return false;
                        }
                        
                        // Team formation stages don't require forms - hide form selection
                        if ($slug === 'team-formation') {
                            return false;
                        }
                        
                        // Show forms select for:
                        // 1. All stages except registration and team-formation (registration uses single form_id, team-formation doesn't need forms)
                        // 2. When form_ids is already set
                        // 3. When slug is selected (for new stage creation)
                        return ($slug !== 'registration' && $slug !== 'team-formation')
                            || !empty($formIds)
                            || (!empty($slug) && $slug !== 'registration' && $slug !== 'team-formation');
                    })
                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                        // Normalize the state to array format for storage
                        $slug = $get('slug');
                        $isEvaluation = $slug === 'evaluation' || (is_string($slug) && str_starts_with($slug, 'evaluation'));
                        $isProjectSubmission = $slug === 'project-submission' || (is_string($slug) && str_starts_with($slug, 'project-'));
                        
                        if ($isEvaluation) {
                            // For evaluation stages, ensure single value is stored as array
                            $formIds = $state ? (is_array($state) ? $state : [$state]) : [];
                            // Limit to first item only
                            $formIds = !empty($formIds) ? [$formIds[0]] : [];
                            $set('form_ids', $formIds);
                            // Also update form_id to first one for backward compatibility, or clear if empty
                            if (!empty($formIds)) {
                                $set('form_id', $formIds[0]);
                            } else {
                                $set('form_id', null);
                            }
                        } elseif ($isProjectSubmission) {
                            // For project-submission stages, enforce uniqueness (proactively filter not strictly required since options list will filter already used forms)
                            $programId = $get('program_id');
                            $recordId = null;
                            // Try to get current record ID if inside edit
                            $idVal = $get('id');
                            if (null !== $idVal) $recordId = $idVal;
                            $usedFormIds = [];
                            if ($programId) {
                                $usedFormIds = \App\Models\Stage::where('program_id', $programId)
                                    ->where(function ($q) {
                                        $q->where('slug', 'project-submission')
                                          ->orWhere('slug', 'like', 'project-%');
                                    })
                                    ->when($recordId, fn($q) => $q->where('id', '!=', $recordId))
                                    ->get()
                                    ->flatMap(function ($stage) {
                                        return is_array($stage->form_ids) ? $stage->form_ids : [];
                                    })
                                    ->unique()
                                    ->toArray();
                            }
                            $formIds = is_array($state) ? $state : ($state ? [$state] : []);
                            // Uniqueness filter
                            $formIds = array_values(array_diff($formIds, $usedFormIds));
                            $set('form_ids', $formIds);
                            // Also update form_id to first one for backward compatibility, or clear if empty
                            if (!empty($formIds)) {
                                $set('form_id', $formIds[0]);
                            } else {
                                $set('form_id', null);
                            }
                        } else {
                            // For other stage types (non-registration, non-team-formation)
                            $formIds = is_array($state) ? $state : ($state ? [$state] : []);
                            $set('form_ids', $formIds);
                            // Update form_id or clear if empty
                            if (!empty($formIds)) {
                                $set('form_id', $formIds[0]);
                            } else {
                                $set('form_id', null);
                            }
                        }
                    })
                    ->helperText(function (callable $get) {
                        $slug = $get('slug');
                        if (empty($slug)) {
                            return 'Please select the stage type first / يرجى اختيار نوع المرحلة أولاً';
                        }
                        if ($slug === 'evaluation' || (is_string($slug) && str_starts_with($slug, 'evaluation'))) {
                            return 'Select one evaluation form for this stage / اختر نموذج تقييم واحد لهذه المرحلة';
                        }
                        if ($slug === 'project-submission' || (is_string($slug) && str_starts_with($slug, 'project-'))) {
                            return 'Only unused Project Forms can be selected for this stage. Each Project Form can only be used in one stage. / لا يمكن اختيار أي نموذج مشروع تم استخدامه مسبقا في مرحلة أخرى';
                        }
                        return 'Select form(s) for this stage / اختر النموذج/النماذج لهذه المرحلة';
                    })
                    ->columnSpanFull(),

        ];
    }

    public function program()
    {
        return $this->belongsTo(program::class);
    }

    /**
     * Get the single form (for backward compatibility).
     */
    public function formStage()
    {
        return $this->belongsTo(Form::class,'form_id');
    }

    /**
     * Get all forms associated with this stage from form_ids array.
     */
    public function forms()
    {
        $formIds = $this->getFormIds();
        if (empty($formIds)) {
            return collect();
        }
        return Form::whereIn('id', $formIds)->get();
    }

    /**
     * Get form IDs array (combines form_id and form_ids).
     * For registration stages, returns only form_id.
     */
    public function getFormIds(): array
    {
        // For registration stages, only return form_id (single form)
        if ($this->slug === 'registration') {
            return $this->form_id ? [$this->form_id] : [];
        }

        $formIds = [];

        // Get from form_ids JSON column (for project-submission stages)
        if ($this->form_ids && is_array($this->form_ids)) {
            $formIds = array_merge($formIds, $this->form_ids);
        }

        // Get from form_id (for backward compatibility or if form_ids is empty)
        if ($this->form_id && !in_array($this->form_id, $formIds)) {
            $formIds[] = $this->form_id;
        }

        // Remove duplicates and return
        return array_unique(array_filter($formIds));
    }

    /**
     * Set form IDs.
     */
    public function setFormIds(array $formIds): void
    {
        // Enforce uniqueness across all project-submission stages
        if (
            $this->slug === 'project-submission' ||
            (is_string($this->slug) && str_starts_with($this->slug, 'project-'))
        ) {
            $usedFormIds = self::where('program_id', $this->program_id)
                ->where(function ($q) {
                    $q->where('slug', 'project-submission')
                        ->orWhere('slug', 'like', 'project-%');
                })
                ->where('id', '!=', $this->id ?? 0)
                ->get()
                ->flatMap(function ($stage) {
                    return is_array($stage->form_ids) ? $stage->form_ids : [];
                })
                ->unique()
                ->toArray();
            $formIds = array_values(array_diff($formIds, $usedFormIds));
        }
        $this->form_ids = array_unique(array_filter($formIds));
        // Also set form_id to first one for backward compatibility
        if (!empty($formIds)) {
            $this->form_id = $formIds[0];
        }
    }

}
