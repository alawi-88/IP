<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MentorSessionResource\Pages;
use App\Filament\Resources\MentorSessionResource\RelationManagers;
use App\Filament\Exports\MentorSessionExporter;
use App\Models\MentorSession;
use App\Models\Competition;
use App\Models\Mentor;
use App\Models\Participant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class MentorSessionResource extends Resource
{
    protected static ?string $model = MentorSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Mentorship Sessions';

    protected static ?string $modelLabel = 'Session';

    protected static ?string $pluralModelLabel = 'Sessions';

    protected static ?string $navigationGroup = 'Users & Roles';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('mentor_id')
                    ->label('Mentor')
                    ->relationship('mentor', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('participant_id')
                    ->label('Participant')
                    ->relationship('participant', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Forms\Components\Select::make('competition_id')
                    ->label('Program')
                    ->relationship('competition', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->maxLength(1000)
                    ->rows(3),

                Forms\Components\DateTimePicker::make('scheduled_at')
                    ->label('Scheduled At')
                    ->required()
                    ->native(false),

                Forms\Components\TextInput::make('duration_minutes')
                    ->label('Duration (minutes)')
                    ->numeric()
                    ->default(30)
                    ->minValue(15)
                    ->maxValue(480),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(MentorSession::STATUSES)
                    ->default('scheduled')
                    ->required(),

                Forms\Components\Select::make('video_tool')
                    ->label('Video Tool')
                    ->options(MentorSession::VIDEO_TOOLS)
                    ->nullable(),

                Forms\Components\TextInput::make('meeting_id')
                    ->label('Meeting ID')
                    ->disabled(),

                Forms\Components\TextInput::make('join_url')
                    ->label('Join URL')
                    ->url()
                    ->disabled(),

                Forms\Components\TextInput::make('password')
                    ->label('Meeting Password')
                    ->disabled(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3),

                Forms\Components\Textarea::make('feedback')
                    ->label('Feedback')
                    ->rows(3),

                Forms\Components\TextInput::make('rating')
                    ->label('Rating')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5),

                Forms\Components\DateTimePicker::make('started_at')
                    ->label('Started At')
                    ->disabled(),

                Forms\Components\DateTimePicker::make('ended_at')
                    ->label('Ended At')
                    ->disabled(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Session Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Title'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'scheduled' => 'warning',
                                'confirmed' => 'info',
                                'in_progress' => 'primary',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                'no_show' => 'secondary',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => MentorSession::STATUSES[$state] ?? $state),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Schedule')
                    ->schema([
                        Infolists\Components\TextEntry::make('scheduled_at')
                            ->label('Scheduled At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('duration_minutes')
                            ->label('Duration')
                            ->formatStateUsing(fn (int $state): string => self::formatDuration($state)),
                        Infolists\Components\TextEntry::make('end_time')
                            ->label('End Time')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('started_at')
                            ->label('Started At')
                            ->formatStateUsing(function ($state): string {
                                if ($state instanceof \Carbon\CarbonInterface) {
                                    return $state->format('M d, Y \a\t g:i A');
                                }
                                return 'N/A';
                            }),
                        Infolists\Components\TextEntry::make('ended_at')
                            ->label('Ended At')
                            ->formatStateUsing(function ($state): string {
                                if ($state instanceof \Carbon\CarbonInterface) {
                                    return $state->format('M d, Y \a\t g:i A');
                                }
                                return 'N/A';
                            }),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Video Tool')
                    ->schema([
                        Infolists\Components\TextEntry::make('video_tool')
                            ->label('Video Tool')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'zoom' => 'blue',
                                'teams' => 'purple',
                                'google_meet' => 'green',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => $state ? (MentorSession::VIDEO_TOOLS[$state] ?? $state) : 'N/A'),
                        Infolists\Components\TextEntry::make('meeting_id')
                            ->label('Meeting ID')
                            ->default('N/A'),
                        Infolists\Components\TextEntry::make('join_url')
                            ->label('Join URL')
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->default('N/A'),
                        Infolists\Components\TextEntry::make('password')
                            ->label('Meeting Password')
                            ->default('N/A'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Participants')
                    ->schema([
                        Infolists\Components\TextEntry::make('mentor.name')
                            ->label('Mentor'),
                        Infolists\Components\TextEntry::make('mentor.email')
                            ->label('Mentor Email'),
                        Infolists\Components\TextEntry::make('participant.name')
                            ->label('Participant')
                            ->default('N/A'),
                        Infolists\Components\TextEntry::make('participant.email')
                            ->label('Participant Email')
                            ->default('N/A'),
                        Infolists\Components\TextEntry::make('competition.title')
                            ->label('Program'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Feedback')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->columnSpanFull()
                            ->default('N/A'),
                        Infolists\Components\TextEntry::make('feedback_comments')
                            ->label('Comments')
                            ->columnSpanFull()
                            ->default('N/A'),
                        Infolists\Components\TextEntry::make('feedback_strengths')
                            ->label('Strengths')
                            ->columnSpanFull()
                            ->default('N/A')
                            ->hidden(fn ($record): bool => empty($record->feedback_strengths)),
                        Infolists\Components\TextEntry::make('feedback_improvements')
                            ->label('Areas for Improvement')
                            ->columnSpanFull()
                            ->default('N/A')
                            ->hidden(fn ($record): bool => empty($record->feedback_improvements)),
                        Infolists\Components\TextEntry::make('rating')
                            ->label('Rating')
                            ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('⭐', $state) : 'N/A'),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('sessions.fields.created_at'))
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('sessions.fields.updated_at'))
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('competition.title')
                    ->label(__('sessions.competition'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('mentor.name')
                    ->label(__('sessions.fields.mentor'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('participant.name')
                    ->label(__('sessions.fields.participant'))
                    ->searchable()
                    ->sortable()
                    ->placeholder(__('sessions.fields.no_participant')),

                TextColumn::make('title')
                    ->label(__('sessions.session_title'))
                    ->searchable()
                    ->limit(30),

                TextColumn::make('scheduled_at')
                    ->label(__('sessions.session_date'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('duration_minutes')
                    ->label(__('sessions.fields.duration'))
                    ->formatStateUsing(fn (int $state): string => self::formatDuration($state))
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label(__('sessions.session_status'))
                    ->formatStateUsing(fn (string $state): string => MentorSession::STATUSES[$state] ?? $state)
                    ->colors([
                        'warning' => 'scheduled',
                        'info' => 'confirmed',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'secondary' => 'no_show',
                    ]),

                TextColumn::make('video_tool')
                    ->label(__('sessions.fields.video_tool'))
                    ->formatStateUsing(fn (?string $state): string => $state ? MentorSession::VIDEO_TOOLS[$state] : 'N/A')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'zoom' => 'blue',
                        'teams' => 'purple',
                        'google_meet' => 'green',
                        default => 'gray',
                    }),

                TextColumn::make('rating')
                    ->label(__('sessions.fields.rating'))
                    ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('⭐', $state) : 'N/A')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label(__('sessions.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('competition_id')
                    ->label(__('sessions.competition'))
                    ->relationship('competition', 'title')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('mentor_id')
                    ->label(__('sessions.fields.mentor'))
                    ->relationship('mentor', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('participant_id')
                    ->label(__('sessions.fields.participant'))
                    ->relationship('participant', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label(__('sessions.session_status'))
                    ->options(MentorSession::STATUSES),

                SelectFilter::make('video_tool')
                    ->label(__('sessions.fields.video_tool'))
                    ->options(MentorSession::VIDEO_TOOLS),

                Filter::make('scheduled_at')
                    ->label(__('sessions.filter_by_date_range'))
                    ->form([
                        DatePicker::make('scheduled_from')
                            ->label(__('sessions.from')),
                        DatePicker::make('scheduled_until')
                            ->label(__('sessions.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['scheduled_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_at', '>=', $date),
                            )
                            ->when(
                                $data['scheduled_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('join_meeting')
                    ->label('Join Meeting')
                    ->icon('heroicon-o-video-camera')
                    ->color('success')
                    ->url(function (MentorSession $record): string {
                        // If join_url exists, use it
                        if (!empty($record->join_url)) {
                            return $record->join_url;
                        }

                        // If meeting_id exists but join_url is missing, try to get it
                        if (!empty($record->meeting_id)) {
                            try {
                                $videoToolService = app(\App\Services\VideoToolIntegrationService::class);
                                $joinUrl = $videoToolService->getSessionJoinUrl($record);
                                if ($joinUrl) {
                                    $record->refresh();
                                    return $joinUrl;
                                }
                            } catch (\Exception $e) {
                                // If we can't get the join URL, return empty
                            }
                        }

                        return '#';
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (MentorSession $record): bool => !empty($record->meeting_id) && $record->isInProgress()),

                Action::make('start_session')
                    ->label('Start Session')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->action(function (MentorSession $record) {
                        try {
                            $service = app(\App\Services\SessionSchedulingService::class);
                            $service->startSession($record);

                            \Filament\Notifications\Notification::make()
                                ->title('Session started successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error starting session')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (MentorSession $record): bool => $record->isUpcoming()),

                Action::make('end_session')
                    ->label('End Session')
                    ->icon('heroicon-o-stop')
                    ->color('warning')
                    ->action(function (MentorSession $record) {
                        try {
                            $service = app(\App\Services\SessionSchedulingService::class);
                            $service->endSession($record);

                            \Filament\Notifications\Notification::make()
                                ->title('Session ended successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error ending session')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (MentorSession $record): bool => $record->isInProgress()),

                Action::make('cancel_session')
                    ->label('Cancel Session')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Session')
                    ->modalDescription('Are you sure you want to cancel this session? This action cannot be undone.')
                    ->action(function (MentorSession $record) {
                        try {
                            $service = app(\App\Services\SessionSchedulingService::class);
                            $service->cancelSession($record);

                            \Filament\Notifications\Notification::make()
                                ->title('Session cancelled successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error cancelling session')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (MentorSession $record): bool => $record->isUpcoming()),

                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                ExportBulkAction::make()
                    ->label(__('sessions.export_sessions'))
                    ->exporter(MentorSessionExporter::class)
                    ->columnMapping(false)
                    ->fileName('Mentorship_Sessions_' . now()->format('Y-m-d')),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->emptyStateHeading(__('sessions.no_sessions_found'))
            ->emptyStateDescription(__('sessions.no_sessions_found_desc'))
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMentorSessions::route('/'),
            // 'create' => Pages\CreateMentorSession::route('/create'), // Admins cannot create sessions
            'view' => Pages\ViewMentorSession::route('/{record}'),
            // 'edit' => Pages\EditMentorSession::route('/{record}/edit'), // Admins cannot edit sessions
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view MentorSessions');
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('view MentorSessions');
    }

    /**
     * Admins cannot create sessions - only view
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Admins cannot edit sessions - only view
     */
    public static function canEdit($record): bool
    {
        return auth()->user()->can('update MentorSessions');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete MentorSessions');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete MentorSessions');
    }

    private static function formatDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours}h";
        }

        return "{$remainingMinutes}m";
    }
}
