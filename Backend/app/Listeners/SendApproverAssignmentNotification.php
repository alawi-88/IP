<?php

namespace App\Listeners;

use App\Events\ApproverAssignedToRequest;
use App\Models\ApprovalRequestNotification;
use App\Mail\ApproverAssignedToRequestMail;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;

class SendApproverAssignmentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3; // Maximum retry attempts
    public $backoff = [60, 300, 900]; // Retry after 1 min, 5 min, 15 min

    public function handle(ApproverAssignedToRequest $event): void
    {
        $approvalRequest = $event->approvalRequest;
        $approver = $event->approver;
        $levelNumber = $event->levelNumber;

        // Validate required fields
        if (!$this->validateRequiredFields($approvalRequest, $approver)) {
            Log::warning('Approver assignment notification skipped due to missing required fields', [
                'approval_request_id' => $approvalRequest->id,
                'approver_id' => $approver->id,
            ]);
            return;
        }

        // Check for duplicate notification (prevent sending if already notified for this request and level)
        if ($this->isDuplicateNotification($approvalRequest, $approver, $levelNumber)) {
            return;
        }

        // Set locale based on system default or user preference
        $locale = App::getLocale();
        $isArabic = $locale === 'ar';

        // Prepare notification data
        $notificationData = $this->prepareNotificationData($approvalRequest, $approver, $levelNumber, $isArabic);

        try {
            // IMPORTANT:
            // The admin panel may authenticate using a different model (via AUTH_MODEL / auth provider),
            // e.g. `App\Models\Supervisor` (same `users` table) instead of `App\Models\User`.
            // Laravel notifications are stored with `notifiable_type`, so if we notify the wrong class,
            // the Filament bell (which reads notifications for the authenticated model class) won't show anything.
            $notifiable = $this->resolveAdminNotifiable($approver->id) ?? $approver;

            // Send Filament database notification for admin panel (bell).
            // Keep the exact working pattern used in comment notifications:
            // `$user->notify(Notification::make()->...->toDatabase(),);`
            $notifiable->notify(
                FilamentNotification::make()
                    ->title($notificationData['title'])
                    ->body($notificationData['message'])
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('primary')
                    ->actions([
                        Action::make('view')
                            ->label($isArabic ? 'عرض الطلب' : 'View request')
                            ->url($notificationData['request_link']),
                    ])
                    ->toDatabase(),
            );

            // Create custom database notification record
            // Store the notification data including level_number for duplicate checking
            ApprovalRequestNotification::create([
                'approval_request_id' => $approvalRequest->id,
                'user_id' => $approver->id,
                'type' => 'approver_assigned',
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'data' => array_merge($notificationData, ['level_number' => $levelNumber]),
            ]);

            // Send email notification
            Mail::to($approver->email)->send(
                new ApproverAssignedToRequestMail($approvalRequest, $levelNumber, $notificationData)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send approver assignment notification', [
                'approval_request_id' => $approvalRequest->id,
                'approver_id' => $approver->id,
                'level_number' => $levelNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     */
    private function validateRequiredFields($approvalRequest, $approver): bool
    {
        // Check required fields: Request Title, Requester Name, Request Link, Approver Admin
        if (!$approvalRequest->id) {
            return false;
        }

        if (!$approvalRequest->requestedBy) {
            return false;
        }

        if (!$approver->id) {
            return false;
        }

        return true;
    }

    /**
     * Resolve the notifiable model class used by the admin panel authentication provider.
     *
     * @return object|null  A notifiable model instance matching the auth provider model, if found.
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

        // We only need `notifyNow()` support.
        if (!method_exists($instance, 'notifyNow')) {
            return null;
        }

        return $instance;
    }

    /**
     * Check if this is a duplicate notification
     * Prevents sending duplicate notifications if the same request is reassigned to the same approver
     */
    private function isDuplicateNotification($approvalRequest, $approver, $levelNumber): bool
    {
        // Check if we've already sent a notification for this request, user, and level.
        // NOTE: We must include the level in the query; otherwise `first()` may return a record from a previous level
        // and we'd incorrectly re-send notifications.
        return ApprovalRequestNotification::where('approval_request_id', $approvalRequest->id)
            ->where('user_id', $approver->id)
            ->where('type', 'approver_assigned')
            ->where('data->level_number', $levelNumber)
            ->exists();
    }

    /**
     * Prepare notification data with bilingual support
     */
    private function prepareNotificationData($approvalRequest, $approver, $levelNumber, bool $isArabic): array
    {
        $requestNumber = str_pad((string) $approvalRequest->id, 3, '0', STR_PAD_LEFT);
        $displayRequestId = "##{$requestNumber}";
        $actionType = $this->getActionTypeText($approvalRequest);

        $requestLink = url('/admin/approval-requests/' . $approvalRequest->id);
        $requesterName = $approvalRequest->requestedBy->name ?? ($isArabic ? 'غير معروف' : 'Unknown');
        $approverName = $approver->name ?? ($isArabic ? 'غير معروف' : 'Unknown');
        $requestReason = $approvalRequest->reason ?: ($isArabic ? 'لا يوجد' : 'N/A');

        return [
            // In-app (Filament bell)
            'title' => 'Approval Required / مطلوب موافقة',
            'message' => "A new request {$displayRequestId} for '{$actionType}' requires your approval. / طلب جديد رقم {$displayRequestId} لـ '{$actionType}' يحتاج موافقتك.",

            // Email template fields
            'request_number' => $requestNumber,
            'display_request_id' => $displayRequestId,
            'action_type' => $actionType,
            'status_text' => 'PENDING / قيد الانتظار',
            'submission_date' => $approvalRequest->created_at?->format('M d, Y H:i') ?? ($isArabic ? 'لا يوجد تاريخ' : 'No date'),
            'requester_name' => $requesterName,
            'approver_name' => $approverName,
            'request_reason' => $requestReason,
            'request_link' => $requestLink,
            'level_number' => $levelNumber,
            'action' => $approvalRequest->action,
        ];
    }

    /**
     * Human-friendly action type text used in notifications.
     */
    private function getActionTypeText($approvalRequest): string
    {
        $action = (string) ($approvalRequest->action ?? '');
        $actionText = trim(ucfirst(str_replace('.', ' ', $action)));

        return $actionText !== '' ? $actionText : 'Unknown';
    }

    /**
     * Get request title based on action and target
     */
    private function getRequestTitle($approvalRequest, bool $isArabic): string
    {
        $action = $approvalRequest->action;
        $actionText = ucfirst(str_replace('.', ' ', $action));

        // Try to get a more descriptive title from the target
        if ($approvalRequest->target) {
            $target = $approvalRequest->target;
            
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
    public function failed(ApproverAssignedToRequest $event, \Throwable $exception): void
    {
        Log::error('Failed to send approver assignment notification after retries', [
            'approval_request_id' => $event->approvalRequest->id,
            'approver_id' => $event->approver->id,
            'level_number' => $event->levelNumber,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

