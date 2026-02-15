<?php

namespace App\Http\Middleware;

use App\Services\ApprovalRequestService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckApprovalRequired
{
    protected $approvalRequestService;

    public function __construct(ApprovalRequestService $approvalRequestService)
    {
        $this->approvalRequestService = $approvalRequestService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to admin actions that might require approval
        if (!$this->shouldCheckApproval($request)) {
            return $next($request);
        }

        // Get the action from the request
        $action = $this->getActionFromRequest($request);
        
        if (!$action) {
            return $next($request);
        }

        // Check if approval is required for this action
        $approvalRequest = $this->approvalRequestService->createApprovalRequest(
            $action,
            Auth::user(),
            $this->getActionDataFromRequest($request),
            $this->getReasonFromRequest($request)
        );

        if ($approvalRequest) {
            // Approval is required, return response indicating pending approval
            return response()->json([
                'success' => false,
                'message' => 'Action requires approval. Your request has been submitted for review.',
                'message_ar' => 'يتطلب الإجراء موافقة. تم إرسال طلبك للمراجعة.',
                'approval_request_id' => $approvalRequest->id,
                'status' => 'pending_approval'
            ], 202); // 202 Accepted
        }

        // No approval required, proceed with the request
        return $next($request);
    }

    /**
     * Check if the request should be checked for approval
     */
    protected function shouldCheckApproval(Request $request): bool
    {
        // Only check for authenticated users
        if (!Auth::check()) {
            return false;
        }

        // Only check for admin users
        if (!Auth::user()->hasRole('admin')) {
            return false;
        }

        // Only check for specific routes/methods
        $method = $request->method();
        $path = $request->path();

        // Check for admin routes that might require approval
        $adminRoutes = [
            'admin/competitions',
            'admin/events',
            'admin/projects',
            'admin/users',
        ];

        foreach ($adminRoutes as $route) {
            if (str_starts_with($path, $route) && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the action from the request
     */
    protected function getActionFromRequest(Request $request): ?string
    {
        $method = $request->method();
        $path = $request->path();

        // Map routes to actions
        $actionMap = [
            'admin/competitions' => [
                'POST' => 'Competition.create',
                'PUT' => 'Competition.update',
                'PATCH' => 'Competition.update',
                'DELETE' => 'Competition.delete',
            ],
            'admin/events' => [
                'POST' => 'Event.create',
                'PUT' => 'Event.update',
                'PATCH' => 'Event.update',
                'DELETE' => 'Event.delete',
            ],
            'admin/projects' => [
                'PUT' => 'Project.update',
                'PATCH' => 'Project.update',
                'DELETE' => 'Project.delete',
            ],
            'admin/users' => [
                'POST' => 'User.create',
                'PUT' => 'User.update',
                'PATCH' => 'User.update',
                'DELETE' => 'User.delete',
            ],
        ];

        foreach ($actionMap as $route => $methods) {
            if (str_starts_with($path, $route) && isset($methods[$method])) {
                return $methods[$method];
            }
        }

        return null;
    }

    /**
     * Get action data from the request
     */
    protected function getActionDataFromRequest(Request $request): array
    {
        return [
            'method' => $request->method(),
            'path' => $request->path(),
            'data' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }

    /**
     * Get reason from the request
     */
    protected function getReasonFromRequest(Request $request): ?string
    {
        return $request->input('reason') ?? $request->input('_reason');
    }
}
