<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectComment;
use App\Models\User;
use App\Notifications\ProjectCommentAdded;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Filament\Notifications\Notification;
class ProjectCommentController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $comments = $project->comments()
            ->with(['user', 'author'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($comment) {
                // Transform author_type to readable format
                $authorType = 'participant';
                if ($comment->user_id && !$comment->author_type) {
                    $authorType = 'admin';
                }

                // Transform attachments to full URLs
                $attachments = collect($comment->attachments ?? [])->map(function ($path) {
                    return Storage::disk('public')->url($path);
                })->toArray();

                return [
                    'id' => $comment->id,
                    'project_id' => $comment->project_id,
                    'comment' => $comment->comment,
                    'attachments' => $attachments,
                    'is_read' => $comment->is_read,
                    'author_type' => $authorType,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                    'user' => $comment->user ? [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                    ] : null,
                    'author' => $comment->author ? [
                        'id' => $comment->author->id,
                        'name' => $comment->author->name,
                    ] : null,
                ];
            });

        return response()->json($comments);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        // Prevent comments on pending projects
//        if ($project->isPending()) {
//            return response()->json(['message' => 'Comments are not allowed on pending projects'], 403);
//        }

        // Prevent comments on projects with final statuses (qualified, not_qualified, winner)
        if ($project->isQualified() || $project->isNotQualified() || $project->isWinner()) {
            return response()->json(['message' => 'Comments are not allowed on evaluated projects'], 403);
        }

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:500'],
            'attachments' => ['nullable'],
            'attachments.*' => [
                'file',
                'max:5120',
                'mimes:pdf,doc,docx,png,jpg,jpeg'
            ],
        ]);

        $paths = [];
        $uploaded = $request->file('attachments');
        if ($uploaded) {
            $files = is_array($uploaded) ? $uploaded : [$uploaded];
            foreach ($files as $file) {
                if ($file) {
                    $paths[] = $file->store('project-comments', 'public');
                }
            }
        }

        $comment = $project->comments()->create([
            'comment' => $validated['comment'],
            'attachments' => $paths,
            'is_read' => false,
            'author_id' => auth()->id(),
            'author_type' => get_class(auth()->user()),
        ]);

        // Notify appropriate users based on comment author
        try {
            if ($comment->user_id && !$comment->author_type) {
                // This is an admin comment, notify the participant
                $team = $project->team;
                $leaderMember = $team?->members()->where('is_leader', true)->first();
                $participant = $leaderMember?->participant ?? $project->application?->participant;
                if ($participant) {
                    $participant->notify(new ProjectCommentAdded($comment));
                }
            } else {
                // This is a participant comment, notify admins assigned to this program
                $competition = $project->competition ?? $project->application?->competition;
                
                if ($competition) {
                    // Get admins assigned to this competition
                    $assignedAdminIds = \App\Models\UserCompetition::where('competition_id', $competition->id)
                        ->pluck('user_id');
                    
                    // Get super admins (they should always be notified)
                    $superAdminIds = \App\Models\User::role('super-admin')
                        ->where('is_archived', false)
                        ->pluck('id');
                    
                    // Combine and get unique admin IDs
                    $adminIds = $assignedAdminIds->merge($superAdminIds)->unique();
                    
                    // Get all admins to notify
                    $adminsToNotify = \App\Models\User::whereIn('id', $adminIds)
                        ->where('is_archived', false)
                        ->get();
                } else {
                    // Fallback: if no competition found, use permission-based approach
                    $adminsToNotify = \App\Models\User::permission('create ProjectComment')
                        ->where('is_archived', false)
                        ->get();
                }

                foreach ($adminsToNotify as $admin) {
                    // Send email notification
                    $admin->notify(new ProjectCommentAdded($comment));
                    
                    // Send Filament database notification for admin panel
                    $admin->notify(
                        Notification::make()
                            ->title('New project comment by ' . $comment->author?->name)
                            ->body('A new project comment has been added to the project "' . $project->form_submissions['project_name'] . '" Comment content: "' . $comment->comment . '"')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->color('success')
                            ->toDatabase(),
                    );
                }
            }
        } catch (\Throwable $e) {
            // Failed to notify admins about project comment
        }

        // Return formatted response like index method
        $freshComment = $comment->fresh(['user', 'author']);

        // Transform author_type to readable format
        $authorType = 'participant';
        if ($freshComment->user_id && !$freshComment->author_type) {
            $authorType = 'admin';
        }

        // Transform attachments to full URLs
        $attachments = collect($freshComment->attachments ?? [])->map(function ($path) {
            return Storage::disk('public')->url($path);
        })->toArray();

        $formattedComment = [
            'id' => $freshComment->id,
            'project_id' => $freshComment->project_id,
            'comment' => $freshComment->comment,
            'attachments' => $attachments,
            'is_read' => $freshComment->is_read,
            'author_type' => $authorType,
            'created_at' => $freshComment->created_at,
            'updated_at' => $freshComment->updated_at,
            'user' => $freshComment->user ? [
                'id' => $freshComment->user->id,
                'name' => $freshComment->user->name,
            ] : null,
            'author' => $freshComment->author ? [
                'id' => $freshComment->author->id,
                'name' => $freshComment->author->name,
            ] : null,
        ];

        return response()->json($formattedComment);
    }

    public function markRead(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $project->comments()
            ->where('user_id', '!=', null)
            ->whereNull('author_type')
            ->update(['is_read' => true]);

        return response()->json(['status' => 'ok']);
    }


    private function authorizeProject(Project $project): void
    {
        // Check if participant is archived
        $participant = auth()->user();
        if ($participant && method_exists($participant, 'isArchived') && $participant->isArchived()) {
            abort(401, 'Account has been archived');
        }

        // Prevent access to archived projects
        if ($project->isArchived()) {
            abort(404, 'Project not found');
        }

        $application = $project->application;

        $isOwner = (bool) ($application && $application->participant_id === auth()->id());
        $isTeamMember = (bool) ($application && $application->team?->members()
            ->where('participant_id', auth()->id())
            ->exists());

        abort_unless($isOwner || $isTeamMember, 403);
    }
}


