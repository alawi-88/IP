<?php

namespace App\Filament\Resources\StageResource\Pages;

use App\Filament\Resources\StageResource;
use App\Models\Stage;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;

class ListStages extends ListRecords
{
    protected static string $resource = StageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Stage::byCompetition())
            ->columns(Stage::columns())
            ->defaultSort('starts_at', 'desc')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Stage')
                    ->modalDescription(function ($record) {
                        $formIds = $record->getFormIds();
                        if ($record->slug === 'team-formation') {
                            return 'This stage cannot be deleted because it is a team-formation stage.';
                        }
                        if (!empty($formIds)) {
                            return 'This stage cannot be deleted because it is linked to one or more forms.';
                        }
                        return 'Are you sure you want to delete this stage? This action cannot be undone.';
                    })
                    ->disabled(fn ($record) => $record->slug === 'team-formation' || !empty($record->getFormIds()))
                    ->tooltip(function ($record) {
                        if ($record->slug === 'team-formation') {
                            return 'Cannot delete team-formation stage';
                        }
                        $formIds = $record->getFormIds();
                        if (!empty($formIds)) {
                            return 'Cannot delete stage linked to forms';
                        }
                        return 'Delete stage';
                    }),
            ])->defaultSort('created_at', 'desc');
    }
}
