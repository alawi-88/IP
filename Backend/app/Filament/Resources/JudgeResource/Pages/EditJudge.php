<?php

namespace App\Filament\Resources\JudgeResource\Pages;

use App\Filament\Resources\JudgeResource;
use App\Models\Judge;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditJudge extends EditRecord
{
    protected static string $resource = JudgeResource::class;

    /**
     * Resolve the record and check authorization
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Check if the current user is authorized to edit this judge
        if (!JudgeResource::canEdit($record)) {
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
                ->title('Cannot Edit Archived Judge / لا يمكن تعديل حكم مؤرشف')
                ->body('Archived judges cannot be edited. You can only delete or restore them. / لا يمكن تعديل الحكام المؤرشفين. يمكنك فقط حذفهم أو استعادتهم.')
                ->warning()
                ->send();

            $this->redirect(JudgeResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return route('filament.admin.resources.judges.view', [
            'record' => $this->record->getKey(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema(Judge::form());
    }
}
