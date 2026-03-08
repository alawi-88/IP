<?php

namespace App\Filament\Resources\SatisfactionResource\Pages;

use App\Filament\Exports\SatisfactionExporter;
use App\Filament\Resources\SatisfactionResource;
use App\Models\Satisfaction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Table;
use Filament\Tables;

class ListSatisfactions extends ListRecords
{
    protected static string $resource = SatisfactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Satisfaction::byProgram()->groupBy('participant_id'))
            ->recordUrl(null)
            ->columns(Satisfaction::columns())
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modal()
                    ->modalHeading(fn($record) => 'Satisfaction from [' . ($record?->participant->name) . ']')
                    ->modalContent(fn($record) => view('filament.modals.satisfactions', [
                        'satisfactions' => Satisfaction::byProgram()
                            ->where('participant_id', $record->participant_id)->get(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),

                Tables\Actions\DeleteAction::make()
                    ->action(fn($record) => Satisfaction::byProgram()->where('participant_id', $record->participant_id)->delete())
                    ,
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('participant_id')
                    ->label('Participant')
                    ->placeholder('Select Participant')
                    ->options(fn() => Satisfaction::byProgram()->groupBy('participant_id')->get()->mapWithKeys(fn($satisfaction) => [$satisfaction->participant_id => $satisfaction->participant->name])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(fn($records) => Satisfaction::byProgram()->whereIn('participant_id', $records->pluck('participant_id'))->delete())
                        ,
                ]),

                ExportBulkAction::make()->exporter(SatisfactionExporter::class)
                    ->columnMapping(false)
                    ->fileName('Satisfactions_List_' . now()->format('Y-m-d'))
            ]);
    }
}
