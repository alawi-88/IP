<?php

namespace App\Models;

use App\Notifications\ActivationEmail;
use App\Notifications\NewJudgeAccount;
use App\Traits\HasActivityLog;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Filament\Tables;
use Filament\Forms;
use Spatie\Translatable\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Validation\Rule;

class Judge extends Authenticatable
{
    use HasTranslations, HasFactory, HasApiTokens, Notifiable, LogsActivity, HasActivityLog;

    public array $translatable = ['name', 'experience_field'];

    protected $fillable = [
        'program_id',
        'name',
        'email',
        'phone_number',
        'experience_field',
        'password',
        'last_login_at',
        'password_reset_code',
        'otp_code',
        'otp_code_expires_at',
        'email_verified_at',
        'activation_code',
        'registration_method',
        'is_archived',
        'archived_at'
    ];

    public const REGISTRATION_METHOD_SELF = 'self-registration';
    public const REGISTRATION_METHOD_ADMIN = 'admin-added';

    public static function getRegistrationMethods(): array
    {
        return [
            self::REGISTRATION_METHOD_SELF => 'Self Registration',
            self::REGISTRATION_METHOD_ADMIN => 'Added by Admin',
        ];
    }

    protected $casts = [
        'name' => 'array',
        'experience_field' => 'array',
        'last_login_at' => 'datetime',
        'otp_code_expires_at' => 'datetime',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    protected array $logFields = [
        'name',
        'email',
        'phone_number',
        'experience_field',
        'program.title',
        'program_id',
        'is_archived',
        'archived_at',
    ];

    protected string $moduleName = 'Judge';
    protected string $logName = 'judge';

    /**
     * Override the default notification
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new ActivationEmail);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($judge) {
            do {
                $serialNumber = sprintf('%06d', rand(0, 999999));
            } while (static::where('serial_number', $serialNumber)->exists());

            $judge->serial_number = $serialNumber;
        });

        static::deleting(function ($judge) {

            CommitteeJudge::where('judge_id', $judge->id)->delete();
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'program_judge')->withTimestamps();
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'judge_projects')
            ->withoutGlobalScopes()
            ->withTimestamps()
            ->withPivot(['id', 'evaluation_score']);
    }

    public function getAssignedProgramsCountAttribute(): int
    {
        return $this->programs()->count();
    }

    public function getAssignedProjectsCountAttribute(): int
    {
        return $this->projects()->count();
    }

    public function getSubmittedEvaluationsCountAttribute(): int
    {
        return $this->projects()
            ->whereHas('evaluations')
            ->count();
    }

    public static function columns(): array
    {
        return [
            // serial number column
            Tables\Columns\TextColumn::make('serial_number')
                ->label('Serial Number')
                ->sortable(),
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('email')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('registration_method')
                ->label('Registration')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    self::REGISTRATION_METHOD_SELF => 'warning',
                    self::REGISTRATION_METHOD_ADMIN => 'primary',
                    default => 'gray',
                })
                ->formatStateUsing(fn($state) => str_replace('-', ' ', ucfirst($state))),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Registration Date')
                ->dateTime()
                ->sortable(),

            Tables\Columns\TextColumn::make('last_login_at')
                ->label('Last login')
                ->formatStateUsing(fn($state) => $state?->diffForHumans())
                ->placeholder('Never')
                ->sortable(),


            Tables\Columns\TextColumn::make('email_verified_at')
                ->label('Status')
                ->badge()
                ->formatStateUsing(fn($state) => $state ? 'Verified' : 'Not Verified')
                ->placeholder('Not Verified')
                ->color(fn($state) => $state ? 'success' : 'danger'),

            Tables\Columns\TextColumn::make('assigned_programs_count')
                ->label('Programs Count')
                ->counts('programs'),

            Tables\Columns\TextColumn::make('assigned_projects_count')
                ->label('Projects Count')
                ->counts('projects'),

            Tables\Columns\TextColumn::make('submitted_evaluations_count')
                ->label('Evaluations Count')
                ->counts('projects'),
        ];
    }

    public static function form(): array
    {
        return [
            Forms\Components\TextInput::make('name.en')
                ->label('Full name')
                ->required(),

            Forms\Components\TextInput::make('name.ar')
                ->label('الاسم كامل')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->required(),



            Forms\Components\TextInput::make('email')
                ->label('Email address')
                ->required()
                ->email()
                ->unique(
                    table: Judge::class,
                    column: 'email',
                    ignoreRecord: true
                ),
            Forms\Components\TextInput::make('phone_number')
                ->label('Phone Number'),


            Forms\Components\TextInput::make('experience_field.en')
                ->label('Professional background')
                ->required(),

            Forms\Components\TextInput::make('experience_field.ar')
                ->label('خبرة المهنية')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->required(),

            Forms\Components\Select::make('programs')
                ->label('Programs')
                ->multiple()
                ->required()
                ->relationship(
                    name: 'programs',
                    titleAttribute: 'title',
                    modifyQueryUsing: function ($query) {
                        $user = auth()->user();
                        if (! $user->isSuperAdmin()) {
                            $supervisorPrograms = UserProgram::where('user_id', $user->id)
                                ->pluck('program_id');
                            $query->whereIn('programs.id', $supervisorPrograms);
                        }
                    }
                )
                ->getSearchResultsUsing(function (string $search) {
                    $user = auth()->user();

                    $query = Program::query()
                        ->where('programs.title', 'like', "%{$search}%");

                    if (! $user->isSuperAdmin()) {
                        $supervisorPrograms = UserProgram::where('user_id', $user->id)
                            ->pluck('program_id');
                        $query->whereIn('programs.id', $supervisorPrograms);
                    }

                    return $query->limit(50)->pluck('programs.title', 'programs.id');
                })
                ->columnSpanFull(),

        ];
    }

    public static function details(): array
    {
        return [
            Section::make('Judge Details')
                ->columns()
                ->schema([
                    TextEntry::make('name')->label('Judge name'),
                    TextEntry::make('email')->label('Email'),
                    TextEntry::make('experience_field')->label('Experience field'),
                    TextEntry::make('project_status')
                        ->label('Projects Assigned')
                        ->getStateUsing(function ($record) {
                            $totalProjects = $record->projects()->count();
                            $completedCount = $record->projects()
                                ->wherePivot('evaluation_score', '>', 0)
                                ->count();
                            $pendingCount = $totalProjects - $completedCount;
                            return "{$pendingCount} Pending, {$completedCount} Completed";
                        }),
                    TextEntry::make('programs')
                        ->label('Programs')
                        ->getStateUsing(function ($record) {
                            return $record->programs->map(function ($program) {
                                return "<a href='" . route('programs.show', $program->id) . "' target='_blank'>" . $program->title . "</a>";
                            })->join(', ');
                        })
                        ->html(),

                    TextEntry::make('phone_number'),
                    TextEntry::make('created_at')
                        ->label('Created At')
                        ->getStateUsing(function ($record) {
                            return $record->created_at->format('Y-m-d H:i:s');
                        }),


                ])
        ];
    }

    public function isSelfRegistered(): bool
    {
        return $this->registration_method === self::REGISTRATION_METHOD_SELF;
    }

    public function isAdminAdded(): bool
    {
        return $this->registration_method === self::REGISTRATION_METHOD_ADMIN;
    }

    public static function namesByIds(array $ids): array
    {
        return self::whereIn('id', $ids)->pluck('name')->toArray();
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

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }
}
