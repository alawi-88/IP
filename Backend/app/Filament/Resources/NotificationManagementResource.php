<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationManagementResource\Pages;
use App\Filament\Resources\NotificationManagementResource\RelationManagers;
use App\Models\Competition;
use App\Models\NotificationManagement;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\{Card, Grid, Radio, Select, Textarea, TextInput, Hidden, Placeholder, Toggle};
use App\Models\Participant;
use App\Models\Judge;
use Filament\Forms\Get;
use App\Traits\Notifications\SendingNotificationsService;
use Illuminate\Database\Eloquent\Model;

class NotificationManagementResource extends Resource
{
    use SendingNotificationsService;

    protected static ?string $model = NotificationManagement::class;

    protected static ?string $navigationGroup = 'Notification Management';

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Push Notification';
    protected static ?int $navigationSort = 90;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Card::make()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('title')
                                ->label('Notification Title')
                                ->required()
                                ->maxLength(150)
                                ->reactive()
                                ->placeholder('Enter notification title'),

                            Select::make('competition_id')
                                ->label('Program (optional)')
                                ->options(Competition::query()->pluck('title', 'id'))
                                ->searchable()
                                ->nullable()
                                ->reactive(),
                        ]),

                    Textarea::make('body')
                        ->label('Message Body')
                        ->rows(6)
                        ->required()
                        ->placeholder('Write the notification message here...')
                        ->reactive(),

                    Radio::make('user_type')
                        ->label('Target Audience')
                        ->options([
                            'participant' => 'Participants',
                            'judge' => 'Judges',
                            'all' => 'All Users',
                        ])
                        ->default('all')
                        ->inline()
                        ->reactive(),

                    Toggle::make('send_email')
                        ->label('Send Email Notification')
                        ->default(true)
                        ->helperText('Enable to send this notification as an email to the selected users.')
                        ->inline(),

                    Select::make('user_ids')
                        ->label('Specific Users')
                        ->multiple()
                        ->searchable()
                        ->options(function (Get $get) {
                            $competitionId = $get('competition_id');
                            return match ($get('user_type')) {
                                'participant' => Participant::whereHas('competitionApplications', function ($query) use ($competitionId) {
                                    if ($competitionId) {
                                        $query->where('competition_id', $competitionId);
                                    }
                                })->pluck('name', 'id')->toArray(),

                                'judge' => Judge::when($competitionId, function ($query) use ($competitionId) {
                                    $query->whereHas('competitions', function ($query) use ($competitionId) {
                                        $query->where('competition_id', $competitionId);
                                    });
                                })->pluck('name', 'id')->toArray(),

                                default => [],
                            };
                        })
                        ->visible(fn(Get $get) => in_array($get('user_type'), ['participant', 'judge']))
                        ->required(fn(Get $get) => in_array($get('user_type'), ['participant', 'judge']))
                        ->reactive(),

                    Placeholder::make('recipient_count_preview')
                        ->label('Recipient Count (Estimated)')
                        ->content(function (Get $get) {
                            $type = $get('user_type');
                            $competition = $get('competition_id');
                            $selectedUsers = $get('user_ids') ?? [];

                            if (!empty($selectedUsers) && $type !== 'all') {
                                return count($selectedUsers) . ' selected user(s)';
                            }

                            $count = match ($type) {
                                'participant' => Participant::when($competition, function ($query) use ($competition) {
                                    $query->whereHas('competitionApplications', function ($query) use ($competition) {
                                        if ($competition) {
                                            $query->where('competition_id', $competition);
                                        }
                                    });
                                })->count(),

                                'judge' => Judge::when($competition, function ($query) use ($competition) {
                                    $query->whereHas('competitions', function ($query) use ($competition) {
                                        if ($competition) {
                                            $query->where('competition_id', $competition);
                                        }
                                    });
                                })->count(),

                                'all' => Participant::when($competition, function ($query) use ($competition) {
                                    $query->whereHas('competitionApplications', function ($query) use ($competition) {
                                        if ($competition) {
                                            $query->where('competition_id', $competition);
                                        }
                                    });
                                })->count()
                                    + Judge::when($competition, function ($query) use ($competition) {
                                        $query->whereHas('competitions', function ($query) use ($competition) {
                                            if ($competition) {
                                                $query->where('competition_id', $competition);
                                            }
                                        });
                                    })->count(),

                                default => 0,
                            };

                            return $count . ' ' . ($type === 'all' ? 'total users' : ($type === 'participant' ? 'participants' : 'judges'));
                        })
                        ->visible(fn(Get $get) => filled($get('user_type'))),

                    Hidden::make('recipient_count')
                        ->default(function (Get $get) {
                            $type = $get('user_type');
                            $competition = $get('competition_id');
                            $selectedUsers = $get('user_ids') ?? [];

                            if (!empty($selectedUsers)) {
                                return count($selectedUsers);
                            }

                            return match ($type) {
                                'participant' => Participant::whereHas('competitionApplications', function ($query) use ($competition) {
                                    if ($competition) {
                                        $query->where('competition_id', $competition);
                                    }
                                })->count(),

                                'judge' => Judge::whereHas('competitions', function ($query) use ($competition) {
                                    if ($competition) {
                                        $query->where('competition_id', $competition);
                                    }
                                })->count(),

                                'all' => Participant::whereHas('competitionApplications', function ($query) use ($competition) {
                                    if ($competition) {
                                        $query->where('competition_id', $competition);
                                    }
                                })->count()
                                    + Judge::whereHas('competitions', function ($query) use ($competition) {
                                        if ($competition) {
                                            $query->where('competition_id', $competition);
                                        }
                                    })->count(),

                                default => 0,
                            };
                        })
                        ->required(),


                    Hidden::make('admin_id')
                        ->default(fn() => auth()->id())
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->limit(30)
                    ->tooltip(fn($record): string => $record->title)
                    ->searchable(),

                TextColumn::make('user_type')
                    ->label('Target Audience')
                    ->formatStateUsing(fn($state): string => match ($state) {
                        'participant' => 'Participants',
                        'judge' => 'Judges',
                        'all' => 'All Users',
                        default => ucfirst($state),
                    })
                    ->badge()
                    ->color(fn($state): string => match ($state) {
                        'participant' => 'success',
                        'judge' => 'warning',
                        'all' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('body')
                    ->label('Content')
                    ->limit(50)
                    ->tooltip(fn($record): string => $record->body)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                TextColumn::make('delivery_status')
                    ->label('Delivery Status')
                    ->badge()
                    ->state('sent')
                    ->color('success'),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_type')
                    ->label('User Type')
                    ->options([
                        'participant' => 'Participants',
                        'judge' => 'Judges',
                        'all' => 'All Users',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete NotificationManagement')),
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
            'index' => Pages\ListNotificationManagement::route('/'),
            'create' => Pages\CreateNotificationManagement::route('/create'),
            'edit' => Pages\EditNotificationManagement::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view NotificationManagement') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create NotificationManagement') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete NotificationManagement');
    }
}
