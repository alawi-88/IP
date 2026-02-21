<?php

namespace App\Filament\Resources\ApprovalRequestResource\Pages;

use App\Filament\Resources\ApprovalRequestResource;
use App\Models\ApprovalRequest;
use App\Services\ApprovalRequestService;
use App\Traits\SafeDataFormatting;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use App\Models\ApprovalRequestComment;
use Spatie\Activitylog\Models\Activity;

class ViewApprovalRequest extends ViewRecord
{
    use SafeDataFormatting;
    protected static string $resource = ApprovalRequestResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Check authorization using resource's authorization method
        if (!static::getResource()::canView($this->record)) {
            abort(403, 'Unauthorized access / وصول غير مصرح به');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve / موافقة')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (): bool => $this->record->isPending() && $this->canApprove())
                ->requiresConfirmation()
                ->modalHeading('Approve Request / موافقة على الطلب')
                ->modalDescription('Are you sure you want to approve this request? / هل أنت متأكد من الموافقة على هذا الطلب؟')
                ->form([
                    \Filament\Forms\Components\Textarea::make('comment')
                        ->label('Comment / التعليق')
                        ->placeholder('Add a comment (optional) / أضف تعليق (اختياري)')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $comment = $data['comment'] ?? null;

                    if (!empty($comment)) {
                        ApprovalRequestComment::create([
                            'approval_request_id' => $this->record->id,
                            'user_id' => auth()->id(),
                            'comment' => $comment,
                            'is_internal' => false,
                        ]);
                    }
                    $this->approveRequest($comment);
                }),

            Actions\Action::make('reject')
                ->label('Reject / رفض')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn (): bool => $this->record->isPending() && $this->canApprove())
                ->requiresConfirmation()
                ->modalHeading('Reject Request / رفض الطلب')
                ->modalDescription('Are you sure you want to reject this request? / هل أنت متأكد من رفض هذا الطلب؟')
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection Reason / سبب الرفض')
                        ->required()
                        ->placeholder('Enter rejection reason / أدخل سبب الرفض'),
                    \Filament\Forms\Components\Textarea::make('comment')
                        ->label('Comment / التعليق')
                        ->placeholder('Add a comment (optional) / أضف تعليق (اختياري)')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    if (!empty($data['comment'])) {
                        ApprovalRequestComment::create([
                            'approval_request_id' => $this->record->id,
                            'user_id' => auth()->id(),
                            'comment' => $data['comment'],
                            'is_internal' => false,
                        ]);
                    }
                    $this->rejectRequest($data['rejection_reason']);
                }),

                Actions\Action::make('return')
                    ->label('Return / إعادة للمرسل')
                    ->icon('heroicon-o-arrow-left')
                    ->color('gray')
                    ->visible(fn (): bool => $this->record->isPending() && $this->canApprove())
                    ->form([
                        \Filament\Forms\Components\Textarea::make('comment')
                            ->label('Return Comment / تعليق الإعادة')
                            ->placeholder('Enter return comment (required) / أدخل سبب الإعادة (مطلوب)')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        // Create comment, always required for return
                        ApprovalRequestComment::create([
                            'approval_request_id' => $this->record->id,
                            'user_id' => auth()->id(),
                            'comment' => $data['comment'],
                            'is_internal' => false,
                            'type' => 'return',
                        ]);
                        try {
                            // Update request to "returned" and log action/notify initiator
                            $this->returnToWorkflow(reason: null, comment: $data['comment']);
                            Notification::make()
                                ->title('Request Returned / تم إعادة الطلب')
                                ->body('The approval request has been returned. / تم إعادة طلب الاعتماد.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error Returning Request / خطأ في إعادة الطلب')
                                ->body('An error occurred while returning the request. / حدث خطأ أثناء إعادة الطلب.')
                                ->error()
                                ->send();
                        }
                    }),
        ];
    }

    protected function canApprove(): bool
    {
        // Check if user has permission to approve requests
        return auth()->user()->can('approve ApprovalRequest') ||
               auth()->user()->can('view_any_approval_request');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                Section::make('Request Details / تفاصيل الطلب')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status / الحالة')
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'cancelled' => 'gray',
                                'returned' => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn($state) => match ($state) {
                                'pending' => 'Pending / قيد الانتظار',
                                'approved' => 'Approved / موافق',
                                'rejected' => 'Rejected / مرفوض',
                                'cancelled' => 'Cancelled / ملغي',
                                'returned' => 'Returned / إعادة',
                                default => 'Unknown / غير معروف',
                            }),

                        TextEntry::make('created_at')
                            ->label('Request Date / تاريخ الطلب')
                            ->dateTime('M d, Y H:i')
                            ->formatStateUsing(fn($state) =>
                                $state ? $state->format('M d, Y H:i') : 'No date / لا يوجد تاريخ'
                            ),

                        TextEntry::make('action')
                            ->label('Action / الإجراء')
                            ->badge()
                            ->color('primary')
                            ->formatStateUsing(fn($state) =>
                            is_array($state) ? json_encode($state, JSON_UNESCAPED_UNICODE) : ($state ?: 'No action specified / لم يتم تحديد إجراء')
                            ),

                        TextEntry::make('target_display')
                            ->label('Target / الهدف')
                            ->getStateUsing(fn ($record) => $this->getTargetDisplay($record)),
                    ])
                    ->columns(2),

                Section::make('Requester Information / معلومات مقدم الطلب')
                    ->schema([
                        TextEntry::make('requestedBy.name')
                            ->label('Requester Name / اسم مقدم الطلب')
                            ->formatStateUsing(function ($state, $record) {
                                if (!$record->requestedBy) {
                                    return 'Unknown User / مستخدم غير معروف';
                                }
                                return $state ?: 'Unknown User / مستخدم غير معروف';
                            }),

                        TextEntry::make('requester_email')
                            ->label('Contact / جهة الاتصال')
                            ->getStateUsing(function ($record) {
                                if (!$record->requestedBy) {
                                    return 'No contact information / لا توجد معلومات اتصال';
                                }
                                return $record->requestedBy->email ?: 'No email / لا يوجد بريد إلكتروني';
                            })
                            ->icon('heroicon-o-envelope')
                            ->url(fn ($record) => $record->requestedBy && $record->requestedBy->email
                                ? 'mailto:' . $record->requestedBy->email
                                : null)
                            ->openUrlInNewTab(),

                        TextEntry::make('reason')
                            ->label('Reason / السبب')
                            ->columnSpanFull()
                            ->formatStateUsing(fn($state) =>
                            is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($state ?: 'No reason provided / لم يتم تقديم سبب')
                            ),

                        TextEntry::make('rejection_reason')
                            ->label('Rejection Reason / سبب الرفض')
                            ->columnSpanFull()
                            ->visible(fn($record) => !empty($record->rejection_reason))
                            ->formatStateUsing(fn($state) =>
                            is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($state ?: 'No rejection reason provided / لم يتم تقديم سبب الرفض')
                            ),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Approval Levels / مراحل الموافقة')
                    ->schema([
                        TextEntry::make('no_approval_levels')
                            ->label('')
                            ->getStateUsing(fn($record) =>
                            $record->approvalRequestLevels()->count() === 0 ? 'No approval levels configured / لا توجد مراحل موافقة مكونة' : null
                            )
                            ->visible(fn($record) => $record->approvalRequestLevels()->count() === 0),

                        RepeatableEntry::make('approvalRequestLevels')
                            ->label('')
                            ->getStateUsing(fn($record) => $record->approvalRequestLevels()->with('approver')->orderBy('level_number')->get())
                            ->visible(fn($record) => $record->approvalRequestLevels()->count() > 0)
                            ->schema([
                                TextEntry::make('level_number')
                                    ->label('Level / المرحلة')
                                    ->badge()
                                    ->color('gray')
                                    ->formatStateUsing(fn($state) =>
                                    is_array($state) ? json_encode($state, JSON_UNESCAPED_UNICODE) : "L{$state}"
                                    ),

                                TextEntry::make('status')
                                    ->label('Status / الحالة')
                                    ->badge()
                                    ->color(fn($state) => match ($state) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'cancelled' => 'gray',
                                        'returned' => 'info',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn($state) => match ($state) {
                                        'pending' => 'Pending / قيد الانتظار',
                                        'approved' => 'Approved / موافق',
                                        'rejected' => 'Rejected / مرفوض',
                                        'returned' => 'Returned / إعادة',
                                        'cancelled' => 'Cancelled / ملغي',
                                        default => 'Unknown / غير معروف',
                                    }),

                                TextEntry::make('approved_at')
                                    ->label('Approved At / تاريخ الموافقة')
                                    ->dateTime()
                                    ->placeholder('Not approved / لم يتم الموافقة'),

                                TextEntry::make('rejected_at')
                                    ->label('Rejected At / تاريخ الرفض')
                                    ->dateTime()
                                    ->placeholder('Not rejected / لم يتم الرفض'),

                                ViewEntry::make('votes_timeline')
                                    ->label('')
                                    ->getStateUsing(function ($record) {
                                        $required = (int) ($record->required_approvals ?? 1);
                                        $required = max($required, 1);

                                        $votes = $record->votes()
                                            ->with('user.roles')
                                            ->orderBy('created_at')
                                            ->get();

                                        $items = [];
                                        for ($i = 1; $i <= $required; $i++) {
                                            $vote = $votes->get($i - 1);

                                            if ($vote) {
                                                $roleNames = $vote->user?->roles?->pluck('name')->values()->all() ?? [];
                                                $items[] = [
                                                    'index' => $i,
                                                    'status' => $vote->decision,
                                                    'decision_maker' => ($vote->user?->name ?? 'Unknown') . ' -> ' . (!empty($roleNames) ? implode(', ', $roleNames) : '—'),
                                                    'comment' => $vote->comment,
                                                    'created_at' => $vote->created_at,
                                                ];
                                            } else {
                                                $items[] = [
                                                    'index' => $i,
                                                    'status' => 'pending',
                                                    'decision_maker' => null,
                                                    'comment' => null,
                                                    'created_at' => null,
                                                ];
                                            }
                                        }

                                        return $items;
                                    })
                                    ->view('filament.components.approval-level-votes-timeline')
                                    ->columnSpanFull(),

                                // Visual divider between levels (hide for the last level)
                                ViewEntry::make('level_divider')
                                    ->label('')
                                    ->view('filament.components.approval-level-divider')
                                    ->columnSpanFull()
                                    ->visible(function ($record) {
                                        $maxLevel = \App\Models\ApprovalRequestLevel::where('approval_request_id', $record->approval_request_id)
                                            ->max('level_number');
                                        return (int) $record->level_number < (int) $maxLevel;
                                    }),
                            ])
                            ->columns(4)
                            ->contained(false),
                    ])
                    ->collapsible()
                    ->collapsed(false),


                Section::make('Proposed Changes / التغييرات المقترحة')
                    ->description('View all proposed changes with before/after values / عرض جميع التغييرات المقترحة مع القيم قبل وبعد')
                    ->schema([
                        ViewEntry::make('action_data')
                            ->label('')
                            ->view('filament.custom-entries.action-data-display', [
                                'program_id' => $record->action_data['program_id'] ?? null
                            ])
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->action_data)),

                        TextEntry::make('no_changes')
                            ->label('')
                            ->getStateUsing(fn($record) =>
                                empty($record->action_data) ? 'No changes proposed / لا توجد تغييرات مقترحة' : null
                            )
                            ->visible(fn($record) => empty($record->action_data))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Comments / التعليقات')
                    ->schema([
                        TextEntry::make('no_comments')
                            ->label('')
                            ->getStateUsing(fn($record) =>
                                $record->comments()->count() === 0 ? 'No comments available / لا توجد تعليقات متاحة' : null
                            )
                            ->visible(fn($record) => $record->comments()->count() === 0)
                            ->columnSpanFull(),

                        RepeatableEntry::make('comments')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                try {
                                    $comments = $record->comments()
                                        ->with('user')
                                        ->orderBy('created_at', 'desc')
                                        ->get();
                                    return $comments->toArray();
                                } catch (\Exception $e) {
                                    \Log::error('Error getting comments: ' . $e->getMessage());
                                    return [];
                                }
                            })
                            ->visible(fn ($record) => $record->comments()->count() > 0)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Commenter / المعلق')
                                    ->badge()
                                    ->color('primary')
                                    ->formatStateUsing(fn($state) =>
                                        $state ?: 'Unknown User / مستخدم غير معروف'
                                    ),

                                TextEntry::make('comment')
                                    ->label('Comment / التعليق')
                                    ->formatStateUsing(fn($state) =>
                                        $state ?: 'No comment / لا يوجد تعليق'
                                    )
                                    ->markdown()
                                    ->columnSpanFull(),

                                TextEntry::make('created_at')
                                    ->label('Timestamp / الطابع الزمني')
                                    ->dateTime('M d, Y H:i')
                                    ->formatStateUsing(function ($state) {
                                        if (is_string($state)) {
                                            try {
                                                return \Carbon\Carbon::parse($state)->format('M d, Y H:i');
                                            } catch (\Exception $e) {
                                                return $state;
                                            }
                                        }
                                        return $state ? $state->format('M d, Y H:i') : 'No date / لا يوجد تاريخ';
                                    }),

                                TextEntry::make('is_internal')
                                    ->label('Type / النوع')
                                    ->badge()
                                    ->color(fn($state) =>
                                        (is_bool($state) && $state) || (is_array($state) && ($state['is_internal'] ?? false))
                                            ? 'warning'
                                            : 'info'
                                    )
                                    ->formatStateUsing(function ($state) {
                                        if (is_bool($state)) {
                                            return $state ? 'Internal / داخلي' : 'Public / عام';
                                        }
                                        if (is_array($state) && isset($state['is_internal'])) {
                                            return $state['is_internal'] ? 'Internal / داخلي' : 'Public / عام';
                                        }
                                        return 'Unknown / غير معروف';
                                    }),
                            ])
                            ->columns(3)
                            ->contained(false),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make('History Timeline / الجدول الزمني للتاريخ')
                    ->description('Timeline of comments and actions made by approvers / الجدول الزمني للتعليقات والإجراءات التي قام بها الموافقون')
                    ->schema([
                        TextEntry::make('no_history')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                $hasComments = $record->comments()->count() > 0;
                                $hasActivities = Activity::where('subject_type', ApprovalRequest::class)
                                    ->where('subject_id', $record->id)
                                    ->count() > 0;

                                return (!$hasComments && !$hasActivities)
                                    ? 'No history available / لا يوجد تاريخ متاح'
                                    : null;
                            })
                            ->visible(function ($record) {
                                $hasComments = $record->comments()->count() > 0;
                                $hasActivities = Activity::where('subject_type', ApprovalRequest::class)
                                    ->where('subject_id', $record->id)
                                    ->count() > 0;
                                return !$hasComments && !$hasActivities;
                            })
                            ->columnSpanFull(),

                        RepeatableEntry::make('history_timeline')
                            ->label('')
                            ->getStateUsing(fn ($record) => $record->history_timeline)
                            ->visible(function ($record) {
                                $hasComments = $record->comments()->count() > 0;
                                $hasActivities = Activity::where('subject_type', ApprovalRequest::class)
                                    ->where('subject_id', $record->id)
                                    ->count() > 0;
                                return $hasComments || $hasActivities;
                            })
                            ->schema([
                                TextEntry::make('timestamp')
                                    ->label('Date & Time / التاريخ والوقت'),

                                TextEntry::make('user.name')
                                    ->label('User / المستخدم')
                                    ->badge()
                                    ->color('primary')
                                    ->formatStateUsing(fn ($state) => $state ?: 'System / النظام'),

                                TextEntry::make('action')
                                    ->label('Action / الإجراء')
                                    ->badge()
                                    ->color(function ($state) {
                                        $state = (string) $state;
                                        return (str_contains($state, 'Comment') || str_contains($state, 'تعليق')) ? 'info' : 'success';
                                    })
                                    ->formatStateUsing(fn ($state) => $state ?: 'Unknown / غير معروف'),

                                TextEntry::make('description')
                                    ->label('Description / الوصف')
                                    ->markdown(),
                            ])
                            ->columns(4)
                            ->contained(false),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    protected function formatActivityDescription(Activity $activity): string
    {
        $description = $activity->description ?? '';
        $properties = $activity->properties ?? [];

        if (!empty($properties['attributes'])) {
            $changes = [];
            foreach ($properties['attributes'] as $key => $value) {
                if ($key === 'status') {
                    $oldStatus = $properties['old']['status'] ?? null;
                    $newStatus = $value;
                    $changes[] = "Status changed from '{$oldStatus}' to '{$newStatus}' / تغيرت الحالة من '{$oldStatus}' إلى '{$newStatus}'";
                }
            }
            if (!empty($changes)) {
                return implode("\n", $changes);
            }
        }

        return $description ?: 'Action performed / تم تنفيذ الإجراء';
    }


    protected function approveRequest(?string $comment = null): void
    {
        $approvalRequestService = app(ApprovalRequestService::class);

        // Get the current pending level that needs approval
        $currentLevel = $this->record->getCurrentLevel();

        if (!$currentLevel) {
            // If no pending level, check if request has no levels (legacy support)
            if ($this->record->approvalRequestLevels()->count() === 0) {
                // Direct approval for requests without levels
                $this->record->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);

                Notification::make()
                    ->title('Request Approved / تم الموافقة على الطلب')
                    ->body('The approval request has been approved successfully. / تم الموافقة على طلب الاعتماد بنجاح.')
                    ->success()
                    ->send();
                return;
            }

            // All levels are already processed
            Notification::make()
                ->title('No Pending Level / لا يوجد مستوى معلق')
                ->body('There is no pending level to approve. / لا يوجد مستوى معلق للموافقة عليه.')
                ->warning()
                ->send();
            return;
        }

        // Approve the current level using the service
        $result = $approvalRequestService->approveLevel(
            $this->record,
            $currentLevel->level_number,
            auth()->user(),
            $comment
        );

        if (($result['success'] ?? false) === true) {
            // Refresh the record to show updated status
            $this->record->refresh();
            $this->record->load('approvalRequestLevels');

            $required = $result['required'] ?? $currentLevel->required_approvals ?? 1;
            $approvals = $result['approvals'] ?? 0;

            if (($result['finalized'] ?? false) && ($result['status'] ?? null) === 'approved') {
                Notification::make()
                    ->title('Level Approved / تم الموافقة على المستوى')
                    ->body("Level {$currentLevel->level_number} approved ({$approvals}/{$required}).")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Approval Recorded / تم تسجيل الموافقة')
                    ->body("Recorded your approval for level {$currentLevel->level_number} ({$approvals}/{$required}). Waiting for more approvers.")
                    ->info()
                    ->send();
            }
        } else {
            Notification::make()
                ->title('Approval Failed / فشل الموافقة')
                ->body($result['message'] ?? 'Failed to approve the level. Please check your permissions.')
                ->danger()
                ->send();
        }
    }

    protected function rejectRequest(string $reason): void
    {
        $approvalRequestService = app(ApprovalRequestService::class);

        // Get the current pending level that needs approval
        $currentLevel = $this->record->getCurrentLevel();

        if (!$currentLevel) {
            // If no pending level, check if request has no levels (legacy support)
            if ($this->record->approvalRequestLevels()->count() === 0) {
                // Direct rejection for requests without levels
                $this->record->update([
                    'status' => 'rejected',
                    'rejected_at' => now(),
                    'rejection_reason' => $reason,
                ]);

                Notification::make()
                    ->title('Request Rejected / تم رفض الطلب')
                    ->body('The approval request has been rejected. / تم رفض طلب الاعتماد.')
                    ->warning()
                    ->send();
                return;
            }

            // All levels are already processed
            Notification::make()
                ->title('No Pending Level / لا يوجد مستوى معلق')
                ->body('There is no pending level to reject. / لا يوجد مستوى معلق للرفض.')
                ->warning()
                ->send();
            return;
        }

        // Reject the current level using the service
        $result = $approvalRequestService->rejectLevel(
            $this->record,
            $currentLevel->level_number,
            auth()->user(),
            $reason
        );

        if (($result['success'] ?? false) === true) {
            // Refresh the record to show updated status
            $this->record->refresh();
            $this->record->load('approvalRequestLevels');

            $required = $result['required'] ?? $currentLevel->required_approvals ?? 1;
            $rejections = $result['rejections'] ?? 0;

            if (($result['finalized'] ?? false) && ($result['status'] ?? null) === 'rejected') {
                Notification::make()
                    ->title('Level Rejected / تم رفض المستوى')
                    ->body("Level {$currentLevel->level_number} rejected ({$rejections}/{$required}).")
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Rejection Recorded / تم تسجيل الرفض')
                    ->body("Recorded your rejection for level {$currentLevel->level_number} ({$rejections}/{$required}). Waiting for more approvers.")
                    ->info()
                    ->send();
            }
        } else {
            Notification::make()
                ->title('Rejection Failed / فشل الرفض')
                ->body($result['message'] ?? 'Failed to reject the level. Please check your permissions.')
                ->danger()
                ->send();
        }
    }
    /**
     * Return the request to the initiator with comment.
     *
     * @param string|null $reason
     * @param string|null $comment
     */
    protected function returnToWorkflow(?string $reason = null, ?string $comment = null): void
    {
        // Update the record status to "returned", and set the return reason/comment
        $this->record->update([
            'status' => 'returned',
            'return_reason' => $reason,
        ]);

        // Optionally, add a comment to ApprovalRequestComment for more visibility
        if (!empty($comment)) {
            \App\Models\ApprovalRequestComment::create([
                'approval_request_id' => $this->record->id,
                'user_id' => auth()->id(),
                'comment' => $comment,
                'is_internal' => false,
                'type' => 'return', // Optional: helpful for context/filtering
            ]);
        }

        // Notify the initiator (creator) with the comment
        $initiator = $this->record->initiator ?? $this->record->creator ?? $this->record->user;
        if ($initiator && $initiator->id !== auth()->id()) {
            \Filament\Notifications\Notification::make()
                ->title('Your Request Was Returned / تم إعادة طلبك')
                ->body(
                    ($comment
                        ? "Your request has been returned by the approver. Comments: {$comment}"
                        : "Your request has been returned by the approver / تم إعادة طلبك من قبل المسؤول."
                    ) . "\n\nReason: " . ($reason ?: '-')
                )
                ->success()
                ->sendToDatabase($initiator);
        }

        // Log the return action
        \Log::info("ApprovalRequest [{$this->record->id}] was returned by user [".auth()->id()."]. Reason: ".($reason ?: '-').". Comment: ".($comment ?: '-'));

        // Optionally, show notification to the approver that action succeeded
        Notification::make()
            ->title('Request Returned to Initiator / تم إعادة الطلب للمبادر')
            ->body('The approval request has been sent back to the initiator with your comment. / تم إعادة الطلب إلى المبادر مع تعليقك.')
            ->success()
            ->send();
    }

    protected function cancelRequest(): void
    {
        $this->record->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        Notification::make()
            ->title('Request Cancelled / تم إلغاء الطلب')
            ->body('The approval request has been cancelled. / تم إلغاء طلب الاعتماد.')
            ->info()
            ->send();
    }

    /**
     * Get the display name for the target model
     */
    protected function getTargetDisplay($record): string
    {
        if (!isset($record->target_id) || empty($record->target_id) || !isset($record->target_type)) {
            return $this->getTargetDisplayFromActionData($record->action_data ?? []);
        }

        $targetType = $record->target_type;

        if ($targetType === 'App\\Models\\Project' || $targetType === \App\Models\Project::class) {
            return $this->getProjectDisplay($record);
        }

        if ($targetType === 'App\\Models\\Program' || $targetType === \App\Models\Program::class) {
            return $this->getProgramDisplay($record);
        }

        if ($targetType === 'App\\Models\\ProgramApplication' || $targetType === \App\Models\ProgramApplication::class) {
            return $this->getProgramApplicationDisplay($record);
        }

        if ($record->target) {
            return $this->getTargetDisplayFromModel($record->target, $targetType);
        }
        return $this->getTargetDisplayFromActionData($record->action_data ?? []);
    }

    /**
     * Get display name for Project model
     */
    protected function getProjectDisplay($record): string
    {
        if ($record->project) {
            $formSubmissions = $record->project->form_submissions;
            if ($formSubmissions) {
                $projectName = is_array($formSubmissions)
                    ? ($formSubmissions['project_name'] ?? null)
                    : ($formSubmissions->project_name ?? null);
                if ($projectName) {
                    return $projectName;
                }
            }
            return 'Project #' . $record->project->id;
        }
        $actionData = $record->action_data ?? [];
        if (isset($actionData['project_name'])) {
            return $actionData['project_name'];
        }

        return 'No project information / لا توجد معلومات المشروع';
    }

    /**
     * Get display name for Program model
     */
    protected function getProgramDisplay($record): string
    {
        if ($record->program) {
            $title = $record->program->title;
            if (is_array($title)) {
                return $title['en'] ?? $title['ar'] ?? 'Program #' . $record->program->id;
            }
            return $title ?: 'Program #' . $record->program->id;
        }

        $actionData = $record->action_data ?? [];
        if (isset($actionData['title'])) {
            $title = $actionData['title'];
            if (is_array($title)) {
                return $title['en'] ?? $title['ar'] ?? null;
            }
            return $title;
        }

        return 'No program information / لا توجد معلومات البرنامج';
    }

    /**
     * Get display name for ProgramApplication model
     */
    protected function getProgramApplicationDisplay($record): string
    {
        if ($record->application) {
            if ($record->application->team_name) {
                return $record->application->team_name;
            }
            if ($record->application->participant && $record->application->participant->name) {
                return $record->application->participant->name;
            }
            return 'Application #' . $record->application->id;
        }

        return 'No application information / لا توجد معلومات الطلب';
    }

    /**
     * Get display name from target model using common fields
     */
    protected function getTargetDisplayFromModel($target, string $targetType): string
    {
        if (isset($target->name)) {
            $name = $target->name;
            if (is_array($name)) {
                $locale = app()->getLocale();
                $value = $name[$locale] ?? $name['en'] ?? $name['ar'] ?? reset($name);
                return is_string($value) ? $value : json_encode($name, JSON_UNESCAPED_UNICODE);
            }
            return (string) $name;
        }

        if (isset($target->title)) {
            $title = $target->title;
            if (is_array($title)) {
                $locale = app()->getLocale();
                $value = $title[$locale] ?? $title['en'] ?? $title['ar'] ?? reset($title);
                return is_string($value) ? $value : json_encode($title, JSON_UNESCAPED_UNICODE);
            }
            return (string) $title;
        }

        if (isset($target->email)) {
            return (string) $target->email;
        }

        $className = class_basename($targetType);
        return $className . ' #' . (string) ($target->id ?? '');
    }

    /**
     * Get display name from action_data as fallback
     */
    protected function getTargetDisplayFromActionData(array $actionData): string
    {
        if (empty($actionData) || !is_array($actionData)) {
            return 'No target information / لا توجد معلومات الهدف';
        }

        // Try name field
        if (isset($actionData['name'])) {
            $name = $actionData['name'];
            if (is_array($name)) {
                return $name['en'] ?? $name['ar'] ?? 'No target information / لا توجد معلومات الهدف';
            }
            return (string) $name;
        }

        // Try title field (might be translatable)
        if (isset($actionData['title'])) {
            $title = $actionData['title'];
            if (is_array($title)) {
                return $title['en'] ?? $title['ar'] ?? 'No target information / لا توجد معلومات الهدف';
            }
            return (string) $title;
        }

        // Try email field
        if (isset($actionData['email'])) {
            return (string) $actionData['email'];
        }

        // Try project_name field
        if (isset($actionData['project_name'])) {
            return (string) $actionData['project_name'];
        }

        return 'No target information / لا توجد معلومات الهدف';
    }
}
