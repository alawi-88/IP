<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use App\Models\Team;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use App\Models\TeamMember;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewTeam extends ViewRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Delete Action, visible only if user can delete Team
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete Team')),

            // Change Leader - only for non-archived teams
            Action::make('Change Leader')
                ->label('Change Leader')
                ->icon('heroicon-m-user-group')
                ->form(function ($record) {
                    return [
                        Forms\Components\Select::make('new_leader_id')
                            ->label('Select New Leader')
                            ->options(
                                $record->members()
                                    ->with('participant')
                                    ->get()
                                    ->pluck('participant.name', 'id')
                            )
                            ->required(),
                    ];
                })
                ->action(function ($data, $record) {
                    if ($record->is_completed) {
                        Notification::make()
                            ->title('You cannot change the leader after submission.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $currentLeader = $record->members()->where('is_leader', true)->first();
                    $newLeader = $record->members()->where('id', $data['new_leader_id'])->first();

                    if ($currentLeader && $newLeader && $currentLeader->id !== $newLeader->id) {
                        $currentLeader->update(['is_leader' => false]);
                        $newLeader->update(['is_leader' => true]);

                        Notification::make()
                            ->title('Team leader changed successfully.')
                            ->success()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->visible(fn () => !$this->record->isArchived() && auth()->user()?->can('update Team')),

            // Remove Member - only for non-archived teams
            Action::make('Remove Member')
                ->label('Remove Member')
                ->icon('heroicon-o-user-minus')
                ->form(function ($record) {
                    return [
                        Forms\Components\Select::make('member_id')
                            ->label('Select Member to Remove')
                            ->options(
                                $record->members()
                                    ->with('participant')
                                    ->get()
                                    ->pluck('participant.name', 'id')
                            )
                            ->required(),
                    ];
                })
                ->action(function ($data, $record) {
                    $member = $record->members()->find($data['member_id']);

                    if (! $member) {
                        Notification::make()
                            ->title('Member not found.')
                            ->danger()
                            ->send();
                        return;
                    }

                    if ($member->is_leader) {
                        Notification::make()
                            ->title('You cannot remove the team leader.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $member->delete();

                    Notification::make()
                        ->title('Team member removed successfully.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn () => !$this->record->isArchived() && auth()->user()?->can('update Team')),

            // Restore action - only for archived teams
            Action::make('restore')
                ->label('Restore / استعادة')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Restore Team / استعادة الفريق')
                ->modalDescription('Are you sure you want to restore this team? / هل أنت متأكد من استعادة هذا الفريق؟')
                ->authorize(fn () => TeamResource::canRestore($this->record))
                ->visible(fn () => $this->record->isArchived())
                ->action(function () {
                    $this->record->restore();
                    Notification::make()
                        ->title('Team Restored / تم استعادة الفريق')
                        ->body('The team has been restored successfully. / تم استعادة الفريق بنجاح.')
                        ->success()
                        ->send();
                    
                    $this->redirect(TeamResource::getUrl('index'));
                }),
        ];
    }


    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(Team::details());
    }
}
