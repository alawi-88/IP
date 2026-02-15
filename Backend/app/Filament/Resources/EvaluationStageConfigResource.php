<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationStageConfigResource\Pages;
use App\Models\EvaluationStageConfig;
use App\Models\Form;
use App\Models\Track;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;

class EvaluationStageConfigResource extends Resource
{
    protected static ?string $model = EvaluationStageConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Form Configs';
    protected static ?string $navigationLabel = 'Evaluation Stages';

    protected static ?int $navigationSort = 40;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Evaluation Stages Configuration')
                    ->schema([
                        Forms\Components\Select::make('competition_id')
                            ->label('Program')
                            ->options(fn() => \App\Models\Competition::pluck('title', 'id')->toArray())
                            ->searchable()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('stages')
                            ->label('Stages Setup')
                            ->schema([
                                Forms\Components\Hidden::make('stage_number')
                                    ->default(fn($state, callable $get, callable $set, callable $livewire) => count($livewire->data['stages'] ?? []))
                                    ->dehydrated(true),

                                Forms\Components\Select::make('evaluation_form_id')
                                    ->label('Evaluation Form')
                                    ->options(
                                        fn(callable $get) =>
                                        Form::availableEvaluationForms(
                                            (int) $get('../../competition_id'),
                                            $get('../../stages') ?? [],
                                            $get('../../') ?? [],
                                        )
                                    )
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->preload()
                                    ->columnSpanFull()
                                    ->getOptionLabelUsing(
                                        function ($value) {
                                            $form = \App\Models\Form::find($value);
                                            if (! $form) {
                                                return '';
                                            }

                                            $name = is_array($form->name)
                                                ? ($form->name['en'] ?? reset($form->name))
                                                : $form->name;

                                            return $name ?: 'Untitled #' . $form->id;
                                        }
                                    ),

                                Forms\Components\Toggle::make('apply_to_all_tracks')
                                    ->label('Apply to All Tracks')
                                    ->default(true)
                                    ->reactive()
                                    ->dehydrated(true)
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $set('track_ids', []);
                                        }
                                    }),

                                Forms\Components\Select::make('track_ids')
                                    ->label('Specific Tracks')
                                    ->multiple()
                                    ->options(fn() => Track::pluck('name', 'id')->toArray())
                                    ->visible(fn($get) => !$get('apply_to_all_tracks'))
                                    ->columnSpanFull()
                                    ->dehydrated(true),

                                Forms\Components\Select::make('submission_requirement')
                                    ->label('Submission Requirement')
                                    ->options(function (callable $get) {
                                        $stageNumber = (int) $get('stage_number');
                                        return $stageNumber === 1
                                            ? ['new' => 'New Submission Required']
                                            : [
                                                'new' => 'New Submission Required',
                                                'previous' => 'Use Previous Submission',
                                            ];
                                    })
                                    ->default('new')
                                    ->reactive()
                                    ->required()
                                    ->columnSpanFull()
                                    ->dehydrated(true),

                                Forms\Components\Select::make('previous_stage_number')
                                    ->label('Select Previous Stage')
                                    ->options(function (callable $get) {
                                        $currentStageNumber = $get('stage_number');

                                        if (!$currentStageNumber || $currentStageNumber <= 1) {
                                            return [];
                                        }

                                        return collect(range(1, $currentStageNumber - 1))
                                            ->mapWithKeys(fn($stage) => [$stage => "Stage {$stage}"]);
                                    })
                                    ->visible(fn($get) => $get('submission_requirement') === 'previous')
                                    ->required(fn($get) => $get('submission_requirement') === 'previous')
                                    ->columnSpanFull()
                                    ->dehydrated()

                            ])
                            ->columns()
                            ->minItems(1)
                            ->maxItems(4)
                            ->reorderable(false)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('competition.title')
                    ->label('Program')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('number_of_stages')
                    ->label('Number of Stages')
                    ->sortable()
                    ->getStateUsing(fn($record) => count($record->stages ?? [])),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([])
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
        return [];
    }



    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvaluationStageConfigs::route('/'),
            'create' => Pages\CreateEvaluationStageConfig::route('/create'),
            'edit' => Pages\EditEvaluationStageConfig::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view EvaluationStageConfig') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create EvaluationStageConfig') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update EvaluationStageConfig');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete EvaluationStageConfig');
    }
}
