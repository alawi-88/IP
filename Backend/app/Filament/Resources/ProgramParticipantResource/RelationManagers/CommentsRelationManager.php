<?php

namespace App\Filament\Resources\ProgramParticipantResource\RelationManagers;

use App\Models\ApplicationComment;
use App\Notifications\ApplicationCommentAdded;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';
    protected static ?string $recordTitleAttribute = 'comment';

    public function form(Form $form): Form
    {
        return $form
           ->schema([
               Forms\Components\Textarea::make('comment')
                   ->label('Comment')
                   ->required()
                   ->maxLength(500)
                   ->rows(3),

               Forms\Components\FileUpload::make('attachments')
                   ->label('Attachments')
                   ->multiple()
                   ->directory('application-comments')
                   ->disk('public')
                   ->visibility('public')
                   ->preserveFilenames()
                   ->acceptedFileTypes(['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','image/png','image/jpeg'])
                   ->maxSize(5120),
           ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('author_display')
                    ->label('Author')
                    ->sortable()
                    ->getStateUsing(function (ApplicationComment $record) {
                        if ($record->user_id && !$record->author_type) {
                            return $record->user?->name ?? 'Admin';
                        }
                        return $record->author?->name ?? 'Participant';
                    }),

                Tables\Columns\TextColumn::make('comment')
                    ->label('Comment')
                    ->limit(50),

                Tables\Columns\ViewColumn::make('attachments')
                    ->label('Files')
                    ->view('filament.custom-columns.file-list'),

                Tables\Columns\IconColumn::make('is_read')
                    ->boolean()
                    ->label('Read')
                    ->getStateUsing(function ($record) {
                        if ($record->user_id && !$record->author_type) {
                            return true;
                        }
                        return $record->is_read;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime(),
            ])
            ->defaultSort('created_at', 'asc')
            ->headerActions([
                Action::make('add_comment')
                    ->label('Add Comment')
                    ->visible(fn () => 
                        auth()->user()?->can('create ApplicationComment') && 
                        !$this->getOwnerRecord()->isArchived() &&
                        $this->getOwnerRecord()->isPending()
                    )
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Comment')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Attachments')
                            ->multiple()
                            ->directory('application-comments')
                            ->disk('public')
                            ->visibility('public')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','image/png','image/jpeg'])
                            ->maxSize(5120),
                    ])
                    ->action(function (array $data) {
                        $application = $this->getOwnerRecord();

                        if (!in_array($application->status, ['pending'])) {
                            Notification::make()->title(__('Comments are locked for processed applications.'))->danger()->send();
                            return;
                        }

                        /** @var ApplicationComment $comment */
                        $comment = $application->comments()->create([
                            'comment' => $data['comment'] ?? null,
                            'attachments' => $data['attachments'] ?? [],
                            'is_read' => false,
                            'user_id' => auth()->id(),
                        ]);

                        try {
                            $participant = $application->participant;
                            if ($participant) {
                                $participant->notify(new ApplicationCommentAdded($comment));
                            }
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title(__('Failed to send email notification'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }

                        Notification::make()->title(__('Comment added'))->success()->send();
                    }),
            ])
            ->actions([
                Action::make('mark_read')
                    ->label('Mark as read')
                    ->icon('heroicon-o-eye')
                    ->visible(fn ($record) =>
                        auth()->user()?->can('update ApplicationComment') &&
                        $record->is_read === false &&
                        $record->user_id !== auth()->id()
                    )
                    ->action(function (ApplicationComment $record) {
                        $record->is_read = true;
                        $record->save();
                    }),
            ]);
    }

    protected function afterCreate($record): void {}
}

