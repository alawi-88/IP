<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        // Set default query to show all users (active + archived)
        $table->query(User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super-admin'))
        );

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('programs_count')
                    ->label('Programs Count')
                    ->counts('programs')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->formatStateUsing(fn($state) => $state ? $state->format('Y-m-d H:i') : 'Never')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_archived')
                    ->label(__('user_archive.archived_users'))
                    ->boolean()
                    ->searchable()
                    ->visible(fn() => auth()->user()?->can('archive User') || auth()->user()?->can('restore User')),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('archive')
                    ->label(__('user_archive.archive_user'))
                    ->color('warning')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->modalHeading(__('user_archive.archive_modal_heading'))
                    ->modalDescription(__('user_archive.archive_modal_description'))
                    ->modalSubmitActionLabel(__('user_archive.archive_modal_confirm'))
                    ->action(function ($record) {
                        $record->archive();
                        \Filament\Notifications\Notification::make()
                            ->title(__('user_archive.user_archived'))
                            ->body(__('user_archive.successfully_archived'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => !$record->isArchived() && UserResource::canArchive($record)),

                Tables\Actions\Action::make('restore')
                    ->label(__('user_archive.restore_user'))
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading(__('user_archive.restore_modal_heading'))
                    ->modalDescription(__('user_archive.restore_modal_description'))
                    ->modalSubmitActionLabel(__('user_archive.restore_modal_confirm'))
                    ->action(function ($record) {
                        $record->restore();
                        \Filament\Notifications\Notification::make()
                            ->title(__('user_archive.user_restored'))
                            ->body(__('user_archive.successfully_restored'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => $record->isArchived() && UserResource::canRestore($record)),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->isArchived() && auth()->user()?->can('update Admin')),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->id !== auth()->id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('archive')
                        ->label(__('user_archive.archive_selected'))
                        ->color('warning')
                        ->icon('heroicon-o-archive-box')
                        ->requiresConfirmation()
                        ->modalHeading(__('user_archive.archive_bulk_heading'))
                        ->modalDescription(__('user_archive.archive_bulk_description'))
                        ->modalSubmitActionLabel(__('user_archive.archive_bulk_confirm'))
                        ->action(function ($records) {
                            $count = 0;
                            $alreadyArchived = 0;
                            
                            foreach ($records as $record) {
                                if (!$record->isArchived()) {
                                    $record->archive();
                                    $count++;
                                } else {
                                    $alreadyArchived++;
                                }
                            }
                            
                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('user_archive.users_archived'))
                                    ->body(__('user_archive.successfully_archived_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }
                            
                            if ($alreadyArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('user_archive.warning'))
                                    ->body(__('user_archive.already_archived_count', ['count' => $alreadyArchived]))
                                    ->warning()
                                    ->send();
                            }
                            
                            if ($count === 0 && $alreadyArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('user_archive.no_action_taken'))
                                    ->body(__('user_archive.all_selected_already_archived'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('archive User'))
                        ->authorize(fn () => auth()->user()?->can('archive User')),

                    Tables\Actions\BulkAction::make('restore')
                        ->label(__('user_archive.restore_selected'))
                        ->color('success')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->requiresConfirmation()
                        ->modalHeading(__('user_archive.restore_bulk_heading'))
                        ->modalDescription(__('user_archive.restore_bulk_description'))
                        ->modalSubmitActionLabel(__('user_archive.restore_bulk_confirm'))
                        ->action(function ($records) {
                            $count = 0;
                            $alreadyActive = 0;
                            
                            foreach ($records as $record) {
                                if ($record->isArchived()) {
                                    $record->restore();
                                    $count++;
                                } else {
                                    $alreadyActive++;
                                }
                            }
                            
                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('user_archive.users_restored'))
                                    ->body(__('user_archive.successfully_restored_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }
                            
                            if ($alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('user_archive.warning'))
                                    ->body(__('user_archive.already_active_count', ['count' => $alreadyActive]))
                                    ->warning()
                                    ->send();
                            }
                            
                            if ($count === 0 && $alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('user_archive.no_action_taken'))
                                    ->body(__('user_archive.all_selected_already_active'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('restore User'))
                        ->authorize(fn () => auth()->user()?->can('restore User')),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete Admin')),
                ]),
            ]);
    }

    public function getTabs(): array
    {
        $baseQuery = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super-admin'));

        $tabs = [
            'all' => Tab::make('All')
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn($query) => clone $baseQuery),
            
            'active' => Tab::make(__('user_archive.active_users'))
                ->badge((clone $baseQuery)->active()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->active()),
        ];

        // Only add archived tab for users with archive or restore permissions
        if (auth()->user()?->can('archive User') || auth()->user()?->can('restore User')) {
            $tabs['archived'] = Tab::make(__('user_archive.archived_users'))
                ->badge((clone $baseQuery)->archived()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->archived());
        }

        return $tabs;
    }
}
