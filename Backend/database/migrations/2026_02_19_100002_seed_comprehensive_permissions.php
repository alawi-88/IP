<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Seed comprehensive permissions for ALL admin actions.
     * Fix #6: Every admin action should be added as a permission.
     */
    public function up(): void
    {
        $guard = 'web';

        // All resource models that need CRUD permissions
        $models = [
            'Program',              // Competition
            'CompetitionApplication',
            'Form',
            'Project',
            'Team',
            'Event',
            'Guideline',
            'Judge',
            'Mentor',
            'MentorSession',
            'Participant',
            'Winner',
            'User',
            'Role',
            'Permission',
            'Stage',
            'Track',
            'SubTrack',
            'Service',
            'Page',
            'Committee',
            'NotificationMessage',
            'NotificationManagement',
            'EmailTemplate',
            'TaskTemplate',
            'TaskAssignment',
            'Satisfaction',
            'ContactUs',
            'JudgeContactUs',
            'BrandingCompetition',
            'BrandingSetting',
            'ProjectFormConfig',
            'RegistrationFormConfig',
            'TeamFormConfig',
            'RegistrationEvaluationForm',
            'RegistrationEvaluator',
            'ProjectEvaluation',
            'EvaluationStageConfig',
            'FormAiScoringConfig',
            'FormAiHints',
            'ApprovalWorkflow',
            'ApprovalRequest',
            'CustomDashboard',
            'ActivityLog',
            'LandingPage',
            'MentorVideoTool',
        ];

        // Standard CRUD actions for each model
        $actions = ['view', 'create', 'update', 'delete'];

        // Additional actions
        $additionalActions = [
            'archive', 'restore',      // Lifecycle
            'export', 'import',        // Data operations
            'assign', 'manage',        // Management
        ];

        foreach ($models as $model) {
            // Standard CRUD
            foreach ($actions as $action) {
                Permission::firstOrCreate(
                    ['name' => "{$action} {$model}", 'guard_name' => $guard]
                );
            }
            // Archive & Restore for applicable models
            Permission::firstOrCreate(
                ['name' => "archive {$model}", 'guard_name' => $guard]
            );
            Permission::firstOrCreate(
                ['name' => "restore {$model}", 'guard_name' => $guard]
            );
        }

        // Special permissions
        $specialPermissions = [
            'configure Integrations',
            'manage BrandingSettings',
            'manage LandingPage',
            'manage PlatformSettings',
            'view Dashboard',
            'view Reports',
            'export Reports',
            'manage Notifications',
            'send Notifications',
            'manage HubTabs',
            'assign Tasks',
            'review TaskSubmission',
            'approve TaskSubmission',
            'reject TaskSubmission',
            'manage ApprovalPolicies',
            'view ApprovalPolicies',
            'create ApprovalPolicies',
            'update ApprovalPolicies',
            'delete ApprovalPolicies',
            'approve ApprovalRequest',
            'reject ApprovalRequest',
            'manage Evaluations',
            'assign Evaluators',
            'publish Winners',
            'toggle Winner',
            'manage AI Scoring',
        ];

        foreach ($specialPermissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm, 'guard_name' => $guard]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Permissions are not removed to prevent breaking existing role assignments
    }
};
