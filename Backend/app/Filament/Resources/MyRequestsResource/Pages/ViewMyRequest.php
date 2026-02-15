<?php

namespace App\Filament\Resources\MyRequestsResource\Pages;

use App\Filament\Resources\MyRequestsResource;
use App\Traits\SafeDataFormatting;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use App\Models\ApprovalRequestComment;
use Illuminate\Support\Facades\Log;
class ViewMyRequest extends ViewRecord
{
    use SafeDataFormatting;
    protected static string $resource = MyRequestsResource::class;


    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Check if the current user is authorized to view this request
        if ($record->requested_by !== auth()->id()) {
            $this->halt('You are not authorized to view this request / ليس لديك صلاحية لعرض هذا الطلب', 403);
        }

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to List / العودة للقائمة')
                ->url(route('filament.admin.resources.my-requests.index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),

            Actions\Action::make('cancel')
                ->label('Cancel Request / إلغاء الطلب')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Request / إلغاء الطلب')
                ->modalDescription('Are you sure you want to cancel this request? This action cannot be undone. / هل أنت متأكد من إلغاء هذا الطلب؟ لا يمكن التراجع عن هذا الإجراء.')
                ->visible(fn () => $this->record->status === 'pending' && $this->record->requested_by === auth()->id())
                ->action(function (): void {
                    $this->record->cancel();
                    \Filament\Notifications\Notification::make()
                        ->title('Request Cancelled / تم إلغاء الطلب')
                        ->body('The request has been cancelled successfully. / تم إلغاء الطلب بنجاح.')
                        ->success()
                        ->send();
                    
                    $this->redirect(route('filament.admin.resources.my-requests.index'));
                }),

            Actions\Action::make('add_comment')
                ->label('Add Comment / إضافة تعليق')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('primary')
                ->form([
                    Forms\Components\Textarea::make('comment')
                        ->label('Comment / التعليق')
                        ->required()
                        ->rows(3)
                        ->placeholder('Enter your comment here / أدخل تعليقك هنا'),

                    Forms\Components\Toggle::make('is_internal')
                        ->label('Internal Comment / تعليق داخلي')
                        ->helperText('Internal comments are only visible to admins / التعليقات الداخلية مرئية للمديرين فقط')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    ApprovalRequestComment::create([
                        'approval_request_id' => $this->record->id,
                        'user_id' => auth()->id(),
                        'comment' => $data['comment'],
                        'is_internal' => $data['is_internal'] ?? false,
                    ]);

                    Notification::make()
                        ->title('Comment Added / تم إضافة التعليق')
                        ->body('Comment added successfully / تم إضافة التعليق بنجاح')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Request Overview / نظرة عامة على الطلب')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Request ID / رقم الطلب')
                            ->formatStateUsing(function ($state) {
                                return $this->safeFormatState(function ($state) {
                                    if (is_string($state) || is_numeric($state)) {
                                        return '#' . $state;
                                }
                                    return 'No ID / لا يوجد معرف';
                                }, $state, 'id');
                            })
                            ->size('lg')
                            ->weight('bold'),

                        TextEntry::make('action')
                            ->label('Action / الإجراء')
                            ->formatStateUsing(function ($state) {
                                return $this->safeFormatState(function ($state) {
                                    if (is_string($state)) {
                                        return ucfirst(str_replace('.', ' ', $state));
                                }
                                    return 'Unknown action / إجراء غير معروف';
                                }, $state, 'action');
                            })
                            ->size('lg'),

                        TextEntry::make('status')
                            ->label('Current Status / الحالة الحالية')
                            ->badge()
                            ->size('lg')
                            ->color(function ($state): string {
                                if (is_array($state)) {
                                    return 'gray';
                                }
                                return match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'cancelled' => 'gray',
                                'returned' => 'info',
                                default => 'gray',
                                };
                            })
                            ->formatStateUsing(function ($state): string {
                                return $this->safeFormatState(function ($state) {
                                    if (is_string($state)) {
                                        return match ($state) {
                                'pending' => 'Pending / قيد الانتظار',
                                'approved' => 'Approved / معتمد',
                                'rejected' => 'Rejected / مرفوض',
                                'cancelled' => 'Cancelled / ملغي',
                                'returned' => 'Returned / إعادة',
                                default => ucfirst($state),
                                        };
                                    }
                                    return 'Unknown / غير معروف';
                                }, $state, 'status');
                            }),

                        TextEntry::make('created_at')
                            ->label('Submission Date / تاريخ التقديم')
                            ->formatStateUsing(function ($state) {
                                return $this->safeFormatState(function ($state) {
                                    // Handle Carbon/DateTime objects
                                    if ($state instanceof \DateTime || $state instanceof \Carbon\Carbon) {
                                        return $state->format('M d, Y H:i');
                                    }
                                    // Handle Carbon array representation
                                    if (is_array($state) && isset($state['formatted'])) {
                                        return $state['formatted'];
                                    }
                                    // Handle string dates
                                    if (is_string($state)) {
                                        try {
                                            return \Carbon\Carbon::parse($state)->format('M d, Y H:i');
                                        } catch (\Exception $e) {
                                            return $state;
                                        }
                                    }
                                    return 'No date / لا يوجد تاريخ';
                                }, $state, 'created_at');
                            }),

                        TextEntry::make('updated_at')
                            ->label('Last Updated / آخر تحديث')
                            ->formatStateUsing(function ($state) {
                                return $this->safeFormatState(function ($state) {
                                    // Handle Carbon/DateTime objects
                                    if ($state instanceof \DateTime || $state instanceof \Carbon\Carbon) {
                                        return $state->format('M d, Y H:i');
                                    }
                                    // Handle Carbon array representation
                                    if (is_array($state) && isset($state['formatted'])) {
                                        return $state['formatted'];
                                    }
                                    // Handle string dates
                                    if (is_string($state)) {
                                        try {
                                            return \Carbon\Carbon::parse($state)->format('M d, Y H:i');
                                        } catch (\Exception $e) {
                                            return $state;
                                        }
                                    }
                                    return 'No date / لا يوجد تاريخ';
                                }, $state, 'updated_at');
                            }),

                        TextEntry::make('target_type')
                            ->label('Target Type / نوع الهدف')
                            ->formatStateUsing(function ($state, $record) {
                                // Always show the model name, even if it's empty or not string
                                $modelMap = [
                                    'App\\Models\\Project' => 'Project / مشروع',
                                    'App\\Models\\Competition' => 'Program / برنامج',
                                    'App\\Models\\CompetitionApplication' => 'Application / طلب',
                                    'App\\Models\\Winner' => 'Winner / الفائزين',
                                ];
                                if (isset($modelMap[$state])) {
                                    return $modelMap[$state];
                                }
                                if (is_string($state) && !empty($state)) {
                                    return $state;
                                }
                                return 'No target type / لا يوجد نوع هدف';
                            }),

                        TextEntry::make('target_id')
                            ->label('Target ID / معرف الهدف')
                            ->formatStateUsing(function ($state, $record) {
                                if (is_string($state) || is_numeric($state)) {
                                    return (string) $state;
                                }
                                // Try to get target_id from record/action_data
                                if (isset($record->action_data['project_id'])) {
                                    return (string) $record->action_data['project_id'];
                                }
                                if (isset($record->action_data['competition_id'])) {
                                    return (string) $record->action_data['competition_id'];
                                }
                                return 'No target ID / لا يوجد معرف هدف';
                            }),

                        TextEntry::make('target')
                            ->label('Target / الهدف')
                            ->formatStateUsing(function ($state, $record) {
                                // Always go through SafeDataFormatting to avoid arrays/objects reaching the view
                                return $this->safeFormatState(function () use ($record) {
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
                                            $title = $record->program->title ?? 'N/A';
                                            // Handle translatable/array titles
                                            if (is_array($title)) {
                                                $locale = app()->getLocale();
                                                return $title[$locale] ?? $title['en'] ?? $title['ar'] ?? reset($title);
                                            }
                                            return $title;
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
                                            $title = $record->application->competition->title ?? 'N/A';
                                            if (is_array($title)) {
                                                $locale = app()->getLocale();
                                                return $title[$locale] ?? $title['en'] ?? $title['ar'] ?? reset($title);
                                            }
                                            return $title;
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
                                        $title = $actionData['title'];
                                        if (is_array($title)) {
                                            $locale = app()->getLocale();
                                            return $title[$locale] ?? $title['en'] ?? $title['ar'] ?? reset($title);
                                        }
                                        return $title;
                                    }
                                    if (isset($actionData['name']) && !empty($actionData['name'])) {
                                        return $actionData['name'];
                                    }
                                    if (isset($actionData['email']) && !empty($actionData['email'])) {
                                        return $actionData['email'];
                                    }
                                $toString = function ($value): string {
                                    if (is_array($value)) {
                                        $locale = app()->getLocale();
                                        $picked = $value[$locale] ?? $value['en'] ?? $value['ar'] ?? reset($value);
                                        return is_string($picked) ? $picked : json_encode($value, JSON_UNESCAPED_UNICODE);
                                    }
                                    if (is_bool($value)) {
                                        return $value ? '1' : '0';
                                    }
                                    if ($value === null) {
                                        return '';
                                    }
                                    return (string) $value;
                                };

                                // If it's a Project request, try to get the project name
                        if ($record->target_type === 'App\\Models\\Project') {
                            // First, try to get from database (if project still exists)
                            if ($record->project) {
                                $projectName = $record->project->form_submissions['project_name'] ?? null;
                                if ($projectName) {
                                    return $toString($projectName);
                                }
                                // Fallback to project ID if name not available
                                return 'Project #' . $record->project->id;
                            }
                            
                            // If project was deleted, fall back to action_data
                            $actionData = $record->action_data ?? [];
                            if (isset($actionData['project_name']) && !empty($actionData['project_name'])) {
                                return $toString($actionData['project_name']);
                            }
                            if (isset($actionData['project_id'])) {
                                return 'Project #' . $actionData['project_id'];
                            }
                        }
                        
                        // If it's a Competition request, try to get the competition name
                        if ($record->target_type === 'App\\Models\\Competition') {
                            // First, try to get from database (if competition still exists)
                            if ($record->program) {
                                return $toString($record->program->title ?? 'N/A');
                            }
                            
                            // If competition was deleted, fall back to action_data
                            $actionData = $record->action_data ?? [];
                            if (isset($actionData['title'])) {
                                $title = $actionData['title'];
                                if (is_array($title)) {
                                    $locale = app()->getLocale();
                                    return $toString($title[$locale] ?? $title['en'] ?? $title['ar'] ?? reset($title));
                                }
                                return $toString($title);
                            }
                        }
                        
                        // If it's a CompetitionApplication request, try to get the competition name
                        if ($record->target_type === 'App\\Models\\CompetitionApplication') {
                            // Protect against null $record->application
                            if ($record->application && $record->application->competition) {
                                return $toString($record->application->competition->title ?? 'N/A');
                            }
                            
                            // If competition was deleted, try to get from action_data
                            $actionData = $record->action_data ?? [];
                            if (isset($actionData['title'])) {
                                $title = $actionData['title'];
                                if (is_array($title)) {
                                    $locale = app()->getLocale();
                                    return $toString($title[$locale] ?? $title['en'] ?? $title['ar'] ?? reset($title));
                                }
                                return $toString($title);
                            }
                        }
                        
                        if ($record->target_type === 'App\\Models\\Winner') {
                            if ($record->target) {
                                // Winner::name is translatable JSON/array
                                return $toString($record->target->name ?? ('Winner #' . $record->target_id));
                            }
                            // fall back to action_data when target deleted/not created yet
                            $actionData = $record->action_data ?? [];
                            if (isset($actionData['name']) && !empty($actionData['name'])) {
                                return $toString($actionData['name']);
                            }
                            return 'Winner #' . ($record->target_id ?? ($actionData['winner_id'] ?? ''));
                        }

                        // Fall back to other action_data fields
                        $actionData = $record->action_data ?? [];
                        if (isset($actionData['title']) && !empty($actionData['title'])) {
                            return $toString($actionData['title']);
                        }
                        if (isset($actionData['name']) && !empty($actionData['name'])) {
                            return $toString($actionData['name']);
                        }
                        if (isset($actionData['email']) && !empty($actionData['email'])) {
                            return $toString($actionData['email']);
                        }

                                    return 'N/A';
                                }, $state, 'target');
                                // Fallback: prefer anything informative from action_data
                                // $ad = $record->action_data ?? [];
                                // if (isset($ad['title']) && !empty($ad['title'])) {
                                //     $title = $ad['title'];
                                //     if (is_array($title)) {
                                //         return $title['en'] ?? $title['ar'] ?? 'N/A';
                                //     }
                                //     return $title ?? 'N/A';
                                // }
                                // if (isset($record->target_id) && !empty($record->target_id)) {
                                //     if ($record->target_type === 'App\\Models\\Project') {
                                //         if ($record->project) {
                                //             $projectName = $record->project->form_submissions['project_name'] ?? null;
                                //             if ($projectName) { 
                                //                 return $projectName ?? 'N/A';
                                //             }
                                //             return 'Project #' . $record->project->id;
                                //         }
                                //         return 'No project information / لا توجد معلومات المشروع';
                                //     }

                                //     if ($record->target_type === 'App\\Models\\Competition') {
                                //         if ($record->program) {
                                //             return $record->program->title ?? 'N/A';
                                //         }
                                //         return 'No program information / لا توجد معلومات البرنامج';
                                //     }

                                //     if ($record->target_type === 'App\\Models\\CompetitionApplication') {
                                //         if ($record->application) {
                                //             return $record->application->competition->title ?? 'N/A';
                                //         }
                                //         return 'No application information / لا توجد معلومات الطلب';
                                //     }
                                //     return 'No target information / لا توجد معلومات الهدف';
                                // }

                                // if (isset($ad['project_name']) && !empty($ad['project_name'])) {
                                //     return $ad['project_name'] ?? 'N/A';
                                // }
                                // if (isset($ad['name']) && !empty($ad['name'])) {
                                //     return $ad['name'] ?? 'N/A';
                                // }
                                
                                // if (isset($ad['competition_id']) && !empty($ad['competition_id'])) {
                                //     return 'Application for #' . $ad['competition_id'];
                                // }
                                // return 'No target information / لا توجد معلومات الهدف';
                            }),


                        TextEntry::make('executed_at')
                            ->label('Executed At / تم التنفيذ في')
                            ->formatStateUsing(function ($state) {
                                return $this->safeFormatState(function ($state) {
                                    // Handle Carbon/DateTime objects
                                    if ($state instanceof \DateTime || $state instanceof \Carbon\Carbon) {
                                        return $state->format('M d, Y H:i');
                                    }
                                    // Handle Carbon array representation
                                    if (is_array($state) && isset($state['formatted'])) {
                                        return $state['formatted'];
                                    }
                                    // Handle string dates
                                    if (is_string($state)) {
                                        try {
                                            return \Carbon\Carbon::parse($state)->format('M d, Y H:i');
                                        } catch (\Exception $e) {
                                            return $state;
                                        }
                                    }
                                    return 'Not executed / لم يتم التنفيذ';
                                }, $state, 'executed_at');
                            }),
                    ])
                    ->columns(2),

                Section::make('Request Details / تفاصيل الطلب')
                    ->schema([
                        TextEntry::make('reason')
                            ->label('Reason / السبب')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->reason)
                            ->formatStateUsing(function ($state) {
                                return $this->safeFormatState(function ($state) {
                                    if (is_string($state)) {
                                        return $state;
                                }
                                    return 'No reason provided / لم يتم تقديم سبب';
                                }, $state, 'reason');
                            }),

                        TextEntry::make('rejection_reason')
                            ->label('Rejection Reason / سبب الرفض')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->rejection_reason)
                            ->formatStateUsing(function ($state) {
                                return $this->safeFormatState(function ($state) {
                                    if (is_string($state)) {
                                        return $state;
                                }
                                    return 'No rejection reason provided / لم يتم تقديم سبب الرفض';
                                }, $state, 'rejection_reason');
                            })
                            ->color('danger'),

                        ViewEntry::make('action_data')
                            ->label('Updated Content / المحتوى المحدث')
                            ->view('filament.custom-entries.action-data-display', [
                                'competition_id' => $record->action_data['competition_id'] ?? null
                            ])
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->action_data)),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Approval Workflow / مسار الموافقة')
                    ->schema([
                        TextEntry::make('approvalWorkflow.action')
                            ->label('Workflow Action / إجراء المسار')
                            ->formatStateUsing(function ($state) {
                                return $this->safeFormatState(function ($state) {
                                    if (is_string($state)) {
                                        return ucfirst(str_replace('.', ' ', $state));
                                }
                                    return 'Unknown action / إجراء غير معروف';
                                }, $state, 'approvalWorkflow.action');
                            }),

                        TextEntry::make('approvalWorkflow.levels')
                            ->label('Total Levels / إجمالي المستويات')
                            ->badge()
                            ->color('info')
                            ->formatStateUsing(function ($state) {
                                return $this->safeFormatState(function ($state) {
                                    if (is_string($state) || is_numeric($state)) {
                                        return "{$state} Levels / {$state} مرحلة";
                                }
                                    return 'Unknown levels / مستويات غير معروفة';
                                }, $state, 'approvalWorkflow.levels');
                            }),

                        TextEntry::make('current_level_status')
                            ->label('Current Level / المستوى الحالي')
                            ->getStateUsing(function ($record) {
                                try {
                                $currentLevel = $record->getCurrentLevel();
                                if ($currentLevel) {
                                    return "Level {$currentLevel->level_number} - Pending / المستوى {$currentLevel->level_number} - قيد الانتظار";
                                }
                                return $record->isFullyApproved() ? 'Completed / مكتمل' : 'N/A';
                                } catch (\Exception $e) {
                                    \Log::error('Error getting current_level_status: ' . $e->getMessage());
                                    return 'Error getting level status / خطأ في الحصول على حالة المستوى';
                                }
                            })
                            ->badge()
                            ->color('warning')
                            ->formatStateUsing(function ($state) {
                                return $this->safeFormatState(function ($state) {
                                    if (is_string($state)) {
                                        return $state;
                                }
                                    return 'N/A';
                                }, $state, 'current_level_status');
                            }),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Decision History / سجل القرارات')
                    ->schema([
                        TextEntry::make('decision_history')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                try {
                                    $levels = $record->approvalRequestLevels()->orderBy('level_number')->get();
                                    if ($levels->isEmpty()) {
                                        return 'No decisions yet / لا توجد قرارات بعد';
                                    }
                                    return null; // This will be handled by RepeatableEntry
                                } catch (\Exception $e) {
                                    \Log::error('Error getting decision_history: ' . $e->getMessage());
                                    return 'Error getting decision history / خطأ في الحصول على سجل القرارات';
                                }
                            })
                            ->visible(fn ($record) => $record->approvalRequestLevels()->count() === 0)
                            ->formatStateUsing(function ($state) {
                                return $this->safeFormatState(function ($state) {
                                    if (is_string($state)) {
                                        return $state;
                                    }
                                    return 'No decisions yet / لا توجد قرارات بعد';
                                }, $state, 'decision_history');
                            }),

                        RepeatableEntry::make('approvalRequestLevels')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                try {
                                    return $record->approvalRequestLevels()->with(['approver', 'votes.user.roles'])->orderBy('level_number')->get();
                                } catch (\Exception $e) {
                                    \Log::error('Error getting approvalRequestLevels: ' . $e->getMessage());
                                    return collect([]);
                                }
                            })
                            ->visible(fn ($record) => $record->approvalRequestLevels()->count() > 0)
                            ->schema([
                                TextEntry::make('level_number')
                                    ->label('Level / المستوى')
                                    ->badge()
                                    ->color('primary')
                                    ->formatStateUsing(function ($state) {
                                        return $this->safeFormatState(function ($state) {
                                            if (is_string($state) || is_numeric($state)) {
                                                return "Level {$state} / المستوى {$state}";
                                        }
                                            return 'Unknown Level / مستوى غير معروف';
                                        }, $state, 'level_number');
                                    }),

                                TextEntry::make('status')
                                    ->label('Decision / القرار')
                                    ->badge()
                                    ->color(function ($state): string {
                                        if (is_array($state)) {
                                            return 'gray';
                                        }
                                        return match ($state) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'returned' => 'info',
                                        default => 'gray',
                                        };
                                    })
                                    ->formatStateUsing(function ($state): string {
                                        return $this->safeFormatState(function ($state) {
                                            if (is_string($state)) {
                                                return match ($state) {
                                        'pending' => 'Pending / قيد الانتظار',
                                        'approved' => 'Approved / معتمد',
                                        'rejected' => 'Rejected / مرفوض',
                                        'returned' => 'Returned / إعادة',
                                        default => ucfirst($state),
                                                };
                                            }
                                            return 'Unknown / غير معروف';
                                        }, $state, 'status');
                                    }),

                                TextEntry::make('approved_at')
                                    ->label('Timestamp / الطابع الزمني')
                                    ->formatStateUsing(function ($state) {
                                        return $this->safeFormatState(function ($state) {
                                            // Handle Carbon/DateTime objects
                                            if ($state instanceof \DateTime || $state instanceof \Carbon\Carbon) {
                                                return $state->format('M d, Y H:i');
                                            }
                                            // Handle Carbon array representation
                                            if (is_array($state) && isset($state['formatted'])) {
                                                return $state['formatted'];
                                            }
                                            // Handle string dates
                                            if (is_string($state)) {
                                                try {
                                                    return \Carbon\Carbon::parse($state)->format('M d, Y H:i');
                                                } catch (\Exception $e) {
                                                    return $state;
                                                }
                                            }
                                            return 'N/A';
                                        }, $state, 'approved_at');
                                    }),

                                TextEntry::make('rejection_reason')
                                    ->label('Rejection Reason / سبب الرفض')
                                    ->visible(fn ($record) => $record->rejection_reason)
                                    ->formatStateUsing(function ($state) {
                                        return $this->safeFormatState(function ($state) {
                                            if (is_string($state)) {
                                                return $state;
                                        }
                                            return 'N/A';
                                        }, $state, 'rejection_reason');
                                    })
                                    ->color('danger'),

                                ViewEntry::make('votes_timeline')
                                    ->label('Decisions Timeline / خط زمني القرارات')
                                    ->getStateUsing(function ($record) {
                                        $required = (int) ($record->required_approvals ?? 1);
                                        $required = max($required, 1);

                                        $votes = $record->votes()
                                            ->with('user.roles')
                                            ->orderBy('created_at')
                                            ->get();

                                        $items = [];
                                        for ($i = 1; $i <= $required; $i++) {
                                            $vote = $votes->get($i - 1);

                                            if ($vote) {
                                                $roleNames = $vote->user?->roles?->pluck('name')->values()->all() ?? [];
                                                $items[] = [
                                                    'index' => $i,
                                                    'status' => $vote->decision,
                                                    'decision_maker' => ($vote->user?->name ?? 'Unknown') . ' -> ' . (!empty($roleNames) ? implode(', ', $roleNames) : '—'),
                                                    'comment' => $vote->comment,
                                                    'created_at' => $vote->created_at,
                                                ];
                                            } else {
                                                $items[] = [
                                                    'index' => $i,
                                                    'status' => 'pending',
                                                    'decision_maker' => null,
                                                    'comment' => null,
                                                    'created_at' => null,
                                                ];
                                            }
                                        }

                                        return $items;
                                    })
                                    ->view('filament.components.approval-level-votes-timeline')
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->contained(false)
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Comments / التعليقات')
                    ->schema([
                        TextEntry::make('no_comments')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                try {
                                    $comments = $record->comments()->count();
                                    if ($comments === 0) {
                                        return 'No comments yet / لا توجد تعليقات بعد';
                                    }
                                    return null;
                                } catch (\Exception $e) {
                                    \Log::error('Error getting no_comments: ' . $e->getMessage());
                                    return 'Error getting comments / خطأ في الحصول على التعليقات';
                                }
                            })
                            ->visible(fn ($record) => $record->comments()->count() === 0)
                            ->formatStateUsing(function ($state) {
                                return $this->safeFormatState(function ($state) {
                                    if (is_string($state)) {
                                        return $state;
                                    }
                                    return 'No comments yet / لا توجد تعليقات بعد';
                                }, $state, 'no_comments');
                            }),

                        RepeatableEntry::make('comments')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                try {
                                    $comments = $record->comments()->with('user')->orderBy('created_at', 'desc')->get();
                                    return $comments->toArray();
                                } catch (\Exception $e) {
                                    \Log::error('Error getting comments: ' . $e->getMessage());
                                    return [];
                                }
                            })
                            ->visible(fn ($record) => $record->comments()->count() > 0)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Commenter / المعلق')
                                    ->badge()
                                    ->color('primary')
                                    ->formatStateUsing(function ($state) {
                                        return $this->safeFormatState(function ($state) {
                                            if (is_string($state)) {
                                                return $state;
                                        }
                                            return 'Unknown User / مستخدم غير معروف';
                                        }, $state, 'user.name');
                                    }),

                                TextEntry::make('comment')
                                    ->label('Comment / التعليق')
                                    ->formatStateUsing(function ($state) {
                                        return $this->safeFormatState(function ($state) {
                                            if (is_string($state)) {
                                                return $state;
                                        }
                                            return 'No comment / لا يوجد تعليق';
                                        }, $state, 'comment');
                                    })
                                    ->markdown()
                                    ->columnSpanFull(),

                                TextEntry::make('created_at')
                                    ->label('Timestamp / الطابع الزمني')
                                    ->formatStateUsing(function ($state) {
                                        return $this->safeFormatState(function ($state) {
                                            // Handle Carbon/DateTime objects
                                            if ($state instanceof \DateTime || $state instanceof \Carbon\Carbon) {
                                                return $state->format('M d, Y H:i');
                                            }
                                            // Handle Carbon array representation
                                            if (is_array($state) && isset($state['formatted'])) {
                                                return $state['formatted'];
                                            }
                                            // Handle string dates
                                            if (is_string($state)) {
                                                try {
                                                    return \Carbon\Carbon::parse($state)->format('M d, Y H:i');
                                                } catch (\Exception $e) {
                                                    return $state;
                                                }
                                            }
                                            return 'No date / لا يوجد تاريخ';
                                        }, $state, 'created_at');
                                    }),

                                TextEntry::make('is_internal')
                                    ->label('Type / النوع')
                                    ->badge()
                                    ->color(function ($state) {
                                        if (is_array($state)) {
                                            return 'gray';
                                        }
                                        if (is_bool($state)) {
                                            return $state ? 'warning' : 'info';
                                        }
                                        return 'gray';
                                    })
                                    ->formatStateUsing(function ($state) {
                                        return $this->safeFormatState(function ($state) {
                                            if (is_bool($state)) {
                                        return $state ? 'Internal / داخلي' : 'Public / عام';
                                            }
                                            return 'Unknown / غير معروف';
                                        }, $state, 'is_internal');
                                    }),
                            ])
                            ->columns(3)
                            ->contained(false)
                    ])
                    ->collapsible()
                    ->collapsed(false),

            ]);
    }
}
