<?php

namespace App\Filament\Widgets;

use App\Enums\VentureStatus;
use App\Models\Venture;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class VentureTopPerformers extends BaseWidget
{
    protected static ?string $heading = 'Top Ventures by Viability Score';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Venture::query()
                    ->where('viability_score', '>', 0)
                    ->with('participant')
                    ->orderByDesc('viability_score')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Venture')
                    ->searchable()
                    ->weight('bold')
                    ->limit(50),

                Tables\Columns\TextColumn::make('participant.name')
                    ->label('Participant')
                    ->searchable(),

                Tables\Columns\TextColumn::make('viability_score')
                    ->label('Score')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '/100' : 'N/A')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 80 => 'success',
                        $state >= 60 => 'info',
                        $state >= 40 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (VentureStatus $state) => match ($state) {
                        VentureStatus::Completed => 'success',
                        VentureStatus::PartiallyCompleted => 'warning',
                        VentureStatus::Generating => 'info',
                        VentureStatus::Failed => 'danger',
                    }),

                Tables\Columns\TextColumn::make('total_tokens_used')
                    ->label('Tokens')
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('sections_count')
                    ->label('Sections')
                    ->counts('sections')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->defaultSort('viability_score', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Venture $record) => route('filament.admin.resources.ventures.view', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
