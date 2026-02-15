<?php

namespace App\Models;

use App\Models\Scopes\CompetitionApplicationScope;
use App\Traits\Competition\FilterByCompetition;
use App\Traits\HasActivityLog;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;
use Filament\Forms;
use Filament\Tables;

#[ScopedBy([CompetitionApplicationScope::class])]
class Event extends Model
{
    use HasTranslations, HasFactory, FilterByCompetition, LogsActivity, HasActivityLog;

    protected array $logFields = [
        'title',
        'brief',
        'speakers',
        'badge',
        'date',
        'time',
        'location',
        'event_link',
        'is_visible',
        'is_archived',
        'archived_at',
        'competition.title',
        'competition_id'
    ];

    protected string $moduleName = 'Event';
    protected string $logName = 'event';

    public array $translatable = [
        'title',
        'brief',
    ];

    protected $fillable = [
        'competition_id',
        'title',
        'brief',
        'badge',
        'date',
        'time',
        'location',
        'speakers',
        'event_link',
        'is_visible',
        'is_archived',
        'archived_at'
    ];

    const LOCATIONS = [
        'virtual',
        'onsite',
    ];

    const BADGES = [
        'completed',
        'upcoming',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'time' => 'datetime:H:i',
        'speakers' => 'array',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($event) {
            $event->competition_id = currentCompetitionId();
            $event->date = $event->date->format('Y-m-d') . ' ' . $event->time->format('H:i:s');
            $event->badge = $event->date->isFuture() ? 'upcoming' : 'completed';

            // Provide default value for event_link if null
            if (empty($event->event_link)) {
                $event->event_link = '';
            }
        });
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function isUpcoming(): bool
    {
        $dateTime = $this->date->format('Y-m-d') . ' ' . $this->time->format('H:i:s');
        return now()->lt($dateTime);
    }

    public function isCompleted(): bool
    {
        $dateTime = $this->date->format('Y-m-d') . ' ' . $this->time->format('H:i:s');
        return now()->gt($dateTime);
    }

    // Archiving methods
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

    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public static function form(): array
    {
        return [
            Forms\Components\Section::make('Event Information')
                ->schema([
                    Forms\Components\TextInput::make('title.en')
                        ->label('Title')
                        ->required(),
            Forms\Components\TextInput::make('title.ar')
                        ->label('العنوان')
                ->required(),


            Forms\Components\Textarea::make('brief.en')
                        ->label('Brief')
                        ->required(),
                    Forms\Components\Textarea::make('brief.ar')
                        ->label('الوصف')
                ->required(),


            Forms\Components\DatePicker::make('date')
                ->label('Date')
                ->required(),

            Forms\Components\TimePicker::make('time')
                ->label('Time')
                ->required(),

            Forms\Components\Select::make('location')
                ->label('Location')
                ->options([
                    'virtual' => 'Virtual',
                    'onsite' => 'Onsite',
                ])
                ->required(),

            Forms\Components\TextInput::make('event_link')
                ->url()
                ->label('Event Link')
                        ->helperText('Optional - Add a link to the event (e.g., Zoom, Teams, etc.)')
                        ->nullable(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Speakers (Optional)')
                ->description('Add one or more speakers for this event. Leave empty if no speakers.')
                ->schema([
                    Forms\Components\Repeater::make('speakers')
                        ->label('Speakers')
                        ->schema([
                            Forms\Components\TextInput::make('name.en')
                                ->label('Speaker Name')
                                ->placeholder('Enter speaker name')
                                ->requiredWith('speakers'),
                            Forms\Components\TextInput::make('name.ar')
                                ->label('اسم المتحدث')
                                ->placeholder('أدخل اسم المتحدث')
                                ->requiredWith('speakers'),

                            Forms\Components\TextInput::make('experience.en')
                                ->label('Speaker Experience')
                                ->placeholder('e.g., Software Engineer at Google, 10 years experience')
                                ->helperText('e.g., Software Engineer at Google, 10 years experience')
                                ->nullable(),

                            Forms\Components\TextInput::make('experience.ar')
                                ->label('خبرة المتحدث')
                                ->placeholder('مثال: مهندس برمجيات في جوجل، 10 سنوات خبرة')
                                ->helperText('مثال: مهندس برمجيات في جوجل، 10 سنوات خبرة')
                                ->nullable(),

                            Forms\Components\Textarea::make('brief.en')
                                ->label('Speaker Brief')
                                ->placeholder('Short description about the speaker')
                                ->helperText('Short description about the speaker')
                                ->nullable(),
                            Forms\Components\Textarea::make('brief.ar')
                                ->label('وصف المتحدث')
                                ->placeholder('وصف مختصر عن المتحدث')
                                ->helperText('وصف مختصر عن المتحدث')
                                ->nullable(),

                            Forms\Components\FileUpload::make('photo')
                ->label('Speaker Photo')
                                ->image()
                ->directory('event_speakers')
                                ->helperText('Upload speaker photo (optional)')
                                ->nullable(),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->cloneable()
                        ->addActionLabel('Add Speaker')
                        ->itemLabel(fn (array $state): ?string => $state['name']['en'] ?? $state['name']['ar'] ?? 'Speaker')
                        ->defaultItems(0),
                ])
                ->collapsible()
                ->collapsed(false),
        ];
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('location')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('date')
                ->date('Y-m-d')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('time')
                ->time('H:i')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('badge')
                ->badge()
                ->color(function ($record) {
                    return $record->isUpcoming() ? 'success' : 'danger';
                })
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('speakers')
                ->label('Speakers')
                ->formatStateUsing(function ($state) {

                    // Case 1: No speakers
                    if (empty($state) || is_null($state)) {
                        return 'No speakers';
                    }

                    // Check for empty string or empty array
                    if (is_string($state) && (trim($state) === '' || trim($state) === '[]' || trim($state) === '{}')) {
                        return 'No speakers';
                    }

                    // Check for null or empty speakers in JSON
                    if (is_string($state) && (trim($state) === 'null' || trim($state) === '""' || trim($state) === '{}')) {
                        return 'No speakers';
                    }

                    // Handle both array and JSON string formats
                    $speakers = $state;
                    if (is_string($state)) {
                        // First try normal JSON decode
                        $speakers = json_decode($state, true);
                        $jsonError = json_last_error();

                        // If JSON decode failed, try to fix the malformed JSON
                        if ($jsonError !== JSON_ERROR_NONE) {
                            // Check if it's a malformed array (missing brackets)
                            if (strpos($state, '}, {') !== false) {
                                // Wrap the string in brackets to make it a proper JSON array
                                $fixedJson = '[' . $state . ']';
                                $speakers = json_decode($fixedJson, true);
                                $jsonError = json_last_error();
                            }

                            // If still failed, return error
                            if ($jsonError !== JSON_ERROR_NONE) {
                                return 'Invalid speakers data: ' . json_last_error_msg();
                            }
                        }
                    }

                    if (!is_array($speakers)) {
                        return 'No speakers';
                    }

                    // Check if array is empty
                    if (is_array($speakers) && count($speakers) === 0) {
                        return 'No speakers';
                    }

                    // Case 2: Single speaker object (has 'name' key directly)
                    if (isset($speakers['name'])) {
                        $name = $speakers['name']['en'] ?? $speakers['name']['ar'] ?? $speakers['name'] ?? null;
                        return $name ?: 'No speaker name';
                    }

                    // Case 3: Array of speakers (multiple speakers)
                    if (is_array($speakers) && count($speakers) > 0) {
                        $speakerNames = collect($speakers)->map(function ($speaker) {
                            if (is_array($speaker) && isset($speaker['name'])) {
                                // Try English first, then Arabic, then any available name
                                return $speaker['name']['en'] ?? $speaker['name']['ar'] ?? $speaker['name'] ?? null;
                            }
                            return null;
                        })->filter()->toArray();

                        return implode(', ', $speakerNames) ?: 'No speaker names';
                    }

                    return 'No speakers';
                })
                ->searchable()
                ->sortable(),

            Tables\Columns\IconColumn::make('is_visible')
                ->label('Visible')
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
                        ->visible(fn () => auth()->user()?->can('update Event'))
                ),

            Tables\Columns\TextColumn::make('created_at')
                ->searchable()
                ->sortable()
        ];
    }

    public static function details(): array
    {
        return [
            Section::make('Event Details')
                ->columns(3)
                ->schema([
                        TextEntry::make('title'),

                        TextEntry::make('brief'),

                        TextEntry::make('badge')
                            ->badge()
                            ->color(fn($record) => $record->badge === 'upcoming' ? 'success' : 'danger'),

                        TextEntry::make('date'),

                        TextEntry::make('time'),

                        TextEntry::make('location'),

                        TextEntry::make('event_link'),
                    ]
                ),

            Section::make('Speaker Details')
                ->schema(function ($record) {
                    $speakers = $record->speakers;

                    // Handle JSON string format
                    if (is_string($speakers)) {
                        $speakers = json_decode($speakers, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $speakers = [];
                        }
                    }

                    // Case 1: No speakers
                    if (empty($speakers) || !is_array($speakers)) {
                        return [
                            TextEntry::make('no_speakers')
                                ->label('Speakers')
                                ->getStateUsing(fn() => 'No speakers for this event')
                        ];
                    }

                    // Case 2: Single speaker object (has 'name' key directly)
                    if (isset($speakers['name'])) {
                        $speaker = $speakers;
                        return [
                            Section::make("Speaker 1")
                                ->schema([
                                    ImageEntry::make('speaker_photo')
                                        ->label('Photo')
                                        ->state($speaker['photo'] ?? null)
                                        ->circular(),
                                    TextEntry::make('speaker_name')
                                        ->label('Name')
                                        
                                        ->state($speaker['name']['en'] ?? $speaker['name']['ar'] ?? 'N/A'),
                                    TextEntry::make('speaker_experience')
                                        ->label('Experience')
                                        ->state($speaker['experience']['en'] ?? $speaker['experience']['ar'] ?? 'N/A'),
                                    TextEntry::make('speaker_brief')
                                        ->label('Brief')
                                        ->state($speaker['brief']['en'] ?? $speaker['brief']['ar'] ?? 'N/A')
                                        ->columnSpanFull(),
                                ])
                ->columns(3)
                        ];
                    }

                    // Case 3: Array of speakers (multiple speakers)
                    $speakerEntries = [];
                    foreach ($speakers as $index => $speaker) {
                        $speakerEntries[] = Section::make("Speaker " . (intval($index) + 1))
                ->schema([
                                ImageEntry::make('speaker_photo')
                                    ->label('Photo')
                                    ->state($speaker['photo'] ?? null)
                                    ->circular(),
                                TextEntry::make('speaker_name')
                                    ->label('Name')
                                    ->state(function () use ($speaker) {
                                        $en = $speaker['name']['en'] ?? 'N/A';
                                        $ar = $speaker['name']['ar'] ?? 'N/A';
                                        $name_d = $en .' - '.$ar;
                                        return "$name_d";
                                    }),
                                TextEntry::make('speaker_experience')
                                    ->label('Experience')
                                    ->state(function () use ($speaker) {
                                        $en = $speaker['experience']['en'] ?? 'N/A';
                                        $ar = $speaker['experience']['ar'] ?? 'N/A';
                                        $experience_d = $en .' - '.$ar;
                                        return "$experience_d";
                                    }),
                                TextEntry::make('speaker_brief')
                                    ->label('Brief')
                                    ->state(function () use ($speaker) {
                                        $en = $speaker['brief']['en'] ?? 'N/A';
                                        $ar = $speaker['brief']['ar'] ?? 'N/A';
                                        $brief_d = $en .' - '.$ar;
                                        return "$brief_d";
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->columns(3);
                    }
                    return $speakerEntries;
                }),
        ];
    }
}
