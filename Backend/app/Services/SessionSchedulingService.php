<?php

namespace App\Services;

use App\Models\Mentor;
use App\Models\MentorSession;
use App\Models\MentorAvailability;
use App\Models\Participant;
use App\Models\Competition;
use App\Services\VideoToolIntegrationService;
use App\Notifications\Mentor\SessionScheduledNotification as MentorSessionScheduledNotification;
use App\Notifications\Mentor\SessionUpdatedNotification as MentorSessionUpdatedNotification;
use App\Notifications\Mentor\SessionCancelledNotification as MentorSessionCancelledNotification;
use App\Notifications\Participant\SessionScheduledNotification as ParticipantSessionScheduledNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SessionSchedulingService
{
    protected VideoToolIntegrationService $videoToolService;

    public function __construct(VideoToolIntegrationService $videoToolService)
    {
        $this->videoToolService = $videoToolService;
    }

    /**
     * Schedule a new session.
     *
     * Uses database transactions with row-level locking to prevent race conditions
     * when multiple users try to book the same time slot simultaneously.
     */
    public function scheduleSession(array $data, ?string $locale = null): MentorSession
    {
        // Wrap everything in a database transaction with row-level locking
        return DB::transaction(function () use ($data, $locale) {
            // Lock the mentor record for update to prevent concurrent bookings
            // This ensures only one booking process can access the mentor's availability at a time
            $mentor = Mentor::lockForUpdate()->findOrFail($data['mentor_id']);
        $participant = Participant::findOrFail($data['participant_id']);
        $competition = Competition::findOrFail($data['competition_id']);

        // Convert scheduled_at to Carbon instance if it's a string
        // CRITICAL: If no timezone is provided, assume the time is in Asia/Riyadh (Saudi Arabia timezone)
        // This ensures "1 PM" from frontend is interpreted as 1 PM AST, not 1 PM UTC
        if ($data['scheduled_at'] instanceof Carbon) {
            $scheduledAt = $data['scheduled_at']->copy();
            // Ensure the Carbon instance is in Asia/Riyadh timezone if it doesn't have a timezone set
            // or if it's in UTC (which might be the default Laravel timezone)
            // We want to interpret the time as local time (Asia/Riyadh), not UTC
            $timezoneName = $scheduledAt->timezone->getName();
            if ($timezoneName === 'UTC' || $timezoneName === '+00:00') {
                // If timezone is UTC, assume the time value is meant to be in Asia/Riyadh
                // Use shiftTimezone to change timezone without changing the actual time value
                $scheduledAt = $scheduledAt->shiftTimezone('Asia/Riyadh');
            }
        } else {
            $dateString = is_string($data['scheduled_at']) ? $data['scheduled_at'] : '';

            // Check if string contains timezone info (ISO 8601 with timezone offset or timezone name)
            $hasTimezone = preg_match('/[+-]\d{2}:?\d{2}$|[A-Z]{3,4}$/', $dateString) ||
                          preg_match('/T.*[+-]\d{2}:?\d{2}/', $dateString);

            if ($hasTimezone) {
                // String has timezone, parse it directly
                $scheduledAt = Carbon::parse($dateString);
            } else {
                // No timezone in string - assume it's in Asia/Riyadh (UTC+3)
                // Parse the date and explicitly set timezone to Asia/Riyadh
                try {
                    // Try common date formats
                    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateString)) {
                        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i:s', $dateString, 'Asia/Riyadh');
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dateString)) {
                        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $dateString, 'Asia/Riyadh');
                    } else {
                        // Fallback: parse then set timezone
                        $scheduledAt = Carbon::parse($dateString, 'Asia/Riyadh');
                    }
                } catch (\Exception $e) {
                    // Final fallback
                    $scheduledAt = Carbon::parse($dateString)->setTimezone('Asia/Riyadh');
                }
            }
        }

        // Ensure scheduled_at is in Asia/Riyadh timezone
        // We'll save it directly in Asia/Riyadh timezone (Laravel will still convert to UTC in DB, but we ensure the value is correct)
        $scheduledAtLocal = $scheduledAt->copy()->setTimezone('Asia/Riyadh');

        // Ensure duration_minutes is 30 (override 60 if sent from frontend)
        $durationMinutes = $data['duration_minutes'] ?? 30;
        if ($durationMinutes == 60) {
            $durationMinutes = 30; // Override 60 to 30 (temporary fix until frontend is updated)
        }

            // Validate mentor availability (now atomic with the lock)
            // This will check for conflicts with other sessions that might have been created
            // while this transaction was waiting for the lock
        $this->validateMentorAvailability($mentor, $scheduledAt, $durationMinutes);
            // Use local timezone for validation to match availability windows
            $this->validateMentorAvailability($mentor, $scheduledAtLocal, $data['duration_minutes']);

            // Create session (now protected by the lock)
            // Store in Asia/Riyadh timezone - Laravel will handle conversion to UTC for database storage
            // But we ensure the time value represents the correct local time
        $session = MentorSession::create([
            'mentor_id' => $mentor->id,
            'participant_id' => $participant->id,
            'competition_id' => $competition->id,
            'title' => $data['title'] ?? 'Mentor Session', // Ensure title is never null
            'description' => $data['description'] ?? null,
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
            'duration_minutes' => $durationMinutes,
            'status' => 'scheduled',
        ]);

        // Create video meeting if mentor has video tool integration
            // Note: This happens outside the critical section to avoid holding the lock too long
            // If video creation fails, the session is still created
        $defaultVideoTool = $this->videoToolService->resolveVideoToolForMentor($mentor->id);

        if ($defaultVideoTool) {
            // Auto-refresh token if expired before creating session
            if ($defaultVideoTool->isTokenExpired() && $defaultVideoTool->refresh_token) {
                try {
                    if ($this->videoToolService->refreshToken($defaultVideoTool)) {
                        $defaultVideoTool->refresh();
                    }
                } catch (\Exception $e) {
                    // Failed to refresh token, continue anyway - createSession will try to refresh again
                }
            }

            // Check if video tool is valid after token refresh
            // We check is_active and access_token, but not token expiration
            // because createSession will handle token refresh if needed
            if ($defaultVideoTool->is_active && $defaultVideoTool->access_token) {
                try {
                    $this->videoToolService->createSession($session);
                    // Refresh session to get updated meeting details
                    $session->refresh();
                    $session->update(['status' => 'scheduled']);
                } catch (\Exception $e) {
                    // Log the error so we can diagnose why the link wasn't created
                    Log::error('Failed to create video meeting link for session', [
                        'session_id' => $session->id,
                        'mentor_id' => $session->mentor_id,
                        'participant_id' => $session->participant_id,
                        'video_tool_type' => $defaultVideoTool->tool_type,
                        'video_tool_id' => $defaultVideoTool->id,
                        'error_message' => $e->getMessage(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    // Session is still created but without video meeting
                    $session->update(['status' => 'scheduled']);
                }
            } else {
                // Log when video tool exists but is not active or has no access token
                Log::warning('Video tool exists but is not active or has no access token', [
                    'session_id' => $session->id,
                    'mentor_id' => $session->mentor_id,
                    'video_tool_type' => $defaultVideoTool->tool_type,
                    'video_tool_id' => $defaultVideoTool->id,
                    'is_active' => $defaultVideoTool->is_active,
                    'has_access_token' => !empty($defaultVideoTool->access_token),
                ]);
            }
        } else {
            // Log when no video tool is found for the mentor
            Log::warning('No video tool found for mentor when creating session', [
                'session_id' => $session->id,
                'mentor_id' => $mentor->id,
                'participant_id' => $session->participant_id,
            ]);
        }

            // Send notifications after transaction commits to ensure data is persisted
            $sessionId = $session->id;
            $sessionStatus = $session->status; // Capture status before commit
            $serviceInstance = $this; // Capture service instance for use in closure

            DB::afterCommit(function () use ($sessionId, $sessionStatus, $locale, $serviceInstance) {
                try {
                    // Refresh session to get latest status (confirmed if video meeting was created)
                    $freshSession = MentorSession::with(['mentor', 'participant', 'competition'])->find($sessionId);
                    if ($freshSession) {
                        $serviceInstance->sendSessionNotifications($freshSession, $locale);
                    }
                } catch (\Exception $e) {
                    // Error in afterCommit callback
                }
            });

        return $session->fresh(['mentor', 'participant']);
        });
    }

    /**
     * Update an existing session.
     *
     * Uses database transactions with row-level locking to prevent race conditions
     * when updating session times.
     */
    public function updateSession(MentorSession $session, array $data, ?string $locale = null): MentorSession
    {
        // Wrap in transaction if we're changing the scheduled time
        if (isset($data['scheduled_at'])) {
            return DB::transaction(function () use ($session, $data, $locale) {
        $originalScheduledAt = $session->scheduled_at;
        $originalDuration = $session->duration_minutes;
        $originalTitle = $session->title;

                // Lock the mentor record and the session record to prevent concurrent updates
                $mentor = Mentor::lockForUpdate()->findOrFail($session->mentor_id);
                $session = MentorSession::lockForUpdate()->findOrFail($session->id);

            // Convert scheduled_at to Carbon instance if it's a string
            // CRITICAL: If no timezone is provided, assume the time is in Asia/Riyadh (Saudi Arabia timezone)
            // This ensures "1 PM" from frontend is interpreted as 1 PM AST, not 1 PM UTC
            if ($data['scheduled_at'] instanceof Carbon) {
                $scheduledAt = $data['scheduled_at']->copy();
                // Ensure the Carbon instance is in Asia/Riyadh timezone if it doesn't have a timezone set
                // or if it's in UTC (which might be the default Laravel timezone)
                // We want to interpret the time as local time (Asia/Riyadh), not UTC
                $timezoneName = $scheduledAt->timezone->getName();
                if ($timezoneName === 'UTC' || $timezoneName === '+00:00') {
                    // If timezone is UTC, assume the time value is meant to be in Asia/Riyadh
                    // Use shiftTimezone to change timezone without changing the actual time value
                    $scheduledAt = $scheduledAt->shiftTimezone('Asia/Riyadh');
                }
            } else {
                $dateString = is_string($data['scheduled_at']) ? $data['scheduled_at'] : '';

                // Check if string contains timezone info (ISO 8601 with timezone offset or timezone name)
                $hasTimezone = preg_match('/[+-]\d{2}:?\d{2}$|[A-Z]{3,4}$/', $dateString) ||
                              preg_match('/T.*[+-]\d{2}:?\d{2}/', $dateString);

                if ($hasTimezone) {
                    // String has timezone, parse it directly
                    $scheduledAt = Carbon::parse($dateString);
                } else {
                    // No timezone in string - assume it's in Asia/Riyadh (UTC+3)
                    // Parse the date and explicitly set timezone to Asia/Riyadh
                    try {
                        // Try common date formats
                        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateString)) {
                            $scheduledAt = Carbon::createFromFormat('Y-m-d H:i:s', $dateString, 'Asia/Riyadh');
                        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dateString)) {
                            $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $dateString, 'Asia/Riyadh');
                        } else {
                            // Fallback: parse then set timezone
                            $scheduledAt = Carbon::parse($dateString, 'Asia/Riyadh');
                        }
                    } catch (\Exception $e) {
                        // Final fallback
                        $scheduledAt = Carbon::parse($dateString)->setTimezone('Asia/Riyadh');
                    }
                }
            }

            // Ensure scheduled_at is in Asia/Riyadh timezone
            // We'll save it directly in Asia/Riyadh timezone (Laravel will still convert to UTC in DB, but we ensure the value is correct)
            $scheduledAtLocal = $scheduledAt->copy()->setTimezone('Asia/Riyadh');

            // Use local timezone for validation to match availability windows
                // Use duration from request if provided, otherwise use original
                $durationMinutes = $data['duration_minutes'] ?? $originalDuration;

            $this->validateMentorAvailability(
                    $mentor,
                $scheduledAtLocal,
                    $durationMinutes, // Use duration from request if provided
                $session->id
            );

            // Update the data array with the time as string in Asia/Riyadh timezone
            // Laravel will parse it and convert to UTC for database storage
            $data['scheduled_at'] = $scheduledAtLocal->format('Y-m-d H:i:s');

                // Update session
                $session->update($data);

                // Refresh session to ensure scheduled_at is properly cast
                $session->refresh();

                // Log reschedule activity for audit tracking
                if ($session->participant) {
                    activity('mentor_session')
                        ->performedOn($session)
                        ->causedBy($session->participant)
                        ->event('session_rescheduled')
                        ->withProperties([
                            'session_id' => $session->id,
                            'old_scheduled_at' => $originalScheduledAt ? $originalScheduledAt->format('Y-m-d H:i:s') : null,
                            'new_scheduled_at' => $scheduledAtLocal->format('Y-m-d H:i:s'),
                            'mentor_id' => $session->mentor_id,
                            'participant_id' => $session->participant_id,
                        ])
                        ->log('Session rescheduled by participant');
                }

                // Update video meeting if it exists
                $resolvedVideoTool = $this->videoToolService->resolveVideoToolForMentor($session->mentor_id);
                if ($session->meeting_id && $resolvedVideoTool) {
                    try {
                        $this->videoToolService->updateSession($session);
                    } catch (\Exception $e) {
                        // Failed to update video meeting
                    }
                }

                // Prepare changes array for notifications
                $changes = [];
                if (isset($data['scheduled_at'])) {
                    $changes['scheduled_at'] = [
                        'old' => $originalScheduledAt,
                        'new' => $scheduledAtLocal,
                    ];
                }
                if (isset($data['duration_minutes']) && $data['duration_minutes'] != $originalDuration) {
                    $changes['duration_minutes'] = [
                        'old' => $originalDuration,
                        'new' => $durationMinutes,
                    ];
                }
                if (isset($data['title']) && $data['title'] != $originalTitle) {
                    $changes['title'] = [
                        'old' => $originalTitle,
                        'new' => $data['title'],
                    ];
                }

                // Store session ID and changes for notification after commit
                $sessionId = $session->id;
                $sessionChanges = $changes;
                $notificationLocale = $locale;

                // Send update notifications after transaction commits to ensure data is persisted
                DB::afterCommit(function () use ($sessionId, $sessionChanges, $notificationLocale) {
                    $freshSession = MentorSession::with(['mentor', 'participant', 'competition'])->find($sessionId);
                    if ($freshSession) {
                        $this->sendSessionUpdateNotifications($freshSession, $sessionChanges, $notificationLocale);
                    }
                });

                return $session->fresh();
            });
        }

        // If not changing time, no locking needed
        // Still track changes for notifications
        $changes = [];
        $originalTitle = $session->title;
        $originalDuration = $session->duration_minutes;

        if (isset($data['title']) && $data['title'] != $originalTitle) {
            $changes['title'] = [
                'old' => $originalTitle,
                'new' => $data['title'],
            ];
        }
        if (isset($data['duration_minutes']) && $data['duration_minutes'] != $originalDuration) {
            $changes['duration_minutes'] = [
                'old' => $originalDuration,
                'new' => $data['duration_minutes'],
            ];
        }

        $session->update($data);

        // Update video meeting if it exists
        $resolvedVideoTool = $this->videoToolService->resolveVideoToolForMentor($session->mentor_id);
        if ($session->meeting_id && $resolvedVideoTool) {
            try {
                $this->videoToolService->updateSession($session);
            } catch (\Exception $e) {
                // Failed to update video meeting
            }
        }

        // Send update notifications with changes
        $this->sendSessionUpdateNotifications($session, $changes, $locale);

        return $session->fresh();
    }

    /**
     * Cancel a session.
     */
    public function cancelSession(MentorSession $session, string $reason = null, ?string $locale = null, string $cancelledBy = 'participant'): MentorSession
    {
        // Delete video meeting if it exists
        if ($session->meeting_id) {
            try {
                $this->videoToolService->deleteSession($session);
            } catch (\Exception $e) {
                // Failed to delete video meeting
            }
        }

        // Determine who cancelled the session
        $isCancelledByMentor = $cancelledBy === 'mentor' || (Auth::check() && Auth::id() === $session->mentor_id);

        // Update session status (activity logging handled by LogsActivity trait)
        $updateData = [
            'status' => 'cancelled',
        ];

        // If cancelled by mentor, save reason in declined_reason, otherwise in cancellation_reason
        if ($isCancelledByMentor) {
            $updateData['declined_reason'] = $reason;
        } else {
            $updateData['cancellation_reason'] = $reason;
        }

        $session->update($updateData);

        // Log cancellation activity
        $cancelledByUser = $isCancelledByMentor ? $session->mentor : $session->participant;
        activity('mentor_session')
            ->performedOn($session)
            ->causedBy($cancelledByUser ?? $session->participant ?? $session->mentor)
            ->event('session_cancelled')
            ->withProperties([
                'session_id' => $session->id,
                'scheduled_at' => $session->scheduled_at ? $session->scheduled_at->format('Y-m-d H:i:s') : null,
                'cancellation_reason' => $reason,
                'cancelled_by' => $cancelledBy,
                'mentor_id' => $session->mentor_id,
                'participant_id' => $session->participant_id,
            ])
            ->log($isCancelledByMentor ? 'Session cancelled by mentor' : 'Session cancelled by participant');

        // Send cancellation notifications
        $this->sendSessionCancellationNotifications($session, $reason, $locale);

        return $session->fresh();
    }

    /**
     * Start a session.
     */
    public function startSession(MentorSession $session): MentorSession
    {
        if (!$session->isUpcoming()) {
            throw new \Exception("Cannot start a session that is not scheduled or confirmed");
        }

        $session->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return $session->fresh();
    }

    /**
     * End a session.
     */
    public function endSession(MentorSession $session): MentorSession
    {
        if (!$session->isInProgress()) {
            throw new \Exception("Cannot end a session that is not in progress");
        }

        $session->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        return $session->fresh();
    }

    /**
     * Mark session as no show.
     */
    public function markNoShow(MentorSession $session): MentorSession
    {
        if (!$session->isUpcoming()) {
            throw new \Exception("Cannot mark a session as no show if it's not scheduled or confirmed");
        }

        $session->update([
            'status' => 'no_show',
            'ended_at' => now(),
        ]);

        return $session->fresh();
    }

    /**
     * Get available time slots for a mentor on a specific date.
     *
     * @param int $mentorId The mentor ID
     * @param string $date The date to get slots for (Y-m-d format)
     * @param int $durationMinutes Duration of the session in minutes
     * @param int|null $excludeSessionId Optional session ID to exclude from conflict checking (useful when rescheduling)
     * @param string|null $day Optional day name filter (e.g., "Saturday", "Monday")
     */
    public function getAvailableSlots(int $mentorId, string $date, int $durationMinutes = 30, ?int $excludeSessionId = null, ?string $day = null): array
    {
        $mentor = Mentor::findOrFail($mentorId);
        $requestedDate = Carbon::parse($date);
        $dateString = $requestedDate->format('Y-m-d');

        // Get mentor's availability for this date
        $availabilities = MentorAvailability::getAvailableSlotsForDate($mentorId, $requestedDate);

        if ($availabilities->isEmpty()) {
            return [];
        }

        $availableSlots = [];
        $seenKeys = []; // Track unique slots to avoid duplicates

        // Day name mapping
        $dayNameMap = [
            'sunday' => 'Sunday',
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
        ];

        foreach ($availabilities as $availability) {
            // Get the start and end times
            $startTime = $availability->start_time instanceof \Carbon\Carbon
                ? $availability->start_time
                : \Carbon\Carbon::parse($availability->start_time);
            $endTime = $availability->end_time instanceof \Carbon\Carbon
                ? $availability->end_time
                : \Carbon\Carbon::parse($availability->end_time);

            // Calculate time range in minutes
            $startHour = $startTime->hour;
            $startMinute = $startTime->minute;
            $endHour = $endTime->hour;
            $endMinute = $endTime->minute;

            $startTotal = $startHour * 60 + $startMinute;
            $endTotal = $endHour * 60 + $endMinute;

            // Determine if this is a recurring slot and get day name
            $dayName = null;
            if ($availability->is_recurring && $availability->day_of_week) {
                $dayOfWeek = strtolower($availability->day_of_week);
                $dayName = $dayNameMap[$dayOfWeek] ?? ucfirst($dayOfWeek);
            }

            // Generate slots within this availability window (divide into duration chunks)
            $cursor = $startTotal;
            while (($cursor + $durationMinutes) <= $endTotal) {
                $slotStartH = str_pad((string) floor($cursor / 60), 2, '0', STR_PAD_LEFT);
                $slotStartM = str_pad((string) ($cursor % 60), 2, '0', STR_PAD_LEFT);

                $slotEnd = $cursor + $durationMinutes;
                $slotEndH = str_pad((string) floor($slotEnd / 60), 2, '0', STR_PAD_LEFT);
                $slotEndM = str_pad((string) ($slotEnd % 60), 2, '0', STR_PAD_LEFT);

                $slotStartTime = $slotStartH . ':' . $slotStartM;
                $slotEndTime = $slotEndH . ':' . $slotEndM;

                // Create full datetime for conflict checking
                $slotStartDateTime = $requestedDate->copy()->setTime($slotStartH, $slotStartM, 0);
                $slotEndDateTime = $requestedDate->copy()->setTime($slotEndH, $slotEndM, 0);

                // Skip past slots - only show future slots
                if ($slotStartDateTime->isPast()) {
                    $cursor += $durationMinutes;
                    continue;
                }

                // Check if this slot conflicts with existing sessions
                // We exclude sessions that are cancelled, completed, or no_show
                // A slot conflicts if there's an existing session that overlaps with this slot time range
                // IMPORTANT: This query checks ALL sessions with status scheduled/confirmed/in_progress
                // regardless of participant_id, so the same participant cannot book overlapping slots
                // We explicitly exclude cancelled, completed, and no_show sessions to ensure cancelled slots become available again
                // We force a fresh query to ensure we get the latest data from the database
                $conflictQuery = MentorSession::withoutGlobalScopes()
                    ->where('mentor_id', $mentorId)
                    ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
                    ->whereNotIn('status', ['cancelled', 'completed', 'no_show']) // Explicitly exclude cancelled sessions
                    ->whereNotNull('scheduled_at')
                    ->where(function ($query) use ($slotStartDateTime, $slotEndDateTime) {
                        // A session conflicts with a slot if they overlap in time
                        // Overlap condition: (session_start < slot_end) AND (session_end > slot_start)
                        // This covers all overlap scenarios including exact matches:
                        // 1. Session starts exactly at slot start
                        // 2. Session starts within slot
                        // 3. Session starts before slot but overlaps
                        // 4. Session starts during slot but extends beyond
                        // 5. Session completely contains the slot
                        $query->where(function ($q) use ($slotStartDateTime, $slotEndDateTime) {
                            // Condition: session starts before slot ends AND session ends after slot starts
                            // This ensures we catch ALL overlapping scenarios
                            $q->where('scheduled_at', '<', $slotEndDateTime)
                              ->whereRaw('DATE_ADD(scheduled_at, INTERVAL COALESCE(duration_minutes, 30) MINUTE) > ?', [$slotStartDateTime]);
                        });
                    });

                // Exclude the current session if rescheduling (this allows the session being rescheduled to not block its old slot)
                // This is critical: when rescheduling, we exclude the session being rescheduled so its OLD time slot becomes available
                if ($excludeSessionId) {
                    $conflictQuery->where('id', '!=', $excludeSessionId);
                }

                // Execute the query to check for conflicts
                // Force fresh query execution to ensure we get the latest data from the database
                $conflicts = $conflictQuery->exists();

                if (!$conflicts) {
                    // Create unique key with date, start_time, and end_time
                    $uniqueKey = $dateString . '_' . $slotStartTime . '_' . $slotEndTime;

                    // Only add if we haven't seen this exact slot before
                    if (!isset($seenKeys[$uniqueKey])) {
                        $seenKeys[$uniqueKey] = true;

                        $slot = [
                            'start_time' => $slotStartTime,
                            'end_time' => $slotEndTime,
                            'date' => $dateString,
                            'duration_minutes' => $durationMinutes,
                        ];

                        // Add day name for recurring slots
                        if ($dayName) {
                            $slot['day'] = $dayName;
                        }

                        $availableSlots[] = $slot;
                    }
                }

                $cursor += $durationMinutes;
            }
        }

        // Filter by day if provided
        if ($day) {
            $availableSlots = array_filter($availableSlots, function($slot) use ($day) {
                return isset($slot['day']) && $slot['day'] === $day;
            });
            // Re-index array after filtering
            $availableSlots = array_values($availableSlots);
        }

        // Sort slots by start_time
        usort($availableSlots, function($a, $b) {
            return strcmp($a['start_time'], $b['start_time']);
        });

        return $availableSlots;
    }

    /**
     * Validate mentor availability for a specific time slot.
     *
     * Validates against both availability windows AND generated available slots
     * to ensure the time is actually bookable (not conflicted with existing sessions).
     */
    protected function validateMentorAvailability(
        Mentor $mentor,
        Carbon $scheduledAt,
        int $durationMinutes,
        int $excludeSessionId = null
    ): void {
        $endTime = $scheduledAt->copy()->addMinutes($durationMinutes);
        $dateString = $scheduledAt->format('Y-m-d');

        // First, get the generated available slots - these exclude conflicts
        // Pass excludeSessionId so we don't conflict with the session being rescheduled
        // We generate slots based on the requested duration to find valid start times
        $availableSlots = $this->getAvailableSlots($mentor->id, $dateString, $durationMinutes, $excludeSessionId);

        // Check if the requested start time matches any available slot start time
        // This ensures we only allow times that are actually bookable (considering conflicts and slot boundaries)
        $slotMatches = false;
        foreach ($availableSlots as $slot) {
            // Combine date and time from the slot
            $slotDate = $slot['date'] ?? $dateString;
            $slotStartTimeStr = $slot['start_time']; // Format: "HH:MM"

            // Parse the slot start time with the correct date
            $slotStartTime = Carbon::parse("{$slotDate} {$slotStartTimeStr}:00");

            // Compare the scheduled time with the slot start time
            if ($scheduledAt->format('Y-m-d H:i:s') === $slotStartTime->format('Y-m-d H:i:s')) {
                $slotMatches = true;
                break;
            }
        }

        // If slots exist but requested time doesn't match, reject it
        if (!empty($availableSlots) && !$slotMatches) {
            $slotTimes = array_map(function($slot) {
                // Extract time from slot format (HH:MM)
                return $slot['start_time'] ?? 'N/A';
            }, $availableSlots);
            throw new \Exception(
                "The requested time slot ({$scheduledAt->format('H:i')}) is not available for a {$durationMinutes}-minute session on {$scheduledAt->format('M d, Y')}. " .
                "Available time slots: " . implode(', ', $slotTimes) . ". " .
                "You must select a start time that exactly matches one of these available slot start times."
            );
        }

        // If no slots generated, reject the booking
        // This handles cases where:
        // 1. No availability configured for this date
        // 2. All slots are booked/conflicted
        // 3. The requested time doesn't align with slot boundaries
        if (empty($availableSlots)) {
            $availabilities = MentorAvailability::getAvailableSlotsForDate($mentor->id, $scheduledAt);

            if ($availabilities->isEmpty()) {
                throw new \Exception(
                    "The mentor has no availability configured for {$scheduledAt->format('M d, Y')}."
                );
            }

            // Even if availability exists, if no slots can be generated, reject the booking
            throw new \Exception(
                "No available time slots found for {$scheduledAt->format('M d, Y')}. " .
                "All slots may already be booked or conflicted with existing sessions. " .
                "The requested time ({$scheduledAt->format('H:i')}) does not match any available slot start time."
            );
        }

        // If we reach here, slotMatches must be true (we checked above)
        // Now verify the slot also falls within availability windows as a final check
        $availabilities = MentorAvailability::getAvailableSlotsForDate($mentor->id, $scheduledAt);

        $isAvailable = false;
        foreach ($availabilities as $availability) {
            // Get time string from Carbon instance (cast as datetime)
            $startTimeStr = $availability->start_time instanceof \Carbon\Carbon
                ? $availability->start_time->format('H:i:s')
                : (string)$availability->start_time;
            $endTimeStr = $availability->end_time instanceof \Carbon\Carbon
                ? $availability->end_time->format('H:i:s')
                : (string)$availability->end_time;

            $availabilityStart = $scheduledAt->copy()->setTimeFromTimeString($startTimeStr);
            $availabilityEnd = $scheduledAt->copy()->setTimeFromTimeString($endTimeStr);

            // Session must start at or after availability start, and end at or before availability end
            // Use gte for start (can start exactly at availability start) and lte for end (must end before or at availability end)
            if ($scheduledAt->gte($availabilityStart) && $endTime->lte($availabilityEnd)) {
                $isAvailable = true;
                break;
            }
        }

        // Final check: ensure the time falls within an availability window
        if (!$isAvailable) {
            $availableWindows = [];
            foreach ($availabilities as $availability) {
                $startTimeStr = $availability->start_time instanceof \Carbon\Carbon
                    ? $availability->start_time->format('H:i:s')
                    : (string)$availability->start_time;
                $endTimeStr = $availability->end_time instanceof \Carbon\Carbon
                    ? $availability->end_time->format('H:i:s')
                    : (string)$availability->end_time;
                $availableWindows[] = "{$startTimeStr} - {$endTimeStr}";
            }

            $windowsList = !empty($availableWindows) ? ' Available windows: ' . implode(', ', $availableWindows) : '';
            throw new \Exception("Mentor is not available at the requested time ({$scheduledAt->format('Y-m-d H:i:s')}).{$windowsList}");
        }

        // Check for conflicts with existing sessions
        // Explicitly exclude cancelled, completed, and no_show sessions to ensure cancelled slots become available again
        $conflicts = MentorSession::where('mentor_id', $mentor->id)
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
            ->whereNotIn('status', ['cancelled', 'completed', 'no_show']) // Explicitly exclude cancelled sessions
            ->when($excludeSessionId, function ($query) use ($excludeSessionId) {
                $query->where('id', '!=', $excludeSessionId);
            })
            ->where(function ($query) use ($scheduledAt, $endTime) {
                $query->whereBetween('scheduled_at', [$scheduledAt, $endTime])
                    ->orWhere(function ($q) use ($scheduledAt, $endTime) {
                        $q->where('scheduled_at', '<', $scheduledAt)
                          ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$scheduledAt]);
                    });
            })
            ->exists();

        if ($conflicts) {
            throw new \Exception("Time slot conflicts with an existing session");
        }
    }

    /**
     * Send session notifications.
     */
    protected function sendSessionNotifications(MentorSession $session, ?string $locale = null): void
    {
        try {
            // Ensure relationships are loaded
            if (!$session->relationLoaded('mentor')) {
                $session->load('mentor');
            }
            if (!$session->relationLoaded('participant')) {
                $session->load('participant');
            }
            if (!$session->relationLoaded('competition')) {
                $session->load('competition');
            }

            // Notify mentor about new booking
            if ($session->mentor) {
                try {
                    $notification = new \App\Notifications\Mentor\NewBookingNotification($session);
                    if ($locale) {
                        $notification->locale = $locale;
                    }
                    $session->mentor->notify($notification);
                } catch (\Exception $e) {
                    // Failed to send notification
                }
            }

            // Notify participant when session is booked (scheduled or confirmed)
            if ($session->participant && in_array($session->status, ['scheduled', 'confirmed'])) {
                try {
                    $notification = new ParticipantSessionScheduledNotification($session);
                    if ($locale) {
                        $notification->locale = $locale;
                    }
                    $session->participant->notify($notification);
                } catch (\Exception $e) {
                    // Failed to send notification
                }
            }
        } catch (\Exception $e) {
            // Failed to send notifications
        }
    }

    /**
     * Send session update notifications.
     */
    protected function sendSessionUpdateNotifications(MentorSession $session, array $changes = [], ?string $locale = null): void
    {
        // Notify mentor
        $mentorNotification = new MentorSessionUpdatedNotification($session, $changes);
        if ($locale) {
            $mentorNotification->locale = $locale;
        }
        $session->mentor->notify($mentorNotification);

        // Notify participant - use reschedule notification if there are changes, otherwise use scheduled notification
        if ($session->participant) {
            if (!empty($changes)) {
                $participantNotification = new \App\Notifications\Participant\SessionRescheduledNotification($session, $changes);
            } else {
                $participantNotification = new ParticipantSessionScheduledNotification($session);
            }
            if ($locale) {
                $participantNotification->locale = $locale;
            }
            $session->participant->notify($participantNotification);
        }
    }

    /**
     * Send session cancellation notifications.
     */
    protected function sendSessionCancellationNotifications(MentorSession $session, ?string $reason = null, ?string $locale = null): void
    {
        // Notify mentor
        $mentorNotification = new MentorSessionCancelledNotification($session, $reason);
        if ($locale) {
            $mentorNotification->locale = $locale;
        }
        $session->mentor->notify($mentorNotification);

        // Notify participant
        if ($session->participant) {
            $participantNotification = new MentorSessionCancelledNotification($session, $reason);
            if ($locale) {
                $participantNotification->locale = $locale;
            }
            $session->participant->notify($participantNotification);
        }
    }
}
