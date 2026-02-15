<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormAiHintsResource\Pages;
use App\Models\FormAiEnhancementConfig;
use App\Models\UserCompetition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FormAiHintsResource extends Resource
{
    protected static ?string $model = FormAiEnhancementConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $navigationGroup = 'AI Agent';
    protected static ?string $navigationLabel = 'AI Hints';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Select Form / اختر النموذج')
                ->schema([
                    Forms\Components\Select::make('competition_id')
                        ->label('Program / البرنامج')
                        ->options(function () {
                            $user = auth()->user();
                            $currentCompetitionId = currentCompetitionId();

                            if ($user->isSuperAdmin()) {
                                $competitions = \App\Models\Competition::pluck('title', 'id')->toArray();
                            } else {
                                $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
                                    ->pluck('competition_id')
                                    ->toArray();

                                $competitions = \App\Models\Competition::whereIn('id', $supervisorCompetitions)
                                    ->pluck('title', 'id')
                                    ->toArray();
                            }

                            // Prioritize current competition - move it to the top
                            if ($currentCompetitionId && isset($competitions[$currentCompetitionId])) {
                                $currentTitle = $competitions[$currentCompetitionId];
                                unset($competitions[$currentCompetitionId]);
                                $competitions = [$currentCompetitionId => $currentTitle] + $competitions;
                            }

                            return $competitions;
                        })
                        ->default(function () {
                            return currentCompetitionId();
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
                        ->disabled(fn (callable $get) => !$get('competition_id'))
                        ->helperText('Please select a form type / يرجى اختيار نوع النموذج'),

                    Forms\Components\Select::make('form_id')
                        ->label('Form / النموذج')
                        ->placeholder('Select form / اختر النموذج')
                        ->options(function (callable $get) {
                            $competitionId = $get('competition_id');
                            $formType = $get('form_type');

                            if (!$competitionId || !$formType) {
                                return [];
                            }

                            $query = \App\Models\Form::where('type', $formType)
                                ->where('competition_id', $competitionId)
                                ->active()
                                ->where('is_archived', false);

                            // Show all forms (no restriction)
                            // Forms can have AI Enhancement config independently

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

            Forms\Components\Section::make('AI Enhancement / تحسين الذكاء الاصطناعي')
                ->schema([
                    Forms\Components\Toggle::make('ai_enhancement_enabled')
                        ->label('Enable AI Enhancement / تفعيل تحسين الذكاء الاصطناعي')
                        ->default(false)
                        ->columnSpanFull()
                        ->helperText('Allow participants to enhance their form submissions using AI / السماح للمشاركين بتحسين إجاباتهم باستخدام الذكاء الاصطناعي')
                        ->live(),

                    Forms\Components\Repeater::make('ai_enhancement_fields')
                        ->label('Fields with Instructions / الحقول مع التعليمات')
                        ->schema([
                            Forms\Components\Select::make('slug')
                                ->label('Field / الحقل')
                                ->options(function (callable $get) {
                                    $formId = $get('../../form_id');
                                    if (!$formId) {
                                        return [];
                                    }

                                    $form = \App\Models\Form::with('fields')->find($formId);
                                    if (!$form) {
                                        return [];
                                    }

                                    // Only show text and textarea fields that can be enhanced
                                    $enhanceableTypes = ['text', 'textarea', 'email', 'url'];

                                    return $form->fields()
                                        ->whereIn('type', $enhanceableTypes)
                                        ->orderBy('sort')
                                        ->get()
                                        ->unique('id')
                                        ->mapWithKeys(function ($field) {
                                            $label = is_array($field->label)
                                                ? ($field->label['en'] ?? reset($field->label))
                                                : $field->label;
                                            return [$field->slug => $label];
                                        })
                                        ->toArray();
                                })
                                ->required()
                                ->searchable()
                                ->reactive()
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('instructions')
                                ->label('Instructions / التعليمات')
                                ->placeholder('Enter specific instructions for this field (e.g., "Improve clarity and professionalism")')
                                ->rows(2)
                                ->required()
                                ->columnSpanFull(),
                            Forms\Components\Select::make('context')
                                ->label('Context Field / حقل السياق')
                                ->options(function (callable $get) {
                                    $formId = $get('../../form_id');
                                    if (!$formId) {
                                        return [];
                                    }

                                    $form = \App\Models\Form::with('fields')->find($formId);
                                    if (!$form) {
                                        return [];
                                    }

                                    // Show all fields that can provide context
                                    return $form->fields()
                                        ->orderBy('sort')
                                        ->get()
                                        ->unique('id')
                                        ->mapWithKeys(function ($field) {
                                            $label = is_array($field->label)
                                                ? ($field->label['en'] ?? reset($field->label))
                                                : $field->label;
                                            return [$field->slug => $label];
                                        })
                                        ->toArray();
                                })
                                ->searchable()
                                ->placeholder('Select a field to use as context / اختر حقل ليكون سياق')
                                ->helperText('The value of this field will be sent as context to help AI understand better / قيمة هذا الحقل سترسل كسياق لمساعدة الذكاء الاصطناعي')
                                ->columnSpanFull(),
                        ])
                        ->columns(1)
                        ->columnSpanFull()
                        ->visible(fn (callable $get) => $get('ai_enhancement_enabled') && $get('form_id'))
                        ->helperText('Add fields with specific instructions and context. Leave empty to enhance all text/textarea fields with default instructions. / أضف حقول مع تعليمات وسياق محدد. اتركه فارغًا لتحسين جميع حقول النص بتعليمات افتراضية.')
                        ->itemLabel(fn (array $state): ?string => $state['slug'] ?? null)
                        ->defaultItems(0)
                        ->collapsible()
                        ->disabled(fn (callable $get) => !$get('form_id')),
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
                $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
                    ->pluck('competition_id')
                    ->toArray();

                return $query->whereHas('form.competition', function ($q) use ($supervisorCompetitions) {
                    $q->whereIn('id', $supervisorCompetitions);
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('form.competition.title')
                    ->label('Program')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('form.type')
                    ->label('Form Type')
                    ->formatStateUsing(fn ($state) => \App\Models\Form::getAvailableFormTypes()[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('form.name')
                    ->label('Form')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return $state['en'] ?? reset($state);
                        }
                        return $state;
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('ai_enhancement_enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ai_enhancement_fields_count')
                    ->label('Fields Count')
                    ->getStateUsing(function ($record) {
                        $fields = $record->ai_enhancement_fields ?? [];
                        return is_array($fields) ? count($fields) : 0;
                    })
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('competition_id')
                    ->label('Program')
                    ->relationship('form.competition', 'title')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('form_type')
                    ->label('Form Type')
                    ->options(\App\Models\Form::getAvailableFormTypes())
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('form', function ($q) use ($data) {
                                $q->where('type', $data['value']);
                            });
                        }
                    }),

                Tables\Filters\TernaryFilter::make('ai_enhancement_enabled')
                    ->label('AI Enhancement Enabled')
                    ->placeholder('All')
                    ->trueLabel('Enabled')
                    ->falseLabel('Disabled'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Delete')
                    ->modalHeading('Delete')
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->title('Deleted / تم الحذف')
                            ->body('Record deleted successfully. / تم الحذف بنجاح.')
                            ->success()
                    )
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete')
                        ->successNotification(
                            \Filament\Notifications\Notification::make()
                                ->title('Deleted / تم الحذف')
                                ->body('Records deleted successfully. / تم الحذف بنجاح.')
                                ->success()
                        ),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // No relations needed for AI Enhancements
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFormAiHints::route('/'),
            'create' => Pages\CreateFormAiHints::route('/create'),
            'view' => Pages\ViewFormAiHints::route('/{record}'),
            'edit' => Pages\EditFormAiHints::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view FormAiEnhancementConfig') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create FormAiEnhancementConfig') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update FormAiEnhancementConfig');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete FormAiEnhancementConfig');
    }

    public static function getPluralModelLabel(): string
    {
        return 'AI Enhancements';
    }

    public static function getModelLabel(): string
    {
        return 'AI Enhancement';
    }
}
