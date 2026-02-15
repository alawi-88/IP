<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\SupervisorResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewUser  extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => !$this->record->isArchived()),

            Actions\Action::make('restore')
                ->label('Restore / استعادة')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Restore Admin / استعادة المسؤول')
                ->modalDescription('Are you sure you want to restore this admin? / هل أنت متأكد من استعادة هذا المسؤول؟')
                ->authorize(fn () => UserResource::canRestore($this->record))
                ->visible(fn () => $this->record->isArchived())
                ->action(function () {
                    $this->record->restore();
                    \Filament\Notifications\Notification::make()
                        ->title('Admin Restored / تم استعادة المسؤول')
                        ->body('The admin has been restored successfully. / تم استعادة المسؤول بنجاح.')
                        ->success()
                        ->send();
                    
                    $this->redirect(UserResource::getUrl('index'));
                }),

            Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->id !== auth()->id()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(User::details());
    }
}
