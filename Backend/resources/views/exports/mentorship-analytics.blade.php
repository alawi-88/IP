<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $translations['mentorship_analytics_report'] ?? 'Mentorship Analytics Report' }}</title>
    <style>
        * {
            font-family: DejaVu Sans, Arial, sans-serif;
        }
        
        body {
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 12px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #074d31;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #074d31;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        
        .header .date {
            color: #666;
            margin-top: 5px;
            font-size: 14px;
        }
        
        .filters {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        
        .filters h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #074d31;
        }
        
        .filter-item {
            margin: 5px 0;
            font-size: 11px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            page-break-inside: auto;
        }
        
        thead {
            background-color: #074d31;
            color: white;
        }
        
        th {
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            border: 1px solid #ddd;
        }
        
        @if(app()->getLocale() === 'ar')
            th { text-align: right; }
        @endif
        
        tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 11px;
            vertical-align: top;
        }
        
        @if(app()->getLocale() === 'ar')
            td { text-align: right; }
        @endif
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }
        
        .summary {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #e8f5e9;
            border-radius: 5px;
        }
        
        .summary h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #074d31;
        }
        
        .summary-item {
            margin: 5px 0;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $translations['mentorship_analytics_report'] ?? 'Mentorship Analytics Report' }}</h1>
        <div class="date">{{ $translations['generated_on'] ?? 'Generated on' }}: {{ now()->format('Y-m-d H:i:s') }}</div>
    </div>
    
    @if(!empty($filters))
    <div class="filters">
        <h3>{{ $translations['applied_filters'] ?? 'Applied Filters' }}:</h3>
        @if(!empty($filters['competition']))
            <div class="filter-item"><strong>{{ $translations['program'] ?? 'Program' }}:</strong> {{ $filters['competition'] }}</div>
        @endif
        @if(!empty($filters['mentor']))
            <div class="filter-item"><strong>{{ $translations['mentor'] ?? 'Mentor' }}:</strong> {{ $filters['mentor'] }}</div>
        @endif
        @if(!empty($filters['start_date']))
            <div class="filter-item"><strong>{{ $translations['start_date'] ?? 'Start Date' }}:</strong> {{ $filters['start_date'] }}</div>
        @endif
        @if(!empty($filters['end_date']))
            <div class="filter-item"><strong>{{ $translations['end_date'] ?? 'End Date' }}:</strong> {{ $filters['end_date'] }}</div>
        @endif
    </div>
    @endif
    
    @if(!empty($summary))
    <div class="summary">
        <h3>{{ $translations['summary'] ?? 'Summary' }}:</h3>
        <div class="summary-item"><strong>{{ $translations['total_sessions'] ?? 'Total Sessions' }}:</strong> {{ $summary['total_sessions'] ?? 0 }}</div>
        <div class="summary-item"><strong>{{ $translations['completed_sessions'] ?? 'Completed Sessions' }}:</strong> {{ $summary['completed'] ?? 0 }}</div>
        <div class="summary-item"><strong>{{ $translations['cancellations'] ?? 'Cancellations' }}:</strong> {{ $summary['cancelled'] ?? 0 }}</div>
        <div class="summary-item"><strong>{{ $translations['reschedules'] ?? 'Reschedules' }}:</strong> {{ $summary['rescheduled'] ?? 0 }}</div>
    </div>
    @endif
    
    @if($sessions->count() > 0)
    <table>
        <thead>
            <tr>
                <th>{{ $translations['session_id'] ?? 'Session ID' }}</th>
                <th>{{ $translations['program'] ?? 'Program' }}</th>
                <th>{{ $translations['mentor'] ?? 'Mentor' }}</th>
                <th>{{ $translations['participant'] ?? 'Participant' }}</th>
                <th>{{ $translations['session_title'] ?? 'Session Title' }}</th>
                <th>{{ $translations['scheduled_at'] ?? 'Scheduled At' }}</th>
                <th>{{ $translations['duration_minutes'] ?? 'Duration (Minutes)' }}</th>
                <th>{{ $translations['status'] ?? 'Status' }}</th>
                <th>{{ $translations['created_at'] ?? 'Created At' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sessions as $session)
            <tr>
                <td>{{ $session->id }}</td>
                <td>{{ $session->competition_title ?? 'N/A' }}</td>
                <td>{{ $session->mentor_name ?? 'N/A' }}</td>
                <td>{{ $session->participant_name ?? 'N/A' }}</td>
                <td>{{ $session->title ?? 'N/A' }}</td>
                <td>{{ $session->scheduled_at ? $session->scheduled_at->format('Y-m-d H:i') : 'N/A' }}</td>
                <td>{{ $session->duration_minutes ?? 60 }}</td>
                <td>{{ $session->status ?? 'N/A' }}</td>
                <td>{{ $session->created_at ? $session->created_at->format('Y-m-d H:i') : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">
        {{ $translations['no_sessions_found'] ?? 'No sessions found' }}
    </div>
    @endif
    
    <div class="footer">
        {{ $translations['report_generated_by'] ?? 'Report generated by' }} {{ $user_name ?? 'System' }} | 
        {{ $translations['page'] ?? 'Page' }} <span class="page"></span>
    </div>
</body>
</html>
