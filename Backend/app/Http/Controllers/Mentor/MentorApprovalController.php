<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mentor\MentorApprovalRequest;
use App\Models\Mentor;
use App\Notifications\Mentor\MentorApproved;
use App\Notifications\Mentor\MentorRejected;
use App\Notifications\Mentor\MentorRegistrationPending;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MentorApprovalController extends Controller
{
    /**
     * Get pending mentors for admin review
     */
    public function index(Request $request): JsonResponse
    {
        $mentors = Mentor::where('status', 'pending')
            ->with(['competition', 'track'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($mentors);
    }

    /**
     * Get specific mentor details for review
     */
    public function show(Mentor $mentor): JsonResponse
    {
        $mentor->load(['competition', 'track']);
        
        return response()->json([
            'mentor' => $mentor,
            'status' => $mentor->status,
            'created_at' => $mentor->created_at,
            'can_approve' => $mentor->status === 'pending',
            'can_reject' => $mentor->status === 'pending',
        ]);
    }

    /**
     * Approve mentor registration
     */
    public function approve(MentorApprovalRequest $request, Mentor $mentor): JsonResponse
    {
        if ($mentor->status !== 'pending') {
            return response()->json([
                'message' => __('mentor.already_processed'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $mentor->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        // Mark pending registration notification as read
        $mentor->notifications()
            ->where('type', MentorRegistrationPending::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Send approval notification to mentor
        $mentor->notify(new MentorApproved($mentor));

        return response()->json([
            'message' => __('mentor.approved_successfully'),
            'mentor' => $mentor->fresh(['competition', 'track']),
        ], Response::HTTP_OK);
    }

    /**
     * Reject mentor registration
     */
    public function reject(MentorApprovalRequest $request, Mentor $mentor): JsonResponse
    {
        if ($mentor->status !== 'pending') {
            return response()->json([
                'message' => __('mentor.already_processed'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $mentor->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'approved_by' => auth()->id(),
            'rejection_reason' => $request->input('reason'),
        ]);

        // Send rejection notification to mentor
        $mentor->notify(new MentorRejected($mentor, $request->input('reason')));

        return response()->json([
            'message' => __('mentor.rejected_successfully'),
            'mentor' => $mentor->fresh(['competition', 'track']),
        ], Response::HTTP_OK);
    }

    /**
     * Get mentor statistics for admin dashboard
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => Mentor::count(),
            'pending' => Mentor::where('status', 'pending')->count(),
            'approved' => Mentor::where('status', 'approved')->count(),
            'rejected' => Mentor::where('status', 'rejected')->count(),
        ];

        return response()->json($stats);
    }
}
