<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrackResource\Pages;
use App\Filament\Resources\TrackResource\RelationManagers;
use App\Models\Track;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Model;

class TrackResource extends Resource
{
    protected static ?string $model = Track::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Forms & Content';
    protected static ?string $navigationLabel = 'Tracks & Sub-Tracks';
    protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Information')
                ->description('Select the program and order of this track.')
                ->schema([
                    Select::make('program_id')
                        ->label('Program')
                        ->options(\App\Models\Program::pluck('title', 'id'))
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->helperText('Choose the program this track belongs to.')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('order')
                        ->label('Order')
                        ->numeric()
                        ->default(1)
                        ->helperText('Defines the order of the track in listings.')
                        ->columnSpanFull()
                        ->rules(function (callable $get) {
                            $programId = $get('program_id');
                            return [
                                Rule::unique('tracks', 'order')
                                    ->where(fn($query) => $query->where('program_id', $programId)),
                            ];
                        }),
                ]),

            Forms\Components\Section::make('Track Names')
                ->description('Enter the track names in both English and Arabic.')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('name.en')
                                ->label('Track Name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Enter track name in English')
                                ->rules(function (callable $get) {
                                    return [
                                        function (string $attribute, $value, \Closure $fail) use ($get) {
                                            if (blank($value)) {
                                                return;
                                            }
                                            $programId = $get('program_id');
                                            if (blank($programId)) {
                                                return;
                                            }
                                            $query = Track::where('program_id', $programId)
                                                ->where('name->en', $value);
                                            $recordId = request()->route('record');
                                            if ($recordId) {
                                                $query->where('id', '!=', $recordId);
                                            }
                                            if ($query->exists()) {
                                                $fail(__('A track with this name already exists in the selected program.'));
                                            }
                                        },
                                    ];
                                }),

                            Forms\Components\TextInput::make('name.ar')
                                ->label('اسم المسار')
                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                ->required()
                                ->maxLength(255)
                                ->placeholder('ادخل اسم المسار بالعربية'),

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
                Tables\Columns\TextColumn::make('program.title')
                    ->label('Program')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('order'),

                Tables\Columns\TextColumn::make('sub_tracks_count')
                    ->counts('subTracks')
                    ->label('Sub-Tracks')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(function ($record) {
                        return !\App\Models\ProgramApplication::query()
                            ->where('form_submissions->track', $record->id)
                            ->exists();
                    }),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListTracks::route('/'),
            'create' => Pages\CreateTrack::route('/create'),
            'edit' => Pages\EditTrack::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Track') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create Track') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update Track');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete Track');
    }
}
