<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\Participant;
use App\Models\ProjectComment;
use App\Notifications\ProjectCommentAdded;
use App\Services\ProjectApprovalService;
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
                   ->directory('project-comments')
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
                    ->getStateUsing(function ($record) {
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
                        // For admin comments, always show as read
                        if ($record->user_id && !$record->author_type) {
                            return true;
                        }
                        // For participant comments, show actual read status
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
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->visible(fn () =>
                        !$this->getOwnerRecord()?->isArchived()
                        && (auth()->user()?->can('update Project') ?? false)
                        && (auth()->user()?->can('create ProjectComment') ?? false)
                    )
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Comment')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Attachments')
                            ->multiple()
                            ->directory('project-comments')
                            ->disk('public')
                            ->visibility('public')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','image/png','image/jpeg'])
                            ->maxSize(5120),
                    ])
                    ->action(function (array $data) {
                        $project = $this->getOwnerRecord();
                        if (!$project || $project->isArchived()) {
                            Notification::make()
                                ->title('Error / خطأ')
                                ->body('Cannot update an archived project. / لا يمكن تحديث مشروع مؤرشف.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Prevent comments on pending projects
//                        if ($project->isPending()) {
//                            Notification::make()->title(__('Comments are not allowed on pending projects.'))->danger()->send();
//                            return;
//                        }

                        // Prevent comments on projects with final statuses
                        if ($project->isQualified() || $project->isNotQualified() || $project->isWinner()) {
                            Notification::make()->title(__('Comments are not allowed on evaluated projects.'))->danger()->send();
                            return;
                        }

                        $approvalService = new ProjectApprovalService();
                        $requiresApproval = $approvalService->hasWorkflowForAction('update');

                        if ($requiresApproval) {
                            $result = $approvalService->processAction(
                                'update',
                                [
                                    'comment_create' => [
                                        'comment' => $data['comment'] ?? null,
                                        'attachments' => $data['attachments'] ?? [],
                                    ],
                                    'project_id' => $project->id,
                                    'project_name' => data_get($project->form_submissions, 'project_name', 'N/A'),
                                ],
                                $project->id,
                                'Add project comment request / طلب إضافة تعليق على المشروع'
                            );

                            if (!($result['success'] ?? false)) {
                                Notification::make()
                                    ->title('Error / خطأ')
                                    ->body($result['message'] ?? 'Failed to submit request. / فشل في تقديم الطلب.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            Notification::make()
                                ->title('Request Submitted / تم تقديم الطلب')
                                ->body('Your request has been submitted for approval. / تم تقديم طلبك للموافقة.')
                                ->success()
                                ->send();

                            $this->redirect(route('filament.admin.resources.my-requests.index'));
                            return;
                        }

                        /** @var ProjectComment $comment */
                        $comment = $project->comments()->create([
                            'user_id' => Auth::id(),
                            'comment' => $data['comment'] ?? null,
                            'attachments' => $data['attachments'] ?? [],
                            'is_read' => false,
                        ]);

                        try {
                            $team = $project->team;
                            $leaderMember = $team?->members()->where('is_leader', true)->first();
                            $participant = $leaderMember?->participant ?? $project->application?->participant;
                            if ($participant) {
                                $participant->notify(new ProjectCommentAdded($comment));
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
                        auth()->user()?->can('update ProjectComment') &&
                        $record->is_read === false &&
                        $record->user_id !== auth()->id() // Only show for non-admin comments
                    )
                    ->action(function (ProjectComment $record) {
                        $project = $this->getOwnerRecord();
                        if (!$project || $project->isArchived()) {
                            Notification::make()
                                ->title('Error / خطأ')
                                ->body('Cannot update an archived project. / لا يمكن تحديث مشروع مؤرشف.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $approvalService = new ProjectApprovalService();
                        $requiresApproval = $approvalService->hasWorkflowForAction('update');

                        if ($requiresApproval) {
                            $result = $approvalService->processAction(
                                'update',
                                [
                                    'comment_mark_read' => [
                                        'comment_id' => $record->id,
                                    ],
                                    'project_id' => $project->id,
                                    'project_name' => data_get($project->form_submissions, 'project_name', 'N/A'),
                                ],
                                $project->id,
                                'Mark comment as read request / طلب تعليم التعليق كمقروء'
                            );

                            if (!($result['success'] ?? false)) {
                                Notification::make()
                                    ->title('Error / خطأ')
                                    ->body($result['message'] ?? 'Failed to submit request. / فشل في تقديم الطلب.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            Notification::make()
                                ->title('Request Submitted / تم تقديم الطلب')
                                ->body('Your request has been submitted for approval. / تم تقديم طلبك للموافقة.')
                                ->success()
                                ->send();

                            $this->redirect(route('filament.admin.resources.my-requests.index'));
                            return;
                        }

                        $record->is_read = true;
                        $record->save();
                    }),
            ]);
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('add_comment')
                ->label('Add Comment')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->visible(fn () =>
                    !$this->getOwnerRecord()?->isArchived()
                    && (auth()->user()?->can('update Project') ?? false)
                    && (auth()->user()?->can('create ProjectComment') ?? false)
                )
                ->form([
                    Forms\Components\Textarea::make('comment')
                        ->label('Comment')
                        ->required()
                        ->rows(3),
                    Forms\Components\FileUpload::make('attachments')
                        ->label('Attachments')
                        ->multiple()
                        ->directory('project-comments')
                        ->preserveFilenames()
                        ->acceptedFileTypes(['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','image/png','image/jpeg'])
                        ->maxSize(2048),
                ])
                ->action(function (array $data) {
                    $project = $this->getOwnerRecord();
                    if (!$project || $project->isArchived()) {
                        Notification::make()
                            ->title('Error / خطأ')
                            ->body('Cannot update an archived project. / لا يمكن تحديث مشروع مؤرشف.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Prevent comments on pending projects
                    if ($project->isPending()) {
                        Notification::make()->title(__('Comments are not allowed on pending projects.'))->danger()->send();
                        return;
                    }

                    // Prevent comments on projects with final statuses
                    if ($project->isQualified() || $project->isNotQualified() || $project->isWinner()) {
                        Notification::make()->title(__('Comments are not allowed on evaluated projects.'))->danger()->send();
                        return;
                    }

                    $approvalService = new ProjectApprovalService();
                    $requiresApproval = $approvalService->hasWorkflowForAction('update');

                    if ($requiresApproval) {
                        $result = $approvalService->processAction(
                            'update',
                            [
                                'comment_create' => [
                                    'comment' => $data['comment'] ?? null,
                                    'attachments' => $data['attachments'] ?? [],
                                ],
                                'project_id' => $project->id,
                                'project_name' => data_get($project->form_submissions, 'project_name', 'N/A'),
                            ],
                            $project->id,
                            'Add project comment request / طلب إضافة تعليق على المشروع'
                        );

                        if (!($result['success'] ?? false)) {
                            Notification::make()
                                ->title('Error / خطأ')
                                ->body($result['message'] ?? 'Failed to submit request. / فشل في تقديم الطلب.')
                                ->danger()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Request Submitted / تم تقديم الطلب')
                            ->body('Your request has been submitted for approval. / تم تقديم طلبك للموافقة.')
                            ->success()
                            ->send();

                        $this->redirect(route('filament.admin.resources.my-requests.index'));
                        return;
                    }

                    /** @var ProjectComment $comment */
                    $comment = $project->comments()->create([
                        'user_id' => Auth::id(),
                        'comment' => $data['comment'] ?? null,
                        'attachments' => $data['attachments'] ?? [],
                        'is_read' => false,
                    ]);

                    try {
                        $team = $project->team;
                        $leaderMember = $team?->members()->where('is_leader', true)->first();
                        $participant = $leaderMember?->participant ?? $project->application?->participant;
                        if ($participant) {
                            $participant->notify(new ProjectCommentAdded($comment));
                        }

                    } catch (\Throwable $e) {
                        logger()->error('Failed to send ProjectCommentAdded notification', [
                            'project_id' => $project->id,
                            'comment_id' => $comment->id,
                            'error' => $e->getMessage(),
                        ]);
                        Notification::make()
                            ->title(__('Failed to send email notification'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }

                    Notification::make()->title(__('Comment added'))->success()->send();
                }),
        ];
    }

    protected function afterCreate($record): void {}
}
