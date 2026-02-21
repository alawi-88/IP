<?php

namespace App\Models;

use App\Models\Scopes\ProgramApplicationScope;
use App\Traits\Program\FilterByProgram;
use App\Traits\HasActivityLog;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\ViewColumn;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;
use Filament\Forms;
use Filament\Tables;

/**
 * @method static byProgram()
 */
#[ScopedBy([ProgramApplicationScope::class])]
class Guideline extends Model
{
    use HasTranslations, FilterByProgram, LogsActivity, HasActivityLog;

    protected array $logFields = [
        'title',
        'is_visible',
        'is_archived',
        'archived_at',
        'program.title',
        'program_id'
    ];

    protected string $moduleName = 'Guideline';
    protected string $logName = 'guideline';

    public array $translatable = [
        'title',
    ];

    protected $fillable = ['program_id', 'title', 'is_visible', 'is_archived', 'archived_at'];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected $with = ['files'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($mentor) {
            $mentor->program_id = currentProgramId();
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(GuidelineFile::class);
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
                ->placeholder('العنوان'),



            Forms\Components\Repeater::make('files')
                ->schema(GuidelineFile::form())
                ->hiddenOn('edit')
                ->maxItems(4)
                ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                    // Handle the three attachment types and merge them into a single attachment field
                    $data['attachment'] = $data['attachment_video']
                        ?? $data['attachment_document']
                        ?? $data['attachment_image']
                        ?? null;

                    // Set file_type based on which attachment field has data
                    if (!empty($data['attachment_video'])) {
                        $data['file_type'] = 'video';
                    } elseif (!empty($data['attachment_document'])) {
                        $data['file_type'] = 'document';
                    } elseif (!empty($data['attachment_image'])) {
                        $data['file_type'] = 'image';
                    }

                    // Clean up the separate attachment fields
                    unset($data['attachment_video'], $data['attachment_document'], $data['attachment_image']);

                    return $data;
                })
        ];
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('program.title')
                ->label('Program'),

            Tables\Columns\TextColumn::make('title')
                ->label('Title')
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
                        ->visible(fn () => auth()->user()?->can('update Guideline'))
                ),
            Tables\Columns\TextColumn::make('created_at')
                ->searchable()
                ->sortable()
        ];
    }

    public static function details(): array
    {
        return [
            Section::make('Guideline Details')
                ->columns(3)
                ->schema([
                    TextEntry::make('program.title')
                        ->label('Program'),

                    TextEntry::make('title')
                        ->label('العنوان')
                        ->getStateUsing(fn($record) => $record->getTranslation('title', 'ar')),

                    TextEntry::make('title')
                        ->label('Title')
                        ->getStateUsing(fn($record) => $record->getTranslation('title', 'en')),
                ]),
        ];
    }
}
