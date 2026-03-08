<?php

namespace App\Filament\Resources\MentorResource\Widgets;

use App\Models\MentorSession;
use App\Models\Program;
use App\Models\Mentor;
use App\Traits\HasDateRangeFilter;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class MentorshipStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters, HasDateRangeFilter;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $programId = $this->filters['program_id'] ?? null;
        $mentorId = $this->filters['mentor_id'] ?? null;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        $query = MentorSession::query();

        // Apply filters (empty string means "all")
        if ($programId && $programId !== '') {
            $query->where('program_id', $programId);
        }

        if ($mentorId && $mentorId !== '') {
            $query->where('mentor_id', $mentorId);
        }

        if ($startDate || $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                if ($startDate) {
                    $q->whereDate('scheduled_at', '>=', Carbon::parse($startDate)->startOfDay());
                }
                if ($endDate) {
                    $q->whereDate('scheduled_at', '<=', Carbon::parse($endDate)->endOfDay());
                }
            });
        }

        // Calculate statistics
        $totalSessions = (clone $query)->count();
        $cancellations = (clone $query)->where('status', 'cancelled')->count();
        
        // Count reschedules (sessions with activity log entries for reschedule)
        $reschedules = DB::table('activity_log')
            ->where('log_name', 'mentor_session')
            ->where('event', 'session_rescheduled')
            ->when($programId && $programId !== '', function ($q) use ($programId) {
                $sessionIds = MentorSession::where('program_id', $programId)->pluck('id');
                return $q->whereIn('subject_id', $sessionIds);
            })
            ->when($mentorId && $mentorId !== '', function ($q) use ($mentorId) {
                $sessionIds = MentorSession::where('mentor_id', $mentorId)->pluck('id');
                return $q->whereIn('subject_id', $sessionIds);
            })
            ->when($startDate || $endDate, function ($q) use ($startDate, $endDate) {
                if ($startDate) {
                    $q->whereDate('created_at', '>=', Carbon::parse($startDate)->startOfDay());
                }
                if ($endDate) {
                    $q->whereDate('created_at', '<=', Carbon::parse($endDate)->endOfDay());
                }
            })
            ->count();

        // Additional stats
        $completedSessions = (clone $query)->where('status', 'completed')->count();
        $scheduledSessions = (clone $query)->whereIn('status', ['scheduled', 'confirmed'])->count();
        $noShowSessions = (clone $query)->where('status', 'no_show')->count();
        $inProgressSessions = (clone $query)->where('status', 'in_progress')->count();

        // Calculate completion rate
        $completionRate = $totalSessions > 0 
            ? round(($completedSessions / $totalSessions) * 100, 2) 
            : 0;

        // Calculate cancellation rate
        $cancellationRate = $totalSessions > 0 
            ? round(($cancellations / $totalSessions) * 100, 2) 
            : 0;

        // Calculate reschedule rate
        $rescheduleRate = $totalSessions > 0 
            ? round(($reschedules / $totalSessions) * 100, 2) 
            : 0;

        // Calculate attendance rate (completed vs scheduled+confirmed)
        $scheduledAndConfirmed = (clone $query)->whereIn('status', ['scheduled', 'confirmed', 'completed'])->count();
        $attendanceRate = $scheduledAndConfirmed > 0 
            ? round(($completedSessions / $scheduledAndConfirmed) * 100, 2) 
            : 0;

        // Calculate average session duration
        $avgDuration = (clone $query)
            ->whereNotNull('duration_minutes')
            ->avg('duration_minutes');
        $avgDurationFormatted = $avgDuration ? round($avgDuration) . ' ' . __('analytics.minutes') : __('analytics.n_a');

        // This week sessions
        $thisWeekStart = Carbon::now()->startOfWeek();
        $thisWeekEnd = Carbon::now()->endOfWeek();
        $thisWeekSessions = (clone $query)
            ->whereBetween('scheduled_at', [$thisWeekStart, $thisWeekEnd])
            ->count();

        // This month sessions
        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();
        $thisMonthSessions = (clone $query)
            ->whereBetween('scheduled_at', [$thisMonthStart, $thisMonthEnd])
            ->count();

        return [
            Stat::make(__('analytics.total_sessions'), $totalSessions)
                ->description(__('analytics.all_time_sessions'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary')
                ->chart($this->getSessionChartData($programId, $mentorId, $startDate, $endDate)),

            Stat::make(__('analytics.completed_sessions'), $completedSessions)
                ->description(__('analytics.completion_rate', ['rate' => $completionRate . '%']))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(__('analytics.cancellations'), $cancellations)
                ->description(__('analytics.cancellation_rate', ['rate' => $cancellationRate . '%']))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make(__('analytics.reschedules'), $reschedules)
                ->description(__('analytics.reschedule_rate', ['rate' => $rescheduleRate . '%']))
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('warning'),

            Stat::make(__('analytics.scheduled_sessions'), $scheduledSessions)
                ->description(__('analytics.upcoming_sessions'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make(__('analytics.in_progress_sessions'), $inProgressSessions)
                ->description(__('analytics.active_now'))
                ->descriptionIcon('heroicon-m-play-circle')
                ->color('success'),

            Stat::make(__('analytics.no_show_sessions'), $noShowSessions)
                ->description(__('analytics.missed_sessions'))
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('gray'),

            Stat::make(__('analytics.attendance_rate'), $attendanceRate . '%')
                ->description(__('analytics.completed_vs_scheduled'))
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info'),

            Stat::make(__('analytics.average_duration'), $avgDurationFormatted)
                ->description(__('analytics.per_session'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'),

            Stat::make(__('analytics.this_week_sessions'), $thisWeekSessions)
                ->description(__('analytics.scheduled_this_week'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make(__('analytics.this_month_sessions'), $thisMonthSessions)
                ->description(__('analytics.scheduled_this_month'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
        ];
    }

    protected function getSessionChartData($programId, $mentorId, $startDate, $endDate): array
    {
        $query = MentorSession::query()
            ->select(DB::raw('DATE(scheduled_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date');

        if ($programId && $programId !== '') {
            $query->where('program_id', $programId);
        }

        if ($mentorId && $mentorId !== '') {
            $query->where('mentor_id', $mentorId);
        }

        // Default to last 30 days if no date range specified
        if (!$startDate && !$endDate) {
            $startDate = now()->subDays(30)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
        }

        if ($startDate) {
            $query->whereDate('scheduled_at', '>=', Carbon::parse($startDate)->startOfDay());
        }
        if ($endDate) {
            $query->whereDate('scheduled_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $data = $query->get()->pluck('count')->toArray();
        
        // Ensure we have at least 7 data points for the chart
        return count($data) > 0 ? $data : array_fill(0, 7, 0);
    }
}

