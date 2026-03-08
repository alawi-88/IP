<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Tables;

/**
 * @method static updateOrCreate(array $array, array $tabs)
 */
class CompetitionTab extends Model
{
    protected $fillable = [
        'competition_id',
        'tab',
        'label_en',
        'label_ar',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('tab')
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'my-team' => 'your team',
                    'projects' => 'project',
                    'leaderboard' => 'leaderboard',
                    default => $state,
                })
                ->extraAttributes(['class' => 'capitalize'])
                ->sortable(),

            Tables\Columns\TextInputColumn::make('label_en')
                ->label('Label (EN)')
                ->placeholder('Default')
                ->rules(['nullable', 'string', 'max:50']),

            Tables\Columns\TextInputColumn::make('label_ar')
                ->label('Label (AR)')
                ->placeholder('افتراضي')
                ->rules(['nullable', 'string', 'max:50']),

            Tables\Columns\ToggleColumn::make('is_visible'),
        ];
    }
}
