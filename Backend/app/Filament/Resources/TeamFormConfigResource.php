<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamFormConfigResource\Pages;
use App\Filament\Resources\TeamFormConfigResource\RelationManagers;
use App\Models\Competition;
use App\Models\TeamFormConfig;
use App\Models\UserCompetition;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{Card, Section, Group, TextInput, Toggle, Select, Placeholder};
use Illuminate\Database\Eloquent\Model;


class TeamFormConfigResource extends Resource
{
    protected static ?string $model = TeamFormConfig::class;

    // Managed via Competition Hub
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'Forms & Configuration';
    protected static ?string $navigationLabel = 'Add Team Form';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {

        return $form->schema([
            Card::make()
                ->schema([
                    Placeholder::make('header')
                        ->label('Team Configuration')
                        ->content('Configure team settings for this program. These settings affect how teams are formed and managed.'),

                    Section::make('Program Settings')
                        ->schema([
                            Select::make('competition_id')
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
                                })                                ->searchable()
                                ->required()
                                ->native(false)
                                ->columnSpanFull(),

                            Toggle::make('is_active')
                                ->label('Add Team Form Status')
                                ->default(true)
                                ->inline(false),
                        ])
                        ->columns(2),

                    Section::make('Team Size Settings')
                        ->schema([
                            Group::make([
                                TextInput::make('min_team_members')
                                    ->label('Min Members')
                                    ->numeric()
                                    ->default(2)
                                    ->minValue(2),

                                TextInput::make('max_team_members')
                                    ->label('Max Members')
                                    ->numeric()
                                    ->default(6)
                                    ->minValue(2)
                                    ->maxValue(10),
                            ])->columns(2),
                        ]),

                    Section::make('Track Rules Settings')
                        ->schema([
                            Toggle::make('allow_track_selection')
                                ->label('Allow Track/Subtrack Selection')
                                ->helperText('If enabled, team leaders can select a track during team creation.')
                                ->live(),

                            Toggle::make('require_same_track')
                                ->label('Require Same Track for All Members')
                                ->helperText('If enabled, all invited members must belong to the same track.')
                                ->reactive(),
                        ])
                        ->columns(2),

                    Section::make('Publishing Settings')
                        ->schema([
                            Toggle::make('auto_publish_teams')
                                ->label('Auto-Publish Teams')
                                ->helperText('If enabled, teams will be automatically visible in the public list.')
                                ->default(false),
                        ]),
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

                return $query->whereIn('competition_id', $supervisorCompetitions);
            })
            ->columns([
                Tables\Columns\TextColumn::make('competition.title')
                    ->label('Program')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->isArchived()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete TeamFormConfig')),
                ]),
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
            'index' => Pages\ListTeamFormConfigs::route('/'),
            'create' => Pages\CreateTeamFormConfig::route('/create'),
            'edit' => Pages\EditTeamFormConfig::route('/{record}/edit'),
        ];
    }


    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view TeamFormConfig') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create TeamFormConfig') ?? false;
    }

    /**
     * IDOR prevention: verify user has access to the competition.
     */
    public static function canEdit(Model $record): bool
    {
        if ($record->isArchived() || !auth()->user()?->can('update TeamFormConfig')) {
            return false;
        }
        return $record->competition && $record->competition->canAccessProgram();
    }

    /**
     * IDOR prevention: verify user has access to the competition.
     */
    public static function canDelete(Model $record): bool
    {
        if (!auth()->user()?->can('delete TeamFormConfig')) {
            return false;
        }
        return $record->competition && $record->competition->canAccessProgram();
    }

    /**
     * IDOR prevention: verify user has access to the competition.
     */
    public static function canArchive(Model $record): bool
    {
        if (!auth()->user()?->can('archive TeamFormConfig') || $record->isArchived()) {
            return false;
        }
        return $record->competition && $record->competition->canAccessProgram();
    }

    /**
     * IDOR prevention: verify user has access to the competition.
     */
    public static function canRestore(Model $record): bool
    {
        if (!auth()->user()?->can('restore TeamFormConfig') || !$record->isArchived()) {
            return false;
        }
        return $record->competition && $record->competition->canAccessProgram();
    }
}
