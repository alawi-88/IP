<?php

namespace App\Notifications;

use App\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRequestStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public ApprovalRequest $approvalRequest,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $statusText = $this->getStatusText($this->newStatus);
        $actionText = $this->getActionText($this->approvalRequest->action);
        
        return (new MailMessage)
            ->subject("Approval Request Status Changed - {$statusText}")
            ->salutation(' ')
            //->salutation(' ')
            ->greeting("Hello {$notifiable->name},")
            ->line("Your approval request has been {$statusText}.")
            ->line("Request Details:")
            ->line("• Action: {$actionText}")
            ->line("• Status: {$statusText}")
            ->line("• Submitted: {$this->approvalRequest->created_at->format('M d, Y H:i')}")
            ->when($this->newStatus === 'rejected' && $this->approvalRequest->rejection_reason, function ($message) {
                return $message->line("• Rejection Reason: {$this->approvalRequest->rejection_reason}");
            })
            ->action('View Request', url('/admin/approval-requests/' . $this->approvalRequest->id))
            ->line('Thank you for using our platform!');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'approval_request_id' => $this->approvalRequest->id,
            'action' => $this->approvalRequest->action,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'status_text' => $this->getStatusText($this->newStatus),
            'action_text' => $this->getActionText($this->approvalRequest->action),
            'rejection_reason' => $this->approvalRequest->rejection_reason,
        ];
    }

    private function getStatusText(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending / قيد الانتظار',
            'approved' => 'Approved / موافق',
            'rejected' => 'Rejected / مرفوض',
            'cancelled' => 'Cancelled / ملغي',
            'returned' => 'Returned / إعادة',
            default => ucfirst($status),
        };
    }

    private function getActionText(string $action): string
    {
        return match ($action) {
            'ProgramApplication.update' => 'Update Program Application / تحديث طلب مسابقة',
            'ProgramApplication.delete' => 'Delete Program Application / حذف طلب مسابقة',
            'ProgramApplication.archive' => 'Archive Program Application / أرشفة طلب مسابقة',
            'Program.create' => 'Create Program / إنشاء برنامج',
            'Program.update' => 'Update Program / تحديث برنامج',
            'Program.delete' => 'Delete Program / حذف برنامج',
            'Program.archive' => 'Archive Program / أرشفة برنامج',
            'Form.create' => 'Create Form / إنشاء نموذج',
            'Form.update' => 'Update Form / تحديث النموذج',
            'Form.delete' => 'Delete Form / حذف النموذج',
            'Form.archive' => 'Archive Form / أرشفة النموذج',
            'Project.update' => 'Update Project / تحديث مشروع',
            'Project.delete' => 'Delete Project / حذف مشروع',
            'Project.archive' => 'Archive Project / أرشفة مشروع',
            'Project.restore' => 'Restore Project / استعادة مشروع',
            default => ucfirst(str_replace('.', ' ', $action)),
        };
    }
}