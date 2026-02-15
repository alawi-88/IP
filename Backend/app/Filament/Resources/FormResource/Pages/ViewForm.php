<?php

namespace App\Filament\Resources\FormResource\Pages;

use App\Filament\Resources\FormResource;
use App\Services\FormApprovalService;
use Filament\Actions;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewForm extends ViewRecord
{
    protected static string $resource = FormResource::class;

    /**
     * Resolve the record and check authorization to prevent IDOR
     * Returns 404 instead of 403 to avoid revealing resource existence
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Check if the current user is authorized to view this form
        // Return 404 to avoid revealing that the resource exists but user lacks access
        if (!FormResource::canView($record)) {
            abort(404);
        }

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->authorize(fn () => FormResource::canEdit($this->record))
                ->visible(fn () => !$this->record->isArchived()),
            Actions\Action::make('delete')
                ->label('Delete / حذف')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->authorize(fn () => FormResource::canDelete($this->record))
                ->visible(fn () => !$this->record->isArchived())
                ->modalHeading('Delete Form / حذف النموذج')
                ->modalDescription('Are you sure you want to delete this form? This action will be submitted for approval. / هل أنت متأكد من حذف هذا النموذج؟ سيتم تقديم هذا الإجراء للموافقة.')
                ->action(function () {
                    // Use FormApprovalService for deleting forms
                    $approvalService = new FormApprovalService();
                    $result = $approvalService->processAction(
                        'delete',
                        ['form_id' => $this->record->id, 'name' => $this->record->name],
                        $this->record->id,
                        'Form deletion request / طلب حذف النموذج',
                        auth()->id()
                    );
                    
                    if ($result['success']) {
                        if ($result['requires_approval']) {
                            Notification::make()
                                ->title('Request Submitted / تم تقديم الطلب')
                                ->body('Your deletion request has been submitted for approval. / تم تقديم طلب الحذف للموافقة.')
                                ->success()
                                ->send();
                            // Don't delete the record - it needs approval
                        } else {
                            // Execute immediately if no workflow
                            $this->record->delete();
                            Notification::make()
                                ->title('Form Deleted / تم حذف النموذج')
                                ->body('The form has been deleted successfully. / تم حذف النموذج بنجاح.')
                                ->success()
                                ->send();
                            
                            // Redirect to forms list after deletion
                            $this->redirect(route('filament.admin.resources.forms.index'));
                        }
                    } else {
                        Notification::make()
                            ->title('Error / خطأ')
                            ->body('Failed to submit deletion request. / فشل في تقديم طلب الحذف.')
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('archive')
                ->label('Archive / أرشفة')
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->requiresConfirmation()
                ->authorize(fn () => FormResource::canArchive($this->record))
                ->visible(fn () => !$this->record->isArchived())
                ->action(function () {
                    $approvalService = new FormApprovalService();
                    
                    $result = $approvalService->processAction(
                        'archive',
                        [
                            'is_archived' => true,
                            'form_id' => $this->record->id,
                            'name' => $this->record->name,
                            'old_values' => ['is_archived' => $this->record->is_archived ?? false],
                        ],
                        $this->record->id,
                        'Form archive request / طلب أرشفة النموذج',
                        auth()->id()
                    );

                    if ($result['success']) {
                        if ($result['requires_approval']) {
                            Notification::make()
                                ->title('Archive Request Submitted / تم تقديم طلب الأرشفة')
                                ->body('Your form archive request has been submitted for approval. / تم تقديم طلب أرشفة النموذج للموافقة.')
                                ->success()
                                ->send();

                            $this->redirect(route('filament.admin.resources.my-requests.index'));
                        } else {
                            // Execute immediately if no workflow
                            $this->record->update(['is_archived' => true, 'archived_at' => now()]);
                            Notification::make()
                                ->title('Form Archived / تم أرشفة النموذج')
                                ->body('The form has been archived successfully. / تم أرشفة النموذج بنجاح.')
                                ->success()
                                ->send();

                            $this->redirect(route('filament.admin.resources.forms.index'));
                        }
                    } else {
                        Notification::make()
                            ->title('Error / خطأ')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('restore')
                ->label('Restore / استعادة')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Restore Form / استعادة النموذج')
                ->modalDescription('Are you sure you want to restore this form? / هل أنت متأكد من استعادة هذا النموذج؟')
                ->authorize(fn () => FormResource::canRestore($this->record))
                ->visible(fn () => $this->record->isArchived())
                ->action(function () {
                    $this->record->restore();
                    Notification::make()
                        ->title('Form Restored / تم استعادة النموذج')
                        ->body('The form has been restored successfully. / تم استعادة النموذج بنجاح.')
                        ->success()
                        ->send();
                    
                    $this->redirect(FormResource::getUrl('index'));
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
                Section::make()
                    ->columns()
                    ->schema(
                        [
                            TextEntry::make('name')->label('Name'),

                            TextEntry::make('type')->label('Type')
                                ->formatStateUsing(fn($state) => str($state)->title()),

                            TextEntry::make('created_at')->label('Created At'),

                            TextEntry::make('updated_at')->label('Updated At'),

                            TextEntry::make('competition.title')->label('Program'),

                            TextEntry::make('number_of_submissions')->default(0)->label('Number of Submissions'),

                            IconEntry::make('is_published')->boolean()->label('Published'),
                        ]
                    )
            ]
        );
    }
}
