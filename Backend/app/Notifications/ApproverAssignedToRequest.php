<?php

namespace App\Notifications;

use App\Models\ApprovalRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class ApproverAssignedToRequest extends Notification
{
    public function __construct(
        public ApprovalRequest $approvalRequest,
        public int $levelNumber
    ) {}

    public function via($notifiable): array
    {
        // Email is sent via a dedicated Mailable in the listener.
        // Keep this notification database-only so Filament's bell always shows it.
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $requestNumber = str_pad((string) $this->approvalRequest->id, 3, '0', STR_PAD_LEFT);
        $displayRequestId = "##{$requestNumber}";
        $actionType = trim(ucfirst(str_replace('.', ' ', (string) $this->approvalRequest->action)));

        $requestLink = url('/admin/approval-requests/' . $this->approvalRequest->id);
        $title = 'Approval Required / مطلوب موافقة';
        $body = "A new request {$displayRequestId} for '{$actionType}' requires your approval. / طلب جديد رقم {$displayRequestId} لـ '{$actionType}' يحتاج موافقتك.";

        return [
            'approval_request_id' => $this->approvalRequest->id,
            'level_number' => $this->levelNumber,
            'type' => 'approver_assigned',
            'title' => $title,
            // Filament's database notifications (bell) expect a `body` field (see comment notifications).
            'body' => $body,
            'request_link' => $requestLink,
            'request_id' => $displayRequestId,
            'action' => $this->approvalRequest->action,
            'created_at' => $this->approvalRequest->created_at->format('M d, Y H:i'),
            'url' => $requestLink, // This is used by Filament for notification clicks
        ];
    }

    /**
     * Get request title based on action and target
     */
    private function getRequestTitle(bool $isArabic): string
    {
        $action = $this->approvalRequest->action;
        $actionText = ucfirst(str_replace('.', ' ', $action));

        // Try to get a more descriptive title from the target
        if ($this->approvalRequest->target) {
            $target = $this->approvalRequest->target;
            
            // If target has a name or title field
            if (isset($target->name)) {
                return $target->name;
            }
            if (isset($target->title)) {
                return $target->title;
            }
        }

        return $isArabic 
            ? "طلب {$actionText}"
            : "Request for {$actionText}";
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Failed to send approver assignment notification', [
            'approval_request_id' => $this->approvalRequest->id,
            'level_number' => $this->levelNumber,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

