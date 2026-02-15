<?php

namespace App\Filament\Resources\ProjectFormConfigResource\Pages;

use App\Filament\Resources\ProjectFormConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProjectFormConfig extends EditRecord
{
    protected static string $resource = ProjectFormConfigResource::class;

    /**
     * Resolve the record and check authorization
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Check if the current user is authorized to edit this project form config
        if (!ProjectFormConfigResource::canEdit($record)) {
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
                ->title('Cannot Edit Archived Project Form Config / لا يمكن تعديل إعداد نموذج المشروع المؤرشف')
                ->body('Archived project form configs cannot be edited. You can only delete or restore them. / لا يمكن تعديل إعدادات نماذج المشاريع المؤرشفة. يمكنك فقط حذفها أو استعادتها.')
                ->warning()
                ->send();

            $this->redirect(ProjectFormConfigResource::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.project-form-configs.index');
    }
}
