<?php

namespace App\Filament\Resources\TaskAssignmentResource\Pages;

use App\Filament\Resources\TaskAssignmentResource;
use App\Models\TaskAssignment;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTaskAssignment extends ViewRecord
{
    protected static string $resource = TaskAssignmentResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Task Details / تفاصيل المهمة')
                ->columns(2)
                ->schema([
                    TextEntry::make('title')
                        ->label('Title')
                        ->getStateUsing(fn ($record) => $record->getTranslation('title', 'en')),
                    TextEntry::make('title_ar')
                        ->label('العنوان')
                        ->getStateUsing(fn ($record) => $record->getTranslation('title', 'ar')),
                    TextEntry::make('program.title')
                        ->label('Program')
                        ->getStateUsing(fn ($record) => $record->program?->getTranslation('title', 'en')),
                    TextEntry::make('stage.title')
                        ->label('Stage')
                        ->getStateUsing(fn ($record) => $record->stage?->getTranslation('title', 'en') ?? 'N/A'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn ($record) => $record->status_color),
                    TextEntry::make('assignment_type')
                        ->badge(),
                    TextEntry::make('assignee')
                        ->label('Assigned To')
                        ->getStateUsing(fn ($record) => $record->assignee_name),
                    TextEntry::make('due_date')
                        ->date()
                        ->color(fn ($record) => $record->due_date && $record->isOverdue() ? 'danger' : null),
                    TextEntry::make('assignedByUser.name')
                        ->label('Assigned By'),
                    TextEntry::make('created_at')
                        ->since(),
                ]),

            Section::make('Description / الوصف')
                ->schema([
                    TextEntry::make('description_en')
                        ->label('Description (English)')
                        ->html()
                        ->getStateUsing(fn ($record) => $record->getTranslation('description', 'en')),
                    TextEntry::make('description_ar')
                        ->label('الوصف (عربي)')
                        ->html()
                        ->getStateUsing(fn ($record) => $record->getTranslation('description', 'ar')),
                ]),

            Section::make('Instructions / التعليمات')
                ->schema([
                    TextEntry::make('instructions_en')
                        ->label('Instructions (English)')
                        ->html()
                        ->getStateUsing(fn ($record) => $record->getTranslation('instructions', 'en')),
                ]),

            Section::make('File Requirements / متطلبات الملفات')
                ->columns(2)
                ->schema([
                    TextEntry::make('allowed_file_formats')
                        ->label('Allowed Formats')
                        ->getStateUsing(fn ($record) => $record->allowed_file_formats ? implode(', ', $record->allowed_file_formats) : 'Any'),
                    TextEntry::make('max_file_size_mb')
                        ->label('Max File Size')
                        ->suffix(' MB'),
                ]),

            Section::make('Latest Submission / آخر تسليم')
                ->visible(fn ($record) => $record->latestSubmission !== null)
                ->schema([
                    TextEntry::make('latestSubmission.version')
                        ->label('Version'),
                    TextEntry::make('latestSubmission.status')
                        ->badge(),
                    TextEntry::make('latestSubmission.submitted_at')
                        ->label('Submitted At')
                        ->dateTime(),
                    TextEntry::make('latestSubmission.admin_feedback')
                        ->label('Admin Feedback')
                        ->visible(fn ($record) => $record->latestSubmission?->admin_feedback),
                ]),

            Section::make('Review Details / تفاصيل المراجعة')
                ->visible(fn ($record) => $record->reviewed_at !== null)
                ->columns(2)
                ->schema([
                    TextEntry::make('reviewedByUser.name')
                        ->label('Reviewed By'),
                    TextEntry::make('reviewed_at')
                        ->label('Reviewed At')
                        ->dateTime(),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // IN-2065: Review Task Submissions - Approve
            Action::make('approveTask')
                ->label('Approve / موافقة')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $this->record->canReview())
                ->form([
                    Forms\Components\Textarea::make('feedback')
                        ->label('Feedback / ملاحظات')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $submission = $this->record->latestSubmission;
                    if ($submission) {
                        $submission->update([
                            'status' => 'approved',
                            'admin_feedback' => $data['feedback'] ?? null,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    }

                    $this->record->approve(auth()->id(), $data['feedback'] ?? null);

                    // Send notification
                    try {
                        $this->notifyParticipant('approved');
                    } catch (\Exception $e) {
                        \Log::warning('Task status notification failed: ' . $e->getMessage());
                    }

                    Notification::make()
                        ->title('Task Approved / تم قبول المهمة')
                        ->success()
                        ->send();
                }),

            // IN-2065: Review Task Submissions - Reject
            Action::make('rejectTask')
                ->label('Reject / رفض')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn () => $this->record->canReview())
                ->form([
                    Forms\Components\Textarea::make('feedback')
                        ->label('Rejection Reason / سبب الرفض')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $submission = $this->record->latestSubmission;
                    if ($submission) {
                        $submission->update([
                            'status' => 'rejected',
                            'admin_feedback' => $data['feedback'],
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    }

                    $this->record->reject(auth()->id(), $data['feedback']);

                    try {
                        $this->notifyParticipant('rejected');
                    } catch (\Exception $e) {
                        \Log::warning('Task status notification failed: ' . $e->getMessage());
                    }

                    Notification::make()
                        ->title('Task Rejected / تم رفض المهمة')
                        ->danger()
                        ->send();
                }),

            // IN-2065: Request Revision
            Action::make('requestRevision')
                ->label('Request Revision / طلب تعديل')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => $this->record->canReview())
                ->form([
                    Forms\Components\Textarea::make('feedback')
                        ->label('Revision Notes / ملاحظات التعديل')
                        ->required()
                        ->rows(3)
                        ->helperText('Describe what needs to be changed in the submission.'),
                ])
                ->action(function (array $data) {
                    $submission = $this->record->latestSubmission;
                    if ($submission) {
                        $submission->update([
                            'status' => 'revision_requested',
                            'admin_feedback' => $data['feedback'],
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    }

                    $this->record->requestRevision(auth()->id(), $data['feedback']);

                    try {
                        $this->notifyParticipant('revision_requested');
                    } catch (\Exception $e) {
                        \Log::warning('Task status notification failed: ' . $e->getMessage());
                    }

                    Notification::make()
                        ->title('Revision Requested / تم طلب التعديل')
                        ->warning()
                        ->send();
                }),
        ];
    }

    private function notifyParticipant(string $newStatus): void
    {
        $assignment = $this->record;

        if ($assignment->assignment_type === 'team' && $assignment->team) {
            $members = $assignment->team->members ?? collect();
            foreach ($members as $member) {
                if ($member->participant) {
                    $member->participant->notify(new \App\Notifications\Participant\TaskStatusChangedNotification($assignment, $newStatus));
                }
            }
        } elseif ($assignment->assignment_type === 'participant' && $assignment->participant) {
            $assignment->participant->notify(new \App\Notifications\Participant\TaskStatusChangedNotification($assignment, $newStatus));
        }
    }
}
