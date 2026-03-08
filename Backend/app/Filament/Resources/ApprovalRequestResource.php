<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApprovalRequestResource\Pages;
use App\Models\ApprovalRequest;
use App\Models\User;
use App\Notifications\ApprovalRequestStatusChanged;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class ApprovalRequestResource extends Resource
{
    protected static ?string $model = ApprovalRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Notifications & Approvals';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Approval Requests';

    protected static ?string $modelLabel = 'Approval Request';

    protected static ?string $pluralModelLabel = 'Approval Requests';

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view ApprovalRequest');
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('view ApprovalRequest');
    }

    public static function canCreate(): bool
    {
        return false; // Approval requests are created automatically
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update ApprovalRequest');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete ApprovalRequest');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete ApprovalRequest');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Details')
                    ->schema([
                        Forms\Components\TextInput::make('action')
                            ->label('Action / الإجراء')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('status')
                            ->label('Status / الحالة')
                            ->options([
                                'pending' => 'Pending / قيد الانتظار',
                                'approved' => 'Approved / موافق',
                                'rejected' => 'Rejected / مرفوض',
                                'cancelled' => 'Cancelled / ملغي',
                                'returned' => 'Returned / إعادة',
                            ])
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('requested_by')
                            ->label('Requested By / طلب بواسطة')
                            ->relationship('requestedBy', 'name')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('approval_workflow_id')
                            ->label('Approval Workflow / مسار الاعتماد')
                            ->relationship('approvalWorkflow', 'action')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason / السبب')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason / سبب الرفض')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Action Data')
                    ->schema([
                        Forms\Components\KeyValue::make('action_data')
                            ->label('Action Data / بيانات الإجراء')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Created At / تاريخ الإنشاء')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Approved At / تاريخ الموافقة')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DateTimePicker::make('rejected_at')
                            ->label('Rejected At / تاريخ الرفض')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->label('Cancelled At / تاريخ الإلغاء')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('action')
                    ->label('Action / الإجراء')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('target_type')
                    ->label('Type / النوع')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'App\\Models\\Competition' => 'info',
                        'App\\Models\\CompetitionApplication' => 'warning',
                        'App\\Models\\Form' => 'primary',
                        'App\\Models\\Project' => 'success',
                        'App\\Models\\Mentor' => 'info',
                        'App\\Models\\Judge' => 'warning',
                        'App\\Models\\Participant' => 'success',
                        'App\\Models\\Winner' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'App\\Models\\Competition' => 'Program / برنامج',
                        'App\\Models\\CompetitionApplication' => 'Application / طلب',
                        'App\\Models\\Form' => 'Form / نموذج',
                        'App\\Models\\Project' => 'Project / مشروع',
                        'App\\Models\\Mentor' => 'Mentor / مدرب',
                        'App\\Models\\Judge' => 'Judge / محكم',
                        'App\\Models\\Participant' => 'Participant / مشارك',
                        'App\\Models\\Winner' => 'Winner / الفائزين',
                        default => 'Other / آخر',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('target_id')
                    ->label('Target ID / معرف الهدف')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('secondary')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('program.title')
                    ->label('Program / البرنامج')
                    ->searchable()
                    ->sortable()
                    ->placeholder('N/A')
                    ->visible(fn ($record) => $record?->isProgramRequest()),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status / الحالة')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        'returned' => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending / قيد الانتظار',
                        'approved' => 'Approved / موافق',
                        'rejected' => 'Rejected / مرفوض',
                        'cancelled' => 'Cancelled / ملغي',
                        'returned' => 'Returned / إعادة',
                    }),

                Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('Requested By / طلب بواسطة')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return json_encode($state, JSON_UNESCAPED_UNICODE);
                        }
                        return $state ?: 'Unknown User / مستخدم غير معروف';
                    }),

                Tables\Columns\TextColumn::make('approvalWorkflow.action')
                    ->label('Workflow / المسار')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return json_encode($state, JSON_UNESCAPED_UNICODE);
                        }
                        return $state ?: 'No workflow / لا يوجد مسار';
                    }),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason / السبب')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            $state = json_encode($state, JSON_UNESCAPED_UNICODE);
                        }
                        return $state ?: 'No reason provided / لم يتم تقديم سبب';
                    })
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (is_array($state)) {
                            $state = json_encode($state, JSON_UNESCAPED_UNICODE);
                        }
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At / تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved At / تاريخ الموافقة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('rejected_at')
                    ->label('Rejected At / تاريخ الرفض')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status / الحالة')
                    ->options([
                        'pending' => 'Pending / قيد الانتظار',
                        'approved' => 'Approved / موافق',
                        'rejected' => 'Rejected / مرفوض',
                        'cancelled' => 'Cancelled / ملغي',
                        'returned' => 'Returned / إعادة',
                    ]),

                Tables\Filters\SelectFilter::make('action')
                    ->label('Action / الإجراء')
                    ->options([
                        // Competition Actions
                        'Competition.create' => 'Create Competition / إنشاء مسابقة',
                        'Competition.update' => 'Update Competition / تحديث مسابقة',
                        'Competition.delete' => 'Delete Competition / حذف مسابقة',
                        'Competition.archive' => 'Archive Competition / أرشفة مسابقة',
                        
                        // Competition Application Actions
                        'CompetitionApplication.update' => 'Update Competition Application / تحديث طلب مسابقة',
                        'CompetitionApplication.delete' => 'Delete Competition Application / حذف طلب مسابقة',
                        'CompetitionApplication.archive' => 'Archive Competition Application / أرشفة طلب مسابقة',
                        
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
                    ]),

                Tables\Filters\SelectFilter::make('requested_by')
                    ->label('Requested By / طلب بواسطة')
                    ->relationship('requestedBy', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('target_type')
                    ->label('Type / النوع')
                    ->options([
                        'App\\Models\\Competition' => 'Program / برنامج',
                        'App\\Models\\CompetitionApplication' => 'Application / طلب',
                        'App\\Models\\Form' => 'Form / نموذج',
                        'App\\Models\\Project' => 'Project / مشروع',
                        'App\\Models\\Mentor' => 'Mentor / مدرب',
                        'App\\Models\\Judge' => 'Judge / محكم',
                        'App\\Models\\Participant' => 'Participant / مشارك',
                        'App\\Models\\Winner' => 'Winner / الفائزين',
                    ]),

                Tables\Filters\Filter::make('program_requests')
                    ->label('Program Requests Only / طلبات البرامج فقط')
                    ->query(fn (Builder $query): Builder => $query->where('target_type', 'App\\Models\\Competition'))
                    ->toggle(),

                Tables\Filters\Filter::make('application_requests')
                    ->label('Application Requests Only / طلبات التطبيقات فقط')
                    ->query(fn (Builder $query): Builder => $query->where('target_type', 'App\\Models\\CompetitionApplication'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View / عرض'),
                Tables\Actions\Action::make('approve')
                    ->label('Approve / موافقة')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (ApprovalRequest $record): bool => $record->isPending() && auth()->user()->can('approve ApprovalRequest'))
                    ->requiresConfirmation()
                    ->modalHeading('Approve Request / موافقة على الطلب')
                    ->modalDescription('Are you sure you want to approve this request? / هل أنت متأكد من الموافقة على هذا الطلب؟')
                    ->action(function (ApprovalRequest $record) {
                        $service = app(\App\Services\ApprovalRequestService::class);
                        $currentLevel = $record->getCurrentLevel();
                        if (!$currentLevel) {
                            Notification::make()
                                ->title('No Pending Level / لا يوجد مستوى معلق')
                                ->body('There is no pending level to approve.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $result = $service->approveLevel($record, $currentLevel->level_number, auth()->user());
                        $required = $result['required'] ?? $currentLevel->required_approvals ?? 1;
                        $approvals = $result['approvals'] ?? 0;

                        if (($result['success'] ?? false) === true) {
                            if (($result['finalized'] ?? false) && ($result['status'] ?? null) === 'approved') {
                                Notification::make()
                                    ->title('Level Approved / تم الموافقة على المستوى')
                                    ->body("Level {$currentLevel->level_number} approved ({$approvals}/{$required}).")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Approval Recorded / تم تسجيل الموافقة')
                                    ->body("Recorded your approval for level {$currentLevel->level_number} ({$approvals}/{$required}). Waiting for more approvers.")
                                    ->info()
                                    ->send();
                            }
                        } else {
                            Notification::make()
                                ->title('Approval Failed / فشل الموافقة')
                                ->body($result['message'] ?? 'Failed to approve the level.')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject / رفض')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (ApprovalRequest $record): bool => $record->isPending() && auth()->user()->can('reject ApprovalRequest'))
                    ->requiresConfirmation()
                    ->modalHeading('Reject Request / رفض الطلب')
                    ->modalDescription('Are you sure you want to reject this request? / هل أنت متأكد من رفض هذا الطلب؟')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason / سبب الرفض')
                            ->required(),
                    ])
                    ->action(function (ApprovalRequest $record, array $data) {
                        $service = app(\App\Services\ApprovalRequestService::class);
                        $currentLevel = $record->getCurrentLevel();
                        if (!$currentLevel) {
                            Notification::make()
                                ->title('No Pending Level / لا يوجد مستوى معلق')
                                ->body('There is no pending level to reject.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $result = $service->rejectLevel($record, $currentLevel->level_number, auth()->user(), $data['rejection_reason']);
                        $required = $result['required'] ?? $currentLevel->required_approvals ?? 1;
                        $rejections = $result['rejections'] ?? 0;

                        if (($result['success'] ?? false) === true) {
                            if (($result['finalized'] ?? false) && ($result['status'] ?? null) === 'rejected') {
                                Notification::make()
                                    ->title('Level Rejected / تم رفض المستوى')
                                    ->body("Level {$currentLevel->level_number} rejected ({$rejections}/{$required}).")
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Rejection Recorded / تم تسجيل الرفض')
                                    ->body("Recorded your rejection for level {$currentLevel->level_number} ({$rejections}/{$required}). Waiting for more approvers.")
                                    ->info()
                                    ->send();
                            }
                        } else {
                            Notification::make()
                                ->title('Rejection Failed / فشل الرفض')
                                ->body($result['message'] ?? 'Failed to reject the level.')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('return')
                    ->label('Return / إعادة')
                    ->icon('heroicon-o-arrow-left')
                    ->color('gray')
                    ->visible(fn (ApprovalRequest $record): bool => $record->isPending() && auth()->user()->can('return ApprovalRequest'))
                    ->requiresConfirmation()
                    ->modalHeading('Return Request / إعادة الطلب')
                    ->modalDescription('Are you sure you want to return this request? / هل أنت متأكد من إعادة هذا الطلب؟')
                    ->action(function (ApprovalRequest $record) {
                        try {
                            $oldStatus = $record->status;
                            $record->update([
                                'status' => 'returned',
                                'returned_at' => now(),
                            ]);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error Returning Request / خطأ في إعادة الطلب')
                                ->body('An error occurred while returning the request. / حدث خطأ أثناء إعادة الطلب.')
                                ->error()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No approval requests found / لم يتم العثور على طلبات موافقة')
            ->emptyStateDescription('Approval requests will appear here when admins perform actions that require approval / ستظهر طلبات الموافقة هنا عندما يقوم المدراء بإجراءات تتطلب موافقة')
            ->emptyStateIcon('heroicon-o-clock');
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
            'index' => Pages\ListApprovalRequests::route('/'),
            'view' => Pages\ViewApprovalRequest::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'requestedBy', 
                'approvalWorkflow', 
                'approvalRequestLevels.approver', 
                'target', 
                'program', 
                'application.participant', 
                'project'
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
