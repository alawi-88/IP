<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormAiScoringConfigResource\Pages;
use App\Filament\Resources\FormAiScoringConfigResource\RelationManagers;
use App\Models\FormAiScoringConfig;
use App\Models\FormAiEnhancementConfig;
use App\Models\UserProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FormAiScoringConfigResource extends Resource
{
    protected static ?string $model = FormAiScoringConfig::class;

    protected static ?string $navigationIcon = 'heroicon-s-sparkles';
    protected static ?string $navigationGroup = 'AI & Automation';
    protected static ?string $navigationLabel = 'AI Scoring';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Select Form / اختر النموذج')
                ->schema([
                    Forms\Components\Select::make('program_id')
                        ->label('Program / البرنامج')
                        ->options(function () {
                            $user = auth()->user();
                            $currentProgramId = currentProgramId();

                            if ($user->isSuperAdmin()) {
                                $programs = \App\Models\Program::pluck('title', 'id')->toArray();
                            } else {
                                $supervisorPrograms = UserProgram::where('user_id', $user->id)
                                    ->pluck('program_id')
                                    ->toArray();

                                $programs = \App\Models\Program::whereIn('id', $supervisorPrograms)
                                    ->pluck('title', 'id')
                                    ->toArray();
                            }

                            // Prioritize current program - move it to the top
                            if ($currentProgramId && isset($programs[$currentProgramId])) {
                                $currentTitle = $programs[$currentProgramId];
                                unset($programs[$currentProgramId]);
                                $programs = [$currentProgramId => $currentTitle] + $programs;
                            }

                            return $programs;
                        })
                        ->default(function () {
                            return currentProgramId();
                        })
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('form_type', null))
                        ->columnSpanFull()
                        ->helperText('Please select a program / يرجى اختيار برنامج'),

                    Forms\Components\Select::make('form_type')
                        ->label('Form Type / نوع النموذج')
                        ->placeholder('Select form type / اختر نوع النموذج')
                        ->options(\App\Models\Form::getAvailableFormTypes())
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('form_id', null))
                        ->disabled(fn (callable $get) => !$get('program_id'))
                        ->helperText('Please select a form type / يرجى اختيار نوع النموذج'),

                    Forms\Components\Select::make('form_id')
                        ->label('Form / النموذج')
                        ->placeholder('Select form / اختر النموذج')
                        ->options(function (callable $get) {
                            $programId = $get('program_id');
                            $formType = $get('form_type');
                            
                            if (!$programId || !$formType) {
                                return [];
                            }

                            $query = \App\Models\Form::where('type', $formType)
                                ->where('program_id', $programId)
                                ->active()
                                ->where('is_archived', false);

                            // Exclude forms with existing configs (will be handled in Edit page)
                            $existingConfigs = FormAiScoringConfig::pluck('form_id')->toArray();
                            $query->whereNotIn('id', $existingConfigs);

                            return $query->get()->mapWithKeys(function ($form) {
                                $name = is_array($form->name) 
                                    ? ($form->name['en'] ?? reset($form->name)) 
                                    : $form->name;
                                return [$form->id => $name];
                            });
                        })
                        ->required()
                        ->live()
                        ->disabled(fn (callable $get) => !$get('form_type'))
                        ->helperText('Please select a form / يرجى اختيار النموذج'),
                ])
                ->columns(1),

            Forms\Components\Section::make('AI Configuration / إعدادات الذكاء الاصطناعي')
                ->schema([
                    Forms\Components\Textarea::make('ai_prompt')
                        ->label('AI Prompt / توجيه الذكاء الاصطناعي')
                        ->placeholder('Enter AI prompt (e.g., "You are an expert on fintech…")')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull()
                        ->helperText('Enter AI prompt (e.g., "You are an expert on fintech…")'),

                    Forms\Components\TextInput::make('total_weight')
                        ->label('Total Weight / الوزن الإجمالي')
                        ->placeholder('Enter total weight (e.g., 100) / أدخل الوزن الإجمالي')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->default(100)
                        ->helperText('Total weight for this form / الوزن الإجمالي لهذا النموذج'),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $user = auth()->user();
                if ($user->isSuperAdmin()) {
                    return $query;
                }
                $supervisorPrograms = UserProgram::where('user_id', $user->id)
                    ->pluck('program_id')
                    ->toArray();

                return $query->whereHas('form.program', function ($q) use ($supervisorPrograms) {
                    $q->whereIn('id', $supervisorPrograms);
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('form.program.title')
                    ->label('Program / البرنامج')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('form.type')
                    ->label('Form Type / نوع النموذج')
                    ->formatStateUsing(fn ($state) => \App\Models\Form::getAvailableFormTypes()[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('form.name')
                    ->label('Form / النموذج')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return $state['en'] ?? reset($state);
                        }
                        return $state;
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_weight')
                    ->label('Total Weight / الوزن الإجمالي')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('allocated_weight')
                    ->label('Allocated / المخصص')
                    ->getStateUsing(function ($record) {
                        return $record->activeAssessmentCriteria()->sum('weight');
                    })
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('remaining_weight')
                    ->label('Remaining / المتبقي')
                    ->getStateUsing(function ($record) {
                        $allocated = $record->activeAssessmentCriteria()->sum('weight');
                        return max(0, $record->total_weight - $allocated);
                    })
                    ->alignEnd()
                    ->color(function ($record) {
                        $allocated = $record->activeAssessmentCriteria()->sum('weight');
                        $remaining = max(0, $record->total_weight - $allocated);
                        return $remaining > 0 ? 'warning' : 'success';
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At / تم الإنشاء في')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('program_id')
                    ->label('Program / البرنامج')
                    ->relationship('form.program', 'title')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('form_type')
                    ->label('Form Type / نوع النموذج')
                    ->options(\App\Models\Form::getAvailableFormTypes())
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('form', function ($q) use ($data) {
                                $q->where('type', $data['value']);
                            });
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // Check if AI Enhancement config exists and is enabled
                        $enhancementConfig = FormAiEnhancementConfig::where('form_id', $record->form_id)->first();
                        if ($enhancementConfig && $enhancementConfig->ai_enhancement_enabled) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot Delete / لا يمكن الحذف')
                                ->body('Cannot delete AI Scoring configuration because AI Enhancement is enabled. Please delete AI Enhancements first from the AI Enhancements section. / لا يمكن حذف إعدادات تقييم الذكاء الاصطناعي لأن تحسين الذكاء الاصطناعي مفعّل. يرجى حذف تحسينات الذكاء الاصطناعي أولاً من قسم تحسينات الذكاء الاصطناعي.')
                                ->danger()
                                ->send();
                            
                            // Prevent deletion
                            throw new \Filament\Support\Exceptions\Halt();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Check if any record has AI Enhancement enabled
                            $formIds = $records->pluck('form_id')->toArray();
                            $enhancementConfigs = FormAiEnhancementConfig::whereIn('form_id', $formIds)
                                ->where('ai_enhancement_enabled', true)
                                ->exists();
                            
                            if ($enhancementConfigs) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Cannot Delete / لا يمكن الحذف')
                                    ->body('Cannot delete AI Scoring configurations because some records have AI Enhancement enabled. Please delete AI Enhancements first from the AI Enhancements section. / لا يمكن حذف إعدادات تقييم الذكاء الاصطناعي لأن بعض السجلات لديها تحسين الذكاء الاصطناعي مفعّل. يرجى حذف تحسينات الذكاء الاصطناعي أولاً من قسم تحسينات الذكاء الاصطناعي.')
                                    ->danger()
                                    ->send();
                                
                                // Prevent deletion
                                throw new \Filament\Support\Exceptions\Halt();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\FormAiScoringConfigResource\RelationManagers\AssessmentCriteriaRelationManager::class,
            \App\Filament\Resources\FormAiScoringConfigResource\RelationManagers\FieldMappingRelationManager::class,
            \App\Filament\Resources\FormAiScoringConfigResource\RelationManagers\ContextRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFormAiScoringConfigs::route('/'),
            'create' => Pages\CreateFormAiScoringConfig::route('/create'),
            'view' => Pages\ViewFormAiScoringConfig::route('/{record}'),
            'edit' => Pages\EditFormAiScoringConfig::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view FormAiScoringConfig') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create FormAiScoringConfig') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update FormAiScoringConfig');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete FormAiScoringConfig');
    }
}

