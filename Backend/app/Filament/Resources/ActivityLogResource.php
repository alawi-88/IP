<?php

namespace App\Filament\Resources;

use App\Filament\Exports\ActivityLogExporter;
use App\Models\UserCompetition;
use Exception;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Rmsramos\Activitylog\Actions\Concerns\ActionContent;
use Rmsramos\Activitylog\ActivitylogPlugin;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;
use App\Filament\Resources\ActivitylogResource\Pages;
use App\Models\Competition;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Rmsramos\Activitylog\Traits\HasCustomActivityResource;
use Spatie\Activitylog\Models\Activity;
use Filament\Tables\Actions\ExportAction as TableExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Contracts\HasTable;
use Rmsramos\Activitylog\Resources\ActivitylogResource as BaseActivitylogResource;


class ActivitylogResource extends BaseActivitylogResource
{
    use ActionContent;

    protected static ?string $slug = 'activitylogs';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getModel(): string
    {
        return config('activitylog.activity_model', Activity::class);
    }

    protected static ?string $navigationGroup = 'System Logs';
    protected static ?string $label = 'Log';
    protected static ?string $pluralLabel = 'Logs';

    public static function getModelLabel(): string
    {
        return ActivitylogPlugin::get()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return ActivitylogPlugin::get()->getPluralLabel();
    }

    public static function getNavigationIcon(): string
    {
        return ActivitylogPlugin::get()->getNavigationIcon();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel()) ?? Str::title(static::getModelLabel());
    }

    public static function getNavigationSort(): ?int
    {
        return ActivitylogPlugin::get()->getNavigationSort();
    }

    public static function getNavigationGroup(): ?string
    {
        return ActivitylogPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationBadge(): ?string
    {
        return ActivitylogPlugin::get()->getNavigationCountBadge() ?
            number_format(static::getModel()::count()) : null;
    }

    public static function restoreActivity(int|string $key): void
    {
        $activity = Activity::find($key);

        if (! $activity) {
            Notification::make()
                ->title(__('activitylog::notifications.activity_not_found'))
                ->danger()
                ->send();

            return;
        }

        $oldProperties = data_get($activity, 'properties.old');
        $newProperties = data_get($activity, 'properties.attributes');

        if ($oldProperties === null) {
            Notification::make()
                ->title(__('activitylog::notifications.no_properties_to_restore'))
                ->danger()
                ->send();

            return;
        }

        try {
            $record = $activity->subject;

            if (! $record) {
                Notification::make()
                    ->title(__('activitylog::notifications.subject_not_found'))
                    ->danger()
                    ->send();

                return;
            }

            // Temporarily disable activity logging to prevent updated log
            activity()->withoutLogs(function () use ($record, $oldProperties) {
                $record->update($oldProperties);
            });

            // Log the restore event
            $user = auth()->user();

            if ($user) {
                activity()
                    ->performedOn($record)
                    ->causedBy(auth()->user())
                    ->withProperties(['attributes' => $oldProperties, 'old' => $newProperties])
                    ->tap(function ($log) {
                        $log->event = 'restored';
                    })
                    ->log('restored');
            }

            Notification::make()
                ->title(__('activitylog::notifications.activity_restored_successfully'))
                ->success()
                ->send();
        } catch (ModelNotFoundException $e) {
            Notification::make()
                ->title(__('activitylog::notifications.record_not_found'))
                ->danger()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->title(__('activitylog::notifications.failed_to_restore_activity', ['error' => $e->getMessage()]))
                ->danger()
                ->send();
        }
    }

    protected  static function getResourceUrl(Activity $record): string
    {
        $panelID = Filament::getCurrentPanel()->getId();

        if ($record->subject_type && $record->subject_id) {
            try {
                $model = app($record->subject_type);

                if (ActivityLogHelper::classUsesTrait($model, HasCustomActivityResource::class)) {
                    $resourceModel      = $model->getFilamentActualResourceModel($record);
                    $resourcePluralName = ActivityLogHelper::getResourcePluralName($resourceModel);

                    return route('filament.' . $panelID . '.resources.' . $resourcePluralName . '.edit', ['record' => $resourceModel->id]);
                }

                // Fallback to a standard resource mapping
                $resourcePluralName = ActivityLogHelper::getResourcePluralName($record->subject_type);

                return route('filament.' . $panelID . '.resources.' . $resourcePluralName . '.edit', ['record' => $record->subject_id]);
            } catch (Exception $e) {
                // If there's any error generating the URL, return placeholder
                return '#';
            }
        }

        return '#';
    }

    protected static function canViewResource(Activity $record): bool
    {
        if ($record->subject_type && $record->subject_id) {
            try {
                $model = app($record->subject_type);

                if (ActivityLogHelper::classUsesTrait($model, HasCustomActivityResource::class)) {
                    $resourceModel = $model->getFilamentActualResourceModel($record);
                    $user          = auth()->user();

                    return $user && $user->can('update', $resourceModel);
                }

                // Fallback to check if the user can edit the model using a generic policy
                $user = auth()->user();

                return $user && $record->subject && $user->can('update', $record->subject);
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Section::make([
                        TextInput::make('causer_id')
                            ->afterStateHydrated(function ($component, ?Model $record) {
                                /** @phpstan-ignore-next-line */
                                return $component->state($record?->causer?->name ?? '-');
                            })
                            ->label(__('activitylog::forms.fields.causer.label')),

                        TextInput::make('subject_type')
                            ->afterStateHydrated(function ($component, ?Model $record, $state) {
                                /** @var Activity $record */
                                return $state ? $component->state(Str::of($state)->afterLast('\\')->headline() . ' # ' . $record->subject_id) : $component->state('-');
                            })
                            ->label(__('activitylog::forms.fields.subject_type.label')),

                        Textarea::make('description')
                            ->label(__('activitylog::forms.fields.description.label'))
                            ->rows(2)
                            ->columnSpan('full'),
                    ]),
                    Section::make([
                        Placeholder::make('log_name')
                            ->content(function (?Model $record): string {
                                /** @var Activity $record */
                                return $record?->log_name ? ucwords($record->log_name) : '-';
                            })
                            ->label(__('activitylog::forms.fields.log_name.label')),

                        Placeholder::make('event')
                            ->content(function (?Model $record): string {
                                /** @var Activity $record */
                                if (!$record?->event) {
                                    return '-';
                                }
                                
                                // Try to get translation
                                $translationKey = 'activitylog::action.event.' . $record->event;
                                $translated = __($translationKey);
                                
                                // If translation key is returned (meaning translation not found), format the event name
                                if ($translated === $translationKey) {
                                    // Format the event name: session_cancelled -> Session Cancelled
                                    $formatted = str_replace('_', ' ', $record->event);
                                    $formatted = ucwords($formatted);
                                    return $formatted;
                                }
                                
                                return ucwords($translated);
                            })
                            ->label(__('activitylog::forms.fields.event.label')),

                        Placeholder::make('created_at')
                            ->label(__('activitylog::forms.fields.created_at.label'))
                            ->content(function (?Model $record): string {
                                /** @var Activity $record */
                                if (! $record?->created_at) {
                                    return '-';
                                }

                                $parser = ActivitylogPlugin::get()->getDateParser();

                                return $parser($record->created_at)
                                    ->format(ActivitylogPlugin::get()->getDatetimeFormat());
                            }),
                    ])->grow(false),
                ])->from('md'),

                Section::make(__('activitylog::forms.changes'))
                    ->headerActions([
                        Action::make('restore')
                            ->label(__('activitylog::action.restore'))
                            ->icon('heroicon-o-arrow-uturn-left')
                            ->color('primary')
                            ->action(fn(Activity $record) => self::restoreActivity($record->id))
                            ->visible(fn() => ! ActivitylogPlugin::get()->getIsRestoreActionHidden())
                            ->authorize(fn() => auth()->user()?->can('restore_activitylog') ?? false)
                            ->requiresConfirmation(),
                        Action::make('edit')
                            ->label(__('activitylog::action.edit'))
                            ->icon('heroicon-o-eye')
                            ->color('info')
                            ->url(fn(Activity $record) => self::getResourceUrl($record))
                            ->visible(fn() => ! ActivitylogPlugin::get()->getIsResourceActionHidden())
                            ->authorize(fn(Activity $record) => self::canViewResource($record)),
                    ])
                    ->columns()
                    ->visible(fn(?Model $record) => $record?->properties?->count() > 0)
                    ->schema(function (?Model $record) {
                        /** @var Activity $record */
                        if (! $record?->properties) {
                            return [];
                        }

                        $properties = $record->properties->except(['attributes', 'old']);
                        $schema     = [];

                        if ($properties->count()) {
                            $schema[] = KeyValue::make('properties')
                                ->afterStateHydrated(function (KeyValue $component) use ($properties) {
                                    $component->state($properties->toArray());
                                })
                                ->label(__('activitylog::forms.fields.properties.label'))
                                ->columnSpan('full')
                                ->disabled();
                        }

                        if ($old = $record->properties->get('old')) {
                            $schema[] = KeyValue::make('old')
                                ->afterStateHydrated(function (KeyValue $component) use ($old) {
                                    $component->state(is_array($old) ? $old : []);
                                })
                                ->label(__('activitylog::forms.fields.old.label'))
                                ->disabled();
                        }

                        if ($attributes = $record->properties->get('attributes')) {
                            $schema[] = KeyValue::make('attributes')
                                ->afterStateHydrated(function (KeyValue $component) use ($attributes) {
                                    $component->state(is_array($attributes) ? $attributes : []);
                                })
                                ->label(__('activitylog::forms.fields.attributes.label'))
                                ->disabled();
                        }

                        return $schema;
                    }),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        $logAfterExport = function (\Filament\Actions\Exports\Models\Export $export, array $data): void {
            activity()
                ->performedOn($export)
                ->causedBy(auth()->user())
                ->event('exported')
                ->withProperties([
                    'scope' => $data['scope'] ?? 'visible',
                ])
                ->log(auth()->user()->name . ' exported activity log');
        };

        return $table
            ->columns([
                static::getLogNameColumnComponent(),
                static::getEventColumnComponent(),
                static::getDescriptionColumnComponent(),
                static::getCauserNameAndIdColumnComponent(),
                static::getCauserRoleTypeColumnComponent(),
                static::getAffectedEntityColumnComponent(),
                static::getPropertiesColumnComponent(),
                static::getCreatedAtColumnComponent(),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();

                if ($user->isSuperAdmin()) {
                    return $query;
                }
                $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
                    ->pluck('competition_id')
                    ->toArray();

                return $query->whereIn('properties->attributes->competition_id', $supervisorCompetitions);
            })
            ->filters([
                static::getDateFilterComponent(),
                static::getEventFilterComponent(),
                static::getCompetitionFilterComponent(),
                static::getUsernameFilterComponent(),
                static::getUserRoleFilterComponent(),
            ])
            ->headerActions([
                TableExportAction::make('export')
                    ->label('Export logs')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->exporter(ActivityLogExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->columnMapping(false)
                    ->form([
                        Radio::make('scope')
                            ->label('Export scope')
                            ->options([
                                'visible'     => 'Currently displayed',
                                'date_range'  => 'Date range',
                                'competition' => 'Program',
                            ])
                            ->default('visible')
                            ->columnSpanFull()
                            ->required()
                            ->reactive(),

                        DatePicker::make('from_date')
                            ->label('Start date')
                            ->visible(fn($get) => $get('scope') === 'date_range')
                            ->required(fn($get) => $get('scope') === 'date_range'),

                        DatePicker::make('to_date')
                            ->label('End date')
                            ->visible(fn($get) => $get('scope') === 'date_range')
                            ->required(fn($get) => $get('scope') === 'date_range'),

                        Select::make('competition_id')
                            ->label('Program')
                            ->options(fn() => \App\Models\Competition::pluck('title', 'id'))
                            ->searchable()
                            ->visible(fn($get) => $get('scope') === 'competition')
                            ->required(fn($get) => $get('scope') === 'competition'),
                    ])
                    ->options(function (array $data) {
                        return $data;
                    })
                    ->modifyQueryUsing(function (Builder $query, array $data = []) {
                        if ($data['scope'] == 'date_range') {
                            if (!empty($data['from_date']) && !empty($data['to_date'])) {
                                $query->whereBetween('created_at', [
                                    Carbon::parse($data['from_date'])->startOfDay(),
                                    Carbon::parse($data['to_date'])->endOfDay(),
                                ]);
                            }
                        } elseif ($data['scope'] == 'competition') {
                            $query->where('properties->attributes->competition_id', $data['competition_id']);
                        }
                    })
                    ->fileName(function (Export $export, array $data): string {
                        $scope = $data['scope'] ?? 'visible';

                        $dateRange = match ($scope) {
                            'date_range' => isset($data['from_date'], $data['to_date'])
                                ? Carbon::parse($data['from_date'])->format('Y-m-d') . '_to_' . Carbon::parse($data['to_date'])->format('Y-m-d')
                                : 'date_range',
                            'competition' => 'competition',
                            default       => 'current_view',
                        };

                        return 'ActivityLogs_' .
                            $dateRange . '_' .
                            now()->format('Y-m-d_His');
                    })
                    ->after($logAfterExport),
            ])
            ->actions([
               DeleteAction::make()
                   ->visible(fn () => auth()->user()?->can('delete ActivityLog')),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->label('Export selected')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->exporter(ActivityLogExporter::class)
                    ->columnMapping(false)
                    ->after($logAfterExport),

                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete ActivityLog')),
                ]),
            ])
            ->paginated([
                'defaultPerPage' => 25,
            ])->emptyStateHeading(
                fn(HasTable $livewire) => ($livewire->hasTableSearch() || filled($livewire->tableFilters))
                    ? 'No logs found matching your criteria.'
                    : 'No Activity Logs'
            );
    }

    public static function getLogNameColumnComponent(): Column
    {
        return TextColumn::make('log_name')
            ->label(__('activitylog::tables.columns.log_name.label'))
            ->formatStateUsing(fn($state) => $state ? ucwords($state) : '-')
            ->sortable()
            ->badge();
    }

    public static function getEventColumnComponent(): Column
    {
        return TextColumn::make('event')
            ->label(__('activitylog::tables.columns.event.label'))
            ->formatStateUsing(function ($state) {
                if (!$state) {
                    return '-';
                }
                
                // Try to get translation
                $translationKey = 'activitylog::action.event.' . $state;
                $translated = __($translationKey);
                
                // If translation key is returned (meaning translation not found), format the event name
                if ($translated === $translationKey) {
                    // Format the event name: session_cancelled -> Session Cancelled
                    $formatted = str_replace('_', ' ', $state);
                    $formatted = ucwords($formatted);
                    return $formatted;
                }
                
                return ucwords($translated);
            })
            ->badge()
            ->color(fn(?string $state): string => match ($state) {
                'draft'    => 'gray',
                'updated'  => 'warning',
                'created'  => 'success',
                'deleted'  => 'danger',
                'restored' => 'info',
                'session_cancelled' => 'danger',
                'session_declined' => 'danger',
                'session_accepted' => 'success',
                'session_rescheduled' => 'warning',
                'new_time_proposed' => 'info',
                'session_auto_marked_no_show' => 'warning',
                'feedback_submitted' => 'success',
                default    => 'primary',
            })
            ->sortable();
    }

    public static function getDescriptionColumnComponent(): Column
    {
        return TextColumn::make('description')
            ->label(__('Description'))
            ->wrap()
            ->searchable()
            ->sortable();
    }

    public static function getCauserRoleTypeColumnComponent(): Column
    {
        return TextColumn::make('causer.roles.name')
            ->label('User Role')
            ->getStateUsing(function (Model $record) {
                /** @var Activity $record */
                if ($record->causer_id === null || $record->causer === null) {
                    return new HtmlString('&mdash;');
                }

                return $record->causer?->roles?->pluck('name')->first() ?: new HtmlString('&mdash;');
            })
            ->badge()
            ->colors([
                'success' => 'admin',
                'info'    => 'supervisor',
                'danger'  => 'super-admin',
                'secondary' => fn ($state): bool => ! in_array($state, ['admin', 'supervisor', 'super-admin']),
            ])
            ->sortable(
                query: function (Builder $query, string $direction): Builder {
                    return $query
                        ->leftJoin('users as causers', function ($join) {
                            $join->on('causers.id', '=', 'activity_log.causer_id')
                                ->where('activity_log.causer_type', User::class);
                        })
                        ->leftJoin('model_has_roles as mhr', 'mhr.model_id', '=', 'causers.id')
                        ->leftJoin('roles as r', 'r.id', '=', 'mhr.role_id')
                        ->orderBy('r.name', $direction)
                        ->select('activity_log.*');
                }
            );
    }

    public static function getCauserNameAndIdColumnComponent(): Column
    {
        return TextColumn::make('causer.name')
            ->label('User ID/Username')
            ->getStateUsing(function (Model $record) {
                /** @var Activity $record */
                if ($record->causer_id === null || $record->causer === null) {
                    return new HtmlString('&mdash;');
                }

                return $record->causer->name . ' (#' . $record->causer_id . ')';
            });
    }
    public static function getAffectedEntityColumnComponent(): Column
    {
        return TextColumn::make('affected_columns')
            ->label('Affected Entity')
            ->wrap()
            ->getStateUsing(function (Model $record) {
                try {
                    $properties = $record->properties;

                    // Convert to array if it's a Collection or JSON string
                    if ($properties instanceof \Illuminate\Support\Collection) {
                        $properties = $properties->toArray();
                    } elseif (is_string($properties)) {
                        $properties = json_decode($properties, true) ?? [];
                    }

                    // Initialize columns array
                    $columns = [];

                    // Handle all possible property structures
                    if (isset($properties['attributes']) && is_array($properties['attributes'])) {
                        $columns = array_merge($columns, array_keys($properties['attributes']));
                    }
                    if (isset($properties['old']) && is_array($properties['old'])) {
                        $columns = array_merge($columns, array_keys($properties['old']));
                    }
                    if (isset($properties['properties']['attributes'])) {
                        $columns = array_merge($columns, array_keys($properties['properties']['attributes']));
                    }
                    if (isset($properties['properties']['old'])) {
                        $columns = array_merge($columns, array_keys($properties['properties']['old']));
                    }

                    // Clean up relationship notations (e.g., participant.name → participant)
                    $columns = array_map(function ($column) {
                        return strpos($column, '.') !== false
                            ? explode('.', $column)[0]
                            : $column;
                    }, $columns);

                    // Remove duplicates and empty values
                    $columns = array_unique(array_filter($columns));

                    if (empty($columns)) {
                        return new HtmlString('&mdash;');
                    }

                    return implode(', ', $columns);
                } catch (\Exception $e) {
                    // Failed to parse activity log properties
                    return new HtmlString('Error');
                }
            });
    }

    public static function getPropertiesColumnComponent(): Column
    {
        return ViewColumn::make('properties')
            ->searchable()
            ->label(__('activitylog::tables.columns.properties.label'))
            ->view('activitylog::filament.tables.columns.activity-logs-properties')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function getCreatedAtColumnComponent(): Column
    {
        $column = TextColumn::make('created_at')
            ->label(__('activitylog::tables.columns.created_at.label'))
            ->dateTime(ActivitylogPlugin::get()->getDatetimeFormat())
            ->searchable()
            ->sortable();

        // Apply the custom callback if set
        $callback = ActivitylogPlugin::get()->getDatetimeColumnCallback();

        if ($callback) {
            $column = $callback($column);
        }

        return $column;
    }

    public static function getDateFilterComponent(): Filter
    {
        return Filter::make('date_range')
            ->form([
                Select::make('range')
                    ->native(false)
                    ->placeholder('—')
                    ->options([
                        'today'     => 'Today',
                        'yesterday' => 'Yesterday',
                        'last7'     => 'Last 7 days',
                        'last30'    => 'Last 30 days',
                        'custom'    => 'Custom range',
                    ])
                    ->reactive(),

                DatePicker::make('from_date')
                    ->label('Start Date')
                    ->visible(fn($get) => $get('range') === 'custom'),

                DatePicker::make('to_date')
                    ->label('End Date')
                    ->visible(fn($get) => $get('range') === 'custom'),
            ])

            ->indicateUsing(function (array $data): array {
                if (! isset($data['range'])) {
                    return [];
                }

                // quick presets
                if ($data['range'] !== 'custom') {
                    return [
                        Indicator::make(match ($data['range']) {
                            'today'     => 'Today',
                            'yesterday' => 'Yesterday',
                            'last7'     => 'Last 7 days',
                            'last30'    => 'Last 30 days',
                        }),
                    ];
                }

                // custom range
                if (($data['from_date'] ?? null) || ($data['to_date'] ?? null)) {
                    return [
                        Indicator::make(sprintf(
                            'From %s to %s',
                            Carbon::parse($data['from_date'])->toFormattedDateString(),
                            Carbon::parse($data['to_date'])->toFormattedDateString(),
                        )),
                    ];
                }

                return [];
            })

            ->query(function (Builder $query, array $data): Builder {
                return match ($data['range'] ?? null) {
                    'today'     => $query->whereDate('created_at', today()),
                    'yesterday' => $query->whereDate('created_at', today()->subDay()),
                    'last7'     => $query->whereBetween('created_at', [
                        now()->subDays(7)->startOfDay(),
                        now()->endOfDay(),
                    ]),
                    'last30'    => $query->whereBetween('created_at', [
                        now()->subDays(30)->startOfDay(),
                        now()->endOfDay(),
                    ]),
                    'custom'    => ($data['from_date'] && $data['to_date'])
                        ? $query->whereBetween('created_at', [
                            Carbon::parse($data['from_date'])->startOfDay(),
                            Carbon::parse($data['to_date'])->endOfDay(),
                        ])
                        : $query,
                    default     => $query,
                };
            });
    }

    public static function getEventFilterComponent(): SelectFilter
    {
        return SelectFilter::make('event')
            ->label(__('activitylog::tables.filters.event.label'))
            ->options(
                static::getModel()::distinct()
                    ->pluck('event', 'event')
                    ->mapWithKeys(fn($value, $key) => [$key => __('activitylog::action.event.' . $value)])
            );
    }

    public static function getCompetitionFilterComponent(): SelectFilter
    {
        return SelectFilter::make('competition_id')
            ->label('Program')
            ->options(function () {
                $user = auth()->user();

                if ($user->isSuperAdmin()) {
                    return Competition::pluck('title', 'id')->toArray();
                }

                $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
                    ->pluck('competition_id')
                    ->toArray();

                return Competition::whereIn('id', $supervisorCompetitions)
                    ->pluck('title', 'id')
                    ->toArray();
            })

            ->query(function (Builder $query, array $data): Builder {
                if (! isset($data['value'])) {
                    return $query;
                }

                return $query->where('properties->attributes->competition_id', $data['value']);
            });
    }

    public static function getUsernameFilterComponent(): SelectFilter
    {
        return SelectFilter::make('causer_id')
            ->label('Username')
            ->options(function () {
                return static::getModel()::query()
                    ->whereNotNull('causer_id')
                    ->whereNotNull('causer_type')
                    ->distinct()
                    ->with('causer')
                    ->get()
                    ->mapWithKeys(function ($activity) {
                        return [$activity->causer_id => $activity->causer?->name ?? 'Unknown'];
                    })
                    ->filter()
                    ->unique()
                    ->toArray();
            });
    }

    public static function getUserRoleFilterComponent(): SelectFilter
    {
        return SelectFilter::make('causer_role')
            ->label('User Role')
            ->options(
                fn() => \Spatie\Permission\Models\Role::pluck('name', 'name')->toArray()
            )
            ->query(function (Builder $query, array $data): Builder {
                $role = $data['value'] ?? null;
                if (! $role) {
                    return $query;
                }

                return $query->whereHasMorph(
                    'causer',
                    [User::class],
                    fn(Builder $q) => $q->whereHas('roles', fn($qr) => $qr->where('name', $role))
                );
            })
            ->searchable();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivitylog::route('/'),
            'view'  => Pages\ViewActivitylog::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canAccess(): bool
    {
        return static::canViewAny()
            && ActivitylogPlugin::get()->isAuthorized();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view ActivityLog') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete ActivityLog');
    }
}
