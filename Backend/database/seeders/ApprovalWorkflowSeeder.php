<?php

namespace Database\Seeders;

use App\Models\ApprovalLevel;
use App\Models\ApprovalWorkflow;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class ApprovalWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some sample roles if they don't exist
        $roles = [
            ['name' => 'Manager', 'guard_name' => 'web'],
            ['name' => 'Director', 'guard_name' => 'web'],
            ['name' => 'Admin', 'guard_name' => 'web'],
            ['name' => 'Supervisor', 'guard_name' => 'web'],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }

        // Get role IDs
        $managerRole = Role::where('name', 'Manager')->first();
        $directorRole = Role::where('name', 'Director')->first();
        $adminRole = Role::where('name', 'Admin')->first();
        $supervisorRole = Role::where('name', 'Supervisor')->first();

        // Create sample approval workflows
        $workflows = [
            [
                'action' => 'Competition.update',
                'levels' => 2,
                'is_active' => true,
                'levels_data' => [
                    [
                        'level_number' => 1,
                        'role_ids' => [$managerRole->id, $supervisorRole->id],
                        'required_approvals' => 1,
                    ],
                    [
                        'level_number' => 2,
                        'role_ids' => [$directorRole->id],
                        'required_approvals' => 1,
                    ],
                ],
            ],
            [
                'action' => 'Event.create',
                'levels' => 1,
                'is_active' => true,
                'levels_data' => [
                    [
                        'level_number' => 1,
                        'role_ids' => [$managerRole->id],
                        'required_approvals' => 1,
                    ],
                ],
            ],
            [
                'action' => 'Project.update',
                'levels' => 3,
                'is_active' => true,
                'levels_data' => [
                    [
                        'level_number' => 1,
                        'role_ids' => [$supervisorRole->id],
                        'required_approvals' => 1,
                    ],
                    [
                        'level_number' => 2,
                        'role_ids' => [$managerRole->id],
                        'required_approvals' => 1,
                    ],
                    [
                        'level_number' => 3,
                        'role_ids' => [$directorRole->id, $adminRole->id],
                        'required_approvals' => 1,
                    ],
                ],
            ],
        ];

        foreach ($workflows as $workflowData) {
            $levelsData = $workflowData['levels_data'];
            unset($workflowData['levels_data']);

            $workflow = ApprovalWorkflow::create($workflowData);

            // Create approval levels
            foreach ($levelsData as $levelData) {
                ApprovalLevel::create([
                    'approval_workflow_id' => $workflow->id,
                    'level_number' => $levelData['level_number'],
                    'role_ids' => $levelData['role_ids'],
                    'required_approvals' => $levelData['required_approvals'],
                ]);
            }
        }
    }
}
