<?php

namespace App\Filament\Resources\ApprovalWorkflowResource\Pages;

use App\Filament\Resources\ApprovalWorkflowResource;
use App\Models\ApprovalLevel;
use Spatie\Permission\Models\Role;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateApprovalWorkflow extends CreateRecord
{
    protected static string $resource = ApprovalWorkflowResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Initialize approval levels based on levels count
        if (isset($data['levels']) && $data['levels'] > 0) {
            $levels = [];
            for ($i = 0; $i < $data['levels']; $i++) {
                $levels[] = [
                    'level_number' => $i + 1,
                    'role_ids' => [],
                    'required_approvals' => 1,
                ];
            }
            $data['approvalLevels'] = $levels;
        }

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Check if a workflow already exists for this action
        $existingWorkflow = \App\Models\ApprovalWorkflow::where('action', $data['action'])->first();

        if ($existingWorkflow) {
            Notification::make()
                ->title('Duplicate Action Policy / سياسة إجراء مكررة')
                ->body('A workflow already exists for this action. / يوجد مسار اعتماد لهذا الإجراء بالفعل.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        // Validate that at least one role exists
        if (Role::count() === 0) {
            Notification::make()
                ->title('No Roles Available / لا توجد أدوار متاحة')
                ->body('Please create roles before assigning them to workflows.')
                ->warning()
                ->send();

            $this->halt();
        }

        // Ensure levels is a positive integer
        if (!isset($data['levels']) || $data['levels'] < 1) {
            $data['levels'] = 1;
        }

        // Process approval levels data
        if (isset($data['approvalLevels']) && is_array($data['approvalLevels'])) {
            // Ensure each level has a proper level_number and role_ids is array
            foreach ($data['approvalLevels'] as $index => $level) {
                // Simple sequential numbering starting from 1
                $data['approvalLevels'][$index]['level_number'] = (int)$index + 1;

                // Ensure role_ids is an array
                if (isset($level['role_ids']) && !is_array($level['role_ids'])) {
                    $data['approvalLevels'][$index]['role_ids'] = json_decode($level['role_ids'], true) ?? [];
                }
            }
        }

        // Check for duplicate roles before saving
        if (isset($data['approvalLevels']) && is_array($data['approvalLevels'])) {
            $this->checkDuplicateRoles($data['approvalLevels']);
        }

        return $data;
    }

    /**
     * Check for duplicate roles across workflow levels
     */
    protected function checkDuplicateRoles(array $levels): void
    {
        $roleUsage = [];
        $duplicateRoles = [];

        foreach ($levels as $index => $level) {
            $roleIds = $level['role_ids'] ?? [];

            if (is_array($roleIds)) {
                foreach ($roleIds as $roleId) {
                    if (!isset($roleUsage[$roleId])) {
                        $roleUsage[$roleId] = [];
                    }
                    // Ensure index is always an integer
                    $levelNumber = is_numeric($index) ? (int)$index + 1 : 1;
                    $roleUsage[$roleId][] = $levelNumber;
                }
            }
        }

        // Find roles used in multiple levels
        foreach ($roleUsage as $roleId => $levelNumbers) {
            if (count($levelNumbers) > 1) {
                $duplicateRoles[] = [
                    'role_id' => $roleId,
                    'levels' => $levelNumbers
                ];
            }
        }

        // Show warning if duplicate roles found
        if (!empty($duplicateRoles)) {
            $roleNames = \Spatie\Permission\Models\Role::whereIn('id', array_column($duplicateRoles, 'role_id'))
                ->pluck('name', 'id')
                ->toArray();

            $warningMessage = 'Warning: The following roles are assigned to multiple levels: / تحذير: الأدوار التالية مخصصة لأكثر من مرحلة: ';
            $warnings = [];

            foreach ($duplicateRoles as $duplicate) {
                $roleName = $roleNames[$duplicate['role_id']] ?? "Role ID {$duplicate['role_id']}";
                $levels = implode(', ', $duplicate['levels']);
                $warnings[] = "{$roleName} (Levels: {$levels})";
            }

            $warningMessage .= implode(', ', $warnings);

            \Filament\Notifications\Notification::make()
                ->title('Duplicate Roles Warning / تحذير الأدوار المكررة')
                ->body($warningMessage)
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function afterCreate(): void
    {
        // Create approval levels based on the number of levels specified
        $workflow = $this->record;
        $levels = (int) $workflow->levels;

        // Ensure levels is at least 1
        if ($levels < 1) {
            $levels = 1;
        }

        // The approval levels are now created through the form relationship
        // No need for manual creation since we're using the repeater with relationship

        Notification::make()
            ->title('Workflow Created Successfully / تم إنشاء مسار الاعتماد بنجاح')
            ->body('The approval workflow has been created and is ready for configuration.')
            ->success()
            ->send();
    }
}
