<?php

namespace App\Filament\Resources\RegistrationFormConfigResource\Pages;

use App\Filament\Resources\RegistrationFormConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditRegistrationFormConfig extends EditRecord
{
    protected static string $resource = RegistrationFormConfigResource::class;

    /**
     * Resolve the record and check authorization
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Check if the current user is authorized to edit this registration form config
        if (!RegistrationFormConfigResource::canEdit($record)) {
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
                ->title('Cannot Edit Archived Registration Form Config / لا يمكن تعديل إعداد نموذج التسجيل المؤرشف')
                ->body('Archived registration form configs cannot be edited. You can only delete or restore them. / لا يمكن تعديل إعدادات نماذج التسجيل المؤرشفة. يمكنك فقط حذفها أو استعادتها.')
                ->warning()
                ->send();

            $this->redirect(RegistrationFormConfigResource::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.registration-form-configs.index');
    }

    protected function beforeValidate(): void
    {
        // Skip validation here - it happens too early and form state may not have repeater data yet
        // Validation will happen in beforeSave() and mutateFormDataBeforeSave()
    }

    protected function beforeSave(): void
    {
        // Validation happens in mutateFormDataBeforeSave where data is more reliably available
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Get form state to validate - this should have the latest data including repeater
        $formData = $this->form->getState();
        
        // Validate scoring configuration and assessment criteria
        try {
            $this->validateScoringConfiguration($formData);
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
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Validate after save - this is where relationship data is available
        // Filament saves relationship repeater data after the main record is saved
        try {
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

        // Get criteria from multiple sources - prioritize data array, then form state, then database
        $criteria = null;
        
        // First, try data array (most reliable in mutateFormDataBeforeSave)
        if (isset($data['assessment_criteria'])) {
            $dataCriteria = $data['assessment_criteria'];
            if (is_array($dataCriteria) && !empty($dataCriteria)) {
                $criteria = $dataCriteria;
            }
        }
        
        // If not found in data array, try form state
        if (($criteria === null || empty($criteria)) && $this->form) {
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
            } catch (\Exception $e) {
                // Form state might not be available
            }
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
        
        // Also check existing database records (for edit mode) if form data is empty
        // This handles the case where existing criteria aren't in form state
        if (empty($allCriteria) && $this->record && $this->record->exists) {
            $existingCriteria = $this->record->assessmentCriteria()->get();
            if ($existingCriteria->isNotEmpty()) {
                // If we have existing criteria in DB, validation passes
                return;
            }
        }

        // Filter out empty criteria (where description is missing or empty)
        // Also handle cases where Filament might store data in different structures
        $validCriteria = collect($allCriteria)->filter(function ($criterion) {
            if (is_array($criterion)) {
                $description = null;
                
                // Try multiple paths to get description
                if (isset($criterion['description'])) {
                    $description = $criterion['description'];
                } elseif (isset($criterion['data']['description'])) {
                    $description = $criterion['data']['description'];
                } elseif (isset($criterion['data']) && is_array($criterion['data']) && isset($criterion['data']['description'])) {
                    $description = $criterion['data']['description'];
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
        
        // Collect all sort_order values with their IDs (if available), handling various data structures
        $sortOrdersWithIds = [];
        
        foreach ($criteria as $criterion) {
            $sortOrder = null;
            $criterionId = null;
            
            // Handle array format (from form state)
            if (is_array($criterion)) {
                $sortOrder = $criterion['sort_order'] ?? null;
                $criterionId = $criterion['id'] ?? null;
                // Also check nested data structure (Filament might nest it)
                if ($sortOrder === null && isset($criterion['data']['sort_order'])) {
                    $sortOrder = $criterion['data']['sort_order'];
                }
                if ($criterionId === null && isset($criterion['data']['id'])) {
                    $criterionId = $criterion['data']['id'];
                }
                // Check if it's a nested array with data key
                if ($sortOrder === null && isset($criterion['data']) && is_array($criterion['data'])) {
                    $sortOrder = $criterion['data']['sort_order'] ?? null;
                    $criterionId = $criterionId ?? ($criterion['data']['id'] ?? null);
                }
            }
            // Handle object format (from relationship or model)
            elseif (is_object($criterion)) {
                // Try direct property access
                $sortOrder = $criterion->sort_order ?? null;
                $criterionId = $criterion->id ?? null;
                // Try attributes array (Eloquent models)
                if ($sortOrder === null && isset($criterion->attributes) && is_array($criterion->attributes)) {
                    $sortOrder = $criterion->attributes['sort_order'] ?? null;
                    $criterionId = $criterionId ?? ($criterion->attributes['id'] ?? null);
                }
                // Try toArray() if it's a model
                if ($sortOrder === null && method_exists($criterion, 'toArray')) {
                    $criterionArray = $criterion->toArray();
                    $sortOrder = $criterionArray['sort_order'] ?? null;
                    $criterionId = $criterionId ?? ($criterionArray['id'] ?? null);
                }
            }
            
            // Now we know sort_order is not empty (validated above), so add it
            if ($sortOrder !== null && $sortOrder !== '') {
                $sortOrdersWithIds[] = [
                    'sort_order' => (int) $sortOrder,
                    'id' => $criterionId ? (int) $criterionId : null,
                ];
            }
        }

        // Group by sort_order and check for duplicates
        // Only consider it a duplicate if the same sort_order appears multiple times
        // AND they are different records (different IDs or one has no ID)
        $groupedByOrder = collect($sortOrdersWithIds)->groupBy('sort_order');
        $duplicates = [];
        
        foreach ($groupedByOrder as $sortOrder => $items) {
            if ($items->count() > 1) {
                // Check if these are actually different records
                $ids = $items->pluck('id')->filter()->unique();
                // If we have multiple different IDs, or multiple items without IDs, it's a duplicate
                if ($ids->count() > 1 || ($ids->count() === 0 && $items->count() > 1)) {
                    $duplicates[] = $sortOrder;
                } elseif ($ids->count() === 1 && $items->count() > 1) {
                    // Same ID appearing multiple times - might be a form issue, but not a real duplicate
                    // Only flag if there are items without IDs mixed with items with IDs
                    $hasId = $items->filter(fn($item) => $item['id'] !== null)->count();
                    $noId = $items->filter(fn($item) => $item['id'] === null)->count();
                    if ($hasId > 0 && $noId > 0) {
                        $duplicates[] = $sortOrder;
                    }
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
