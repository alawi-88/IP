<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MyRequestsResource\Pages;
use App\Models\ApprovalRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MyRequestsResource extends Resource
{
    protected static ?string $model = ApprovalRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Notifications & Approvals';

    protected static ?string $navigationLabel = 'My Requests';

    protected static ?string $modelLabel = 'My Request / طلبي';

    protected static ?string $pluralModelLabel = 'My Requests / طلباتي';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('requested_by', Auth::id())
            ->with(['requestedBy', 'approvalWorkflow', 'approvalRequestLevels', 'program', 'target', 'application.competition', 'project']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Information / معلومات الطلب')
                    ->schema([
                        Forms\Components\TextInput::make('action')
                            ->label('Action / الإجراء')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('status')
                            ->label('Status / الحالة')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason / السبب')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(3),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason / سبب الرفض')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(3)
                            ->visible(fn ($record) => $record?->status === 'rejected'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Action Data / بيانات الإجراء')
                    ->schema([
                        Forms\Components\KeyValue::make('action_data')
                            ->label('Action Data / بيانات الإجراء')
                            ->disabled()
                            ->dehydrated(false)
                            ->keyLabel('Key / المفتاح')
                            ->valueLabel('Value / القيمة'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Request ID / رقم الطلب')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => '#' . $state),

                Tables\Columns\TextColumn::make('action')
                    ->label('Action / الإجراء')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return json_encode($state, JSON_UNESCAPED_UNICODE);
                        }
                        return ucfirst(str_replace('.', ' ', $state));
                    }),

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
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->target_type === 'App\\Models\\Competition') {
                            // Program: return its name/title
                            return $record->program?->title ?? (
                                (!empty($record->action_data['title']))
                                    ? (is_array($record->action_data['title'])
                                        ? ($record->action_data['title'][app()->getLocale()] ?? $record->action_data['title']['en'] ?? $record->action_data['title']['ar'] ?? reset($record->action_data['title']))
                                        : $record->action_data['title'])
                                    : 'N/A'
                            );
                        }
                        if ($record->target_type === 'App\\Models\\CompetitionApplication') {
                            // Application: return the application id
                            return 'Application #' . $record->application?->id ?? $state;
                        }
                        // default: just show the ID (project, others)
                        return $state;
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('target_display')
                    ->label('Target / الهدف')
                    ->getStateUsing(function ($record) {
                        // If it's a Project request, try to get the project name
                        if ($record->target_type === 'App\\Models\\Project') {
                            // First, try to get from database (if project still exists)
                            if ($record->project) {
                                $projectName = $record->project->form_submissions['project_name'] ?? null;
                                if ($projectName) {
                                    return $projectName;
                                }
                                // Fallback to project ID if name not available
                                return 'Project #' . $record->project->id;
                            }
                            
                            // If project was deleted, fall back to action_data
                            $actionData = $record->action_data ?? [];
                            if (isset($actionData['project_name']) && !empty($actionData['project_name'])) {
                                return $actionData['project_name'];
                            }
                            if (isset($actionData['project_id'])) {
                                return 'Project #' . $actionData['project_id'];
                            }
                        }
                        
                        // If it's a Competition request, try to get the competition name
                        if ($record->target_type === 'App\\Models\\Competition') {
                            // First, try to get from database (if competition still exists)
                            if ($record->program) {
                                return $record->program->title ?? 'N/A';
                            }
                            
                            // If competition was deleted, fall back to action_data
                            $actionData = $record->action_data ?? [];
                            if (isset($actionData['title'])) {
                                $title = $actionData['title'];
                                if (is_array($title)) {
                                    $locale = app()->getLocale();
                                    return $title[$locale] ?? $title['en'] ?? $title['ar'] ?? reset($title);
                                }
                                return $title;
                            }
                        }
                        
                        // If it's a CompetitionApplication request, try to get the competition name
                        if ($record->target_type === 'App\\Models\\CompetitionApplication') {
                            // Protect against null $record->application
                            if ($record->application && $record->application->competition) {
                                return $record->application->competition->title ?? 'N/A';
                            }
                            
                            // If competition was deleted, try to get from action_data
                            $actionData = $record->action_data ?? [];
                            if (isset($actionData['title'])) {
                                $title = $actionData['title'];
                                if (is_array($title)) {
                                    $locale = app()->getLocale();
                                    return $title[$locale] ?? $title['en'] ?? $title['ar'] ?? reset($title);
                                }
                                return $title;
                            }
                        }
                        
                        // Fall back to other action_data fields
                        $actionData = $record->action_data ?? [];
                        if (isset($actionData['title']) && !empty($actionData['title'])) {
                            return $actionData['title'];
                        }
                        if (isset($actionData['name']) && !empty($actionData['name'])) {
                            return $actionData['name'];
                        }
                        if (isset($actionData['email']) && !empty($actionData['email'])) {
                            return $actionData['email'];
                        }

                        return 'N/A';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status / الحالة')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        'returned' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending / قيد الانتظار',
                        'approved' => 'Approved / معتمد',
                        'rejected' => 'Rejected / مرفوض',
                        'cancelled' => 'Cancelled / ملغي',
                        'returned' => 'Returned / إعادة',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason / السبب')
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
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('current_level')
                    ->label('Current Level / المستوى الحالي')
                    ->getStateUsing(function ($record) {
                        $currentLevel = $record->getCurrentLevel();
                        if ($currentLevel) {
                            return "L{$currentLevel->level_number}";
                        }
                        return $record->isFullyApproved() ? 'Completed / مكتمل' : 'N/A';
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submission Date / تاريخ التقديم')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(false),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Status Update / آخر تحديث للحالة')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status / الحالة')
                    ->options([
                        'pending' => 'Pending / قيد الانتظار',
                        'approved' => 'Approved / معتمد',
                        'rejected' => 'Rejected / مرفوض',
                        'cancelled' => 'Cancelled / ملغي',
                    ]),

                Tables\Filters\SelectFilter::make('action')
                    ->label('Request Type / نوع الطلب')
                    ->options(function () {
                        return ApprovalRequest::where('requested_by', Auth::id())
                            ->distinct()
                            ->pluck('action')
                            ->mapWithKeys(fn ($action) => [$action => ucfirst(str_replace('.', ' ', $action))])
                            ->toArray();
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From Date / من تاريخ'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until Date / إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View Details / عرض التفاصيل'),
            ])
            ->bulkActions([
                // No bulk actions for my requests
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No Requests Found / لم يتم العثور على طلبات')
            ->emptyStateDescription('You have not submitted any approval requests yet. / لم تقم بتقديم أي طلبات موافقة بعد.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyRequests::route('/'),
            'view' => Pages\ViewMyRequest::route('/{record}'),
        ];
    }
}
