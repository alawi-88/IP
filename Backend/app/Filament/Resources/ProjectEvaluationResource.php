<?php

namespace App\Filament\Resources;

use App\Filament\Exports\EvaluationExporter;
use App\Filament\Resources\ProjectEvaluationResource\Pages;
use App\Filament\Resources\ProjectEvaluationResource\RelationManagers;
use App\Filament\Traits\CanBeDeletable;
use App\Models\Judge;
use App\Models\ProjectEvaluation;
use App\Models\ProjectEvaluationNote;
use App\Models\SubTrack;
use App\Models\Track;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Colors\Color;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProjectEvaluationResource extends Resource
{
    use CanBeDeletable;

    protected static ?string $model = \App\Models\FormEvaluationScore::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Evaluations';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationGroup = 'Programs';


    public static function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\FormEvaluationScore::query()
                    ->selectRaw('
                        form_evaluation_scores.id AS id,
                        form_evaluation_scores.judge_project_id,
                        form_evaluation_scores.form_id,
                        JSON_UNQUOTE(JSON_EXTRACT(forms.name, "$.' . app()->getLocale() . '")) AS form_name,
                        form_evaluation_scores.evaluation_score as final_score,
                        form_evaluation_scores.created_at,
                        (SELECT AVG(answer) FROM project_evaluations 
                         WHERE judge_project_id = form_evaluation_scores.judge_project_id 
                         AND form_id = form_evaluation_scores.form_id 
                         AND is_archived = false) as average_score,
                        form_evaluation_scores.exclude_from_calculation
                    ')
                    ->join('judge_projects', 'judge_projects.id', '=', 'form_evaluation_scores.judge_project_id')
                    ->join('judges', 'judges.id', '=', 'judge_projects.judge_id')
                    ->join('projects', 'projects.id', '=', 'judge_projects.project_id')
                    ->join('forms', 'forms.id', '=', 'form_evaluation_scores.form_id')
                    ->whereHas('judgeProject', fn($query) => $query->whereHas('project', fn($query) => $query->byCompetition()))
                    ->where('form_evaluation_scores.is_archived', false)
                    ->orderBy('form_evaluation_scores.form_id')
            )
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('submission_id')
                    ->label('Submission ID')
                    ->getStateUsing(fn($record) => $record->id ?? '—'),

                Tables\Columns\TextColumn::make('project')
                    ->label('Project')
                    ->getStateUsing(fn($record) => $record->judgeProject->project->form_submissions['project_name'] ?? '—'),

                Tables\Columns\TextColumn::make('form_name')
                    ->label('Form')
                    ->getStateUsing(fn ($record) => $record->form?->name ?? '—')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('judge')
                    ->getStateUsing(fn($record) => $record->judgeProject->judge->name)
                    ->sortable(query: fn($query, $direction) =>
                    $query->orderBy('judges.name', $direction)
                    )
                    ->searchable(true, fn($query, $search) =>
                    $query->where('judges.name', 'like', '%' . $search . '%')
                    ),

                Tables\Columns\TextColumn::make('track')
                    ->label('Track')
                    ->getStateUsing(function ($record) {
                        $trackId = $record->judgeProject->project->form_submissions['track'] ?? null;
                        return $trackId ? Track::find($trackId)?->name ?? '—' : '—';
                    }),

                Tables\Columns\TextColumn::make('sub_track')
                    ->label('Sub-Track')
                    ->getStateUsing(function ($record) {
                        $subTrackId = $record->judgeProject->project->form_submissions['sub_track'] ?? null;
                        return $subTrackId ? SubTrack::find($subTrackId)?->name ?? '—' : '—';
                    }),

                Tables\Columns\TextColumn::make('final_score')
                    ->label('Evaluation Score')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(function ($record) {
                        $score = $record->final_score;
                        return is_null($score) ? '—' : (fmod($score, 1) === 0.0 ? intval($score) : rtrim(rtrim(number_format($score, 2, '.', ''), '0'), '.')) . '%';
                    })
                    ->sortable(query: fn($query, $direction) =>
                    $query->orderBy('form_evaluation_scores.evaluation_score', $direction)
                    ),

                Tables\Columns\IconColumn::make('exclude_from_calculation')
                    ->label('Included in Average')
                    ->boolean()
                    ->getStateUsing(fn($record) => !$record->exclude_from_calculation)
                    ->icon(fn(bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn(bool $state) => $state ? 'success' : 'danger')
                    ->tooltip(fn($record) => $record->exclude_from_calculation ? 'Excluded from average calculation' : 'Included in average calculation'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable(query: fn($query, $direction) =>
                    $query->orderBy('project_evaluations.created_at', $direction)
                    )
                    ->searchable(true, fn($query, $search) =>
                    $query->where('project_evaluations.created_at', 'like', '%' . $search . '%')
                    ),

                Tables\Columns\IconColumn::make('has_comments')
                    ->label('Comments')
                    ->boolean()
                    ->getStateUsing(fn($record) => ProjectEvaluation::where('judge_project_id', $record->judge_project_id)
                        ->where('form_id', $record->form_id)
                        ->whereHas('notes')
                        ->exists())
                    ->icon(fn(bool $state) => $state ? 'heroicon-o-chat-bubble-left-ellipsis' : 'heroicon-o-x-mark')
                    ->color(fn(bool $state) => $state ? 'success' : 'gray')
                    ->visible(fn () => auth()->user()?->can('view ProjectEvaluation') ?? false),

            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modal()
                    ->modalHeading(fn($record) => 'Evaluations for [' . ($record->judgeProject?->judge->name) . ']')
                    ->modalContent(fn($record) => view('filament.modals.evaluations', [
                        'evaluations' => ProjectEvaluation::where('judge_project_id', $record->judge_project_id)
                            ->where('form_id', $record->form_id)
                            ->get(),
                        'formScore' => \App\Models\FormEvaluationScore::where('judge_project_id', $record->judge_project_id)
                            ->where('form_id', $record->form_id)
                            ->first(),
                        'form' => \App\Models\Form::find($record->form_id),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),

                Action::make('add_comment')
                    ->label('Add Comment')
                    ->icon('heroicon-o-chat-bubble-left')
                    ->color(Color::Amber)
                    ->modalHeading('Add Evaluation Comment')
                    ->form([
                        Select::make('type')
                            ->label('Comment Type')
                            ->options(ProjectEvaluationNote::TYPES)
                            ->required(),
                        Textarea::make('content')
                            ->label('Comment')
                            ->required()
                            ->maxLength(1000)
                            ->rows(4),
                    ])
                    ->action(function (array $data, $record) {
                        // Find a ProjectEvaluation record that matches this FormEvaluationScore
                        $projectEvaluation = ProjectEvaluation::where('judge_project_id', $record->judge_project_id)
                            ->where('form_id', $record->form_id)
                            ->where('stage_id', $record->stage_id)
                            ->where('is_archived', false)
                            ->first();

                        if (!$projectEvaluation) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('No active evaluation found to attach the comment to.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Create the note on the ProjectEvaluation record
                        $projectEvaluation->notes()->create([
                            'admin_id' => auth()->id(),
                            'type' => $data['type'],
                            'content' => $data['content'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Comment Added')
                            ->body('The comment has been successfully added.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => auth()->user()?->can('update ProjectEvaluation') ?? false && !$record->isArchived()),

                Action::make('view_comments')
                    ->label('View Comments')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color(Color::Blue)
                    ->modalContent(function($record) {
                        // Find a ProjectEvaluation record that matches this FormEvaluationScore
                        $projectEvaluation = ProjectEvaluation::where('judge_project_id', $record->judge_project_id)
                            ->where('form_id', $record->form_id)
                            ->where('stage_id', $record->stage_id)
                            ->where('is_archived', false)
                            ->first();

                        if (!$projectEvaluation) {
                            return '<div class="p-4 text-gray-500">No evaluation found to display comments.</div>';
                        }

                        return view('filament.modals.comments-modal', [
                            'evaluationId' => $projectEvaluation->id
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Action::make('archive')
                    ->label(__('evaluation_archive.archive'))
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('evaluation_archive.confirm_archive'))
                    ->modalDescription(__('evaluation_archive.archive_confirmation'))
                    ->action(function ($record) {
                        try {
                            $record->archive();
                            
                            \Filament\Notifications\Notification::make()
                                ->title(__('evaluation_archive.evaluation_archived'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('evaluation_archive.failed_to_archive'))
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => static::canArchive($record)),

                Action::make('restore')
                    ->label(__('evaluation_archive.restore'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('evaluation_archive.confirm_restore'))
                    ->modalDescription(__('evaluation_archive.restore_confirmation'))
                    ->action(function ($record) {
                        try {
                            $record->restore();
                            
                            \Filament\Notifications\Notification::make()
                                ->title(__('evaluation_archive.evaluation_restored'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('evaluation_archive.failed_to_restore'))
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => static::canRestore($record)),

                Action::make('exclude_from_calculation')
                    ->label('Exclude from Average')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Exclude from Average Calculation')
                    ->modalDescription('This evaluation will be excluded from the project\'s average score calculation but will remain in the system.')
                    ->action(function ($record) {
                        try {
                            $record->excludeFromCalculation();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Evaluation Excluded from Average')
                                ->body('This evaluation has been excluded from average calculations.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to Exclude Evaluation')
                                ->body('An error occurred while excluding the evaluation.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => !$record->exclude_from_calculation && static::canUpdate($record) && !$record->isArchived()),

                Action::make('include_in_calculation')
                    ->label('Include in Average')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Include in Average Calculation')
                    ->modalDescription('This evaluation will be included in the project\'s average score calculation.')
                    ->action(function ($record) {
                        try {
                            $record->includeInCalculation();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Evaluation Included in Average')
                                ->body('This evaluation has been included in average calculations.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to Include Evaluation')
                                ->body('An error occurred while including the evaluation.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => !$record->isArchived() && $record->exclude_from_calculation && static::canUpdate($record)),


                Tables\Actions\DeleteAction::make()
                    ->action(function ($record) {
                        ProjectEvaluation::where('judge_project_id', $record->judge_project_id)
                            ->where('form_id', $record->form_id)
                            ->get()
                            ->each
                            ->delete();
                        $record->judgeProject?->update(['evaluation_score' => 0]);
                        $record->judgeProject?->project->updateScore();
                    })
                    ->visible(fn () => auth()->user()?->can('delete ProjectEvaluation') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('archive')
                        ->label(__('evaluation_archive.archive_selected'))
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading(__('evaluation_archive.confirm_archive'))
                        ->modalDescription(__('evaluation_archive.archive_selected_confirmation'))
                        ->action(function (Collection $records) {
                            $count = 0;
                            $alreadyArchived = 0;
                            
                            try {
                                $records->each(function ($record) use (&$count, &$alreadyArchived) {
                                    if (!$record->isArchived()) {
                                        $record->archive();
                                        $count++;
                                    } else {
                                        $alreadyArchived++;
                                    }
                                });
                                
                                if ($count > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('evaluation_archive.evaluations_archived'))
                                        ->body(__('evaluation_archive.successfully_archived_count', ['count' => $count]))
                                        ->success()
                                        ->send();
                                }
                                
                                if ($alreadyArchived > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('evaluation_archive.warning'))
                                        ->body(__('evaluation_archive.already_archived_count', ['count' => $alreadyArchived]))
                                        ->warning()
                                        ->send();
                                }
                                
                                if ($count === 0 && $alreadyArchived > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('evaluation_archive.no_action_taken'))
                                        ->body(__('evaluation_archive.all_selected_already_archived'))
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('evaluation_archive.failed_to_archive_selected'))
                                    ->body(__('evaluation_archive.error_occurred'))
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('archive ProjectEvaluation') ?? false),

                    Tables\Actions\BulkAction::make('restore')
                        ->label(__('evaluation_archive.restore_selected'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('evaluation_archive.confirm_restore'))
                        ->modalDescription(__('evaluation_archive.restore_selected_confirmation'))
                        ->action(function (Collection $records) {
                            $count = 0;
                            $alreadyActive = 0;
                            
                            try {
                                $records->each(function ($record) use (&$count, &$alreadyActive) {
                                    if ($record->isArchived()) {
                                        $record->restore();
                                        $count++;
                                    } else {
                                        $alreadyActive++;
                                    }
                                });
                                
                                if ($count > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('evaluation_archive.evaluations_restored'))
                                        ->body(__('evaluation_archive.successfully_restored_count', ['count' => $count]))
                                        ->success()
                                        ->send();
                                }
                                
                                if ($alreadyActive > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('evaluation_archive.warning'))
                                        ->body(__('evaluation_archive.already_active_count', ['count' => $alreadyActive]))
                                        ->warning()
                                        ->send();
                                }
                                
                                if ($count === 0 && $alreadyActive > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('evaluation_archive.no_action_taken'))
                                        ->body(__('evaluation_archive.all_selected_already_active'))
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('evaluation_archive.failed_to_restore_selected'))
                                    ->body(__('evaluation_archive.error_occurred'))
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('restore ProjectEvaluation') ?? false),

                    Tables\Actions\BulkAction::make('exclude_from_calculation')
                        ->label('Exclude from Average')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Exclude from Average Calculation')
                        ->modalDescription('Selected evaluations will be excluded from average calculations but will remain in the system.')
                        ->action(function (Collection $records) {
                            $count = 0;
                            $alreadyExcluded = 0;
                            
                            try {
                                $records->each(function ($record) use (&$count, &$alreadyExcluded) {
                                    if (!$record->exclude_from_calculation) {
                                        $record->excludeFromCalculation();
                                        $count++;
                                    } else {
                                        $alreadyExcluded++;
                                    }
                                });
                                
                                if ($count > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Evaluations Excluded from Average')
                                        ->body("Successfully excluded {$count} evaluation(s) from average calculations.")
                                        ->success()
                                        ->send();
                                }
                                
                                if ($alreadyExcluded > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Warning')
                                        ->body("{$alreadyExcluded} evaluation(s) were already excluded from calculations.")
                                        ->warning()
                                        ->send();
                                }
                                
                                if ($count === 0 && $alreadyExcluded > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('No Action Taken')
                                        ->body('All selected evaluations were already excluded from calculations.')
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Failed to Exclude Evaluations')
                                    ->body('An error occurred while excluding evaluations.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('update ProjectEvaluation') ?? false),

                    Tables\Actions\BulkAction::make('include_in_calculation')
                        ->label('Include in Average')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Include in Average Calculation')
                        ->modalDescription('Selected evaluations will be included in average calculations.')
                        ->action(function (Collection $records) {
                            $count = 0;
                            $alreadyIncluded = 0;
                            
                            try {
                                $records->each(function ($record) use (&$count, &$alreadyIncluded) {
                                    if ($record->exclude_from_calculation) {
                                        $record->includeInCalculation();
                                        $count++;
                                    } else {
                                        $alreadyIncluded++;
                                    }
                                });
                                
                                if ($count > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Evaluations Included in Average')
                                        ->body("Successfully included {$count} evaluation(s) in average calculations.")
                                        ->success()
                                        ->send();
                                }
                                
                                if ($alreadyIncluded > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Warning')
                                        ->body("{$alreadyIncluded} evaluation(s) were already included in calculations.")
                                        ->warning()
                                        ->send();
                                }
                                
                                if ($count === 0 && $alreadyIncluded > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('No Action Taken')
                                        ->body('All selected evaluations were already included in calculations.')
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Failed to Include Evaluations')
                                    ->body('An error occurred while including evaluations.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('update ProjectEvaluation') ?? false),

                    Tables\Actions\DeleteBulkAction::make()
                        ->action(fn(Collection $records) =>
                        $records->each(function ($record) {
                            ProjectEvaluation::where('judge_project_id', $record->judge_project_id)
                                ->where('form_id', $record->form_id)
                                ->get()
                                ->each
                                ->delete();
                            $record->judgeProject?->update(['evaluation_score' => 0]);
                            $record->judgeProject?->project->updateScore();
                        })
                        )
                        ->visible(fn () => auth()->user()?->can('delete ProjectEvaluation') ?? false),
                ]),

                ExportBulkAction::make()
                    ->exporter(EvaluationExporter::class)
                    ->columnMapping(false)
                    ->fileName('Evaluations_List_' . now()->format('Y-m-d'))
                    ->visible(fn () => auth()->user()?->can('export ProjectEvaluation') ?? false)
                    ->after(function (\Filament\Actions\Exports\Models\Export $export) {
                        activity('EvaluationExport')
                            ->performedOn($export)
                            ->causedBy(auth()->user())
                            ->event('exported')
                            ->withProperties([
                                'exported_rows' => $export->successful_rows,
                                'failed_rows' => $export->getFailedRowsCount(),
                                'file_name' => $export->file_name,
                            ])
                            ->log(auth()->user()->name . ' exported evaluations');
                    }),
            ])

            ->filters([
                SelectFilter::make('track_id')
                    ->label('Track')
                    ->placeholder('All Tracks')
                    ->options(fn () => \App\Models\Track::pluck('name', 'id')->toArray())
                    ->query(function ($query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereHas('judgeProject.project', function ($q) use ($data) {
                                $q->where('form_submissions->track', (int) $data['value']);
                            });
                        }
                    }),

                SelectFilter::make('judge_id')
                    ->options(fn() => Judge::pluck('name', 'id'))
                    ->label('Judge')
                    ->placeholder('Select Judge'),

                SelectFilter::make('exclude_from_calculation')
                    ->label('Included in Average')
                    ->options([
                        '0' => 'Included in Average',
                        '1' => 'Excluded from Average',
                    ])
                    ->placeholder('All Evaluations')
                    ->query(function ($query, array $data) {
                        if (filled($data['value'])) {
                            $query->where('form_evaluation_scores.exclude_from_calculation', (bool) $data['value']);
                        }
                    }),
            ]);
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
            'index' => Pages\ListProjectEvaluations::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view ProjectEvaluation') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update ProjectEvaluation') && !$record->isArchived()   ;
    }

    public static function canUpdate(Model $record): bool
    {
        // Archived records cannot be updated - only deleted or restored
        if ($record->isArchived()) {
            return false;
        }
        
        return auth()->user()?->can('update ProjectEvaluation');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete ProjectEvaluation');
    }

    public static function canArchive(Model $record): bool
    {
        return auth()->user()?->can('archive ProjectEvaluation') && !$record->isArchived();
    }

    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->can('restore ProjectEvaluation') && $record->isArchived();
    }
}
