<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notificationData['title'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            font-size: 12px;
            color: #6c757d;
        }
        .request-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .request-details h4 {
            margin-top: 0;
            color: #495057;
        }
        .request-details p {
            margin: 5px 0;
        }
        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }
        .action-button {
            background-color: #007bff;
            color: white !important;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $notificationData['title'] }}</h2>
        <p>IP Innovation Platform / منصة الابتكار</p>
    </div>

    <div class="content">
        @php
            $data = $notificationData['data'] ?? [];
            $type = $notificationData['type'] ?? null;
            $requestNumber = $data['request_id'] ?? str_pad((string) $approvalRequest->id, 3, '0', STR_PAD_LEFT);
            $actionType = $data['action_type'] ?? ucfirst(str_replace('.', ' ', $approvalRequest->action));
            $viewLink = $data['view_link'] ?? url('/admin/my-requests/' . $approvalRequest->id);
        @endphp

        <p>Hello {{ $user->name }},</p>

        @if($type === 'approved')
            <p>Your approval request has been Approved / تمت الموافقة على طلبك.</p>
        @elseif($type === 'rejected')
            <p>Your approval request has been Rejected / تم رفض طلبك.</p>
        @else
            <p>{{ $notificationData['message'] }}</p>
        @endif

        <div class="request-details">
            <h4>Request Details / تفاصيل الطلب:</h4>
            <p><strong>- Action / الإجراء:</strong> {{ $actionType }}</p>
            @if($type === 'approved')
                <p><strong>- Status / الحالة:</strong> Approved / تمت الموافقة</p>
            @elseif($type === 'rejected')
                <p><strong>- Status / الحالة:</strong> Rejected / مرفوض</p>
            @endif
            <p><strong>- Submitted / تم التقديم:</strong> {{ $data['submission_date'] ?? $approvalRequest->created_at->format('M d, Y H:i') }}</p>

            @if($type === 'approved')
                <p><strong>- Approved By / تمت الموافقة بواسطة:</strong> {{ $data['approver_name'] ?? 'Unknown' }}</p>
                <p><strong>- Approved On / تاريخ الموافقة:</strong> {{ $data['approval_date'] ?? ($approvalRequest->approved_at?->format('M d, Y H:i')) }}</p>
            @elseif($type === 'rejected')
                <p><strong>- Rejected By / تم الرفض بواسطة:</strong> {{ $data['approver_name'] ?? 'Unknown' }}</p>
                <p><strong>- Rejected On / تاريخ الرفض:</strong> {{ $data['rejection_date'] ?? ($approvalRequest->rejected_at?->format('M d, Y H:i')) }}</p>
                <p><strong>- Rejection Reason / سبب الرفض:</strong> {{ $data['rejection_reason'] ?? $approvalRequest->rejection_reason ?? 'N/A / لا يوجد' }}</p>
            @endif
        </div>

        <p>
            <a href="{{ $viewLink }}" class="action-button">
                View Request / عرض الطلب
            </a>
        </p>

        <p>Thank you for using IP Innovation Platform.<br>شكراً لاستخدامك منصة الابتكار.</p>
    </div>

    <div class="footer">
        <p>This is an automated notification. Please do not reply to this email.</p>
        <p>هذا إشعار تلقائي. يرجى عدم الرد على هذا البريد الإلكتروني.</p>
        <p>&copy; {{ date('Y') }} IP Innovation Platform. All rights reserved.</p>
    </div>
</body>
</html>
