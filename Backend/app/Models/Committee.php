<?php

namespace App\Models;

use App\Traits\Competition\FilterByCompetition;
use App\Traits\HasActivityLog;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Filament\Forms;
use Filament\Tables;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method static byCompetition()
 */
class Committee extends Model
{
    use FilterByCompetition, LogsActivity, HasActivityLog;

    protected $fillable = [
        'competition_id',
        'title',
    ];

    protected array $logFields = [
        'title',
        'competition.title',
        'competition_id'
    ];

    protected string $moduleName = 'Committee';
    protected string $logName = 'committee';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($committee) {
            $committee->competition_id = currentCompetitionId() ?? Competition::first()->id;
        });

        static::deleting(function ($committee) {
            JudgeProject::whereIn('judge_id', $committee->judges->pluck('id')->toArray())->delete();
            $committee->judges()->detach();
        });
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function judges(): BelongsToMany
    {
        return $this->belongsToMany(Judge::class, 'committee_judges')->withTimestamps();
    }

    public static function form(): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->label('Title')
                ->required()
                ->columnSpanFull(),

            Forms\Components\Select::make('judge_id')
                ->label('Judge')
                ->preload()
                ->maxItems(5)
                ->options(CompetitionJudge::query()
                    ->select('id', 'judge_id')
                    ->where('competition_id', 1)
                    ->get()
                    ->pluck('judge.name', 'id')
                    ->toArray()
                )
                ->relationship('judges', 'name')
                ->required()
                ->multiple()
                ->columnSpanFull()
        ];
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('title')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('judge_id')
                ->label('Judges')
                ->getStateUsing(function ($record) {
                    return $record->judges->pluck('name')->join(', ');
                })
        ];
    }

    public static function details(): array
    {
        return [
            Section::make('Committee Details')
                ->columns(3)
                ->schema([
                    TextEntry::make('title'),
                    TextEntry::make('Judges')
                        ->getStateUsing(function ($record) {
                            return $record->judges->pluck('name')->join(', ');
                        }),
                ]),
        ];
    }
}
