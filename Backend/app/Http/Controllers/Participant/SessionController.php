<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\BookSessionRequest;
use App\Http\Requests\Participant\RescheduleSessionRequest;
use App\Http\Resources\MentorSessionResource;
use App\Models\CompetitionApplication;
use App\Models\Mentor;
use App\Models\MentorSession;
use App\Services\SessionSchedulingService;
use App\Services\VideoToolIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    protected SessionSchedulingService $sessionService;
    protected VideoToolIntegrationService $videoToolService;

    public function __construct(SessionSchedulingService $sessionService, VideoToolIntegrationService $videoToolService)
    {
        $this->sessionService = $sessionService;
        $this->videoToolService = $videoToolService;
    }

    /**
     * Get participant's sessions.
     *
     * Supports filtering by category:
     * - 'upcoming': Future sessions that are not canceled
     * - 'past': Past sessions that are not canceled
     * - 'canceled': All canceled sessions regardless of date
     * - No filter: Returns all sessions
     *
     * If application_id is provided, filters sessions for that specific competition.
     * If application_id is not provided, returns sessions from all competitions.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $participant = Auth::user();
            $applicationId = $request->input('application_id');

            // Auto-update expired sessions to no_show before querying
            $this->updateExpiredSessionsForParticipant($participant->id, $applicationId);

            // Query sessions for this participant
            $query = MentorSession::where('participant_id', $participant->id)
                ->with(['mentor', 'competition']);

            // Filter by competition if application_id is provided
            if ($applicationId) {
                $application = CompetitionApplication::find($applicationId);
                if (!$application) {
                    return response()->json([
                        'success' => false,
                        'message' => __('sessions.application_not_found'),
                    ], 404);
                }

                // IDOR Prevention: Verify that the application belongs to the authenticated user
                if ($application->participant_id !== $participant->id) {
                    return response()->json([
                        'success' => false,
                        'message' => __('sessions.application_not_found'),
                    ], 404);
                }

                $competitionId = $application->competition_id;
                $query->where('competition_id', $competitionId);
            }

            // Filter by category (upcoming, past, canceled)
            $category = $request->input('category', $request->input('filter')); // Support both 'category' and 'filter' for backwards compatibility

            if ($category === 'upcoming') {
                // Future sessions with status scheduled or confirmed (not canceled)
                // Check that session hasn't ended yet (scheduled_at + duration_minutes > NOW())
                $query->whereIn('status', ['scheduled', 'confirmed'])
                      ->whereNotNull('scheduled_at')
                      ->whereRaw('DATE_ADD(scheduled_at, INTERVAL COALESCE(duration_minutes, 30) MINUTE) > NOW()');
            } elseif ($category === 'past') {
                // Past sessions that are not canceled
                // Session must have ended (scheduled_at + duration_minutes < NOW())
                $query->whereNotNull('scheduled_at')
                      ->whereRaw('DATE_ADD(scheduled_at, INTERVAL COALESCE(duration_minutes, 30) MINUTE) < NOW()')
                      ->where('status', '!=', 'cancelled');
            } elseif ($category === 'canceled' || $category === 'cancelled') {
                // All canceled sessions regardless of date
                $query->where('status', 'cancelled');
            } elseif ($request->has('status')) {
                // Legacy filter by specific status
                $query->where('status', $request->input('status'));
            }

            // Filter by date range (additional filtering)
            if ($request->has('start_date')) {
                $query->where('scheduled_at', '>=', $request->input('start_date'));
            }

            if ($request->has('end_date')) {
                $query->where('scheduled_at', '<=', $request->input('end_date'));
            }

            // Sort by appropriate field and direction based on category
            if ($category === 'upcoming') {
                // Upcoming: sort ascending (earliest first)
                $query->orderBy('scheduled_at', 'asc');
            } elseif ($category === 'canceled' || $category === 'cancelled') {
                // Canceled: sort by scheduled_at (chronological order)
                $query->orderBy('scheduled_at', 'asc');
            } else {
                // Past or no category: sort ascending (earliest first)
                $query->orderBy('scheduled_at', 'asc');
            }

            $sessions = $query->paginate(15);

            // Prepare response with empty state message if needed
            $response = [
                'success' => true,
                'data' => MentorSessionResource::collection($sessions),
                'pagination' => [
                    'current_page' => $sessions->currentPage(),
                    'last_page' => $sessions->lastPage(),
                    'per_page' => $sessions->perPage(),
                    'total' => $sessions->total(),
                ],
            ];

            // Add empty state message if no sessions found
            if ($sessions->isEmpty()) {
                $message = match($category) {
                    'upcoming' => __('sessions.no_upcoming_sessions_found'),
                    'past' => __('sessions.no_past_sessions_found'),
                    'canceled', 'cancelled' => __('sessions.no_canceled_sessions_found'),
                    default => __('sessions.no_sessions_found'),
                };
                $response['message'] = $message;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.failed_to_get_sessions'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific session.
     */
    public function show(MentorSession $session): JsonResponse
    {
        // Ensure the session belongs to the authenticated participant
        if ($session->participant_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_found'),
            ], 404);
        }

        $session->load(['mentor', 'competition']);

        return response()->json([
            'success' => true,
            'data' => new MentorSessionResource($session),
        ]);
    }

    /**
     * Get sessions for a specific mentor.
     */
    public function indexByMentor(Request $request, $mentorId): JsonResponse
    {
        try {
            $participant = Auth::user();
            $applicationId = $request->input('application_id');

            if (!$applicationId) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.application_id_required'),
                ], 400);
            }

            // Get competition_id from application
            $application = CompetitionApplication::find($applicationId);
            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.application_not_found'),
                ], 404);
            }

            // IDOR Prevention: Verify that the application belongs to the authenticated user
            if ($application->participant_id !== $participant->id) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.application_not_found'),
                ], 404);
            }

            $competitionId = $application->competition_id;

            // Fetch mentor with visibility and approval constraints
            $mentor = Mentor::query()
                ->where('id', $mentorId)
                ->where('is_visible', true)
                ->where('status', 'approved')
                ->whereHas('competitions', function ($q) use ($competitionId) {
                    $q->where('competitions.id', $competitionId);
                })
                ->active()
                ->first();

            if (!$mentor) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.mentor_not_found'),
                ], 404);
            }

            // Query sessions for this participant with this mentor in this competition
            $query = MentorSession::where('participant_id', $participant->id)
                ->where('mentor_id', $mentor->id)
                ->where('competition_id', $competitionId)
                ->with(['mentor', 'competition']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            $sessions = $query->orderBy('scheduled_at', 'asc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => MentorSessionResource::collection($sessions),
                'pagination' => [
                    'current_page' => $sessions->currentPage(),
                    'last_page' => $sessions->lastPage(),
                    'per_page' => $sessions->perPage(),
                    'total' => $sessions->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.failed_to_get_sessions'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available time slots for a mentor.
     */
    public function getAvailableSlots(Request $request, $mentorId): JsonResponse
    {
        $request->validate([
            'date' => 'required_without:day|date',
            'duration_minutes' => 'integer|min:15|max:480',
            'application_id' => 'required|exists:competition_applications,id',
            'day' => 'required_without:date|string|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
        ]);

        try {
            $participant = Auth::user();
            $applicationId = $request->input('application_id');
            $application = CompetitionApplication::findOrFail($applicationId);
            
            // IDOR Prevention: Verify that the application belongs to the authenticated user
            if ($application->participant_id !== $participant->id) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.application_not_found'),
                ], 404);
            }
            
            $competitionId = $application->competition_id;

            // Fetch mentor with visibility and approval constraints
            $mentor = Mentor::query()
                ->where('id', $mentorId)
                ->where('is_visible', true)
                ->where('status', 'approved')
                ->whereHas('competitions', function ($q) use ($competitionId) {
                    $q->where('competitions.id', $competitionId);
                })
                ->active()
                ->first();

            if (!$mentor) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.mentor_not_found'),
                ], 404);
            }

            $date = $request->input('date');
            $durationMinutes = $request->input('duration_minutes', 30);
            $day = $request->input('day');

            // If only day is provided, find the next occurrence of that day
            if (!$date && $day) {
                $dayNameMap = [
                    'Sunday' => \Carbon\Carbon::SUNDAY,
                    'Monday' => \Carbon\Carbon::MONDAY,
                    'Tuesday' => \Carbon\Carbon::TUESDAY,
                    'Wednesday' => \Carbon\Carbon::WEDNESDAY,
                    'Thursday' => \Carbon\Carbon::THURSDAY,
                    'Friday' => \Carbon\Carbon::FRIDAY,
                    'Saturday' => \Carbon\Carbon::SATURDAY,
                ];

                $dayOfWeek = $dayNameMap[$day] ?? null;
                if ($dayOfWeek !== null) {
                    $startDate = now()->startOfDay();
                    // Find the next occurrence of this day (within next 7 days)
                    for ($i = 0; $i < 7; $i++) {
                        $checkDate = $startDate->copy()->addDays($i);
                        if ($checkDate->dayOfWeek === $dayOfWeek) {
                            $date = $checkDate->format('Y-m-d');
                            break;
                        }
                    }
                }
            }

            // If date is still not set, return error
            if (!$date) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.invalid_date_or_day', [], 'en') ?: 'Either date or day must be provided',
                ], 400);
            }

            // Validate that the date is not in the past
            $requestedDateObj = \Carbon\Carbon::parse($date);
            if ($requestedDateObj->isPast() && $requestedDateObj->format('Y-m-d') !== now()->format('Y-m-d')) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.past_date_not_allowed', [], 'en') ?: 'Cannot book sessions in the past. Please select a future date.',
                    'date' => $date,
                ], 400);
            }

            $slots = $this->sessionService->getAvailableSlots($mentor->id, $date, $durationMinutes, null, $day);

            // Filter out any past slots (additional safety check)
            $slots = array_filter($slots, function($slot) {
                $slotDate = $slot['date'] ?? null;
                $slotStartTime = $slot['start_time'] ?? null;

                if (!$slotDate || !$slotStartTime) {
                    return false;
                }

                try {
                    $slotDateTime = \Carbon\Carbon::parse("{$slotDate} {$slotStartTime}:00");
                    return !$slotDateTime->isPast();
                } catch (\Exception $e) {
                    return false;
                }
            });

            // Re-index array after filtering
            $slots = array_values($slots);

            return response()->json([
                'success' => true,
                'data' => $slots,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.failed_to_get_slots'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Book a new session with a mentor (via mentor route).
     */
    public function store(BookSessionRequest $request, $mentorId): JsonResponse
    {
        try {
            $participant = Auth::user();
            $data = $request->validated();

            // Get competition_id from application
            $application = CompetitionApplication::findOrFail($data['application_id']);
            
            // IDOR Prevention: Verify that the application belongs to the authenticated user
            if ($application->participant_id !== $participant->id) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.application_not_found'),
                ], 404);
            }
            
            $data['competition_id'] = $application->competition_id;
            $competitionId = $application->competition_id;

            // Fetch mentor with visibility and approval constraints
            $mentor = Mentor::query()
                ->where('id', $mentorId)
                ->where('is_visible', true)
                ->where('status', 'approved')
                ->whereHas('competitions', function ($q) use ($competitionId) {
                    $q->where('competitions.id', $competitionId);
                })
                ->active()
                ->first();

            if (!$mentor) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.mentor_not_found'),
                ], 404);
            }

            // Set participant_id from authenticated user
            $data['participant_id'] = $participant->id;
            // Set mentor_id from route parameter
            $data['mentor_id'] = $mentor->id;

            // Pre-validate: Check if the requested slot is actually available
            // This provides better error messages before calling the service
            // Ensure scheduled_at is a Carbon instance with correct timezone
            if ($data['scheduled_at'] instanceof \Carbon\Carbon) {
                $scheduledAt = $data['scheduled_at']->copy();
            } else {
                // Parse with Asia/Riyadh timezone if no timezone is provided
                $dateString = is_string($data['scheduled_at']) ? $data['scheduled_at'] : '';
                $hasTimezone = preg_match('/[+-]\d{2}:?\d{2}$|[A-Z]{3,4}$/', $dateString) ||
                              preg_match('/T.*[+-]\d{2}:?\d{2}/', $dateString);

                if ($hasTimezone) {
                    $scheduledAt = \Carbon\Carbon::parse($dateString);
                } else {
                    // No timezone - assume Asia/Riyadh
                    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateString)) {
                        $scheduledAt = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateString, 'Asia/Riyadh');
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dateString)) {
                        $scheduledAt = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $dateString, 'Asia/Riyadh');
                    } else {
                        $scheduledAt = \Carbon\Carbon::parse($dateString, 'Asia/Riyadh');
                    }
                }
            }
            $dateString = $scheduledAt->format('Y-m-d');
            $durationMinutes = $data['duration_minutes'] ?? 30;

            // Get available slots for this date
            $availableSlots = $this->sessionService->getAvailableSlots($mentor->id, $dateString, $durationMinutes);

            // If no slots available at all, provide detailed error message
            if (empty($availableSlots)) {
                // Check if mentor has any availability configured
                $hasAnyAvailability = \App\Models\MentorAvailability::where('mentor_id', $mentor->id)
                    ->where('is_active', true)
                    ->exists();

                if (!$hasAnyAvailability) {
                    return response()->json([
                        'success' => false,
                        'message' => __('sessions.mentor_no_availability_configured'),
                        'error' => 'The mentor has not configured any availability slots yet. Please contact the mentor or try another mentor.',
                    ], 404);
                }

                // Check what day of week this is
                $dayOfWeek = strtolower($scheduledAt->format('l')); // e.g., 'friday'

                // Check if mentor has availability for this day of week
                // Use the same method as getAvailableSlotsForDate to check availability
                // This ensures consistency with how slots are generated
                $availabilities = \App\Models\MentorAvailability::getAvailableSlotsForDate($mentor->id, $dateString);
                $hasDayAvailability = $availabilities->isNotEmpty();

                if (!$hasDayAvailability) {
                    return response()->json([
                        'success' => false,
                        'message' => __('sessions.no_slots_available_for_date'),
                        'error' => "The mentor does not have availability configured for {$scheduledAt->format('l, M d, Y')}. Please select a different date or contact the mentor.",
                        'date' => $dateString,
                        'day_of_week' => $dayOfWeek,
                    ], 404);
                }

                // If availability exists but no slots are returned, they might all be booked
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.no_slots_available_for_date'),
                    'error' => "No available time slots found for {$scheduledAt->format('M d, Y')}. All slots may already be booked, or there may be conflicts with existing sessions. Please try a different date or time.",
                    'date' => $dateString,
                    'day_of_week' => $dayOfWeek,
                ], 409);
            }

            // Check if the requested time slot exists in available slots
            $requestedTime = $scheduledAt;
            $slotFound = false;

            foreach ($availableSlots as $slot) {
                // Combine date and time from the slot
                $slotDate = $slot['date'] ?? $dateString;
                $slotStartTimeStr = $slot['start_time']; // Format: "HH:MM"

                // Parse the slot start time with the correct date and timezone (Asia/Riyadh)
                $slotStartTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', "{$slotDate} {$slotStartTimeStr}:00", 'Asia/Riyadh');

                // Check if requested time EXACTLY matches the slot start time
                // Compare in the same timezone
                if ($requestedTime->setTimezone('Asia/Riyadh')->format('Y-m-d H:i:s') === $slotStartTime->format('Y-m-d H:i:s')) {
                    // Use the exact slot time to ensure consistency (pass Carbon instance with timezone)
                    $data['scheduled_at'] = $slotStartTime;
                    $slotFound = true;
                    break;
                }
            }

            if (!$slotFound) {
                // Provide helpful error message with available slots
                $availableTimes = array_map(function($slot) {
                    // Extract time from slot format (HH:MM)
                    return $slot['start_time'] ?? 'N/A';
                }, $availableSlots);

                return response()->json([
                    'success' => false,
                    'message' => __('sessions.slot_no_longer_available'),
                    'error' => "The requested time slot ({$scheduledAt->format('H:i')}) is not available for a {$durationMinutes}-minute session on {$scheduledAt->format('M d, Y')}. Available time slots: " . (count($availableTimes) > 0 ? implode(', ', $availableTimes) : 'none'),
                    'available_slots' => $availableSlots,
                    'requested_time' => $scheduledAt->format('Y-m-d H:i:s'),
                    'requested_duration_minutes' => $durationMinutes,
                ], 409);
            }

            // Remove application_id as it's not needed in session drop table
            unset($data['application_id']);

            // Check if mentor has valid video tool before creating session
            // Use resolveVideoToolForMentor to support global accounts (Google Meet)
            $defaultVideoTool = $this->videoToolService->resolveVideoToolForMentor($mentor->id);

            // Check if video tool exists and is active
            if (!$defaultVideoTool || !$defaultVideoTool->is_active || !$defaultVideoTool->access_token) {
                $errorMessage = 'The mentor has not configured any valid meeting tools (Zoom, Teams, Google Meet, etc.). Please contact the mentor to set up a meeting tool before booking a session.';

                // If global account is enabled but not found, provide more specific error
                if (config('video_tools.google.use_global_account', false)) {
                    $globalEmail = config('video_tools.google.default_account_email');
                    $errorMessage = "Global Google Meet account is enabled but not configured properly. Please contact the administrator to ensure the account email '{$globalEmail}' is authorized and active.";
                }

                return response()->json([
                    'success' => false,
                    'message' => __('sessions.mentor_no_video_tools_configured'),
                    'error' => $errorMessage,
                ], 400);
            }

            // Capture locale from request header
            $locale = $request->header('Accept-Language', 'en');
            // Normalize locale (e.g., 'ar-SA' -> 'ar', 'ar,en' -> 'ar')
            $locale = substr($locale, 0, 2);
            $locale = in_array($locale, ['en', 'ar']) ? $locale : 'en';

            // Schedule the session (this will validate availability and prevent double-booking)
            $session = $this->sessionService->scheduleSession($data, $locale);

            return response()->json([
                'success' => true,
                'message' => __('sessions.session_booked_successfully'),
                'data' => new MentorSessionResource($session->load(['mentor', 'competition'])),
            ], 201);

        } catch (\Exception $e) {
            // Check if it's an availability/conflict error
            if (str_contains($e->getMessage(), 'not available') || str_contains($e->getMessage(), 'conflicts')) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.slot_no_longer_available'),
                    'error' => $e->getMessage(),
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => __('sessions.failed_to_book_session'),
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reschedule a session (update scheduled time).
     *
     * Allows participants to reschedule their upcoming sessions to a new date/time.
     */
    public function reschedule(RescheduleSessionRequest $request, MentorSession $session): JsonResponse
    {
        // Ensure the session belongs to the authenticated participant
        if ($session->participant_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_found'),
            ], 404);
        }

        // Only allow rescheduling of upcoming sessions
        if (!$session->isUpcoming()) {
            // Check if already cancelled
            if ($session->isCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.cannot_reschedule_cancelled_booking'),
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => __('sessions.can_only_reschedule_upcoming_sessions'),
            ], 400);
        }

        try {
            $data = $request->validated();

            // Pre-validate: Check if the requested slot is actually available
            // Ensure scheduled_at is a Carbon instance with correct timezone
            if ($data['scheduled_at'] instanceof \Carbon\Carbon) {
                $scheduledAt = $data['scheduled_at']->copy();
            } else {
                // Parse with Asia/Riyadh timezone if no timezone is provided
                $dateString = is_string($data['scheduled_at']) ? $data['scheduled_at'] : '';
                $hasTimezone = preg_match('/[+-]\d{2}:?\d{2}$|[A-Z]{3,4}$/', $dateString) ||
                              preg_match('/T.*[+-]\d{2}:?\d{2}/', $dateString);

                if ($hasTimezone) {
                    $scheduledAt = \Carbon\Carbon::parse($dateString);
                } else {
                    // No timezone - assume Asia/Riyadh
                    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateString)) {
                        $scheduledAt = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateString, 'Asia/Riyadh');
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dateString)) {
                        $scheduledAt = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $dateString, 'Asia/Riyadh');
                    } else {
                        $scheduledAt = \Carbon\Carbon::parse($dateString, 'Asia/Riyadh');
                    }
                }
            }
            $dateString = $scheduledAt->format('Y-m-d');
            $durationMinutes = $data['duration_minutes'] ?? $session->duration_minutes ?? 30;

            // Get available slots for this date, excluding the current session being rescheduled
            $availableSlots = $this->sessionService->getAvailableSlots($session->mentor_id, $dateString, $durationMinutes, $session->id);

            // If no slots available at all, provide detailed error message
            if (empty($availableSlots)) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.no_slots_available_for_date'),
                    'error' => "No available time slots found for {$scheduledAt->format('M d, Y')}. All slots may already be booked, or there may be conflicts with existing sessions. Please try a different date or time.",
                    'date' => $dateString,
                ], 409);
            }

            // Check if the requested time slot exists in available slots (exact match only)
            $requestedTime = $scheduledAt;
            $slotFound = false;

            foreach ($availableSlots as $slot) {
                // Combine date and time from the slot
                $slotDate = $slot['date'] ?? $dateString;
                $slotStartTimeStr = $slot['start_time']; // Format: "HH:MM"

                // Parse the slot start time with the correct date and timezone (Asia/Riyadh)
                $slotStartTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', "{$slotDate} {$slotStartTimeStr}:00", 'Asia/Riyadh');

                // Check if requested time EXACTLY matches the slot start time
                // Compare in the same timezone
                if ($requestedTime->setTimezone('Asia/Riyadh')->format('Y-m-d H:i:s') === $slotStartTime->format('Y-m-d H:i:s')) {
                    // Use the exact slot time to ensure consistency (pass Carbon instance with timezone)
                    $data['scheduled_at'] = $slotStartTime;
                    $slotFound = true;
                    break;
                }
            }

            if (!$slotFound) {
                // Provide helpful error message with available slots
                $availableTimes = array_map(function($slot) {
                    return $slot['start_time']; // Already in "HH:MM" format
                }, $availableSlots);

                return response()->json([
                    'success' => false,
                    'message' => __('sessions.slot_no_longer_available'),
                    'error' => __('sessions.please_select_valid_slot'),
                    'available_slots' => $availableSlots,
                    'requested_time' => $scheduledAt->format('Y-m-d H:i:s'),
                ], 409);
            }

            // Capture locale from request header
            $locale = $request->header('Accept-Language', 'en');
            // Normalize locale (e.g., 'ar-SA' -> 'ar', 'ar,en' -> 'ar')
            $locale = substr($locale, 0, 2);
            $locale = in_array($locale, ['en', 'ar']) ? $locale : 'en';

            // Update the session using the service (handles validation and notifications)
            $updatedSession = $this->sessionService->updateSession($session, $data, $locale);

            return response()->json([
                'success' => true,
                'message' => __('sessions.session_rescheduled_successfully'),
                'data' => new MentorSessionResource($updatedSession->load(['mentor', 'competition'])),
            ]);

        } catch (\Exception $e) {
            // Check if it's an availability/conflict error
            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, 'not available') ||
                str_contains($errorMessage, 'conflicts') ||
                str_contains($errorMessage, 'does not match') ||
                str_contains($errorMessage, 'No available time slots') ||
                str_contains($errorMessage, 'no availability configured')) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.slot_no_longer_available'),
                    'error' => $errorMessage,
                    'available_slots' => $availableSlots ?? [],
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => __('sessions.error_occurred'),
                'error' => $errorMessage,
            ], 500);
        }
    }

    /**
     * Cancel a session.
     *
     * Allows participants to cancel their upcoming sessions.
     */
    public function cancel(Request $request, MentorSession $session): JsonResponse
    {
        // Ensure the session belongs to the authenticated participant
        if ($session->participant_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_found'),
            ], 404);
        }

        // Prevent double cancellation
        if ($session->isCancelled()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.booking_already_cancelled'),
            ], 400);
        }

        // Only allow cancellation of upcoming sessions
        if (!$session->isUpcoming()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.cannot_cancel_booking'),
            ], 400);
        }

        // Validate cancellation reason if provided
        $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $reason = $request->input('reason');

            // Capture locale from request header
            $locale = $request->header('Accept-Language', 'en');
            // Normalize locale (e.g., 'ar-SA' -> 'ar', 'ar,en' -> 'ar')
            $locale = substr($locale, 0, 2);
            $locale = in_array($locale, ['en', 'ar']) ? $locale : 'en';

            $session = $this->sessionService->cancelSession($session, $reason, $locale);

            return response()->json([
                'success' => true,
                'message' => __('sessions.session_cancelled'),
                'data' => new MentorSessionResource($session->load(['mentor', 'competition'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.error_occurred'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update expired sessions to no_show status for the participant.
     * This runs automatically before fetching sessions to ensure data is up-to-date.
     */
    protected function updateExpiredSessionsForParticipant(int $participantId, ?int $applicationId = null): void
    {
        try {
            // Build query for expired sessions
            $query = MentorSession::where('participant_id', $participantId)
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->whereNotNull('scheduled_at')
                ->whereRaw('DATE_ADD(scheduled_at, INTERVAL COALESCE(duration_minutes, 30) MINUTE) < NOW()');

            // Filter by competition if application_id is provided
            if ($applicationId) {
                $application = CompetitionApplication::find($applicationId);
                if ($application) {
                    $query->where('competition_id', $application->competition_id);
                }
            }

            $expiredSessions = $query->get();

            foreach ($expiredSessions as $session) {
                try {
                    // Store the original status before updating
                    $previousStatus = $session->status;

                    // Calculate the end time
                    $endTime = $session->scheduled_at->copy()->addMinutes($session->duration_minutes ?? 30);

                    // Update session status to no_show
                    $session->update([
                        'status' => 'no_show',
                        'ended_at' => $endTime,
                    ]);

                    // Log the status change
                    activity('mentor_session')
                        ->performedOn($session)
                        ->event('session_auto_marked_no_show')
                        ->withProperties([
                            'session_id' => $session->id,
                            'scheduled_at' => $session->scheduled_at->format('Y-m-d H:i:s'),
                            'end_time' => $endTime->format('Y-m-d H:i:s'),
                            'previous_status' => $previousStatus,
                            'new_status' => 'no_show',
                            'mentor_id' => $session->mentor_id,
                            'participant_id' => $session->participant_id,
                        ])
                        ->log('Session automatically marked as no_show after end time passed');

                } catch (\Exception $e) {
                    // Failed to auto-update session status
                }
            }
        } catch (\Exception $e) {
            // Failed to update expired sessions
            // Don't throw - we don't want to break the API call if this fails
        }
    }
}

