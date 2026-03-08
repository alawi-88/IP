<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubTrackResource\Pages;
use App\Filament\Resources\SubTrackResource\RelationManagers;
use App\Models\SubTrack;
use App\Models\Track;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;


class SubTrackResource extends Resource
{
    protected static ?string $model = SubTrack::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Forms & Configuration';
    protected static ?string $navigationLabel = 'SubTrack';
    protected static ?int $navigationSort = 7;

    // Merged into TrackResource (Tracks & Sub-Tracks)
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Information')
                ->schema([
                    Forms\Components\Select::make('track_id')
                        ->label('Track')
                        ->options(fn () => Track::with('program')->get()->mapWithKeys(function ($track) {
                            return [$track->id => $track->program->title . ' / ' . $track->name];
                        }))
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->helperText('Select the parent Track for this SubTrack.')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('order')
                        ->label('Order')
                        ->numeric()
                        ->default(1)
                        ->helperText('Defines the order of the sub-track in listings.')
                        ->columnSpanFull()
                        ->rules(function (callable $get) {
                            $trackId = $get('track_id');
                            return [
                                Rule::unique('sub_tracks', 'order')
                                    ->where(fn ($query) => $query->where('track_id', $trackId)),
                            ];
                        }),
                ]),

            Forms\Components\Section::make('Sub Track Names')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('name.en')
                                ->label('Sub Track Name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Enter sub-track name in English')
                                ->rules(function (callable $get) {
                                    return [
                                        function (string $attribute, $value, \Closure $fail) use ($get) {
                                            if (blank($value)) {
                                                return;
                                            }
                                            $trackId = $get('track_id');
                                            if (blank($trackId)) {
                                                return;
                                            }
                                            $query = SubTrack::where('track_id', $trackId)
                                                ->where('name->en', $value);
                                            $recordId = request()->route('record');
                                            if ($recordId) {
                                                $query->where('id', '!=', $recordId);
                                            }
                                            if ($query->exists()) {
                                                $fail(__('A sub track with this name already exists in the selected track.'));
                                            }
                                        },
                                    ];
                                }),

                            Forms\Components\TextInput::make('name.ar')
                                ->label('اسم المسار الفرعي')
                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                ->required()
                                ->maxLength(255)
                                ->placeholder('ادخل اسم المسار الفرعي بالعربية'),

                            Forms\Components\Hidden::make('slug')
                                ->label('Slug'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('track.program.title')
                    ->label('Program')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('track.name')
                    ->label('Track')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Sub Track')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('order')
                    ->label('Order'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListSubTracks::route('/'),
            'create' => Pages\CreateSubTrack::route('/create'),
            'edit' => Pages\EditSubTrack::route('/{record}/edit'),
        ];
    }


    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view SubTrack') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create SubTrack') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update SubTrack');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete SubTrack');
    }
}
