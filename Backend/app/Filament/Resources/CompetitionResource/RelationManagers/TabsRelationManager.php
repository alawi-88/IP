<?php

namespace App\Filament\Resources\CompetitionResource\RelationManagers;

use App\Models\CompetitionTab;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TabsRelationManager extends RelationManager
{
    protected static string $relationship = 'tabs';

    protected static ?string $title = 'Participation Hub';

    protected static ?string $icon = 'heroicon-o-queue-list';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns(CompetitionTab::columns())
            ->defaultSort(fn(Builder $query) => $query->orderByRaw("FIELD(tab, 'events', 'mentors', 'my-team', 'teams', 'projects', 'guidelines', 'leaderboard')"))
            ->headerActions([]);

    }
}
