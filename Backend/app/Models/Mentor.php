<?php

namespace App\Models;

use App\Models\Scopes\CompetitionApplicationScope;
use App\Traits\Competition\FilterByCompetition;
use App\Traits\HasActivityLog;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;

#[ScopedBy([CompetitionApplicationScope::class])]
class Mentor extends AuthenticatableUser
{
    use HasTranslations, HasFactory, FilterByCompetition, LogsActivity, HasActivityLog, Notifiable, HasApiTokens;

    protected array $logFields = [
        'name',
        'experience',
        'brief',
        'image',
        'linkedin',
        'facebook',
        'instagram',
        'is_visible',
        'competition.title',
        'competition_id',
        'track.name',
        'is_archived',
        'archived_at'
    ];

    protected string $moduleName = 'Mentor';
    protected string $logName = 'mentor';

    public array $translatable = [
        'name',
        'experience',
        'brief',
        'profession',
    ];

    protected $fillable = [
        'track_id',
        'competition_id',
        'name',
        'experience',
        'brief',
        'profession',
        'email',
        'phone',
        'password',
        'password_reset_code',
        'password_reset_code_expires_at',
        'otp_code',
        'last_login_at',
        'image',
        'linkedin',
        'facebook',
        'instagram',
        'is_visible',
        'status',
        'approved_at',
        'rejected_at',
        'approved_by',
        'rejection_reason',
        'is_archived',
        'archived_at'
    ];

    protected $rememberTokenName = null; // Mentors don't use remember tokens

    protected $casts = [
        'name' => 'array',
        'experience' => 'array',
        'brief' => 'array',
        'profession' => 'array',
        'last_login_at' => 'datetime',
        'password_reset_code_expires_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'otp_code',
        'password_reset_code',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($mentor) {
            // Only set competition_id if not already provided
            if (!$mentor->competition_id) {
                $mentor->competition_id = currentCompetitionId();
            }
        });

        static::created(function ($mentor) {
            // Attach mentor to current competition using many-to-many relationship
            $currentCompetitionId = currentCompetitionId();
            if ($currentCompetitionId && $mentor->competitions()->count() === 0) {
                $mentor->competitions()->attach($currentCompetitionId);
            }
        });
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function competitions(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class, 'mentor_competitions')->withTimestamps();
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class, 'track_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Override byCompetition scope to use many-to-many relationship
     */
    public function scopeByCompetition($query)
    {
        $competitionId = currentCompetitionId();
        
        if (!$competitionId) {
            return $query;
        }
        
        return $query->whereHas('competitions', function ($q) use ($competitionId) {
            $q->where('competition_id', $competitionId);
        });
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(MentorAvailability::class);
    }

    public function videoTools(): HasMany
    {
        return $this->hasMany(MentorVideoTool::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MentorSession::class);
    }

    /**
     * Get teams assigned to this mentor
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'mentor_team')
            ->withPivot(['assigned_by', 'assigned_at', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get individual participants assigned to this mentor (non-team participants)
     */
    /**
     * Get individual participants assigned to this mentor (non-team participants)
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class, 'mentor_participant')
            ->withPivot(['assigned_by', 'assigned_at', 'notes', 'competition_id'])
            ->withTimestamps();
    }    

    /**
     * Get the default video tool for this mentor.
     */
    public function defaultVideoTool(): HasOne
    {
        return $this->hasOne(MentorVideoTool::class, 'mentor_id', 'id')
            ->where('is_default', true)
            ->where('is_active', true);
    }

    /**
     * Get active video tools for this mentor.
     */
    public function activeVideoTools(): HasMany
    {
        return $this->hasMany(MentorVideoTool::class)->where('is_active', true);
    }

    // public function setPasswordAttribute($value): void
    // {
    //     $this->attributes['password'] = bcrypt($value);
    // }

    public static function form(): array
    {
        return [
            

            Forms\Components\TextInput::make('name.en')
                ->label('Name')
                ->required(),
            
            Forms\Components\TextInput::make('name.ar')
                ->label('الاسم')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->required(),
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->required()
                ->email()
                ->unique(
                    table: Mentor::class,
                    column: 'email',
                    ignoreRecord: true
                ),
            Forms\Components\TextInput::make('phone')
                ->label('Phone')
                ->required()
                ->numeric()
                ->unique(
                    table: Mentor::class,
                    column: 'phone',
                    ignoreRecord: true
                ),


            Forms\Components\TextInput::make('experience.en')
                ->label('Experience')
                ->required(),

            Forms\Components\TextInput::make('experience.ar')
                ->label('الخبرة المهنية')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->required(),

            Forms\Components\TextInput::make('profession.en')
                ->label('Profession')
                ->required(),

            Forms\Components\TextInput::make('profession.ar')
                ->label('المهنة')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->required(),

            Forms\Components\TextInput::make('brief.en')
                ->label('Brief')
                ->required(),

            Forms\Components\TextInput::make('brief.ar')
                ->label('الوصف')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->required(),

            Forms\Components\Select::make('track_id')
                ->label('Track')
                ->options(function ($get) {
                    $competitionIds = $get('competitions') ?? [currentCompetitionId()];
                    return \App\Models\Track::whereIn('competition_id', (array) $competitionIds)->pluck('name', 'id');
                }),
            Forms\Components\TextInput::make('linkedin')
                ->label('LinkedIn')
                ->url(),
            Forms\Components\TextInput::make('facebook')
                ->label('Facebook')
                ->url(),
            Forms\Components\TextInput::make('instagram')
                ->label('Instagram')
                ->url(),

            Forms\Components\FileUpload::make('image')
                ->required()
                ->image()
                ->directory('mentors')
                ->visibility('public')
                ->label('Image'),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->required()
                ->default('approved'),
            
            Forms\Components\Select::make('competitions')
                ->label('Programs')
                ->relationship('competitions', 'title')
                ->options(function () {
                    return \App\Models\Competition::query()
                        ->where('is_archived', false)
                        ->whereNotNull('title')
                        ->get()
                        ->mapWithKeys(function ($competition) {
                            $title = is_array($competition->title) 
                                ? ($competition->title['en'] ?? $competition->title['ar'] ?? 'Unknown')
                                : $competition->title;
                            return [$competition->id => $title];
                        });
                })
                ->multiple()
                ->searchable()
                ->preload()
                ->placeholder('Select programs...')
                ->default(function ($get, $set, $state) {
                    if (!empty($state)) {
                        // If a value is already selected (editing), don't override it
                        return $state;
                    }
                    // Use currentCompetitionId() helper if available
                    if (function_exists('currentCompetitionId')) {
                        $current = currentCompetitionId();
                        if ($current) {
                            return [$current];
                        }
                    }
                    return [];
                })
                ->helperText('Select one or more programs to assign this mentor to.'),
            
        ];
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('competitions')
                ->label('Programs')
                ->getStateUsing(function ($record) {
                    $competitions = $record->competitions ?? [];
                    // If competitions is a relation, turn into a collection; 
                    // if not, fallback to empty array
                    if (is_object($competitions) && method_exists($competitions, 'pluck')) {
                        $competitions = $competitions->all();
                    }
                    return collect($competitions)->pluck('title')->map(function ($title) {
                        if (is_array($title)) {
                            return $title['en'] ?? $title['ar'] ?? 'Unknown';
                        }
                        return $title;
                    })->join(', ');
                })
                ->limit(40),
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('phone')->searchable(),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                }),
            Tables\Columns\TextColumn::make('approved_at')
                ->label('Approved At')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('approvedBy.name')
                ->label('Approved By')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\IconColumn::make('is_visible')
                ->label('Active')
                ->boolean()
                ->trueIcon('heroicon-o-eye')
                ->falseIcon('heroicon-o-eye-slash')
                ->trueColor('success')
                ->falseColor('gray')
                ->alignCenter()
                ->action(
                    Tables\Actions\Action::make('toggleVisibility')
                        ->requiresConfirmation()
                        ->modalHeading(fn(Model $record) => $record->is_visible ? 'Hide this record?' : 'Show this record again?')
                        ->modalDescription(fn(Model $record) => $record->is_visible
                            ? 'Hidden records are no longer visible to participants.'
                            : 'This record will become visible to all participants.')
                        ->modalSubmitActionLabel(fn(Model $record) => $record->is_visible ? 'Yes, hide it' : 'Yes, show it')
                        ->modalCancelActionLabel('Cancel')
                        ->color(fn(Model $record) => $record->is_visible ? 'danger' : 'success')
                        ->action(fn(Model $record) => $record->update(['is_visible' => !$record->is_visible]))
                        ->visible(fn () => auth()->user()?->can('update Mentor')),
                ),
            Tables\Columns\TextColumn::make('created_at')
                ->searchable()
                ->sortable(),
        ];
    }

    public static function details(): array
    {
        return [
            Section::make('Mentor Details / تفاصيل المرشد')
                ->columns(2)
                ->schema([
                    ImageEntry::make('image')->circular()->columnSpanFull(),

                    // Name - English and Arabic
                    TextEntry::make('name')
                        ->label('Name')
                        ->getStateUsing(fn($record) => $record->getTranslation('name', 'en')),
                    TextEntry::make('name')
                        ->label('الاسم')
                        ->getStateUsing(fn($record) => $record->getTranslation('name', 'ar')),
                
                    // Email and phone
                    TextEntry::make('email')->label('Email / البريد الإلكتروني'),
                    TextEntry::make('phone')->label('Phone / الهاتف'),

                    // Experience - English and Arabic
                    TextEntry::make('experience')
                        ->label('Experience')
                        ->getStateUsing(fn($record) => $record->getTranslation('experience', 'en')),
                    TextEntry::make('experience')
                        ->label('الخبرة المهنية')
                        ->getStateUsing(fn($record) => $record->getTranslation('experience', 'ar'))
                        ,
                    
                    // Profession - English and Arabic
                    TextEntry::make('profession')
                        ->label('Profession')
                        ->getStateUsing(fn($record) => $record->getTranslation('profession', 'en')),
                    TextEntry::make('profession')
                        ->label('المهنة')
                        ->getStateUsing(fn($record) => $record->getTranslation('profession', 'ar'))
                        ,

                    // Brief - English and Arabic
                    TextEntry::make('brief')
                        ->label('Brief')
                        ->getStateUsing(fn($record) => $record->getTranslation('brief', 'en')),
                    TextEntry::make('brief')
                        ->label('الوصف')
                        ->getStateUsing(fn($record) => $record->getTranslation('brief', 'ar'))
                        ,    
                    TextEntry::make('track.name')
                        ->label('Track')
                        ->getStateUsing(fn($record) => $record->track?->name ?? '-'),
                    TextEntry::make('linkedin')->label('LinkedIn'),
                    TextEntry::make('facebook')->label('Facebook'),
                    TextEntry::make('instagram')->label('Instagram'),
                ]),
            
            Section::make('Assigned Programs / البرامج المعينة')
                ->description('Programs this mentor is assigned to / البرامج المخصصة لهذا المرشد')
                ->schema([
                    RepeatableEntry::make('competitions')
                        ->label('Programs / البرامج')
                        ->schema([
                            TextEntry::make('title')
                                ->label('Program Name / اسم البرنامج')
                                ->getStateUsing(function ($record) {
                                    if (is_array($record->title)) {
                                        $en = $record->title['en'] ?? '';
                                        $ar = $record->title['ar'] ?? '';
                                        if ($en && $ar) {
                                            return "EN: {$en} | AR: {$ar}";
                                        } elseif ($en) {
                                            return "EN: {$en}";
                                        } elseif ($ar) {
                                            return "AR: {$ar}";
                                        }
                                        return 'Unknown';
                                    }
                                    return $record->title;
                                }),
                        ])
                        ->columns(1),
                ])
                ->collapsible(),
            
            Section::make('Assigned Teams / الفرق المعينة')
                ->description('Teams assigned to this mentor / الفرق المخصصة لهذا المرشد')
                ->schema([
                    RepeatableEntry::make('teams')
                        ->label('Teams / الفرق')
                        ->schema([
                            TextEntry::make('name')
                                ->label('Team Name / اسم الفريق'),
                            TextEntry::make('pivot.assigned_at')
                                ->label('Assigned At / تاريخ التعيين')
                                ->dateTime('M d, Y H:i'),
                            TextEntry::make('application.competition.title')
                                ->label('Program / البرنامج')
                                ->getStateUsing(function ($record) {
                                    $title = $record->application?->competition?->title;
                                    if (is_array($title)) {
                                        return $title['en'] ?? $title['ar'] ?? 'Unknown';
                                    }
                                    return $title ?? 'Unknown';
                                }),
                        ])
                        ->columns(3),
                ])
                ->collapsible()
                ->collapsed(),

            Section::make('Assigned Individual Participants / المشاركين الفرديين المعينين')
                ->description('Individual participants (without teams) assigned to this mentor / المشاركون الفرديون (بدون فرق) المعينون لهذا المرشد')
                ->schema([
                    RepeatableEntry::make('participants')
                        ->label('Individual Participants / المشاركين الفرديين')
                        ->schema([
                            TextEntry::make('name')
                                ->label('Participant Name / اسم المشارك')
                                ->getStateUsing(function ($record) {
                                    $name = $record->name;
                                    if (is_array($name)) {
                                        $en = $name['en'] ?? '';
                                        $ar = $name['ar'] ?? '';
                                        if ($en && $ar) {
                                            return "{$en} / {$ar}";
                                        }
                                        return $en ?: $ar ?: 'Unknown';
                                    }
                                    return $name ?? 'Unknown';
                                }),
                            TextEntry::make('email')
                                ->label('Email / البريد الإلكتروني'),
                            TextEntry::make('pivot.assigned_at')
                                ->label('Assigned At / تاريخ التعيين')
                                ->dateTime('M d, Y H:i'),
                            // TextEntry::make('pivot.notes')
                            //     ->label('Notes / ملاحظات')
                            //     ->placeholder('No notes / لا توجد ملاحظات')
                            //     ->columnSpanFull(),
                        ])
                        ->columns(3),
                ])
                ->collapsible()
                ->collapsed(),
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
