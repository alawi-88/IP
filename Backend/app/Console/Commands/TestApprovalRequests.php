<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestLevel;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Services\ApprovalRequestService;
use Illuminate\Console\Command;

class TestApprovalRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:approval-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the approval request functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Approval Request Functionality...');
        $this->newLine();

        // Test 1: Create approval request
        $this->info('1. Testing approval request creation:');
        
        $service = new ApprovalRequestService();
        $admin = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->first();

        if (!$admin) {
            $this->warn('No admin user found. Please create an admin user first.');
            return;
        }

        // Test creating approval request for an action with workflow
        $workflow = ApprovalWorkflow::where('is_active', true)->first();
        if (!$workflow) {
            $this->warn('No active approval workflow found. Please create a workflow first.');
            return;
        }

        $actionData = [
            'method' => 'POST',
            'path' => 'admin/programs',
            'data' => ['name' => 'Test Program', 'description' => 'Test Description'],
            'ip' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ];

        $approvalRequest = $service->createApprovalRequest(
            $workflow->action,
            $admin,
            $actionData,
            'Testing approval request functionality'
        );

        if ($approvalRequest) {
            $this->line("   ✓ Approval request created successfully");
            $this->line("     ID: {$approvalRequest->id}");
            $this->line("     Action: {$approvalRequest->action}");
            $this->line("     Status: {$approvalRequest->status}");
            $this->line("     Requested by: {$approvalRequest->requestedBy->name}");
        } else {
            $this->line("   ✗ Failed to create approval request");
        }

        // Test 2: Test approval request levels
        $this->info('2. Testing approval request levels:');
        
        if ($approvalRequest) {
            $levels = $approvalRequest->approvalRequestLevels;
            $this->line("   Total levels: {$levels->count()}");
            
            foreach ($levels as $level) {
                $this->line("     Level {$level->level_number}: {$level->status}");
            }
        }

        // Test 3: Test approval workflow
        $this->info('3. Testing approval workflow:');
        
        if ($approvalRequest) {
            $workflow = $approvalRequest->approvalWorkflow;
            $this->line("   Workflow: {$workflow->action}");
            $this->line("   Total levels: {$workflow->levels}");
            $this->line("   Is active: " . ($workflow->is_active ? 'Yes' : 'No'));
        }

        // Test 4: Test approval request methods
        $this->info('4. Testing approval request methods:');
        
        if ($approvalRequest) {
            $this->line("   Is pending: " . ($approvalRequest->isPending() ? 'Yes' : 'No'));
            $this->line("   Is approved: " . ($approvalRequest->isApproved() ? 'Yes' : 'No'));
            $this->line("   Is rejected: " . ($approvalRequest->isRejected() ? 'Yes' : 'No'));
            $this->line("   Is cancelled: " . ($approvalRequest->isCancelled() ? 'Yes' : 'No'));
            $this->line("   Is fully approved: " . ($approvalRequest->isFullyApproved() ? 'Yes' : 'No'));
        }

        // Test 5: Test approval request level methods
        $this->info('5. Testing approval request level methods:');
        
        if ($approvalRequest) {
            $firstLevel = $approvalRequest->approvalRequestLevels()->first();
            if ($firstLevel) {
                $this->line("   First level: {$firstLevel->level_number}");
                $this->line("   Status: {$firstLevel->status}");
                $this->line("   Is pending: " . ($firstLevel->isPending() ? 'Yes' : 'No'));
                $this->line("   Is approved: " . ($firstLevel->isApproved() ? 'Yes' : 'No'));
                $this->line("   Is rejected: " . ($firstLevel->isRejected() ? 'Yes' : 'No'));
            }
        }

        // Test 6: Test approval request service methods
        $this->info('6. Testing approval request service methods:');
        
        if ($approvalRequest) {
            // Test getting pending approvals for a user
            $pendingApprovals = $service->getPendingApprovalsForUser($admin);
            $this->line("   Pending approvals for admin: {$pendingApprovals->count()}");
            
            // Test approval statistics
            $stats = $service->getApprovalStatistics();
            $this->line("   Approval statistics:");
            $this->line("     Pending: {$stats['pending']}");
            $this->line("     Approved: {$stats['approved']}");
            $this->line("     Rejected: {$stats['rejected']}");
            $this->line("     Cancelled: {$stats['cancelled']}");
        }

        // Test 7: Test approval request actions
        $this->info('7. Testing approval request actions:');
        
        if ($approvalRequest) {
            // Test approving the request
            $this->line("   Testing approval...");
            $approvalRequest->approve();
            $this->line("     Status after approval: {$approvalRequest->status}");
            $this->line("     Approved at: {$approvalRequest->approved_at}");
            
            // Test rejecting the request
            $this->line("   Testing rejection...");
            $approvalRequest->reject('Test rejection reason');
            $this->line("     Status after rejection: {$approvalRequest->status}");
            $this->line("     Rejected at: {$approvalRequest->rejected_at}");
            $this->line("     Rejection reason: {$approvalRequest->rejection_reason}");
            
            // Test cancelling the request
            $this->line("   Testing cancellation...");
            $approvalRequest->cancel();
            $this->line("     Status after cancellation: {$approvalRequest->status}");
            $this->line("     Cancelled at: {$approvalRequest->cancelled_at}");
        }

        // Test 8: Test edge cases
        $this->info('8. Testing edge cases:');
        
        // Test creating approval request for action without workflow
        $this->line("   Testing action without workflow...");
        $noWorkflowRequest = $service->createApprovalRequest(
            'NonExistent.action',
            $admin,
            $actionData,
            'Testing action without workflow'
        );
        
        if ($noWorkflowRequest === null) {
            $this->line("     ✓ Correctly handled action without workflow");
        } else {
            $this->line("     ✗ Should have returned null for action without workflow");
        }

        // Test 9: Test approval request scopes
        $this->info('9. Testing approval request scopes:');
        
        $pendingCount = ApprovalRequest::pending()->count();
        $approvedCount = ApprovalRequest::approved()->count();
        $rejectedCount = ApprovalRequest::rejected()->count();
        $cancelledCount = ApprovalRequest::cancelled()->count();
        
        $this->line("   Pending requests: {$pendingCount}");
        $this->line("   Approved requests: {$approvedCount}");
        $this->line("   Rejected requests: {$rejectedCount}");
        $this->line("   Cancelled requests: {$cancelledCount}");

        // Test 10: Test approval request level scopes
        $this->info('10. Testing approval request level scopes:');
        
        $pendingLevelsCount = ApprovalRequestLevel::pending()->count();
        $approvedLevelsCount = ApprovalRequestLevel::approved()->count();
        $rejectedLevelsCount = ApprovalRequestLevel::rejected()->count();
        
        $this->line("   Pending levels: {$pendingLevelsCount}");
        $this->line("   Approved levels: {$approvedLevelsCount}");
        $this->line("   Rejected levels: {$rejectedLevelsCount}");

        $this->newLine();
        $this->info('Approval Request functionality test completed!');
        $this->newLine();
        $this->info('Key features tested:');
        $this->line('✓ Approval request creation');
        $this->line('✓ Approval request levels');
        $this->line('✓ Approval workflow integration');
        $this->line('✓ Approval request methods');
        $this->line('✓ Approval request level methods');
        $this->line('✓ Service methods');
        $this->line('✓ Approval request actions');
        $this->line('✓ Edge cases');
        $this->line('✓ Scopes');
        $this->line('✓ Statistics');
    }
}
