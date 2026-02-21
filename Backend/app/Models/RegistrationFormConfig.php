<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class RegistrationFormConfig extends Model
{
    use HasTranslations, LogsActivity, HasActivityLog;

    protected $fillable = [
        'program_id',
        'registration_type',
        'min_age',
        'max_age',
        'min_team_members',
        'max_team_members',
        'team_fields_enabled',
        'label_register_as',
        'option_register_individual',
        'option_register_team',
        'label_team_name',
        'label_team_logo',
        'label_team_serial',
        'help_team_serial',
        'is_active',
        'is_archived',
        'archived_at',
        'scoring_enabled',
    ];

    public array $translatable = [
        'label_register_as',
        'option_register_individual',
        'option_register_team',
        'label_team_name',
        'label_team_logo',
        'label_team_serial',
        'help_team_serial',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'scoring_enabled' => 'boolean',
    ];

    protected array $logFields = [
        'registration_type',
        'min_age',
        'max_age',
        'min_team_members',
        'max_team_members',
        'team_fields_enabled',
        'label_register_as',
        'option_register_individual',
        'option_register_team',
        'label_team_name',
        'label_team_logo',
        'label_team_serial',
        'help_team_serial',
        'is_active',
        'is_archived',
        'archived_at',
        'scoring_enabled',
        'program.title',
        'program_id',
    ];

    protected string $moduleName = 'Registration Form Config';
    protected string $logName = 'registration_form_config';

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the assessment criteria for this registration form config.
     */
    public function assessmentCriteria()
    {
        return $this->hasMany(AssessmentCriterion::class)->orderBy('sort_order');
    }

    public static function form(): array
    {
        return [
            // Top Info Box
            Forms\Components\Placeholder::make('info')
                ->columnSpanFull(),

            Forms\Components\Section::make('Registration Configuration')
                ->schema([
                    Forms\Components\Select::make('program_id')
                        ->label('Program')
                        ->options(function () {
                            $user = auth()->user();

                            if ($user->isSuperAdmin()) {
                                return Program::pluck('title', 'id')->toArray();
                            }

                            $supervisorPrograms = UserProgram::where('user_id', $user->id)
                                ->pluck('program_id')
                                ->toArray();

                            return Program::whereIn('id', $supervisorPrograms)
                                ->pluck('title', 'id')
                                ->toArray();
                        })
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Select::make('registration_type')
                        ->label('Registration Type')
                        ->options([
                            'individual' => 'Individual (No additional fields)',
                            'team' => 'Team',
                            'both' => 'Both Individual & Team',
                        ])
                        ->helperText('Select how participants can register.')
                        ->required()
                        ->reactive()
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline(false),
                ]),

            // Age Restrictions Section
            Forms\Components\Section::make('Age Restrictions')
                ->schema([
                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('min_age')
                            ->label('Minimum Age')
                            ->numeric()
                            ->nullable()
                            ->minValue(10)
                            ->reactive(),

                        Forms\Components\TextInput::make('max_age')
                            ->label('Maximum Age')
                            ->numeric()
                            ->nullable()
                            ->reactive()
                            ->rule(function (callable $get) {
                                $min = $get('min_age');
                                return function ($attribute, $value, $fail) use ($min) {
                                    if ($min !== null && $value !== null && $value < $min) {
                                        $fail('Maximum age must be greater than or equal to minimum age.');
                                    }
                                };
                            }),
                    ])->columns(2),
                ]),

            // Team Settings (only if type = team)
            Forms\Components\Section::make('Team Registration Settings')
                ->visible(fn($get) => in_array($get('registration_type'), ['team', 'both']))
                ->schema([
                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('min_team_members')
                            ->label('Minimum Team Members')
                            ->default(2)
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\TextInput::make('max_team_members')
                            ->label('Maximum Team Members')
                            ->numeric()
                            ->nullable(),
                    ])->columns(),

                    Forms\Components\Placeholder::make('team_fields_info')
                        ->content('The following fields will be automatically added: Team Name (required), Team Logo (optional), Team Serial Number (required).')
                        ->columnSpanFull(),
                ]),

            // Register As Options (only if type = both)
            // make it required if registration_type is both
            Forms\Components\Section::make('Register As Options')
                ->visible(fn($get) => $get('registration_type') === 'both')
                ->schema([
                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('label_register_as.en')
                            ->label('Label - Register As')
                            ->required(fn(callable $get) => $get('registration_type') === 'both'),
                        Forms\Components\TextInput::make('label_register_as.ar')
                            ->label('التسمية - التسجيل ك')
                            ->extraFieldWrapperAttributes(['class' => 'text-right'])
                            ->required(fn(callable $get) => $get('registration_type') === 'both'),
                    ])->columns(),

                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('option_register_individual.en')
                            ->label('Option - Individual')
                            ->required(fn(callable $get) => $get('registration_type') === 'both'),
                        Forms\Components\TextInput::make('option_register_individual.ar')
                            ->label('الخيار - الفرد')
                            ->extraFieldWrapperAttributes(['class' => 'text-right'])
                            ->required(fn(callable $get) => $get('registration_type') === 'both'),
                    ])->columns(),

                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('option_register_team.en')
                            ->label('Option - Team')
                            ->required(fn(callable $get) => $get('registration_type') === 'both'),
                        Forms\Components\TextInput::make('option_register_team.ar')
                            ->label('الخيار - الفريق')
                            ->extraFieldWrapperAttributes(['class' => 'text-right'])
                            ->required(fn(callable $get) => $get('registration_type') === 'both'),
                    ])->columns(),
                ]),

            // Field Labels & Team Fields Labels (Visible if team or both)
            Forms\Components\Section::make('Field Labels & Text (Optional)')
                ->visible(fn($get) => in_array($get('registration_type'), ['team', 'both']))
                ->collapsible()
                ->collapsed(fn($get) => !in_array($get('registration_type'), ['team', 'both']))
                ->schema([
                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('label_team_name.en')
                            ->label('Team Name Label')
                            ->required(fn(callable $get) => in_array($get('registration_type'), ['team', 'both'])),

                        Forms\Components\TextInput::make('label_team_name.ar')
                            ->label('اسم الفريق (التسمية)')
                            ->extraFieldWrapperAttributes(['class' => 'text-right'])
                            ->required(fn(callable $get) => in_array($get('registration_type'), ['team', 'both'])),
                    ])->columns(),

                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('label_team_logo.en')->label('Team Logo Label'),
                        Forms\Components\TextInput::make('label_team_logo.ar')->label('شعار الفريق')->extraFieldWrapperAttributes(['class' => 'text-right']),
                    ])->columns(),

                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('label_team_serial.en')
                            ->label('Team Serial Label')
                            ->required(fn(callable $get) => in_array($get('registration_type'), ['team', 'both'])),
                        Forms\Components\TextInput::make('label_team_serial.ar')
                            ->label('رقم الفريق')
                            ->extraFieldWrapperAttributes(['class' => 'text-right'])
                            ->required(fn(callable $get) => in_array($get('registration_type'), ['team', 'both'])),
                    ])->columns(),

                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('help_team_serial.en')->label('Help Text - Serial Number'),
                        Forms\Components\TextInput::make('help_team_serial.ar')->label('الرقم التسلسلي')->extraFieldWrapperAttributes(['class' => 'text-right']),
                    ])->columns(),
                ]),

            // Scoring Configuration Section
            Forms\Components\Section::make('Scoring Configuration')
                ->description('Configure assessment criteria for scoring registration form submissions. When enabled, admins will be prompted to enter scores when accepting submissions.')
                ->schema([
                    Forms\Components\Toggle::make('scoring_enabled')
                        ->label('Enable Scoring')
                        ->helperText('When enabled, admins will be required to enter scores for each assessment criterion when accepting submissions.')
                        ->reactive()
                        ->default(false)
                        ->columnSpanFull(),

                    Forms\Components\Repeater::make('assessment_criteria')
                        ->label('Assessment Criteria')
                        ->relationship('assessmentCriteria')
                        ->afterStateUpdated(function (callable $get, callable $set, $state) {
                            // Validate unique sort_order values whenever the repeater state changes
                            if (!is_array($state) || empty($state)) {
                                return;
                            }
                            
                            $sortOrders = collect($state)
                                ->pluck('sort_order')
                                ->filter(fn($order) => $order !== null && $order !== '')
                                ->map(fn($order) => (int) $order);
                            
                            $duplicates = $sortOrders
                                ->countBy()
                                ->filter(fn($count) => $count > 1)
                                ->keys();
                            
                            if ($duplicates->isNotEmpty()) {
                                $duplicateValues = $duplicates->implode(', ');
                                \Filament\Notifications\Notification::make()
                                    ->title('Validation Error / خطأ في التحقق')
                                    ->body("Duplicate sort order values found: {$duplicateValues}. Each criterion must have a unique sort order. / تم العثور على قيم ترتيب مكررة: {$duplicateValues}. يجب أن يكون لكل معيار قيمة ترتيب فريدة.")
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->schema([
                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->placeholder('Enter description')
                                ->required()
                                ->helperText('Describe what is being scored (e.g., "Technical Skills", "Innovation", "Feasibility")')
                                ->rows(2)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('max_score')
                                ->label('Maximum Score')
                                ->placeholder('Enter max score')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->helperText('Maximum score that can be assigned for this criterion')
                                ->default(50),

                            Forms\Components\TextInput::make('sort_order')
                                ->label('Sort Order')
                                ->integer()
                                ->required()
                                ->minValue(1)
                                ->default(1)
                                ->helperText('Order in which this criterion appears (lower numbers appear first)')
                                ->reactive()
                                ->rule(function (callable $get) {
                                    return function ($attribute, $value, $fail) use ($get) {
                                        // Validate that sort_order is not empty
                                        if ($value === null || $value === '') {
                                            $fail('The sort order field is required. / حقل الترتيب مطلوب.');
                                            return;
                                        }
                                        
                                        // Validate that sort_order is a positive integer
                                        if (!is_numeric($value) || (float)$value != (int)$value || (int)$value < 1) {
                                            $fail('The sort order must be a positive integer. / يجب أن يكون الترتيب رقماً صحيحاً موجباً.');
                                            return;
                                        }
                                        
                                        // Get all assessment criteria from the repeater
                                        // Try different paths to access the repeater data
                                        $allCriteria = $get('../..') ?? $get('../../assessment_criteria') ?? $get('assessment_criteria') ?? [];
                                        
                                        // If we got the parent repeater state, it should be an array of items
                                        if (is_array($allCriteria) && !empty($allCriteria) && isset($allCriteria[0]) && is_array($allCriteria[0])) {
                                            // This is the repeater items array
                                        } else {
                                            // Try to get from the form root
                                            $allCriteria = $get('../../../../assessment_criteria') ?? [];
                                        }
                                        
                                        // Count how many criteria have this sort_order value
                                        $duplicateCount = 0;
                                        foreach ($allCriteria as $criterion) {
                                            if (!is_array($criterion)) {
                                                continue;
                                            }
                                            $orderValue = $criterion['sort_order'] ?? null;
                                            if ($orderValue !== null && $orderValue !== '' && (int)$orderValue === (int)$value) {
                                                $duplicateCount++;
                                            }
                                        }
                                        
                                        if ($duplicateCount > 1) {
                                            $fail('Each assessment criterion must have a unique sort order value. This order value is already assigned to another criterion. / يجب أن يكون لكل معيار تقييم قيمة ترتيب فريدة. قيمة الترتيب هذه مخصصة بالفعل لمعيار آخر.');
                                        }
                                    };
                                }),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel('Add Assessment Criterion')
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['description'] ?? 'New Criterion')
                        ->visible(fn (callable $get) => $get('scoring_enabled') === true)
                        ->columnSpanFull(),
                ]),
        ];
    }

    public static function table(): array
    {
        return [
            Tables\Columns\TextColumn::make('program.title')
                ->label('Program')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('registration_type')
                ->getStateUsing(fn($record) => ($record->registration_type === 'both') ? 'Both (Individual & Team)' : ucfirst($record->registration_type))
                ->sortable()
                ->searchable(),

            Tables\Columns\IconColumn::make('is_active')
                ->label('Active')
                ->boolean()
                ->sortable()
                ->searchable(),
        ];
    }

    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    public function archive(): bool
    {
        return $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);
    }

    public function restore(): bool
    {
        return $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }
}
