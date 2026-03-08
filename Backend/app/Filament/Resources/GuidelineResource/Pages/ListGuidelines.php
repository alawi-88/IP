<?php

namespace App\Filament\Resources\GuidelineResource\Pages;

use App\Filament\Exports\GuidelineExporter;
use App\Filament\Resources\GuidelineResource;
use App\Models\Guideline;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListGuidelines extends ListRecords
{
    protected static string $resource = GuidelineResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('guideline_archive.all_guidelines'))
                ->badge(Guideline::byProgram()->count()),
            
            'active' => Tab::make(__('guideline_archive.active'))
                ->badge(Guideline::byProgram()->active()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->active()),
            
            'archived' => Tab::make(__('guideline_archive.archived'))
                ->badge(Guideline::byProgram()->archived()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->archived()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->createAnother(false),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Guideline::byProgram())
            ->columns(Guideline::columns())
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Guideline $record) => !$record->isArchived()),
                Action::make('archive')
                    ->label(__('guideline_archive.archive_guideline'))
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('guideline_archive.confirm_archive'))
                    ->modalDescription(__('guideline_archive.confirm_archive_message'))
                    ->action(function (Guideline $record) {
                        if (GuidelineResource::canArchive($record)) {
                            $record->archive();
                            Notification::make()
                                ->title(__('guideline_archive.guideline_archived_successfully'))
                                ->success()
                                ->send();
                        }
                    })
                    ->visible(fn (Guideline $record) => !$record->isArchived() && GuidelineResource::canArchive($record)),
                Action::make('restore')
                    ->label(__('guideline_archive.restore_guideline'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('guideline_archive.confirm_restore'))
                    ->modalDescription(__('guideline_archive.confirm_restore_message'))
                    ->action(function (Guideline $record) {
                        if (GuidelineResource::canRestore($record)) {
                            $record->restore();
                            Notification::make()
                                ->title(__('guideline_archive.guideline_restored_successfully'))
                                ->success()
                                ->send();
                        }
                    })
                    ->visible(fn (Guideline $record) => $record->isArchived() && GuidelineResource::canRestore($record)),
                DeleteAction::make()
                    ->visible(fn (Guideline $record) => auth()->user()?->can('delete Guideline')),
            ], ActionsPosition::AfterColumns)
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('archive')
                        ->label(__('guideline_archive.archive_selected'))
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading(__('guideline_archive.confirm_archive'))
                        ->modalDescription(__('guideline_archive.confirm_archive_selected_message'))
                        ->action(function ($records) {
                            $count = 0;
                            $alreadyArchived = 0;
                            
                            foreach ($records as $record) {
                                if (GuidelineResource::canArchive($record)) {
                                    if (!$record->isArchived()) {
                                        $record->archive();
                                        $count++;
                                    } else {
                                        $alreadyArchived++;
                                    }
                                }
                            }
                            
                            if ($count > 0) {
                                Notification::make()
                                    ->title(__('guideline_archive.guidelines_archived_successfully'))
                                    ->body(__('guideline_archive.successfully_archived_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }
                            
                            if ($alreadyArchived > 0) {
                                Notification::make()
                                    ->title(__('guideline_archive.warning'))
                                    ->body(__('guideline_archive.already_archived_count', ['count' => $alreadyArchived]))
                                    ->warning()
                                    ->send();
                            }
                            
                            if ($count === 0 && $alreadyArchived > 0) {
                                Notification::make()
                                    ->title(__('guideline_archive.no_action_taken'))
                                    ->body(__('guideline_archive.all_selected_already_archived'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('archive Guideline'))
                        ->authorize(fn () => auth()->user()?->can('archive Guideline')),
                    BulkAction::make('restore')
                        ->label(__('guideline_archive.restore_selected'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('guideline_archive.confirm_restore'))
                        ->modalDescription(__('guideline_archive.confirm_restore_selected_message'))
                        ->action(function ($records) {
                            $count = 0;
                            $alreadyActive = 0;
                            
                            foreach ($records as $record) {
                                if (GuidelineResource::canRestore($record)) {
                                    if ($record->isArchived()) {
                                        $record->restore();
                                        $count++;
                                    } else {
                                        $alreadyActive++;
                                    }
                                }
                            }
                            
                            if ($count > 0) {
                                Notification::make()
                                    ->title(__('guideline_archive.guidelines_restored_successfully'))
                                    ->body(__('guideline_archive.successfully_restored_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }
                            
                            if ($alreadyActive > 0) {
                                Notification::make()
                                    ->title(__('guideline_archive.warning'))
                                    ->body(__('guideline_archive.already_active_count', ['count' => $alreadyActive]))
                                    ->warning()
                                    ->send();
                            }
                            
                            if ($count === 0 && $alreadyActive > 0) {
                                Notification::make()
                                    ->title(__('guideline_archive.no_action_taken'))
                                    ->body(__('guideline_archive.all_selected_already_active'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('restore Guideline'))
                        ->authorize(fn () => auth()->user()?->can('restore Guideline')),
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete Guideline')),
                ]),
                Tables\Actions\ExportBulkAction::make()
                    ->exporter(GuidelineExporter::class)
                    ->columnMapping(false)
                    ->fileName('Guidelines_List_' . now()->format('Y-m-d')),
            ])
            ->defaultSort('created_at', 'desc');
    }

}
