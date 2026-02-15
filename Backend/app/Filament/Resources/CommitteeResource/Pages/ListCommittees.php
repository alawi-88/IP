<?php

namespace App\Filament\Resources\CommitteeResource\Pages;

use App\Filament\Exports\CommitteeExporter;
use App\Filament\Resources\CommitteeResource;
use App\Models\Committee;
use App\Models\CompetitionJudge;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Table;
use Filament\Tables;

class ListCommittees extends ListRecords
{
    protected static string $resource = CommitteeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Committee::byCompetition())
            ->columns(Committee::columns())
            ->filters([
                Tables\Filters\SelectFilter::make('judges')
                    ->options(CompetitionJudge::query()
                        ->select('id', 'judge_id')
                        ->where('competition_id', 1)
                        ->get()
                        ->pluck('judge.name', 'id')
                        ->toArray())
                    ->relationship('judges', 'name')
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('delete Committee')),
                ]),

                ExportBulkAction::make()
                    ->exporter(CommitteeExporter::class)
                    ->columnMapping(false)
                    ->fileName('Committees_List_' . now()->format('Y-m-d')),
            ]);
    }

}
