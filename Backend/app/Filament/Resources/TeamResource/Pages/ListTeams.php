<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Exports\TeamExporter;
use App\Filament\Resources\TeamResource;
use App\Models\SubTrack;
use App\Models\Team;
use App\Models\Track;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTeams extends ListRecords
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        $baseQuery = Team::byProgram();
        
        return $table
            ->query($baseQuery)
            ->columns(Team::columns())
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Team $record) => !$record->isArchived()),
                Tables\Actions\DeleteAction::make(),
                
                // Archive action
                Tables\Actions\Action::make('archive')
                    ->label(__('team_archive.archive_team'))
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('team_archive.confirm_archive'))
                    ->modalDescription(__('team_archive.confirm_archive_message'))
                    ->action(function (Team $record) {
                        try {
                            $record->archive();
                            \Filament\Notifications\Notification::make()
                                ->title(__('team_archive.team_archived_successfully'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('team_archive.failed_to_archive_team'))
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Team $record) => TeamResource::canArchive($record)),
                
                // Restore action
                Tables\Actions\Action::make('restore')
                    ->label(__('team_archive.restore_team'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('team_archive.confirm_restore'))
                    ->modalDescription(__('team_archive.confirm_restore_message'))
                    ->action(function (Team $record) {
                        try {
                            $record->restore();
                            \Filament\Notifications\Notification::make()
                                ->title(__('team_archive.team_restored_successfully'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('team_archive.failed_to_restore_team'))
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Team $record) => TeamResource::canRestore($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete Team')),
                    
                    // Archive selected teams
                    Tables\Actions\BulkAction::make('archive_selected')
                        ->label(__('team_archive.archive_selected_teams'))
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading(__('team_archive.confirm_archive'))
                        ->modalDescription(__('team_archive.confirm_archive_selected_message'))
                        ->action(function ($records) {
                            $count = 0;
                            $alreadyArchived = 0;
                            $noPermission = 0;
                            
                            try {
                                foreach ($records as $record) {
                                    // Check if user has permission to archive this team
                                    if (auth()->user()?->can('archive Team')) {
                                        if (!$record->isArchived()) {
                                            $record->archive();
                                            $count++;
                                        } else {
                                            $alreadyArchived++;
                                        }
                                    } else {
                                        $noPermission++;
                                    }
                                }
                                
                                // Prepare notification messages
                                $messages = [];
                                
                                // Show success message if any teams were archived
                                if ($count > 0) {
                                    $messages[] = [
                                        'title' => __('team_archive.teams_archived_successfully'),
                                        'body' => __('team_archive.successfully_archived_count', ['count' => $count]),
                                        'type' => 'success'
                                    ];
                                }
                                
                                // Show warning if some teams were already archived
                                if ($alreadyArchived > 0) {
                                    $messages[] = [
                                        'title' => __('team_archive.warning'),
                                        'body' => __('team_archive.already_archived_count', ['count' => $alreadyArchived]),
                                        'type' => 'warning'
                                    ];
                                }
                                
                                // Show warning if all selected teams were already archived
                                if ($count === 0 && $alreadyArchived > 0 && $noPermission === 0) {
                                    $messages[] = [
                                        'title' => __('team_archive.no_action_taken'),
                                        'body' => __('team_archive.all_selected_already_archived'),
                                        'type' => 'warning'
                                    ];
                                }
                                
                                // Show error if no permission
                                if ($noPermission > 0) {
                                    $messages[] = [
                                        'title' => __('team_archive.no_permission'),
                                        'body' => __('team_archive.no_permission_message', ['count' => $noPermission]),
                                        'type' => 'danger'
                                    ];
                                }
                                
                                // Send all notifications
                                foreach ($messages as $message) {
                                    \Filament\Notifications\Notification::make()
                                        ->title($message['title'])
                                        ->body($message['body'])
                                        ->{$message['type']}()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('team_archive.failed_to_archive_teams'))
                                    ->body(__('team_archive.error_occurred'))
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('archive Team')),
                    
                    // Restore selected teams
                    Tables\Actions\BulkAction::make('restore_selected')
                        ->label(__('team_archive.restore_selected_teams'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('team_archive.confirm_restore'))
                        ->modalDescription(__('team_archive.confirm_restore_selected_message'))
                        ->action(function ($records) {
                            $count = 0;
                            $alreadyActive = 0;
                            $noPermission = 0;
                            
                            try {
                                foreach ($records as $record) {
                                    // Check if user has permission to restore this team
                                    if (auth()->user()?->can('restore Team')) {
                                        if ($record->isArchived()) {
                                            $record->restore();
                                            $count++;
                                        } else {
                                            $alreadyActive++;
                                        }
                                    } else {
                                        $noPermission++;
                                    }
                                }
                                
                                // Prepare notification messages
                                $messages = [];
                                
                                // Show success message if any teams were restored
                                if ($count > 0) {
                                    $messages[] = [
                                        'title' => __('team_archive.teams_restored_successfully'),
                                        'body' => __('team_archive.successfully_restored_count', ['count' => $count]),
                                        'type' => 'success'
                                    ];
                                }
                                
                                // Show warning if some teams were already active
                                if ($alreadyActive > 0) {
                                    $messages[] = [
                                        'title' => __('team_archive.warning'),
                                        'body' => __('team_archive.already_active_count', ['count' => $alreadyActive]),
                                        'type' => 'warning'
                                    ];
                                }
                                
                                // Show warning if all selected teams were already active
                                if ($count === 0 && $alreadyActive > 0 && $noPermission === 0) {
                                    $messages[] = [
                                        'title' => __('team_archive.no_action_taken'),
                                        'body' => __('team_archive.all_selected_already_active'),
                                        'type' => 'warning'
                                    ];
                                }
                                
                                // Show error if no permission
                                if ($noPermission > 0) {
                                    $messages[] = [
                                        'title' => __('team_archive.no_permission'),
                                        'body' => __('team_archive.no_permission_restore_message', ['count' => $noPermission]),
                                        'type' => 'danger'
                                    ];
                                }
                                
                                // Send all notifications
                                foreach ($messages as $message) {
                                    \Filament\Notifications\Notification::make()
                                        ->title($message['title'])
                                        ->body($message['body'])
                                        ->{$message['type']}()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('team_archive.failed_to_restore_teams'))
                                    ->body(__('team_archive.error_occurred'))
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('restore Team')),
                ]),

                Tables\Actions\ExportBulkAction::make()->exporter(TeamExporter::class)
                    ->columnMapping(false)
                    ->fileName('Teams_List_' . now()->format('Y-m-d'))
            ])
            ->filters([
                Filter::make('track_subtrack')
                    ->label('Track / Sub-track')
                    ->form([
                        Select::make('track_id')
                            ->label('Track')
                            ->placeholder('All Tracks')
                            ->options(fn() => Track::pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(
                                fn(callable $set) =>
                                $set('sub_track_id', null)
                            ),

                        Select::make('sub_track_id')
                            ->label('Sub Track')
                            ->placeholder('Select a track first')
                            ->options(
                                fn(callable $get) =>
                                $get('track_id')
                                    ? SubTrack::where('track_id', $get('track_id'))->pluck('name', 'id')
                                    : []
                            )
                            ->disabled(
                                fn(callable $get) => ! $get('track_id')
                            ),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['track_id'] ?? null,
                                fn($q, $track) => $q->where('track_id', $track)
                            )
                            ->when(
                                $data['sub_track_id'] ?? null,
                                fn($q, $sub) => $q->where('sub_track_id', $sub)
                            );
                    }),
                Tables\Filters\SelectFilter::make('is_published')
                    ->label('Published')
                    ->options([
                        1 => 'Published',
                        0 => 'Unpublished',
                    ])
            ]);
    }

    public function getTabs(): array
    {
        $baseQuery = Team::byProgram();
        
        $tabs = [
            'all' => Tab::make(__('team_archive.all_teams'))
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn (Builder $query) => clone $baseQuery),
            
            'active' => Tab::make(__('team_archive.active_teams'))
                ->badge((clone $baseQuery)->where('is_archived', false)->count())
                ->modifyQueryUsing(fn (Builder $query) => (clone $baseQuery)->where('is_archived', false)),
        ];
        
        // Only add archived tab if user has permissions
        if (auth()->user()?->can('archive Team') || auth()->user()?->can('restore Team')) {
            $tabs['archived'] = Tab::make(__('team_archive.archived_teams'))
                ->badge((clone $baseQuery)->where('is_archived', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => (clone $baseQuery)->where('is_archived', true));
        }
        
        return $tabs;
    }
}
