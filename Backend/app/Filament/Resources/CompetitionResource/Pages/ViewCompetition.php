<?php

namespace App\Filament\Resources\CompetitionResource\Pages;

use App\Filament\Resources\CompetitionResource;
use App\Models\Competition;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCompetition extends ViewRecord
{
    protected static string $resource = CompetitionResource::class;

    /**
     * Resolve the record and check authorization to prevent IDOR
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Check if user can view this specific competition
        if (!CompetitionResource::canView($record)) {
            abort(403, 'You do not have permission to view this program. / ليس لديك صلاحية لعرض هذا البرنامج.');
        }

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->authorize(fn ($record) => CompetitionResource::canEdit($record))
                ->visible(fn ($record) => !$record->isArchived()),
            Actions\Action::make('restore')
                ->label('Restore / استعادة')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Restore Program / استعادة البرنامج')
                ->modalDescription('Are you sure you want to restore this program? / هل أنت متأكد من استعادة هذا البرنامج؟')
                ->authorize(fn ($record) => CompetitionResource::canRestore($record))
                ->visible(fn ($record) => $record->isArchived())
                ->action(function (Competition $record) {
                    $record->restore();
                    \Filament\Notifications\Notification::make()
                        ->title('Program Restored / تم استعادة البرنامج')
                        ->body('The program has been restored successfully. / تم استعادة البرنامج بنجاح.')
                        ->success()
                        ->send();

                    $this->redirect(route('filament.admin.resources.competitions.index'));
                }),
            Actions\Action::make('delete')
                ->label('Delete / حذف')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->authorize(fn ($record) => CompetitionResource::canDelete($record))
                ->modalHeading('Delete Program / حذف البرنامج')
                ->modalDescription('Are you sure you want to delete this program? This action will be submitted for approval. / هل أنت متأكد من حذف هذا البرنامج؟ سيتم تقديم هذا الإجراء للموافقة.')
                ->action(function (Competition $record) {
                    // Use ProgramApprovalService for deleting competitions
                    $approvalService = new \App\Services\ProgramApprovalService();
                    $result = $approvalService->processAction('delete', ['competition_id' => $record->id, 'title' => $record->title], $record->id, 'Program deletion request');

                    if ($result['success']) {
                        if ($result['requires_approval']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Request Submitted / تم تقديم الطلب')
                                ->body('Your deletion request has been submitted for approval. / تم تقديم طلب الحذف للموافقة.')
                                ->success()
                                ->send();
                            // Don't delete the record - it needs approval
                            // The action will not delete the record automatically
                        } else {
                            // Execute immediately if no workflow
                            $record->delete();
                            \Filament\Notifications\Notification::make()
                                ->title('Program Deleted / تم حذف البرنامج')
                                ->body('The program has been deleted successfully. / تم حذف البرنامج بنجاح.')
                                ->success()
                                ->send();

                            // Redirect to competitions list after deletion
                            $this->redirect(route('filament.admin.resources.competitions.index'));
                        }
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Error / خطأ')
                            ->body('Failed to submit deletion request. / فشل في تقديم طلب الحذف.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(Competition::details());
    }
}
