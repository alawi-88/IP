<?php

namespace App\Filament\Resources\CompetitionResource\RelationManagers;

use App\Filament\Traits\ManageableRelation;
use App\Models\Stage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StagesRelationManager extends RelationManager
{
    use ManageableRelation;

    protected static string $relationship = 'stages';

    protected static ?string $title = 'Program Stages';

    public function form(Form $form): Form
    {
        return $form->schema(Stage::form());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns(Stage::columns())
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Stage')
                    ->modalDescription(function ($record) {
                        if ($record->slug === 'team-formation') {
                            return 'This stage cannot be deleted because it is a team-formation stage.';
                        }
                        $formIds = $record->getFormIds();
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
            ])
            ->bulkActions([
            ]);
    }

    protected function canCreate(): bool
    {
        return $this->getOwnerRecord()->stages()->count() < 7;
    }
}
