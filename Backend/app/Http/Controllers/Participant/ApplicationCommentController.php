<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\ProgramApplication;
use App\Models\ApplicationComment;
use App\Models\User;
use App\Notifications\ParticipantApplicationReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

use Filament\Notifications\Notification;

class ApplicationCommentController extends Controller
{
    public function index(ProgramApplication $application): JsonResponse
    {
        $this->authorizeApplication($application);

        $comments = $application->comments()
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

    public function store(Request $request, ProgramApplication $application): JsonResponse
    {
        $this->authorizeApplication($application);

        // Lock thread if application is processed
        if (!in_array($application->status, ['pending'])) {
            return response()->json(['message' => 'Thread is locked for processed applications'], 403);
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
                    $paths[] = $file->store('application-comments', 'public');
                }
            }
        }

        $comment = $application->comments()->create([
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
                $participant = $application->participant;
                if ($participant) {
                    $participant->notify(new ParticipantApplicationReply($comment));
                }
            } else {
                // This is a participant comment, notify admins assigned to this program
                $program = $application->program;
                
                if ($program) {
                    // Get admins assigned to this program
                    $assignedAdminIds = \App\Models\UserProgram::where('program_id', $program->id)
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
                    // Fallback: if no program found, use permission-based approach
                    $adminsToNotify = \App\Models\User::permission('create ApplicationComment')
                        ->where('is_archived', false)
                        ->get();
                }

                foreach ($adminsToNotify as $admin) {
                    // Send email notification
                    $admin->notify(new ParticipantApplicationReply($comment));
                    
                    // Send Filament database notification for admin panel
                    $programName = $application->program->title ?? 'Program';
                    $participantName = $comment->author?->name ?? __('A participant');
                    $admin->notify(
                        Notification::make()
                            ->title('New reply on application: ' . $programName)
                            ->body('"' . $participantName . '" replied to your comment on their application: "' . $comment->comment . '"')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->color('success')
                            ->toDatabase(),
                    );
                }
            }
        } catch (\Throwable $e) {
            // Failed to notify admins about application comment
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
            'application_id' => $freshComment->application_id,
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

    public function markRead(ProgramApplication $application): JsonResponse
    {
        $this->authorizeApplication($application);

        $application->comments()->whereNull('author_type') // authored by admin
            ->update(['is_read' => true]);

        return response()->json(['status' => 'ok']);
    }


    private function authorizeApplication(ProgramApplication $application): void
    {
        // Check if participant is archived
        $participant = auth()->user();
        if ($participant && method_exists($participant, 'isArchived') && $participant->isArchived()) {
            abort(401, 'Account has been archived');
        }

        abort_unless($application->participant_id === auth()->id(), 403);
    }
}
