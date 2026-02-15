<?php

namespace App\Filament\Resources\FormAiScoringConfigResource\RelationManagers;

use App\Models\FormAssessmentCriterion;
use App\Models\FormField;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class FieldMappingRelationManager extends RelationManager
{
    protected static string $relationship = 'assessmentCriteria';

    protected static ?string $title = 'Field Mapping';


    public function table(Table $table): Table
    {
        $formId = $this->ownerRecord->form_id;
        $criteria = FormAssessmentCriterion::where('form_id', $formId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();
        
        $fields = FormField::where('form_id', $formId)
            ->orderBy('sort')
            ->get();

        return $table
            ->heading('Field Mapping Matrix')
            ->description(function () use ($formId) {
                $totalCriteria = FormAssessmentCriterion::where('form_id', $formId)
                    ->where('status', 'active')
                    ->count();
                $mappedCriteria = FormAssessmentCriterion::where('form_id', $formId)
                    ->where('status', 'active')
                    ->whereHas('formFields')
                    ->count();
                $unmappedCriteria = $totalCriteria - $mappedCriteria;
                $totalFields = FormField::where('form_id', $formId)->count();
                $mappedFields = FormField::where('form_id', $formId)
                    ->whereHas('assessmentCriteria')
                    ->count();
                
                $message = "Criteria: {$mappedCriteria}/{$totalCriteria} mapped | Fields: {$mappedFields}/{$totalFields} mapped";
                if ($unmappedCriteria > 0) {
                    $message .= " | ⚠️ {$unmappedCriteria} criterion/criteria without field mappings";
                }
                return $message;
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Assessment Criterion')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('mapped_fields')
                    ->label('Mapped Fields')
                    ->getStateUsing(function ($record) use ($fields) {
                        $mappedFields = $record->formFields;
                        if ($mappedFields->isEmpty()) {
                            return '—';
                        }
                        return $mappedFields->map(function ($field) {
                            $label = is_array($field->label) 
                                ? ($field->label['en'] ?? reset($field->label)) 
                                : $field->label;
                            return $label . ' (' . $field->type . ')';
                        })->join(', ');
                    })
                    ->wrap()
                    ->badge()
                    ->color(fn ($record) => $record->formFields->isEmpty() ? 'gray' : 'success')
                    ->placeholder('No fields mapped'),

                Tables\Columns\TextColumn::make('field_count')
                    ->label('Field Count')
                    ->getStateUsing(fn ($record) => $record->formFields->count())
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($record) => $record->formFields->isEmpty() ? 'danger' : 'success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('has_mappings')
                    ->label('Mapping Status')
                    ->options([
                        'mapped' => 'Has Mappings',
                        'unmapped' => 'No Mappings',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] === 'mapped') {
                            return $query->whereHas('formFields');
                        } elseif ($data['value'] === 'unmapped') {
                            return $query->whereDoesntHave('formFields');
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_mapping')
                    ->label('Edit Mapping')
                    ->icon('heroicon-o-pencil')
                    ->form(function ($record) use ($fields) {
                        return [
                            Forms\Components\Select::make('field_ids')
                                ->label('Form Fields')
                                ->options(function () use ($fields) {
                                    return $fields->mapWithKeys(function ($field) {
                                        $label = is_array($field->label) 
                                            ? ($field->label['en'] ?? reset($field->label)) 
                                            : $field->label;
                                        return [$field->id => $label . ' (' . $field->type . ')'];
                                    })->toArray();
                                })
                                ->multiple()
                                ->required()
                                ->searchable()
                                ->default($record->formFields->pluck('id')->toArray())
                                ->placeholder('Select fields')
                                ->helperText('Select one or more fields that this criterion will assess. At least one field is required for active criteria.')
                                ->rules([
                                    function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            if (empty($value) || (is_array($value) && count($value) === 0)) {
                                                $fail('At least one field is required');
                                            }
                                        };
                                    },
                                ]),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        // Validate at least one field is selected
                        if (empty($data['field_ids'])) {
                            Notification::make()
                                ->title('Validation Error')
                                ->body('At least one field is required.')
                                ->danger()
                                ->send();
                            return;
                        }
                        $this->syncFieldMapping($record, $data['field_ids']);
                    })
                    ->modalHeading('Edit Field Mapping')
                    ->modalSubmitActionLabel('Save')
                    ->modalCancelActionLabel('Cancel'),

                Tables\Actions\Action::make('clear_mapping')
                    ->label('Clear Mapping')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Clear Field Mapping')
                    ->modalDescription('Are you sure you want to remove all field mappings for this criterion?')
                    ->action(function ($record) {
                        $record->formFields()->detach();
                        Notification::make()
                            ->title('Mapping Cleared')
                            ->body('All field mappings for this criterion have been removed.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->formFields->isNotEmpty()),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('clear_all_mappings')
                    ->label('Clear All Mappings')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Clear All Field Mappings')
                    ->modalDescription('Are you sure you want to remove all field mappings for the selected criteria?')
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            $record->formFields()->detach();
                        }
                        Notification::make()
                            ->title('Mappings Cleared')
                            ->body('All field mappings for selected criteria have been removed.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No Assessment Criteria')
            ->emptyStateDescription('Please add at least one active assessment criterion first.')
            ->modifyQueryUsing(function ($query) {
                return $query->where('status', 'active')->orderBy('sort_order');
            });
    }

    protected function syncFieldMapping(FormAssessmentCriterion $criterion, array $fieldIds): void
    {
        // Validate that fields belong to the same form
        $formId = $this->ownerRecord->form_id;
        $validFieldIds = FormField::where('form_id', $formId)
            ->whereIn('id', $fieldIds)
            ->pluck('id')
            ->toArray();

        if (count($validFieldIds) !== count($fieldIds)) {
            Notification::make()
                ->title('Invalid Fields')
                ->body('Some selected fields are invalid or do not belong to this form.')
                ->danger()
                ->send();
            return;
        }

        if (empty($validFieldIds)) {
            Notification::make()
                ->title('No Fields Selected')
                ->body('Please select at least one field to map.')
                ->warning()
                ->send();
            return;
        }

        // Check for duplicate mappings (this shouldn't happen due to unique constraint, but validate anyway)
        $existingMappings = $criterion->formFields()->pluck('form_fields.id')->toArray();
        $duplicates = array_intersect($existingMappings, $validFieldIds);
        
        if (!empty($duplicates) && count($validFieldIds) === count($duplicates) && count($existingMappings) === count($validFieldIds)) {
            // All fields are already mapped, no change needed
            Notification::make()
                ->title('No Changes')
                ->body('The selected fields are already mapped to this criterion.')
                ->info()
                ->send();
            return;
        }

        // Validate no duplicate mappings exist (database constraint will also prevent this)
        foreach ($validFieldIds as $fieldId) {
            $existingMapping = \DB::table('form_assessment_criterion_form_field')
                ->where('form_assessment_criterion_id', $criterion->id)
                ->where('form_field_id', $fieldId)
                ->exists();
            
            if ($existingMapping && !in_array($fieldId, $existingMappings)) {
                // This shouldn't happen, but handle it gracefully
                continue;
            }
        }

        // Sync the mappings
        try {
            $criterion->formFields()->sync($validFieldIds);

            $fieldCount = count($validFieldIds);
            Notification::make()
                ->title('Mapping Saved')
                ->body("Successfully mapped {$fieldCount} field(s) to this criterion.")
                ->success()
                ->send();
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle duplicate key constraint violation
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate')) {
                Notification::make()
                    ->title('Duplicate Mapping')
                    ->body('Duplicate mapping not allowed.')
                    ->danger()
                    ->send();
            } else {
                Notification::make()
                    ->title('Error')
                    ->body('An error occurred while saving the mapping.')
                    ->danger()
                    ->send();
            }
        }
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        // Only show if there's at least one active assessment criterion
        $hasCriteria = FormAssessmentCriterion::where('form_id', $ownerRecord->form_id)
            ->where('status', 'active')
            ->exists();
        
        return $hasCriteria;
    }
}
