<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectFormConfigResource\Pages;
use App\Models\ProjectFormConfig;
use App\Models\UserCompetition;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{Card, Section, Toggle, Select, Placeholder};
use Illuminate\Database\Eloquent\Model;

class ProjectFormConfigResource extends Resource
{
    protected static ?string $model = ProjectFormConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Form Configs';
    protected static ?string $navigationLabel = 'Project Form Configs';
    protected static ?int $navigationSort = 41;

    public static function form(Form $form): Form
    {

        return $form->schema([
            Card::make()
                ->schema([
                    Placeholder::make('header')
                        ->label('Team Configuration')
                        ->content('Configure Project settings for this program. These settings affect how Project are formed and managed.'),

                    // from selection
                    Section::make('From Settings')
                        ->schema([
                            Select::make('form_id')
                                ->label('From')
                                ->options(function () {
                                    $user = auth()->user();
                                    $usedFormIds = ProjectFormConfig::pluck('form_id')->toArray();

                                    $query = \App\Models\Form::projecttype()
                                        ->whereNotIn('id', $usedFormIds);

                                    if ($user->isSuperAdmin()) {
                                        return $query
                                            ->where('competition_id', currentCompetitionId())
                                            ->pluck('name', 'id');
                                    }

                                    $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
                                        ->pluck('competition_id')
                                        ->toArray();

                                    return $query
                                        ->whereIn('competition_id', $supervisorCompetitions)
                                        ->where('competition_id', currentCompetitionId())
                                        ->pluck('name', 'id');
                                })
                                ->getOptionLabelUsing(function ($value) {
                                    return \App\Models\Form::find($value)?->name;
                                })
                                ->searchable()
                                ->required()
                                ->native(false)
                                ->columnSpanFull()
                        ])
                        ->columns(1),

                    // Track change option
                    Section::make('Project Form Settings')
                        ->description('Configure how participants can submit projects.')
                        ->schema([
                            Toggle::make('allow_track_change')
                                ->label('Allow Track/Subtrack Changes')
                                ->helperText('Allow participants to change their track/subtrack selection when submitting their project.')
                                ->inline(false)
                                ->hint('If disabled, tracks/subtracks will be locked to the participant\'s original selection.'),
                        ])
                        ->columns(1),
                ]),
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

                return $query->whereHas('form', function ($q) use ($supervisorCompetitions) {
                    $q->whereIn('competition_id', $supervisorCompetitions);
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('form.name')
                    ->label('From')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('allow_track_change')
                    ->label('Allow Track Change')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime()
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
                        ->visible(fn () => auth()->user()?->can('delete ProjectFormConfig')),
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
            'index' => Pages\ListProjectFormConfigs::route('/'),
            'create' => Pages\CreateProjectFormConfig::route('/create'),
            'edit' => Pages\EditProjectFormConfig::route('/{record}/edit'),
        ];
    }


    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view ProjectFormConfig') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create ProjectFormConfig') ?? false;
    }

    /**
     * IDOR prevention: verify user has access to the form's program.
     */
    public static function canEdit(Model $record): bool
    {
        if ($record->isArchived() || !auth()->user()?->can('update ProjectFormConfig')) {
            return false;
        }
        $form = $record->form;
        return $form && $form->competition && $form->competition->canAccessProgram();
    }

    /**
     * IDOR prevention: verify user has access to the form's program.
     */
    public static function canDelete(Model $record): bool
    {
        if (!auth()->user()?->can('delete ProjectFormConfig')) {
            return false;
        }
        $form = $record->form;
        return $form && $form->competition && $form->competition->canAccessProgram();
    }

    /**
     * IDOR prevention: verify user has access to the form's program.
     */
    public static function canArchive(Model $record): bool
    {
        if (!auth()->user()?->can('archive ProjectFormConfig') || $record->isArchived()) {
            return false;
        }
        $form = $record->form;
        return $form && $form->competition && $form->competition->canAccessProgram();
    }

    /**
     * IDOR prevention: verify user has access to the form's program.
     */
    public static function canRestore(Model $record): bool
    {
        if (!auth()->user()?->can('restore ProjectFormConfig') || !$record->isArchived()) {
            return false;
        }
        $form = $record->form;
        return $form && $form->competition && $form->competition->canAccessProgram();
    }
}
