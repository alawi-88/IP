<?php

namespace App\Listeners;

use App\Events\ApprovalRequestStatusChanged;
use App\Models\ApprovalRequestNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApprovalRequestStatusChangedMail;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class SendApprovalRequestNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ApprovalRequestStatusChanged $event): void
    {
        $approvalRequest = $event->approvalRequest;
        $user = $approvalRequest->requestedBy;
        
        if (!$user) {
            return;
        }

        // Requester admin should only be notified when the approval flow is fully completed:
        // final status Approved or Rejected (not on intermediate levels / pending changes).
        if (!in_array($event->newStatus, ['approved', 'rejected'], true)) {
            return;
        }

        $notificationData = $this->getNotificationData($event);
        
        // Send notification to the requester
        $this->sendNotificationToUser($user, $approvalRequest, $notificationData);
        
        // Note: Approver assignment notifications are now handled by ApproverAssignedToRequest event
        // This listener only handles status change notifications for requesters
    }
    
    private function sendNotificationToUser($user, $approvalRequest, $notificationData): void
    {
        $locale = App::getLocale();
        $isArabic = $locale === 'ar';

        $notifiable = $this->resolveAdminNotifiable((int) $user->id) ?? $user;

        // Create custom database notification
        ApprovalRequestNotification::create([
            'approval_request_id' => $approvalRequest->id,
            'user_id' => $user->id,
            'type' => $notificationData['type'],
            'title' => $notificationData['title'],
            'message' => $notificationData['message'],
            'data' => $notificationData['data'],
        ]);

        // Send Filament database notification (bell) with expected title/body/url fields.
        $notifiable->notify(
            FilamentNotification::make()
                ->title($notificationData['title'])
                ->body($notificationData['message'])
                ->icon($notificationData['type'] === 'rejected' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color($notificationData['type'] === 'rejected' ? 'danger' : 'success')
                ->actions([
                    Action::make('view')
                        ->label($isArabic ? 'عرض الطلب' : 'View Request')
                        ->url($notificationData['data']['view_link'] ?? url('/admin/my-requests/' . $approvalRequest->id)),
                ])
                ->toDatabase(),
        );

        // Send email notification
        try {
            Mail::to($user->email)->send(new ApprovalRequestStatusChangedMail($approvalRequest, $notificationData));
        } catch (\Exception $e) {
            \Log::error('Failed to send approval request notification email: ' . $e->getMessage());
        }
    }

    private function getNotificationData(ApprovalRequestStatusChanged $event): array
    {
        $approvalRequest = $event->approvalRequest;
        $newStatus = $event->newStatus;

        $actionType = trim(ucfirst(str_replace('.', ' ', (string) $approvalRequest->action)));
        $requestNumber = str_pad((string) $approvalRequest->id, 3, '0', STR_PAD_LEFT);
        $displayRequestId = "##{$requestNumber}";
        $viewLink = url('/admin/my-requests/' . $approvalRequest->id);

        // Identify the last decision maker + decision date (best effort).
        $decisionLevel = null;
        $decisionAt = null;
        $decisionBy = null;

        if ($newStatus === 'approved') {
            $decisionLevel = $approvalRequest->approvalRequestLevels()
                ->whereNotNull('approved_at')
                ->with('approver')
                ->orderByDesc('approved_at')
                ->first();
            $decisionAt = $approvalRequest->approved_at ?? $decisionLevel?->approved_at;
            $decisionBy = $decisionLevel?->approver?->name;
        } elseif ($newStatus === 'rejected') {
            $decisionLevel = $approvalRequest->approvalRequestLevels()
                ->whereNotNull('rejected_at')
                ->with('approver')
                ->orderByDesc('rejected_at')
                ->first();
            $decisionAt = $approvalRequest->rejected_at ?? $decisionLevel?->rejected_at;
            $decisionBy = $decisionLevel?->approver?->name;
        }

        $submittedAt = $approvalRequest->created_at;
        $requesterName = $approvalRequest->requestedBy?->name ?? 'Unknown';
        $approverName = $decisionBy ?? 'Unknown';

        switch ($newStatus) {
            case 'approved':
                return [
                    'type' => 'approved',
                    'title' => 'Request Approved / تمت الموافقة على الطلب',
                    'message' => "Your request {$displayRequestId} for '{$actionType}' has been approved. / تمت الموافقة على طلبك رقم {$displayRequestId} لـ '{$actionType}'.",
                    'data' => [
                        'request_id' => $requestNumber,
                        'display_request_id' => $displayRequestId,
                        'action_type' => $actionType,
                        'status' => 'Approved / تمت الموافقة',
                        'submission_date' => $submittedAt?->format('M d, Y H:i'),
                        'requester_name' => $requesterName,
                        'approver_name' => $approverName,
                        'approval_date' => $decisionAt?->format('M d, Y H:i'),
                        'view_link' => $viewLink,
                    ]
                ];

            case 'rejected':
                return [
                    'type' => 'rejected',
                    'title' => 'Request Rejected / تم رفض الطلب',
                    'message' => "Your request {$displayRequestId} for '{$actionType}' has been rejected. / تم رفض طلبك رقم {$displayRequestId} لـ '{$actionType}'.",
                    'data' => [
                        'request_id' => $requestNumber,
                        'display_request_id' => $displayRequestId,
                        'action_type' => $actionType,
                        'status' => 'Rejected / مرفوض',
                        'submission_date' => $submittedAt?->format('M d, Y H:i'),
                        'requester_name' => $requesterName,
                        'approver_name' => $approverName,
                        'rejection_date' => $decisionAt?->format('M d, Y H:i'),
                        'rejection_reason' => $approvalRequest->rejection_reason,
                        'view_link' => $viewLink,
                    ]
                ];
        }

        // Should not reach here because we early-return for non-final statuses.
        return [
            'type' => 'status_changed',
            'title' => 'Status Changed / تغيرت الحالة',
            'message' => "Your request {$displayRequestId} for '{$actionType}' status has changed. / تغيرت حالة طلبك رقم {$displayRequestId} لـ '{$actionType}'.",
            'data' => [
                'request_id' => $requestNumber,
                'display_request_id' => $displayRequestId,
                'action_type' => $actionType,
                'view_link' => $viewLink,
            ]
        ];
    }

    /**
     * Resolve the notifiable model class used by the admin panel authentication provider.
     */
    private function resolveAdminNotifiable(int $userId): ?object
    {
        $modelClass = Config::get('auth.providers.users.model');

        if (!is_string($modelClass) || $modelClass === '') {
            return null;
        }

        if (!class_exists($modelClass)) {
            return null;
        }

        $instance = $modelClass::query()->find($userId);

        if (!$instance) {
            return null;
        }

        if (!method_exists($instance, 'notifyNow')) {
            return null;
        }

        return $instance;
    }
}
