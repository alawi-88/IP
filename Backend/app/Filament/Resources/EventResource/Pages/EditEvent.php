<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Models\Event;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    /**
     * Resolve the record and check authorization
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Check if the current user is authorized to edit this event
        if (!EventResource::canEdit($record)) {
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
                ->title('Cannot Edit Archived Event / لا يمكن تعديل حدث مؤرشف')
                ->body('Archived events cannot be edited. You can only delete or restore them. / لا يمكن تعديل الأحداث المؤرشفة. يمكنك فقط حذفها أو استعادتها.')
                ->warning()
                ->send();

            $this->redirect(EventResource::getUrl('view', ['record' => $this->record]));
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
        return $form->schema(Event::form());
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Check if speakers data exists and is not empty
        $hasSpeakers = isset($data['speakers']) && is_array($data['speakers']) && !empty($data['speakers']);
        
        if ($hasSpeakers) {
            $speakers = [];
            foreach ($data['speakers'] as $index => $speaker) {
                if (is_array($speaker)) {
                    $processedSpeaker = [
                        'name' => [
                            'en' => $speaker['name']['en'] ?? '',
                            'ar' => $speaker['name']['ar'] ?? '',
                        ],
                        'experience' => [
                            'en' => $speaker['experience']['en'] ?? '',
                            'ar' => $speaker['experience']['ar'] ?? '',
                        ],
                        'brief' => [
                            'en' => $speaker['brief']['en'] ?? '',
                            'ar' => $speaker['brief']['ar'] ?? '',
                        ],
                        'photo' => $speaker['photo'] ?? null,
                    ];
                    $speakers[] = $processedSpeaker;
                }
            }
            $data['speakers'] = $speakers;
        } else {
            // If no speakers data, set empty array
            $data['speakers'] = [];
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.events.index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure speakers data is properly formatted before saving
        if (isset($data['speakers']) && is_array($data['speakers'])) {
            $speakers = [];
            foreach ($data['speakers'] as $speaker) {
                if (is_array($speaker) && (!empty($speaker['name']['en']) || !empty($speaker['name']['ar']))) {
                    $speakers[] = [
                        'name' => [
                            'en' => $speaker['name']['en'] ?? '',
                            'ar' => $speaker['name']['ar'] ?? '',
                        ],
                        'experience' => [
                            'en' => $speaker['experience']['en'] ?? '',
                            'ar' => $speaker['experience']['ar'] ?? '',
                        ],
                        'brief' => [
                            'en' => $speaker['brief']['en'] ?? '',
                            'ar' => $speaker['brief']['ar'] ?? '',
                        ],
                        'photo' => $speaker['photo'] ?? null,
                    ];
                }
            }
            $data['speakers'] = $speakers;
        }

        return $data;
    }
}
