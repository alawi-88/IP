<?php

namespace App\Filament\Resources\FormAiScoringConfigResource\Pages;

use App\Filament\Resources\FormAiScoringConfigResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateFormAiScoringConfig extends CreateRecord
{
    protected static string $resource = FormAiScoringConfigResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Get form_id from the form
        $formId = $data['form_id'];
        
        // Remove program_id and form_type from data as they're not in the model
        unset($data['program_id']);
        unset($data['form_type']);
        
        // Set form_id
        $data['form_id'] = $formId;
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}

