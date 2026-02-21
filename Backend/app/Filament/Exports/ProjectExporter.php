<?php

namespace App\Filament\Exports;

use App\Models\Form;
use App\Models\FormField;
use App\Models\Project;
use App\Models\Track;
use App\Models\SubTrack;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ProjectExporter extends BaseExporter
{
    protected static ?string $model = Project::class;

    /**
     * Get project form fields to use as dynamic export columns.
     * Uses current program from session when set; otherwise all forms that have projects.
     */
    protected static function getFormFieldsForExport(): \Illuminate\Support\Collection
    {
        $currentProgramId = session('current_program_id');
        if (!$currentProgramId && function_exists('request') && request()->hasSession()) {
            $currentProgramId = request()->session()->get('current_program_id');
        }

        if ($currentProgramId) {
            $program = \App\Models\Program::find($currentProgramId);
            if ($program) {
                $projectStage = $program->projectStage();
                if ($projectStage?->form_id) {
                    $form = Form::where('id', $projectStage->form_id)
                        ->projectType()
                        ->published()
                        ->active()
                        ->first();
                    if ($form) {
                        return $form->fields()
                            ->whereNotIn('type', ['section_header', 'paragraph'])
                            ->orderBy('sort')
                            ->get();
                    }
                }
                $projectForms = Form::projectType()
                    ->published()
                    ->active()
                    ->where('program_id', $currentProgramId)
                    ->get();
                if ($projectForms->isNotEmpty()) {
                    $allFields = collect();
                    foreach ($projectForms as $form) {
                        foreach ($form->fields()->whereNotIn('type', ['section_header', 'paragraph'])->orderBy('sort')->get() as $field) {
                            if (!$allFields->has($field->slug)) {
                                $allFields->put($field->slug, $field);
                            }
                        }
                    }
                    return $allFields->values();
                }
            }
        }

        // Fallback: collect all unique form fields from forms that have at least one project
        $formIds = Project::query()
            ->select('form_id')
            ->distinct()
            ->whereNotNull('form_id')
            ->pluck('form_id')
            ->filter()
            ->unique()
            ->values();

        if ($formIds->isEmpty()) {
            return collect();
        }

        $allFields = collect();
        foreach (Form::whereIn('id', $formIds)->get() as $form) {
            foreach ($form->fields()->whereNotIn('type', ['section_header', 'paragraph'])->orderBy('sort')->get() as $field) {
                if (!$allFields->has($field->slug)) {
                    $allFields->put($field->slug, $field);
                }
            }
        }

        return $allFields->values();
    }

    /**
     * Get human-readable label for a form field (for column header).
     */
    protected static function getFormFieldLabel(FormField $field): string
    {
        $label = $field->label;
        if (is_array($label)) {
            return trim((string) ($label['en'] ?? $label['ar'] ?? $field->slug ?? ''));
        }
        return trim((string) ($label ?? $field->slug ?? ''));
    }

    /**
     * Format a single form submission value for export (option labels, track names, arrays, etc.).
     */
    protected static function formatFormSubmissionValue(Project $record, string $slug, ?FormField $formField): string
    {
        $submissions = $record->form_submissions instanceof \Spatie\SchemalessAttributes\SchemalessAttributes
            ? $record->form_submissions->toArray()
            : (array) $record->form_submissions;
        $value = $submissions[$slug] ?? null;

        if ($value === null || $value === '') {
            return '';
        }

        // Track / sub_track: resolve to display name
        if ($slug === 'track') {
            $track = is_numeric($value)
                ? Track::find((int) $value)
                : Track::where('slug', $value)->first();
            if ($track) {
                $name = $track->name;
                return is_array($name) ? ($name['en'] ?? $name['ar'] ?? $track->slug ?? '') : (string) $name;
            }
            return (string) $value;
        }
        if ($slug === 'sub_track') {
            $subTrack = is_numeric($value)
                ? SubTrack::find((int) $value)
                : SubTrack::where('slug', $value)->first();
            if ($subTrack) {
                $name = $subTrack->name;
                return is_array($name) ? ($name['en'] ?? $name['ar'] ?? $subTrack->slug ?? '') : (string) $name;
            }
            return (string) $value;
        }

        // Array (e.g. multi_select, checkbox group)
        if (is_array($value)) {
            $value = array_map(function ($v) use ($formField) {
                if (is_bool($v)) {
                    return $v ? 'Yes' : 'No';
                }
                // Resolve option keys to labels for checkbox/multi_select
                if ($formField && in_array($formField->type, ['checkbox', 'multi_select'], true)) {
                    $label = static::getOptionLabel($formField, $v);
                    if ($label !== null) {
                        return $label;
                    }
                }
                return (string) $v;
            }, $value);
            return implode(', ', $value);
        }

        // Comma-separated string of IDs (e.g. "1,4,2,3" from checkbox/multi_select)
        if ($formField && in_array($formField->type, ['checkbox', 'multi_select'], true)
            && is_string($value) && str_contains($value, ',')
            && preg_match('/^\s*\d+(\s*,\s*\d+)*\s*$/', trim($value))) {
            $ids = array_map('trim', explode(',', $value));
            $labels = [];
            foreach ($ids as $id) {
                $label = static::getOptionLabel($formField, $id);
                $labels[] = $label !== null ? $label : $id;
            }
            return implode(', ', $labels);
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        // Option-based fields: resolve numeric/index to label
        if ($formField && in_array($formField->type, ['dropdown', 'radio', 'rating', 'checkbox', 'multi_select'], true)) {
            $label = static::getOptionLabel($formField, $value);
            if ($label !== null) {
                return $label;
            }
        }

        if ($formField && $formField->type === 'date' && is_string($value)) {
            try {
                $date = Carbon::parse($value);
                return $date->format('d/m/Y');
            } catch (\Exception $e) {
                return (string) $value;
            }
        }

        if ($formField && $formField->type === 'time' && is_string($value)) {
            try {
                $time = Carbon::parse($value);
                return $time->format('H:i');
            } catch (\Exception $e) {
                return (string) $value;
            }
        }

        // File upload fields: output full URL so the link opens when pasted from the export
        if ($formField && $formField->type === 'file') {
            return static::filePathToExportUrl($value);
        }
        if ($formField && !in_array($formField->type, ['date', 'time'], true) && static::looksLikeStoragePath($value)) {
            return static::filePathToExportUrl($value);
        }

        return (string) $value;
    }

    protected static function looksLikeStoragePath($value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return false;
        }
        return str_contains($value, '/') || str_contains($value, '\\');
    }

    /**
     * Format comment attachments array to full URLs (comma-separated).
     */
    protected static function formatCommentAttachments($attachments): string
    {
        if ($attachments === null || $attachments === '') {
            return '';
        }
        $paths = is_array($attachments) ? $attachments : (array) json_decode((string) $attachments, true);
        if (empty($paths)) {
            return '';
        }
        return static::filePathToExportUrl($paths);
    }

    protected static function filePathToExportUrl($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_array($value)) {
            $urls = array_map(fn ($path) => static::filePathToExportUrl($path), $value);
            return implode(', ', array_filter($urls));
        }
        $path = (string) $value;
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            return URL::to($path);
        }
        return URL::to(Storage::disk('public')->url($path));
    }

    /**
     * Get option label from FormField for a given value.
     */
    protected static function getOptionLabel(FormField $field, $value): ?string
    {
        if (!$field->options || !is_array($field->options)) {
            return null;
        }

        $options = $field->options;
        $currentLang = app()->getLocale();
        $useAr = $currentLang === 'ar';

        if (isset($options['en']) && is_string($options['en']) && isset($options['ar']) && is_string($options['ar'])) {
            $enOpts = FormField::parseOptionsString($options['en']);
            $arOpts = FormField::parseOptionsString($options['ar']);
            if (is_numeric($value)) {
                $idx = (int) $value - 1; // 1-based (e.g. id: 1 -> index 0)
                if ($idx >= 0 && isset($enOpts[$idx])) {
                    return $useAr ? ($arOpts[$idx] ?? $enOpts[$idx]) : $enOpts[$idx];
                }
                $idx = (int) $value; // 0-based fallback (e.g. index 0, 1, 2)
                if (isset($enOpts[$idx])) {
                    return $useAr ? ($arOpts[$idx] ?? $enOpts[$idx]) : $enOpts[$idx];
                }
            } else {
                $pos = array_search($value, $enOpts, true);
                if ($pos !== false) {
                    return $useAr ? ($arOpts[$pos] ?? $enOpts[$pos]) : $enOpts[$pos];
                }
            }
            return null;
        }

        if (isset($options[0]) && is_array($options[0])) {
            if (isset($options[0]['value'])) {
                foreach ($options as $opt) {
                    $optVal = $opt['value'] ?? null;
                    if ((string) $optVal === (string) $value) {
                        return $opt['en'] ?? $opt['ar'] ?? $opt['label']['en'] ?? $opt['label']['ar'] ?? (string) $optVal;
                    }
                }
            }
            if (is_numeric($value)) {
                $idx = (int) $value - 1; // 1-based
                if ($idx >= 0 && isset($options[$idx])) {
                    $opt = $options[$idx];
                    if (is_array($opt)) {
                        return $opt['en'] ?? $opt['ar'] ?? $opt['label']['en'] ?? $opt['label']['ar'] ?? null;
                    }
                }
                $idx = (int) $value; // 0-based fallback
                if (isset($options[$idx])) {
                    $opt = $options[$idx];
                    if (is_array($opt)) {
                        return $opt['en'] ?? $opt['ar'] ?? $opt['label']['en'] ?? $opt['label']['ar'] ?? null;
                    }
                }
            }
        }

        return null;
    }

    public static function getColumns(): array
    {
        $columns = [
            ExportColumn::make('id')->label('Submission ID'),
            ExportColumn::make('responder_name')
                ->label('Responder Name')
                ->getStateUsing(function ($record) {
                    $team = $record->team;
                    if (!$team) {
                        return $record->application?->participant?->name ?? 'N/A';
                    }
                    $leader = optional($team->members)->where('is_leader', true)->first();
                    return optional(optional($leader)->participant)->name ?? 'N/A';
                }),
            ExportColumn::make('team.name')->label('Team Name'),
            ExportColumn::make('team_members')
                ->label('Team Members')
                ->getStateUsing(fn ($record) => $record->team?->members->pluck('participant.name')->filter()->implode(', ') ?? ''),
            ExportColumn::make('team_members_emails')
                ->label("Team Members' Emails")
                ->getStateUsing(fn ($record) => $record->team?->members->pluck('participant.email')->filter()->implode(', ') ?? ''),
            ExportColumn::make('project_name')
                ->label('Project Name')
                ->getStateUsing(fn ($record) => $record->form_submissions['project_name'] ?? ''),
            ExportColumn::make('track')
                ->label('Project Track')
                ->getStateUsing(fn ($record) => static::formatFormSubmissionValue($record, 'track', null)),
            ExportColumn::make('sub_track')
                ->label('Sub-Track')
                ->getStateUsing(fn ($record) => static::formatFormSubmissionValue($record, 'sub_track', null)),
            ExportColumn::make('status')->formatStateUsing(fn ($record) => str($record->status)->headline()),
            ExportColumn::make('created_at')
                ->label('Submitted At')
                ->formatStateUsing(fn ($record) => $record->created_at->format('d/m/Y H:i:s')),
            ExportColumn::make('assigned_judges_count')
                ->label('Assigned Judges Count')
                ->getStateUsing(fn ($record) => $record->judges->count()),
            ExportColumn::make('assigned_judges')
                ->label('Assigned Judges')
                ->getStateUsing(fn ($record) => $record->judges->pluck('name')->implode(', ')),
            ExportColumn::make('assigned_judges_emails')
                ->label('Assigned Judges Emails')
                ->getStateUsing(function ($record) {
                    return $record->judges->map(fn ($judge) => $judge->email ?? '')->filter()->implode(', ');
                }),
            ExportColumn::make('assigned_judges_details')
                ->label('Assigned Judges Details')
                ->getStateUsing(function ($record) {
                    $judges = $record->judges;
                    if ($judges->isEmpty()) {
                        return '';
                    }
                    $lines = [];
                    foreach ($judges as $judge) {
                        $name = is_array($judge->name) ? ($judge->name['en'] ?? $judge->name['ar'] ?? 'Judge') : ($judge->name ?? 'Judge');
                        $email = $judge->email ?? '—';
                        $phone = $judge->phone_number ?? '—';
                        $judgeProjectId = $judge->pivot->id ?? null;
                        $scoreStr = '—';
                        if ($judgeProjectId) {
                            $formScore = \App\Models\FormEvaluationScore::where('judge_project_id', $judgeProjectId)
                                ->where('is_archived', false)
                                ->where('exclude_from_calculation', false)
                                ->first();
                            $scoreStr = $formScore && $formScore->evaluation_score !== null
                                ? round((float) $formScore->evaluation_score, 2) . '%'
                                : '—';
                        }
                        $finalComment = $judge->pivot->final_comment ?? '';
                        $finalComment = $finalComment ? str_replace(["\r\n", "\n", "\r"], ' ', $finalComment) : '—';
                        $lines[] = "{$name} | {$email} | {$phone} | Score: {$scoreStr} | Comment: {$finalComment}";
                    }
                    return implode("\n", $lines);
                }),
            ExportColumn::make('judges_evaluated')
                ->label('Judges Evaluated')
                ->getStateUsing(fn ($record) => $record->judges()->wherePivot('evaluation_score', '!=', 0)->count()),
            ExportColumn::make('evaluation_status')
                ->formatStateUsing(fn ($record) => $record->evaluation_status ? 'Evaluated' : 'Not Evaluated'),
            ExportColumn::make('total_score')
                ->label('Assessment Total Score')
                ->formatStateUsing(fn ($record) => $record->total_score . '%'),
            ExportColumn::make('assessment_scores_by_judge')
                ->label('Assessment Scores (by Judge)')
                ->getStateUsing(function ($record) {
                    $pairs = $record->judges()
                        ->wherePivot('evaluation_score', '!=', null)
                        ->get()
                        ->map(function ($judge) {
                            $score = $judge->pivot->evaluation_score;
                            $name = is_array($judge->name) ? ($judge->name['en'] ?? $judge->name['ar'] ?? 'Judge') : ($judge->name ?? 'Judge');
                            $scoreStr = $score !== null && $score !== '' ? (is_numeric($score) ? round((float) $score, 2) . '%' : $score) : '—';
                            return $name . ': ' . $scoreStr;
                        });
                    return $pairs->implode('; ');
                }),
            ExportColumn::make('ai_evaluation_status')
                ->label('AI Evaluation Status')
                ->getStateUsing(function ($record) {
                    $response = $record->ai_evaluation_response;
                    if (empty($response) || !is_array($response)) {
                        return '';
                    }
                    return data_get($response, 'status') ?? (!empty($response) ? 'completed' : 'pending');
                }),
            ExportColumn::make('ai_evaluation_score')
                ->label('AI Evaluation Score')
                ->getStateUsing(function ($record) {
                    $response = $record->ai_evaluation_response;
                    if (empty($response) || !is_array($response)) {
                        return '';
                    }
                    $normalizedScore = data_get($response, 'meta.normalized_score');
                    $totalScore = data_get($response, 'meta.total_score');
                    $maxWeight = data_get($response, 'meta.max_weight');
                    if ($normalizedScore !== null) {
                        $maxTotal = data_get($response, 'meta.target_total_weight', $maxWeight);
                        return $maxTotal > 0
                            ? round((float) $normalizedScore, 2) . ' / ' . round((float) $maxTotal, 2)
                            : (string) round((float) $normalizedScore, 2);
                    }
                    if ($totalScore !== null) {
                        return $maxWeight > 0
                            ? round((float) $totalScore, 2) . ' / ' . round((float) $maxWeight, 2)
                            : (string) round((float) $totalScore, 2);
                    }
                    $criteria = data_get($response, 'data.criteria', []);
                    $sum = 0;
                    $max = 0;
                    foreach ($criteria as $c) {
                        $sum += (float) (data_get($c, 'totalScore', 0));
                        $max += (float) (data_get($c, 'maxWeight', 0));
                    }
                    return $max > 0 ? round($sum, 2) . ' / ' . round($max, 2) : ($sum > 0 ? (string) round($sum, 2) : '');
                }),
            ExportColumn::make('ai_evaluation_details')
                ->label('AI Evaluation Details')
                ->getStateUsing(function ($record) {
                    $criteria = $record->ai_evaluation_display_criteria ?? [];
                    if (empty($criteria)) {
                        $msg = data_get($record->ai_evaluation_response, 'message');
                        return is_string($msg) ? $msg : '';
                    }
                    $parts = [];
                    foreach ($criteria as $c) {
                        $name = data_get($c, 'name', data_get($c, 'description', '—'));
                        $score = data_get($c, 'totalScore', 0);
                        $max = data_get($c, 'maxWeight', 0);
                        $parts[] = $max > 0 ? "{$name}: {$score}/{$max}" : "{$name}: {$score}";
                    }
                    return implode('; ', $parts);
                }),
            ExportColumn::make('evaluation_details')
                ->label('Evaluation Details (by Judge)')
                ->getStateUsing(function ($record) {
                    $judgeProjects = \App\Models\JudgeProject::where('project_id', $record->id)
                        ->with(['judge', 'evaluations' => fn ($q) => $q->where('is_archived', false)->orderBy('form_id')->orderBy('question')])
                        ->get();
                    if ($judgeProjects->isEmpty()) {
                        return '';
                    }
                    $sections = [];
                    foreach ($judgeProjects as $jp) {
                        $judge = $jp->judge;
                        $judgeName = $judge ? (is_array($judge->name) ? ($judge->name['en'] ?? $judge->name['ar'] ?? 'Judge') : ($judge->name ?? 'Judge')) : 'Judge';
                        $evaluations = $jp->evaluations;
                        if ($evaluations->isEmpty()) {
                            $sections[] = "{$judgeName}: No evaluation submitted";
                            continue;
                        }
                        $byForm = $evaluations->groupBy('form_id');
                        $judgeLines = [];
                        foreach ($byForm as $formId => $evals) {
                            $form = \App\Models\Form::find($formId);
                            $formName = $form ? (is_array($form->name) ? ($form->name['en'] ?? $form->name['ar'] ?? "Form {$formId}") : ($form->name ?? "Form {$formId}")) : "Form {$formId}";
                            $entries = $evals->map(function ($eval) {
                                $q = ucwords(str_replace('_', ' ', $eval->question ?? ''));
                                $a = $eval->answer ?? '—';
                                $c = $eval->comment ? str_replace(["\r\n", "\n", "\r"], ' ', $eval->comment) : '';
                                return $c ? "{$q}: {$a} ({$c})" : "{$q}: {$a}";
                            })->implode(' | ');
                            $judgeLines[] = "[{$formName}] {$entries}";
                        }
                        $sections[] = $judgeName . "\n  " . implode("\n  ", $judgeLines);
                    }
                    return implode("\n---\n", $sections);
                }),
            ExportColumn::make('comments_added')
                ->label('Comments Added')
                ->getStateUsing(function ($record) {
                    $comments = $record->comments()->orderBy('created_at')->get();
                    if ($comments->isEmpty()) {
                        return '';
                    }
                    return $comments->map(function ($c) {
                        $author = ($c->user_id && !$c->author_type)
                            ? ($c->user?->name ?? 'Admin')
                            : ($c->author?->name ?? 'Participant');
                        $date = $c->created_at->format('Y-m-d H:i');
                        $text = str_replace(["\r\n", "\n", "\r"], ' ', $c->comment ?? '');
                        $attachmentPart = static::formatCommentAttachments($c->attachments);
                        return $attachmentPart ? "{$date} | {$author} | {$text} | Attachments: {$attachmentPart}" : "{$date} | {$author} | {$text}";
                    })->implode("\n");
                }),
            ExportColumn::make('comment_attachments')
                ->label('Comment Attachments')
                ->getStateUsing(function ($record) {
                    $allUrls = [];
                    foreach ($record->comments()->orderBy('created_at')->get() as $c) {
                        $urls = static::formatCommentAttachments($c->attachments);
                        if ($urls) {
                            $allUrls[] = $urls;
                        }
                    }
                    return implode("\n", $allUrls);
                }),
        ];

        // Dynamic columns from project form fields (answers from form_submissions)
        $formFields = static::getFormFieldsForExport();
        $excludedSlugs = ['project_name', 'track', 'sub_track'];
        foreach ($formFields as $field) {
            if (in_array($field->slug, $excludedSlugs, true)) {
                continue;
            }
            $slug = $field->slug;
            $label = static::getFormFieldLabel($field);
            $columns[] = ExportColumn::make('form_submissions_' . $slug)
                ->label($label)
                ->getStateUsing(fn ($record) => static::formatFormSubmissionValue($record, $slug, $field));
        }

        return $columns;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your projects export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';
        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }
        return $body;
    }
}
