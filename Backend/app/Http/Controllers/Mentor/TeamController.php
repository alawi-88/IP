<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Http\Resources\MentorTeamResource;
use App\Http\Resources\MentorProjectResource;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Track;
use App\Models\SubTrack;

class TeamController extends Controller
{
    /**
     * Get all teams and individual participants assigned to the authenticated mentor.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $mentor = Auth::user();
            $search = $request->input('search');
            $competitionId = $request->input('competition_id');
            $typeFilter = $request->input('type'); // 'team', 'participant', or null for all
            $delivererFilter = $request->input('deliverer'); // 'true', 'false', or null for all
            
            $results = collect();
            
            // Get Teams (if not filtering by participant only)
            if (!$typeFilter || $typeFilter === 'team') {
                $teamsQuery = $mentor->teams()
                    ->with([
                        'members.participant',
                        'track',
                        'subTrack',
                        'project',
                        'application.competition',
                    ])
                    ->withPivot(['assigned_at', 'notes']);

                if ($competitionId) {
                    $teamsQuery->whereHas('application', function ($q) use ($competitionId) {
                        $q->where('competition_id', $competitionId);
                    });
                }

                if ($search) {
                    $teamsQuery->where('name', 'like', "%{$search}%");
                }

                // Filter teams by deliverer status (has submitted project or not)
                if ($delivererFilter !== null) {
                    if ($delivererFilter === 'true' || $delivererFilter === true || $delivererFilter === '1') {
                        // Only teams that have delivered (have at least one non-draft project)
                        $teamsQuery->whereHas('projects', function ($q) {
                            $q->where('type', '!=', 'draft');
                        });
                    } else {
                        // Only teams that have NOT delivered (no non-draft projects)
                        $teamsQuery->whereDoesntHave('projects', function ($q) {
                            $q->where('type', '!=', 'draft');
                        });
                    }
                }

                $teams = $teamsQuery->get()->map(function ($team) {
                    $competitionTitle = $team->application?->competition?->title;
                    if (is_array($competitionTitle)) {
                        $competitionTitle = $competitionTitle['en'] ?? $competitionTitle['ar'] ?? 'Unknown';
                    }
                
                    
                    // Check if team has delivered (has any non-draft project)
                    $hasDelivered = $team->projects()
                        ->where('type', '!=', 'draft')
                        ->exists();

                    
                    return [
                        'id' => $team->id,
                        'type' => 'team',
                        'name' => $team->name,
                        'logo' => $team->logo ? Team::normalizeStorageUrl($team->logo) : null,
                        'email' => null,
                        'members_count' => $team->members->count(),
                        'has_delivered' => $hasDelivered,
                        'members' => $team->members->map(function ($member) {
                            $participantName = is_array($member->participant?->name)
                                ? ($member->participant->name['en'] ?? $member->participant->name['ar'] ?? 'Unknown')
                                : ($member->participant?->name ?? 'Unknown');
                            return [
                                'id' => $member->participant?->id,
                                'name' => $participantName,
                                'email' => $member->participant?->email,
                                'is_leader' => $member->is_leader,
                            ];
                        }),
                        'competition' => $team->application?->competition ? [
                            'id' => $team->application->competition->id,
                            'title' => $competitionTitle,
                        ] : null,
                        'track' => $team->track ? [
                            'id' => $team->track->id,
                            'name' => is_array($team->track->name) 
                                ? ($team->track->name['en'] ?? $team->track->name['ar'] ?? 'Unknown')
                                : $team->track->name,
                        ] : null,
                        'sub_track' => $team->subTrack ? [
                            'id' => $team->subTrack->id,
                            'name' => is_array($team->subTrack->name) 
                                ? ($team->subTrack->name['en'] ?? $team->subTrack->name['ar'] ?? 'Unknown')
                                : $team->subTrack->name,
                        ] : null,
                        'project' => $team->project ? [
                            'id' => $team->project->id,
                            'name' => $team->project->form_submissions['project_name'] ?? null,
                            'status' => $team->project->status,
                        ] : null,
                        'assignment' => [
                            'assigned_at' => $team->pivot?->assigned_at,
                            'notes' => $team->pivot?->notes,
                        ],
                    ];
                });
                
                $results = $results->merge($teams);
            }
            
            // Get Individual Participants (if not filtering by team only)
            if (!$typeFilter || $typeFilter === 'participant') {
                $participantsQuery = $mentor->participants()
                    ->with(['applications' => function ($query) use ($competitionId) {
                        $query->where(function ($q) {
                            $q->where('registered_as', 'individual')
                              ->orWhere('form_submissions->register_as', 'individual');
                        })
                        ->with(['competition', 'projects' => function ($q) {
                            $q->where('type', '!=', 'draft');
                        }]);
                        if ($competitionId) {
                            $query->where('competition_id', $competitionId);
                        }
                    }])
                    ->withPivot(['assigned_at', 'notes']);

                if ($search) {
                    $participantsQuery->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
                }

                // Filter individual participants by deliverer status (has submitted project or not)
                if ($delivererFilter !== null) {
                    if ($delivererFilter === 'true' || $delivererFilter === true || $delivererFilter === '1') {
                        // Only participants that have delivered (have at least one non-draft project in individual applications)
                        $participantsQuery->whereHas('applications', function ($appQuery) {
                            $appQuery->where(function ($q) {
                                $q->where('registered_as', 'individual')
                                  ->orWhere('form_submissions->register_as', 'individual');
                            })
                            ->whereHas('projects', function ($projQuery) {
                                $projQuery->where('type', '!=', 'draft');
                            });
                        });
                    } else {
                        // Only participants that have NOT delivered (no non-draft projects in individual applications)
                        $participantsQuery->whereDoesntHave('applications', function ($appQuery) {
                            $appQuery->where(function ($q) {
                                $q->where('registered_as', 'individual')
                                  ->orWhere('form_submissions->register_as', 'individual');
                            })
                            ->whereHas('projects', function ($projQuery) {
                                $projQuery->where('type', '!=', 'draft');
                            });
                        });
                    }
                }

                $participants = $participantsQuery->get()->map(function ($participant) {
                    $name = is_array($participant->name) 
                        ? ($participant->name['en'] ?? $participant->name['ar'] ?? 'Unknown')
                        : $participant->name;

                    // Get competition & track from first individual application
                    $application = $participant->applications->first();
                    $competitionTitle = $application?->competition?->title;
                    if (is_array($competitionTitle)) {
                        $competitionTitle = $competitionTitle['en'] ?? $competitionTitle['ar'] ?? 'Unknown';
                    }
                    
                    // Derive track/sub-track for individual participant
                    [$trackData, $subTrackData] = $this->extractTrackData($application);
                    
                    // Get first project
                    $project = $application?->projects->first();
                    
                    // Check if participant has delivered (has any non-draft project in individual applications)
                    $hasDelivered = $participant->applications->contains(function ($app) {
                        return $app->projects->where('type', '!=', 'draft')->isNotEmpty();
                    });

                    return [
                        'id' => $participant->id,
                        'type' => 'participant',
                        'name' => $name,
                        'logo' => $participant->avatar ? Team::normalizeStorageUrl($participant->avatar) : null,
                        'email' => $participant->email,
                        'members_count' => 1,
                        'has_delivered' => $hasDelivered,
                        'members' => [[
                            'id' => $participant->id,
                            'name' => $name,
                            'email' => $participant->email,
                            'is_leader' => true,
                        ]],
                        'competition' => $application?->competition ? [
                            'id' => $application->competition->id,
                            'title' => $competitionTitle,
                        ] : null,
                        'track' => $trackData,
                        'sub_track' => $subTrackData,
                        'project' => $project ? [
                            'id' => $project->id,
                            'name' => $project->form_submissions['project_name'] ?? null,
                            'status' => $project->status,
                        ] : null,
                        'assignment' => [
                            'assigned_at' => $participant->pivot?->assigned_at,
                            'notes' => $participant->pivot?->notes,
                        ],
                    ];
                });
                
                $results = $results->merge($participants);
            }
            
            // Sort by assigned_at descending
            $results = $results->sortByDesc('assignment.assigned_at')->values();
            
            // Manual pagination
            $perPage = 15;
            $page = $request->input('page', 1);
            $total = $results->count();
            $paginatedData = $results->forPage($page, $perPage)->values();

            return response()->json([
                'success' => true,
                'data' => $paginatedData,
                'pagination' => [
                    'current_page' => (int) $page,
                    'last_page' => (int) ceil($total / $perPage),
                    'per_page' => $perPage,
                    'total' => $total,
                ],
                'message' => $results->isEmpty() ? __('mentor.no_teams_assigned') : null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('mentor.failed_to_load_teams'),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get detailed information about a specific assigned team or individual participant.
     * First tries to find as team, if not found tries as individual participant.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $mentor = Auth::user();
            
            // Try to find as team first
            $team = $mentor->teams()
                ->with([
                    'members.participant',
                    'track',
                    'subTrack',
                    'projects' => function ($query) {
                        $query->where('type', '!=', 'draft')
                            ->with(['comments' => function ($q) {
                                $q->with(['user', 'author'])->orderBy('created_at', 'asc');
                            }]);
                    },
                    'application.competition',
                ])
                ->withPivot(['assigned_at', 'notes'])
                ->find($id);

            if ($team) {
                return response()->json([
                    'success' => true,
                    'data' => $this->formatTeamResponse($team),
                ]);
            }
            
            // Try to find as individual participant
            $participant = $mentor->participants()
                ->with(['applications' => function ($query) {
                    $query->where(function ($q) {
                        $q->where('registered_as', 'individual')
                          ->orWhere('form_submissions->register_as', 'individual');
                    })
                    ->with(['competition', 'projects' => function ($q) {
                        $q->where('type', '!=', 'draft')
                            ->with(['comments' => function ($cq) {
                                $cq->with(['user', 'author'])->orderBy('created_at', 'asc');
                            }]);
                    }]);
                }])
                ->withPivot(['assigned_at', 'notes'])
                ->find($id);

            if ($participant) {
                return response()->json([
                    'success' => true,
                    'data' => $this->formatParticipantResponse($participant),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => __('mentor.team_not_found'),
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('mentor.failed_to_load_team_details'),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Format team response with projects.
     */
    private function formatTeamResponse($team): array
    {
        $competitionTitle = $team->application?->competition?->title;
        if (is_array($competitionTitle)) {
            $competitionTitle = $competitionTitle['en'] ?? $competitionTitle['ar'] ?? 'Unknown';
        }
        
        return [
            'id' => $team->id,
            'type' => 'team',
            'name' => $team->name,
            'logo' => $team->logo ? Team::normalizeStorageUrl($team->logo) : null,
            'members_count' => $team->members->count(),
            'members' => $team->members->map(function ($member) {
                $name = is_array($member->participant?->name)
                    ? ($member->participant->name['en'] ?? $member->participant->name['ar'] ?? 'Unknown')
                    : ($member->participant?->name ?? 'Unknown');
                return [
                    'id' => $member->participant?->id,
                    'name' => $name,
                    'email' => $member->participant?->email,
                    'phone' => $member->participant?->phone,
                    'is_leader' => $member->is_leader,
                ];
            }),
            'competition' => $team->application?->competition ? [
                'id' => $team->application->competition->id,
                'title' => $competitionTitle,
            ] : null,
            'track' => $team->track ? [
                'id' => $team->track->id,
                'name' => is_array($team->track->name) 
                    ? ($team->track->name['en'] ?? $team->track->name['ar'] ?? 'Unknown')
                    : $team->track->name,
            ] : null,
            'sub_track' => $team->subTrack ? [
                'id' => $team->subTrack->id,
                'name' => is_array($team->subTrack->name) 
                    ? ($team->subTrack->name['en'] ?? $team->subTrack->name['ar'] ?? 'Unknown')
                    : $team->subTrack->name,
            ] : null,
            'projects' => $team->projects->map(function ($project) {
                return $this->formatProjectResponse($project);
            }),
            'assignment' => [
                'assigned_at' => $team->pivot?->assigned_at,
                'notes' => $team->pivot?->notes,
            ],
        ];
    }

    /**
     * Format individual participant response with projects.
     */
    private function formatParticipantResponse($participant): array
    {
        $name = is_array($participant->name) 
            ? ($participant->name['en'] ?? $participant->name['ar'] ?? 'Unknown')
            : $participant->name;

        // Collect all projects from all applications
        $allProjects = collect();
        $competition = null;
        $firstApplication = $participant->applications->first();
        [$trackData, $subTrackData] = $this->extractTrackData($firstApplication);
        
        foreach ($participant->applications as $application) {
            if (!$competition && $application->competition) {
                $competitionTitle = $application->competition->title;
                if (is_array($competitionTitle)) {
                    $competitionTitle = $competitionTitle['en'] ?? $competitionTitle['ar'] ?? 'Unknown';
                }
                $competition = [
                    'id' => $application->competition->id,
                    'title' => $competitionTitle,
                ];
            }
            
            foreach ($application->projects as $project) {
                $allProjects->push($this->formatProjectResponse($project));
            }
        }

        return [
            'id' => $participant->id,
            'type' => 'participant',
            'name' => $name,
            'logo' => $participant->avatar ? Team::normalizeStorageUrl($participant->avatar) : null,
            'email' => $participant->email,
            'phone' => $participant->phone,
            'members_count' => 1,
            'members' => [[
                'id' => $participant->id,
                'name' => $name,
                'email' => $participant->email,
                'phone' => $participant->phone,
                'is_leader' => true,
            ]],
            'competition' => $competition,
            'track' => $trackData,
            'sub_track' => $subTrackData,
            'projects' => $allProjects,
            'assignment' => [
                'assigned_at' => $participant->pivot?->assigned_at,
                'notes' => $participant->pivot?->notes,
            ],
        ];
    }

    /**
     * Extract track and sub-track data from an individual application (if present).
     */
    private function extractTrackData($application): array
    {
        if (!$application) {
            return [null, null];
        }

        $track = null;
        $subTrack = null;

        // Track from form submissions (preferred for individual applicants)
        $trackId = $application->form_submissions['track'] ?? null;
        if ($trackId) {
            $trackModel = Track::find($trackId);
            if ($trackModel) {
                $track = [
                    'id' => $trackModel->id,
                    'name' => is_array($trackModel->name)
                        ? ($trackModel->name['en'] ?? $trackModel->name['ar'] ?? 'Unknown')
                        : $trackModel->name,
                ];
            }
        }

        $subTrackId = $application->form_submissions['sub_track'] ?? null;
        if ($subTrackId) {
            $subTrackModel = SubTrack::find($subTrackId);
            if ($subTrackModel) {
                $subTrack = [
                    'id' => $subTrackModel->id,
                    'name' => is_array($subTrackModel->name)
                        ? ($subTrackModel->name['en'] ?? $subTrackModel->name['ar'] ?? 'Unknown')
                        : $subTrackModel->name,
                ];
            }
        }

        return [$track, $subTrack];
    }

    /**
     * Format project response with comments.
     */
    private function formatProjectResponse($project): array
    {
        return [
            'id' => $project->id,
            'project_name' => $project->form_submissions['project_name'] ?? null,
            'status' => $project->status,
            'type' => $project->type,
            'total_score' => $project->total_score,
            'evaluation_status' => $project->evaluation_status,
            'created_at' => $project->created_at?->toIso8601String(),
            'comments' => $project->comments->map(function ($comment) {
                return $this->formatCommentResponse($comment);
            }),
            'comments_count' => $project->comments->count(),
        ];
    }

    /**
     * Format comment response.
     */
    private function formatCommentResponse($comment): array
    {
        $authorType = 'participant';
        if ($comment->user_id && !$comment->author_type) {
            $authorType = 'admin';
        } elseif ($comment->author_type === \App\Models\Mentor::class) {
            $authorType = 'mentor';
        }

        $attachments = collect($comment->attachments ?? [])->map(function ($path) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        })->toArray();

        $authorName = null;
        if ($comment->user) {
            $authorName = $comment->user->name;
        } elseif ($comment->author) {
            $authorName = is_array($comment->author->name)
                ? ($comment->author->name['en'] ?? $comment->author->name['ar'] ?? 'Unknown')
                : $comment->author->name;
        }

        return [
            'id' => $comment->id,
            'comment' => $comment->comment,
            'attachments' => $attachments,
            'is_read' => $comment->is_read,
            'author_type' => $authorType,
            'author' => [
                'id' => $comment->user_id ?? $comment->author_id,
                'name' => $authorName,
            ],
            'created_at' => $comment->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get all participants from assigned teams.
     */
    public function participants(Request $request): JsonResponse
    {
        try {
            $mentor = Auth::user();
            
            // Get all team IDs assigned to this mentor
            $teamIds = $mentor->teams()->pluck('teams.id');

            if ($teamIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => __('mentor.no_teams_assigned'),
                ]);
            }

            // Get all team members from these teams
            $members = \App\Models\TeamMember::whereIn('team_id', $teamIds)
                ->with(['participant', 'team.project', 'team.track'])
                ->get();

            $participantsData = $members->map(function ($member) {
                return [
                    'id' => $member->participant->id ?? null,
                    'name' => $member->participant->name ?? null,
                    'email' => $member->participant->email ?? null,
                    'phone' => $member->participant->phone ?? null,
                    'avatar' => $member->participant->avatar ?? null,
                    'is_leader' => $member->is_leader,
                    'team' => [
                        'id' => $member->team->id,
                        'name' => $member->team->name,
                    ],
                    'project_name' => $member->team->project->name ?? null,
                    'track' => $member->team->track ? [
                        'id' => $member->team->track->id,
                        'name' => $member->team->track->name,
                    ] : null,
                ];
            });

            // Search filter
            if ($request->has('search')) {
                $search = strtolower($request->input('search'));
                $participantsData = $participantsData->filter(function ($participant) use ($search) {
                    $name = is_array($participant['name']) 
                        ? strtolower(implode(' ', $participant['name']))
                        : strtolower($participant['name'] ?? '');
                    $email = strtolower($participant['email'] ?? '');
                    
                    return str_contains($name, $search) || str_contains($email, $search);
                });
            }

            return response()->json([
                'success' => true,
                'data' => $participantsData->values(),
                'total' => $participantsData->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('mentor.failed_to_load_participants'),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get summary statistics for mentor's assignments.
     */
    public function summary(): JsonResponse
    {
        try {
            $mentor = Auth::user();
            
            $totalTeams = $mentor->teams()->count();
            $totalTeamParticipants = \App\Models\TeamMember::whereIn(
                'team_id', 
                $mentor->teams()->pluck('teams.id')
            )->count();
            $totalIndividualParticipants = $mentor->participants()->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_teams' => $totalTeams,
                    'total_team_participants' => $totalTeamParticipants,
                    'total_individual_participants' => $totalIndividualParticipants,
                    'total_participants' => $totalTeamParticipants + $totalIndividualParticipants,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('mentor.failed_to_load_summary'),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get project details by ID (only for projects belonging to assigned teams or individual participants).
     */
    public function showProject(int $projectId): JsonResponse
    {
        try {
            $mentor = Auth::user();
            
            // Get all team IDs assigned to this mentor
            $teamIds = $mentor->teams()->pluck('teams.id')->toArray();
            
            // Get all participant IDs assigned to this mentor (individual participants)
            $participantIds = $mentor->participants()->pluck('participants.id')->toArray();
            
            // Get application IDs for individual participants
            $applicationIds = [];
            if (!empty($participantIds)) {
                $applicationIds = \App\Models\CompetitionApplication::whereIn('participant_id', $participantIds)
                    ->where(function ($q) {
                        $q->where('registered_as', 'individual')
                          ->orWhere('form_submissions->register_as', 'individual');
                    })
                    ->pluck('id')
                    ->toArray();
            }

            if (empty($teamIds) && empty($applicationIds)) {
                return response()->json([
                    'success' => false,
                    'message' => __('mentor.no_teams_assigned'),
                ], 404);
            }

            // Find the project - it can belong to a team OR an individual participant's application
            $project = Project::with([
                'team.members.participant',
                'team.track',
                'team.subTrack',
                'competition',
                'application.participant',
                'form',
                'comments' => function ($query) {
                    $query->with(['user', 'author'])->orderBy('created_at', 'asc');
                },
            ])
            ->where('type', '!=', 'draft')
            ->where(function ($query) use ($teamIds, $applicationIds) {
                // Project belongs to assigned team
                if (!empty($teamIds)) {
                    $query->whereIn('team_id', $teamIds);
                }
                // OR project belongs to individual participant's application
                if (!empty($applicationIds)) {
                    $query->orWhereIn('application_id', $applicationIds);
                }
            })
            ->find($projectId);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => __('mentor.project_not_found'),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new MentorProjectResource($project),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('mentor.failed_to_load_project'),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get all projects from assigned teams.
     */
    public function projects(Request $request): JsonResponse
    {
        try {
            $mentor = Auth::user();
            
            // Get all team IDs assigned to this mentor
            $teamIds = $mentor->teams()->pluck('teams.id')->toArray();
            
            // Get all participant IDs assigned to this mentor (individual participants)
            $participantIds = $mentor->participants()->pluck('participants.id')->toArray();
            
            // Get application IDs for individual participants
            $applicationIds = [];
            if (!empty($participantIds)) {
                $applicationIds = \App\Models\CompetitionApplication::whereIn('participant_id', $participantIds)
                    ->where(function ($q) {
                        $q->where('registered_as', 'individual')
                          ->orWhere('form_submissions->register_as', 'individual');
                    })
                    ->pluck('id')
                    ->toArray();
            }

            if (empty($teamIds) && empty($applicationIds)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => __('mentor.no_teams_assigned'),
                ]);
            }

            $query = Project::with([
                'team',
                'competition',
                'application.participant',
            ])
            ->where('type', '!=', 'draft')
            ->where(function ($q) use ($teamIds, $applicationIds) {
                // Project belongs to assigned team
                if (!empty($teamIds)) {
                    $q->whereIn('team_id', $teamIds);
                }
                // OR project belongs to individual participant's application
                if (!empty($applicationIds)) {
                    $q->orWhereIn('application_id', $applicationIds);
                }
            });

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            // Filter by competition
            if ($request->has('competition_id')) {
                $query->where('competition_id', $request->input('competition_id'));
            }

            // Search by project name
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where('form_submissions->project_name', 'like', "%{$search}%");
            }

            $projects = $query->orderBy('created_at', 'desc')->paginate(15);

            // Transform projects
            $projectsData = $projects->getCollection()->map(function ($project) {
                return new MentorProjectResource($project);
            });

            return response()->json([
                'success' => true,
                'data' => $projectsData,
                'pagination' => [
                    'current_page' => $projects->currentPage(),
                    'last_page' => $projects->lastPage(),
                    'per_page' => $projects->perPage(),
                    'total' => $projects->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('mentor.failed_to_load_projects'),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
