<?php

namespace App\Filament\Resources\MentorResource\Pages;

use App\Filament\Resources\MentorResource;
use App\Models\Mentor;
use App\Notifications\Mentor\MentorApproved;
use App\Notifications\Mentor\MentorRejected;
use App\Notifications\Mentor\MentorRegistrationPending;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditMentor extends EditRecord
{
    protected static string $resource = MentorResource::class;
    
    protected $originalStatus = null;

    /**
     * Resolve the record and check authorization
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Check if the current user is authorized to edit this mentor
        if (!MentorResource::canEdit($record)) {
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
                ->title('Cannot Edit Archived Mentor / لا يمكن تعديل مرشد مؤرشف')
                ->body('Archived mentors cannot be edited. You can only delete or restore them. / لا يمكن تعديل المرشدين المؤرشفين. يمكنك فقط حذفهم أو استعادتهم.')
                ->warning()
                ->send();

            $this->redirect(MentorResource::getUrl('view', ['record' => $this->record]));
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
        return $form->schema(Mentor::form());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store original status to detect changes
        $this->originalStatus = $this->record->status;
        
        // Set rejected_at if status is rejected
        if (isset($data['status']) && $data['status'] === 'rejected') {
            // Only set rejected_at if not already set or if status is changing to rejected
            if (!$this->record->rejected_at || $this->originalStatus !== 'rejected') {
                $data['rejected_at'] = now();
            }
            // Set approved_by if not already set
            if (!$this->record->approved_by) {
                $data['approved_by'] = auth()->id();
            }
        } elseif (isset($data['status']) && $data['status'] !== 'rejected') {
            // Clear rejected_at if status is changing away from rejected
            if ($this->originalStatus === 'rejected') {
                $data['rejected_at'] = null;
                $data['rejection_reason'] = null;
            }
        }
        
        // Set approved_at if status is approved
        if (isset($data['status']) && $data['status'] === 'approved') {
            if (!$this->record->approved_at || $this->originalStatus !== 'approved') {
                $data['approved_at'] = now();
            }
            if (!$this->record->approved_by) {
                $data['approved_by'] = auth()->id();
            }
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Refresh record to get latest data
        $currentStatus = $this->record->fresh()->status;
        
        // Check if status changed to rejected
        if ($currentStatus === 'rejected' && $this->originalStatus !== 'rejected') {
            
            // Send rejection notification
            $rejectionReason = $this->record->rejection_reason ?? null;
            $this->record->notify(new MentorRejected($this->record, $rejectionReason));
        }
        
        // Check if status changed to approved
        if ($currentStatus === 'approved' && $this->originalStatus !== 'approved') {
            
            // Mark pending registration notification as read
            $this->record->notifications()
                ->where('type', MentorRegistrationPending::class)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
            
            // Send approval notification
            $this->record->notify(new MentorApproved($this->record));
        }
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.mentors.index');
    }
}
