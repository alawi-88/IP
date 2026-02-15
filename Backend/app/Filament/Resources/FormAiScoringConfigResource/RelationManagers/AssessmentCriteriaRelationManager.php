<?php

namespace App\Filament\Resources\FormAiScoringConfigResource\RelationManagers;

use App\Models\FormAssessmentCriterion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AssessmentCriteriaRelationManager extends RelationManager
{
    protected static string $relationship = 'assessmentCriteria';

    protected static ?string $title = 'Assessment Criteria';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Criteria Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter criteria name'),

                Forms\Components\Textarea::make('description')
                    ->label('Criteria Description')
                    ->required()
                    ->rows(3)
                    ->placeholder('Enter criteria description'),

                Forms\Components\Textarea::make('instruction')
                    ->label('Instruction')
                    ->helperText('What the agent should do for this criterion')
                    ->required()
                    ->rows(2)
                    ->placeholder('Enter instruction'),

                Forms\Components\TextInput::make('weight')
                    ->label('Weight')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->rules([
                        function ($get, $livewire) {
                            return function (string $attribute, $value, \Closure $fail) use ($get, $livewire) {
                                $status = $get('status') ?? 'active';
                                // Only validate weight if status is active
                                if ($status === 'active') {
                                    $totalWeight = $this->ownerRecord->total_weight ?? 100;
                                    
                                    // Basic validation: weight must not exceed total weight
                                    if ($value > $totalWeight) {
                                        $fail("Weight must not exceed total weight ({$totalWeight}). / يجب ألا يتجاوز الوزن الوزن الإجمالي ({$totalWeight}).");
                                        return;
                                    }
                                    
                                    // Calculate remaining weight
                                    $query = FormAssessmentCriterion::where('form_id', $this->ownerRecord->form_id)
                                        ->where('status', 'active');
                                    
                                    // If editing, exclude the current record from allocated weight calculation
                                    $recordId = null;
                                    if (isset($livewire->mountedTableActionRecord)) {
                                        $record = $livewire->mountedTableActionRecord;
                                        $recordId = is_object($record) ? ($record->id ?? null) : $record;
                                    } elseif (isset($livewire->mountedTableActionData['id'])) {
                                        $recordId = $livewire->mountedTableActionData['id'];
                                    } elseif (method_exists($livewire, 'getMountedTableActionRecord')) {
                                        $record = $livewire->getMountedTableActionRecord();
                                        $recordId = is_object($record) ? ($record->id ?? null) : $record;
                                    }
                                    
                                    if ($recordId !== null) {
                                        $query->where('id', '!=', $recordId);
                                    }
                                    
                                    $currentAllocated = $query->sum('weight');
                                    $remainingWeight = max(0, $totalWeight - $currentAllocated);
                                    
                                    // Validate against remaining weight
                                    if ($value > $remainingWeight) {
                                        $fail("Weight exceeds remaining weight ({$remainingWeight}). / يتجاوز الوزن الوزن المتبقي ({$remainingWeight}).");
                                        return;
                                    }
                                }
                            };
                        },
                    ])
                    ->placeholder('Enter criteria weight')
                    ->helperText(function () {
                        $totalWeight = $this->ownerRecord->total_weight ?? 100;
                        $currentAllocated = FormAssessmentCriterion::where('form_id', $this->ownerRecord->form_id)
                            ->where('status', 'active')
                            ->sum('weight');
                        $remainingWeight = max(0, $totalWeight - $currentAllocated);
                        return "Remaining weight: {$remainingWeight} / الوزن المتبقي: {$remainingWeight}";
                    })
                    ->reactive(),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'disabled' => 'Disabled',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Criteria Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),

                Tables\Columns\TextColumn::make('instruction')
                    ->label('Instruction')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->instruction)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('weight')
                    ->label('Weight')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->status === 'active' ? 'success' : 'gray')
                    ->formatStateUsing(fn ($record) => ucfirst($record->status)),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['form_id'] = $this->ownerRecord->form_id;
                        $maxSortOrder = FormAssessmentCriterion::where('form_id', $this->ownerRecord->form_id)
                            ->max('sort_order') ?? 0;
                        $data['sort_order'] = $maxSortOrder + 1;
                        return $data;
                    })
                    ->using(function (array $data): \Illuminate\Database\Eloquent\Model {
                        // Only validate weight if status is active
                        if (($data['status'] ?? 'active') === 'active') {
                            $this->validateWeightOnAdd($data['weight']);
                        }
                        $record = $this->getRelationship()->create($data);
                        $this->refreshWeightSummary();
                        return $record;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        // Only validate weight if status is active
                        if (($data['status'] ?? $record->status) === 'active') {
                            $this->validateWeightOnEdit($record, $data['weight']);
                        }
                        return $data;
                    })
                    ->after(function ($record) {
                        $this->refreshWeightSummary();
                    }),
                Tables\Actions\Action::make('disable')
                    ->label('Disable')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Disable Criterion')
                    ->modalDescription('Are you sure you want to disable this criterion? Its weight will be excluded from the allocated weight.')
                    ->visible(fn ($record) => $record->status === 'active')
                    ->action(function ($record) {
                        $record->update(['status' => 'disabled']);
                        $this->refreshWeightSummary();
                        Notification::make()
                            ->title('Criterion Disabled')
                            ->body('The criterion has been disabled successfully.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('enable')
                    ->label('Enable')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Enable Criterion')
                    ->modalDescription('Are you sure you want to enable this criterion? Make sure the total allocated weight does not exceed the total weight.')
                    ->visible(fn ($record) => $record->status === 'disabled')
                    ->action(function ($record) {
                        // Validate that enabling won't exceed total weight
                        $totalWeight = $this->ownerRecord->total_weight ?? 100;
                        $currentAllocated = FormAssessmentCriterion::where('form_id', $this->ownerRecord->form_id)
                            ->where('status', 'active')
                            ->sum('weight');
                        
                        if ($currentAllocated + $record->weight > $totalWeight) {
                            $remaining = $totalWeight - $currentAllocated;
                            Notification::make()
                                ->title('Cannot Enable Criterion / لا يمكن تفعيل المعيار')
                                ->body("Enabling this criterion would exceed the total weight. Remaining weight: {$remaining}. / تفعيل هذا المعيار سيتجاوز الوزن الإجمالي. الوزن المتبقي: {$remaining}.")
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $record->update(['status' => 'active']);
                        $this->refreshWeightSummary();
                        Notification::make()
                            ->title('Criterion Enabled')
                            ->body('The criterion has been enabled successfully.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        $this->refreshWeightSummary();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['form_id'] = $this->ownerRecord->form_id;
                        $maxSortOrder = FormAssessmentCriterion::where('form_id', $this->ownerRecord->form_id)
                            ->max('sort_order') ?? 0;
                        $data['sort_order'] = $maxSortOrder + 1;
                        return $data;
                    })
                    ->using(function (array $data): \Illuminate\Database\Eloquent\Model {
                        // Only validate weight if status is active
                        if (($data['status'] ?? 'active') === 'active') {
                            $this->validateWeightOnAdd($data['weight']);
                        }
                        $record = $this->getRelationship()->create($data);
                        $this->refreshWeightSummary();
                        return $record;
                    }),
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Criterion Information')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Criteria Name'),
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        TextEntry::make('instruction')
                            ->label('Instruction')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Weight & Status')
                    ->schema([
                        TextEntry::make('weight')
                            ->label('Weight')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($record) => $record->status === 'active' ? 'success' : 'gray')
                            ->formatStateUsing(fn ($record) => ucfirst($record->status)),
                        TextEntry::make('sort_order')
                            ->label('Sort Order'),
                    ])
                    ->columns(3),
            ]);
    }

    protected function refreshWeightSummary(): void
    {
        $allocated = FormAssessmentCriterion::where('form_id', $this->ownerRecord->form_id)
            ->where('status', 'active')
            ->sum('weight');
        $total = $this->ownerRecord->total_weight ?? 100;
        $remaining = max(0, $total - $allocated);

        $message = "Total Weight: {$total} | Allocated: {$allocated} | Remaining: {$remaining}";

        \Filament\Notifications\Notification::make()
            ->title('Weight Summary')
            ->body($message)
            ->success()
            ->send();
    }

    protected function validateWeight($value, string $attribute, \Closure $fail, ?int $excludeRecordId = null): void
    {
        $totalWeight = $this->ownerRecord->total_weight ?? 100;
        $query = FormAssessmentCriterion::where('form_id', $this->ownerRecord->form_id)
            ->where('status', 'active');
        
        // Exclude the current record if we're editing
        if ($excludeRecordId !== null) {
            $query->where('id', '!=', $excludeRecordId);
        }
        
        $currentAllocated = $query->sum('weight');

        if ($value > $totalWeight) {
            $fail("Weight must not exceed total weight ({$totalWeight}). / يجب ألا يتجاوز الوزن الوزن الإجمالي ({$totalWeight}).");
            return;
        }

        if ($currentAllocated + $value > $totalWeight) {
            $remaining = $totalWeight - $currentAllocated;
            $fail("Weight exceeds remaining weight ({$remaining}). / يتجاوز الوزن الوزن المتبقي ({$remaining}).");
        }
    }

    protected function validateWeightOnAdd(int $weight): void
    {
        $totalWeight = $this->ownerRecord->total_weight ?? 100;
        $currentAllocated = FormAssessmentCriterion::where('form_id', $this->ownerRecord->form_id)
            ->where('status', 'active')
            ->sum('weight');

        if ($weight > $totalWeight) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['weight' => ["Weight must not exceed total weight ({$totalWeight}). / يجب ألا يتجاوز الوزن الوزن الإجمالي ({$totalWeight})."]]
            );
        }

        if ($currentAllocated + $weight > $totalWeight) {
            $remaining = $totalWeight - $currentAllocated;
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['weight' => ["Weight exceeds remaining weight ({$remaining}). / يتجاوز الوزن الوزن المتبقي ({$remaining})."]]
            );
        }
    }

    protected function validateWeightOnEdit($record, int $newWeight): void
    {
        $totalWeight = $this->ownerRecord->total_weight ?? 100;
        $currentAllocated = FormAssessmentCriterion::where('form_id', $this->ownerRecord->form_id)
            ->where('status', 'active')
            ->where('id', '!=', $record->id)
            ->sum('weight');

        if ($newWeight > $totalWeight) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['weight' => ["Weight must not exceed total weight ({$totalWeight}). / يجب ألا يتجاوز الوزن الوزن الإجمالي ({$totalWeight})."]]
            );
        }

        if ($currentAllocated + $newWeight > $totalWeight) {
            $remaining = $totalWeight - $currentAllocated;
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['weight' => ["Weight exceeds remaining weight ({$remaining}). / يتجاوز الوزن الوزن المتبقي ({$remaining})."]]
            );
        }
    }
}

