<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mentor\SessionRequest;
use App\Http\Resources\MentorSessionResource;
use App\Http\Requests\Mentor\ProvideFeedbackRequest;
use App\Http\Requests\Mentor\AcceptSessionRequest;
use App\Http\Requests\Mentor\DeclineSessionRequest;
use App\Http\Requests\Mentor\ProposeNewTimeRequest;
use App\Models\MentorSession;
use App\Models\User;
use App\Services\SessionSchedulingService;
use App\Services\VideoToolIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
     * Get mentor's sessions.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $mentor = Auth::user();
            $query = $mentor->sessions()->with(['participant', 'program']);

            // Filter by status
            if ($request->has('status')) {
                $status = $request->input('status');
                if ($status === 'scheduled') {
                    // When filtering by 'scheduled', return both 'scheduled' and 'confirmed'
                    $query->whereIn('status', ['scheduled', 'confirmed']);
                } else {
                    $query->where('status', $status);
                }
            }

            // Filter by date range
            if ($request->has('start_date')) {
                $query->where('scheduled_at', '>=', $request->input('start_date'));
            }

            if ($request->has('end_date')) {
                $query->where('scheduled_at', '<=', $request->input('end_date'));
            }

            // Filter by video tool
            if ($request->has('video_tool')) {
                $query->where('video_tool', $request->input('video_tool'));
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
     * Get mentor's session history (segmented by status).
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $mentor = Auth::user();
            $baseQuery = $mentor->sessions()->with(['participant', 'program']);

            // Get category filter (upcoming, past, canceled)
            // Support both 'category' and 'status' parameters for backward compatibility
            // If 'status' is a category value (upcoming, past, canceled), treat it as category
            $statusParam = $request->input('status');
            $categoryParam = $request->input('category', 'all');

            // Determine category: if status is a category value, use it; otherwise use category param
            $category = 'all';
            if (in_array($statusParam, ['upcoming', 'past', 'canceled'])) {
                $category = $statusParam;
            } elseif ($categoryParam !== 'all') {
                $category = $categoryParam;
            }

            // Apply category filter
            switch ($category) {
                case 'upcoming':
                    // Display upcoming sessions (scheduled/confirmed with end time in future)
                    $query = (clone $baseQuery)->upcoming();
                    break;
                case 'past':
                    $query = (clone $baseQuery)->past();
                    break;
                case 'canceled':
                    $query = (clone $baseQuery)->canceled();
                    break;
                default:
                    $query = clone $baseQuery;
                    break;
            }

            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('scheduled_at', '>=', $request->input('start_date'));
            }

            if ($request->has('end_date')) {
                $query->whereDate('scheduled_at', '<=', $request->input('end_date'));
            }

            // Filter by status (additional filter) - only if status is not a category value
            if ($request->has('status') && !in_array($statusParam, ['upcoming', 'past', 'canceled'])) {
                $query->where('status', $request->input('status'));
            }

            // Sort by date (ascending or descending)
            $sortDirection = $request->input('sort', 'asc'); // asc or desc
            $query->orderBy('scheduled_at', $sortDirection);

            // Pagination
            $perPage = $request->input('per_page', 15);
            $sessions = $query->paginate($perPage);

            // Get counts for each category
            $upcomingCount = (clone $baseQuery)->upcoming()->count();
            $pastCount = (clone $baseQuery)->past()->count();
            $canceledCount = (clone $baseQuery)->canceled()->count();

            return response()->json([
                'success' => true,
                'data' => MentorSessionResource::collection($sessions),
                'pagination' => [
                    'current_page' => $sessions->currentPage(),
                    'last_page' => $sessions->lastPage(),
                    'per_page' => $sessions->perPage(),
                    'total' => $sessions->total(),
                ],
                'categories' => [
                    'upcoming' => $upcomingCount,
                    'past' => $pastCount,
                    'canceled' => $canceledCount,
                ],
                'filters' => [
                    'category' => $category,
                    'start_date' => $request->input('start_date'),
                    'end_date' => $request->input('end_date'),
                    'status' => $request->input('status'),
                    'sort' => $sortDirection,
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
     * Get a specific session.
     */
    public function show(MentorSession $session): JsonResponse
    {
        // Ensure the session belongs to the authenticated mentor
        if ($session->mentor_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_found'),
            ], 404);
        }

        $session->load(['participant', 'program']);

        return response()->json([
            'success' => true,
            'data' => new MentorSessionResource($session),
        ]);
    }

    /**
     * Schedule a new session.
     */
    public function store(SessionRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['mentor_id'] = Auth::id();

            // Capture locale from request header
            $locale = $request->header('Accept-Language', 'en');
            $locale = in_array($locale, ['en', 'ar']) ? $locale : 'en';

            // Check if mentor has valid video tool before creating session
            $mentor = Auth::user();

            // Get default video tool using resolveVideoToolForMentor to support global accounts
            // This will use global Google Meet account if GOOGLE_MEET_USE_GLOBAL_ACCOUNT is enabled
            $defaultVideoTool = $this->videoToolService->resolveVideoToolForMentor($mentor->id);

            // Check if video tool exists and is active
            if (!$defaultVideoTool || !$defaultVideoTool->is_active || !$defaultVideoTool->access_token) {
                $errorMessage = 'You have not configured any valid meeting tools (Zoom, Teams, Google Meet, etc.). Please configure at least one meeting tool before scheduling sessions.';
                
                // If global account is enabled but not found, provide more specific error
                if (config('video_tools.google.use_global_account', false)) {
                    $globalEmail = config('video_tools.google.default_account_email');
                    $errorMessage = "Global Google Meet account is enabled but not configured properly. Please ensure the account email '{$globalEmail}' is authorized and active.";
                }
                
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.mentor_no_video_tools_configured'),
                    'error' => $errorMessage,
                ], 400);
            }

            // Auto-refresh token if expired before creating session
            if ($defaultVideoTool->isTokenExpired()) {
                if (!$defaultVideoTool->refresh_token) {
                    return response()->json([
                        'success' => false,
                        'message' => __('sessions.mentor_no_video_tools_configured'),
                        'error' => 'Access token has expired and no refresh token is available. Please re-authorize the video tool integration.',
                    ], 400);
                }

                try {
                    if (!$this->videoToolService->refreshToken($defaultVideoTool)) {
                        return response()->json([
                            'success' => false,
                            'message' => __('sessions.mentor_no_video_tools_configured'),
                            'error' => 'Failed to refresh access token. Please re-authorize the video tool integration.',
                        ], 400);
                    }
                    $defaultVideoTool->refresh();
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => __('sessions.mentor_no_video_tools_configured'),
                        'error' => 'Failed to refresh access token: ' . $e->getMessage() . '. Please re-authorize the video tool integration.',
                    ], 400);
                }
            }

            // Validate video tool after token refresh
            if (!$defaultVideoTool->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => __('sessions.mentor_no_video_tools_configured'),
                    'error' => 'Video tool integration is not valid. Please re-authorize the video tool integration.',
                ], 400);
            }

            $session = $this->sessionService->scheduleSession($data, $locale);

            // Refresh session to get updated data (including meeting details if created)
            $session->refresh();

            // Check if meeting was created successfully
            if ($defaultVideoTool && !$session->meeting_id) {
                // Try to create meeting again if it wasn't created in scheduleSession
                try {
                    $meetingData = $this->videoToolService->createSession($session);
                    $session->refresh();
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();

                    // Check if error is related to invalid token or missing refresh token
                    // Also check for 401 errors which may indicate permission issues
                    // Also check for 403 errors which may indicate Teams license issues
                    if (str_contains($errorMessage, 'invalid') ||
                            str_contains($errorMessage, 'refresh token') ||
                            str_contains($errorMessage, 're-authorize') ||
                            str_contains($errorMessage, 'status 401') ||
                            str_contains($errorMessage, '401') ||
                            str_contains($errorMessage, 'Unauthorized') ||
                            str_contains($errorMessage, 'status 403') ||
                            str_contains($errorMessage, '403') ||
                            str_contains($errorMessage, 'Forbidden')) {

                            // Check if error is specifically about permissions or Teams license
                            // 401 errors with empty response body typically indicate permission issues
                            // 403 errors typically indicate Teams license issues
                            $isPermissionError = str_contains($errorMessage, 'permissions') ||
                                                str_contains($errorMessage, 'Authorization_RequestDenied') ||
                                                str_contains($errorMessage, 'required API permissions') ||
                                                str_contains($errorMessage, 'Azure Portal') ||
                                                str_contains($errorMessage, 'admin consent') ||
                                                (str_contains($errorMessage, '401') && str_contains($errorMessage, 'Unauthorized')) ||
                                                (str_contains($errorMessage, 'status 401') && str_contains($errorMessage, 'Unauthorized'));

                            $isTeamsLicenseError = str_contains($errorMessage, '403') ||
                                                  str_contains($errorMessage, 'Forbidden') ||
                                                  str_contains($errorMessage, 'Teams license') ||
                                                  str_contains($errorMessage, 'TeamsMeetingProcessorException') ||
                                                  str_contains($errorMessage, 'status 403');

                            // Mark video tool as invalid for future reference
                            try {
                                $defaultVideoTool->update(['is_active' => false]);
                            } catch (\Exception $updateException) {
                                // Failed to mark video tool as inactive
                            }

                            // Don't delete the session - allow it to be created without video meeting
                            // The session will be created but without meeting_id and join_url
                            // This allows the mentor to fix the permissions issue and manually add the meeting link later

                            // Return success response but with warning message
                            $warningMsg = $isTeamsLicenseError
                                ? __('sessions.video_tool_teams_license_error')
                                : ($isPermissionError
                                    ? __('sessions.video_tool_permissions_error')
                                    : 'Your video tool integration token is invalid. Please re-authorize the video tool integration.');

                            return response()->json([
                                'success' => true,
                                'message' => __('sessions.session_scheduled'),
                                'warning' => $warningMsg,
                                'data' => new MentorSessionResource($session->load(['participant', 'program'])),
                            ], 201);
                        }

                        // If error is not related to token, re-throw the exception
                        throw $e;
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('sessions.session_scheduled'),
                'data' => new MentorSessionResource($session->load(['participant', 'program'])),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.failed_to_schedule'),
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update a session.
     */
    public function update(SessionRequest $request, MentorSession $session): JsonResponse
    {
        // Ensure the session belongs to the authenticated mentor
        if ($session->mentor_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_found'),
            ], 404);
        }

        try {
            $data = $request->validated();

            // Capture locale from request header
            $locale = $request->header('Accept-Language', 'en');
            $locale = in_array($locale, ['en', 'ar']) ? $locale : 'en';

            $session = $this->sessionService->updateSession($session, $data, $locale);

            return response()->json([
                'success' => true,
                'message' => __('sessions.session_updated'),
                'data' => new MentorSessionResource($session->load(['participant', 'program'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.failed_to_update'),
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel a session.
     */
    public function cancel(Request $request, MentorSession $session): JsonResponse
    {
        // Ensure the session belongs to the authenticated mentor
        if ($session->mentor_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_found'),
            ], 404);
        }

        try {
            $reason = $request->input('reason');

            // Capture locale from request header
            $locale = $request->header('Accept-Language', 'en');
            $locale = in_array($locale, ['en', 'ar']) ? $locale : 'en';

            $session = $this->sessionService->cancelSession($session, $reason, $locale, 'mentor');

            return response()->json([
                'success' => true,
                'message' => __('sessions.session_cancelled'),
                'data' => new MentorSessionResource($session->load(['participant', 'program'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.failed_to_cancel'),
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Start a session.
     */
    public function start(MentorSession $session): JsonResponse
    {
        // Ensure the session belongs to the authenticated mentor
        if ($session->mentor_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_found'),
            ], 404);
        }

        try {
            $session = $this->sessionService->startSession($session);

            // Get join URL when session starts (for security - URL only available when session starts)
            if ($session->meeting_id && !$session->join_url) {
                $joinUrl = $this->videoToolService->getSessionJoinUrl($session);
                $session->refresh(); // Refresh to get updated join_url
            }

            return response()->json([
                'success' => true,
                'message' => __('sessions.session_started'),
                'data' => new MentorSessionResource($session->load(['participant', 'program'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.failed_to_start'),
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * End a session.
     */
    public function end(Request $request, MentorSession $session): JsonResponse
    {
        // Ensure the session belongs to the authenticated mentor
        if ($session->mentor_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_found'),
            ], 404);
        }

        try {
            $session = $this->sessionService->endSession($session);

            return response()->json([
                'success' => true,
                'message' => __('sessions.session_ended'),
                'data' => new MentorSessionResource($session->load(['participant', 'program'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.failed_to_end'),
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Provide structured feedback for a completed session.
     */
    public function feedback(ProvideFeedbackRequest $request, MentorSession $session): JsonResponse
    {
        if ($session->mentor_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_found'),
            ], 404);
        }

        if (!$session->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.only_completed_feedback'),
            ], 400);
        }

        if (!empty($session->rating) || !empty($session->feedback_comments)) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.duplicate_feedback'),
            ], 409);
        }

        $data = $request->validated();

        $session->update([
            'rating' => $data['rating'],
            'feedback_comments' => $data['comments'],
            'feedback_strengths' => $data['strengths'],
            'feedback_improvements' => $data['improvements'],
        ]);

        // Notify participant
        if ($session->participant) {
            $session->participant->notify(new \App\Notifications\Participant\SessionFeedbackSubmittedNotification($session));
        }

        // Notify admins (super-admin and supervisor roles)
        User::role(['super-admin', 'supervisor'])->active()->get()->each(function ($admin) use ($session) {
            $admin->notify(new \App\Notifications\Admin\SessionFeedbackSubmittedNotification($session));
        });

        activity('mentor_session')
            ->performedOn($session)
            ->causedBy(Auth::user())
            ->event('feedback_submitted')
            ->withProperties([
                'session_id' => $session->id,
                'mentor_id' => $session->mentor_id,
                'participant_id' => $session->participant_id,
            ])
            ->log('Mentor submitted session feedback');

        return response()->json([
            'success' => true,
            'message' => __('sessions.feedback_submitted'),
            'data' => new MentorSessionResource($session->fresh(['participant', 'program'])),
        ]);
    }

    /**
     * Mark session as no show.
     */
    public function markNoShow(MentorSession $session): JsonResponse
    {
        // Ensure the session belongs to the authenticated mentor
        if ($session->mentor_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_found'),
            ], 404);
        }

        try {
            $session = $this->sessionService->markNoShow($session);

            return response()->json([
                'success' => true,
                'message' => __('sessions.session_marked_no_show'),
                'data' => new MentorSessionResource($session->load(['participant', 'program'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.failed_to_mark_no_show'),
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get available time slots for scheduling.
     */
    public function getAvailableSlots(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'duration_minutes' => 'integer|min:15|max:480',
        ]);

        try {
            $mentor = Auth::user();
            $date = $request->input('date');
            $durationMinutes = $request->input('duration_minutes', 30);

            $slots = $this->sessionService->getAvailableSlots($mentor->id, $date, $durationMinutes);

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
     * Get pending session requests for the mentor.
     */
    public function pendingRequests(Request $request): JsonResponse
    {
        try {
            $mentor = Auth::user();
            $query = $mentor->sessions()
                ->pendingRequests()
                ->with(['participant', 'program']);

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
                'message' => __('sessions.failed_to_load_requests'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Accept a session request.
     */
    public function accept(AcceptSessionRequest $request, MentorSession $session): JsonResponse
    {
        // Ensure the session belongs to the authenticated mentor
        if ($session->mentor_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_found'),
            ], 404);
        }

        // Ensure the session is pending
        if (!$session->isPendingRequest()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_pending'),
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Update session status to confirmed
            $session->update([
                'status' => 'confirmed',
                'declined_reason' => null, // Clear any previous decline reason
                'proposed_time' => null, // Clear any previous proposed time
            ]);

            // Store session ID and locale for notification after commit
            $sessionId = $session->id;
            $participantId = $session->participant_id;
            $locale = $request->header('Accept-Language', 'en');
            // Normalize locale (e.g., 'ar-SA' -> 'ar', 'ar,en' -> 'ar')
            $locale = substr($locale, 0, 2);
            $locale = in_array($locale, ['en', 'ar']) ? $locale : 'en';

            // Log activity
            activity('mentor_session')
                ->performedOn($session)
                ->causedBy(Auth::user())
                ->event('session_accepted')
                ->withProperties([
                    'session_id' => $session->id,
                    'mentor_id' => $session->mentor_id,
                    'participant_id' => $session->participant_id,
                ])
                ->log('Mentor accepted session request');

            DB::commit();

            // Send notification after transaction commits to ensure data is persisted
            DB::afterCommit(function () use ($sessionId, $participantId, $locale) {
                try {
                    $freshSession = MentorSession::with(['mentor', 'participant', 'program'])->find($sessionId);
                    if ($freshSession && $freshSession->participant) {
                        $notification = new \App\Notifications\Participant\SessionAcceptedNotification($freshSession);
                        $notification->locale = $locale;
                        $freshSession->participant->notify($notification);
                    }
                } catch (\Exception $e) {
                    // Error in afterCommit callback
                }
            });

            return response()->json([
                'success' => true,
                'message' => __('sessions.session_accepted'),
                'data' => new MentorSessionResource($session->load(['participant', 'program'])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('sessions.failed_to_accept_request'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Decline a session request.
     */
    public function decline(DeclineSessionRequest $request, MentorSession $session): JsonResponse
    {
        // Ensure the session belongs to the authenticated mentor
        if ($session->mentor_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_found'),
            ], 404);
        }

        // Ensure the session is pending
        if (!$session->isPendingRequest()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_pending'),
            ], 400);
        }

        try {
            DB::beginTransaction();

            $reason = $request->input('reason');

            // Update session status to cancelled and store decline reason
            $session->update([
                'status' => 'cancelled',
                'declined_reason' => $reason,
                'proposed_time' => null, // Clear any previous proposed time
            ]);

            // Notify participant
            if ($session->participant) {
                $session->participant->notify(
                    new \App\Notifications\Participant\SessionDeclinedNotification($session, $reason)
                );
            }

            // Log activity
            activity('mentor_session')
                ->performedOn($session)
                ->causedBy(Auth::user())
                ->event('session_declined')
                ->withProperties([
                    'session_id' => $session->id,
                    'mentor_id' => $session->mentor_id,
                    'participant_id' => $session->participant_id,
                    'reason' => $reason,
                ])
                ->log('Mentor declined session request');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('sessions.session_declined'),
                'data' => new MentorSessionResource($session->load(['participant', 'program'])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('sessions.failed_to_decline_request'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Propose a new time for a session request.
     */
    public function proposeNewTime(ProposeNewTimeRequest $request, MentorSession $session): JsonResponse
    {
        // Ensure the session belongs to the authenticated mentor
        if ($session->mentor_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_found'),
            ], 404);
        }

        // Ensure the session is pending
        if (!$session->isPendingRequest()) {
            return response()->json([
                'success' => false,
                'message' => __('sessions.session_not_pending'),
            ], 400);
        }

        try {
            DB::beginTransaction();

            $proposedTime = \Carbon\Carbon::parse($request->input('proposed_time'));

            // Store proposed time (status remains 'scheduled' until mentee approves)
            $session->update([
                'proposed_time' => $proposedTime,
                'declined_reason' => null, // Clear any previous decline reason
            ]);

            // Notify participant
            if ($session->participant) {
                $session->participant->notify(
                    new \App\Notifications\Participant\NewTimeProposedNotification($session, $proposedTime)
                );
            }

            // Log activity
            activity('mentor_session')
                ->performedOn($session)
                ->causedBy(Auth::user())
                ->event('new_time_proposed')
                ->withProperties([
                    'session_id' => $session->id,
                    'mentor_id' => $session->mentor_id,
                    'participant_id' => $session->participant_id,
                    'proposed_time' => $proposedTime->format('Y-m-d H:i:s'),
                ])
                ->log('Mentor proposed new time for session');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('sessions.new_time_proposed'),
                'data' => new MentorSessionResource($session->load(['participant', 'program'])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('sessions.failed_to_propose_new_time'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
