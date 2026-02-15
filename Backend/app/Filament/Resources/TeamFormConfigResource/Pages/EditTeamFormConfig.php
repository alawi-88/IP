<?php

namespace App\Filament\Resources\TeamFormConfigResource\Pages;

use App\Filament\Resources\TeamFormConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeamFormConfig extends EditRecord
{
    protected static string $resource = TeamFormConfigResource::class;

    /**
     * Resolve the record and check authorization
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Check if the current user is authorized to edit this team form config
        if (!TeamFormConfigResource::canEdit($record)) {
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
                ->title('Cannot Edit Archived Team Form Config / لا يمكن تعديل إعداد نموذج الفريق المؤرشف')
                ->body('Archived team form configs cannot be edited. You can only delete or restore them. / لا يمكن تعديل إعدادات نماذج الفرق المؤرشفة. يمكنك فقط حذفها أو استعادتها.')
                ->warning()
                ->send();

            $this->redirect(TeamFormConfigResource::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
