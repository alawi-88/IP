<?php

namespace App\Filament\Resources\FormResource\Pages;

use App\Filament\Resources\FormResource;
use App\Services\FormApprovalService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class EditForm extends EditRecord
{
    protected static string $resource = FormResource::class;

    /**
     * Resolve the record and check authorization to prevent IDOR
     * Returns 404 instead of 403 to avoid revealing resource existence
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Check if the current user is authorized to edit this form
        // Return 404 to avoid revealing that the resource exists but user lacks access
        if (!FormResource::canEdit($record)) {
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
                ->title('Cannot Edit Archived Form / لا يمكن تعديل نموذج مؤرشف')
                ->body('Archived forms cannot be edited. You can only delete or restore them. / لا يمكن تعديل النماذج المؤرشفة. يمكنك فقط حذفها أو استعادتها.')
                ->warning()
                ->send();

            $this->redirect(FormResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('delete')
                ->label('Delete / حذف')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->authorize(fn () => FormResource::canDelete($this->record))
                ->modalHeading('Delete Form / حذف النموذج')
                ->modalDescription('Are you sure you want to delete this form? This action will be submitted for approval. / هل أنت متأكد من حذف هذا النموذج؟ سيتم تقديم هذا الإجراء للموافقة.')
                ->action(function () {
                    // Check if approval workflow exists for form deletion
                    $approvalService = new FormApprovalService();
                    
                    $result = $approvalService->processAction(
                        'delete',
                        [
                            'form_id' => $this->record->id,
                            'name' => $this->record->name,
                            'old_values' => $this->record->toArray(), // Store current values for reference
                        ],
                        $this->record->id,
                        'Form deletion request / طلب حذف النموذج',
                        auth()->id()
                    );

                    if ($result['success']) {
                        if ($result['requires_approval']) {
                            Notification::make()
                                ->title('Deletion Request Submitted / تم تقديم طلب الحذف')
                                ->body('Your form deletion request has been submitted for approval. / تم تقديم طلب حذف النموذج للموافقة.')
                                ->success()
                                ->send();

                            $this->redirect(route('filament.admin.resources.my-requests.index'));
                        } else {
                            // Execute immediately if no workflow
                            $this->record->delete();
                            Notification::make()
                                ->title('Form Deleted / تم حذف النموذج')
                                ->body('The form has been deleted successfully. / تم حذف النموذج بنجاح.')
                                ->success()
                                ->send();

                            $this->redirect(route('filament.admin.resources.forms.index'));
                        }
                    } else {
                        Notification::make()
                            ->title('Error / خطأ')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('archive')
                ->label('Archive / أرشفة')
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->requiresConfirmation()
                ->authorize(fn () => FormResource::canArchive($this->record))
                ->visible(fn () => !$this->record->isArchived())
                ->action(function () {
                    $this->handleArchiveAction();
                }),
        ];
    }

    protected function handleArchiveAction(): void
    {
        $approvalService = new FormApprovalService();
        
        $result = $approvalService->processAction(
            'archive',
            [
                'is_archived' => true,
                'form_id' => $this->record->id,
                'name' => $this->record->name,
                'old_values' => ['is_archived' => $this->record->is_archived ?? false], // Store current archive status
            ],
            $this->record->id,
            'Form archive request / طلب أرشفة النموذج',
            auth()->id()
        );

        if ($result['success']) {
            if ($result['requires_approval']) {
                Notification::make()
                    ->title('Archive Request Submitted / تم تقديم طلب الأرشفة')
                    ->body('Your form archive request has been submitted for approval. / تم تقديم طلب أرشفة النموذج للموافقة.')
                    ->success()
                    ->send();

                $this->redirect(route('filament.admin.resources.my-requests.index'));
            } else {
                // Execute immediately if no workflow
                $this->record->update(['is_archived' => true, 'archived_at' => now()]);
                Notification::make()
                    ->title('Form Archived / تم أرشفة النموذج')
                    ->body('The form has been archived successfully. / تم أرشفة النموذج بنجاح.')
                    ->success()
                    ->send();

                $this->redirect(route('filament.admin.resources.forms.index'));
            }
        } else {
            Notification::make()
                ->title('Error / خطأ')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.forms.index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Check if approval workflow exists for form update
        $approvalService = new FormApprovalService();
        
        // Store old values for comparison in approval request
        $oldValues = $this->record->only(array_keys($data));
        
        // Add old values for translatable fields
        if (isset($oldValues['name'])) {
            $oldValues['name'] = is_array($this->record->name) 
                ? $this->record->name 
                : ['en' => $this->record->name, 'ar' => $this->record->name];
        }
        
        if (isset($oldValues['description'])) {
            $oldValues['description'] = is_array($this->record->description) 
                ? $this->record->description 
                : ['en' => $this->record->description ?? '', 'ar' => $this->record->description ?? ''];
        }
        
        // Merge data: $data (new values) takes priority, then add form_id
        $actionData = array_merge($data, [
            'form_id' => $this->record->id,
            'old_values' => $oldValues, // Store old values for before/after comparison
        ]);
        
        $result = $approvalService->processAction(
            'update',
            $actionData,
            $this->record->id,
            'Form update request / طلب تحديث النموذج',
            auth()->id()
        );

        if ($result['success']) {
            if ($result['requires_approval']) {
                Notification::make()
                    ->title('Update Request Submitted / تم تقديم طلب التحديث')
                    ->body('Your form update request has been submitted for approval. / تم تقديم طلب تحديث النموذج للموافقة.')
                    ->success()
                    ->send();

                $this->redirect(route('filament.admin.resources.my-requests.index'));
                $this->halt();
            } else {
                // No approval required, continue with normal save
                // The form will be updated normally in the parent method
            }
        } else {
            Notification::make()
                ->title('Error / خطأ')
                ->body($result['message'])
                ->danger()
                ->send();

            $this->halt();
        }

        $oldConfig = $this->record->evaluation_config ?? [];

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
            } elseif (isset($data['evaluation_agreement_text.en']) || isset($data['evaluation_agreement_text.ar'])) {
                // Handle form field format (en/ar as separate fields)
                $agreementText = [
                    'en' => $data['evaluation_agreement_text.en'] ?? '',
                    'ar' => $data['evaluation_agreement_text.ar'] ?? '',
                ];
            } else {
                // Fallback to old config or empty
                $agreementText = $oldConfig['evaluation_agreement_text'] ?? ['en' => '', 'ar' => ''];
                // If old format is string, convert it
                if (is_string($agreementText)) {
                    $agreementText = ['en' => $agreementText, 'ar' => $agreementText];
                }
            }
            
            $data['evaluation_config'] = array_merge($oldConfig, [
                'evaluation_criteria' => $data['evaluation_criteria'] ?? [],
                'enable_overall_comments' => $data['enable_overall_comments'],
                'total_score' => $data['total_score'] ?? 100,
                'rounding_rule' => $data['rounding_rule'] ?? '1',
                'evaluation_agreement_text' => $agreementText,
                'require_agreement_acceptance' => $data['require_agreement_acceptance'] ?? false,
            ]);
        }

        // Handle conditional logic fields before saving
        if (!empty($data['fields'])) {
            foreach ($data['fields'] as $fieldIndex => &$field) {
                // Format conditional logic rules for saving
                if (!empty($field['conditional_logic']) && !empty($field['conditional_logic_rules'])) {
                    $field['conditional_logic_rules'] = $this->formatConditionalLogicRulesForSave($field['conditional_logic_rules']);
                }
            }
            $data['fields'] = $data['fields'];
        }

        unset(
            $data['evaluation_criteria'],
            $data['total_score'],
            $data['enable_overall_comments'],
            $data['rounding_rule'],
            $data['evaluation_agreement_text'],
            $data['evaluation_agreement_text.en'],
            $data['evaluation_agreement_text.ar'],
            $data['require_agreement_acceptance']
        );

        return $data;
    }



    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!empty($data['evaluation_config'])) {
            $data['evaluation_criteria'] = $data['evaluation_config']['evaluation_criteria'] ?? [];
            $data['enable_overall_comments'] = $data['evaluation_config']['enable_overall_comments'] ?? false;
            $data['total_score'] = $data['evaluation_config']['total_score'] ?? 100;
            $data['rounding_rule'] = $data['evaluation_config']['rounding_rule'] ?? '1';
            // Handle bilingual evaluation_agreement_text
            $agreementText = $data['evaluation_config']['evaluation_agreement_text'] ?? '';
            if (is_string($agreementText)) {
                // Old format: string - convert to array format for form
                $data['evaluation_agreement_text'] = [
                    'en' => $agreementText,
                    'ar' => $agreementText,
                ];
            } elseif (is_array($agreementText)) {
                // New format: already an array
                $data['evaluation_agreement_text'] = $agreementText;
            } else {
                // Fallback: empty bilingual format
                $data['evaluation_agreement_text'] = ['en' => '', 'ar' => ''];
            }
            $data['require_agreement_acceptance'] = $data['evaluation_config']['require_agreement_acceptance'] ?? false;
        }

        // Handle conditional logic fields
        if (!empty($data['fields'])) {
            foreach ($data['fields'] as $fieldIndex => &$field) {
                // Ensure conditional logic fields are properly loaded
                if (!empty($field['conditional_logic']) && !empty($field['conditional_logic_rules'])) {
                    // Make sure conditional logic rules are properly formatted
                    $field['conditional_logic_rules'] = $this->formatConditionalLogicRulesForEdit($field['conditional_logic_rules']);
                }
            }
            $data['fields'] = $data['fields'];
        }

        return $data;
    }

    /**
     * Format conditional logic rules for editing
     */
    protected function formatConditionalLogicRulesForEdit(array $rules): array
    {
        return collect($rules)->map(function ($rule) {
            if (isset($rule['values']) && is_array($rule['values'])) {
                // Ensure values are properly formatted for Filament
                $rule['values'] = collect($rule['values'])->map(function ($value) {
                    // If value is already properly formatted for Filament, return as is
                    if (is_array($value) && isset($value['en']) && isset($value['ar'])) {
                        return $value;
                    }
                    
                    // If value is in the storage format ['value' => 'some_value']
                    if (is_array($value) && isset($value['value'])) {
                        return [
                            'en' => $value['value'],
                            'ar' => $value['value'],
                        ];
                    }
                    
                    // If value is a simple string, format it properly
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
