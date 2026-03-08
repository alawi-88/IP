<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MentorVideoToolResource\Pages;
use App\Models\MentorVideoTool;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class MentorVideoToolResource extends Resource
{
    protected static ?string $model = MentorVideoTool::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'Video Tool Integrations';

    protected static ?string $modelLabel = 'Video Tool Integration';

    protected static ?string $pluralModelLabel = 'Video Tool Integrations';

    protected static ?string $navigationGroup = 'Users & Roles';

    protected static ?int $navigationSort = 5;

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

                Forms\Components\Select::make('tool_type')
                    ->label('Video Tool')
                    ->options(MentorVideoTool::TOOL_TYPES)
                    ->required()
                    ->disabled(),

                Forms\Components\TextInput::make('account_email')
                    ->label('Account Email')
                    ->email()
                    ->disabled(),

                Forms\Components\TextInput::make('account_id')
                    ->label('Account ID')
                    ->disabled(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                Forms\Components\Toggle::make('is_default')
                    ->label('Default Tool')
                    ->default(false),

                Forms\Components\DateTimePicker::make('last_sync_at')
                    ->label('Last Sync')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mentor.name')
                    ->label('Mentor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tool_type')
                    ->label('Video Tool')
                    ->formatStateUsing(fn (string $state): string => MentorVideoTool::TOOL_TYPES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'zoom' => 'blue',
                        'teams' => 'purple',
                        'google_meet' => 'green',
                        default => 'gray',
                    }),

                TextColumn::make('account_email')
                    ->label('Account Email')
                    ->searchable()
                    ->copyable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('last_sync_at')
                    ->label('Last Sync')
                    ->dateTime()
                    ->sortable()
                    ->since(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tool_type')
                    ->label('Video Tool')
                    ->options(MentorVideoTool::TOOL_TYPES),

                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),

                TernaryFilter::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueLabel('Default only')
                    ->falseLabel('Non-default only')
                    ->native(false),
            ])
            ->actions([
                Action::make('disconnect')
                    ->label('Disconnect')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Disconnect Video Tool')
                    ->modalDescription('Are you sure you want to disconnect this video tool integration? This action cannot be undone.')
                    ->action(function (MentorVideoTool $record) {
                        try {
                            $service = app(\App\Services\VideoToolIntegrationService::class);
                            $success = $service->disconnectTool($record->mentor, $record->tool_type);
                            
                            if ($success) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Video tool disconnected successfully')
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Failed to disconnect video tool')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error disconnecting video tool')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListMentorVideoTools::route('/'),
            'create' => Pages\CreateMentorVideoTool::route('/create'),
            'edit' => Pages\EditMentorVideoTool::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view VideoToolIntegrations');
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('view VideoToolIntegrations');
    }

    public static function canCreate(): bool
    {
        return false; // Video tools are created through OAuth flow
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update VideoToolIntegrations');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete VideoToolIntegrations');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete VideoToolIntegrations');
    }
}
