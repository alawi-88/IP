<?php

namespace App\Models;

use App\Models\Scopes\ProgramApplicationScope;
use App\Traits\Program\FilterByProgram;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Tables;

/**
 * @method static create(mixed $validated)
 * @method static byProgram()
 */
#[ScopedBy([ProgramApplicationScope::class])]
class Satisfaction extends Model
{
    use FilterByProgram;

    protected $fillable = [
        'program_id',
        'participant_id',
        'question',
        'answer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($satisfaction) {
            $applicationId = request('application_id');
            $application = ProgramApplication::where('id', $applicationId)->firstOrFail();

            $satisfaction->program_id = $application->program_id;
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
