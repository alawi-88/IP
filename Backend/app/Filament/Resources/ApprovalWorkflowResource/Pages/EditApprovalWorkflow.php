<?php

namespace App\Filament\Resources\ApprovalWorkflowResource\Pages;

use App\Filament\Resources\ApprovalWorkflowResource;
use App\Models\ApprovalLevel;
use Spatie\Permission\Models\Role;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditApprovalWorkflow extends EditRecord
{
    protected static string $resource = ApprovalWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Delete / حذف')
                ->requiresConfirmation()
                ->modalHeading('Delete Workflow Policy / حذف مسار الاعتماد')
                ->modalDescription('Are you sure you want to delete this workflow policy? This will not affect existing approval requests. / هل أنت متأكد أنك تريد حذف مسار الاعتماد هذا؟ لن يؤثر ذلك على الطلبات قيد التنفيذ.')
                ->modalSubmitActionLabel('Confirm Delete / تأكيد الحذف')
                ->modalCancelActionLabel('Cancel / إلغاء')
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->title('Workflow Deleted Successfully / تم حذف مسار الاعتماد بنجاح')
                        ->body('The workflow policy has been removed from the system. / تم إزالة مسار الاعتماد من النظام.')
                        ->success()
                )
                ->before(function ($record) {
                    // Check if there are any active approval requests for this workflow
                    // This would be implemented when approval requests are created
                    // For now, no additional side effects are needed.
                })
                ->after(function () {
                    // Redirect to the list page after deletion
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load approval levels for editing
        $workflow = $this->record;
        $levels = $workflow->approvalLevels()->orderBy('level_number')->get();
        
        $data['approvalLevels'] = $levels->map(function ($level) {
            return [
                'id' => $level->id,
                'level_number' => $level->level_number,
                'role_ids' => is_array($level->role_ids) ? $level->role_ids : json_decode($level->role_ids, true) ?? [],
                'required_approvals' => $level->required_approvals,
            ];
        })->toArray();

        // Store original levels count for comparison
        $data['original_levels'] = $workflow->levels;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Check if trying to change action to an existing one (shouldn't happen as action is disabled in edit)
        if (isset($data['action']) && $data['action'] !== $this->record->action) {
            $existingWorkflow = \App\Models\ApprovalWorkflow::where('action', $data['action'])
                ->where('id', '!=', $this->record->id)
                ->first();
            
            if ($existingWorkflow) {
                Notification::make()
                    ->title('Duplicate Action Policy / سياسة إجراء مكررة')
                    ->body('A workflow already exists for this action. / يوجد مسار اعتماد لهذا الإجراء بالفعل.')
                    ->danger()
                    ->persistent()
                    ->send();
                
                $this->halt();
            }
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

        // Check if levels are being reduced
        $originalLevels = $this->record->levels;
        $newLevels = $data['levels'] ?? $originalLevels;
        
        // Note: Level reduction warning is now shown in afterStateUpdated callback
        // when the user changes the levels count in the form

        // Check for duplicate roles before saving
        if (isset($data['approvalLevels']) && is_array($data['approvalLevels'])) {
            $this->checkDuplicateRoles($data['approvalLevels']);
        }

        // Validate that each level has at least one role
        if (isset($data['approvalLevels'])) {
            foreach ($data['approvalLevels'] as $index => $level) {
                // Ensure level_number is preserved and not changed
                $expectedLevelNumber = $index + 1;
                if (!isset($level['level_number']) || $level['level_number'] !== $expectedLevelNumber) {
                    $data['approvalLevels'][$index]['level_number'] = $expectedLevelNumber;
                }
                
                if (empty($level['role_ids']) || count($level['role_ids']) === 0) {
                    Notification::make()
                        ->title('Validation Error / خطأ في التحقق')
                        ->body('Workflow must have at least one role per level. / يجب أن يحتوي مسار الاعتماد على دور واحد على الأقل لكل مرحلة.')
                        ->danger()
                        ->send();
                    
                    $this->halt();
                }

                // Validate required approvals don't exceed available roles
                $requiredApprovals = $level['required_approvals'] ?? 1;
                $roleCount = count($level['role_ids']);
                
                if ($requiredApprovals > $roleCount) {
                    Notification::make()
                        ->title('Validation Error / خطأ في التحقق')
                        ->body("Required approvals ({$requiredApprovals}) cannot exceed number of roles ({$roleCount}) for level " . ($index + 1) . " / عدد الاعتمادات المطلوبة ({$requiredApprovals}) لا يمكن أن يتجاوز عدد الأدوار ({$roleCount}) للمرحلة " . ($index + 1))
                        ->danger()
                        ->send();
                    
                    $this->halt();
                }
            }
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

    protected function afterSave(): void
    {
        // Update approval levels
        $workflow = $this->record;
        $levels = $workflow->levels;
        
        // Store original levels for comparison
        $originalLevels = $this->data['original_levels'] ?? $levels;
        
        // Delete existing levels
        $workflow->approvalLevels()->delete();
        
        // Create new levels based on the form data
        if (isset($this->data['approvalLevels'])) {
            foreach ($this->data['approvalLevels'] as $levelData) {
                // Only create levels up to the new level count
                if ($levelData['level_number'] <= $levels) {
                    ApprovalLevel::create([
                        'approval_workflow_id' => $workflow->id,
                        'level_number' => $levelData['level_number'],
                        'role_ids' => $levelData['role_ids'] ?? [],
                        'required_approvals' => $levelData['required_approvals'] ?? 1,
                    ]);
                }
            }
        } else {
            // If no levels provided, create default levels
            for ($i = 1; $i <= $levels; $i++) {
                ApprovalLevel::create([
                    'approval_workflow_id' => $workflow->id,
                    'level_number' => $i,
                    'role_ids' => [],
                    'required_approvals' => 1,
                ]);
            }
        }

        // Show appropriate success message based on changes
        $message = 'The approval workflow has been updated.';
        $arabicMessage = 'تم تحديث مسار الاعتماد.';
        
        if ($levels < $originalLevels) {
            $message .= ' Higher level roles have been removed.';
            $arabicMessage .= ' تم إزالة أدوار المراحل الأعلى.';
        }

        Notification::make()
            ->title('Workflow Updated Successfully / تم تحديث مسار الاعتماد بنجاح')
            ->body($message . ' / ' . $arabicMessage)
            ->success()
            ->send();
    }
}
