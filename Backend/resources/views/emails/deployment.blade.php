<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Deployment Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .status-success { color: green; }
        .status-failed { color: red; }
        .details { background: #f4f4f4; padding: 10px; border-radius: 5px; margin-top: 10px; }
    </style>
</head>
<body>
    <h2 class="status-{{ $status }}">{{ ucfirst($status) }} Deployment</h2>
    <p><strong>Branch / Environment:</strong> {{ $environment }}</p>
    <p><strong>Server:</strong> {{ $server }}</p>
    <p><strong>Details:</strong> {{ $details }}</p>
    <p><strong>Updated files:</strong> {{ $updated_files }}</p>
    <p><strong>Last commit message:</strong> {{ $last_commit_message }}</p>
</body>
</html>
