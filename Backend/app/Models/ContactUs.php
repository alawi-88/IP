<?php

namespace App\Models;

use App\Traits\Program\FilterByProgram;
use App\Traits\HasActivityLog;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Tables;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method static byProgram()
 */
class ContactUs extends Model
{
    use LogsActivity, HasActivityLog, FilterByProgram;

    // pending and resolved constants
    const PENDING = 'pending';
    const RESOLVED = 'resolved';

    protected $fillable = [
        'title',
        'message',
        'attachments',
        'reply',
        'replied_at',
        'status',
        'replied_by',
        'model_id',
        'model_type',
        'is_archived',
        'archived_at'
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected array $logFields = [
        'title',
        'message',
        'attachments',
        'reply',
        'replied_at',
        'status',
        'replied_by',
        'is_archived',
        'archived_at',
    ];

    protected string $moduleName = 'Contact Us';
    protected string $logName = 'contact_us';

    /**
     * Activity logging configuration
     * 
     * This model now logs all changes including archiving and restoration
     * activities. The activity log will record:
     * - Who performed the action (user)
     * - What action was performed (created, updated, archived, restored)
     * - When the action was performed (timestamp)
     * - What fields were changed (dirty fields only)
     */

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($contactUs) {

            if ($contactUs->model_id && $contactUs->model_type) {
                return;
            }

            if (auth()->check()) {
                $contactUs->model_id   = auth()->id();
                $contactUs->model_type = auth()->user()->getMorphClass();

                return;
            }
        });
    }

    public function model()
    {
        return $this->morphTo();
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')->label('Submission ID')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('name')
                ->label('Name')
                ->getStateUsing(fn($record) => $record->model_type ? ($record->model?->name ?? '—') : '—'),
            Tables\Columns\TextColumn::make('email')
                ->label('Email')
                ->getStateUsing(fn($record) => $record->model_type ? ($record->model?->email ?? '—') : '—'),
            Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('message')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->searchable()
                ->sortable()
                ->color(fn($state) => $state === self::PENDING ? 'warning' : ($state === self::RESOLVED ? 'success' : 'default'))
                ->formatStateUsing(fn($state) => ucfirst($state)),
            Tables\Columns\TextColumn::make('replier.name')->label('Replied By')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('created_at')->label('Submission Date')->searchable()->sortable(),
        ];
    }

    public static function details(): array
    {
        return [
            Section::make()
                ->columns()
                ->schema(
                    [
                        // name
                        TextEntry::make('name')
                            ->label('Name')
                            ->getStateUsing(fn($record) => $record->model_type ? ($record->model?->name ?? '—') : '—'),

                        // email
                        TextEntry::make('email')
                            ->label('Email')
                            ->getStateUsing(fn($record) => $record->model_type ? ($record->model?->email ?? '—') : '—'),

                        TextEntry::make('title'),
                        TextEntry::make('message'),
                        TextEntry::make('documents')
                            ->getStateUsing(fn($record) => collect($record->attachments)
                                ->map(fn($attachment) => "<a href='" . Storage::url($attachment) . "'>" . $attachment . "</a>")->join(' '))
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('created_at')->date()
                    ]
                )
        ];
    }

    public function replier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    // is replied?
    public function isReplied(): bool
    {
        return $this->reply != null && $this->status === self::RESOLVED;
    }

    // Archive functionality
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }
}

