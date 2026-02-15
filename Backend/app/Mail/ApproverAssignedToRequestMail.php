<?php

namespace App\Mail;

use App\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class ApproverAssignedToRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3; // Maximum retry attempts
    public $backoff = [60, 300, 900]; // Retry after 1 min, 5 min, 15 min

    public function __construct(
        public ApprovalRequest $approvalRequest,
        public int $levelNumber,
        public array $notificationData
    ) {}

    public function envelope(): Envelope
    {
        $requestNumber = $this->notificationData['request_number']
            ?? str_pad((string) $this->approvalRequest->id, 3, '0', STR_PAD_LEFT);
        $displayRequestId = '##' . $requestNumber;
        $actionType = $this->notificationData['action_type']
            ?? trim(ucfirst(str_replace('.', ' ', (string) $this->approvalRequest->action)));

        return new Envelope(
            subject: "Approval Required: {$actionType} Request {$displayRequestId} / مطلوب موافقة: طلب {$actionType} رقم {$displayRequestId}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.approver-assigned-to-request',
            with: [
                'approvalRequest' => $this->approvalRequest,
                'levelNumber' => $this->levelNumber,
                'notificationData' => $this->notificationData,
                'isArabic' => App::getLocale() === 'ar',
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Failed to send approver assignment email', [
            'approval_request_id' => $this->approvalRequest->id,
            'level_number' => $this->levelNumber,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

