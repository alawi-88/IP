<?php

namespace App\Mail;

use App\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApprovalRequestStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ApprovalRequest $approvalRequest,
        public array $notificationData
    ) {}

    public function envelope(): Envelope
    {
        $requestNumber = $this->notificationData['data']['request_id']
            ?? str_pad((string) $this->approvalRequest->id, 3, '0', STR_PAD_LEFT);
        $displayRequestId = '##' . $requestNumber;
        $actionType = $this->notificationData['data']['action_type']
            ?? trim(ucfirst(str_replace('.', ' ', (string) $this->approvalRequest->action)));

        $type = $this->notificationData['type'] ?? null;
        $subject = match ($type) {
            'approved' => "Request Approved: {$actionType} {$displayRequestId} / تمت الموافقة: {$actionType} رقم {$displayRequestId}",
            'rejected' => "Request Rejected: {$actionType} {$displayRequestId} / تم الرفض: {$actionType} رقم {$displayRequestId}",
            default => $this->notificationData['title'] ?? 'Approval Request Update',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.approval-request-status-changed',
            with: [
                'approvalRequest' => $this->approvalRequest,
                'notificationData' => $this->notificationData,
                'user' => $this->approvalRequest->requestedBy,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
