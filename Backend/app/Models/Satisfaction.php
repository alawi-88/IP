<?php

namespace App\Models;

use App\Models\Scopes\CompetitionApplicationScope;
use App\Traits\Competition\FilterByCompetition;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Tables;

/**
 * @method static create(mixed $validated)
 * @method static byCompetition()
 */
#[ScopedBy([CompetitionApplicationScope::class])]
class Satisfaction extends Model
{
    use FilterByCompetition;

    protected $fillable = [
        'competition_id',
        'participant_id',
        'question',
        'answer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($satisfaction) {
            $applicationId = request('application_id');
            $application = CompetitionApplication::where('id', $applicationId)->firstOrFail();

            $satisfaction->competition_id = $application->competition_id;
            $satisfaction->participant_id = auth()->id();
        });
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('participant.name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('created_at')->searchable()->sortable()
        ];
    }
}
