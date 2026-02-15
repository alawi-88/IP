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

class ContextRelationManager extends RelationManager
{
    protected static string $relationship = 'assessmentCriteria';

    protected static ?string $title = 'Context';

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
            ->heading('Contextual Fields Configuration')
            ->description(function () use ($formId) {
                $totalCriteria = FormAssessmentCriterion::where('form_id', $formId)
                    ->where('status', 'active')
                    ->whereHas('formFields')
                    ->count();
                $criteriaWithContext = FormAssessmentCriterion::where('form_id', $formId)
                    ->where('status', 'active')
                    ->whereHas('formFields')
                    ->whereHas('contextualFields')
                    ->count();
                
                return "Configure contextual fields for each assessment criterion. {$criteriaWithContext}/{$totalCriteria} criteria have contextual fields configured.";
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Assessment Criterion')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('mapped_field')
                    ->label('Mapped Field')
                    ->getStateUsing(function ($record) {
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
                    ->color('info')
                    ->placeholder('No field mapped'),

                Tables\Columns\TextColumn::make('contextual_fields')
                    ->label('Context Fields')
                    ->getStateUsing(function ($record) {
                        $contextFields = $record->contextualFields;
                        if ($contextFields->isEmpty()) {
                            return '—';
                        }
                        return $contextFields->map(function ($field) {
                            $label = is_array($field->label) 
                                ? ($field->label['en'] ?? reset($field->label)) 
                                : $field->label;
                            return $label . ' (' . $field->type . ')';
                        })->join(', ');
                    })
                    ->wrap()
                    ->badge()
                    ->color(fn ($record) => $record->contextualFields->isEmpty() ? 'gray' : 'success')
                    ->placeholder('No context fields'),

                Tables\Columns\TextColumn::make('context_count')
                    ->label('Context Count')
                    ->getStateUsing(fn ($record) => $record->contextualFields->count())
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($record) => $record->contextualFields->isEmpty() ? 'gray' : 'success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('has_context')
                    ->label('Context Status')
                    ->options([
                        'has_context' => 'Has Context Fields',
                        'no_context' => 'No Context Fields',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] === 'has_context') {
                            return $query->whereHas('contextualFields');
                        } elseif ($data['value'] === 'no_context') {
                            return $query->whereDoesntHave('contextualFields');
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('manage_context')
                    ->label('Add Context Field')
                    ->icon('heroicon-o-plus-circle')
                    ->form(function ($record) use ($fields) {
                        // Get mapped field IDs to exclude them
                        $mappedFieldIds = $record->formFields->pluck('id')->toArray();
                        
                        // Get currently selected contextual fields
                        $currentContextFieldIds = $record->contextualFields->pluck('id')->toArray();
                        
                        // Filter out mapped fields from available options
                        $availableFields = $fields->filter(function ($field) use ($mappedFieldIds) {
                            return !in_array($field->id, $mappedFieldIds);
                        });

                        return [
                            Forms\Components\Select::make('context_field_ids')
                                ->label('Context Fields')
                                ->options(function () use ($availableFields) {
                                    return $availableFields->mapWithKeys(function ($field) {
                                        $label = is_array($field->label) 
                                            ? ($field->label['en'] ?? reset($field->label)) 
                                            : $field->label;
                                        return [$field->id => $label . ' (' . $field->type . ')'];
                                    })->toArray();
                                })
                                ->multiple()
                                ->searchable()
                                ->default($currentContextFieldIds)
                                ->placeholder('Select context fields')
                                ->helperText('Select one or more contextual fields that provide additional context for this criterion. Mapped fields are excluded from this list.')
                                ->rules([
                                    function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            // Context fields are optional, so no validation needed
                                        };
                                    },
                                ]),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        $this->syncContextFields($record, $data['context_field_ids'] ?? []);
                    })
                    ->modalHeading('Manage Context Fields')
                    ->modalSubmitActionLabel('Save')
                    ->modalCancelActionLabel('Cancel')
                    ->visible(fn ($record) => $record->formFields->isNotEmpty()),

                Tables\Actions\Action::make('remove_context')
                    ->label('Remove Context')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove Context Fields')
                    ->modalDescription('Are you sure you want to remove all contextual fields for this criterion?')
                    ->action(function ($record) {
                        $record->contextualFields()->detach();
                        Notification::make()
                            ->title('Context Removed')
                            ->body('All contextual fields for this criterion have been removed.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->contextualFields->isNotEmpty()),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('clear_all_context')
                    ->label('Clear All Context')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Clear All Context Fields')
                    ->modalDescription('Are you sure you want to remove all contextual fields for the selected criteria?')
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            $record->contextualFields()->detach();
                        }
                        Notification::make()
                            ->title('Context Cleared')
                            ->body('All contextual fields for selected criteria have been removed.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No Assessment Criteria with Field Mappings')
            ->emptyStateDescription('Please map fields to assessment criteria first in the Field Mapping section.')
            ->modifyQueryUsing(function ($query) {
                // Only show criteria that have field mappings
                return $query->where('status', 'active')
                    ->whereHas('formFields')
                    ->orderBy('sort_order');
            });
    }

    protected function syncContextFields(FormAssessmentCriterion $criterion, array $contextFieldIds): void
    {
        // Validate that fields belong to the same form
        $formId = $this->ownerRecord->form_id;
        $validFieldIds = FormField::where('form_id', $formId)
            ->whereIn('id', $contextFieldIds)
            ->pluck('id')
            ->toArray();

        // Check if any selected fields are mapped fields (should not happen, but validate anyway)
        $mappedFieldIds = $criterion->formFields->pluck('id')->toArray();
        $conflictingFields = array_intersect($validFieldIds, $mappedFieldIds);
        
        if (!empty($conflictingFields)) {
            Notification::make()
                ->title('Invalid Selection')
                ->body('Cannot select mapped fields as contextual fields. Please select different fields.')
                ->danger()
                ->send();
            return;
        }

        // Sync the contextual fields
        try {
            $criterion->contextualFields()->sync($validFieldIds);

            $fieldCount = count($validFieldIds);
            if ($fieldCount > 0) {
                Notification::make()
                    ->title('Context Saved')
                    ->body("Successfully configured {$fieldCount} contextual field(s) for this criterion.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Context Cleared')
                    ->body('All contextual fields have been removed from this criterion.')
                    ->info()
                    ->send();
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle duplicate key constraint violation
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate')) {
                Notification::make()
                    ->title('Duplicate Mapping')
                    ->body('Duplicate contextual field mapping not allowed.')
                    ->danger()
                    ->send();
            } else {
                Notification::make()
                    ->title('Error')
                    ->body('An error occurred while saving the contextual fields.')
                    ->danger()
                    ->send();
            }
        }
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        // Only show if there's at least one active assessment criterion with field mappings
        $hasCriteriaWithMappings = FormAssessmentCriterion::where('form_id', $ownerRecord->form_id)
            ->where('status', 'active')
            ->whereHas('formFields')
            ->exists();
        
        return $hasCriteriaWithMappings;
    }
}

