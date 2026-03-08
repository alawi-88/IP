<?php

namespace App\Filament\Resources\ProgramParticipantResource\Pages;

use App\Filament\Resources\ProgramParticipantResource;
use App\Models\ProgramApplication;
use App\Models\Program;
use App\Models\Project;
use App\Services\ApplicationApprovalService;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListProgramParticipants extends ListRecords
{
    protected static string $resource = ProgramParticipantResource::class;

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $currentProgramId = session('current_program_id');

        // Build base query - only submission type applications
        $baseQuery = ProgramApplication::query()
            ->where('type', 'submission')
            ->with(['program.stages' => function ($q) {
                $q->where('slug', '!=', 'registration')
                  ->orderBy('starts_at', 'asc');
            }, 'participant', 'team']);

        // Filter by current program if set
        if ($currentProgramId) {
            $baseQuery->where('program_id', $currentProgramId);
        }

        // Filter by admin's assigned programs (unless super admin)
        if ($user && !$user->isSuperAdmin()) {
            // Get program IDs the user has access to
            $programIds = \App\Models\UserProgram::where('user_id', $user->id)
                ->pluck('program_id')
                ->toArray();

            if (!empty($programIds)) {
                $baseQuery->whereIn('program_id', $programIds);
            } else {
                // If user has no assigned programs, show nothing
                $baseQuery->whereRaw('1 = 0');
            }
        } else {
            // Super admin sees all participants (filtered by current program if set)
            if (!$currentProgramId) {
                $baseQuery = ProgramApplication::byProgram()->where('type', 'submission');
            }
        }

        // Set default query
        $table->query($baseQuery);

        // Get current program for dynamic columns
        $currentProgram = $currentProgramId ? Program::with(['stages' => function ($q) {
            $q->where('slug', '!=', 'registration')
              ->orderBy('starts_at', 'asc');
        }])->find($currentProgramId) : null;

        // Build columns
        $columns = $this->buildBaseColumns();

        // Add dynamic stage columns if program is selected
        if ($currentProgram && $currentProgram->stages) {
            $stages = $currentProgram->stages;
            foreach ($stages as $stage) {
                $formIds = $stage->getFormIds();
                if (!empty($formIds)) {
                    // Skip submission column for evaluation stages
                    $isEvaluationStage = $stage->slug === 'evaluation' || str_starts_with($stage->slug, 'evaluation');
                    
                    if (!$isEvaluationStage) {
                        // Submission status column
                        $columns[] = Tables\Columns\TextColumn::make("stage_{$stage->id}_submission")
                            ->label("{$stage->title} Submission")
                            ->getStateUsing(function ($record) use ($stage, $formIds) {
                                $project = Project::where('application_id', $record->id)
                                    ->whereIn('form_id', $formIds)
                                    ->where('type', 'submission')
                                    ->where('is_archived', false)
                                    ->first();
                                return $project ? 'Submitted' : 'Not Submitted';
                            })
                            ->badge()
                            ->color(fn ($record, $state) => str_contains($state, 'Submitted') && !str_contains($state, 'Not') ? 'success' : 'gray')
                            ->toggleable();
                    }

                    // Project score column for project stages
                    if (str_starts_with($stage->slug, 'project-')) {
                        $columns[] = Tables\Columns\TextColumn::make("stage_{$stage->id}_score")
                            ->label("{$stage->title} Score")
                            ->getStateUsing(function ($record) use ($stage, $formIds) {
                                // Get all projects for this stage (not just the first one)
                                $projects = Project::where('application_id', $record->id)
                                    ->whereIn('form_id', $formIds)
                                    ->where('is_archived', false)
                                    ->where('type', 'submission')
                                    ->get();
                                
                                if ($projects->isEmpty()) {
                                    return '—';
                                }
                                
                                // Sum all AI project scores for this stage (matching leaderboard logic)
                                $totalScore = 0;
                                foreach ($projects as $project) {
                                    // Calculate AI score only (for Project Score)
                                    $aiScore = 0;
                                    $aiResponse = $project->ai_evaluation_response;
                                    $aiStatus = data_get($aiResponse, 'status');
                                    $hasCompletedAi = $aiStatus === 'completed' && is_array($aiResponse);

                                    if ($hasCompletedAi) {
                                        // AI evaluation is completed - use AI score from meta data (preferred)
                                        $normalizedScore = data_get($aiResponse, 'meta.normalized_score');
                                        $aiTotalScore = data_get($aiResponse, 'meta.total_score');

                                        if ($normalizedScore !== null) {
                                            $aiScore = (float) $normalizedScore;
                                        } elseif ($aiTotalScore !== null) {
                                            $aiScore = (float) $aiTotalScore;
                                        } else {
                                            // Last fallback: calculate from criteria if meta is not available
                                            $criteria = data_get($aiResponse, 'data.criteria', []);
                                            foreach ($criteria as $criterion) {
                                                $aiScore += (float) (data_get($criterion, 'totalScore', 0));
                                            }
                                        }
                                    }
                                    
                                    $totalScore += $aiScore;
                                }
                                
                                return $totalScore > 0 ? number_format($totalScore, 2) : '—';
                            })
                            ->sortable()
                            ->toggleable();
                    }
                }
            }
        }

        // Add program column if no current program is set
        if (!$currentProgramId) {
            $columns[] = Tables\Columns\TextColumn::make('program.title')
                ->label('Program')
                ->searchable()
                ->sortable()
                ->toggleable();
        }

        return $table
            ->recordTitleAttribute('id')
            ->columns($columns)
            ->defaultSort('total_score', 'desc')
            ->emptyStateHeading('No Participants')
            ->emptyStateDescription($currentProgramId 
                ? 'No participants have registered for this program yet.'
                : 'Please select a program to view participants, or no participants have registered yet.')
            ->emptyStateIcon('heroicon-o-users')
            ->headerActions([
                // No header actions needed
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View')
                    ->authorize(function ($record) {
                        return ProgramParticipantResource::canView($record);
                    }),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Participant')
                    ->modalDescription('Are you sure you want to approve this participant? This action will be submitted for approval.')
                    ->visible(fn ($record) => !$record->isArchived() && ($record->isPending() || $record->isRejected()))
                    ->action(function ($record) {
                        $approvalService = new ApplicationApprovalService();
                        $result = $approvalService->processAction(
                            'update',
                            ['status' => 'approved', 'program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                            $record->id,
                            'Approve participant request'
                        );

                        if ($result['success']) {
                            if ($result['requires_approval']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Request Submitted for Approval')
                                    ->body('Your approval request has been submitted for approval. You will be notified once approved.')
                                    ->success()
                                    ->send();
                            } else {
                                $record->approve();
                                \Filament\Notifications\Notification::make()
                                    ->title('Participant Approved')
                                    ->body('The participant has been approved successfully.')
                                    ->success()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->authorize(fn ($record) =>
                        auth()->user()?->can('update ProgramApplication') &&
                        ProgramParticipantResource::canView($record)
                    ),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Participant')
                    ->modalDescription('Are you sure you want to reject this participant? This action will be submitted for approval.')
                    ->visible(fn ($record) => !$record->isArchived() && ($record->isPending() || $record->isApproved()))
                    ->action(function ($record) {
                        $approvalService = new ApplicationApprovalService();
                        $result = $approvalService->processAction(
                            'update',
                            ['status' => 'rejected', 'program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                            $record->id,
                            'Reject participant request'
                        );

                        if ($result['success']) {
                            if ($result['requires_approval']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Request Submitted for Approval')
                                    ->body('Your rejection request has been submitted for approval. You will be notified once approved.')
                                    ->success()
                                    ->send();
                            } else {
                                $record->reject();
                                \Filament\Notifications\Notification::make()
                                    ->title('Participant Rejected')
                                    ->body('The participant has been rejected successfully.')
                                    ->success()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->authorize(fn ($record) =>
                        auth()->user()?->can('update ProgramApplication') &&
                        ProgramParticipantResource::canView($record)
                    ),

                Tables\Actions\Action::make('disqualify')
                    ->label('Disqualify')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->requiresConfirmation()
                    ->modalHeading('Disqualify Participant')
                    ->modalDescription('Are you sure you want to disqualify this participant? This will mark them as disqualified and they will not be able to continue in the program. This action will be submitted for approval.')
                    ->visible(fn ($record) => !$record->isArchived() && $record->isApproved())
                    ->action(function ($record) {
                        $approvalService = new ApplicationApprovalService();
                        $result = $approvalService->processAction(
                            'update',
                            ['status' => 'rejected', 'program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                            $record->id,
                            'Disqualify participant request'
                        );

                        if ($result['success']) {
                            if ($result['requires_approval']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Request Submitted for Approval')
                                    ->body('Your disqualification request has been submitted for approval. You will be notified once approved.')
                                    ->success()
                                    ->send();
                            } else {
                                $record->reject();
                                \Filament\Notifications\Notification::make()
                                    ->title('Participant Disqualified')
                                    ->body('The participant has been disqualified successfully.')
                                    ->success()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->authorize(fn ($record) =>
                        auth()->user()?->can('update ProgramApplication') &&
                        ProgramParticipantResource::canView($record)
                    ),

                Tables\Actions\Action::make('archive')
                    ->label('Deactivate')
                    ->color('warning')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate Participant')
                    ->modalDescription('Are you sure you want to deactivate this participant? This action will be submitted for approval.')
                    ->modalSubmitActionLabel('Deactivate')
                    ->visible(fn ($record) => !$record->isArchived() && ProgramParticipantResource::canArchive($record))
                    ->action(function ($record) {
                        $approvalService = new ApplicationApprovalService();
                        $result = $approvalService->processAction(
                            'archive',
                            ['is_archived' => true, 'archived_at' => now(), 'program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                            $record->id,
                            'Archive participant request'
                        );

                        if ($result['success']) {
                            if ($result['requires_approval']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Request Submitted for Approval')
                                    ->body('Your deactivate request has been submitted for approval. You will be notified once approved.')
                                    ->success()
                                    ->send();
                            } else {
                                $record->archive();
                                \Filament\Notifications\Notification::make()
                                    ->title('Participant Deactivated')
                                    ->body('The participant has been deactivated successfully.')
                                    ->success()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->authorize(fn ($record) => ProgramParticipantResource::canArchive($record)),

                Tables\Actions\Action::make('restore')
                    ->label('Activate')
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading('Activate Participant')
                    ->modalDescription('Are you sure you want to activate this participant?')
                    ->modalSubmitActionLabel('Activate')
                    ->visible(fn ($record) => $record->isArchived() && ProgramParticipantResource::canRestore($record))
                    ->action(function ($record) {
                        try {
                            $record->restore();
                            \Filament\Notifications\Notification::make()
                                ->title('Participant Activated')
                                ->body('The participant has been activated successfully.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->authorize(fn ($record) => ProgramParticipantResource::canRestore($record)),

                Tables\Actions\Action::make('delete')
                    ->label('Delete')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Participant')
                    ->modalDescription('Are you sure you want to delete this participant? This action cannot be undone. This action will be submitted for approval.')
                    ->modalSubmitActionLabel('Delete')
                    ->action(function ($record) {
                        $approvalService = new ApplicationApprovalService();
                        $result = $approvalService->processAction(
                            'delete',
                            ['program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                            $record->id,
                            'Delete participant request'
                        );

                        if ($result['success']) {
                            if ($result['requires_approval']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Request Submitted for Approval')
                                    ->body('Your delete request has been submitted for approval. You will be notified once approved.')
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Participant Deleted')
                                    ->body('The participant has been deleted successfully.')
                                    ->success()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => auth()->user()?->can('delete ProgramApplication'))
                    ->authorize(fn ($record) => auth()->user()?->can('delete ProgramApplication')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk-approve')
                        ->label('Approve Selected')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Selected Participants')
                        ->modalDescription('Are you sure you want to approve the selected participants? This action will be submitted for approval.')
                        ->action(function ($records) {
                            $approvalService = new ApplicationApprovalService();
                            $count = 0;
                            $pendingApproval = 0;
                            $skippedArchived = 0;
                            $skippedInvalidStatus = 0;

                            foreach ($records as $record) {
                                if ($record->isArchived()) {
                                    $skippedArchived++;
                                    continue;
                                }
                                
                                if ($record->isPending() || $record->isRejected()) {
                                    $result = $approvalService->processAction(
                                        'update',
                                        ['status' => 'approved', 'program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                                        $record->id,
                                        'Bulk approve participant request'
                                    );

                                    if ($result['success']) {
                                        if ($result['requires_approval']) {
                                            $pendingApproval++;
                                        } else {
                                            $record->approve();
                                            $count++;
                                        }
                                    }
                                } else {
                                    $skippedInvalidStatus++;
                                }
                            }

                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Participants Approved')
                                    ->body("{$count} participant(s) have been approved successfully.")
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApproval > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval')
                                    ->body("{$pendingApproval} approval request(s) have been submitted for approval.")
                                    ->success()
                                    ->send();
                            }

                            if ($skippedArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Archived Participants Skipped')
                                    ->body("{$skippedArchived} archived participant(s) were skipped. Archived participants cannot be approved.")
                                    ->warning()
                                    ->send();
                            }

                            if ($skippedInvalidStatus > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Participants Skipped')
                                    ->body("{$skippedInvalidStatus} participant(s) were skipped because they are not in a pending or rejected status.")
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $pendingApproval === 0 && $skippedArchived === 0 && $skippedInvalidStatus === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No Action Taken')
                                    ->body('No participants were approved.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('update ProgramApplication')),

                    Tables\Actions\BulkAction::make('bulk-reject')
                        ->label('Reject Selected')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Selected Participants')
                        ->modalDescription('Are you sure you want to reject the selected participants? This action will be submitted for approval.')
                        ->action(function ($records) {
                            $approvalService = new ApplicationApprovalService();
                            $count = 0;
                            $pendingApproval = 0;
                            $skippedArchived = 0;
                            $skippedInvalidStatus = 0;

                            foreach ($records as $record) {
                                if ($record->isArchived()) {
                                    $skippedArchived++;
                                    continue;
                                }
                                
                                if ($record->isPending() || $record->isApproved()) {
                                    $result = $approvalService->processAction(
                                        'update',
                                        ['status' => 'rejected', 'program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                                        $record->id,
                                        'Bulk reject participant request'
                                    );

                                    if ($result['success']) {
                                        if ($result['requires_approval']) {
                                            $pendingApproval++;
                                        } else {
                                            $record->reject();
                                            $count++;
                                        }
                                    }
                                } else {
                                    $skippedInvalidStatus++;
                                }
                            }

                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Participants Rejected')
                                    ->body("{$count} participant(s) have been rejected successfully.")
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApproval > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval')
                                    ->body("{$pendingApproval} rejection request(s) have been submitted for approval.")
                                    ->success()
                                    ->send();
                            }

                            if ($skippedArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Archived Participants Skipped')
                                    ->body("{$skippedArchived} archived participant(s) were skipped. Archived participants cannot be rejected.")
                                    ->warning()
                                    ->send();
                            }

                            if ($skippedInvalidStatus > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Participants Skipped')
                                    ->body("{$skippedInvalidStatus} participant(s) were skipped because they are not in a pending or approved status.")
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $pendingApproval === 0 && $skippedArchived === 0 && $skippedInvalidStatus === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No Action Taken')
                                    ->body('No participants were rejected.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('update ProgramApplication')),

                    Tables\Actions\BulkAction::make('bulk-disqualify')
                        ->label('Disqualify Selected')
                        ->color('danger')
                        ->icon('heroicon-o-x-mark')
                        ->requiresConfirmation()
                        ->modalHeading('Disqualify Selected Participants')
                        ->modalDescription('Are you sure you want to disqualify the selected participants? This will mark them as disqualified and they will not be able to continue in the program. This action will be submitted for approval.')
                        ->action(function ($records) {
                            $approvalService = new ApplicationApprovalService();
                            $count = 0;
                            $pendingApproval = 0;
                            $skippedArchived = 0;
                            $skippedInvalidStatus = 0;

                            foreach ($records as $record) {
                                if ($record->isArchived()) {
                                    $skippedArchived++;
                                    continue;
                                }
                                
                                if ($record->isApproved()) {
                                    $result = $approvalService->processAction(
                                        'update',
                                        ['status' => 'rejected', 'program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                                        $record->id,
                                        'Bulk disqualify participant request'
                                    );

                                    if ($result['success']) {
                                        if ($result['requires_approval']) {
                                            $pendingApproval++;
                                        } else {
                                            $record->reject();
                                            $count++;
                                        }
                                    }
                                } else {
                                    $skippedInvalidStatus++;
                                }
                            }

                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Participants Disqualified')
                                    ->body("{$count} participant(s) have been disqualified successfully.")
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApproval > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval')
                                    ->body("{$pendingApproval} disqualification request(s) have been submitted for approval.")
                                    ->success()
                                    ->send();
                            }

                            if ($skippedArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Archived Participants Skipped')
                                    ->body("{$skippedArchived} archived participant(s) were skipped. Archived participants cannot be disqualified.")
                                    ->warning()
                                    ->send();
                            }

                            if ($skippedInvalidStatus > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Participants Skipped')
                                    ->body("{$skippedInvalidStatus} participant(s) were skipped because they are not in an approved status.")
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $pendingApproval === 0 && $skippedArchived === 0 && $skippedInvalidStatus === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No Action Taken')
                                    ->body('No participants were disqualified.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('update ProgramApplication')),

                    Tables\Actions\BulkAction::make('bulk-archive')
                        ->label('Deactivate Selected')
                        ->color('warning')
                        ->icon('heroicon-o-archive-box')
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate Selected Participants')
                        ->modalDescription('Are you sure you want to deactivate the selected participants? This action will be submitted for approval.')
                        ->action(function ($records) {
                            $approvalService = new ApplicationApprovalService();
                            $count = 0;
                            $alreadyArchived = 0;
                            $pendingApproval = 0;

                            foreach ($records as $record) {
                                if (!$record->isArchived()) {
                                    $result = $approvalService->processAction(
                                        'archive',
                                        ['is_archived' => true, 'archived_at' => now(), 'program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                                        $record->id,
                                        'Bulk archive participant request'
                                    );

                                    if ($result['success']) {
                                        if ($result['requires_approval']) {
                                            $pendingApproval++;
                                        } else {
                                            $record->archive();
                                            $count++;
                                        }
                                    }
                                } else {
                                    $alreadyArchived++;
                                }
                            }

                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Participants Deactivated')
                                    ->body("{$count} participant(s) have been deactivated successfully.")
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApproval > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval')
                                    ->body("{$pendingApproval} deactivate request(s) have been submitted for approval.")
                                    ->success()
                                    ->send();
                            }

                            if ($alreadyArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Participants Already Archived')
                                    ->body("{$alreadyArchived} participant(s) were already archived.")
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $alreadyArchived > 0 && $pendingApproval === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No Action Taken')
                                    ->body('All selected participants were already archived.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('archive ProgramApplication'))
                        ->authorize(fn () => auth()->user()?->can('archive ProgramApplication')),

                    Tables\Actions\BulkAction::make('bulk-restore')
                        ->label('Activate Selected')
                        ->color('success')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->requiresConfirmation()
                        ->modalHeading('Activate Selected Participants')
                        ->modalDescription('Are you sure you want to activate the selected participants?')
                        ->action(function ($records) {
                            $count = 0;
                            $alreadyActive = 0;
                            $errors = [];

                            foreach ($records as $record) {
                                if ($record->isArchived()) {
                                    try {
                                        $record->restore();
                                        $count++;
                                    } catch (\Exception $e) {
                                        $errors[] = "Participant ID {$record->id}: " . $e->getMessage();
                                    }
                                } else {
                                    $alreadyActive++;
                                }
                            }

                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Participants Activated')
                                    ->body("{$count} participant(s) have been activated successfully.")
                                    ->success()
                                    ->send();
                            }

                            if ($alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Participants Already Active')
                                    ->body("{$alreadyActive} participant(s) were already active.")
                                    ->warning()
                                    ->send();
                            }

                            if (!empty($errors)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Errors Occurred')
                                    ->body(implode('<br>', $errors))
                                    ->danger()
                                    ->send();
                            }

                            if ($count === 0 && $alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No Action Taken')
                                    ->body('All selected participants were already active.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('restore ProgramApplication'))
                        ->authorize(fn () => auth()->user()?->can('restore ProgramApplication')),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete ProgramApplication')),
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Registration Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('has_team')
                    ->label('Type')
                    ->options([
                        1 => 'Team',
                        0 => 'Individual',
                    ]),
            ])
            ->searchable();
    }

    protected function buildBaseColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('participant.name')
                ->label('Name')
                ->getStateUsing(function ($record) {
                    if ($record->has_team && $record->team_name) {
                        return $record->team_name;
                    }
                    if ($record->participant) {
                        return $record->participant->name ?? '—';
                    }
                    $firstName = $record->form_submissions['first_name'] ?? '';
                    $lastName = $record->form_submissions['last_name'] ?? '';
                    return trim($firstName . ' ' . $lastName) ?: '—';
                })
                ->searchable(query: function ($query, $search) {
                    return $query->whereHas('participant', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    })->orWhere('team_name', 'like', "%{$search}%")
                      ->orWhere('form_submissions->first_name', 'like', "%{$search}%")
                      ->orWhere('form_submissions->last_name', 'like', "%{$search}%")
                      ->orWhere('form_submissions->participant_email', 'like', "%{$search}%")
                      ->orWhere('form_submissions->email', 'like', "%{$search}%");
                })
                ->sortable(),

            Tables\Columns\TextColumn::make('type')
                ->label('Type')
                ->getStateUsing(function ($record) {
                    if ($record->has_team) {
                        return 'Team';
                    }
                    return 'Individual';
                })
                ->badge()
                ->color(fn ($record) => $record->has_team ? 'success' : 'info')
                ->sortable(),

            Tables\Columns\TextColumn::make('status')
                ->label('Registration Status')
                ->getStateUsing(fn($record) => str($record->status)->ucfirst())
                ->badge()
                ->color(fn($record) => match ($record->status) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'gray',
                })
                ->sortable(),

            Tables\Columns\TextColumn::make('registration_total_score')
                ->label('Registration Score')
                ->getStateUsing(function ($record) {
                    $combinedScore = $record->registration_total_score ?? 0;
                    if ($combinedScore > 0) {
                        return number_format($combinedScore, 2);
                    }
                    return '—';
                })
                ->sortable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('total_combined_score')
                ->label('Total Score')
                ->getStateUsing(function ($record) {
                    // Start with combined registration score (manual + AI evaluation)
                    $total = $record->registration_total_score ?? 0;

                    // Get program stages
                    $program = $record->program;
                    if (!$program) {
                        return $total > 0 ? number_format($total, 2) : '—';
                    }

                    $stages = $program->stages()
                        ->where('slug', '!=', 'registration')
                        ->orderBy('starts_at', 'asc')
                        ->get();

                    // Add AI project scores from project stages
                    foreach ($stages as $stage) {
                        if (str_starts_with($stage->slug, 'project-')) {
                            $formIds = $stage->getFormIds();
                            if (!empty($formIds)) {
                                // Get all projects for this stage (not just the first one)
                                $projects = Project::where('application_id', $record->id)
                                    ->whereIn('form_id', $formIds)
                                    ->where('is_archived', false)
                                    ->where('type', 'submission')
                                    ->get();

                                // Sum all AI project scores for this stage (matching leaderboard logic)
                                foreach ($projects as $project) {
                                    // Calculate AI score only (for Project Score)
                                    $aiScore = 0;
                                    $aiResponse = $project->ai_evaluation_response;
                                    $aiStatus = data_get($aiResponse, 'status');
                                    $hasCompletedAi = $aiStatus === 'completed' && is_array($aiResponse);

                                    if ($hasCompletedAi) {
                                        // AI evaluation is completed - use AI score from meta data (preferred)
                                        $normalizedScore = data_get($aiResponse, 'meta.normalized_score');
                                        $aiTotalScore = data_get($aiResponse, 'meta.total_score');

                                        if ($normalizedScore !== null) {
                                            $aiScore = (float) $normalizedScore;
                                        } elseif ($aiTotalScore !== null) {
                                            $aiScore = (float) $aiTotalScore;
                                        } else {
                                            // Last fallback: calculate from criteria if meta is not available
                                            $criteria = data_get($aiResponse, 'data.criteria', []);
                                            foreach ($criteria as $criterion) {
                                                $aiScore += (float) (data_get($criterion, 'totalScore', 0));
                                            }
                                        }
                                    }
                                    
                                    $total += $aiScore;
                                }
                            }
                        }
                    }

                    return $total > 0 ? number_format($total, 2) : '—';
                })
                ->sortable()
                ->toggleable(),
        ];
    }
}
