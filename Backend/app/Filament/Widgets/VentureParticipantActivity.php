<?php

namespace App\Filament\Widgets;

use App\Enums\VentureStatus;
use App\Models\Venture;
use App\Models\Participant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class VentureParticipantActivity extends BaseWidget
{
    protected static ?string $heading = 'Participant Venture Activity';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Participant::query()
                    ->whereHas('ventures')
                    ->withCount([
                        'ventures',
                        'ventures as completed_ventures_count' => function (Builder $query) {
                            $query->where('status', VentureStatus::Completed);
                        },
                        'ventures as partial_ventures_count' => function (Builder $query) {
                            $query->where('status', VentureStatus::PartiallyCompleted);
                        },
                        'ventures as failed_ventures_count' => function (Builder $query) {
                            $query->where('status', VentureStatus::Failed);
                        },
                    ])
                    ->withAvg('ventures', 'viability_score')
                    ->withSum('ventures', 'total_tokens_used')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Participant')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ventures_count')
                    ->label('Total')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('completed_ventures_count')
                    ->label('Completed')
                    ->sortable()
                    ->alignCenter()
                    ->color('success'),

                Tables\Columns\TextColumn::make('partial_ventures_count')
                    ->label('Partial')
                    ->sortable()
                    ->alignCenter()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('failed_ventures_count')
                    ->label('Failed')
                    ->sortable()
                    ->alignCenter()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('ventures_avg_viability_score')
                    ->label('Avg Score')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) : 'N/A')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('ventures_sum_total_tokens_used')
                    ->label('Total Tokens')
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0))
                    ->sortable()
                    ->alignCenter(),
            ])
            ->defaultSort('ventures_count', 'desc')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
