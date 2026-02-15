<?php

namespace App\Filament\Resources\MentorResource\Pages;

use App\Filament\Resources\MentorResource;
use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Models\Mentor;
use App\Models\Participant;
use App\Models\Team;
use App\Models\TeamMember;
use App\Notifications\MentorTeamAssigned;
use App\Notifications\MentorParticipantAssigned;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Illuminate\Support\Facades\DB;

class ViewMentor extends ViewRecord
{
    protected static string $resource = MentorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assignTeamsAndParticipants')
                ->label('Assign Applications / تعيين الطلبات')
                ->icon('heroicon-o-user-group')
                ->color('success')
                ->modalHeading('Assign Applications / تعيين الطلبات')
                ->modalDescription('Select team applications and/or individual applications to assign to this mentor. / اختر طلبات الفرق و/أو الطلبات الفردية لتعيينها لهذا المرشد.')
                ->modalWidth('xl')
                ->form([
                    Forms\Components\Section::make('Team Applications / طلبات الفرق')
                        ->description('Select team applications (has_team = true) to assign / اختر طلبات الفرق للتعيين')
                        ->schema([
                            Forms\Components\Select::make('team_applications')
                                ->label('Team Applications / طلبات الفرق')
                                ->options(function () {
                                    $competitionIds = $this->record->competitions()->pluck('competitions.id')->toArray();
                                    $currentMentorId = $this->record->id;
                                    
                                    // If mentor has no assigned competitions, return empty
                                    if (empty($competitionIds)) {
                                        return [];
                                    }
                                    
                                    // Get team applications ONLY from mentor's assigned competitions
                                    // Exclude teams that already have a mentor assigned (except this mentor's teams)
                                    return CompetitionApplication::query()
                                        ->where(function ($q) {
                                            $q->where('has_team', true)
                                              ->orWhere('registered_as', 'team');
                                        })
                                        ->whereIn('status', ['approved', 'pending', 'submitted'])
                                        ->whereIn('competition_id', $competitionIds)
                                        ->whereHas('team', function ($query) use ($currentMentorId) {
                                            $query->where('is_archived', false)
                                                 // ->where('is_published', true)
                                                  // Exclude teams with other mentors, but include teams assigned to this mentor
                                                  ->where(function ($q) use ($currentMentorId) {
                                                      $q->whereDoesntHave('mentors')
                                                        ->orWhereHas('mentors', function ($mq) use ($currentMentorId) {
                                                            $mq->where('mentor_id', $currentMentorId);
                                                        });
                                                  });
                                        })
                                        ->with(['team.mentors', 'competition', 'participant'])
                                        ->orderBy('id', 'desc')
                                        ->get()
                                        ->mapWithKeys(function ($application) {
                                            $teamName = $application->team?->name ?? 'Unknown Team';
                                            $competitionName = $application->competition?->title;
                                            if (is_array($competitionName)) {
                                                $competitionName = $competitionName['en'] ?? $competitionName['ar'] ?? '';
                                            }
                                            $status = ucfirst($application->status ?? '');
                                            $label = "#{$application->id} - {$teamName}";
                                            if ($competitionName) {
                                                $label .= " ({$competitionName})";
                                            }
                                            $label .= " [{$status}]";
                                            return [$application->id => $label];
                                        });
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->placeholder('Select team applications... / اختر طلبات الفرق...')
                                ->default(function () {
                                    // Get application IDs for currently assigned teams (only from assigned competitions)
                                    $competitionIds = $this->record->competitions()->pluck('competitions.id')->toArray();
                                    $teamIds = $this->record->teams()->pluck('teams.id')->toArray();
                                    
                                    if (empty($competitionIds) || empty($teamIds)) {
                                        return [];
                                    }
                                    
                                    return CompetitionApplication::whereHas('team', function ($q) use ($teamIds) {
                                        $q->whereIn('id', $teamIds);
                                    })
                                    ->whereIn('competition_id', $competitionIds)
                                    ->pluck('id')
                                    ->toArray();
                                })
                                ->helperText('Teams with mentors already assigned are hidden. / الفرق التي لديها مرشد معين مسبقاً مخفية.'),
                        ])
                        ->collapsible(),
                    
                    Forms\Components\Section::make('Individual Applications / الطلبات الفردية')
                        ->description('Select individual applications (has_team = false) to assign / اختر الطلبات الفردية للتعيين')
                        ->schema([
                            Forms\Components\Select::make('individual_applications')
                                ->label('Individual Applications / الطلبات الفردية')
                                ->options(function () {
                                    $competitionIds = $this->record->competitions()->pluck('competitions.id')->toArray();
                                    $currentMentorId = $this->record->id;
                                    
                                    // If mentor has no assigned competitions, return empty
                                    if (empty($competitionIds)) {
                                        return [];
                                    }
                                    
                                    // Get individual applications ONLY from mentor's assigned competitions
                                    // Exclude participants that already have a mentor assigned (except this mentor's participants)
                                    return CompetitionApplication::query()
                                        ->where(function ($q) {
                                            $q->where('has_team', false)
                                              ->orWhere('registered_as', 'individual');
                                        })
                                        ->whereIn('status', ['approved', 'pending', 'submitted'])
                                        ->whereIn('competition_id', $competitionIds)
                                        ->whereHas('participant', function ($q) use ($currentMentorId) {
                                            $q->where('is_archived', false)
                                             // Exclude participants with other mentors, but include participants assigned to this mentor
                                              ->where(function ($pq) use ($currentMentorId) {
                                                  $pq->whereDoesntHave('mentors')
                                                     ->orWhereHas('mentors', function ($mq) use ($currentMentorId) {
                                                         $mq->where('mentor_id', $currentMentorId);
                                                     });
                                              });
                                        })
                                        ->with(['competition', 'participant.mentors'])
                                        ->orderBy('id', 'desc')
                                        ->get()
                                        ->mapWithKeys(function ($application) {
                                            $participantName = $application->participant?->name;
                                            if (is_array($participantName)) {
                                                $participantName = $participantName['en'] ?? $participantName['ar'] ?? 'Unknown';
                                            }
                                            $participantEmail = $application->participant?->email ?? '';
                                            $competitionName = $application->competition?->title;
                                            if (is_array($competitionName)) {
                                                $competitionName = $competitionName['en'] ?? $competitionName['ar'] ?? '';
                                            }
                                            $status = ucfirst($application->status ?? '');
                                            $label = "#{$application->id} - {$participantName} ({$participantEmail})";
                                            if ($competitionName) {
                                                $label .= " - {$competitionName}";
                                            }
                                            $label .= " [{$status}]";
                                            return [$application->id => $label];
                                        });
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->placeholder('Select individual applications... / اختر الطلبات الفردية...')
                                ->default(function () {
                                    // Get assignments exactly as they are in the database (scoped by competition)
                                    $assignments = DB::table('mentor_participant')
                                        ->where('mentor_id', $this->record->id)
                                        ->get();
                                    
                                    $identifiers = [];
                                    $globalParticipantIds = [];
                                    
                                    foreach ($assignments as $assignment) {
                                        if ($assignment->competition_id) {
                                            $identifiers[] = $assignment->participant_id . '-' . $assignment->competition_id;
                                        } else {
                                            $globalParticipantIds[] = $assignment->participant_id;
                                        }
                                    }
                                    
                                    if (empty($identifiers) && empty($globalParticipantIds)) {
                                        return [];
                                    }
                                    
                                    // Fetch all potential applications
                                    $competitionIds = $this->record->competitions()->pluck('competitions.id')->toArray();
                                    
                                    $query = CompetitionApplication::where(function ($q) {
                                            $q->where('has_team', false)
                                              ->orWhere('registered_as', 'individual');
                                        })
                                        ->whereIn('competition_id', $competitionIds);
                                        
                                    // Optimization: filter by participants we care about
                                    $allParticipantIds = array_unique(array_merge(
                                        $globalParticipantIds, 
                                        array_map(fn($id) => explode('-', $id)[0], $identifiers)
                                    ));
                                    
                                    $applications = $query->whereIn('participant_id', $allParticipantIds)
                                        ->select('id', 'participant_id', 'competition_id')
                                        ->get();
                                        
                                    $selectedApplicationIds = [];
                                    
                                    foreach ($applications as $app) {
                                        // If participant is globally assigned, include this app
                                        if (in_array($app->participant_id, $globalParticipantIds)) {
                                            $selectedApplicationIds[] = $app->id;
                                            continue;
                                        }
                                        
                                        // If participant is assigned for this specific competition, include this app
                                        $ident = $app->participant_id . '-' . $app->competition_id;
                                        if (in_array($ident, $identifiers)) {
                                            $selectedApplicationIds[] = $app->id;
                                        }
                                    }
                                    
                                    return $selectedApplicationIds;
                                })
                                ->helperText('Participants with mentors already assigned are hidden. / المشاركين الذين لديهم مرشد معين مسبقاً مخفيين.'),
                        ])
                        ->collapsible(),
                    
                    // Forms\Components\Textarea::make('notes')
                    //     ->label('Notes / ملاحظات')
                    //     ->placeholder('Optional notes about the assignment... / ملاحظات اختيارية حول التعيين...')
                    //     ->maxLength(500),
                ])
                ->action(function (array $data) {
                    $this->assignApplicationsToMentor($data);
                })
                ->visible(fn () => !$this->record->isArchived() && 
                                   $this->record->status === 'approved' && 
                                   auth()->user()?->can('update Mentor'))
                ->disabled(fn () => $this->record->status !== 'approved')
                ->tooltip(fn () => $this->record->status !== 'approved' 
                    ? 'Mentor must be approved to make assignments. / يجب أن يكون المرشد معتمداً للتعيين.' 
                    : null),

            Actions\Action::make('assignPrograms')
                ->label('Assign to Program(s)')
                ->icon('heroicon-o-link')
                ->color('primary')
                ->modalHeading('Assign Mentor to Programs')
                ->modalDescription('Select one or more programs to assign this mentor to.')
                ->form([
                    Forms\Components\Select::make('competitions')
                        ->label('Programs')
                        ->options(function () {
                            return Competition::query()
                                ->whereNotNull('title')
                                ->where('is_archived', false)
                                ->get()
                                ->pluck('title', 'id')
                                ->mapWithKeys(function ($title, $id) {
                                    if (is_array($title)) {
                                        return [$id => $title['en'] ?? $title['ar'] ?? 'Unknown'];
                                    }
                                    return [$id => $title];
                                });
                        })
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->placeholder('Select programs...')
                        ->default(fn () => $this->record->competitions()->pluck('competitions.id')->toArray())
                        ->required()
                        ->helperText('Search and select one or more programs for this mentor.')
                ])
                ->action(function (array $data) {
                    // Get currently assigned competition IDs before sync
                    $originalCompetitionIds = $this->record->competitions()->pluck('competitions.id')->toArray();
                    
                    // Sync new competitions
                    $this->record->competitions()->sync($data['competitions']);
                    
                    // Determine removed competitions
                    $newCompetitionIds = $data['competitions'];
                    $removedCompetitionIds = array_diff($originalCompetitionIds, $newCompetitionIds);
                    
                    // If competitions were removed, cleanup assigned participants and teams
                    if (!empty($removedCompetitionIds)) {
                        $mentorId = $this->record->id;
                        
                        // 1. Cleanup Teams
                        // Find teams assigned to this mentor that belong to the removed competitions
                        $teamsToRemove = Team::query()
                            ->whereHas('mentors', function ($q) use ($mentorId) {
                                $q->where('mentor_id', $mentorId);
                            })
                            ->whereHas('application', function ($q) use ($removedCompetitionIds) {
                                $q->whereIn('competition_id', $removedCompetitionIds);
                            })
                            ->pluck('id')
                            ->toArray();
                            
                        if (!empty($teamsToRemove)) {
                            $this->record->teams()->detach($teamsToRemove);
                        }
                        
                        // 2. Cleanup Individual Participants
                        // Find participants assigned to this mentor via applications in removed competitions
                        // We need to be careful: a participant might be in multiple competitions.
                        // We should only remove if their *application for that competition* was the reason for assignment.
                        // However, the relationship is Mentor <-> Participant. 
                        // If we assume a mentor is assigned to a participant in the context of a competition...
                        
                        // Let's look at applications for removed competitions where the participant is assigned to this mentor
                        // and detach the participant.
                        
                        // Better approach: Find participants assigned to this mentor who ONLY have active applications 
                        // in the REMOVED competitions (relative to what the mentor is assigned to).
                        // OR simpler: Just find participants whose active applications in Removed Competitions match, and detach them?
                        // But wait, what if they are also in a Kept Competition?
                        
                        // Let's refine:
                        // Find participants assigned to this mentor.
                        // For each participant, check if they still have ANY valid application in the *newly assigned* competitions of this mentor.
                        // If NOT, then detach them.
                        // This seems safest.
                        
                        $assignedParticipantIds = $this->record->participants()->pluck('participants.id')->toArray();
                        
                        if (!empty($assignedParticipantIds)) {
                            // Find which of these currently assigned participants have valid applications in the NEW competition list
                            $participantsToKeep = CompetitionApplication::query()
                                ->whereIn('participant_id', $assignedParticipantIds)
                                ->whereIn('competition_id', $newCompetitionIds)
                                ->where(function ($q) {
                                    $q->where('has_team', false)
                                      ->orWhere('registered_as', 'individual');
                                })
                                ->active()
                                ->pluck('participant_id')
                                ->toArray();
                                
                            $participantsToRemove = array_diff($assignedParticipantIds, $participantsToKeep);
                            
                            if (!empty($participantsToRemove)) {
                                $this->record->participants()->detach($participantsToRemove);
                            }
                        }
                    }
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Programs Assigned')
                        ->body('The mentor has been successfully assigned to the selected programs.' . (!empty($removedCompetitionIds) ? ' Related assignments from removed programs have been cleaned up.' : ''))
                        ->success()
                        ->send();
                })
                ->visible(fn () => !$this->record->isArchived() && auth()->user()?->can('update Mentor')),
            
            Actions\EditAction::make()
                ->visible(fn () => !$this->record->isArchived()),
            Actions\Action::make('restore')
                ->label('Restore / استعادة')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Restore Mentor / استعادة المرشد')
                ->modalDescription('Are you sure you want to restore this mentor? / هل أنت متأكد من استعادة هذا المرشد؟')
                ->authorize(fn () => MentorResource::canRestore($this->record))
                ->visible(fn () => $this->record->isArchived())
                ->action(function () {
                    $this->record->restore();
                    \Filament\Notifications\Notification::make()
                        ->title('Mentor Restored / تم استعادة المرشد')
                        ->body('The mentor has been restored successfully. / تم استعادة المرشد بنجاح.')
                        ->success()
                        ->send();
                    
                    $this->redirect(MentorResource::getUrl('index'));
                }),
        ];
    }

    /**
     * Assign applications (teams and individual participants) to mentor
     */
    protected function assignApplicationsToMentor(array $data): void
    {
        try {
            DB::beginTransaction();
            
            $selectedTeamApplicationIds = $data['team_applications'] ?? [];
            $selectedIndividualApplicationIds = $data['individual_applications'] ?? [];
            $notes = $data['notes'] ?? null;
            $currentUserId = auth()->id();
            
            // Check if mentor is inactive/archived
            if ($this->record->isArchived()) {
                throw new \Exception('Mentor is inactive. Cannot make assignments to an archived mentor. / المرشد غير نشط. لا يمكن التعيين لمرشد مؤرشف.');
            }
            
            // Check if mentor is approved
            if ($this->record->status !== 'approved') {
                throw new \Exception('Mentor must be approved to make assignments. / يجب أن يكون المرشد معتمداً للتعيين.');
            }
            
            // Process Team Applications - get team IDs from applications
            $selectedTeamIds = [];
            if (!empty($selectedTeamApplicationIds)) {
                $selectedTeamIds = CompetitionApplication::whereIn('id', $selectedTeamApplicationIds)
                    ->whereHas('team')
                    ->with('team')
                    ->get()
                    ->pluck('team.id')
                    ->filter()
                    ->toArray();
            }
            
            // Process Individual Applications - get participant IDs AND competition IDs
            $selectedParticipantMap = []; // participant_id => [competition_id => true]
            if (!empty($selectedIndividualApplicationIds)) {
                $applications = CompetitionApplication::whereIn('id', $selectedIndividualApplicationIds)
                    ->select('id', 'participant_id', 'competition_id')
                    ->get();
                
                foreach ($applications as $app) {
                    if (!isset($selectedParticipantMap[$app->participant_id])) {
                        $selectedParticipantMap[$app->participant_id] = [];
                    }
                    $selectedParticipantMap[$app->participant_id][] = $app->competition_id;
                }
            }
            
            // Process Teams
            $this->processTeamAssignments($selectedTeamIds, $notes, $currentUserId);
            
            // Process Individual Participants
            $this->processParticipantAssignments($selectedParticipantMap, $notes, $currentUserId);
            
            DB::commit();
            
            \Filament\Notifications\Notification::make()
                ->title('Assignments Updated Successfully / تم تحديث التعيينات بنجاح')
                ->body('The mentor assignments have been updated. Notifications have been sent. / تم تحديث تعيينات المرشد. تم إرسال الإشعارات.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Filament\Notifications\Notification::make()
                ->title('Assignment Failed / فشل التعيين')
                ->body('Failed to update assignments: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Process team assignments
     */
    protected function processTeamAssignments(array $selectedTeamIds, ?string $notes, int $currentUserId): void
    {
        // Validate all selected teams are active and published
        if (!empty($selectedTeamIds)) {
            $inactiveTeams = Team::whereIn('id', $selectedTeamIds)
                ->where(function ($query) {
                    $query->where('is_archived', true);
                         // ->orWhere('is_published', false);
                })
                ->pluck('name')
                ->toArray();
            
            if (!empty($inactiveTeams)) {
                $teamNames = implode(', ', $inactiveTeams);
                throw new \Exception("The following teams are inactive or not published: {$teamNames} / الفرق التالية غير نشطة أو غير منشورة: {$teamNames}");
            }
        }
        
        // Get currently assigned teams
        $currentTeamIds = $this->record->teams()->pluck('teams.id')->toArray();
        
        // Teams to add
        $teamsToAdd = array_diff($selectedTeamIds, $currentTeamIds);
        // Teams to remove
        $teamsToRemove = array_diff($currentTeamIds, $selectedTeamIds);
        
        // Check if teams to add already have another mentor assigned
        if (!empty($teamsToAdd)) {
            $teamsWithOtherMentors = Team::whereIn('id', $teamsToAdd)
                ->whereHas('mentors', function ($query) {
                    $query->where('mentor_id', '!=', $this->record->id);
                })
                ->with(['mentors' => function ($query) {
                    $query->where('mentor_id', '!=', $this->record->id);
                }])
                ->get();
            
            if ($teamsWithOtherMentors->isNotEmpty()) {
                $teamDetails = $teamsWithOtherMentors->map(function ($team) {
                    $mentorNames = $team->mentors->map(function ($mentor) {
                        $name = is_array($mentor->name) 
                            ? ($mentor->name['en'] ?? $mentor->name['ar'] ?? 'Unknown')
                            : $mentor->name;
                        return $name;
                    })->implode(', ');
                    return "{$team->name} (Mentor: {$mentorNames})";
                })->implode('; ');
                
                throw new \Exception("The following teams already have mentors assigned: {$teamDetails} / الفرق التالية لديها مرشدين معينين بالفعل: {$teamDetails}");
            }
        }
        
        // Remove teams no longer selected
        if (!empty($teamsToRemove)) {
            foreach ($teamsToRemove as $removedTeamId) {
                $team = Team::find($removedTeamId);
                if ($team) {
                    $this->sendTeamRemovalNotifications($team);
                }
            }
            $this->record->teams()->detach($teamsToRemove);
        }
        
        // Add new teams
        foreach ($teamsToAdd as $teamId) {
            $team = Team::find($teamId);
            if (!$team || $team->isArchived() /* || !$team->is_published */) {
                continue;
            }
            
            $this->record->teams()->attach($teamId, [
                'assigned_by' => $currentUserId,
                'assigned_at' => now(),
                'notes' => $notes,
            ]);
            
            $this->sendTeamAssignmentNotifications($team);
        }
    }

    /**
     * Process individual participant assignments
     */
    /**
     * Process individual participant assignments
     * @param array $selectedParticipantMap Format: [participant_id => [competition_id_1, competition_id_2]]
     */
    protected function processParticipantAssignments(array $selectedParticipantMap, ?string $notes, int $currentUserId): void
    {
        $mentorId = $this->record->id;
        
        // 1. Validate active participants
        if (!empty($selectedParticipantMap)) {
            $participantIds = array_keys($selectedParticipantMap);
            $inactiveCount = Participant::whereIn('id', $participantIds)
                ->where('is_archived', true)
                ->count();
            
            if ($inactiveCount > 0) {
                throw new \Exception('Some selected participants are inactive. / بعض المشاركين المحددين غير نشطين.');
            }
        }
        
        // 2. Check for conflicts (Participants already assigned to other mentors in these competitions)
        $conflicts = [];
        $participantIds = array_keys($selectedParticipantMap);
        
        if (!empty($participantIds)) {
            // Find any existing assignments for these participants with OTHER mentors
            $existingAssignments = DB::table('mentor_participant')
                ->whereIn('participant_id', $participantIds)
                ->where('mentor_id', '!=', $mentorId)
                ->get();
                
            foreach ($existingAssignments as $assignment) {
                $pId = $assignment->participant_id;
                $cId = $assignment->competition_id;
                
                // Get the desired competitions for this participant
                $desiredCompIds = $selectedParticipantMap[$pId] ?? [];
                
                foreach ($desiredCompIds as $desiredCId) {
                    // Conflict if:
                    // 1. The existing assignment is Global (NULL cId) - covers ALL competitions
                    // 2. The existing assignment matches the desired competition
                    if (is_null($cId) || $cId == $desiredCId) {
                        // We have a conflict
                        $conflicts[$pId] = $conflicts[$pId] ?? [];
                        $conflicts[$pId][] = $desiredCId;
                    }
                }
            }
        }
        
        if (!empty($conflicts)) {
            $conflictDetails = [];
            // Fetch names for nice error message
            $participants = Participant::whereIn('id', array_keys($conflicts))->pluck('name', 'id');
            $competitions = Competition::whereIn('id', \Illuminate\Support\Arr::flatten($conflicts))->pluck('title', 'id');
            
            foreach ($conflicts as $pId => $compIds) {
                $pName = $participants[$pId] ?? $pId;
                if (is_array($pName)) $pName = $pName['en'] ?? $pName['ar'] ?? 'Unknown';
                
                $compNames = [];
                foreach (array_unique($compIds) as $cId) {
                    $cName = $competitions[$cId] ?? $cId;
                    if (is_array($cName)) $cName = $cName['en'] ?? $cName['ar'] ?? 'Unknown';
                    $compNames[] = $cName;
                }
                
                $conflictDetails[] = "$pName (" . implode(', ', $compNames) . ")";
            }
            
            $errorMsg = "The following participants already have mentors assigned in the selected programs: " . implode('; ', $conflictDetails);
            $errorMsgAr = "المشاركون التاليون لديهم بالفعل مرشدون معينون في البرامج المختارة: " . implode('; ', $conflictDetails);
            
            throw new \Exception("$errorMsg / $errorMsgAr");
        }
        
        // 3. Sync Assignments
        // Get all current assignments for this mentor
        $currentAssignments = DB::table('mentor_participant')
            ->where('mentor_id', $mentorId)
            ->get(); // Collection of {id, mentor_id, participant_id, competition_id, ...}
            
        // Get mentor's managed competitions (Scope)
        $managedCompetitionIds = $this->record->competitions()->pluck('competitions.id')->toArray();
        
        // Track what we've handled
        $processedPairs = []; // "part_id:comp_id" => true
        
        foreach ($currentAssignments as $assignment) {
            $pId = $assignment->participant_id;
            $cId = $assignment->competition_id; // Can be NULL (Global)
            
            // Handle Global Assignment (Legacy)
            if (is_null($cId)) {
                // We interpret Global as "Assigned to all managed competitions".
                // Since we are migrating to Scoped, we will REMOVE the global assignment.
                // If the user INTENDED to keep them, they would have selected the applications in the UI.
                // Since the UI pre-selects based on existing assignments, the user's selection should include them if they were relevant.
                DB::table('mentor_participant')->where('id', $assignment->id)->delete();
                continue;
            }
            
            // Handle Scoped Assignment
            // Is this assignment within the scope of competitions we are managing right now?
            if (!in_array($cId, $managedCompetitionIds)) {
                // Out of scope (e.g. mentor assigned to CompA and CompB, but this assignment is for CompC).
                // Keep it safe.
                continue;
            }
            
            // It IS in scope. Check if it is in the Desired Selection.
            $desiredCompetitions = $selectedParticipantMap[$pId] ?? [];
            if (in_array($cId, $desiredCompetitions)) {
                // Keep it.
                $pairKey = "{$pId}:{$cId}";
                $processedPairs[$pairKey] = true;
            } else {
                // Not in desired selection -> Remove it.
                $this->record->participants()->wherePivot('competition_id', $cId)->detach($pId);
            }
        }
        
        // 4. Add New Assignments
        foreach ($selectedParticipantMap as $pId => $compIds) {
            foreach ($compIds as $cId) {
                $pairKey = "{$pId}:{$cId}";
                
                // If not already processed (meaning it didn't exist or was re-added)
                if (!isset($processedPairs[$pairKey])) {
                    // Check if we should add it (is it in managed scope? Yes, selection constrained by UI).
                    $this->record->participants()->syncWithoutDetaching([
                        $pId => [
                            'competition_id' => $cId,
                            'assigned_by' => $currentUserId,
                            'assigned_at' => now(),
                            'notes' => $notes,
                        ]
                    ]);
                    // $this->record->participants()->attach($pId, [
                    //     'competition_id' => $cId,
                    //     'assigned_by' => $currentUserId,
                    //     'assigned_at' => now(),
                    //     'notes' => $notes,
                    // ]);
                    
                    // Send notification
                     $participant = Participant::find($pId);
                     if ($participant) {
                        $this->sendParticipantAssignmentNotifications($participant);
                     }
                }
            }
        }
    }

    /**
     * Send notifications to mentor and participant when individual participant is assigned
     */
    protected function sendParticipantAssignmentNotifications(Participant $participant): void
    {
        try {
            // Notify the mentor
            $this->record->notify(new MentorParticipantAssigned($this->record, $participant, 'mentor'));
            
            // Notify the participant
            $participant->notify(new MentorParticipantAssigned($this->record, $participant, 'participant'));
        } catch (\Exception $e) {
            \Log::error('Failed to send mentor-participant assignment notifications: ' . $e->getMessage());
        }
    }

    /**
     * Send notifications to mentor and team members
     */
    protected function sendTeamAssignmentNotifications(Team $team): void
    {
        try {
            // Notify the mentor
            $this->record->notify(new MentorTeamAssigned($this->record, $team, 'mentor'));
            
            // Notify team members (participants)
            $teamMembers = $team->members()->with('participant')->get();
            foreach ($teamMembers as $member) {
                if ($member->participant) {
                    $member->participant->notify(new MentorTeamAssigned($this->record, $team, 'team'));
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send mentor-team assignment notifications: ' . $e->getMessage());
        }
    }

    /**
     * Send notifications when a team is removed from a mentor
     */
    protected function sendTeamRemovalNotifications(Team $team): void
    {
        try {
            \Filament\Notifications\Notification::make()
                ->title('Team Removed / تم إزالة الفريق')
                ->body("Team '{$team->name}' has been removed from this mentor. / تم إزالة الفريق '{$team->name}' من هذا المرشد.")
                ->info()
                ->send();
        } catch (\Exception $e) {
            \Log::error('Failed to send team removal notifications: ' . $e->getMessage());
        }
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(Mentor::details());
    }
}
