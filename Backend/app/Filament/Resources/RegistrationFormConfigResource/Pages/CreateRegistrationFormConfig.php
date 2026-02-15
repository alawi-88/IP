<?php

namespace App\Filament\Resources\RegistrationFormConfigResource\Pages;

use App\Filament\Resources\RegistrationFormConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateRegistrationFormConfig extends CreateRecord
{
    protected static string $resource = RegistrationFormConfigResource::class;

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.registration-form-configs.index');
    }

    protected function beforeValidate(): void
    {
        // Skip validation here - it happens too early and form state may not have repeater data yet
        // Validation will happen in beforeCreate() and mutateFormDataBeforeCreate()
    }

    protected function beforeCreate(): void
    {
        // Validation happens in mutateFormDataBeforeCreate where data is more reliably available
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Note: For relationship repeaters, Filament saves the relationship data AFTER the main record
        // So we can't reliably validate scoring configuration here (it will fail even if criteria exist)
        // Validation for scoring configuration happens in afterCreate() instead
        // We only validate assessment criteria for duplicates here if data is available
        if ($this->form) {
            try {
                $formData = $this->form->getState();
                // Only validate assessment criteria for duplicates - not scoring configuration
                $this->validateAssessmentCriteria($formData);
            } catch (ValidationException $e) {
                // Show notification
                $errors = $e->errors();
                $errorMessage = !empty($errors) ? collect($errors)->flatten()->first() : $e->getMessage();
                
                \Filament\Notifications\Notification::make()
                    ->title('Validation Error / خطأ في التحقق')
                    ->body($errorMessage)
                    ->danger()
                    ->persistent()
                    ->send();
                
                throw $e;
            }
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Validate after create - this is where relationship data is available
        // Filament saves relationship repeater data after the main record is created
        try {
            // Refresh record to get latest data including relationships
            $this->record->refresh();
            
            // Validate scoring configuration - check if scoring is enabled but no criteria exist
            if ($this->record->scoring_enabled) {
                $criteria = $this->record->assessmentCriteria()->get();
                
                if ($criteria->isEmpty()) {
                    // Disable scoring and show error
                    $this->record->scoring_enabled = false;
                    $this->record->save();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Validation Error / خطأ في التحقق')
                        ->body('Scoring was disabled because no assessment criteria were found. Please add at least one assessment criterion and enable scoring again. / تم تعطيل التسجيل لأنه لم يتم العثور على معايير تقييم. يرجى إضافة معيار تقييم واحد على الأقل وتمكين التسجيل مرة أخرى.')
                        ->warning()
                        ->persistent()
                        ->send();
                    
                    // Redirect to edit page so user can add criteria
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                    return;
                }
            }
            
            // Validate assessment criteria for duplicates
            $this->validateAndPreventDuplicates();
        } catch (ValidationException $e) {
            // Show notification with error message
            $errors = $e->errors();
            $errorMessage = !empty($errors) ? collect($errors)->flatten()->first() : $e->getMessage();
            
            \Filament\Notifications\Notification::make()
                ->title('Validation Error / خطأ في التحقق')
                ->body($errorMessage)
                ->danger()
                ->persistent()
                ->send();
            
            // Re-throw to ensure Filament displays the error
            throw $e;
        } catch (\Exception $e) {
            // Catch any other exceptions (like ValidationException from Model)
            if ($e instanceof ValidationException) {
                $errors = $e->errors();
                $errorMessage = !empty($errors) ? collect($errors)->flatten()->first() : $e->getMessage();
                
                \Filament\Notifications\Notification::make()
                    ->title('Validation Error / خطأ في التحقق')
                    ->body($errorMessage)
                    ->danger()
                    ->persistent()
                    ->send();
            }
            throw $e;
        }
    }

    /**
     * Validate and prevent duplicate sort_order values after save
     * This method checks the actual saved data and throws an exception if duplicates exist
     */
    protected function validateAndPreventDuplicates(): void
    {
        if (!$this->record || !$this->record->exists) {
            return;
        }

        // Refresh the record to get latest data
        $this->record->refresh();
        
        // Get all assessment criteria from database
        $criteria = $this->record->assessmentCriteria()->get();
        
        if ($criteria->isEmpty()) {
            return;
        }

        // Group by sort_order to find duplicates
        $groupedByOrder = $criteria->groupBy('sort_order');
        $duplicates = $groupedByOrder->filter(fn($group) => $group->count() > 1);

        if ($duplicates->isNotEmpty()) {
            // Get duplicate values for error message
            $duplicateValues = $duplicates->keys()->implode(', ');
            
            // Delete the duplicate criteria (keep only the first one of each group)
            foreach ($duplicates as $sortOrder => $duplicateCriteria) {
                // Keep only the first one, delete the rest
                $toDelete = $duplicateCriteria->skip(1);
                foreach ($toDelete as $criterion) {
                    $criterion->delete();
                }
            }

            // Throw validation exception to prevent the save from completing
            // This will show the error and prevent navigation
            throw ValidationException::withMessages([
                'assessment_criteria' => "Duplicate sort order values were detected and removed: {$duplicateValues}. Please ensure each criterion has a unique sort order. / تم اكتشاف قيم ترتيب مكررة وتم حذفها: {$duplicateValues}. يرجى التأكد من أن كل معيار له قيمة ترتيب فريدة.",
            ]);
        }
    }

    /**
     * Validate that scoring cannot be enabled without assessment criteria
     */
    protected function validateScoringConfiguration(array $data): void
    {
        $scoringEnabled = $data['scoring_enabled'] ?? false;
        
        if (!$scoringEnabled) {
            return; // Scoring is disabled, no validation needed
        }

        // Get criteria from multiple sources - try form state first (most reliable)
        $criteria = null;
        
        // First, try form state (most reliable for relationship repeaters)
        if ($this->form) {
            try {
                $formState = $this->form->getState();
                
                // Try multiple paths to get assessment_criteria
                if (isset($formState['assessment_criteria'])) {
                    $formCriteria = $formState['assessment_criteria'];
                    // Check if it's a valid array with data
                    if (is_array($formCriteria) && !empty($formCriteria)) {
                        $criteria = $formCriteria;
                    }
                }
                
                // Also try to access via Livewire component if available
                if (($criteria === null || empty($criteria)) && property_exists($this, 'mountedTableActionsData')) {
                    $mountedData = $this->mountedTableActionsData ?? [];
                    if (isset($mountedData['assessment_criteria']) && is_array($mountedData['assessment_criteria'])) {
                        $criteria = $mountedData['assessment_criteria'];
                    }
                }
            } catch (\Exception $e) {
                // Form state might not be available, continue to try data array
            }
        }
        
        // If not found in form state, try data array
        if (($criteria === null || empty($criteria)) && isset($data['assessment_criteria'])) {
            $dataCriteria = $data['assessment_criteria'];
            if (is_array($dataCriteria) && !empty($dataCriteria)) {
                $criteria = $dataCriteria;
            }
        }
        
        // If still no criteria found, set to empty array
        if ($criteria === null || !is_array($criteria)) {
            $criteria = [];
        }
        
        // Process form data - handle various formats from Filament repeater
        $allCriteria = [];
        
        if (is_array($criteria) && !empty($criteria)) {
            foreach ($criteria as $criterion) {
                if (is_array($criterion)) {
                    $allCriteria[] = $criterion;
                } elseif (is_object($criterion)) {
                    $allCriteria[] = (array) $criterion;
                }
            }
        }

        // Filter out empty criteria (where description is missing or empty)
        // Also handle cases where Filament might store data in different structures
        $validCriteria = collect($allCriteria)->filter(function ($criterion) {
            if (is_array($criterion)) {
                $description = null;
                
                // Try multiple paths to get description - check all possible locations
                if (isset($criterion['description'])) {
                    $description = $criterion['description'];
                } elseif (isset($criterion['data']['description'])) {
                    $description = $criterion['data']['description'];
                } elseif (isset($criterion['data']) && is_array($criterion['data'])) {
                    // Try nested data structure
                    if (isset($criterion['data']['description'])) {
                        $description = $criterion['data']['description'];
                    }
                    // Also check if data itself is the description
                    if ($description === null && is_string($criterion['data'])) {
                        $description = $criterion['data'];
                    }
                }
                
                // Also check for nested arrays
                if ($description === null && isset($criterion[0]) && is_array($criterion[0])) {
                    $description = $criterion[0]['description'] ?? null;
                }
                
                // Check if description is a string or array (for translatable fields)
                if (is_array($description)) {
                    // If it's an array, check if any language has content
                    $description = collect($description)->filter(fn($val) => !empty(trim($val)))->first();
                }
                
                return !empty(trim($description ?? ''));
            } elseif (is_object($criterion)) {
                $description = $criterion->description ?? null;
                
                // Handle array descriptions
                if (is_array($description)) {
                    $description = collect($description)->filter(fn($val) => !empty(trim($val)))->first();
                }
                
                return !empty(trim($description ?? ''));
            }
            return false;
        });

        if ($validCriteria->isEmpty()) {
            \Filament\Notifications\Notification::make()
                ->title('Validation Error / خطأ في التحقق')
                ->body('Scoring cannot be enabled without at least one assessment criterion. Please add at least one assessment criterion before enabling scoring. / لا يمكن تفعيل التسجيل بدون معيار تقييم واحد على الأقل. يرجى إضافة معيار تقييم واحد على الأقل قبل تفعيل التسجيل.')
                ->danger()
                ->send();
            throw ValidationException::withMessages([
                'scoring_enabled' => 'Scoring cannot be enabled without at least one assessment criterion. Please add at least one assessment criterion before enabling scoring. / لا يمكن تفعيل التسجيل بدون معيار تقييم واحد على الأقل. يرجى إضافة معيار تقييم واحد على الأقل قبل تفعيل التسجيل.',
            ]);
        }
    }

    /**
     * Validate that assessment criteria have unique sort_order values
     */
    protected function validateAssessmentCriteria(array $data): void
    {
        // Try to get criteria from multiple sources
        $criteria = null;
        
        // First, try from the data array
        if (isset($data['assessment_criteria']) && is_array($data['assessment_criteria'])) {
            $criteria = $data['assessment_criteria'];
        }
        // If not found, try to get from form state directly
        elseif ($this->form) {
            $formState = $this->form->getState();
            if (isset($formState['assessment_criteria']) && is_array($formState['assessment_criteria'])) {
                $criteria = $formState['assessment_criteria'];
            }
        }
        
        // If still no criteria found, return early
        if ($criteria === null || empty($criteria)) {
            return;
        }
        
        // First, validate that all criteria have sort_order values and they are positive integers
        $missingSortOrders = [];
        $invalidSortOrders = [];
        foreach ($criteria as $index => $criterion) {
            $sortOrder = null;
            
            // Handle array format (from form state)
            if (is_array($criterion)) {
                $sortOrder = $criterion['sort_order'] ?? null;
                // Also check nested data structure (Filament might nest it)
                if ($sortOrder === null && isset($criterion['data']['sort_order'])) {
                    $sortOrder = $criterion['data']['sort_order'];
                }
                // Check if it's a nested array with data key
                if ($sortOrder === null && isset($criterion['data']) && is_array($criterion['data'])) {
                    $sortOrder = $criterion['data']['sort_order'] ?? null;
                }
            }
            // Handle object format (from relationship or model)
            elseif (is_object($criterion)) {
                // Try direct property access
                $sortOrder = $criterion->sort_order ?? null;
                // Try attributes array (Eloquent models)
                if ($sortOrder === null && isset($criterion->attributes) && is_array($criterion->attributes)) {
                    $sortOrder = $criterion->attributes['sort_order'] ?? null;
                }
                // Try toArray() if it's a model
                if ($sortOrder === null && method_exists($criterion, 'toArray')) {
                    $criterionArray = $criterion->toArray();
                    $sortOrder = $criterionArray['sort_order'] ?? null;
                }
            }
            
            $description = is_array($criterion) 
                ? ($criterion['description'] ?? $criterion['data']['description'] ?? "Criterion " . ($index + 1))
                : ($criterion->description ?? "Criterion " . ($index + 1));
            
            // Check if sort_order is missing or empty
            if ($sortOrder === null || $sortOrder === '') {
                $missingSortOrders[] = $description;
            }
            // Validate that sort_order is a positive integer
            elseif (!is_numeric($sortOrder) || (float)$sortOrder != (int)$sortOrder || (int)$sortOrder < 1) {
                $invalidSortOrders[] = $description;
            }
        }
        
        // If any criteria have invalid sort_order values, throw validation error
        if (!empty($invalidSortOrders)) {
            $invalidList = implode(', ', array_slice($invalidSortOrders, 0, 3));
            if (count($invalidSortOrders) > 3) {
                $invalidList .= '...';
            }
            \Filament\Notifications\Notification::make()
                ->title('Validation Error / خطأ في التحقق')
                ->body("The sort order must be a positive integer for all assessment criteria. Invalid sort order for: {$invalidList} / يجب أن يكون الترتيب رقماً صحيحاً موجباً لجميع معايير التقييم. ترتيب غير صحيح لـ: {$invalidList}")
                ->danger()
                ->send();
            throw ValidationException::withMessages([
                'assessment_criteria' => "The sort order must be a positive integer for all assessment criteria. / يجب أن يكون الترتيب رقماً صحيحاً موجباً لجميع معايير التقييم.",
            ]);
        }
        
        // If any criteria are missing sort_order, throw validation error
        if (!empty($missingSortOrders)) {
            $missingList = implode(', ', array_slice($missingSortOrders, 0, 3));
            if (count($missingSortOrders) > 3) {
                $missingList .= '...';
            }
            \Filament\Notifications\Notification::make()
                ->title('Validation Error / خطأ في التحقق')
                ->body("The sort order field is required for all assessment criteria. Missing sort order for: {$missingList} / حقل الترتيب مطلوب لجميع معايير التقييم. مفقود الترتيب لـ: {$missingList}")
                ->danger()
                ->send();
            throw ValidationException::withMessages([
                'assessment_criteria' => "The sort order field is required for all assessment criteria. / حقل الترتيب مطلوب لجميع معايير التقييم.",
            ]);
        }
        
        // Collect all sort_order values, handling various data structures
        $sortOrders = [];
        
        foreach ($criteria as $criterion) {
            $sortOrder = null;
            
            // Handle array format (from form state)
            if (is_array($criterion)) {
                $sortOrder = $criterion['sort_order'] ?? null;
                // Also check nested data structure (Filament might nest it)
                if ($sortOrder === null && isset($criterion['data']['sort_order'])) {
                    $sortOrder = $criterion['data']['sort_order'];
                }
                // Check if it's a nested array with data key
                if ($sortOrder === null && isset($criterion['data']) && is_array($criterion['data'])) {
                    $sortOrder = $criterion['data']['sort_order'] ?? null;
                }
            }
            // Handle object format (from relationship or model)
            elseif (is_object($criterion)) {
                // Try direct property access
                $sortOrder = $criterion->sort_order ?? null;
                // Try attributes array (Eloquent models)
                if ($sortOrder === null && isset($criterion->attributes) && is_array($criterion->attributes)) {
                    $sortOrder = $criterion->attributes['sort_order'] ?? null;
                }
                // Try toArray() if it's a model
                if ($sortOrder === null && method_exists($criterion, 'toArray')) {
                    $criterionArray = $criterion->toArray();
                    $sortOrder = $criterionArray['sort_order'] ?? null;
                }
            }
            
            // Now we know sort_order is not empty (validated above), so add it
            if ($sortOrder !== null && $sortOrder !== '') {
                $sortOrders[] = (int) $sortOrder;
            }
        }

        // Find duplicates by counting occurrences
        $duplicates = [];
        if (!empty($sortOrders)) {
            $counts = array_count_values($sortOrders);
            foreach ($counts as $order => $count) {
                if ($count > 1) {
                    $duplicates[] = $order;
                }
            }
        }

        if (!empty($duplicates)) {
            $duplicateValues = implode(', ', $duplicates);
            \Filament\Notifications\Notification::make()
                ->title('Validation Error / خطأ في التحقق')
                ->body("Each assessment criterion must have a unique sort order value. Duplicate order values found: {$duplicateValues} / يجب أن يكون لكل معيار تقييم قيمة ترتيب فريدة. تم العثور على قيم ترتيب مكررة: {$duplicateValues}")
                ->danger()
                ->send();
            throw ValidationException::withMessages([
                'assessment_criteria' => "Each assessment criterion must have a unique sort order value. Duplicate order values found: {$duplicateValues} / يجب أن يكون لكل معيار تقييم قيمة ترتيب فريدة. تم العثور على قيم ترتيب مكررة: {$duplicateValues}",
            ]);
        }
    }
}
