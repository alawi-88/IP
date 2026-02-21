<?php

namespace App\Filament\Resources\ApprovalWorkflowResource\Pages;

use App\Filament\Resources\ApprovalWorkflowResource;
use Spatie\Permission\Models\Role;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewApprovalWorkflow extends ViewRecord
{
    protected static string $resource = ApprovalWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit / تعديل'),
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
                ),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Workflow Details / تفاصيل مسار الاعتماد')
                    ->schema([
                        TextEntry::make('action')
                            ->label('Action / الإجراء')
                            ->badge()
                            ->color('primary')
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return json_encode($state, JSON_UNESCAPED_UNICODE);
                                }
                                
                                $actions = [
                                    // Program Actions
                                    'Program.create' => 'Create Program / إنشاء برنامج',
                                    'Program.update' => 'Update Program / تحديث برنامج',
                                    'Program.delete' => 'Delete Program / حذف برنامج',
                                    'Program.archive' => 'Archive Program / أرشفة برنامج',

                                    // Program Actions
                                    'Program.create' => 'Create Program / إنشاء مسابقة',
                                    'Program.update' => 'Update Program / تحديث مسابقة',
                                    'Program.delete' => 'Delete Program / حذف مسابقة',

                                    // Program Application Actions
                                    'ProgramApplication.update' => 'Update Program Application / تحديث طلب مسابقة',
                                    'ProgramApplication.delete' => 'Delete Program Application / حذف طلب مسابقة',
                                    'ProgramApplication.archive' => 'Archive Program Application / أرشفة طلب مسابقة',

                                    // Form Actions
                                    'Form.create' => 'Create Form / إنشاء نموذج',
                                    'Form.update' => 'Update Form / تحديث النموذج',
                                    'Form.delete' => 'Delete Form / حذف النموذج',
                                    'Form.archive' => 'Archive Form / أرشفة النموذج',

                                    // Project Actions
                                    'Project.update' => 'Update Project / تحديث مشروع',
                                    'Project.delete' => 'Delete Project / حذف مشروع',
                                    'Project.archive' => 'Archive Project / أرشفة مشروع',
                                    'Project.restore' => 'Restore Project / استعادة مشروع',
                                ];
                                
                                return $actions[$state] ?? $state;
                            }),

                        TextEntry::make('is_active')
                            ->label('Status / الحالة')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Active / نشط' : 'Inactive / غير نشط'),

                        TextEntry::make('levels')
                            ->label('Number of Levels / عدد المراحل')
                            ->badge()
                            ->color('info')
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return json_encode($state, JSON_UNESCAPED_UNICODE);
                                }
                                return "{$state} Levels / {$state} مرحلة";
                            }),

                        TextEntry::make('created_at')
                            ->label('Created At / تاريخ الإنشاء')
                            ->formatStateUsing(fn ($state) => $state?->format('M d, Y H:i')),

                        TextEntry::make('updated_at')
                            ->label('Last Updated / آخر تحديث')
                            ->formatStateUsing(fn ($state) => $state?->format('M d, Y H:i')),
                    ])
                    ->columns(2),

                Section::make('Approval Levels / مراحل الاعتماد')
                    ->schema([
                        RepeatableEntry::make('approvalLevels')
                            ->label('')
                            ->schema([
                                TextEntry::make('level_number')
                                    ->label('Level / المرحلة')
                                    ->badge()
                                    ->color('gray')
                                    ->formatStateUsing(function ($state) {
                                        if (is_array($state)) {
                                            return json_encode($state, JSON_UNESCAPED_UNICODE);
                                        }
                                        return "L{$state}";
                                    }),

                                TextEntry::make('role_ids')
                                    ->label('Roles / الأدوار')
                                    ->html()
                                    ->formatStateUsing(function ($state, $record) {
                                        // Get role_ids from the record directly
                                        $roleIds = $record->role_ids ?? [];
                                        
                                        // Handle different data types for role_ids
                                        if (is_array($roleIds)) {
                                            // Already an array
                                        } elseif (is_string($roleIds)) {
                                            $decoded = json_decode($roleIds, true);
                                            $roleIds = is_array($decoded) ? $decoded : [];
                                        } elseif (is_numeric($roleIds)) {
                                            $roleIds = [(int)$roleIds];
                                        } else {
                                            $roleIds = [];
                                        }
                                        
                                        if (empty($roleIds)) {
                                            return '<span class="text-gray-500">No roles assigned / لا توجد أدوار مخصصة</span>';
                                        }
                                        
                                        $roles = Role::whereIn('id', $roleIds)->get();
                                        
                                        if ($roles->isEmpty()) {
                                            return '<span class="text-gray-500">No roles found / لا توجد أدوار</span>';
                                        }
                                        
                                        $roleNames = $roles->pluck('name')->toArray();
                                        $missingRoles = array_diff($roleIds, $roles->pluck('id')->toArray());
                                        
                                        // Create individual badges for each role
                                        $badges = [];
                                        foreach ($roleNames as $roleName) {
                                            $badges[] = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 mr-1 mb-1">' . $roleName . '</span>';
                                        }
                                        
                                        // Add badges for missing roles
                                        if (!empty($missingRoles)) {
                                            $badges[] = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 mr-1 mb-1">Unknown Role / دور غير معروف</span>';
                                        }
                                        
                                        return implode('', $badges);
                                    }),

                                TextEntry::make('required_approvals')
                                    ->label('Required Approvals / الاعتمادات المطلوبة')
                                    ->badge()
                                    ->color('warning')
                                    ->formatStateUsing(function ($state) {
                                        if (is_array($state)) {
                                            return json_encode($state, JSON_UNESCAPED_UNICODE);
                                        }
                                        return "{$state} Required / {$state} مطلوب";
                                    }),
                            ])
                            ->columns(3)
                            ->contained(false),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }
}
