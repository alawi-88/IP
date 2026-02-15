<!DOCTYPE html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Required / مطلوب موافقة</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 680px;
            margin: 0 auto;
            padding: 20px;
            direction: {{ $isArabic ? 'rtl' : 'ltr' }};
        }
        .header {
            background-color: #f8f9fa;
            padding: 18px 20px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }
        .details {
            margin: 14px 0 6px;
        }
        .divider {
            border-top: 1px solid #e9ecef;
            margin: 10px 0;
        }
        .action-button {
            background-color: #0d6efd;
            color: #fff !important;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
            margin: 14px 0;
        }
        .footer {
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid #e9ecef;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin: 0 0 6px;">Approval Required / مطلوب موافقة</h2>
        <div style="color:#6c757d;">IP Innovation Platform / منصة الابتكار</div>
    </div>

    <div class="content">
        <p>Dear {{ $notificationData['approver_name'] ?? 'Approver' }},</p>
        <p>
            A new request <span class="mono">##{{ $notificationData['request_number'] ?? str_pad((string) $approvalRequest->id, 3, '0', STR_PAD_LEFT) }}</span>
            for '{{ $notificationData['action_type'] ?? ucfirst(str_replace('.', ' ', $approvalRequest->action)) }}' requires your approval.
            / طلب جديد رقم <span class="mono">##{{ $notificationData['request_number'] ?? str_pad((string) $approvalRequest->id, 3, '0', STR_PAD_LEFT) }}</span>
            لـ '{{ $notificationData['action_type'] ?? ucfirst(str_replace('.', ' ', $approvalRequest->action)) }}' يحتاج موافقتك.
        </p>

        <div class="details">
            <p><strong>Request Details / تفاصيل الطلب</strong></p>
            <div class="divider"></div>
            <p><strong>Request ID / رقم الطلب:</strong> <span class="mono">#{{ $notificationData['request_number'] ?? str_pad((string) $approvalRequest->id, 3, '0', STR_PAD_LEFT) }}</span></p>
            <p><strong>Action / الإجراء:</strong> {{ $notificationData['action_type'] ?? ucfirst(str_replace('.', ' ', $approvalRequest->action)) }}</p>
            <p><strong>Status / الحالة:</strong> PENDING / قيد الانتظار</p>
            <p><strong>Submitted / تم التقديم:</strong> {{ $notificationData['submission_date'] ?? $approvalRequest->created_at?->format('M d, Y H:i') }}</p>
            <p><strong>Requested By / مقدم الطلب:</strong> {{ $notificationData['requester_name'] ?? ($approvalRequest->requestedBy->name ?? 'Unknown') }}</p>
            <p><strong>Reason / السبب:</strong> {{ $notificationData['request_reason'] ?? ($approvalRequest->reason ?: 'N/A / لا يوجد') }}</p>
        </div>

        <p>
            <a href="{{ $notificationData['request_link'] ?? url('/admin/approval-requests/' . $approvalRequest->id) }}" class="action-button">
                View Request Details / عرض تفاصيل الطلب
            </a>
        </p>

        <p>Thank you for using IP Innovation Platform.<br>شكراً لاستخدامك منصة الابتكار.</p>
    </div>

    <div class="footer">
        This is an automated notification. Please do not reply to this email.
        <br>
        هذا إشعار تلقائي. يرجى عدم الرد على هذا البريد الإلكتروني.
    </div>
</body>
</html>

