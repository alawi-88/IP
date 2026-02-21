<?php

namespace App\Models;

use App\Models\Scopes\ProgramApplicationScope;
use App\Traits\HasActivityLog;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Tables;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method static withoutGlobalScopes()
 * @method static byProgram()
 * @method static published()
 */
#[ScopedBy([ProgramApplicationScope::class])]
class Team extends Model
{
    use LogsActivity, HasActivityLog;

    protected $fillable = [
        'application_id',
        'name',
        'logo',
        'strength',
        'track_id',
        'sub_track_id',
        'idea_description',
        'previous_participation',
        'contact_email',
        'skills',
        'is_published',
        'is_completed',
        'is_archived',
        'archived_at',
    ];

    protected $casts = [
        'previous_participation' => 'boolean',
        'is_published' => 'boolean',
        'skills' => 'array',
        'is_completed' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected array $logFields = [
        'name',
        'logo',
        'strength',
        'track_id',
        'sub_track_id',
        'idea_description',
        'previous_participation',
        'contact_email',
        'skills',
        'is_published',
        'is_completed',
        'is_archived',
        'archived_at',
        'track.title',
        'subTrack.title',
        'application.program.title',
        'application.program_id',
    ];

    protected string $moduleName = 'Team';
    protected string $logName = 'team';

    public function scopeByProgram($query)
    {
        $applicationsIds = ProgramApplication::byProgram()->pluck('id');
        return $query->whereIn('application_id', $applicationsIds);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    public function archive(): void
    {
        $result = $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);

    }

    public function restore(): void
    {
        $result = $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);

    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ProgramApplication::class);
    }

    public function members(): HasMany
    {
        return $this->HasMany(TeamMember::class);
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class, 'track_id');
    }

    public function subTrack(): BelongsTo
    {
        return $this->belongsTo(SubTrack::class, 'sub_track_id');
    }

    public function project()
    {
        return $this->hasOne(Project::class, 'team_id');
    }

    /**
     * Get all projects for this team
     */
    public function projects()
    {
        return $this->hasMany(Project::class, 'team_id');
    }

    /**
     * Get mentors assigned to this team
     */
    public function mentors(): BelongsToMany
    {
        return $this->belongsToMany(Mentor::class, 'mentor_team')
            ->withPivot(['assigned_by', 'assigned_at', 'notes'])
            ->withTimestamps();
    }


    public function isCompleted(): bool
    {
        return $this->members()->count() == config('team.max_members');
    }

    public function isParticipantLeader(): bool
    {
        return $this->members()
            ->where('participant_id', auth()->id())
            ->where('is_leader', true)->exists();
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('team leader')
                ->getStateUsing(fn($record) => $record->members()->where('is_leader', true)->first()?->participant?->name)
                ->searchable(true, fn($query, $search) => $query->whereHas('members', fn($query) => $query->where('is_leader', true)->whereHas('participant', fn($query) => $query->where('name', 'like', "%$search%"))))
                ->sortable(true, fn($query, $direction) => $query->whereHas('members', fn($query) => $query->where('is_leader', true))->orderBy('name', $direction)),

            Tables\Columns\TextColumn::make('track.name')
                ->label('Track')
                ->searchable(true, fn($query, $search) => $query->whereHas('track', fn($query) => $query->where('name->en', 'like', "%$search%")))
                ->sortable(),
            Tables\Columns\TextColumn::make('subTrack.name')
                ->searchable(true, fn($query, $search) => $query->whereHas('subTrack', fn($query) => $query->where('name->en', 'like', "%$search%")))
                ->sortable(),

            Tables\Columns\ToggleColumn::make('is_published')
                ->disabled(fn () => ! auth()->user()->can('update Team')),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Submitted At')
                ->sortable(),
        ];
    }

    public static function details(): array
    {
        return [
            Section::make('Team Information')
                ->columns()
                ->schema([
                    TextEntry::make('name')
                        ->label('Team Name'),
                    IconEntry::make('previous_participation')
                        ->boolean()
                        ->falseIcon('heroicon-o-x-circle'),
                    ViewEntry::make('logo')
                        ->label('Logo')
                        ->view('filament.custom-entries.file-preview')
                        ->viewData(fn($record) => [
                            'url' => $record->logo ? static::normalizeStorageUrl($record->logo) : null,
                            'filename' => $record->logo ? basename($record->logo) : '',
                            'isImage' => $record->logo ? preg_match('/\.(jpg|jpeg|png|webp)$/i', $record->logo) : false,
                            'label' => 'Logo',
                        ])
                        ->columnSpanFull(),
                    TextEntry::make('skills')->label('Requested Skills'),
                    TextEntry::make('contact_email')->label('Email to Contact'),
                ]),

            Section::make('Team Members')
                ->schema([
                    RepeatableEntry::make('members')
                        ->hiddenLabel()
                        ->columns(3)
                        ->schema([
                            TextEntry::make('participant.name')->label('Name'),
                            TextEntry::make('participant.email')->label('Email'),
                            IconEntry::make('is_leader')->boolean()->label('Is Team Leader?'),
                        ]),

                ]),

            Section::make('Idea Information')
                ->columns()
                ->schema([
                    TextEntry::make('track.name')
                        ->label('Track')->default('N/A'),
                    TextEntry::make('subTrack.name')
                        ->label('SubTrack')->default('N/A'),
                    TextEntry::make('idea_description')
                        ->label('Idea Description'),
                ])
                ->columns(),
        ];
    }

    /**
     * Normalize storage URL - handles both relative paths and full URLs
     * Prevents double URL prefix issues
     */
    public static function normalizeStorageUrl($path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // If it's already a full URL, return as is
        if (preg_match('#^https?://#', $path)) {
            return $path;
        }

        // Remove any leading /storage/ prefix if present
        $path = preg_replace('#^/?storage/#', '', $path);

        // Remove any leading slash
        $path = ltrim($path, '/');

        // Generate storage URL for relative path
        return Storage::url($path);
    }
}
