<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Models\Event;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;


    public function form(Form $form): Form
    {
        return $form->schema(Event::form());
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.events.index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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
