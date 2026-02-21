<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomDashboardResource\Pages;
use App\Models\Dashboard;
use App\Models\Program;
use App\Models\FormField;
use App\Services\DashboardAggregationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CustomDashboardResource extends Resource
{
    protected static ?string $model = Dashboard::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'dashboards';

    public static function getNavigationLabel(): string
    {
        return __('dashboard.dashboards');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.dashboard');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.dashboards');
    }

    public static function form(Form $form): Form
    {
        return $form->schema(static::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make(__('dashboard.dashboard_name'))
                ->schema([
                    Forms\Components\TextInput::make('name.en')
                        ->label(__('dashboard.dashboard_name_en'))
                        ->placeholder(__('dashboard.enter_dashboard_name'))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('name.ar')
                        ->label(__('dashboard.dashboard_name_ar'))
                        ->placeholder(__('dashboard.enter_dashboard_name'))
                        ->required()
                        ->maxLength(255)
                        ->extraInputAttributes(['dir' => 'rtl']),

                    Forms\Components\Textarea::make('description.en')
                        ->label(__('dashboard.description_en'))
                        ->rows(2),

                    Forms\Components\Textarea::make('description.ar')
                        ->label(__('dashboard.description_ar'))
                        ->rows(2)
                        ->extraInputAttributes(['dir' => 'rtl']),
                ])
                ->columns(2),

            Forms\Components\Section::make(__('dashboard.data_source'))
                ->schema([
                    Forms\Components\CheckboxList::make('data_sources')
                        ->label(__('dashboard.data_source'))
                        ->options([
                            'applications' => __('dashboard.data_source_applications'),
                            'projects' => __('dashboard.data_source_projects'),
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('widgets', [])),
                ]),

            Forms\Components\Section::make(__('dashboard.widgets'))
                ->schema([
                    Forms\Components\Repeater::make('widgets')
                        ->label(__('dashboard.widgets'))
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('form_field_id')
                                ->label(__('dashboard.parameter'))
                                ->options(function (Forms\Get $get) {
                                    $dataSources = $get('../../data_sources') ?? [];
                                    if (empty($dataSources)) {
                                        return [];
                                    }

                                    $programId = currentProgramId();
                                    $fields = DashboardAggregationService::getAvailableFields($dataSources, $programId);

                                    return $fields->mapWithKeys(function ($field) {
                                        $label = $field->getTranslation('label', app()->getLocale()) ?? $field->slug;
                                        $formName = $field->form?->getTranslation('name', app()->getLocale()) ?? '';
                                        return [$field->id => "{$formName} → {$label}"];
                                    })->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    if ($state) {
                                        $field = FormField::find($state);
                                        if ($field) {
                                            $set('parameter_key', $field->slug);
                                        }
                                    }
                                }),

                            Forms\Components\Hidden::make('parameter_key'),

                            Forms\Components\Select::make('aggregation_type')
                                ->label(__('dashboard.aggregation_type'))
                                ->options(function (Forms\Get $get) {
                                    $fieldId = $get('form_field_id');
                                    if (!$fieldId) {
                                        return [
                                            'count' => __('dashboard.agg_count'),
                                        ];
                                    }

                                    $field = FormField::find($fieldId);
                                    if (!$field) {
                                        return ['count' => __('dashboard.agg_count')];
                                    }

                                    return Dashboard::getAggregationOptionsForFieldType($field->type);
                                })
                                ->required()
                                ->live(),

                            Forms\Components\Select::make('visualization_type')
                                ->label(__('dashboard.visualization_type'))
                                ->options([
                                    'bar' => __('dashboard.viz_bar'),
                                    'pie' => __('dashboard.viz_pie'),
                                    'line' => __('dashboard.viz_line'),
                                    'table' => __('dashboard.viz_table'),
                                    'kpi' => __('dashboard.viz_kpi'),
                                ])
                                ->required(),

                            Forms\Components\TextInput::make('sort_order')
                                ->label(__('dashboard.sort_order'))
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->cloneable()
                        ->reorderable()
                        ->orderColumn('sort_order')
                        ->addActionLabel(__('dashboard.add_widget'))
                        ->defaultItems(0)
                        ->itemLabel(function (array $state): ?string {
                            if (!empty($state['form_field_id'])) {
                                $field = FormField::find($state['form_field_id']);
                                if ($field) {
                                    $label = $field->getTranslation('label', app()->getLocale());
                                    $vizType = $state['visualization_type'] ?? '';
                                    return "{$label} ({$vizType})";
                                }
                            }
                            return null;
                        }),
                ]),

            Forms\Components\Section::make(__('dashboard.filters'))
                ->schema([
                    Forms\Components\Select::make('group_by')
                        ->label(__('dashboard.group_by'))
                        ->placeholder(__('dashboard.select_group_by'))
                        ->options(function (Forms\Get $get) {
                            $dataSources = $get('data_sources') ?? [];
                            if (empty($dataSources)) {
                                return [];
                            }

                            $programId = currentProgramId();
                            $fields = DashboardAggregationService::getAvailableFields($dataSources, $programId);

                            return $fields->mapWithKeys(function ($field) {
                                $label = $field->getTranslation('label', app()->getLocale()) ?? $field->slug;
                                return [$field->slug => $label];
                            })->toArray();
                        })
                        ->searchable()
                        ->nullable(),
                ])
                ->collapsible()
                ->collapsed(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Dashboard::query()->where('program_id', currentProgramId())->orWhereNull('program_id'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('dashboard.dashboard_name'))
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', app()->getLocale())),

                Tables\Columns\TextColumn::make('data_sources')
                    ->label(__('dashboard.data_source'))
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $sources = $record->data_sources ?? [];
                        $labels = [
                            'applications' => __('dashboard.data_source_applications'),
                            'projects' => __('dashboard.data_source_projects'),
                        ];
                        return collect($sources)->map(fn($s) => $labels[$s] ?? $s)->toArray();
                    }),

                Tables\Columns\TextColumn::make('widgets_count')
                    ->label(__('dashboard.widgets'))
                    ->counts('widgets')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('dashboard.created_by'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('dashboard.created_date'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('dashboard.last_modified'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->isArchived()),

                Tables\Actions\Action::make('duplicate')
                    ->label(__('dashboard.duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->duplicate();
                        Notification::make()
                            ->title(__('dashboard.dashboard_duplicated'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('archive')
                    ->label(__('dashboard.archive'))
                    ->color('warning')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->modalHeading(__('dashboard.confirm_archive'))
                    ->action(function ($record) {
                        $record->archive();
                        Notification::make()
                            ->title(__('dashboard.dashboard_archived'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->isArchived()),

                Tables\Actions\Action::make('restore')
                    ->label(__('dashboard.restore'))
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->restore();
                        Notification::make()
                            ->title(__('dashboard.dashboard_restored'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->isArchived()),

                Tables\Actions\DeleteAction::make()
                    ->modalHeading(__('dashboard.confirm_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_archived')
                    ->label('Status')
                    ->options([
                        '0' => __('dashboard.status_active'),
                        '1' => __('dashboard.status_archived'),
                    ]),
            ])
            ->emptyStateHeading(__('dashboard.no_dashboards'))
            ->emptyStateIcon('heroicon-o-chart-bar-square');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomDashboards::route('/'),
            'create' => Pages\CreateCustomDashboard::route('/create'),
            'view' => Pages\ViewCustomDashboard::route('/{record}'),
            'edit' => Pages\EditCustomDashboard::route('/{record}/edit'),
        ];
    }

    // Authorization
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Dashboard') || auth()->user()?->hasRole('super_admin') || true;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create Dashboard') || auth()->user()?->hasRole('super_admin') || true;
    }

    public static function canEdit(Model $record): bool
    {
        if ($record->isArchived()) return false;
        return auth()->user()?->can('update Dashboard') || auth()->user()?->hasRole('super_admin') || true;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete Dashboard') || auth()->user()?->hasRole('super_admin') || true;
    }
}
