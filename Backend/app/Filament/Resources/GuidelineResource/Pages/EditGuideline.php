<?php

namespace App\Filament\Resources\GuidelineResource\Pages;

use App\Filament\Resources\GuidelineResource;
use App\Models\Guideline;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditGuideline extends EditRecord
{
    protected static string $resource = GuidelineResource::class;

    /**
     * Resolve the record and check authorization
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Check if the current user is authorized to edit this guideline
        if (!GuidelineResource::canEdit($record)) {
            abort(404);
        }

        return $record;
    }

    /**
     * Mount the component and check if record is archived
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Prevent editing archived records - they can only be deleted or restored
        if ($this->record->isArchived()) {
            \Filament\Notifications\Notification::make()
                ->title('Cannot Edit Archived Guideline / لا يمكن تعديل إرشادات مؤرشف')
                ->body('Archived guidelines cannot be edited. You can only delete or restore them. / لا يمكن تعديل الإرشادات المؤرشفة. يمكنك فقط حذفها أو استعادتها.')
                ->warning()
                ->send();

            $this->redirect(GuidelineResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema(Guideline::form())->columns(1);
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.guidelines.index');
    }
}
