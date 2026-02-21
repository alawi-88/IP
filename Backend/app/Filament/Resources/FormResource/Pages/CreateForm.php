<?php

namespace App\Filament\Resources\FormResource\Pages;

use App\Events\FormProgramStagesCreated;
use App\Filament\Resources\FormResource;
use App\Services\FormApprovalService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class CreateForm extends CreateRecord
{
    protected static string $resource = FormResource::class;

    public function getPollingInterval(): ?string
    {
        return '10s';
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.forms.index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Process form data first (evaluation config, conditional logic, etc.)
        // This ensures all data is properly formatted before checking for approval
        
        if ($data['type'] === 'evaluation') {
            // Validate evaluation criteria weights
            $this->validateEvaluationCriteria($data);
            
            // Handle bilingual evaluation_agreement_text
            $agreementText = '';
            if (isset($data['evaluation_agreement_text'])) {
                if (is_array($data['evaluation_agreement_text'])) {
                    // New format: array with en/ar keys
                    $agreementText = $data['evaluation_agreement_text'];
                } else {
                    // Old format: string - convert to bilingual format
                    $agreementText = [
                        'en' => $data['evaluation_agreement_text'] ?? '',
                        'ar' => $data['evaluation_agreement_text'] ?? '',
                    ];
                }
            } else {
                $agreementText = ['en' => '', 'ar' => ''];
            }
            
            $data['evaluation_config'] = [
                'evaluation_criteria' => $data['evaluation_criteria'] ?? [],
                'enable_overall_comments' => $data['enable_overall_comments'] ?? false,
                'total_score' => $data['total_score'] ?? 100,
                'rounding_rule' => $data['rounding_rule'] ?? '1',
                'evaluation_agreement_text' => $agreementText,
                'require_agreement_acceptance' => $data['require_agreement_acceptance'] ?? false,
            ];
        }

        // Handle conditional logic fields before saving
        if (!empty($data['fields'])) {
            foreach ($data['fields'] as $fieldIndex => &$field) {
                // Format conditional logic rules for saving
                if (!empty($field['conditional_logic']) && !empty($field['conditional_logic_rules'])) {
                    $field['conditional_logic_rules'] = $this->formatConditionalLogicRulesForSave($field['conditional_logic_rules']);
                }
            }
        }

        // Check if approval workflow exists for form creation
        $approvalService = new FormApprovalService();
        
        if ($approvalService->hasWorkflowForAction('create')) {
            // Create approval request instead of creating directly
            // Include all form data including fields
            $result = $approvalService->processAction(
                'create',
                $data,
                null,
                'Form creation request / طلب إنشاء نموذج',
                auth()->id()
            );

            if ($result['success'] && $result['requires_approval']) {
                Notification::make()
                    ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                    ->body('Your form creation request has been submitted for approval. You will be notified once approved. / تم تقديم طلب إنشاء النموذج للموافقة. سيتم إشعارك عند الموافقة.')
                    ->success()
                    ->send();

                // Redirect to the approval requests list
                $this->redirect(route('filament.admin.resources.my-requests.index'));
                $this->halt();
            } else {
                Notification::make()
                    ->title('Error / خطأ')
                    ->body($result['message'])
                    ->danger()
                    ->send();
                
                $this->halt();
            }
        } else {
            // No workflow exists, continue with normal creation
            // The form will be created normally in the parent method
        }

        unset(
            $data['evaluation_criteria'],
            $data['enable_overall_comments'],
            $data['total_score'],
            $data['rounding_rule'],
            $data['evaluation_agreement_text'],
            $data['evaluation_agreement_text.en'],
            $data['evaluation_agreement_text.ar'],
            $data['require_agreement_acceptance']
        );

        if (!isset($data['is_published']) || !$data['is_published']) {
            $data['updated_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        event(new FormProgramStagesCreated($this->record));
    }

    /**
     * Format conditional logic rules for saving
     */
    protected function formatConditionalLogicRulesForSave(array $rules): array
    {
        return collect($rules)->map(function ($rule) {
            if (isset($rule['values']) && is_array($rule['values'])) {
                // Convert Filament format back to storage format
                $rule['values'] = collect($rule['values'])->map(function ($value) {
                    // If value has both en and ar, convert to storage format
                    if (is_array($value) && isset($value['en'])) {
                        return [
                            'en' => $value['en'],
                            'ar' => $value['ar'] ?? $value['en'], // Use English as fallback for Arabic
                        ];
                    }
                    
                    // If value is already in storage format, return as is
                    if (is_array($value) && isset($value['value'])) {
                        return [
                            'en' => $value['value'],
                            'ar' => $value['value'],
                        ];
                    }
                    
                    // If value is a simple string, format for storage
                    if (is_string($value)) {
                        return [
                            'en' => $value,
                            'ar' => $value,
                        ];
                    }
                    
                    return $value;
                })->toArray();
            }
            
            return $rule;
        })->toArray();
    }

    protected function validateEvaluationCriteria(array $data): void
    {
        $criteria = $data['evaluation_criteria'] ?? [];
        
        if (empty($criteria)) {
            \Filament\Notifications\Notification::make()
                ->title('Validation Error')
                ->body('Evaluation criteria are required for evaluation forms.')
                ->danger()
                ->send();
            $this->halt('Evaluation criteria are required for evaluation forms.');
        }
        
        // First validate sub-criteria weights total 100 for each main criterion
        foreach ($criteria as $index => $criterion) {
            if (!empty($criterion['subcriteria'])) {
                $subcriteriaWeights = collect($criterion['subcriteria'])->pluck('weight')->sum();
                if ($subcriteriaWeights !== 100) {
                    $criterionName = $criterion['label']['en'] ?? "Criterion " . ($index + 1);
                    \Filament\Notifications\Notification::make()
                        ->title('Validation Error')
                        ->body("Sub-criteria weights for '{$criterionName}' must total 100%. Current total: {$subcriteriaWeights}%")
                        ->danger()
                        ->send();
                    $this->halt("Sub-criteria weights for '{$criterionName}' must total 100%. Current total: {$subcriteriaWeights}%");
                }
            }
        }
        
        // Then validate main criteria weights total 100
        $mainCriteriaWeights = collect($criteria)->pluck('weight')->sum();
        if ($mainCriteriaWeights !== 100) {
            \Filament\Notifications\Notification::make()
                ->title('Validation Error')
                ->body("Main criteria weights must total 100%. Current total: {$mainCriteriaWeights}%")
                ->danger()
                ->send();
            $this->halt("Main criteria weights must total 100%. Current total: {$mainCriteriaWeights}%");
        }

        
    }

}
