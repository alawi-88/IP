<?php

namespace App\Filament\Resources\FormAiHintsResource\Pages;

use App\Filament\Resources\FormAiHintsResource;
use App\Models\FormAiEnhancementConfig;
use Filament\Resources\Pages\CreateRecord;

class CreateFormAiHints extends CreateRecord
{
    protected static string $resource = FormAiHintsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Get form_id from the form
        $formId = $data['form_id'];
        
        // Check if a config already exists for this form
        $existingConfig = FormAiEnhancementConfig::where('form_id', $formId)->first();
        
        if ($existingConfig) {
            // Update existing config
            $existingConfig->update([
                'ai_enhancement_enabled' => $data['ai_enhancement_enabled'] ?? false,
                'ai_enhancement_fields' => $data['ai_enhancement_fields'] ?? null,
            ]);
            
            // Set the record to the existing one
            $this->record = $existingConfig;
            
            // Return empty array to prevent creating a new record
            return [];
        }
        
        // Remove competition_id and form_type from data as they're not in the model
        unset($data['competition_id']);
        unset($data['form_type']);
        
        // Set form_id
        $data['form_id'] = $formId;
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Always redirect to index after create or update
        $this->redirect($this->getResource()::getUrl('index'));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
