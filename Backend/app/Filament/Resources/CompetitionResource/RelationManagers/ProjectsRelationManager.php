<?php

namespace App\Filament\Resources\CompetitionResource\RelationManagers;

use App\Models\Project;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';

    protected static ?string $icon = 'heroicon-o-computer-desktop';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns(Project::columns())
            ->actions([
                Tables\Actions\Action::make('update status')
                    ->hiddenLabel()
                    ->color('primary')
                    ->icon('heroicon-o-pencil')
                    ->form(Project::updateStatusForm())
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->isPending())
                    ->action(function (array $data, Project $record) {
                        $record->setStatusAs($data['status']);
                    }),

                Tables\Actions\Action::make('assign to judges')
                    ->hiddenLabel()
                    ->color('primary')
                    ->icon('heroicon-o-user')
                    ->form(fn (Project $record) => $record->assignToJudgesForm($record))
                    ->requiresConfirmation()
                    ->action(function (array $data, Project $record) {
                        $record->assignToJudges($data['judges']);
                    }),


                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
