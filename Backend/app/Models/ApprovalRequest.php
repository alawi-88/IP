<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Events\ApprovalRequestStatusChanged;
use App\Models\CommitteeJudge;
use App\Models\JudgeProject;
use App\Models\FormEvaluationScore;
use App\Models\ProjectComment;
use App\Notifications\ProjectCommentAdded;

class ApprovalRequest extends Model
{
    use LogsActivity;

    protected $fillable = [
        'action',
        'status',
        'requested_by',
        'approval_workflow_id',
        'action_data',
        'reason',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'target_type',
        'target_id',
        'executed_at',
    ];

    protected $casts = [
        'action_data' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['action', 'status', 'requested_by', 'approval_workflow_id', 'reason', 'rejection_reason'])
            ->logOnlyDirty()
            ->useLogName('approval_request')
            ->setDescriptionForEvent(fn(string $eventName) => "ApprovalRequest was {$eventName}");
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvalWorkflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class);
    }

    public function approvalRequestLevels(): HasMany
    {
        return $this->hasMany(ApprovalRequestLevel::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(ApprovalRequestNotification::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ApprovalRequestComment::class);
    }

    /**
     * Computed timeline used by Filament infolist (History Timeline section).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHistoryTimelineAttribute(): array
    {
        $timeline = [];

        // Comments
        foreach ($this->comments()->with('user')->orderBy('created_at', 'asc')->get() as $comment) {
            $commentText = trim((string) ($comment->comment ?? ''));

            $timeline[] = [
                'type' => 'comment',
                'timestamp_raw' => $comment->created_at,
                'timestamp' => $comment->created_at?->format('M d, Y H:i') ?? 'No date / لا يوجد تاريخ',
                // Keep a nested user array so Filament can use "user.name" like other repeatables in this codebase
                'user' => [
                    'name' => $comment->user?->name ?? 'Unknown',
                ],
                'action' => $comment->is_internal ? 'Internal Comment / تعليق داخلي' : 'Comment / تعليق',
                'description' => $commentText !== '' ? $commentText : 'No description / لا يوجد وصف',
            ];
        }

        // Activity log
        $activities = Activity::query()
            ->where('subject_type', self::class)
            ->where('subject_id', $this->id)
            ->with('causer')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($activities as $activity) {
            $desc = trim($this->formatActivityDescriptionForTimeline($activity));

            $timeline[] = [
                'type' => 'action',
                'timestamp_raw' => $activity->created_at,
                'timestamp' => $activity->created_at?->format('M d, Y H:i') ?? 'No date / لا يوجد تاريخ',
                'user' => [
                    'name' => $activity->causer?->name ?? 'System',
                ],
                'action' => $activity->description ?? 'Action / إجراء',
                'description' => $desc !== '' ? $desc : 'No description / لا يوجد وصف',
            ];
        }

        usort($timeline, function (array $a, array $b) {
            $aTime = $a['timestamp_raw'] ?? null;
            $bTime = $b['timestamp_raw'] ?? null;

            if (!$aTime && !$bTime) {
                return 0;
            }
            if (!$aTime) {
                return 1;
            }
            if (!$bTime) {
                return -1;
            }

            return $aTime <=> $bTime;
        });

        // Remove raw timestamps before returning (keep payload clean for UI)
        return array_map(function (array $item) {
            unset($item['timestamp_raw']);
            return $item;
        }, $timeline);
    }

    protected function formatActivityDescriptionForTimeline(Activity $activity): string
    {
        $description = (string) ($activity->description ?? '');
        $properties = $activity->properties ?? [];

        if (is_object($properties) && method_exists($properties, 'toArray')) {
            $properties = $properties->toArray();
        }

        if (is_array($properties) && !empty($properties['attributes'])) {
            $changes = [];

            foreach ((array) $properties['attributes'] as $key => $value) {
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

        return $description;
    }

    /**
     * Get the target model (polymorphic relationship)
     */
    public function target()
    {
        return $this->morphTo();
    }

    /**
     * Get the program if this is a program approval request
     */
    public function program()
    {
        return $this->belongsTo(Competition::class, 'target_id');
    }

    /**
     * Get the application if this is an application approval request
     */
    public function application()
    {
        return $this->belongsTo(CompetitionApplication::class, 'target_id');
    }

    /**
     * Get the project if this is a project approval request
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'target_id');
    }

    /**
     * Get the form if this is a form approval request
     */
    public function form()
    {
        return $this->belongsTo(Form::class, 'target_id');
    }

    /**
     * Get the winner if this is a winner approval request
     */
    public function winner()
    {
        return $this->belongsTo(Winner::class, 'target_id');
    }

    /**
     * Get the program model if this is a program approval request
     */
    public function getProgram()
    {
        if ($this->target_type === Competition::class && $this->target_id) {
            return $this->program;
        }
        return null;
    }

    /**
     * Get the application model if this is an application approval request
     */
    public function getApplication()
    {
        if ($this->target_type === CompetitionApplication::class && $this->target_id) {
            return $this->application;
        }
        return null;
    }

    /**
     * Check if this is a program approval request
     */
    public function isProgramRequest(): bool
    {
        return $this->target_type === Competition::class;
    }

    /**
     * Check if this is an application approval request
     */
    public function isApplicationRequest(): bool
    {
        return $this->target_type === CompetitionApplication::class;
    }

    /**
     * Check if this is a project approval request
     */
    public function isProjectRequest(): bool
    {
        return $this->target_type === Project::class;
    }

    /**
     * Check if this is a winner approval request
     */
    public function isWinnerRequest(): bool
    {
        return $this->target_type === Winner::class;
    }

    /**
     * Check if this is a form approval request
     */
    public function isFormRequest(): bool
    {
        return $this->target_type === Form::class;
    }

    /**
     * Get the action type for program/project/form/application requests
     */
    public function getActionType(): string
    {
        if ($this->isProgramRequest() || $this->isProjectRequest() || $this->isFormRequest() || $this->isApplicationRequest()) {
            return $this->action_data['action_type'] ?? 'unknown';
        }
        return $this->action;
    }

    /**
     * Execute the approved action
     */
    public function executeAction(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        try {
            if ($this->isProgramRequest()) {
                return $this->executeProgramAction();
            }
            
            if ($this->isApplicationRequest()) {
                return $this->executeApplicationAction();
            }
            
            if ($this->isProjectRequest()) {
                return $this->executeProjectAction();
            }
            
            if ($this->isFormRequest()) {
                return $this->executeFormAction();
            }

            if ($this->isWinnerRequest()) {
                return $this->executeWinnerAction();
            }
            
            // Handle other types of approval requests here
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to execute approval action: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute winner-specific actions
     */
    private function executeWinnerAction(): bool
    {
        $actionType = $this->action_data['action_type'] ?? null;
        $actionData = $this->action_data ?? [];

        // Remove meta fields that should never be persisted
        unset($actionData['action_type']);
        unset($actionData['old_values']);

        switch ($actionType) {
            case 'create':
                $winner = Winner::create($actionData);
                $this->update(['target_id' => $winner->id]);
                return true;

            case 'update':
                if (!$this->target_id) {
                    \Log::error("Winner target_id missing for approval request ID: {$this->id}");
                    return false;
                }
                $winner = Winner::findOrFail($this->target_id);
                return $winner->update($actionData);

            case 'delete':
                if (!$this->target_id) {
                    \Log::error("Winner target_id missing for approval request ID: {$this->id}");
                    return false;
                }
                return Winner::findOrFail($this->target_id)->delete();

            case 'toggle_visibility':
                if (!$this->target_id) {
                    \Log::error("Winner target_id missing for approval request ID: {$this->id}");
                    return false;
                }
                $winner = Winner::findOrFail($this->target_id);
                if (array_key_exists('is_visible', $actionData)) {
                    return $winner->update(['is_visible' => (bool) $actionData['is_visible']]);
                }
                return $winner->update(['is_visible' => !$winner->is_visible]);

            default:
                \Log::warning("Unknown winner approval action_type '{$actionType}' for request ID: {$this->id}");
                return false;
        }
    }

    /**
     * Execute program-specific actions
     */
    private function executeProgramAction(): bool
    {
        $actionType = $this->getActionType();
        $actionData = $this->action_data;
        
        switch ($actionType) {
            case 'create':
                return $this->executeProgramCreateAction($actionData);
            case 'update':
                return $this->executeProgramUpdateAction($actionData);
            case 'delete':
                return $this->executeProgramDeleteAction();
            case 'archive':
                return $this->executeProgramArchiveAction();
            default:
                return false;
        }
    }

    private function executeProgramCreateAction(array $actionData): bool
    {
        unset($actionData['action_type'], $actionData['competition_id'], $actionData['old_values']);
        try {
            $program = Competition::create($actionData);
            

            if ($this->requested_by) {
                $program->users()->syncWithoutDetaching([$this->requested_by]);
            }

            $this->update([
                'target_type' => Competition::class,
                'target_id' => $program->id,
            ]);

            return true;
        } catch (\Throwable $e) {
            \Log::error('Failed to execute program create action: ' . $e->getMessage(), [
                'approval_request_id' => $this->id,
            ]);
            return false;
        }
    }

    private function executeProgramUpdateAction(array $actionData): bool
    {
        $program = $this->program;
        if (!$program) {
            \Log::error("Program not found for approval request ID: {$this->id}");
            return false;
        }

        unset($actionData['action_type'], $actionData['competition_id'], $actionData['old_values']);

        return $program->update($actionData);
    }

    private function executeProgramDeleteAction(): bool
    {
        $program = $this->program;
        if (!$program) {
            \Log::error("Program not found for approval request ID: {$this->id}");
            return false;
        }
        
        return $program->delete();
    }

    private function executeProgramArchiveAction(): bool
    {
        $program = $this->program;
        if (!$program) {
            \Log::error("Program not found for approval request ID: {$this->id}");
            return false;
        }
        
        return $program->update(['is_archived' => true]);
    }

    /**
     * Execute application-specific actions
     */
    private function executeApplicationAction(): bool
    {
        $actionType = $this->getActionType();
        $actionData = $this->action_data;
        
        switch ($actionType) {
            case 'create':
                return $this->executeApplicationCreateAction($actionData);
            case 'update':
                return $this->executeApplicationUpdateAction($actionData);
            case 'delete':
                return $this->executeApplicationDeleteAction();
            case 'archive':
                return $this->executeApplicationArchiveAction();
            default:
                return false;
        }
    }

    private function executeApplicationCreateAction(array $actionData): bool
    {
        $application = CompetitionApplication::create($actionData);
        $this->update(['target_id' => $application->id]);
        return true;
    }

    private function executeApplicationUpdateAction(array $actionData): bool
    {
        $application = $this->application;
        if (!$application) {
            \Log::error("Application not found for approval request ID: {$this->id}");
            return false;
        }
        
        unset($actionData['action_type']);
        unset($actionData['competition_id'], $actionData['old_values']);
        
        $result = true;

        if (array_key_exists('status', $actionData)) {
            $status = (string) $actionData['status'];
            unset($actionData['status']);
            
            if ($status === 'approved') {
                $application->approve();
            } elseif ($status === 'rejected') {
                $application->reject();
            } else {
                $application->update(['status' => $status]);
            }
        } else {
            if ($application->isPending()) {
                $application->approve();
            }
        }

        if (!empty($actionData)) {
            $result = $application->update($actionData);
        }
        
        if ($result && $this->requestedBy) {
            $this->requestedBy->notify(
                new \App\Notifications\ApprovalRequestStatusChanged($this, 'pending', 'approved')
            );
        }
        
        return $result;
    }

    private function executeApplicationDeleteAction(): bool
    {
        return $this->application->delete();
    }

    private function executeApplicationArchiveAction(): bool
    {
        $application = $this->application;
        if (!$application) {
            \Log::error("Application not found for approval request ID: {$this->id}");
            return false;
        }
        
        $result = $application->archive();
    
        if ($result && $this->requestedBy) {
            $this->requestedBy->notify(
                new \App\Notifications\ApprovalRequestStatusChanged($this, 'pending', 'approved')
            );
        }
        
        return $result;
    }

    /**
     * Execute project-specific actions
     */
    private function executeProjectAction(): bool
    {
        $actionType = $this->getActionType();
        $actionData = $this->action_data;
        
        switch ($actionType) {
            case 'update':
                return $this->executeProjectUpdateAction($actionData);
            case 'delete':
                return $this->executeProjectDeleteAction();
            case 'archive':
                return $this->executeProjectArchiveAction();
            case 'restore':
                return $this->executeProjectRestoreAction();
            default:
                return false;
        }
    }

    private function executeProjectUpdateAction(array $actionData): bool
    {
        $project = $this->project;
        if (!$project) {
            \Log::error("Project not found for approval request ID: {$this->id}");
            return false;
        }
        
        // Remove action_type from data as it's not part of the model data
        unset($actionData['action_type']);
        unset($actionData['project_id'], $actionData['project_name'], $actionData['title'], $actionData['old_values']);
        
        $result = true;

        // Special handling for operations that are not simple column updates.
        // These are used by Filament bulk actions and must be applied after approval.
        if (array_key_exists('judge_ids', $actionData)) {
            $judgeIds = is_array($actionData['judge_ids']) ? $actionData['judge_ids'] : [];
            unset($actionData['judge_ids']);

            $project->assignToJudges($judgeIds);
        }

        if (array_key_exists('delete_evaluations', $actionData)) {
            $items = is_array($actionData['delete_evaluations']) ? $actionData['delete_evaluations'] : [];
            unset($actionData['delete_evaluations']);

            foreach ($items as $item) {
                $scoreId = data_get($item, 'form_evaluation_score_id') ?? data_get($item, 'id');
                if (!$scoreId) {
                    continue;
                }

                $score = FormEvaluationScore::find($scoreId);
                if (!$score) {
                    continue;
                }

                // Prefer archiving over hard delete to match the domain model.
                $judgeProject = $score->judgeProject;
                $score->archive();

                if ($judgeProject) {
                    $judgeProject->update(['evaluation_score' => 0]);
                }
            }
        }

        if (array_key_exists('comment_create', $actionData)) {
            $payload = is_array($actionData['comment_create']) ? $actionData['comment_create'] : [];
            unset($actionData['comment_create']);

            $commentText = $payload['comment'] ?? null;
            $attachments = $payload['attachments'] ?? [];

            /** @var ProjectComment $comment */
            $comment = $project->comments()->create([
                'user_id' => $this->requested_by,
                'comment' => $commentText,
                'attachments' => $attachments,
                'is_read' => false,
            ]);

            // Notify participant (best-effort, mirrors UI behavior)
            try {
                $team = $project->team;
                $leaderMember = $team?->members()->where('is_leader', true)->first();
                $participant = $leaderMember?->participant ?? $project->application?->participant;
                if ($participant) {
                    $participant->notify(new ProjectCommentAdded($comment));
                }
            } catch (\Throwable $e) {
                \Log::error('Failed to send ProjectCommentAdded notification', [
                    'project_id' => $project->id,
                    'comment_id' => $comment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (array_key_exists('comment_update', $actionData)) {
            $payload = is_array($actionData['comment_update']) ? $actionData['comment_update'] : [];
            unset($actionData['comment_update']);

            $commentId = $payload['comment_id'] ?? null;
            if ($commentId) {
                $comment = ProjectComment::where('project_id', $project->id)->where('id', $commentId)->first();
                if ($comment) {
                    $updates = [];
                    if (array_key_exists('comment', $payload)) {
                        $updates['comment'] = $payload['comment'];
                    }
                    if (array_key_exists('attachments', $payload)) {
                        $updates['attachments'] = $payload['attachments'];
                    }
                    if (!empty($updates)) {
                        $comment->update($updates);
                    }
                }
            }
        }

        if (array_key_exists('comment_mark_read', $actionData)) {
            $payload = is_array($actionData['comment_mark_read']) ? $actionData['comment_mark_read'] : [];
            unset($actionData['comment_mark_read']);

            $commentId = $payload['comment_id'] ?? null;
            if ($commentId) {
                $comment = ProjectComment::where('project_id', $project->id)->where('id', $commentId)->first();
                if ($comment) {
                    $comment->update(['is_read' => true]);
                }
            }
        }

        if (array_key_exists('committee_id', $actionData)) {
            $committeeId = $actionData['committee_id'];
            unset($actionData['committee_id']);

            if ($committeeId) {
                $judges = CommitteeJudge::where('committee_id', $committeeId)->pluck('judge_id');
                foreach ($judges as $judgeId) {
                    JudgeProject::updateOrCreate(
                        ['project_id' => $project->id, 'judge_id' => $judgeId],
                        ['judge_id' => $judgeId]
                    );
                }
            }
        }

        if (array_key_exists('status', $actionData)) {
            $project->setStatusAs((string) $actionData['status']);
            unset($actionData['status']);
        }

        if (array_key_exists('evaluation_status', $actionData)) {
            $project->setEvaluationStatusAs((bool) $actionData['evaluation_status']);
            unset($actionData['evaluation_status']);
        }

        // Any remaining keys are treated as normal model updates.
        if (!empty($actionData)) {
            $result = $project->update($actionData);
        }
        
        return $result;
    }

    private function executeProjectDeleteAction(): bool
    {
        $project = $this->project;
        if (!$project) {
            \Log::error("Project not found for approval request ID: {$this->id}");
            return false;
        }
        
        $result = $project->delete();
        
        return $result;
    }

    private function executeProjectArchiveAction(): bool
    {
        $project = $this->project;
        if (!$project) {
            \Log::error("Project not found for approval request ID: {$this->id}");
            return false;
        }
        
        $result = $project->archive();
        
        return $result;
    }

    private function executeProjectRestoreAction(): bool
    {
        $project = $this->project;
        if (!$project) {
            \Log::error("Project not found for approval request ID: {$this->id}");
            return false;
        }

        $result = $project->restore();

        return $result;
    }

    /**
     * Execute form-specific actions
     */
    private function executeFormAction(): bool
    {
        $actionType = $this->getActionType();
        $actionData = $this->action_data;
        
        switch ($actionType) {
            case 'create':
                return $this->executeFormCreateAction($actionData);
            case 'update':
                return $this->executeFormUpdateAction($actionData);
            case 'delete':
                return $this->executeFormDeleteAction();
            case 'archive':
                return $this->executeFormArchiveAction();
            default:
                return false;
        }
    }

    private function executeFormCreateAction(array $actionData): bool
    {
        // Remove action_type from data as it's not part of the model data
        unset($actionData['action_type']);
        
        // Extract fields if they exist (they will be saved separately)
        $fields = $actionData['fields'] ?? null;
        unset($actionData['fields']);
        
        // Extract evaluation_criteria and other evaluation config if they exist
        $evaluationCriteria = $actionData['evaluation_criteria'] ?? null;
        unset($actionData['evaluation_criteria']);
        
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            // Create the form
            $form = Form::create($actionData);
            
            if (!$form) {
                \Illuminate\Support\Facades\DB::rollBack();
                return false;
            }
            
            // Create form fields if they exist
            if ($fields && is_array($fields)) {
                foreach ($fields as $fieldData) {
                    // Ensure form_id is set
                    $fieldData['form_id'] = $form->id;
                    
                    // Create the field
                    \App\Models\FormField::create($fieldData);
                }
            }
            
            // Update the approval request with the created form ID
            $this->update(['target_id' => $form->id]);
            
            \Illuminate\Support\Facades\DB::commit();
            
            // Fire the event that would normally be fired when creating a form
            event(new \App\Events\FormCompetitionStagesCreated($form));
            
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Log::error('Failed to execute form create action: ' . $e->getMessage());
            return false;
        }
    }

    private function executeFormUpdateAction(array $actionData): bool
    {
        $form = $this->form;
        if (!$form) {
            \Log::error("Form not found for approval request ID: {$this->id}");
            return false;
        }
        
        // Remove action_type from data as it's not part of the model data
        unset($actionData['action_type']);
        
        // Extract fields if they exist (they will be saved separately)
        $fields = $actionData['fields'] ?? null;
        unset($actionData['fields']);
        
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            // Update the form
            $result = $form->update($actionData);
            
            if (!$result) {
                \Illuminate\Support\Facades\DB::rollBack();
                return false;
            }
            
            // Update form fields if they exist
            if ($fields && is_array($fields)) {
                // Delete existing fields first
                $form->fields()->delete();
                
                // Create new fields
                foreach ($fields as $fieldData) {
                    // Ensure form_id is set
                    $fieldData['form_id'] = $form->id;
                    
                    // Create the field
                    \App\Models\FormField::create($fieldData);
                }
            }
            
            \Illuminate\Support\Facades\DB::commit();
            
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Log::error('Failed to execute form update action: ' . $e->getMessage());
            return false;
        }
    }

    private function executeFormDeleteAction(): bool
    {
        $form = $this->form;
        if (!$form) {
            \Log::error("Form not found for approval request ID: {$this->id}");
            return false;
        }
        
        return $form->delete();
    }

    private function executeFormArchiveAction(): bool
    {
        $form = $this->form;
        if (!$form) {
            \Log::error("Form not found for approval request ID: {$this->id}");
            return false;
        }
        
        return $form->archive();
    }

    /**
     * Get the current level that needs approval
     */
    public function getCurrentLevel(): ?ApprovalRequestLevel
    {
        return $this->approvalRequestLevels()
            ->where('status', 'pending')
            ->orderBy('level_number')
            ->first();
    }

    /**
     * Get the next level that needs approval
     */
    public function getNextLevel(?int $afterLevelNumber = null): ?ApprovalRequestLevel
    {
        $levelNumber = $afterLevelNumber;
        if ($levelNumber === null) {
            $currentLevel = $this->getCurrentLevel();
            if (!$currentLevel) {
                return null;
            }
            $levelNumber = (int) $currentLevel->level_number;
        }

        return $this->approvalRequestLevels()
            ->where('level_number', '>', $levelNumber)
            ->where('status', 'pending')
            ->orderBy('level_number')
            ->first();
    }

    /**
     * Check if the request is fully approved
     */
    public function isFullyApproved(): bool
    {
        $totalLevels = $this->approvalWorkflow->approvalLevels()->count();
        $approvedLevels = $this->approvalRequestLevels()
            ->where('status', 'approved')
            ->count();

        return $approvedLevels >= $totalLevels;
    }

    /**
     * Check if the request has been rejected
     */
    public function isRejected(): bool
    {
        return $this->approvalRequestLevels()
            ->where('status', 'rejected')
            ->exists();
    }

    /**
     * Check if the request is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the request is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the request is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Approve the request
     */
    public function approve(): bool
    {
        // Check if there are approval levels
        $hasApprovalLevels = $this->approvalRequestLevels()->exists();
        
        if (!$hasApprovalLevels || $this->isFullyApproved()) {
            $oldStatus = $this->status;
            $this->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
            
            // Send notification
            event(new ApprovalRequestStatusChanged($this, $oldStatus, 'approved'));
            
            // Execute the approved action
            if ($this->executeAction()) {
                $this->update(['executed_at' => now()]);
            }
            
            return true;
        }
        return false;
    }

    /**
     * Reject the request
     */
    public function reject(string $reason = null): bool
    {
        $oldStatus = $this->status;
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
        
        // If this is an application update request, update the application status to rejected
        if ($this->isApplicationRequest() && $this->getActionType() === 'update' && $this->application) {
            if ($this->application->isPending()) {
                $this->application->reject();
            }
        }
        
        // Send notification
        event(new ApprovalRequestStatusChanged($this, $oldStatus, 'rejected', $reason));
        
        return true;
    }

    /**
     * Cancel the request
     */
    public function cancel(): bool
    {
        $oldStatus = $this->status;
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
        
        // Send notification
        event(new ApprovalRequestStatusChanged($this, $oldStatus, 'cancelled'));
        
        return true;
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for cancelled requests
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}
