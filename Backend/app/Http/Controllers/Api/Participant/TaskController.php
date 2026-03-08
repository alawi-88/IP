<?php

namespace App\Http\Controllers\Api\Participant;

use App\Http\Controllers\Controller;
use App\Models\TaskAssignment;
use App\Models\TaskComment;
use App\Models\TaskSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * IN-2040: Task Inbox - List all tasks assigned to the participant
     * IN-2050: Filter & Search Tasks
     */
    public function index(Request $request): JsonResponse
    {
        $participant = $request->user();
        $competitionId = $request->query('competition_id');

        $query = TaskAssignment::query()
            ->where('is_archived', false)
            ->where(function ($q) use ($participant) {
                $q->where('participant_id', $participant->id)
                  ->orWhere('assignment_type', 'all')
                  ->orWhereHas('team', function ($tq) use ($participant) {
                      $tq->whereHas('members', function ($mq) use ($participant) {
                          $mq->where('participant_id', $participant->id);
                      });
                  });
            })
            ->with(['competition:id,title', 'stage:id,title', 'template:id,title', 'team:id,name', 'latestSubmission']);

        if ($competitionId) {
            $query->where('competition_id', $competitionId);
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title->en', 'like', "%{$search}%")
                  ->orWhere('title->ar', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'due_date', 'status', 'title'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $tasks = $query->paginate($request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    /**
     * IN-2042: View Task Details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $participant = $request->user();

        $task = TaskAssignment::with([
            'competition:id,title',
            'stage:id,title',
            'template:id,title,form_id',
            'team:id,name',
            'submissions' => function ($q) {
                $q->orderBy('version', 'desc');
            },
            'submissions.submittedByParticipant:id,name',
            'comments' => function ($q) {
                $q->where('is_internal', false)->orderBy('created_at', 'desc');
            },
            'assignedByUser:id,name',
        ])->find($id);

        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        // Authorization: check participant has access
        if (!$this->canAccessTask($task, $participant)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Mark as in_progress if not_started
        if ($task->status === TaskAssignment::STATUS_NOT_STARTED) {
            $task->update(['status' => TaskAssignment::STATUS_IN_PROGRESS]);
        }

        return response()->json([
            'success' => true,
            'data' => $task,
        ]);
    }

    /**
     * IN-2044: Track Task Status
     */
    public function status(Request $request, int $id): JsonResponse
    {
        $participant = $request->user();
        $task = TaskAssignment::with(['latestSubmission'])->find($id);

        if (!$task || !$this->canAccessTask($task, $participant)) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $task->id,
                'status' => $task->status,
                'status_color' => $task->status_color,
                'is_overdue' => $task->due_date ? $task->isOverdue() : false,
                'due_date' => $task->due_date?->toISOString(),
                'submitted_at' => $task->submitted_at?->toISOString(),
                'reviewed_at' => $task->reviewed_at?->toISOString(),
                'can_submit' => $task->canSubmit(),
                'latest_submission_version' => $task->latestSubmission?->version,
                'latest_submission_status' => $task->latestSubmission?->status,
                'latest_feedback' => $task->latestSubmission?->admin_feedback,
            ],
        ]);
    }

    /**
     * IN-2045: Submit Task Deliverables
     * IN-2046: Respond to Revision Requests
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $participant = $request->user();
        $task = TaskAssignment::find($id);

        if (!$task || !$this->canAccessTask($task, $participant)) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        if (!$task->canSubmit()) {
            return response()->json([
                'success' => false,
                'message' => 'Task cannot be submitted in its current status.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:5000',
            'form_submissions' => 'nullable|array',
            'files' => 'nullable|array',
            'files.*' => 'file|max:' . (($task->max_file_size_mb ?? 10) * 1024),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate file formats if restricted
        if ($task->allowed_file_formats && $request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $ext = strtolower($file->getClientOriginalExtension());
                if (!in_array($ext, $task->allowed_file_formats)) {
                    return response()->json([
                        'success' => false,
                        'message' => "File format .{$ext} is not allowed. Allowed: " . implode(', ', $task->allowed_file_formats),
                    ], 422);
                }
            }
        }

        // Determine version
        $latestVersion = TaskSubmission::where('task_assignment_id', $task->id)->max('version') ?? 0;
        $newVersion = $latestVersion + 1;

        // Handle file uploads
        $uploadedFiles = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store("task_submissions/{$task->id}", 'public');
                $uploadedFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
        }

        $submission = TaskSubmission::create([
            'task_assignment_id' => $task->id,
            'version' => $newVersion,
            'submitted_by' => $participant->id,
            'form_submissions' => $request->input('form_submissions'),
            'files' => $uploadedFiles,
            'notes' => $request->input('notes'),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // Mark task as submitted
        $task->markAsSubmitted();

        return response()->json([
            'success' => true,
            'message' => 'Task submitted successfully',
            'data' => $submission,
        ], 201);
    }

    /**
     * IN-2047: View Task History & Comments
     */
    public function comments(Request $request, int $id): JsonResponse
    {
        $participant = $request->user();
        $task = TaskAssignment::find($id);

        if (!$task || !$this->canAccessTask($task, $participant)) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        $comments = $task->comments()
            ->where('is_internal', false)
            ->with('commentable')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $comments,
        ]);
    }

    /**
     * IN-2047: Add comment to task
     */
    public function addComment(Request $request, int $id): JsonResponse
    {
        $participant = $request->user();
        $task = TaskAssignment::find($id);

        if (!$task || !$this->canAccessTask($task, $participant)) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $comment = TaskComment::create([
            'task_assignment_id' => $task->id,
            'commentable_type' => 'App\\Models\\Participant',
            'commentable_id' => $participant->id,
            'body' => $request->input('comment'),
            'is_internal' => false,
        ]);

        return response()->json([
            'success' => true,
            'data' => $comment,
        ], 201);
    }

    private function canAccessTask(TaskAssignment $task, $participant): bool
    {
        // Direct assignment to participant
        if ($task->participant_id === $participant->id) return true;

        // Assigned to all
        if ($task->assignment_type === 'all') {
            // Check participant is in this competition
            return $participant->competitionApplications()
                ->where('competition_id', $task->competition_id)
                ->where('status', 'approved')
                ->exists();
        }

        // Team assignment
        if ($task->assignment_type === 'team' && $task->team_id) {
            return $task->team->members()
                ->where('participant_id', $participant->id)
                ->exists();
        }

        return false;
    }
}
