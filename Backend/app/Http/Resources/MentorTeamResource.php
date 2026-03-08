<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MentorTeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo ? Storage::url(ltrim(str_replace(Storage::url('/'), '', $this->logo), '/')) : null,
            
            // Assignment details from pivot
            'assignment' => [
                'assigned_at' => $this->pivot?->assigned_at,
                'notes' => $this->pivot?->notes,
            ],
            
            // Team members/participants
            'members' => $this->whenLoaded('members', function () {
                return $this->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'is_leader' => $member->is_leader,
                        'participant' => [
                            'id' => $member->participant?->id,
                            'name' => $member->participant?->name,
                            'email' => $member->participant?->email,
                            'phone' => $member->participant?->phone,
                            'avatar' => $member->participant?->avatar 
                                ? Storage::url($member->participant->avatar) 
                                : null,
                        ],
                    ];
                });
            }),
            
            // Team leader quick access
            'team_leader' => $this->whenLoaded('members', function () {
                $leader = $this->members->where('is_leader', true)->first();
                if ($leader && $leader->participant) {
                    return [
                        'id' => $leader->participant->id,
                        'name' => $leader->participant->name,
                        'email' => $leader->participant->email,
                        'phone' => $leader->participant->phone,
                    ];
                }
                return null;
            }),
            
            // All projects (excluding drafts)
            'projects' => $this->whenLoaded('projects', function () {
                return $this->projects->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'project_name' => $project->form_submissions['project_name'] ?? null,
                        'status' => $project->status,
                        'type' => $project->type,
                        'total_score' => $project->total_score,
                        'evaluation_status' => $project->evaluation_status,
                        'created_at' => $project->created_at?->toIso8601String(),
                        'comments' => $project->relationLoaded('comments') 
                            ? $project->comments->map(function ($comment) {
                                return $this->formatComment($comment);
                            })
                            : [],
                        'comments_count' => $project->relationLoaded('comments') 
                            ? $project->comments->count() 
                            : 0,
                    ];
                });
            }),
            
            'projects_count' => $this->whenLoaded('projects', fn() => $this->projects->count()),
            
            // Track information
            'track' => $this->whenLoaded('track', function () {
                if (!$this->track) {
                    return null;
                }
                return [
                    'id' => $this->track->id,
                    'name' => $this->track->name,
                ];
            }),
            
            // Sub-track information
            'sub_track' => $this->whenLoaded('subTrack', function () {
                if (!$this->subTrack) {
                    return null;
                }
                return [
                    'id' => $this->subTrack->id,
                    'name' => $this->subTrack->name,
                ];
            }),
            'application' => $this->whenLoaded('application', function () {
                if (!$this->application) {
                    return null;
                }
                return [
                    'id' => $this->application->id,
                    'title' => $this->application->title,
                    'track' => [
                        'id' => $this->application->track->id,
                        'name' => $this->application->track->name,
                    ],
                    'sub_track' => [
                        'id' => $this->application->subTrack->id,
                        'name' => $this->application->subTrack->name,
                    ],
                ];
            }),
            
            // Program/Program information
            'program' => $this->whenLoaded('application', function () {
                if (!$this->application || !$this->application->program) {
                    return null;
                }
                return [
                    'id' => $this->application->program->id,
                    'title' => $this->application->program->title,
                ];
            }),
            
            // Team details
            'idea_description' => $this->idea_description,
            'contact_email' => $this->contact_email,
            'skills' => $this->skills,
            'previous_participation' => $this->previous_participation,
            'is_published' => $this->is_published,
            'is_completed' => $this->is_completed,
            
            // Statistics
            'members_count' => $this->whenLoaded('members', fn() => $this->members->count()),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Format a comment for API response.
     */
    protected function formatComment($comment): array
    {
        // Determine author type
        $authorType = 'participant';
        if ($comment->user_id && !$comment->author_type) {
            $authorType = 'admin';
        } elseif ($comment->author_type === \App\Models\Mentor::class) {
            $authorType = 'mentor';
        }

        // Transform attachments to full URLs
        $attachments = collect($comment->attachments ?? [])->map(function ($path) {
            return Storage::disk('public')->url($path);
        })->toArray();

        // Get author name
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
}

