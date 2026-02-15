<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Team as TeamModel;
use Illuminate\Support\Arr;
use App\Models\CompetitionApplication as CompetitionApplicationModel;
use App\Notifications\ParticipantAddedAsTeamMember;
use App\Models\TeamFormConfig;

class Team
{
    public function show($teamId = null): ?TeamModel
    {
        if ($teamId) {
            $team = TeamModel::withoutGlobalScopes()->findOrFail($teamId);
            // Prevent access to archived teams
            if ($team->isArchived()) {
                return null;
            }
            return $team;
        }

        $user = auth()->user();
        if (!$user) {
            return null;
        }

        // Get application_id from request to filter by competition
        $applicationId = request('application_id');

        if (!$applicationId) {
            return null;
        }

        // Get the application to find the competition
        $application = CompetitionApplicationModel::findOrFail($applicationId);
        $competitionId = $application->competition_id;

        // Find team where user is a member and the team belongs to the current competition
        $userTeam = TeamModel::whereHas('members', function ($query) use ($user) {
                $query->where('participant_id', $user->id);
            })
            ->whereHas('application', function ($query) use ($competitionId) {
                $query->where('competition_id', $competitionId);
            })
            ->active()
            ->first();

        // Prevent access to archived teams
        if ($userTeam && $userTeam->isArchived()) {
            return null;
        }
        return $userTeam;
    }

    public function store($applicationId, $data): TeamModel
    {
        // Validate team size BEFORE creating the team
        // Always validate if has_team is true, even if serial_numbers is empty
        if (isset($data['serial_numbers']) || (isset($data['has_team']) && $data['has_team'])) {
            // Get the application to access competition_id
            $application = \App\Models\CompetitionApplication::find($applicationId);
            if ($application) {
                // Create a temporary team object for validation
                $tempTeam = new TeamModel();
                $tempTeam->setAttribute('application_id', $applicationId);
                $tempTeam->setRelation('application', $application);
                
                // Normalize serial_numbers if it's a string
                $serialNumbers = $data['serial_numbers'] ?? [];
                if (is_string($serialNumbers)) {
                    $serialNumbers = explode(',', $serialNumbers);
                }
                if (!is_array($serialNumbers)) {
                    $serialNumbers = [];
                }
                
                // Normalize and filter empty values
                $serialNumbers = array_unique(array_filter(array_map('trim', $serialNumbers)));
                
                // Validate before creating the team - this will throw ValidationException if invalid
                $this->validateTeamSize($tempTeam, $serialNumbers, true);
            }
        }

        $team = TeamModel::withoutGlobalScopes()->create(Arr::add($data, 'application_id', $applicationId));

        if (isset($data['team_logo']) || isset($data['logo'])) {
            $logoData = $data['logo'] ?? $data['team_logo'];
            
            // Handle both file uploads and existing file paths
            if (is_object($logoData) && method_exists($logoData, 'store')) {
                // It's a file upload object
                $team->update(['logo' => $logoData->store('teams')]);
            } elseif (is_string($logoData) && !empty($logoData)) {
                // It's an existing file path
                $team->update(['logo' => $logoData]);
            }
        }


        if (isset($data['serial_numbers'])) {
            $this->storeTeamMembers($team, $data['serial_numbers']);
        } elseif (isset($data['has_team']) && $data['has_team']) {
            // If has_team is true but no serial_numbers provided, validate minimum team size
            // This handles the case where user submits team registration without members
            $application = $team->application;
            if ($application) {
                $registrationConfig = \App\Models\RegistrationFormConfig::where('competition_id', $application->competition_id)
                    ->active()
                    ->first();
                
                $teamConfig = null;
                
                if ($registrationConfig) {
                    $minTeamMembers = $registrationConfig->min_team_members !== null ? $registrationConfig->min_team_members : 2;
                } else {
                    $teamConfig = TeamFormConfig::where('competition_id', $application->competition_id)
                        ->active()
                        ->notArchived()
                        ->first();
                    
                    if ($teamConfig) {
                        $minTeamMembers = $teamConfig->min_team_members !== null ? $teamConfig->min_team_members : 2;
                    } else {
                        $minTeamMembers = 2;
                    }
                }
                
                // If minimum is greater than 1, reject (leader alone is not enough)
                if ($minTeamMembers > 1) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'serial_numbers' => [
                            __('competition_application.The total number of team members must be at least :min.', [
                                'min' => $minTeamMembers,
                            ]) ?: "The total number of team members must be at least {$minTeamMembers}.",
                        ],
                    ]);
                }
            }
        }

        $team->application()->update(['has_team' => true]);

        $application = $team->application()->first();
        if ($application && $application->competition_id) {
            $teamFormConfig = TeamFormConfig::where('competition_id', $application->competition_id)
                ->active()
                ->notArchived() // Only use non-archived Team Form Configurations
                ->first();
            if ($teamFormConfig && $teamFormConfig->auto_publish_teams) {
                $team->update(['is_published' => true]);
            }
        }

        return $team;
    }

    public function update($team, $data): TeamModel
    {
        if (!isset($data['sub_track_id'])) {
            $data['sub_track_id'] = null;
        }

        $team->update($data);

        $project = $team->project;
        if ($project && $project->form_submissions) {
            $formSubmissions = $project->form_submissions->toArray();

            if (isset($data['track_id'])) {
                $formSubmissions['track'] = $data['track_id'];
            }
            if (isset($data['sub_track_id'])) {
                $formSubmissions['sub_track'] = $data['sub_track_id'];
            }

            $project->form_submissions = $formSubmissions;
            $project->save();
        }
        if (isset($data['logo'])) {
            // Handle both file uploads and existing file paths
            if (is_object($data['logo']) && method_exists($data['logo'], 'store')) {
                // It's a file upload object
                $team->update(['logo' => $data['logo']->store('teams')]);
            } elseif (is_string($data['logo']) && !empty($data['logo'])) {
                // It's an existing file path
                $team->update(['logo' => $data['logo']]);
            }
        }

        return $team;
    }

    public function storeTeamMembers($team, $serialNumbers): void
    {
        if (!is_array($serialNumbers)) {
            $serialNumbers = $serialNumbers ? explode(',', $serialNumbers) : [];
        }

        // Normalize and remove duplicates
        $serialNumbers = array_unique(array_filter(array_map('trim', $serialNumbers)));

        // Server-side validation: Check team size limits (enforced before any database operations)
        // This will validate minimum team size - if serialNumbers is empty, total will be 1 (leader only)
        $this->validateTeamSize($team, $serialNumbers, true);

        // Get participant IDs for final validation check
        $participantIds = Participant::whereIn('serial_number', $serialNumbers)
            ->pluck('id')
            ->toArray();

        // Final validation check right before database operations (prevents race conditions)
        $this->validateTeamSizeBeforeInsert($team, $participantIds);

        // register the current user as a team leader (only if not already a member)
        if (!$team->members()->where('participant_id', auth()->id())->exists()) {
            $team->members()->create(['participant_id' => auth()->id(), 'is_leader' => true]);
            // Refresh team after adding leader to get accurate count
            $team->refresh();
        }

        // Validate again after adding leader (in case leader wasn't counted properly before)
        $this->validateTeamSizeBeforeInsert($team, $participantIds);

        foreach ($serialNumbers as $serialNumber) {
            $serialNumber = trim($serialNumber);
            $participant = Participant::where('serial_number', $serialNumber)->first();

            if ($participant) {
                // Skip if participant is the current user (already added as leader)
                if ($participant->id === auth()->id()) {
                    continue;
                }

                // Check if member already exists before adding
                $memberExists = $team->members()->where('participant_id', $participant->id)->exists();
                
                // If this is a new member, validate team size before adding
                if (!$memberExists) {
                    $team->refresh();
                    $currentCount = $team->members()->count();
                    $application = $team->application;
                    if ($application) {
                        // Get team configuration - try TeamFormConfig first, then RegistrationFormConfig as fallback
                        $teamConfig = TeamFormConfig::where('competition_id', $application->competition_id)
                            ->active()
                            ->notArchived()
                            ->first();
                        
                        // If TeamFormConfig doesn't exist, try RegistrationFormConfig
                        if (!$teamConfig) {
                            $registrationConfig = \App\Models\RegistrationFormConfig::where('competition_id', $application->competition_id)
                                ->active()
                                ->notArchived()
                                ->first();
                            
                            if ($registrationConfig) {
                                // Use RegistrationFormConfig values
                                $minTeamMembers = $registrationConfig->min_team_members ?? 2;
                                $maxTeamMembers = $registrationConfig->max_team_members ?? config('team.max_members', 6);
                            } else {
                                // Use defaults if neither exists
                                $minTeamMembers = 2;
                                $maxTeamMembers = config('team.max_members', 6);
                            }
                        } else {
                            // Use TeamFormConfig values
                            $minTeamMembers = $teamConfig->min_team_members ?? 2;
                            $maxTeamMembers = $teamConfig->max_team_members ?? config('team.max_members', 6);
                        }
                        $totalAfterAdd = $currentCount + 1;
                        
                        // Validate MINIMUM
                        if ($totalAfterAdd < $minTeamMembers) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'serial_numbers' => [
                                    __('competition_application.The total number of team members must be at least :min.', [
                                        'min' => $minTeamMembers,
                                    ]) ?: "The total number of team members must be at least {$minTeamMembers}.",
                                ],
                            ]);
                        }
                        
                        // Validate MAXIMUM
                        if ($totalAfterAdd > $maxTeamMembers) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'serial_numbers' => [
                                    __('competition_application.The total number of team members must not exceed :max.', [
                                        'max' => $maxTeamMembers,
                                    ]),
                                ],
                            ]);
                        }
                    }
                }

                // update has_team for each participant
                CompetitionApplicationModel::where('participant_id', $participant->id)
                    ->where('competition_id', $team->application->competition_id)
                    ->update(['has_team' => true]);

                $team->members()->updateOrCreate(
                    ['participant_id' => $participant->id],
                    ['is_leader' => false],
                );

                // Send notification to the added team member
                if (env('APP_ENV') != 'local') {
                    $participant->notify(new ParticipantAddedAsTeamMember($team));
                }
            }
        }
        
        // Final validation after all members are added
        $team->refresh();
        $finalCount = $team->members()->count();
        $application = $team->application;
        
        if ($application) {
            // Get team configuration for final validation
            $registrationConfig = \App\Models\RegistrationFormConfig::where('competition_id', $application->competition_id)
                ->active()
                ->first();
            
            $teamConfig = null;
            
            if ($registrationConfig) {
                $minTeamMembers = $registrationConfig->min_team_members !== null ? $registrationConfig->min_team_members : 2;
                $maxTeamMembers = $registrationConfig->max_team_members !== null ? $registrationConfig->max_team_members : config('team.max_members', 6);
            } else {
                $teamConfig = TeamFormConfig::where('competition_id', $application->competition_id)
                    ->active()
                    ->notArchived()
                    ->first();
                
                if ($teamConfig) {
                    $minTeamMembers = $teamConfig->min_team_members !== null ? $teamConfig->min_team_members : 2;
                    $maxTeamMembers = $teamConfig->max_team_members !== null ? $teamConfig->max_team_members : config('team.max_members', 6);
                } else {
                    $minTeamMembers = 2;
                    $maxTeamMembers = config('team.max_members', 6);
                }
            }

            // Final validation - ensure team size meets minimum requirement
            if ($finalCount < $minTeamMembers) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'serial_numbers' => [
                        __('competition_application.The total number of team members must be at least :min.', [
                            'min' => $minTeamMembers,
                        ]) ?: "The total number of team members must be at least {$minTeamMembers}.",
                    ],
                ]);
            }

            // Final validation - ensure team size doesn't exceed maximum
            if ($finalCount > $maxTeamMembers) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'serial_numbers' => [
                        __('competition_application.The total number of team members must not exceed :max.', [
                            'max' => $maxTeamMembers,
                        ]),
                    ],
                ]);
            }
        }
    }

    public function updateTeamMembers($team, $serialNumbers): TeamModel
    {
        if (!is_array($serialNumbers)) {
            $serialNumbers = explode(',', $serialNumbers);
        }

        // Normalize and remove duplicates
        $serialNumbers = array_unique(array_filter(array_map('trim', $serialNumbers)));

        // Get participant IDs for validation
        $participantIds = Participant::whereIn('serial_number', $serialNumbers)
            ->pluck('id')
            ->toArray();

        // For updateTeamMembers, we need to validate the FINAL state after replacement
        // Calculate what the team will look like after replacing members
        // The final team will be: leader (always 1) + new members from serialNumbers
        
        $application = $team->application;
        if ($application) {
            // Get team configuration
            $registrationConfig = \App\Models\RegistrationFormConfig::where('competition_id', $application->competition_id)
                ->active()
                ->first();
            
            $teamConfig = null;
            
            if ($registrationConfig) {
                $minTeamMembers = $registrationConfig->min_team_members !== null ? $registrationConfig->min_team_members : 2;
                $maxTeamMembers = $registrationConfig->max_team_members !== null ? $registrationConfig->max_team_members : config('team.max_members', 6);
            } else {
                $teamConfig = TeamFormConfig::where('competition_id', $application->competition_id)
                    ->active()
                    ->notArchived()
                    ->first();
                
                if ($teamConfig) {
                    $minTeamMembers = $teamConfig->min_team_members !== null ? $teamConfig->min_team_members : 2;
                    $maxTeamMembers = $teamConfig->max_team_members !== null ? $teamConfig->max_team_members : config('team.max_members', 6);
                } else {
                    $minTeamMembers = 2;
                    $maxTeamMembers = config('team.max_members', 6);
                }
            }

            // Filter out the leader from participant IDs
            $memberParticipantIds = array_diff($participantIds, [auth()->id()]);
            $newMembersCount = count($memberParticipantIds);
            
            // Final team size: leader (1) + new members
            $finalTeamSize = 1 + $newMembersCount;

            // Validate MINIMUM team size BEFORE making any changes
            if ($finalTeamSize < $minTeamMembers) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'serial_numbers' => [
                        __('competition_application.The total number of team members must be at least :min.', [
                            'min' => $minTeamMembers,
                        ]) ?: "The total number of team members must be at least {$minTeamMembers}.",
                    ],
                ]);
            }

            // Validate MAXIMUM team size BEFORE making any changes
            if ($finalTeamSize > $maxTeamMembers) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'serial_numbers' => [
                        __('competition_application.The total number of team members must not exceed :max.', [
                            'max' => $maxTeamMembers,
                        ]),
                    ],
                ]);
            }
        }

        // Ensure leader exists and refresh team
        $team->refresh();
        if (!$team->members()->where('participant_id', auth()->id())->exists()) {
            // Leader should already exist, but if not, add them
            $team->members()->create(['participant_id' => auth()->id(), 'is_leader' => true]);
            $team->refresh();
        }

        // Delete all existing members except the leader (since we're replacing them)
        $team->members()
            ->where('participant_id', '!=', auth()->id())
            ->delete();
        
        $team->refresh();

        foreach ($serialNumbers as $serialNumber) {
            $serialNumber = trim($serialNumber);
            $participant = Participant::where('serial_number', $serialNumber)->first();

            if ($participant) {
                // Skip if participant is the current user (leader)
                if ($participant->id === auth()->id()) {
                    continue;
                }

                // update has_team for each participant
                CompetitionApplicationModel::where('participant_id', $participant->id)
                    ->where('competition_id', $team->application->competition_id)
                    ->update(['has_team' => true]);

                $team->members()->create([
                    'participant_id' => $participant->id,
                    'is_leader' => false,
                ]);

                // Send notification to newly added team members
                if (env('APP_ENV') != 'local') {
                    $participant->notify(new ParticipantAddedAsTeamMember($team));
                }
            }
        }
        
        // Final validation after all members are added
        $team->refresh();
        $finalCount = $team->members()->count();
        
        if ($application) {
            // Get team configuration again for final validation
            $registrationConfig = \App\Models\RegistrationFormConfig::where('competition_id', $application->competition_id)
                ->active()
                ->first();
            
            $teamConfig = null;
            
            if ($registrationConfig) {
                $minTeamMembers = $registrationConfig->min_team_members !== null ? $registrationConfig->min_team_members : 2;
                $maxTeamMembers = $registrationConfig->max_team_members !== null ? $registrationConfig->max_team_members : config('team.max_members', 6);
            } else {
                $teamConfig = TeamFormConfig::where('competition_id', $application->competition_id)
                    ->active()
                    ->notArchived()
                    ->first();
                
                if ($teamConfig) {
                    $minTeamMembers = $teamConfig->min_team_members !== null ? $teamConfig->min_team_members : 2;
                    $maxTeamMembers = $teamConfig->max_team_members !== null ? $teamConfig->max_team_members : config('team.max_members', 6);
                } else {
                    $minTeamMembers = 2;
                    $maxTeamMembers = config('team.max_members', 6);
                }
            }

            // Final validation - ensure team size meets minimum requirement
            if ($finalCount < $minTeamMembers) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'serial_numbers' => [
                        __('competition_application.The total number of team members must be at least :min.', [
                            'min' => $minTeamMembers,
                        ]) ?: "The total number of team members must be at least {$minTeamMembers}.",
                    ],
                ]);
            }

            // Final validation - ensure team size doesn't exceed maximum
            if ($finalCount > $maxTeamMembers) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'serial_numbers' => [
                        __('competition_application.The total number of team members must not exceed :max.', [
                            'max' => $maxTeamMembers,
                        ]),
                    ],
                ]);
            }
        }

        return $team;
    }

    public function deleteTeamMembers($team, $serialNumbers): TeamModel
    {
        if (!is_array($serialNumbers)) {
            $serialNumbers = is_string($serialNumbers) ? [$serialNumbers] : [];
        }

        foreach ($serialNumbers as $serialNumber) {
            $serialNumber = trim($serialNumber);

            if (empty($serialNumber)) {
                continue;
            }

            $participant = Participant::where('serial_number', $serialNumber)->first();

            if ($participant) {
                // Prevent deleting the team leader
                $member = $team->members()->where('participant_id', $participant->id)->first();

                if ($member && $member->is_leader) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], []),
                        ['serial_numbers' => ['Cannot delete the team leader.']]
                    );
                }

                // update has_team for each participant
                CompetitionApplicationModel::where('participant_id', $participant->id)
                    ->where('competition_id', $team->application->competition_id)
                    ->update(['has_team' => false]);

                $team->members()->where('participant_id', $participant->id)->delete();
            }
        }

        return $team->fresh();
    }

    /**
     * Validate team size before adding members
     * This is a centralized validation method to ensure consistent enforcement
     *
     * @param TeamModel $team
     * @param array $serialNumbers Array of serial numbers to be added
     * @param bool $includeLeader Whether to include the team leader in the count
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateTeamSize(TeamModel $team, array $serialNumbers, bool $includeLeader = true): void
    {
        $application = $team->application;
        if (!$application) {
            return;
        }

        // Get team configuration - prioritize RegistrationFormConfig over TeamFormConfig
        // RegistrationFormConfig is the source of truth for team size limits
        // Note: scopeActive() already checks is_archived, so we don't need notArchived()
        $registrationConfig = \App\Models\RegistrationFormConfig::where('competition_id', $application->competition_id)
            ->active()
            ->first();
        
        $teamConfig = null;
        
        // If RegistrationFormConfig exists, use it (it's the source of truth)
        if ($registrationConfig) {
            // Use RegistrationFormConfig values - don't use ?? operator if value is explicitly null
            $minTeamMembers = $registrationConfig->min_team_members !== null ? $registrationConfig->min_team_members : 2;
            $maxTeamMembers = $registrationConfig->max_team_members !== null ? $registrationConfig->max_team_members : config('team.max_members', 6);
        } else {
            // Fallback to TeamFormConfig if RegistrationFormConfig doesn't exist
            $teamConfig = TeamFormConfig::where('competition_id', $application->competition_id)
                ->active()
                ->notArchived()
                ->first();
            
            if ($teamConfig) {
                // Use TeamFormConfig values - don't use ?? operator if value is explicitly null
                $minTeamMembers = $teamConfig->min_team_members !== null ? $teamConfig->min_team_members : 2;
                $maxTeamMembers = $teamConfig->max_team_members !== null ? $teamConfig->max_team_members : config('team.max_members', 6);
            } else {
                // Use defaults if neither exists
                $minTeamMembers = 2;
                $maxTeamMembers = config('team.max_members', 6);
            }
        }

        // Normalize serial numbers
        $serialNumbers = array_unique(array_filter(array_map('trim', $serialNumbers)));

        // Get existing member participant IDs (excluding the current user who is/will be leader)
        $existingMemberParticipantIds = $team->members()
            ->where('participant_id', '!=', auth()->id())
            ->pluck('participant_id')
            ->toArray();

        // Get participant IDs for the serial numbers being added
        $participantIds = Participant::whereIn('serial_number', $serialNumbers)
            ->pluck('id')
            ->toArray();

        // Filter out participants that already exist in the team
        $newParticipantIds = array_diff($participantIds, $existingMemberParticipantIds);
        // Also filter out the current user if they're in the serial numbers
        $newParticipantIds = array_diff($newParticipantIds, [auth()->id()]);
        $newMembersCount = count($newParticipantIds);

        $existingMembersCount = count($existingMemberParticipantIds);

        // Calculate total members: existing members (excluding leader) + new members + leader (always counted as 1)
        // The leader is ALWAYS counted as 1 member, regardless of whether they're already in the team
        $totalMembers = $existingMembersCount + $newMembersCount;
        if ($includeLeader) {
            $totalMembers += 1; // Always add leader (counted as 1 member)
        }

        // Validate MINIMUM team size
        if ($totalMembers < $minTeamMembers) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'serial_numbers' => [
                    __('competition_application.The total number of team members must be at least :min.', [
                        'min' => $minTeamMembers,
                    ]) ?: "The total number of team members must be at least {$minTeamMembers}.",
                ],
            ]);
        }

        // Validate MAXIMUM team size - reject if exceeded
        if ($totalMembers > $maxTeamMembers) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'serial_numbers' => [
                    __('competition_application.The total number of team members must not exceed :max.', [
                        'max' => $maxTeamMembers,
                    ]),
                ],
            ]);
        }
    }

    /**
     * Final validation check right before database operations
     * This prevents race conditions where team size might change between validation and insertion
     *
     * @param TeamModel $team
     * @param array $participantIds Array of participant IDs to be added
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateTeamSizeBeforeInsert(TeamModel $team, array $participantIds): void
    {
        $application = $team->application;
        if (!$application) {
            return;
        }

        // Get team configuration - prioritize RegistrationFormConfig over TeamFormConfig
        // RegistrationFormConfig is the source of truth for team size limits
        // Note: scopeActive() already checks is_archived, so we don't need notArchived()
        $registrationConfig = \App\Models\RegistrationFormConfig::where('competition_id', $application->competition_id)
            ->active()
            ->first();
        
        $teamConfig = null;
        
        // If RegistrationFormConfig exists, use it (it's the source of truth)
        if ($registrationConfig) {
            // Use RegistrationFormConfig values - don't use ?? operator if value is explicitly null
            $minTeamMembers = $registrationConfig->min_team_members !== null ? $registrationConfig->min_team_members : 2;
            $maxTeamMembers = $registrationConfig->max_team_members !== null ? $registrationConfig->max_team_members : config('team.max_members', 6);
        } else {
            // Fallback to TeamFormConfig if RegistrationFormConfig doesn't exist
            $teamConfig = TeamFormConfig::where('competition_id', $application->competition_id)
                ->active()
                ->notArchived()
                ->first();
            
            if ($teamConfig) {
                // Use TeamFormConfig values - don't use ?? operator if value is explicitly null
                $minTeamMembers = $teamConfig->min_team_members !== null ? $teamConfig->min_team_members : 2;
                $maxTeamMembers = $teamConfig->max_team_members !== null ? $teamConfig->max_team_members : config('team.max_members', 6);
            } else {
                // Use defaults if neither exists
                $minTeamMembers = 2;
                $maxTeamMembers = config('team.max_members', 6);
            }
        }

        // Refresh team to get latest member count (prevents race conditions)
        $team->refresh();
        
        // Get current member count (this already includes the leader if they're already a member)
        $currentMemberCount = $team->members()->count();
        
        // Filter out participants that already exist
        $existingParticipantIds = $team->members()->pluck('participant_id')->toArray();
        $newParticipantIds = array_diff($participantIds, $existingParticipantIds);
        $newMembersCount = count($newParticipantIds);

        // Check if leader is already a member
        $leaderIsMember = in_array(auth()->id(), $existingParticipantIds);
        
        // Calculate total after adding new members
        // The leader is ALWAYS counted as 1 member in the total
        $totalAfterAdd = $currentMemberCount + $newMembersCount;
        
        // If leader is not yet a member, add 1 for the leader (they will be added)
        if (!$leaderIsMember) {
            $totalAfterAdd += 1; // Add leader (always counted as 1 member)
        }

        // Validate MINIMUM team size
        if ($totalAfterAdd < $minTeamMembers) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'serial_numbers' => [
                    __('competition_application.The total number of team members must be at least :min.', [
                        'min' => $minTeamMembers,
                    ]) ?: "The total number of team members must be at least {$minTeamMembers}.",
                ],
            ]);
        }

        // Validate MAXIMUM team size - reject if exceeded
        if ($totalAfterAdd > $maxTeamMembers) {
                'new_members_count' => $newMembersCount,
                'leader_is_member' => $leaderIsMember,
                'total_after_add' => $totalAfterAdd,
                'max_team_members' => $maxTeamMembers,
                'min_team_members' => $minTeamMembers,
                'participant_ids' => $participantIds,
            ]);
            
            throw \Illuminate\Validation\ValidationException::withMessages([
                'serial_numbers' => [
                    __('competition_application.The total number of team members must not exceed :max.', [
                        'max' => $maxTeamMembers,
                    ]),
                ],
            ]);
        }
    }

    /**
     * @param $data
     * @return array
     */
    public function formatTeamData($data): array
    {
        return [
            'name' => $data['answers']['team_name'] ?? '',
            'track_id' => $data['track_id'] ?? null,
            'sub_track_id' => $data['sub_track_id'] ?? null,
            'logo' => $data['answers']['team_logo'] ?? null,
            'serial_numbers' => $data['answers']['team_serial'] ?? null
        ];
    }
}
