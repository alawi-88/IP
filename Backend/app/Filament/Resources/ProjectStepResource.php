<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectStepResource\Pages;
use App\Filament\Resources\ProjectStepResource\RelationManagers;
use App\Models\FormField;
use App\Models\ProjectStep;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;

class ProjectStepResource extends Resource
{
    protected static ?string $model = ProjectStep::class;

    // Managed via Competition Hub
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Forms & Configuration';

    protected static ?string $navigationLabel = 'Project Steps';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Step Information')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('form_id')
                                ->label('Form')
                                ->options(\App\Models\Form::projecttype()->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->placeholder('Select a form')
                                ->columnSpan(6),

                            Forms\Components\TextInput::make('step_order')
                                ->label('Step Order')
                                ->required()
                                ->numeric()
                                ->placeholder('Enter step order')
                                ->helperText('The order in which this step will appear.')
                                ->default(1)
                                ->minValue(1)
                                ->columnSpan(6)
                                ->rules(function (callable $get) {
                                    $formId = $get('form_id');
                                    return [
                                        Rule::unique('project_steps', 'step_order')
                                            ->where(fn($query) => $query->where('form_id', $formId)),
                                    ];
                                }),
                        ])->columns(12),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('name.en')
                                ->label('Step Name (English)')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Enter step name in English')
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('name.ar')
                                ->label('Step Name (Arabic)')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('أدخل اسم الخطوة بالعربية')
                                ->columnSpan(6),
                        ])->columns(12),

                        Forms\Components\MultiSelect::make('field_ids')
                            ->label('Fields')
                            ->options(function (callable $get, callable $set, $livewire = null) {
                                $formId = $get('form_id');
                                $currentStepId = $get('id'); // Only works when editing

                                if (!$formId) return [];

                                $usedFieldIds = ProjectStep::where('form_id', $formId)
                                    ->when($currentStepId, fn($query) => $query->where('id', '!=', $currentStepId))
                                    ->get()
                                    ->flatMap(function ($step) {
                                        return collect($step->field_ids ?? [])->values();
                                    })
                                    ->unique()
                                    ->toArray();

                                return FormField::where('form_id', $formId)
                                    ->whereNotIn('id', $usedFieldIds)
                                    ->orderBy('label->en')
                                    ->pluck('label', 'id')
                                    ->mapWithKeys(function ($label, $id) {
                                        if (is_array($label)) {
                                            return [$id => $label['en'] ?? $label['ar'] ?? 'Field #' . $id];
                                        }
                                        return [$id => $label];
                                    })
                                    ->toArray();
                            })
                            ->reactive()
                            ->required()
                            ->helperText('Select the fields that belong to this step. Already-used fields are hidden.'),


                    ]),

            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('step_order')->sortable(),
                Tables\Columns\TextColumn::make('form.name')->label('Form')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectSteps::route('/'),
            'create' => Pages\CreateProjectStep::route('/create'),
            'edit' => Pages\EditProjectStep::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view ProjectStep') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create ProjectStep') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update ProjectStep');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete ProjectStep');
    }
}
