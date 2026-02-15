<?php

namespace App\Filament\Resources\GuidelineResource\Pages;

use App\Filament\Resources\GuidelineResource;
use App\Models\Competition;
use App\Models\Guideline;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateGuideline extends CreateRecord
{
    protected static string $resource = GuidelineResource::class;

    public function form(Form $form): Form
    {
        return $form->schema(Guideline::form())->columns(1);
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.guidelines.index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    protected function afterCreate(): void
    {
        // Get the form data to process files
        $formData = $this->form->getState();
        
        if (isset($formData['files']) && is_array($formData['files'])) {
            foreach ($formData['files'] as $fileData) {
                // Handle the three attachment types and merge them into a single attachment field
                $attachment = $fileData['attachment_video']
                    ?? $fileData['attachment_document']
                    ?? $fileData['attachment_image']
                    ?? null;

                // Set file_type based on which attachment field has data
                $fileType = 'video'; // default
                if (!empty($fileData['attachment_video'])) {
                    $fileType = 'video';
                } elseif (!empty($fileData['attachment_document'])) {
                    $fileType = 'document';
                } elseif (!empty($fileData['attachment_image'])) {
                    $fileType = 'image';
                }

                // Create the file record with processed data
                $processedFileData = [
                    'title' => $fileData['title'] ?? [],
                    'description' => $fileData['description'] ?? [],
                    'file_type' => $fileType,
                    'attachment' => $attachment,
                ];
                
                // Create the file record
                $this->record->files()->create($processedFileData);
            }
        }
    }
}
