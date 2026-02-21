<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApprovalWorkflowResource\Pages;
use App\Models\ApprovalWorkflow;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApprovalWorkflowResource extends Resource
{
    protected static ?string $model = ApprovalWorkflow::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'Notifications & Approvals';

    protected static ?string $navigationLabel = 'Policies';
    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Approval Policy / سياسة اعتماد';

    protected static ?string $pluralModelLabel = 'Approval Policies / سياسات الاعتماد';

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view ApprovalPolicies');
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('view ApprovalPolicies');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create ApprovalPolicies');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update ApprovalPolicies');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete ApprovalPolicies');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete ApprovalPolicies');
    }


    public static function canReplicate($record): bool
    {
        return auth()->user()->can('update ApprovalPolicies');
    }

    public static function canReorder(): bool
    {
        return auth()->user()->can('update ApprovalPolicies');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Workflow Details')
                    ->schema([
                        Forms\Components\Select::make('action')
                            ->label('Action / الإجراء')
                            ->options([
                                // Program/Program Actions
                                'Program.create' => 'Create Program / إنشاء برنامج',
                                'Program.update' => 'Update Program / تحديث برنامج',
                                'Program.delete' => 'Delete Program / حذف برنامج',
                                'Program.archive' => 'Archive Program / أرشفة برنامج',
                                'Program.restore' => 'Restore Program / استعادة برنامج',

                                // Program Application Actions
                                'ProgramApplication.create' => 'Create Application / إنشاء طلب',
                                'ProgramApplication.update' => 'Update Application / تحديث طلب',
                                'ProgramApplication.delete' => 'Delete Application / حذف طلب',
                                'ProgramApplication.archive' => 'Archive Application / أرشفة طلب',
                                'ProgramApplication.restore' => 'Restore Application / استعادة طلب',

                                // Form Actions
                                'Form.create' => 'Create Form / إنشاء نموذج',
                                'Form.update' => 'Update Form / تحديث نموذج',
                                'Form.delete' => 'Delete Form / حذف نموذج',
                                'Form.archive' => 'Archive Form / أرشفة نموذج',
                                'Form.restore' => 'Restore Form / استعادة نموذج',

                                // Project Actions
                                'Project.create' => 'Create Project / إنشاء مشروع',
                                'Project.update' => 'Update Project / تحديث مشروع',
                                'Project.delete' => 'Delete Project / حذف مشروع',
                                'Project.archive' => 'Archive Project / أرشفة مشروع',
                                'Project.restore' => 'Restore Project / استعادة مشروع',

                                // Team Actions
                                'Team.create' => 'Create Team / إنشاء فريق',
                                'Team.update' => 'Update Team / تحديث فريق',
                                'Team.delete' => 'Delete Team / حذف فريق',
                                'Team.archive' => 'Archive Team / أرشفة فريق',
                                'Team.restore' => 'Restore Team / استعادة فريق',

                                // Event Actions
                                'Event.create' => 'Create Event / إنشاء فعالية',
                                'Event.update' => 'Update Event / تحديث فعالية',
                                'Event.delete' => 'Delete Event / حذف فعالية',
                                'Event.archive' => 'Archive Event / أرشفة فعالية',

                                // Guideline Actions
                                'Guideline.create' => 'Create Guideline / إنشاء إرشاد',
                                'Guideline.update' => 'Update Guideline / تحديث إرشاد',
                                'Guideline.delete' => 'Delete Guideline / حذف إرشاد',

                                // Winner Actions
                                'Winner.create' => 'Create Winner / إنشاء فائز',
                                'Winner.update' => 'Update Winner / تحديث فائز',
                                'Winner.delete' => 'Delete Winner / حذف فائز',
                                'Winner.toggle_visibility' => 'Toggle Winner Visibility / إظهار/إخفاء فائز',

                                // Judge Actions
                                'Judge.create' => 'Create Judge / إنشاء محكم',
                                'Judge.update' => 'Update Judge / تحديث محكم',
                                'Judge.delete' => 'Delete Judge / حذف محكم',
                                'Judge.archive' => 'Archive Judge / أرشفة محكم',

                                // Mentor Actions
                                'Mentor.create' => 'Create Mentor / إنشاء مرشد',
                                'Mentor.update' => 'Update Mentor / تحديث مرشد',
                                'Mentor.delete' => 'Delete Mentor / حذف مرشد',
                                'Mentor.archive' => 'Archive Mentor / أرشفة مرشد',

                                // Participant Actions
                                'Participant.update' => 'Update Participant / تحديث مشارك',
                                'Participant.delete' => 'Delete Participant / حذف مشارك',
                                'Participant.archive' => 'Archive Participant / أرشفة مشارك',

                                // User/Admin Actions
                                'User.create' => 'Create Admin / إنشاء مدير',
                                'User.update' => 'Update Admin / تحديث مدير',
                                'User.delete' => 'Delete Admin / حذف مدير',
                                'User.archive' => 'Archive Admin / أرشفة مدير',

                                // Task Actions
                                'TaskTemplate.create' => 'Create Task Template / إنشاء قالب مهمة',
                                'TaskTemplate.update' => 'Update Task Template / تحديث قالب مهمة',
                                'TaskTemplate.delete' => 'Delete Task Template / حذف قالب مهمة',
                                'TaskAssignment.create' => 'Assign Task / تعيين مهمة',
                                'TaskAssignment.update' => 'Update Task Assignment / تحديث تعيين مهمة',
                                'TaskAssignment.delete' => 'Delete Task Assignment / حذف تعيين مهمة',
                                'TaskSubmission.approve' => 'Approve Task Submission / اعتماد تسليم مهمة',
                                'TaskSubmission.reject' => 'Reject Task Submission / رفض تسليم مهمة',

                                // Stage Actions
                                'Stage.create' => 'Create Stage / إنشاء مرحلة',
                                'Stage.update' => 'Update Stage / تحديث مرحلة',
                                'Stage.delete' => 'Delete Stage / حذف مرحلة',

                                // Track Actions
                                'Track.create' => 'Create Track / إنشاء مسار',
                                'Track.update' => 'Update Track / تحديث مسار',
                                'Track.delete' => 'Delete Track / حذف مسار',

                                // Branding Actions
                                'BrandingSetting.update' => 'Update Platform Branding / تحديث هوية المنصة',
                                'BrandingProgram.update' => 'Update Program Branding / تحديث هوية البرنامج',

                                // Evaluation Actions
                                'ProjectEvaluation.create' => 'Create Evaluation / إنشاء تقييم',
                                'ProjectEvaluation.update' => 'Update Evaluation / تحديث تقييم',
                                'ProjectEvaluation.delete' => 'Delete Evaluation / حذف تقييم',

                                // Notification Actions
                                'NotificationMessage.create' => 'Create Notification / إنشاء إشعار',
                                'NotificationMessage.update' => 'Update Notification / تحديث إشعار',
                                'NotificationMessage.delete' => 'Delete Notification / حذف إشعار',

                                // Service & Page Actions
                                'Service.create' => 'Create Service / إنشاء خدمة',
                                'Service.update' => 'Update Service / تحديث خدمة',
                                'Service.delete' => 'Delete Service / حذف خدمة',
                                'Page.create' => 'Create Page / إنشاء صفحة',
                                'Page.update' => 'Update Page / تحديث صفحة',
                                'Page.delete' => 'Delete Page / حذف صفحة',
                            ])
                            ->searchable()
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->helperText(fn (string $operation): string =>
                                $operation === 'edit'
                                    ? 'Action cannot be changed after creation / لا يمكن تغيير الإجراء بعد الإنشاء'
                                    : 'Select the action that requires approval / اختر الإجراء الذي يتطلب اعتماد'
                            ),

                        Forms\Components\TextInput::make('levels')
                            ->label('Levels / المراحل')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                // Update approval levels when levels count changes
                                $newCount = (int)$state;

                                if ($newCount > 0) {
                                    // Get current levels count from the form
                                    $currentLevels = $get('approvalLevels') ?? [];
                                    $currentCount = count($currentLevels);

                                    // Check if reducing levels
                                    if ($newCount < $currentCount && $currentCount > 0) {
                                        // Show warning about level reduction
                                        \Filament\Notifications\Notification::make()
                                            ->title('Level Reduction Warning / تحذير تقليل المراحل')
                                            ->body('Reducing levels will remove roles assigned to higher levels. / تقليل المراحل سيزيل الأدوار المخصصة للمراحل الأعلى.')
                                            ->warning()
                                            ->persistent()
                                            ->send();
                                    }

                                    // Clear existing levels first
                                    $set('approvalLevels', []);

                                    // Create new levels based on the count
                                    for ($i = 0; $i < $newCount; $i++) {
                                        $set("approvalLevels.{$i}.level_number", (int)$i + 1);
                                        $set("approvalLevels.{$i}.role_ids", []);
                                        $set("approvalLevels.{$i}.required_approvals", 1);
                                    }
                                }
                            })
                            ->helperText('Number of approval levels / عدد مراحل الاعتماد'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active / نشط')
                            ->default(true)
                            ->helperText('Enable or disable this workflow / تفعيل أو إلغاء تفعيل هذا المسار'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Approval Levels')
                    ->schema([
                        Forms\Components\Repeater::make('approvalLevels')
                            ->relationship('approvalLevels')
                            ->schema([
                                Forms\Components\TextInput::make('level_number')
                                    ->label('Level Number / رقم المرحلة')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true) // Changed to true to include in save
                                    ->default(1)
                                    ->live(false) // Don't trigger live updates
                                    ->rules(['min:1']),

                                Forms\Components\Select::make('role_ids')
                                    ->label('Roles / الأدوار')
                                    ->multiple()
                                    ->options(Role::all()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->helperText('Select roles for this approval level / اختر الأدوار لهذه المرحلة'),

                                Forms\Components\TextInput::make('required_approvals')
                                    ->label('Required Approvals / عدد الاعتمادات المطلوبة')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->live(false) // Don't trigger repeater updates
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $roleIds = $get('role_ids') ?? [];
                                        $roleCount = count($roleIds);
                                        $stateValue = (int)$state;

                                        if ($stateValue > $roleCount && $roleCount > 0) {
                                            $set('required_approvals', $roleCount);
                                        }
                                    })
                                    ->helperText('Number of approvals required from this level / عدد الاعتمادات المطلوبة من هذه المرحلة'),
                            ])
                            ->columns(3)
                            ->addable(false) // Disable manual adding since levels are auto-created
                            ->deletable(false) // Disable manual deletion since levels are auto-managed
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => "Level " . ($state['level_number'] ?? 'Unknown'))
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                // Only update level numbers if they don't exist or are incorrect
                                if (is_array($state)) {
                                    $needsLevelNumberUpdate = false;

                                    foreach ($state as $index => $level) {
                                        $currentLevelNumber = $level['level_number'] ?? null;
                                        $expectedLevelNumber = (int)$index + 1;

                                        // Only update if level_number is completely missing or invalid
                                        // Do NOT update if it's a valid number, even if it doesn't match the index
                                        if ($currentLevelNumber === null ||
                                            $currentLevelNumber === '' ||
                                            $currentLevelNumber === 0 ||
                                            !is_numeric($currentLevelNumber)) {
                                            $set("approvalLevels.{$index}.level_number", $expectedLevelNumber);
                                            $needsLevelNumberUpdate = true;
                                        }
                                    }

                                    // Always check for duplicate roles when any change occurs
                                    static::checkDuplicateRolesStatic($state, $set, $get);
                                }
                            })
                    ])
                    ->visible(fn (Forms\Get $get) => $get('levels') > 0),
            ]);
    }


    /**
     * Check for duplicate roles across workflow levels (static version for callbacks)
     */
    public static function checkDuplicateRolesStatic(array $levels, $set, $get): void
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

    /**
     * Check for duplicate roles across workflow levels
     */
    protected function checkDuplicateRoles(array $levels, $set, $get): void
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('action')
                    ->label('Action / الإجراء')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->toggleable(false), // Make column non-toggleable

                Tables\Columns\TextColumn::make('levels')
                    ->label('Levels / المراحل')
                    ->badge()
                    ->color('info')
                    ->toggleable(false), // Make column non-toggleable

                Tables\Columns\TextColumn::make('roles_by_level')
                    ->label('Roles by Level / الأدوار حسب المرحلة')
                    ->getStateUsing(function (ApprovalWorkflow $record): string {
                        $levels = $record->approvalLevels()->orderBy('level_number')->get();
                        $result = [];

                        foreach ($levels as $level) {
                            $roleNames = $level->getRoleNames();

                            $roleNames = empty($roleNames) ? ['No Roles Assigned'] : $roleNames;

                            // Format roles as individual chips for each level
                            $roleChips = collect($roleNames)->map(function ($role) {
                                return "<span class='inline-flex items-center px-1 py-0.5 rounded text-xs font-light bg-blue-100 text-blue-800 dark:bg-gray-700 dark:text-white'>{$role}</span>";
                            })->join(' ');

                            $result[] = $roleChips;
                        }

                        // Join levels with arrows to show flow
                        return implode(' <span class="text-gray-600 dark:text-gray-400">→</span> ', $result);
                    })
                    ->html()
                    ->wrap()
                    ->searchable(false), // Disable search for this virtual column

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status / الحالة')
                    ->disabled(fn () => ! auth()->user()->can('update ApprovalPolicies')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated / آخر تحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(false), // Make column non-toggleable so it cannot be hidden

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At / تاريخ الإنشاء')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Action / الإجراء')
                    ->options(fn () => \App\Models\ApprovalWorkflow::query()
                        ->distinct()
                        ->pluck('action', 'action')
                        ->toArray()
                    )
                    ->searchable(),

                Tables\Filters\SelectFilter::make('roles')
                    ->label('Roles / الأدوار')
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('approvalLevels', function (Builder $query) use ($data) {
                            $query->where(function (Builder $query) use ($data) {
                                foreach ($data['values'] as $roleId) {
                                    $query->orWhere(function (Builder $query) use ($roleId) {
                                        // Try both string and integer formats for role IDs
                                        $query->whereRaw('JSON_CONTAINS(role_ids, ?)', [json_encode((string) $roleId)])
                                              ->orWhereRaw('JSON_CONTAINS(role_ids, ?)', [json_encode((int) $roleId)]);
                                    });
                                }
                            });
                        });
                    })
                    ->options(function () {
                        return \Spatie\Permission\Models\Role::all()->pluck('name', 'id');
                    })
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status / الحالة')
                    ->options([
                        1 => 'Active / نشط',
                        0 => 'Inactive / غير نشط',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === null) {
                            return $query;
                        }
                        return $query->where('is_active', (bool) $data['value']);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View / عرض')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->label('Edit / تعديل')
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->label('Delete / حذف')
                    ->icon('heroicon-o-trash')
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
                    }),
            ])
            ->searchable()
            ->modifyQueryUsing(function (Builder $query) {
                $search = request('tableSearch');
                if ($search) {
                    // First, get role IDs that match the search term
                    $matchingRoleIds = \Spatie\Permission\Models\Role::where('name', 'like', "%{$search}%")->pluck('id')->toArray();

                    $query->where(function (Builder $query) use ($search, $matchingRoleIds) {
                        // Search in action column
                        $query->where('action', 'like', "%{$search}%")
                              // Search in role IDs (if search term is numeric)
                              ->orWhereHas('approvalLevels', function (Builder $query) use ($search) {
                                  $query->whereRaw('JSON_CONTAINS(role_ids, ?)', [json_encode((string) $search)])
                                        ->orWhereRaw('JSON_CONTAINS(role_ids, ?)', [json_encode((int) $search)]);
                              });

                        // Search by matching role IDs
                        if (!empty($matchingRoleIds)) {
                            $query->orWhereHas('approvalLevels', function (Builder $query) use ($matchingRoleIds) {
                                foreach ($matchingRoleIds as $roleId) {
                                    $query->orWhereRaw('JSON_CONTAINS(role_ids, ?)', [json_encode((string) $roleId)]);
                                }
                            });
                        }
                    });
                }
                return $query;
            })
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected / حذف المحدد')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Workflows / حذف المسارات المحددة')
                        ->modalDescription('Are you sure you want to delete the selected workflow policies? This will not affect existing approval requests. / هل أنت متأكد أنك تريد حذف مسارات الاعتماد المحددة؟ لن يؤثر ذلك على الطلبات قيد التنفيذ.')
                        ->modalSubmitActionLabel('Confirm Delete / تأكيد الحذف')
                        ->modalCancelActionLabel('Cancel / إلغاء')
                        ->successNotification(
                            \Filament\Notifications\Notification::make()
                                ->title('Workflows Deleted Successfully / تم حذف مسارات الاعتماد بنجاح')
                                ->body('The selected workflow policies have been removed from the system. / تم إزالة مسارات الاعتماد المحددة من النظام.')
                                ->success()
                        ),
                ]),
            ])
            ->emptyStateHeading('No approval workflows configured / لم يتم إعداد أي مسارات اعتماد')
            ->emptyStateDescription('Create your first approval workflow policy to get started / أنشئ أول سياسة مسار اعتماد للبدء')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApprovalWorkflows::route('/'),
            'create' => Pages\CreateApprovalWorkflow::route('/create'),
            'view' => Pages\ViewApprovalWorkflow::route('/{record}'),
            'edit' => Pages\EditApprovalWorkflow::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
