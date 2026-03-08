<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Context;
use Spatie\Translatable\HasTranslations;
use Filament\Forms;
use Filament\Tables;
use function Symfony\Component\String\s;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method static open()
 * @method static closed()
 * @method static pluck(string $string, string $string1)
 * @method static first()
 */
class Program extends Model
{
    use HasTranslations, HasFactory, LogsActivity, HasActivityLog;

    protected array $logFields = [
        'title',
        'about',
        'type',
        'registration_closed_date',
        'is_published',
        'is_archived',
        'archived_at',
        'terms_and_conditions',
        'banner'
    ];

    protected string $moduleName = 'Program';
    protected string $logName = 'program';

    public array $translatable = [
        'title',
        'about',
        'terms_and_conditions'
    ];

    protected $fillable = [
        'title',
        'about',
        'type',
        'terms_and_conditions',
        'registration_closed_date',
        'banner',
        'is_published',
        'is_archived',
        'archived_at'
    ];

    protected $casts = [
        'registration_closed_date' => 'date',
        'is_published' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'type' => 'string'
    ];


    protected static function boot()
    {
        parent::boot();

        static::created(function (Program $program) {
            if (auth()->check()) {
                $program->users()->syncWithoutDetaching([auth()->id()]);
            }
            
            // Fire ProgramCreated event
            event(new \App\Events\ProgramCreated($program));
        });

        static::deleting(function ($program) {
            $committees = Committee::where('program_id', $program->id);
            $committeesIds = $committees->pluck('id');
            CommitteeJudge::whereIn('committee_id', $committeesIds)->delete();
            $committees->delete();

            $forms = Form::where('program_id', $program->id)->get();
            foreach ($forms as $form) {
                $form->delete();
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_programs');
    }

    public function judges(): BelongsToMany
    {
        return $this->belongsToMany(Judge::class, 'program_judge')->withTimestamps();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProgramApplication::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function mentors(): HasMany
    {
        return $this->hasMany(Mentor::class);
    }

    public function mentorsMany(): BelongsToMany
    {
        return $this->belongsToMany(Mentor::class, 'mentor_programs')->withTimestamps();
    }

    public function guidelines(): HasMany
    {
        return $this->hasMany(Guideline::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class);
    }

    public function registrationStage(): ?Stage
    {
        return $this->stages()->where('slug', 'registration')->where('is_visible', true)->first();
    }

    public function projectStage(): ?Stage
    {
        return $this->stages()->where('slug', 'project-submission')->where('is_visible', true)->first();
    }

    public function currentStage(): ?Stage
    {
        return $this->stages()->where('is_visible', true)->where('starts_at', '<=', now())->where('ends_at', '>=', now())->first();
    }

    public function teams(): HasManyThrough
    {
        return $this->hasManyThrough(Team::class,
            ProgramApplication::class,
            'program_id',
            'application_id'
        );
    }

    public function tabs(): HasMany
    {
        return $this->hasMany(ProgramTab::class);
    }

    public function isClosed(): bool
    {
        $registrationStage = $this->registrationStage();

        if (!$registrationStage) {
            return true; // No registration stage means it's closed
        }

        $now = now();

        // Case 1: Registration has ended (ends_at < now)
        if ($registrationStage->ends_at && $registrationStage->ends_at->lt($now)) {
            return true;
        }

        // Case 2: Registration hasn't started yet (starts_at > now)
        if ($registrationStage->starts_at && $registrationStage->starts_at->gt($now)) {
            return true;
        }

        // Registration is open if it has started and hasn't ended
        return false;
    }

    public function isPublished(): bool
    {
        return $this->is_published;
    }

    public function scopeOpen($query)
    {
        return $query->whereHas('stages', function ($q) {
            $q->where('slug', 'registration')
              ->where(function ($subQ) {
                  // Registration has started (starts_at <= now)
                  $subQ->whereNull('starts_at')
                       ->orWhere('starts_at', '<=', now());
              })
              ->where('ends_at', '>', now());
        });
    }

    public function scopeClosed($query)
    {
        return $query->where(function ($q) {
            $q->whereDoesntHave('stages', function ($subQ) {
                $subQ->where('slug', 'registration');
            })->orWhereHas('stages', function ($subQ) {
                $subQ->where('slug', 'registration')
                     ->where(function ($statusQ) {
                         // Registration has ended (ends_at <= now)
                         $statusQ->where('ends_at', '<=', now())
                                 // OR registration hasn't started yet (starts_at > now)
                                 ->orWhere('starts_at', '>', now());
                     });
            });
        });
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeUnpublished($query)
    {
        return $query->where('is_published', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function isCurrent(): bool
    {
        return (int) $this->id === (int) session('current_program_id');
    }

    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    public function archive(): bool
    {
        $result = $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);


        return $result;
    }

    public function restore(): bool
    {
        $result = $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);


        return $result;
    }

    public function setAsCurrent(): void
    {
        session()->put('current_program_id', $this->id);
    }

    public function registrationFormConfig(): HasOne
    {
        return $this->hasOne(\App\Models\RegistrationFormConfig::class)->active();
    }

    public function evaluationStageConfig(): HasOne
    {
        return $this->hasOne(\App\Models\EvaluationStageConfig::class);
    }

    public function teamFormConfig()
    {
        return $this->hasOne(TeamFormConfig::class);
    }

    public function tracks()
    {
        return $this->hasMany(Track::class)->orderBy('order', 'asc');
    }

    public function labels()
    {
        return $this->hasMany(ProgramLabel::class);
    }

    public function winners()
    {
        return $this->hasMany(Winner::class);
    }
    public function scopeProgramType($query, ?string $programType = null)
    {
        if (! $programType) {
            return $query;
        }

        $programTypeTitle = \Illuminate\Support\Str::headline(str_replace('-', ' ', $programType));

        return $query->where('type', $programTypeTitle);
    }

    /**
     * Check if the currently authenticated user can access (edit or delete) this program (program)
     * This checks if the user is assigned to this program or is a super admin.
     * 
     * @param int|null $userId If null, will use the currently authenticated user
     * @return bool
     */
    public function canAccessProgram(?int $userId = null): bool
    {
        $user = $userId ? \App\Models\User::find($userId) : auth()->user();

        if (!$user) {
            return false;
        }

        // If user is super admin, always allow access
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        // Check if user is assigned to this program (through UserProgram relation/table)
        if (
            \App\Models\UserProgram::where('user_id', $user->id)
                ->where('program_id', $this->id)
                ->exists()
        ) {
            return true;
        }

        // You can add additional checks here, such as direct supervisor relation if needed

        return false;
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('title')
                ->badge(fn($record) => $record->isCurrent())
                ->color(fn($record) => $record->isCurrent() ? 'primary' : 'secondary')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('type')
                ->label('Type')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('applications_count')
                ->label('Applications')
                ->counts('applications')
                ->sortable()
                ->alignCenter(),

            Tables\Columns\TextColumn::make('status')
                ->getStateUsing(function ($record) {
                    return $record->isClosed() ? 'Closed' : 'Open';
                })
                ->badge()
                ->color(function ($record) {
                    return $record->isClosed() ? 'danger' : 'success';
                })
                ->sortable(true, fn($query, $direction) => $query->orderBy('registration_closed_date', $direction)),

            Tables\Columns\IconColumn::make('is_published')
                ->label('Published')
                ->boolean()
                ->searchable(),


            Tables\Columns\TextColumn::make('created_at')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Last Updated')
                ->since()
                ->sortable(),

            Tables\Columns\TextColumn::make('last_updated_by')
                ->label('Last Updated By')
                ->getStateUsing(function ($record) {
                    $activity = \App\Models\ActivityLog::where('log_name', 'program')
                        ->where('subject_id', $record->id)
                        ->orderByDesc('updated_at')
                        ->first();

                    if ($activity && $activity->causer_id) {
                        $user = \App\Models\User::find($activity->causer_id);
                        return $user ? $user->name : '-';
                    }

                    return '-';
                })
                ->sortable(false)
                ->searchable(false),
        ];
    }

    public static function form(): array
    {
        return [


            Forms\Components\TextInput::make('title.en')
                ->label('Title')
                ->required()
                ->placeholder('Title'),

            Forms\Components\TextInput::make('title.ar')
                ->label('العنوان')
                ->required()
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->placeholder('العنوان'),
            Forms\Components\Select::make('type')
                ->label('Program Type')
                ->options([
                    'Hackathon' => 'Hackathon',
                    'Sandbox' => 'Sandbox',
                    ' Idea Bank' => ' Idea Bank',
                ])
                ->required()
                ->columnSpanFull()
                ->searchable()
                ->validationMessages([
                    'unique' => 'This program type has already been registered.',
                ])
                ->placeholder('Select Program Type'),



            Forms\Components\RichEditor::make('about.en')
                ->label('About')
                ->required()
                ->placeholder('About (en)'),
            Forms\Components\RichEditor::make('about.ar')
                ->label('الوصف')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->required()
                ->placeholder('الوصف'),



            Forms\Components\RichEditor::make('terms_and_conditions.en')
                ->label('Terms and conditions')
                ->required()
                ->placeholder('Terms and Conditions (en)'),
            Forms\Components\RichEditor::make('terms_and_conditions.ar')
                ->label('الشروط والأحكام')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->required()
                ->placeholder('الشروط والأحكام'),

            Forms\Components\FileUpload::make('banner')
                ->required()
                ->image()
                ->directory('program_banners')
                ->placeholder('Banner'),

            Forms\Components\Checkbox::make('is_published')
                ->label('Published'),
        ];
    }

    public static function details(): array
    {
        return [
            Section::make('Program Details')
                ->columns()
                ->schema([
                    TextEntry::make('title')
                        ->label('العنوان')
                        ->getStateUsing(fn($record) => $record->getTranslation('title', 'ar')),
                    TextEntry::make('title')
                        ->label('Title')
                        ->getStateUsing(fn($record) => $record->getTranslation('title', 'en')),
                    TextEntry::make('about')
                        ->label('الوصف')
                        ->getStateUsing(fn($record) => $record->getTranslation('about', 'ar'))
                        ->html(),
                    TextEntry::make('about')
                        ->label('About')
                        ->getStateUsing(fn($record) => $record->getTranslation('about', 'en'))
                        ->html(),
                    TextEntry::make('terms_and_conditions')
                        ->label('الشروط والأحكام')
                        ->getStateUsing(fn($record) => $record->getTranslation('terms_and_conditions', 'ar'))
                        ->html(),
                    TextEntry::make('terms_and_conditions')
                        ->label('Terms and conditions')
                        ->getStateUsing(fn($record) => $record->getTranslation('terms_and_conditions', 'en'))
                        ->html(),

                    IconEntry::make('is_published')->boolean()->label('Published'),

                    ImageEntry::make('Banner')
                        ->getStateUsing(fn($record) => $record->banner),
                ]),
        ];
    }

}
