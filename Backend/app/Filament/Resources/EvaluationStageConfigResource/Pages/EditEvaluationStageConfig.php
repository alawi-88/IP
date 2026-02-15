<?php

namespace App\Filament\Resources\EvaluationStageConfigResource\Pages;

use App\Filament\Resources\EvaluationStageConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditEvaluationStageConfig extends EditRecord
{
    protected static string $resource = EvaluationStageConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->prepareStageData($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->prepareStageData($data);
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.evaluation-stage-configs.index');
    }

    /**
     *
     * Prepare and validate the stages before saving.
     */
    protected function prepareStageData(array $data): array
    {
        if (isset($data['stages']) && is_array($data['stages'])) {
            foreach ($data['stages'] as $index => &$stage) {
                if (is_array($stage)) {
                    $stage['stage_number'] = $index + 1;
                }
            }
        }

        $data['number_of_stages'] = count($data['stages']);

        return $data;
    }
}
